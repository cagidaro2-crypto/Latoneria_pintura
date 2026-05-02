<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Usuario.php';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'administrador') {
    header("Location: ../views/usuarios/login.php");
    exit;
}

$db    = (new Database())->conectar();
$model = new Usuario($db);
$accion = $_GET['accion'] ?? ($_POST['accion'] ?? '');

switch ($accion) {

    case 'registrar':
        $datos = [
            'nombres'        => trim($_POST['nombres']        ?? ''),
            'apellidos'      => trim($_POST['apellidos']      ?? ''),
            'identificacion' => trim($_POST['identificacion'] ?? ''),
            'email'          => trim($_POST['email']          ?? ''),
            'telefono'       => trim($_POST['telefono']       ?? ''),
            'password_hash'  => password_hash(trim($_POST['password'] ?? ''), PASSWORD_DEFAULT),
            'rol'            => $_POST['rol'] ?? 'cliente',
        ];

        if (empty($datos['nombres']) || empty($datos['apellidos']) || empty($datos['identificacion']) || empty($datos['email'])) {
            $_SESSION['alert'] = ['icon'=>'warning','title'=>'Campos incompletos','text'=>'Debe completar todos los campos obligatorios antes de continuar.'];
            header("Location: ../views/dashboard/admin_usuarios.php"); exit;
        }

        if ($model->existeCorreoOIdentificacion($datos['email'], $datos['identificacion'])) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Duplicado','text'=>'Ya existe un usuario registrado con este número de identificación.'];
            header("Location: ../views/dashboard/admin_usuarios.php"); exit;
        }

        $res = $model->registrar($datos);
        if ($res === true) {
            $_SESSION['alert'] = ['icon'=>'success','title'=>'¡Registrado!','text'=>'Empleado registrado exitosamente.'];
        } else {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>$res];
        }
        header("Location: ../views/dashboard/admin_usuarios.php"); exit;

    case 'actualizar':
        $id    = (int) ($_POST['id_usuario'] ?? 0);
        $datos = [
            'nombres'        => trim($_POST['nombres']        ?? ''),
            'apellidos'      => trim($_POST['apellidos']      ?? ''),
            'identificacion' => trim($_POST['identificacion'] ?? ''),
            'telefono'       => trim($_POST['telefono']       ?? ''),
            'rol'            => $_POST['rol'] ?? 'cliente',
        ];

        $pass = trim($_POST['password'] ?? '');
        if (!empty($pass)) {
            if (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $pass)) {
                $_SESSION['alert'] = ['icon'=>'error','title'=>'Contraseña débil','text'=>'Los datos ingresados contienen errores. Por favor corrija los campos señalados.'];
                header("Location: ../views/dashboard/admin_usuarios.php"); exit;
            }
            $datos['password_hash'] = password_hash($pass, PASSWORD_DEFAULT);
        }

        $res = $model->actualizar($id, $datos);
        if ($res === true) {
            $_SESSION['alert'] = ['icon'=>'success','title'=>'Actualizado','text'=>'Información actualizada correctamente.'];
        } else {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>$res];
        }
        header("Location: ../views/dashboard/admin_usuarios.php"); exit;

    case 'eliminar':
        $id  = (int) ($_GET['id'] ?? 0);
        $res = $model->eliminar($id);
        if ($res === true) {
            $_SESSION['alert'] = ['icon'=>'success','title'=>'Eliminado','text'=>'Usuario eliminado correctamente.'];
        } else {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'No permitido','text'=>$res];
        }
        header("Location: ../views/dashboard/admin_usuarios.php"); exit;

    default:
        header("Location: ../views/dashboard/admin_usuarios.php"); exit;
}
?>
