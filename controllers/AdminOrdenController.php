<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/OrdenServicio.php';
require_once __DIR__ . '/../models/Vehiculo.php';

if (!isset($_SESSION['usuario'])) {
    header("Location: ../views/usuarios/login.php"); exit;
}

$rol   = $_SESSION['usuario']['rol'];
$db    = (new Database())->conectar();
$model = new OrdenServicio($db);
$accion = $_POST['accion'] ?? ($_GET['accion'] ?? '');

switch ($accion) {

    case 'crear':
        if (!in_array($rol, ['administrador', 'empleado'])) { http_response_code(403); exit; }

        $idVehiculo = (int) ($_POST['id_vehiculo'] ?? 0);
        $idCliente  = (int) ($_POST['id_cliente']  ?? 0);
        $tipo       = trim($_POST['tipo_servicio'] ?? '');

        if (!$idVehiculo || !$idCliente || !$tipo) {
            $_SESSION['alert'] = ['icon'=>'warning','title'=>'Campos incompletos','text'=>'Debe completar todos los campos obligatorios para crear la orden.'];
            header("Location: ../views/dashboard/admin_ordenes.php"); exit;
        }

        // Verificar que el vehículo existe
        $vModel = new Vehiculo($db);
        if (!$vModel->obtenerPorId($idVehiculo)) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Vehículo no encontrado','text'=>'El vehículo ingresado no está registrado en el sistema. Regístrelo primero.'];
            header("Location: ../views/dashboard/admin_ordenes.php"); exit;
        }

        // Verificar orden activa
        if ($model->tieneOrdenActiva($idVehiculo)) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Orden activa','text'=>'Este vehículo ya tiene una orden de servicio activa. Finalícela antes de crear una nueva.'];
            header("Location: ../views/dashboard/admin_ordenes.php"); exit;
        }

        $res = $model->crear([
            'id_cliente'    => $idCliente,
            'id_vehiculo'   => $idVehiculo,
            'id_empleado'   => $_POST['id_empleado'] ?: null,
            'tipo_servicio' => $tipo,
            'descripcion'   => trim($_POST['descripcion'] ?? ''),
            'id_responsable'=> $_SESSION['usuario']['id_usuario'],
        ]);

        if ($res === true) {
            $_SESSION['alert'] = ['icon'=>'success','title'=>'¡Orden creada!','text'=>'Orden de servicio creada exitosamente.'];
        } else {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>$res];
        }
        header("Location: ../views/dashboard/admin_ordenes.php"); exit;

    case 'actualizar_estado':
        if (!in_array($rol, ['administrador', 'empleado'])) { http_response_code(403); exit; }

        $idOrden    = (int) ($_POST['id_orden'] ?? 0);
        $estado     = trim($_POST['estado'] ?? '');
        $observacion= trim($_POST['observacion'] ?? '');

        $res = $model->actualizarEstado($idOrden, $estado, $observacion, $_SESSION['usuario']['id_usuario']);

        if ($res === true) {
            $_SESSION['alert'] = ['icon'=>'success','title'=>'Estado actualizado','text'=>'El estado se actualizó correctamente.'];
        } else {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>$res];
        }
        header("Location: ../views/dashboard/admin_ordenes.php"); exit;

    default:
        header("Location: ../views/dashboard/admin_ordenes.php"); exit;
}
?>
