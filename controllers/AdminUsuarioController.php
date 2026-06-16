<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Usuario.php';

// Solo administrador (rol 1)
if (!isset($_SESSION['usuario']) || (int)($_SESSION['usuario']['rol'] ?? 0) !== 1) {
    header("Location: ../views/usuarios/login.php");
    exit;
}

$db     = (new Database())->conectar();
$accion = $_GET['accion'] ?? ($_POST['accion'] ?? '');

switch ($accion) {

    // ── REGISTRAR ────────────────────────────────────────────────────────────
    case 'registrar':
        $nombre   = trim($_POST['nombre']   ?? '');
        $correo   = trim($_POST['correo']   ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $idRol    = (int)($_POST['id_rol']  ?? 2);

        $nombres  = trim($_POST['nombres']  ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');

        if (empty($nombres) || empty($apellidos) || empty($correo) || empty($telefono) || empty($password)) {
            $_SESSION['alert'] = ['icon'=>'warning','title'=>'Campos incompletos','text'=>'Complete todos los campos obligatorios.'];
            header("Location: ../views/dashboard/admin_usuarios.php"); exit;
        }

        // Verificar correo duplicado
        $stmt = $db->prepare("SELECT id_usuario FROM usuarios WHERE correo = :correo LIMIT 1");
        $stmt->execute([':correo' => $correo]);
        if ($stmt->rowCount() > 0) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Correo duplicado','text'=>'Ya existe un usuario con ese correo.'];
            header("Location: ../views/dashboard/admin_usuarios.php"); exit;
        }

        try {
            $db->prepare(
                "INSERT INTO usuarios (nombres, apellidos, password, correo, telefono, id_rol, activo)
                 VALUES (:nombres, :apellidos, :pass, :correo, :telefono, :id_rol, 1)"
            )->execute([
                ':nombres'   => $nombres,
                ':apellidos' => $apellidos,
                ':pass'      => password_hash($password, PASSWORD_DEFAULT),
                ':correo'    => $correo,
                ':telefono'  => $telefono,
                ':id_rol'    => $idRol,
            ]);
            $_SESSION['alert'] = ['icon'=>'success','title'=>'Registrado','text'=>'Usuario registrado correctamente.'];
        } catch (Exception $e) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>$e->getMessage()];
        }
        header("Location: ../views/dashboard/admin_usuarios.php"); exit;

    // ── ACTUALIZAR ───────────────────────────────────────────────────────────
    case 'actualizar':
        $id        = (int)($_POST['id_usuario'] ?? 0);
        $nombres   = trim($_POST['nombres']  ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $correo    = trim($_POST['correo']   ?? '');
        $telefono  = trim($_POST['telefono'] ?? '');
        $idRol     = (int)($_POST['id_rol']  ?? 2);
        $password  = trim($_POST['password'] ?? '');

        if (!$id || empty($nombres) || empty($apellidos) || empty($correo) || empty($telefono)) {
            $_SESSION['alert'] = ['icon'=>'warning','title'=>'Campos incompletos','text'=>'Complete todos los campos obligatorios.'];
            header("Location: ../views/dashboard/admin_usuarios.php"); exit;
        }

        try {
            if (!empty($password)) {
                $db->prepare(
                    "UPDATE usuarios SET nombres=:nombres, apellidos=:apellidos, correo=:correo, telefono=:telefono,
                     id_rol=:id_rol, password=:pass WHERE id_usuario=:id"
                )->execute([
                    ':nombres'   => $nombres,
                    ':apellidos' => $apellidos,
                    ':correo'    => $correo,
                    ':telefono'  => $telefono,
                    ':id_rol'    => $idRol,
                    ':pass'      => password_hash($password, PASSWORD_DEFAULT),
                    ':id'        => $id,
                ]);
            } else {
                $db->prepare(
                    "UPDATE usuarios SET nombres=:nombres, apellidos=:apellidos, correo=:correo, telefono=:telefono,
                     id_rol=:id_rol WHERE id_usuario=:id"
                )->execute([
                    ':nombres'   => $nombres,
                    ':apellidos' => $apellidos,
                    ':correo'    => $correo,
                    ':telefono'  => $telefono,
                    ':id_rol'    => $idRol,
                    ':id'        => $id,
                ]);
            }
            $_SESSION['alert'] = ['icon'=>'success','title'=>'Actualizado','text'=>'Usuario actualizado correctamente.'];
        } catch (Exception $e) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>$e->getMessage()];
        }
        header("Location: ../views/dashboard/admin_usuarios.php"); exit;

    // ── ELIMINAR (soft delete) ────────────────────────────────────────────────
    case 'eliminar':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            header("Location: ../views/dashboard/admin_usuarios.php"); exit;
        }
        try {
            $db->prepare("UPDATE usuarios SET activo=0 WHERE id_usuario=:id")
               ->execute([':id' => $id]);
            $_SESSION['alert'] = ['icon'=>'success','title'=>'Eliminado','text'=>'Usuario desactivado correctamente.'];
        } catch (Exception $e) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>$e->getMessage()];
        }
        header("Location: ../views/dashboard/admin_usuarios.php"); exit;

    default:
        header("Location: ../views/dashboard/admin_usuarios.php"); exit;
}
?>
