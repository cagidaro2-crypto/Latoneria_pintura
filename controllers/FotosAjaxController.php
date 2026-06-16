<?php
/**
 * FotosAjaxController.php
 * Endpoint AJAX — devuelve fotos de un vehículo en JSON.
 */
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$idVehiculo = (int)($_GET['id_vehiculo'] ?? 0);
if (!$idVehiculo) {
    echo json_encode([]);
    exit;
}

try {
    $db   = (new Database())->conectar();
    $stmt = $db->prepare(
        "SELECT f.id_foto, f.id_vehiculo, f.nombre_archivo, f.etapa,
                f.descripcion, f.fecha_subida AS created_at,
                CONCAT(u.nombres, ' ', COALESCE(u.apellidos, '')) AS subido_por_nombre
         FROM vehiculo_fotos f
         LEFT JOIN usuarios u ON f.id_usuario = u.id_usuario
         WHERE f.id_vehiculo = :id
         ORDER BY f.etapa ASC, f.fecha_subida DESC"
    );
    $stmt->execute([':id' => $idVehiculo]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
