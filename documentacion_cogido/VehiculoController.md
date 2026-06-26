# Documentación: VehiculoController.php

## Descripción general
Controlador unificado para el módulo de vehículos. Gestiona el registro, actualización, cambio de estado y adición de entradas al historial de servicios. Soporta los tres roles del sistema (admin=1, empleado=2, cliente=3) con lógica de permisos y redirección diferenciada para cada uno. Incluye soporte para subida de fotos al registrar un vehículo.

## Dependencias
- `config/database.php` — Conexión PDO a MySQL
- `models/Vehiculo.php` — Modelo con operaciones CRUD sobre vehículos e historial

## Código documentado línea por línea

```php
// Línea 1-4: Encabezado con descripción del controlador
<?php
/**
 * VehiculoController.php
 * Controlador unificado para el módulo de vehículos.
 * Maneja acciones de admin (rol 1), empleado (rol 3) y cliente (rol 2).
 */

// Línea 5: Inicia la sesión
session_start();

// Líneas 6-7: Incluye dependencias (BD y modelo)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Vehiculo.php';

// Líneas 9-12: Guard de seguridad — si no hay sesión activa, redirige al login
if (!isset($_SESSION['usuario'])) {
    header("Location: ../views/usuarios/login.php");
    exit;
}

// Líneas 14-15: Obtiene los datos del usuario y su rol numérico de la sesión
$usuario = $_SESSION['usuario'];
$rol     = (int)($usuario['rol'] ?? 0);   // 1=admin, 2=empleado, 3=cliente

// Líneas 17-21: Define las rutas de redirección para cada rol
$rutaAdmin    = '../views/dashboard/admin_vehiculos.php';
$rutaEmpleado = '../views/dashboard/empleado_vehiculos.php';
$rutaCliente  = '../views/dashboard/cliente_vehiculos.php';

// Línea 23-25: Selecciona la ruta correcta usando match() según el rol
$rutaRol = match($rol) {
    1       => $rutaAdmin,
    2       => $rutaEmpleado,
    default => $rutaCliente,
};

// Líneas 27-29: Crea la conexión y el modelo de vehículo
$db     = (new Database())->conectar();
$vModel = new Vehiculo($db);

// Línea 31: Obtiene la acción (POST tiene prioridad sobre GET)
$accion = $_POST['accion'] ?? ($_GET['accion'] ?? '');

// Línea 33: Inicio del switch de acciones
switch ($accion) {

    // ── CASO: REGISTRAR ──────────────────────────────────────────────────

    // Línea 36-40: Solo admin (1) y cliente (3) pueden registrar vehículos
    case 'registrar':
        if ($rol !== 1 && $rol !== 3) {
            $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Sin permiso',...];
            header("Location: {$rutaRol}"); exit;
        }

        // Líneas 42-46: Obtiene y limpia los campos básicos del formulario
        $placa  = strtoupper(trim($_POST['placa']  ?? ''));
        $marca  = trim($_POST['marca']  ?? '');
        $modelo = trim($_POST['modelo'] ?? '');
        $anio   = trim($_POST['anio']   ?? ($_POST['año'] ?? ''));
        $color  = trim($_POST['color']  ?? '');

        // Líneas 48-51: Valida que los campos obligatorios estén completos
        if (empty($placa) || empty($marca) || empty($modelo) || empty($anio)) {
            $_SESSION['alert'] = ['icon' => 'warning', 'title' => 'Campos incompletos',...];
            header("Location: {$rutaRol}"); exit;
        }

        // Líneas 53-56: Verifica que la placa no esté ya registrada en el sistema
        if ($vModel->existePlaca($placa)) {
            $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Placa duplicada',...];
            header("Location: {$rutaRol}"); exit;
        }

        // Líneas 58-85: Determina el id_cliente según el rol
        if ($rol === 1) {
            // Admin selecciona el cliente del formulario
            $idClientePost = (int)($_POST['id_cliente'] ?? 0);
            // Verifica que el cliente existe en la tabla clientes
            $stmtCheck = $db->prepare("SELECT id_cliente FROM clientes WHERE id_cliente = :id LIMIT 1");
            $stmtCheck->execute([':id' => $idClientePost]);
            $idCliente = $stmtCheck->rowCount() > 0 ? $idClientePost : null;
        } else {
            // Cliente: busca su id_cliente por correo de sesión
            $correoSesion = $usuario['correo'] ?? '';
            $stmtCli = $db->prepare("SELECT id_cliente FROM clientes WHERE correo = :c LIMIT 1");
            $stmtCli->execute([':c' => $correoSesion]);
            $idCliente = (int)($stmtCli->fetchColumn() ?: 0);

            // Si no existe en tabla clientes, lo crea desde su registro en usuarios
            if (!$idCliente) {
                $db->prepare("INSERT INTO clientes (nombres, apellidos, correo, telefono)
                    SELECT nombres, apellidos, correo, telefono FROM usuarios WHERE correo = :c LIMIT 1")
                   ->execute([':c' => $correoSesion]);
                $idCliente = (int)$db->lastInsertId();
            }
        }

        // Líneas 87-92: Determina el estado inicial del vehículo (primer estado disponible si no se indicó)
        $idEstado = (int)($_POST['id_estado_vehiculo'] ?? 0);
        if (!$idEstado) {
            $firstEst = $db->query("SELECT id_estado_vehiculo FROM estado_vehiculo ORDER BY id_estado_vehiculo LIMIT 1")->fetchColumn();
            $idEstado = $firstEst ? (int)$firstEst : 1;
        }

        // Líneas 94-127: Inserta el vehículo y opcionalmente sube y registra la foto adjunta
        try {
            $stmtIns = $db->prepare("INSERT INTO vehiculos (placa, marca, modelo, anio, color, id_cliente, id_estado) VALUES (...)");
            $stmtIns->execute([...]);
            $idVehiculo = (int)$db->lastInsertId();

            // Si se adjuntó una foto válida (jpg/jpeg/png/webp), la mueve al directorio uploads
            if (!empty($_FILES['foto']['name']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg','jpeg','png','webp'])) {
                    $filename = 'v_' . $idVehiculo . '_' . time() . '.' . $ext;
                    move_uploaded_file($_FILES['foto']['tmp_name'], $dir . $filename);
                    // Registra la foto en la tabla vehiculo_fotos con etapa 'antes'
                    $db->prepare("INSERT INTO vehiculo_fotos (...) VALUES (...)")->execute([...]);
                }
            }
            $_SESSION['alert'] = ['icon' => 'success', 'title' => '¡Registrado!',...];
        } catch (Exception $e) {
            $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Error', 'text' => $e->getMessage()];
        }
        header("Location: {$rutaRol}"); exit;

    // ── CASO: ACTUALIZAR ─────────────────────────────────────────────────

    // Línea 131: Solo admin (1) y empleado (2) pueden actualizar datos del vehículo
    case 'actualizar':
        if ($rol !== 1 && $rol !== 3) { /* Permiso denegado */ }

        $id     = (int)($_POST['id_vehiculo'] ?? 0);
        $marca  = trim($_POST['marca']  ?? '');
        $modelo = trim($_POST['modelo'] ?? '');
        $anio   = trim($_POST['anio']   ?? '');
        $idEstado = (int)($_POST['id_estado_vehiculo'] ?? 0);

        // Valida datos básicos, llama a $vModel->actualizar() y redirige
        ...
        header("Location: {$rutaRol}"); exit;

    // ── CASO: CAMBIAR ESTADO ─────────────────────────────────────────────

    // Línea 165: Solo admin (1) y empleado (2) pueden cambiar el estado
    case 'cambiar_estado':
        $id       = (int)($_POST['id_vehiculo']        ?? 0);
        $idEstado = (int)($_POST['id_estado_vehiculo'] ?? 0);
        // Valida, llama a $vModel->cambiarEstado() y redirige
        ...
        header("Location: {$rutaRol}"); exit;

    // ── CASO: AGREGAR HISTORIAL ──────────────────────────────────────────

    // Línea 195: Solo admin (1) y empleado (2) pueden agregar entradas al historial
    case 'agregar_historial':
        $idVehiculo  = (int)($_POST['id_vehiculo']    ?? 0);
        $descripcion = trim($_POST['descripcion']     ?? '');
        $tipoRep     = trim($_POST['tipo_reparacion'] ?? '');
        $fechaReg    = trim($_POST['fecha_registro']  ?? date('Y-m-d'));

        // Verifica que el empleado asignado exista en la tabla usuarios con rol 2
        $stmtEmp = $db->prepare("SELECT id_usuario FROM usuarios WHERE id_usuario=:id AND id_rol=2 LIMIT 1");
        $stmtEmp->execute([':id' => $idUsuarioSeleccionado]);

        // Llama a $vModel->agregarHistorial() con todos los datos
        $res = $vModel->agregarHistorial([...]);
        header("Location: {$rutaRol}"); exit;

    // Línea 247: Caso por defecto — redirige a la vista del rol
    default:
        header("Location: {$rutaRol}"); exit;
}
?>
```

