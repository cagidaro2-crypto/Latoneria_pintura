<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Verificar autenticación y rol (1=admin, 3=empleado)
if (!isset($_SESSION['usuario']) || !in_array((int)$_SESSION['usuario']['rol'], [1, 3])) {
    header("Location: ../views/usuarios/login.php"); exit;
}

$db     = (new Database())->conectar();
$rol    = (int)$_SESSION['usuario']['rol'];
$accion = $_POST['accion'] ?? '';
$idCita = (int)($_POST['id_cita'] ?? 0);

// Destino de redirección según rol
$destino = ($rol === 1)
    ? '../views/dashboard/admin_citas.php'
    : '../views/dashboard/empleado_citas.php';

if (!$idCita) {
    $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Error', 'text' => 'ID de cita no válido.'];
    header("Location: {$destino}"); exit;
}

try {
    switch ($accion) {

        case 'confirmar':
            $db->prepare("UPDATE citas SET estado='Confirmada' WHERE id_cita = :id")
               ->execute([':id' => $idCita]);
            $_SESSION['alert'] = [
                'icon'  => 'success',
                'title' => 'Cita confirmada',
                'text'  => 'La cita ha sido confirmada exitosamente.',
            ];
            break;

        case 'rechazar':
            $db->prepare("UPDATE citas SET estado='Cancelada' WHERE id_cita = :id")
               ->execute([':id' => $idCita]);
            $_SESSION['alert'] = [
                'icon'  => 'info',
                'title' => 'Cita rechazada',
                'text'  => 'La cita ha sido rechazada y marcada como cancelada.',
            ];
            break;

        case 'realizar':
            $db->prepare("UPDATE citas SET estado='Realizada' WHERE id_cita = :id")
               ->execute([':id' => $idCita]);
            $_SESSION['alert'] = [
                'icon'  => 'success',
                'title' => 'Cita realizada',
                'text'  => 'La cita ha sido marcada como realizada.',
            ];
            break;

        case 'cancelar':
            $db->prepare("UPDATE citas SET estado='Cancelada' WHERE id_cita = :id")
               ->execute([':id' => $idCita]);
            $_SESSION['alert'] = [
                'icon'  => 'warning',
                'title' => 'Cita cancelada',
                'text'  => 'La cita ha sido cancelada.',
            ];
            break;

        default:
            $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Acción no válida', 'text' => 'La acción solicitada no existe.'];
            break;
    }
} catch (Exception $e) {
    $_SESSION['alert'] = [
        'icon'  => 'error',
        'title' => 'Error en la base de datos',
        'text'  => $e->getMessage(),
    ];
}

header("Location: {$destino}");
exit;
?>
