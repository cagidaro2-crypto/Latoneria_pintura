<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['usuario'])) {
    header("Location: ../views/usuarios/login.php"); exit;
}

$db        = (new Database())->conectar();
$idCliente = $_SESSION['usuario']['id_usuario'];
$accion    = $_POST['accion'] ?? ($_GET['accion'] ?? '');

function generarRef($db) {
    $stmt = $db->query("SELECT MAX(id_cita) FROM citas");
    $ultimo = (int) $stmt->fetchColumn();
    return 'CIT-' . str_pad($ultimo + 1, 5, '0', STR_PAD_LEFT);
}

switch ($accion) {

    case 'agendar':
        $idVehiculo  = (int) ($_POST['id_vehiculo']   ?? 0);
        $tipoServicio= trim($_POST['tipo_servicio']   ?? '');
        $fechaCita   = trim($_POST['fecha_cita']      ?? '');

        if (!$idVehiculo || !$tipoServicio || !$fechaCita) {
            $_SESSION['alert'] = ['icon'=>'warning','title'=>'Campos incompletos','text'=>'Por favor complete todos los campos obligatorios.'];
            header("Location: ../views/dashboard/cliente_citas.php"); exit;
        }

        // Verificar disponibilidad (no mismo horario exacto)
        $stmt = $db->prepare(
            "SELECT id_cita FROM citas WHERE fecha_cita = :fecha AND estado NOT IN ('Cancelada') LIMIT 1"
        );
        $stmt->execute([':fecha' => $fechaCita]);
        if ($stmt->rowCount() > 0) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Horario no disponible','text'=>'El horario seleccionado no está disponible. Por favor elige otro horario.'];
            header("Location: ../views/dashboard/cliente_citas.php"); exit;
        }

        try {
            $ref = generarRef($db);
            $db->prepare(
                "INSERT INTO citas (numero_ref, id_cliente, id_vehiculo, tipo_servicio, fecha_cita, estado, created_at)
                 VALUES (:ref, :id_cliente, :id_vehiculo, :tipo_servicio, :fecha_cita, 'Pendiente', NOW())"
            )->execute([
                ':ref'          => $ref,
                ':id_cliente'   => $idCliente,
                ':id_vehiculo'  => $idVehiculo,
                ':tipo_servicio'=> $tipoServicio,
                ':fecha_cita'   => $fechaCita,
            ]);

            $fechaFormateada = date('d/m/Y', strtotime($fechaCita));
            $hora            = date('H:i', strtotime($fechaCita));
            $_SESSION['alert'] = [
                'icon'  => 'success',
                'title' => '¡Cita agendada!',
                'text'  => "Cita agendada exitosamente para el {$fechaFormateada} a las {$hora}. Número de referencia: {$ref}.",
            ];
        } catch (Exception $e) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>$e->getMessage()];
        }
        header("Location: ../views/dashboard/cliente_citas.php"); exit;

    case 'cancelar':
        $idCita = (int) ($_GET['id'] ?? 0);
        try {
            $db->prepare(
                "UPDATE citas SET estado='Cancelada' WHERE id_cita=:id AND id_cliente=:cliente"
            )->execute([':id'=>$idCita, ':cliente'=>$idCliente]);
            $_SESSION['alert'] = ['icon'=>'success','title'=>'Cita cancelada','text'=>'Tu cita ha sido cancelada. El taller ha sido notificado del cambio.'];
        } catch (Exception $e) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>$e->getMessage()];
        }
        header("Location: ../views/dashboard/cliente_citas.php"); exit;

    default:
        header("Location: ../views/dashboard/cliente_citas.php"); exit;
}
?>
