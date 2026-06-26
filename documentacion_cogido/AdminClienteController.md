# Documentación: AdminClienteController.php

## Descripción general
Controlador CRUD para la gestión de clientes por parte del administrador. Permite registrar, actualizar, activar/desactivar y hacer soft-delete de usuarios con rol de cliente (id_rol = 3). Trabaja directamente con la tabla `usuarios` sin pasar por un modelo. Solo accesible para usuarios con rol 1 (administrador).

## Dependencias
- `config/database.php` — Conexión PDO a MySQL

## Código documentado línea por línea

```php
// Línea 1: Apertura del bloque PHP
<?php

// Línea 2: Inicia la sesión para poder leer $_SESSION
session_start();

// Línea 3: Incluye la clase de conexión a la base de datos
require_once __DIR__ . '/../config/database.php';

// Líneas 5-7: Guard de seguridad — solo permite acceso al administrador (rol 1)
// Si no hay sesión o el rol no es 1, redirige al login
if (!isset($_SESSION['usuario']) || (int)($_SESSION['usuario']['rol'] ?? 0) !== 1) {
    header("Location: ../views/usuarios/login.php"); exit;
}

// Línea 9: Crea la conexión a la base de datos
$db     = (new Database())->conectar();

// Línea 10: Obtiene la acción desde POST o GET (prioridad POST)
$accion = $_POST['accion'] ?? ($_GET['accion'] ?? '');

// Línea 12: Inicio del switch que despacha a cada operación CRUD
switch ($accion) {

    // ── CASO: REGISTRAR ──────────────────────────────────────────────────

    // Línea 15: Acción para crear un nuevo cliente
    case 'registrar':
        // Líneas 16-20: Obtiene y limpia los campos del formulario
        $nombres  = trim($_POST['nombres']  ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $correo   = trim($_POST['correo']   ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $password = trim($_POST['password'] ?? '');

        // Líneas 22-25: Valida que todos los campos estén completos
        if (empty($nombres) || empty($apellidos) || empty($correo) || empty($telefono) || empty($password)) {
            $_SESSION['alert'] = ['icon'=>'warning','title'=>'Campos incompletos','text'=>'Complete todos los campos obligatorios.'];
            header("Location: ../views/dashboard/admin_clientes.php"); exit;
        }

        // Líneas 27-31: Verifica que el correo no esté ya registrado
        $stmt = $db->prepare("SELECT id_usuario FROM usuarios WHERE correo = :correo LIMIT 1");
        $stmt->execute([':correo' => $correo]);
        if ($stmt->rowCount() > 0) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Correo duplicado','text'=>'Ya existe un cliente con ese correo.'];
            header("Location: ../views/dashboard/admin_clientes.php"); exit;
        }

        // Líneas 33-44: Inserta el nuevo cliente en la tabla usuarios con rol 3 y activo=1
        // La contraseña se hashea con bcrypt antes de guardar
        try {
            $db->prepare(
                "INSERT INTO usuarios (id_rol, nombres, apellidos, password, correo, telefono, activo)
                 VALUES (3, :nombres, :apellidos, :pass, :correo, :telefono, 1)"
            )->execute([...]);
            $_SESSION['alert'] = ['icon'=>'success','title'=>'Registrado','text'=>'Cliente registrado correctamente.'];
        } catch (Exception $e) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>$e->getMessage()];
        }
        header("Location: ../views/dashboard/admin_clientes.php"); exit;

    // ── CASO: ACTUALIZAR ─────────────────────────────────────────────────

    // Línea 48: Acción para editar los datos de un cliente existente
    case 'actualizar':
        // Líneas 49-55: Obtiene y limpia todos los campos del formulario de edición
        $id        = (int)($_POST['id_usuario'] ?? 0);
        $nombres   = trim($_POST['nombres']  ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $correo    = trim($_POST['correo']   ?? '');
        $telefono  = trim($_POST['telefono'] ?? '');
        $password  = trim($_POST['password'] ?? '');

        // Líneas 57-60: Valida que los campos requeridos estén presentes
        if (!$id || empty($nombres) || empty($apellidos) || empty($correo) || empty($telefono)) {
            $_SESSION['alert'] = ['icon'=>'warning','title'=>'Campos incompletos',...];
            header("Location: ../views/dashboard/admin_clientes.php"); exit;
        }

        // Líneas 62-78: Actualiza el registro. Si se envió contraseña la hashea y la actualiza;
        // si no se envió contraseña, actualiza solo los datos básicos
        try {
            if (!empty($password)) {
                // UPDATE incluyendo password hasheado
            } else {
                // UPDATE sin cambiar contraseña
            }
            $_SESSION['alert'] = ['icon'=>'success','title'=>'Actualizado',...];
        } catch (Exception $e) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>$e->getMessage()];
        }
        header("Location: ../views/dashboard/admin_clientes.php"); exit;

    // ── CASO: TOGGLE (ACTIVAR/DESACTIVAR) ────────────────────────────────

    // Línea 82: Acción para cambiar el estado activo/inactivo de un cliente
    case 'toggle':
        // Línea 83: Obtiene el ID del cliente desde GET
        $id     = (int)($_GET['id']     ?? 0);
        // Línea 84: 0=desactivar, 1=activar
        $estado = (int)($_GET['estado'] ?? 0);

        // Líneas 86-87: Si no hay ID válido, redirige sin hacer nada
        if (!$id) {
            header("Location: ../views/dashboard/admin_clientes.php"); exit;
        }

        // Líneas 88-94: Actualiza el campo 'activo' solo para usuarios con rol 3 (clientes)
        try {
            $db->prepare("UPDATE usuarios SET activo=:activo WHERE id_usuario=:id AND id_rol=3")
               ->execute([':activo' => $estado, ':id' => $id]);
            $msg = $estado ? 'Cliente activado correctamente.' : 'Cliente desactivado correctamente.';
            $_SESSION['alert'] = ['icon'=>'success','title'=>'Actualizado','text'=>$msg];
        } catch (Exception $e) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>$e->getMessage()];
        }
        header("Location: ../views/dashboard/admin_clientes.php"); exit;

    // ── CASO: ELIMINAR (soft delete) ──────────────────────────────────────

    // Línea 98: Acción de "eliminación" que en realidad desactiva el cliente (activo=0)
    case 'eliminar':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            header("Location: ../views/dashboard/admin_clientes.php"); exit;
        }
        // Líneas 104-108: No borra el registro; solo pone activo=0 (soft delete)
        try {
            $db->prepare("UPDATE usuarios SET activo=0 WHERE id_usuario=:id AND id_rol=3")
               ->execute([':id' => $id]);
            $_SESSION['alert'] = ['icon'=>'success','title'=>'Desactivado',...];
        } catch (Exception $e) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>$e->getMessage()];
        }
        header("Location: ../views/dashboard/admin_clientes.php"); exit;

    // Línea 113: Caso por defecto — redirige a la lista de clientes
    default:
        header("Location: ../views/dashboard/admin_clientes.php"); exit;
}
?>
```

