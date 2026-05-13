<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['usuario']) || (int)($_SESSION['usuario']['rol'] ?? 0) !== 1) {
    header("Location: ../views/usuarios/login.php"); exit;
}

$db     = (new Database())->conectar();
$accion = $_POST['accion'] ?? ($_GET['accion'] ?? '');

switch ($accion) {

    case 'registrar':
        $nombre   = trim($_POST['nombre']   ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $correo   = trim($_POST['correo']   ?? '') ?: null;

        if (empty($nombre) || empty($telefono)) {
            $_SESSION['alert'] = ['icon'=>'warning','title'=>'Campos incompletos','text'=>'Nombre y teléfono son obligatorios.'];
            header("Location: ../views/dashboard/admin_proveedores.php"); exit;
        }

        try {
            $db->prepare(
                "INSERT INTO proveedor (nombre, telefono, correo) VALUES (:nombre, :tel, :correo)"
            )->execute([':nombre'=>$nombre, ':tel'=>$telefono, ':correo'=>$correo]);
            $_SESSION['alert'] = ['icon'=>'success','title'=>'Registrado','text'=>'Proveedor registrado correctamente.'];
        } catch (Exception $e) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>$e->getMessage()];
        }
        header("Location: ../views/dashboard/admin_proveedores.php"); exit;

    case 'actualizar':
        $id       = (int)($_POST['id_proveedor'] ?? 0);
        $nombre   = trim($_POST['nombre']   ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $correo   = trim($_POST['correo']   ?? '') ?: null;

        try {
            $db->prepare(
                "UPDATE proveedor SET nombre=:nombre, telefono=:tel, correo=:correo WHERE id_proveedor=:id"
            )->execute([':nombre'=>$nombre,':tel'=>$telefono,':correo'=>$correo,':id'=>$id]);
            $_SESSION['alert'] = ['icon'=>'success','title'=>'Actualizado','text'=>'Proveedor actualizado correctamente.'];
        } catch (Exception $e) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>$e->getMessage()];
        }
        header("Location: ../views/dashboard/admin_proveedores.php"); exit;

    case 'eliminar':
        $id = (int)($_GET['id'] ?? 0);
        try {
            $db->prepare("DELETE FROM proveedor WHERE id_proveedor=:id")->execute([':id'=>$id]);
            $_SESSION['alert'] = ['icon'=>'success','title'=>'Eliminado','text'=>'Proveedor eliminado.'];
        } catch (Exception $e) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>'No se puede eliminar: tiene repuestos asociados.'];
        }
        header("Location: ../views/dashboard/admin_proveedores.php"); exit;

    default:
        header("Location: ../views/dashboard/admin_proveedores.php"); exit;
}
?>
