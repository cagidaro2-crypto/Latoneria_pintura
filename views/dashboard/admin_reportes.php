<?php
$titulo = 'Reportes';
require_once __DIR__ . '/../../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario']) || (int)($_SESSION['usuario']['rol'] ?? 0) !== 1) {
    header("Location: ../usuarios/login.php"); exit;
}

require_once __DIR__ . '/../layouts/header.php';

$db    = (new Database())->conectar();
$alert = $_SESSION['alert'] ?? null; unset($_SESSION['alert']);

// ── Ingresos totales (facturas pagadas) ───────────────────────────────────
$totalIngresos = $db->query(
    "SELECT COALESCE(SUM(total),0) FROM facturas WHERE estado_pago='Pagada'"
)->fetchColumn();

// ── Total órdenes (historial_vehiculo como proxy) ─────────────────────────
$totalOrdenes = $db->query("SELECT COUNT(*) FROM historial_vehiculo")->fetchColumn();

// ── Total facturado ───────────────────────────────────────────────────────
$totalVentas = $db->query("SELECT COALESCE(SUM(total),0) FROM facturas")->fetchColumn();

// ── Servicios por tipo (desde historial_vehiculo) ─────────────────────────
$stmtTipo = $db->query(
    "SELECT tipo_reparacion AS tipo_servicio, COUNT(*) AS total
     FROM historial_vehiculo
     GROUP BY tipo_reparacion
     ORDER BY total DESC"
);
$serviciosPorTipo = $stmtTipo->fetchAll(PDO::FETCH_ASSOC);

// ── Últimas facturas ──────────────────────────────────────────────────────
$stmtFact = $db->query(
    "SELECT f.*, cl.nombres AS cliente_nombre, v.placa
     FROM facturas f
     JOIN cotizaciones c  ON f.id_cotizacion = c.id_cotizacion
     JOIN vehiculos v    ON c.id_vehiculo   = v.id_vehiculo
     JOIN clientes  cl   ON v.id_cliente    = cl.id_cliente
     ORDER BY f.fecha DESC
     LIMIT 10"
);
$ventas = $stmtFact ? $stmtFact->fetchAll(PDO::FETCH_ASSOC) : [];
?>

<!-- RESUMEN FINANCIERO -->
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="card shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 p-3 bg-success bg-opacity-10">
                    <i class="fas fa-dollar-sign fa-lg text-success"></i>
                </div>
                <div>
                    <div class="fs-5 fw-bold">$<?= number_format($totalIngresos, 0, ',', '.') ?></div>
                    <div class="text-muted small">Ingresos Facturados</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 p-3 bg-primary bg-opacity-10">
                    <i class="fas fa-clipboard-list fa-lg text-primary"></i>
                </div>
                <div>
                    <div class="fs-5 fw-bold"><?= $totalOrdenes ?></div>
                    <div class="text-muted small">Total Órdenes</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 p-3 bg-warning bg-opacity-10">
                    <i class="fas fa-file-invoice-dollar fa-lg text-warning"></i>
                </div>
                <div>
                    <div class="fs-5 fw-bold">$<?= number_format($totalVentas, 0, ',', '.') ?></div>
                    <div class="text-muted small">Total Facturado</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">

    <!-- SERVICIOS POR TIPO -->
    <div class="col-md-5">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold border-bottom">
                <i class="fas fa-chart-bar text-primary me-2"></i>Servicios por Tipo
            </div>
            <div class="card-body">
                <?php foreach ($serviciosPorTipo as $s):
                    $pct = $totalOrdenes > 0 ? round($s['total'] / $totalOrdenes * 100) : 0;
                ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span><?= htmlspecialchars($s['tipo_servicio']) ?></span>
                        <strong><?= $s['total'] ?></strong>
                    </div>
                    <div class="progress" style="height:8px;">
                        <div class="progress-bar bg-primary" style="width:<?= $pct ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($serviciosPorTipo)): ?>
                    <p class="text-muted text-center small">No hay datos disponibles.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ÚLTIMAS FACTURAS -->
    <div class="col-md-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold border-bottom">
                <i class="fas fa-file-invoice text-primary me-2"></i>Últimas Facturas
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover small mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Cliente</th>
                                <th>Vehículo</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($ventas as $v):
                            $badge = match($v['estado_pago'] ?? 'Pendiente') {
                                'Pagada'  => 'bg-success',
                                'Anulada' => 'bg-danger',
                                default   => 'bg-warning text-dark'
                            };
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($v['cliente_nombre']) ?></td>
                            <td><?= htmlspecialchars($v['placa']) ?></td>
                            <td>$<?= number_format($v['total'], 0, ',', '.') ?></td>
                            <td><span class="badge <?= $badge ?>"><?= $v['estado_pago'] ?? 'Pendiente' ?></span></td>
                            <td><?= date('d/m/Y', strtotime($v['fecha'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($ventas)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-3">No hay facturas registradas.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
