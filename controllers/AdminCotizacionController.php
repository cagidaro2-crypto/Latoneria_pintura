<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Verificar sesión — rol guardado como int: 1=admin, 3=empleado
if (!isset($_SESSION['usuario']) || !in_array((int)($_SESSION['usuario']['rol'] ?? 0), [1, 3])) {
    header("Location: ../views/usuarios/login.php"); exit;
}

$db     = (new Database())->conectar();
$accion = $_POST['accion'] ?? ($_GET['accion'] ?? '');

switch ($accion) {

    case 'crear':
        $idVehiculo = (int)($_POST['id_vehiculo'] ?? 0);
        $fecha      = $_POST['fecha'] ?? date('Y-m-d');
        $servicios  = $_POST['servicios']     ?? [];
        $cantidades = $_POST['serv_cantidad'] ?? [];
        $precios    = $_POST['serv_precio']   ?? [];

        if (!$idVehiculo) {
            $_SESSION['alert'] = ['icon'=>'warning','title'=>'Datos incompletos','text'=>'Seleccione un vehículo.'];
            header("Location: ../views/dashboard/admin_cotizaciones.php"); exit;
        }

        try {
            $db->beginTransaction();

            // Calcular total
            $total = 0;
            foreach ($servicios as $i => $idServ) {
                if (!$idServ) continue;
                $total += (float)($precios[$i] ?? 0) * (int)($cantidades[$i] ?? 1);
            }

            $db->prepare(
                "INSERT INTO cotizacion (fecha, pago_total, id_vehiculo) VALUES (:fecha, :total, :idv)"
            )->execute([':fecha'=>$fecha, ':total'=>$total, ':idv'=>$idVehiculo]);
            $idCot = $db->lastInsertId();

            foreach ($servicios as $i => $idServ) {
                if (!$idServ) continue;
                $db->prepare(
                    "INSERT INTO detalle_servicio (precio, cantidad, id_servicio, id_cotizacion)
                     VALUES (:precio, :cantidad, :idserv, :idcot)"
                )->execute([
                    ':precio'    => (float)($precios[$i] ?? 0),
                    ':cantidad'  => (int)($cantidades[$i] ?? 1),
                    ':idserv'    => (int)$idServ,
                    ':idcot'     => $idCot,
                ]);
            }

            $db->commit();
            $_SESSION['alert'] = ['icon'=>'success','title'=>'Creada','text'=>'Cotización creada correctamente.'];
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>$e->getMessage()];
        }
        header("Location: ../views/dashboard/admin_cotizaciones.php"); exit;

    case 'aprobar':
        $id = (int)($_POST['id_cotizacion'] ?? 0);
        try {
            // Crear factura automáticamente al aprobar
            $cot = $db->prepare("SELECT * FROM cotizacion WHERE id_cotizacion=:id");
            $cot->execute([':id'=>$id]);
            $cotData = $cot->fetch(PDO::FETCH_ASSOC);

            if ($cotData) {
                $db->prepare(
                    "INSERT INTO factura (fecha, total, id_cotizacion) VALUES (CURDATE(), :total, :idcot)"
                )->execute([':total'=>$cotData['pago_total'],':idcot'=>$id]);
            }
            $_SESSION['alert'] = ['icon'=>'success','title'=>'Aprobada','text'=>'Cotización aprobada y factura generada.'];
        } catch (Exception $e) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>$e->getMessage()];
        }
        header("Location: ../views/dashboard/admin_cotizaciones.php"); exit;

    case 'rechazar':
        $id = (int)($_POST['id_cotizacion'] ?? 0);
        $_SESSION['alert'] = ['icon'=>'info','title'=>'Rechazada','text'=>'Cotización marcada como rechazada.'];
        header("Location: ../views/dashboard/admin_cotizaciones.php"); exit;

    default:
        header("Location: ../views/dashboard/admin_cotizaciones.php"); exit;
}
?>
