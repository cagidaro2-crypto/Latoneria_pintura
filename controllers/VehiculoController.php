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
$rol     = (int)($usuario['rol'] ?? 0);   // 1=admin, 2=empleado, 3=cliente

// ── Rutas de redirección por rol ──────────────────────────────────────────────
$rutaAdmin    = '../views/dashboard/admin_vehiculos.php';
$rutaEmpleado = '../views/dashboard/empleado_vehiculos.php';
$rutaCliente  = '../views/dashboard/cliente_vehiculos.php';

$rutaRol = match($rol) {
    1       => $rutaAdmin,
    2       => $rutaEmpleado,
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
        if ($rol !== 1 && $rol !== 3) {
            $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Sin permiso', 'text' => 'No tiene permiso para esta acción.'];
            header("Location: {$rutaRol}");
            exit;
        }

        $placa  = strtoupper(trim($_POST['placa']  ?? ''));
        $marca  = trim($_POST['marca']  ?? '');
        $modelo = trim($_POST['modelo'] ?? '');
        $anio   = trim($_POST['anio']   ?? ($_POST['año'] ?? ''));
        $color  = trim($_POST['color']  ?? '');

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
            $idClientePost = (int)($_POST['id_cliente'] ?? 0);
            if (!$idClientePost) {
                $_SESSION['alert'] = ['icon' => 'warning', 'title' => 'Cliente requerido', 'text' => 'Seleccione un cliente.'];
                header("Location: {$rutaRol}"); exit;
            }
            // Verificar en tabla clientes (schema nuevo)
            $stmtCheck = $db->prepare("SELECT id_cliente FROM clientes WHERE id_cliente = :id LIMIT 1");
            $stmtCheck->execute([':id' => $idClientePost]);
            $idCliente = $stmtCheck->rowCount() > 0 ? $idClientePost : null;
            if (!$idCliente) {
                $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Cliente no encontrado', 'text' => 'El cliente seleccionado no existe.'];
                header("Location: {$rutaRol}"); exit;
            }
        } else {
            // Cliente registrado: buscar en tabla clientes por correo
            $correoSesion = $usuario['correo'] ?? '';
            $stmtCli = $db->prepare("SELECT id_cliente FROM clientes WHERE correo = :c LIMIT 1");
            $stmtCli->execute([':c' => $correoSesion]);
            $idCliente = (int)($stmtCli->fetchColumn() ?: 0);

            if (!$idCliente) {
                // Crear entrada en clientes si no existe
                try {
                    $db->prepare(
                        "INSERT INTO clientes (nombres, apellidos, correo, telefono)
                         SELECT nombres, apellidos, correo, telefono FROM usuarios WHERE correo = :c LIMIT 1"
                    )->execute([':c' => $correoSesion]);
                    $idCliente = (int)$db->lastInsertId();
                } catch (Exception $e) {
                    $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Error', 'text' => 'No se pudo vincular el perfil de cliente.'];
                    header("Location: {$rutaRol}"); exit;
                }
            }
        }

        $idEstado = (int)($_POST['id_estado_vehiculo'] ?? 0);
        if (!$idEstado) {
            $firstEst = $db->query("SELECT id_estado_vehiculo FROM estado_vehiculo ORDER BY id_estado_vehiculo LIMIT 1")->fetchColumn();
            $idEstado = $firstEst ? (int)$firstEst : 1;
        }

        try {
            // Insertar en tabla vehiculos (schema nuevo)
            $stmtIns = $db->prepare(
                "INSERT INTO vehiculos (placa, marca, modelo, anio, color, id_cliente, id_estado)
                 VALUES (:placa, :marca, :modelo, :anio, :color, :id_cliente, :id_estado)"
            );
            $stmtIns->execute([
                ':placa'      => $placa,
                ':marca'      => $marca,
                ':modelo'     => $modelo,
                ':anio'       => $anio,
                ':color'      => $color,
                ':id_cliente' => $idCliente,
                ':id_estado'  => $idEstado,
            ]);
            $idVehiculo = (int)$db->lastInsertId();

            // ── Subir foto si se adjuntó ──────────────────────────────────
            if (!empty($_FILES['foto']['name']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $ext      = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
                $allowed  = ['jpg','jpeg','png','webp'];
                if (in_array($ext, $allowed)) {
                    $dir      = __DIR__ . '/../public/uploads/vehiculos/';
                    if (!is_dir($dir)) mkdir($dir, 0775, true);
                    $filename = 'v_' . $idVehiculo . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($_FILES['foto']['tmp_name'], $dir . $filename)) {
                        $db->prepare(
                            "INSERT INTO vehiculo_fotos (id_vehiculo, id_usuario, nombre_archivo, ruta_archivo, etapa)
                             VALUES (:iv, :iu, :nombre, :ruta, 'antes')"
                        )->execute([
                            ':iv'     => $idVehiculo,
                            ':iu'     => (int)($usuario['id_usuario'] ?? 0),
                            ':nombre' => $filename,
                            ':ruta'   => 'uploads/vehiculos/' . $filename,
                        ]);
                    }
                }
            }

            $_SESSION['alert'] = ['icon' => 'success', 'title' => '¡Registrado!', 'text' => 'Vehículo registrado correctamente.'];
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
            $idUsuarioActual = (int)($usuario['id_usuario'] ?? 0);

            // Si el admin seleccionó un empleado específico en el formulario, usarlo
            $idEmpleadoSeleccionado = (int)($_POST['id_empleado_asignado'] ?? 0);
            $idUsuarioSeleccionado = $idEmpleadoSeleccionado ?: $idUsuarioActual;

            $stmtEmp = $db->prepare(
                "SELECT id_usuario FROM usuarios WHERE id_usuario = :id AND id_rol = 2 LIMIT 1"
            );
            $stmtEmp->execute([':id' => $idUsuarioSeleccionado]);
            $empRow = $stmtEmp->fetch(PDO::FETCH_ASSOC);

            if (!$empRow) {
                $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Error', 'text' => 'No se pudo identificar al empleado. Verifica que tu sesión esté activa.'];
                header("Location: {$rutaRol}");
                exit;
            }

            $idEmpleado = (int)$empRow['id_usuario'];

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
