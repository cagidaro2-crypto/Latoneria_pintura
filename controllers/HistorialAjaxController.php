<?php
/**
 * HistorialAjaxController.php
 * Endpoint AJAX — devuelve el historial de un vehículo en JSON.
 * Usado por las vistas de admin y empleado para cargar el timeline.
 */
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Vehiculo.php';

header('Content-Type: application/json; charset=utf-8');

// Verificar sesión
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$rol = (int)($_SESSION['usuario']['rol'] ?? 0);
// Solo admin (1) y empleado (3) pueden consultar historial vía AJAX
if ($rol !== 1 && $rol !== 3) {
    echo json_encode(['error' => 'Sin permiso']);
    exit;
}

$idVehiculo = (int)($_GET['id_vehiculo'] ?? 0);
if (!$idVehiculo) {
    echo json_encode([]);
    exit;
}

try {
    $db     = (new Database())->conectar();
    $vModel = new Vehiculo($db);
    $data   = $vModel->obtenerHistorial($idVehiculo);
    echo json_encode($data);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
