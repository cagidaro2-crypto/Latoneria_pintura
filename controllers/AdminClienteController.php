<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Solo administrador (rol 1)
if (!isset($_SESSION['usuario']) || (int)($_SESSION['usuario']['rol'] ?? 0) !== 1) {
    header("Location: ../views/usuarios/login.php"); exit;
}

$db     = (new Database())->conectar();
$accion = $_POST['accion'] ?? ($_GET['accion'] ?? '');

switch ($accion) {

    // ── REGISTRAR ────────────────────────────────────────────────────────────
    case 'registrar':
        $nombre   = trim($_POST['nombre']   ?? '');
        $correo   = trim($_POST['correo']   ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($nombre) || empty($correo) || empty($telefono) || empty($password)) {
            $_SESSION['alert'] = ['icon'=>'warning','title'=>'Campos incompletos','text'=>'Complete todos los campos obligatorios.'];
            header("Location: ../views/dashboard/admin_clientes.php"); exit;
        }

        // Verificar correo duplicado
        $stmt = $db->prepare("SELECT id_persona FROM persona WHERE correo = :correo LIMIT 1");
        $stmt->execute([':correo' => $correo]);
        if ($stmt->rowCount() > 0) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Correo duplicado','text'=>'Ya existe un cliente con ese correo.'];
            header("Location: ../views/dashboard/admin_clientes.php"); exit;
        }

        try {
            $db->prepare(
                "INSERT INTO persona (nombre, `contraseña`, correo, telefono, id_rol, activo)
                 VALUES (:nombre, :pass, :correo, :telefono, 2, 1)"
            )->execute([
                ':nombre'   => $nombre,
                ':pass'     => password_hash($password, PASSWORD_DEFAULT),
                ':correo'   => $correo,
                ':telefono' => $telefono,
            ]);
            $_SESSION['alert'] = ['icon'=>'success','title'=>'Registrado','text'=>'Cliente registrado correctamente.'];
        } catch (Exception $e) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>$e->getMessage()];
        }
        header("Location: ../views/dashboard/admin_clientes.php"); exit;

    // ── ACTUALIZAR ───────────────────────────────────────────────────────────
    case 'actualizar':
        $id       = (int)($_POST['id_persona'] ?? 0);
        $nombre   = trim($_POST['nombre']   ?? '');
        $correo   = trim($_POST['correo']   ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (!$id || empty($nombre) || empty($correo) || empty($telefono)) {
            $_SESSION['alert'] = ['icon'=>'warning','title'=>'Campos incompletos','text'=>'Complete todos los campos obligatorios.'];
            header("Location: ../views/dashboard/admin_clientes.php"); exit;
        }

        try {
            if (!empty($password)) {
                $db->prepare(
                    "UPDATE persona SET nombre=:nombre, correo=:correo, telefono=:telefono,
                     `contraseña`=:pass WHERE id_persona=:id"
                )->execute([
                    ':nombre'   => $nombre,
                    ':correo'   => $correo,
                    ':telefono' => $telefono,
                    ':pass'     => password_hash($password, PASSWORD_DEFAULT),
                    ':id'       => $id,
                ]);
            } else {
                $db->prepare(
                    "UPDATE persona SET nombre=:nombre, correo=:correo, telefono=:telefono
                     WHERE id_persona=:id"
                )->execute([
                    ':nombre'   => $nombre,
                    ':correo'   => $correo,
                    ':telefono' => $telefono,
                    ':id'       => $id,
                ]);
            }
            $_SESSION['alert'] = ['icon'=>'success','title'=>'Actualizado','text'=>'Cliente actualizado correctamente.'];
        } catch (Exception $e) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>$e->getMessage()];
        }
        header("Location: ../views/dashboard/admin_clientes.php"); exit;

    // ── TOGGLE ACTIVO/INACTIVO ───────────────────────────────────────────────
    case 'toggle':
        $id     = (int)($_GET['id']     ?? 0);
        $estado = (int)($_GET['estado'] ?? 0); // 0 = desactivar, 1 = activar

        if (!$id) {
            header("Location: ../views/dashboard/admin_clientes.php"); exit;
        }
        try {
            $db->prepare("UPDATE persona SET activo=:activo WHERE id_persona=:id AND id_rol=2")
               ->execute([':activo' => $estado, ':id' => $id]);
            $msg = $estado ? 'Cliente activado correctamente.' : 'Cliente desactivado correctamente.';
            $_SESSION['alert'] = ['icon'=>'success','title'=>'Actualizado','text'=>$msg];
        } catch (Exception $e) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>$e->getMessage()];
        }
        header("Location: ../views/dashboard/admin_clientes.php"); exit;

    // ── ELIMINAR (soft delete) ────────────────────────────────────────────────
    case 'eliminar':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            header("Location: ../views/dashboard/admin_clientes.php"); exit;
        }
        try {
            $db->prepare("UPDATE persona SET activo=0 WHERE id_persona=:id AND id_rol=2")
               ->execute([':id' => $id]);
            $_SESSION['alert'] = ['icon'=>'success','title'=>'Desactivado','text'=>'Cliente desactivado correctamente.'];
        } catch (Exception $e) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>$e->getMessage()];
        }
        header("Location: ../views/dashboard/admin_clientes.php"); exit;

    default:
        header("Location: ../views/dashboard/admin_clientes.php"); exit;
}
?>
