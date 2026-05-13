<?php
/**
 * VehiculoController.php
 * Controlador unificado para el módulo de vehículos.
 * Maneja acciones de admin (rol 1), empleado (rol 3) y cliente (rol 2).
 */
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Vehiculo.php';

// ── Verificar sesión ──────────────────────────────────────────────────────────
if (!isset($_SESSION['usuario'])) {
    header("Location: ../views/usuarios/login.php");
    exit;
}

$usuario = $_SESSION['usuario'];
$rol     = (int)($usuario['rol'] ?? 0);   // 1=admin, 2=cliente, 3=empleado

// ── Rutas de redirección por rol ──────────────────────────────────────────────
$rutaAdmin    = '../views/dashboard/admin_vehiculos.php';
$rutaEmpleado = '../views/dashboard/empleado_vehiculos.php';
$rutaCliente  = '../views/dashboard/cliente_vehiculos.php';

$rutaRol = match($rol) {
    1       => $rutaAdmin,
    3       => $rutaEmpleado,
    default => $rutaCliente,
};

// ── Conexión y modelo ─────────────────────────────────────────────────────────
$db     = (new Database())->conectar();
$vModel = new Vehiculo($db);

$accion = $_POST['accion'] ?? ($_GET['accion'] ?? '');

switch ($accion) {

    /* ══════════════════════════════════════════════════════════════════════════
     *  REGISTRAR — disponible para admin (rol 1) y cliente (rol 2)
     * ══════════════════════════════════════════════════════════════════════════ */
    case 'registrar':
        if ($rol !== 1 && $rol !== 2) {
            $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Sin permiso', 'text' => 'No tiene permiso para esta acción.'];
            header("Location: {$rutaRol}");
            exit;
        }

        $placa  = strtoupper(trim($_POST['placa']  ?? ''));
        $marca  = trim($_POST['marca']  ?? '');
        $modelo = trim($_POST['modelo'] ?? '');
        $anio   = trim($_POST['anio']   ?? ($_POST['año'] ?? ''));

        if (empty($placa) || empty($marca) || empty($modelo) || empty($anio)) {
            $_SESSION['alert'] = ['icon' => 'warning', 'title' => 'Campos incompletos', 'text' => 'Complete todos los campos obligatorios.'];
            header("Location: {$rutaRol}");
            exit;
        }

        if ($vModel->existePlaca($placa)) {
            $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Placa duplicada', 'text' => 'Ya existe un vehículo con esa placa.'];
            header("Location: {$rutaRol}");
            exit;
        }

        // ── Determinar id_cliente ─────────────────────────────────────────────
        if ($rol === 1) {
            // Admin selecciona el cliente — puede venir de tabla cliente o de persona
            $idClientePost = (int)($_POST['id_cliente'] ?? 0);
            if (!$idClientePost) {
                $_SESSION['alert'] = ['icon' => 'warning', 'title' => 'Cliente requerido', 'text' => 'Seleccione un cliente.'];
                header("Location: {$rutaRol}");
                exit;
            }

            // Verificar si el id existe en tabla cliente
            $stmtCheck = $db->prepare("SELECT id_cliente FROM cliente WHERE id_cliente = :id LIMIT 1");
            $stmtCheck->execute([':id' => $idClientePost]);

            if ($stmtCheck->rowCount() > 0) {
                // Ya existe en tabla cliente — usar directo
                $idCliente = $idClientePost;
            } else {
                // Viene de tabla persona — buscar por correo o crear en cliente
                $stmtP = $db->prepare("SELECT nombre, correo, telefono FROM persona WHERE id_persona = :id LIMIT 1");
                $stmtP->execute([':id' => $idClientePost]);
                $persona = $stmtP->fetch(PDO::FETCH_ASSOC);

                if (!$persona) {
                    $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Cliente no encontrado', 'text' => 'No se encontró el cliente seleccionado.'];
                    header("Location: {$rutaRol}");
                    exit;
                }

                // Buscar si ya tiene registro en cliente por correo
                $idCliente = $vModel->buscarIdClientePorCorreo($persona['correo']);
                if (!$idCliente) {
                    // Crear en tabla cliente
                    $idCliente = $vModel->crearClienteDesdePersona([
                        'nombre'   => $persona['nombre'],
                        'correo'   => $persona['correo'],
                        'telefono' => $persona['telefono'] ?? '',
                    ]);
                }
            }
        } else {
            // Cliente: buscar su registro en tabla cliente por correo de sesión
            $correoSesion = $usuario['correo'] ?? '';
            $idCliente    = $vModel->buscarIdClientePorCorreo($correoSesion);

            if (!$idCliente) {
                // No existe en tabla cliente → crearlo desde datos de sesión
                try {
                    // Obtener datos completos desde persona
                    $stmtP = $db->prepare("SELECT nombre, correo, telefono FROM persona WHERE correo = :correo LIMIT 1");
                    $stmtP->execute([':correo' => $correoSesion]);
                    $persona = $stmtP->fetch(PDO::FETCH_ASSOC);

                    if (!$persona) {
                        $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Error', 'text' => 'No se encontró su perfil de usuario.'];
                        header("Location: {$rutaRol}");
                        exit;
                    }

                    $idCliente = $vModel->crearClienteDesdePersona([
                        'nombre'   => $persona['nombre'],
                        'correo'   => $persona['correo'],
                        'telefono' => $persona['telefono'] ?? '',
                    ]);
                } catch (Exception $e) {
                    $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Error', 'text' => 'No se pudo crear el perfil de cliente: ' . $e->getMessage()];
                    header("Location: {$rutaRol}");
                    exit;
                }
            }
        }

        $idEstado = (int)($_POST['id_estado_vehiculo'] ?? 0);

        // Si no viene estado o es 0, usar el primer estado disponible en la BD
        if (!$idEstado) {
            $stmtEst = $db->query("SELECT id_estado_vehiculo FROM estado_vehiculo ORDER BY id_estado_vehiculo LIMIT 1");
            $firstEst = $stmtEst ? $stmtEst->fetchColumn() : null;
            if (!$firstEst) {
                $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Sin estados', 'text' => 'No hay estados de vehículo configurados. Ejecuta el archivo database/datos_iniciales.sql en tu base de datos.'];
                header("Location: {$rutaRol}");
                exit;
            }
            $idEstado = (int)$firstEst;
        }

        try {
            $res = $vModel->registrar([
                'placa'              => $placa,
                'marca'              => $marca,
                'modelo'             => $modelo,
                'año'                => $anio,
                'id_cliente'         => $idCliente,
                'id_estado_vehiculo' => $idEstado,
            ]);

            if ($res === true) {
                $_SESSION['alert'] = ['icon' => 'success', 'title' => 'Registrado', 'text' => 'Vehículo registrado correctamente.'];
            } else {
                $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Error', 'text' => $res];
            }
        } catch (Exception $e) {
            $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Error', 'text' => $e->getMessage()];
        }

        header("Location: {$rutaRol}");
        exit;

    /* ══════════════════════════════════════════════════════════════════════════
     *  ACTUALIZAR — admin (1) y empleado (3)
     * ══════════════════════════════════════════════════════════════════════════ */
    case 'actualizar':
        if ($rol !== 1 && $rol !== 3) {
            $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Sin permiso', 'text' => 'No tiene permiso para esta acción.'];
            header("Location: {$rutaRol}");
            exit;
        }

        $id     = (int)($_POST['id_vehiculo'] ?? 0);
        $marca  = trim($_POST['marca']  ?? '');
        $modelo = trim($_POST['modelo'] ?? '');
        $anio   = trim($_POST['anio']   ?? ($_POST['año'] ?? ''));
        $idEstado = (int)($_POST['id_estado_vehiculo'] ?? 0);

        if (!$id || empty($marca) || empty($modelo)) {
            $_SESSION['alert'] = ['icon' => 'warning', 'title' => 'Datos incompletos', 'text' => 'Complete los campos requeridos.'];
            header("Location: {$rutaRol}");
            exit;
        }

        try {
            $datos = ['marca' => $marca, 'modelo' => $modelo, 'año' => $anio];
            if ($idEstado) {
                $datos['id_estado_vehiculo'] = $idEstado;
            }
            $res = $vModel->actualizar($id, $datos);

            if ($res === true) {
                $_SESSION['alert'] = ['icon' => 'success', 'title' => 'Actualizado', 'text' => 'Vehículo actualizado correctamente.'];
            } else {
                $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Error', 'text' => $res];
            }
        } catch (Exception $e) {
            $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Error', 'text' => $e->getMessage()];
        }

        header("Location: {$rutaRol}");
        exit;

    /* ══════════════════════════════════════════════════════════════════════════
     *  CAMBIAR ESTADO — admin (1) y empleado (3)
     * ══════════════════════════════════════════════════════════════════════════ */
    case 'cambiar_estado':
        if ($rol !== 1 && $rol !== 3) {
            $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Sin permiso', 'text' => 'No tiene permiso para esta acción.'];
            header("Location: {$rutaRol}");
            exit;
        }

        $id       = (int)($_POST['id_vehiculo']        ?? 0);
        $idEstado = (int)($_POST['id_estado_vehiculo'] ?? 0);

        if (!$id || !$idEstado) {
            $_SESSION['alert'] = ['icon' => 'warning', 'title' => 'Datos incompletos', 'text' => 'Seleccione un estado válido.'];
            header("Location: {$rutaRol}");
            exit;
        }

        try {
            $res = $vModel->cambiarEstado($id, $idEstado);
            if ($res === true) {
                $_SESSION['alert'] = ['icon' => 'success', 'title' => 'Estado actualizado', 'text' => 'El estado del vehículo fue cambiado.'];
            } else {
                $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Error', 'text' => $res];
            }
        } catch (Exception $e) {
            $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Error', 'text' => $e->getMessage()];
        }

        header("Location: {$rutaRol}");
        exit;

    /* ══════════════════════════════════════════════════════════════════════════
     *  AGREGAR HISTORIAL — admin (1) y empleado (3)
     * ══════════════════════════════════════════════════════════════════════════ */
    case 'agregar_historial':
        if ($rol !== 1 && $rol !== 3) {
            $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Sin permiso', 'text' => 'No tiene permiso para esta acción.'];
            header("Location: {$rutaRol}");
            exit;
        }

        $idVehiculo  = (int)($_POST['id_vehiculo']    ?? 0);
        $descripcion = trim($_POST['descripcion']     ?? '');
        $tipoRep     = trim($_POST['tipo_reparacion'] ?? '');
        $fechaReg    = trim($_POST['fecha_registro']  ?? date('Y-m-d'));

        if (!$idVehiculo || empty($descripcion) || empty($tipoRep)) {
            $_SESSION['alert'] = ['icon' => 'warning', 'title' => 'Campos incompletos', 'text' => 'Complete descripción y tipo de reparación.'];
            header("Location: {$rutaRol}");
            exit;
        }

        try {
            $idPersonaActual = (int)($usuario['id_usuario'] ?? 0);

            // Si el admin seleccionó un empleado específico en el formulario, usarlo
            $idPersonaAsignada = (int)($_POST['id_empleado_asignado'] ?? 0);
            if ($idPersonaAsignada) {
                $idPersonaActual = $idPersonaAsignada;
            }

            // 1. Buscar en tabla empleado por id_persona
            $stmtEmp = $db->prepare(
                "SELECT id_empleado FROM empleado WHERE id_persona = :id LIMIT 1"
            );
            $stmtEmp->execute([':id' => $idPersonaActual]);
            $empRow = $stmtEmp->fetch(PDO::FETCH_ASSOC);

            // 2. Si no existe en tabla empleado → crearlo automáticamente
            if (!$empRow && $idPersonaActual) {
                $db->prepare(
                    "INSERT INTO empleado (id_persona) VALUES (:id)"
                )->execute([':id' => $idPersonaActual]);
                $idEmpleado = (int)$db->lastInsertId();
            } elseif ($empRow) {
                $idEmpleado = (int)$empRow['id_empleado'];
            } else {
                $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Error', 'text' => 'No se pudo identificar al empleado. Verifica que tu sesión esté activa.'];
                header("Location: {$rutaRol}");
                exit;
            }

            $res = $vModel->agregarHistorial([
                'descripcion'     => $descripcion,
                'fecha_registro'  => $fechaReg,
                'tipo_reparacion' => $tipoRep,
                'id_empleado'     => $idEmpleado,
                'id_vehiculo'     => $idVehiculo,
            ]);

            if ($res === true) {
                $_SESSION['alert'] = ['icon' => 'success', 'title' => 'Historial agregado', 'text' => 'Entrada registrada correctamente.'];
            } else {
                $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Error', 'text' => $res];
            }
        } catch (Exception $e) {
            $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Error', 'text' => $e->getMessage()];
        }

        header("Location: {$rutaRol}");
        exit;

    /* ══════════════════════════════════════════════════════════════════════════
     *  DEFAULT — redirigir a la vista correspondiente
     * ══════════════════════════════════════════════════════════════════════════ */
    default:
        header("Location: {$rutaRol}");
        exit;
}
?>
