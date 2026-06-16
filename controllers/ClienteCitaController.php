<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Verificar autenticación
if (!isset($_SESSION['usuario'])) {
    header("Location: ../views/usuarios/login.php"); exit;
}

$db        = (new Database())->conectar();
$idUsuario = (int)($_SESSION['usuario']['id_usuario'] ?? 0);
$accion    = $_POST['accion'] ?? ($_GET['accion'] ?? '');
$destino   = '../views/dashboard/cliente_citas.php';

/**
 * Genera el siguiente número de referencia en formato CIT-00001
 */
function generarRef(PDO $db): string {
    $stmt  = $db->query("SELECT COALESCE(MAX(id_cita), 0) FROM citas");
    $ultimo = (int)$stmt->fetchColumn();
    return 'CIT-' . str_pad($ultimo + 1, 5, '0', STR_PAD_LEFT);
}

switch ($accion) {

    // ── AGENDAR ──────────────────────────────────────────────────────────
    case 'agendar':
        $idVehiculo   = (int)trim($_POST['id_vehiculo']   ?? '');
        $tipoServicio = trim($_POST['tipo_servicio']      ?? '');
        $fechaCita    = trim($_POST['fecha_cita']         ?? '');
        $notas        = trim($_POST['notas']              ?? '');

        if (!$idVehiculo || !$tipoServicio || !$fechaCita) {
            $_SESSION['alert'] = [
                'icon'  => 'warning',
                'title' => 'Campos incompletos',
                'text'  => 'Completa todos los campos obligatorios.',
            ];
            header("Location: {$destino}"); exit;
        }

        // Validar que la fecha no sea pasada
        if (strtotime($fechaCita) < time()) {
            $_SESSION['alert'] = [
                'icon'  => 'warning',
                'title' => 'Fecha inválida',
                'text'  => 'No puedes agendar una cita en una fecha pasada.',
            ];
            header("Location: {$destino}"); exit;
        }

        // Verificar disponibilidad (no mismo horario exacto)
        $stmtCheck = $db->prepare(
            "SELECT id_cita FROM citas
             WHERE fecha_cita = :fecha AND estado NOT IN ('Cancelada') LIMIT 1"
        );
        $stmtCheck->execute([':fecha' => $fechaCita]);
        if ($stmtCheck->rowCount() > 0) {
            $_SESSION['alert'] = [
                'icon'  => 'error',
                'title' => 'Horario no disponible',
                'text'  => 'El horario seleccionado ya está ocupado. Por favor elige otro.',
            ];
            header("Location: {$destino}"); exit;
        }

        try {
            $ref = generarRef($db);
            $db->prepare(
                "INSERT INTO citas
                    (numero_ref, id_cliente, id_vehiculo, tipo_servicio, fecha_cita, estado, created_at)
                 VALUES
                    (:ref, :id_cliente, :id_vehiculo, :tipo_servicio, :fecha_cita, 'Pendiente', NOW())"
            )->execute([
                ':ref'          => $ref,
                ':id_cliente'   => $idUsuario,
                ':id_vehiculo'  => $idVehiculo,
                ':tipo_servicio'=> $tipoServicio,
                ':fecha_cita'   => $fechaCita,
            ]);

            $fechaFmt = date('d/m/Y', strtotime($fechaCita));
            $hora     = date('H:i',   strtotime($fechaCita));
            $_SESSION['alert'] = [
                'icon'  => 'success',
                'title' => '¡Cita agendada!',
                'text'  => "Tu cita para el {$fechaFmt} a las {$hora} fue registrada. Ref: {$ref}.",
            ];
        } catch (Exception $e) {
            $_SESSION['alert'] = [
                'icon'  => 'error',
                'title' => 'Error al agendar',
                'text'  => $e->getMessage(),
            ];
        }
        header("Location: {$destino}"); exit;

    // ── CANCELAR ─────────────────────────────────────────────────────────
    case 'cancelar':
        $idCita = (int)($_POST['id_cita'] ?? $_GET['id'] ?? 0);

        if (!$idCita) {
            $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Error', 'text' => 'ID de cita no válido.'];
            header("Location: {$destino}"); exit;
        }

        try {
            $stmt = $db->prepare(
                "UPDATE citas SET estado = 'Cancelada'
                 WHERE id_cita = :id AND id_cliente = :cliente
                   AND estado IN ('Pendiente', 'Confirmada')"
            );
            $stmt->execute([':id' => $idCita, ':cliente' => $idUsuario]);

            if ($stmt->rowCount() > 0) {
                $_SESSION['alert'] = [
                    'icon'  => 'success',
                    'title' => 'Cita cancelada',
                    'text'  => 'Tu cita ha sido cancelada correctamente.',
                ];
            } else {
                $_SESSION['alert'] = [
                    'icon'  => 'warning',
                    'title' => 'Sin cambios',
                    'text'  => 'No se pudo cancelar la cita. Puede que ya esté cancelada o realizada.',
                ];
            }
        } catch (Exception $e) {
            $_SESSION['alert'] = [
                'icon'  => 'error',
                'title' => 'Error',
                'text'  => $e->getMessage(),
            ];
        }
        header("Location: {$destino}"); exit;

    default:
        header("Location: {$destino}"); exit;
}
?>
