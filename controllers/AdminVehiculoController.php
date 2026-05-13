<?php
/**
 * AdminVehiculoController.php
 * Controlador legado para compatibilidad — redirige al VehiculoController unificado.
 * Las vistas de admin apuntan directamente a VehiculoController.php.
 */
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Vehiculo.php';

if (!isset($_SESSION['usuario'])) {
    header("Location: ../views/usuarios/login.php"); exit;
}

$rol = (int)($_SESSION['usuario']['rol'] ?? 0);
if ($rol !== 1) {
    header("Location: ../views/usuarios/login.php"); exit;
}

$db     = (new Database())->conectar();
$vModel = new Vehiculo($db);
$accion = $_POST['accion'] ?? ($_GET['accion'] ?? '');

switch ($accion) {

    case 'registrar':
        $placa     = strtoupper(trim($_POST['placa']  ?? ''));
        $marca     = trim($_POST['marca']  ?? '');
        $modelo    = trim($_POST['modelo'] ?? '');
        $anio      = trim($_POST['anio']   ?? ($_POST['año'] ?? ''));
        $idCliente = (int)($_POST['id_cliente'] ?? 0);
        $idEstado  = (int)($_POST['id_estado_vehiculo'] ?? 1);

        if (empty($placa) || empty($marca) || empty($modelo) || !$idCliente) {
            $_SESSION['alert'] = ['icon'=>'warning','title'=>'Campos incompletos','text'=>'Complete todos los campos obligatorios.'];
            header("Location: ../views/dashboard/admin_vehiculos.php"); exit;
        }

        if ($vModel->existePlaca($placa)) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Placa duplicada','text'=>'Ya existe un vehículo con esa placa.'];
            header("Location: ../views/dashboard/admin_vehiculos.php"); exit;
        }

        try {
            $res = $vModel->registrar([
                'id_cliente'         => $idCliente,
                'id_estado_vehiculo' => $idEstado,
                'placa'              => $placa,
                'marca'              => $marca,
                'modelo'             => $modelo,
                'año'                => $anio,
            ]);
            if ($res === true) {
                $_SESSION['alert'] = ['icon'=>'success','title'=>'Registrado','text'=>'Vehículo registrado correctamente.'];
            } else {
                $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>$res];
            }
        } catch (Exception $e) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>$e->getMessage()];
        }
        header("Location: ../views/dashboard/admin_vehiculos.php"); exit;

    case 'actualizar':
        $id       = (int)($_POST['id_vehiculo'] ?? 0);
        $marca    = trim($_POST['marca']  ?? '');
        $modelo   = trim($_POST['modelo'] ?? '');
        $anio     = trim($_POST['anio']   ?? ($_POST['año'] ?? ''));
        $idEstado = (int)($_POST['id_estado_vehiculo'] ?? 0);

        try {
            $datos = ['marca'=>$marca,'modelo'=>$modelo,'año'=>$anio];
            if ($idEstado) $datos['id_estado_vehiculo'] = $idEstado;
            $res = $vModel->actualizar($id, $datos);
            if ($res === true) {
                $_SESSION['alert'] = ['icon'=>'success','title'=>'Actualizado','text'=>'Vehículo actualizado correctamente.'];
            } else {
                $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>$res];
            }
        } catch (Exception $e) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>$e->getMessage()];
        }
        header("Location: ../views/dashboard/admin_vehiculos.php"); exit;

    case 'cambiar_estado':
        $id       = (int)($_POST['id_vehiculo']        ?? 0);
        $idEstado = (int)($_POST['id_estado_vehiculo'] ?? 0);
        try {
            $res = $vModel->cambiarEstado($id, $idEstado);
            $_SESSION['alert'] = $res === true
                ? ['icon'=>'success','title'=>'Estado actualizado','text'=>'El estado fue cambiado.']
                : ['icon'=>'error','title'=>'Error','text'=>$res];
        } catch (Exception $e) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>$e->getMessage()];
        }
        header("Location: ../views/dashboard/admin_vehiculos.php"); exit;

    default:
        header("Location: ../views/dashboard/admin_vehiculos.php"); exit;
}
?>