## Resumen de funciones/métodos

| Acción | Roles permitidos | Operación | Descripción |
|--------|-----------------|-----------|-------------|
| `registrar` | Admin (1), Cliente (3) | INSERT vehiculos + vehiculo_fotos | Crea vehículo con foto opcional |
| `actualizar` | Admin (1), Empleado (2) | UPDATE vehiculos | Edita datos básicos del vehículo |
| `cambiar_estado` | Admin (1), Empleado (2) | UPDATE id_estado | Cambia el estado del vehículo |
| `agregar_historial` | Admin (1), Empleado (2) | INSERT historial_vehiculo | Agrega registro de trabajo realizado |

## Flujo de ejecución
1. Inicia sesión y verifica que el usuario esté autenticado.
2. Determina el rol y la ruta de redirección correspondiente.
3. Crea la conexión a la BD y el modelo Vehiculo.
4. Lee la acción solicitada.
5. Para cada acción, verifica permisos por rol antes de ejecutar.
6. **registrar**: valida campos → verifica placa → resuelve id_cliente → inserta vehículo → sube foto si existe.
7. **actualizar**: valida → llama al modelo para actualizar.
8. **cambiar_estado**: valida → llama al modelo para actualizar el estado.
9. **agregar_historial**: valida → verifica que el empleado exista → llama al modelo.
10. Guarda alerta en sesión y redirige a la vista del rol activo.
