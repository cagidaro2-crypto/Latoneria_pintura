<?php
/**
 * DashboardDataController.php
 * Endpoint AJAX — devuelve datos del dashboard en JSON.
 * Llamado cada 30 segundos desde admin_dashboard.php
 */
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario']) || (int)($_SESSION['usuario']['rol'] ?? 0) !== 1) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

try {
    $db = (new Database())->conectar();

    // ── Tarjetas resumen ──────────────────────────────────────────────────

    // Vehículos en proceso (estado "En reparación" o "Pintura")
    $stmtVeh = $db->query(
        "SELECT COUNT(*) FROM vehiculo v
         JOIN estado_vehiculo ev ON v.id_estado_vehiculo = ev.id_estado_vehiculo
         WHERE ev.estado IN ('En reparación','Pintura')"
    );
    $vehiculosEnProceso = (int)$stmtVeh->fetchColumn();

    // Vehículos en proceso ayer (para comparativa)
    $stmtVehAyer = $db->query(
        "SELECT COUNT(*) FROM historial_vehiculo
         WHERE fecha_registro = DATE_SUB(CURDATE(), INTERVAL 1 DAY)"
    );
    $ordenesAyer = (int)$stmtVehAyer->fetchColumn();

    // Ventas del mes (facturas pagadas o pendientes del mes actual)
    $stmtVentas = $db->query(
        "SELECT COALESCE(SUM(total), 0)
         FROM factura
         WHERE MONTH(fecha) = MONTH(CURDATE())
           AND YEAR(fecha)  = YEAR(CURDATE())"
    );
    $ventasMes = (float)$stmtVentas->fetchColumn();

    // Ventas mes anterior (para % comparativa)
    $stmtVentasAnt = $db->query(
        "SELECT COALESCE(SUM(total), 0)
         FROM factura
         WHERE MONTH(fecha) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
           AND YEAR(fecha)  = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))"
    );
    $ventasMesAnt = (float)$stmtVentasAnt->fetchColumn();
    $pctVentas = $ventasMesAnt > 0
        ? round((($ventasMes - $ventasMesAnt) / $ventasMesAnt) * 100, 1)
        : 0;

    // Órdenes activas (historial del mes actual)
    $stmtOrd = $db->query(
        "SELECT COUNT(*) FROM historial_vehiculo
         WHERE MONTH(fecha_registro) = MONTH(CURDATE())
           AND YEAR(fecha_registro)  = YEAR(CURDATE())"
    );
    $ordenesActivas = (int)$stmtOrd->fetchColumn();

    // Productos bajo stock
    $stmtStock = $db->query(
        "SELECT COUNT(*) FROM inventario
         WHERE cantidad <= stock_minimo"
    );
    // Si la tabla inventario no tiene la nueva estructura, devolver 0
    $bajoStock = 0;
    if ($stmtStock) {
        $bajoStock = (int)$stmtStock->fetchColumn();
    }

    // ── Gráfica ventas mensuales (últimos 6 meses) ────────────────────────
    $stmtGrafVentas = $db->query(
        "SELECT
            DATE_FORMAT(fecha, '%b') AS mes,
            DATE_FORMAT(fecha, '%Y-%m') AS mes_key,
            COALESCE(SUM(total), 0) AS total
         FROM factura
         WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
         GROUP BY mes_key, mes
         ORDER BY mes_key ASC"
    );
    $grafVentas = $stmtGrafVentas->fetchAll(PDO::FETCH_ASSOC);

    // ── Gráfica órdenes por día (últimos 7 días) ──────────────────────────
    $stmtGrafOrd = $db->query(
        "SELECT
            DATE_FORMAT(fecha_registro, '%a') AS dia,
            fecha_registro,
            COUNT(*) AS total
         FROM historial_vehiculo
         WHERE fecha_registro >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
         GROUP BY fecha_registro
         ORDER BY fecha_registro ASC"
    );
    $grafOrdenes = $stmtGrafOrd->fetchAll(PDO::FETCH_ASSOC);

    // ── Vehículos recientes (últimos 5) ───────────────────────────────────
    $stmtVehRec = $db->query(
        "SELECT v.placa, v.marca, v.modelo,
                cl.nombre AS cliente_nombre,
                ev.estado
         FROM vehiculo v
         JOIN cliente       cl ON v.id_cliente        = cl.id_cliente
         JOIN estado_vehiculo ev ON v.id_estado_vehiculo = ev.id_estado_vehiculo
         ORDER BY v.id_vehiculo DESC
         LIMIT 5"
    );
    $vehiculosRecientes = $stmtVehRec->fetchAll(PDO::FETCH_ASSOC);

    // ── Inventario bajo stock (top 5) ─────────────────────────────────────
    $stmtInvBajo = $db->query(
        "SELECT p.nombre, i.cantidad, i.stock_minimo, i.unidad
         FROM inventario i
         JOIN productos p ON i.id_producto = p.id_producto
         WHERE i.cantidad <= i.stock_minimo
         ORDER BY (i.cantidad / i.stock_minimo) ASC
         LIMIT 5"
    );
    $inventarioBajo = $stmtInvBajo ? $stmtInvBajo->fetchAll(PDO::FETCH_ASSOC) : [];

    // ── Citas próximas (si existe la tabla) ───────────────────────────────
    $citasHoy = 0;
    try {
        $stmtCitas = $db->query(
            "SELECT COUNT(*) FROM citas
             WHERE DATE(fecha_cita) = CURDATE()
               AND estado IN ('Pendiente','Confirmada')"
        );
        $citasHoy = (int)$stmtCitas->fetchColumn();
    } catch (Exception $e) {
        $citasHoy = 0;
    }

    echo json_encode([
        'tarjetas' => [
            'vehiculos_proceso' => $vehiculosEnProceso,
            'ordenes_ayer'      => $ordenesAyer,
            'ventas_mes'        => $ventasMes,
            'pct_ventas'        => $pctVentas,
            'ordenes_activas'   => $ordenesActivas,
            'bajo_stock'        => $bajoStock,
            'citas_hoy'         => $citasHoy,
        ],
        'grafica_ventas'  => $grafVentas,
        'grafica_ordenes' => $grafOrdenes,
        'vehiculos_recientes' => $vehiculosRecientes,
        'inventario_bajo'     => $inventarioBajo,
        'timestamp'           => date('H:i:s'),
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
