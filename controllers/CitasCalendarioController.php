<?php
/**
 * CitasCalendarioController.php
 * Endpoint AJAX para FullCalendar — devuelve citas Pendientes y Confirmadas en formato JSON.
 */
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

// Solo usuarios autenticados
if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

try {
    $db = (new Database())->conectar();

    // Rango opcional que FullCalendar envía como parámetros GET
    $inicio = $_GET['start'] ?? null;
    $fin    = $_GET['end']   ?? null;

    $sql = "SELECT c.id_cita, c.numero_ref, c.tipo_servicio, c.fecha_cita, c.estado,
                   cl.nombres AS cliente_nombre,
                   v.placa
            FROM citas c
            LEFT JOIN clientes cl ON c.id_cliente  = cl.id_cliente
            LEFT JOIN vehiculos v ON c.id_vehiculo = v.id_vehiculo
            WHERE c.estado IN ('Pendiente', 'Confirmada')";

    $params = [];

    if ($inicio && $fin) {
        $sql .= " AND c.fecha_cita >= :inicio AND c.fecha_cita < :fin";
        $params[':inicio'] = $inicio;
        $params[':fin']    = $fin;
    }

    $sql .= " ORDER BY c.fecha_cita ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $citas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $eventos = [];
    foreach ($citas as $c) {
        $color = ($c['estado'] === 'Confirmada') ? '#dc3545' : '#f59e0b';
        $label = $c['placa']
            ? $c['placa'] . ' – ' . $c['tipo_servicio']
            : $c['tipo_servicio'];

        $eventos[] = [
            'id'    => $c['id_cita'],
            'title' => $label,
            'start' => $c['fecha_cita'],
            'color' => $color,
            'extendedProps' => [
                'ref'      => $c['numero_ref'],
                'cliente'  => $c['cliente_nombre'] ?? '',
                'servicio' => $c['tipo_servicio'],
                'estado'   => $c['estado'],
                'placa'    => $c['placa'] ?? '',
            ],
        ];
    }

    echo json_encode($eventos, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
exit;
?>