## Resumen de funciones/métodos

| Acción (switch) | Fuente datos | Operación BD | Descripción |
|----------------|-------------|-------------|-------------|
| `registrar` | `$_POST` | INSERT usuarios | Crea nuevo cliente con rol 3 |
| `actualizar` | `$_POST` | UPDATE usuarios | Edita datos del cliente (con o sin cambio de contraseña) |
| `toggle` | `$_GET` | UPDATE activo | Activa o desactiva el cliente |
| `eliminar` | `$_GET` | UPDATE activo=0 | Soft delete: marca como inactivo sin borrar |

## Flujo de ejecución
1. El archivo es llamado desde los formularios de `admin_clientes.php`.
2. Verifica que el usuario de sesión sea administrador (rol 1).
3. Crea la conexión a la BD.
4. Lee la acción desde `$_POST['accion']` o `$_GET['accion']`.
5. El `switch` ejecuta la lógica correspondiente:
   - **registrar**: valida → verifica duplicado → inserta con password_hash.
   - **actualizar**: valida → actualiza (con o sin nueva contraseña).
   - **toggle**: actualiza campo `activo` (0 o 1).
   - **eliminar**: pone `activo=0` sin borrar el registro.
6. En todos los casos guarda un mensaje de alerta en sesión y redirige a `admin_clientes.php`.
