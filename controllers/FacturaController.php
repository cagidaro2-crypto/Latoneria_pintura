<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['usuario'])) {
    header("Location: ../views/usuarios/login.php"); exit;
}

$db     = (new Database())->conectar();
$accion = $_POST['accion'] ?? ($_GET['accion'] ?? '');

switch ($accion) {

    case 'marcar_pagada':
        $id = (int)($_POST['id_factura'] ?? 0);
        try {
            // estado_pago existe tras ejecutar tablas_faltantes.sql
            $db->prepare("UPDATE factura SET estado_pago='Pagada' WHERE id_factura=:id")
               ->execute([':id' => $id]);
            $_SESSION['alert'] = ['icon'=>'success','title'=>'Pagada','text'=>'Factura marcada como pagada.'];
        } catch (Exception $e) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>$e->getMessage()];
        }
        header("Location: ../views/dashboard/admin_facturas.php"); exit;

    case 'pdf':
        $id   = (int)($_GET['id'] ?? 0);
        $stmt = $db->prepare(
            "SELECT f.*,
                    cl.nombre AS cliente_nombre,
                    v.placa
             FROM factura f
             JOIN cotizacion c ON f.id_cotizacion = c.id_cotizacion
             JOIN vehiculo v   ON c.id_vehiculo   = v.id_vehiculo
             JOIN cliente  cl  ON v.id_cliente    = cl.id_cliente
             WHERE f.id_factura = :id"
        );
        $stmt->execute([':id' => $id]);
        $factura = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$factura) { die("Factura no encontrada."); }

        $iva      = $factura['total'] * 0.19;
        $subtotal = $factura['total'] - $iva;

        header('Content-Type: text/html; charset=utf-8');
        echo "<!DOCTYPE html><html lang='es'><head>
              <meta charset='UTF-8'>
              <title>Factura #{$factura['id_factura']}</title>
              <style>
                body{font-family:Arial,sans-serif;max-width:600px;margin:40px auto;color:#333}
                h2{color:#1a3a6b;border-bottom:2px solid #2563eb;padding-bottom:8px}
                table{width:100%;border-collapse:collapse;margin-top:16px}
                td,th{padding:8px 12px;border:1px solid #ddd;text-align:left}
                th{background:#f0f4ff;color:#1a3a6b}
                .total{font-size:1.2rem;font-weight:bold;color:#2563eb}
              </style></head><body>";
        echo "<h2>Factura #{$factura['id_factura']}</h2>";
        echo "<table>
              <tr><th>Cliente</th><td>{$factura['cliente_nombre']}</td></tr>
              <tr><th>Vehículo</th><td>{$factura['placa']}</td></tr>
              <tr><th>Fecha</th><td>{$factura['fecha']}</td></tr>
              <tr><th>Estado</th><td>" . ($factura['estado_pago'] ?? 'Pendiente') . "</td></tr>
              <tr><th>Subtotal</th><td>$" . number_format($subtotal, 2) . "</td></tr>
              <tr><th>IVA (19%)</th><td>$" . number_format($iva, 2) . "</td></tr>
              <tr><th>Total</th><td class='total'>$" . number_format($factura['total'], 2) . "</td></tr>
              </table>";
        echo "<p style='margin-top:24px;font-size:.85rem;color:#888'>Taller de Latonería y Pintura — Documento generado el " . date('d/m/Y H:i') . "</p>";
        echo "</body></html>";
        exit;

    default:
        header("Location: ../views/dashboard/admin_facturas.php"); exit;
}
?>
