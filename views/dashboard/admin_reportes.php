<?php
$titulo = 'Reportes';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../layouts/header.php';

$db    = (new Database())->conectar();
$alert = $_SESSION['alert'] ?? null; unset($_SESSION['alert']);

// Datos resumen
$totalIngresos = $db->query("SELECT COALESCE(SUM(total),0) FROM facturas WHERE estado_pago='Pagada'")->fetchColumn();
$totalOrdenes  = $db->query("SELECT COUNT(*) FROM ordenes_servicio")->fetchColumn();
$totalVentas   = $db->query("SELECT COALESCE(SUM(total),0) FROM ventas WHERE estado='Activa'")->fetchColumn();

// Servicios por tipo
$stmtTipo = $db->query(
    "SELECT tipo_servicio, COUNT(*) as total FROM ordenes_servicio GROUP BY tipo_servicio ORDER BY total DESC"
);
$serviciosPorTipo = $stmtTipo->fetchAll(PDO::FETCH_ASSOC);

// Últimas ventas
$stmtVentas = $db->query(
    "SELECT v.*, CONCAT(u.nombres,' ',u.apellidos) AS cliente_nombre
     FROM ventas v JOIN usuarios u ON v.id_cliente = u.id_usuario
     WHERE v.estado='Activa' ORDER BY v.created_at DESC LIMIT 10"
);
$ventas = $stmtVentas->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- RESUMEN FINANCIERO -->
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="card shadow-sm card-stat">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="fas fa-dollar-sign"></i></div>
                <div>
                    <div class="fs-5 fw-bold">$<?= number_format($totalIngresos, 0, ',', '.') ?></div>
                    <div class="text-muted small">Ingresos Facturados</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card shadow-sm card-stat">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="fas fa-clipboard-list"></i></div>
                <div>
                    <div class="fs-5 fw-bold"><?= $totalOrdenes ?></div>
                    <div class="text-muted small">Total Órdenes</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card shadow-sm card-stat">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="fas fa-cash-register"></i></div>
                <div>
                    <div class="fs-5 fw-bold">$<?= number_format($totalVentas, 0, ',', '.') ?></div>
                    <div class="text-muted small">Total Ventas</div>
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
                <?php foreach ($serviciosPorTipo as $s): ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span><?= htmlspecialchars($s['tipo_servicio']) ?></span>
                        <strong><?= $s['total'] ?></strong>
                    </div>
                    <?php $pct = $totalOrdenes > 0 ? round($s['total'] / $totalOrdenes * 100) : 0; ?>
                    <div class="progress" style="height:8px;">
                        <div class="progress-bar bg-primary" style="width:<?= $pct ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($serviciosPorTipo)): ?>
                    <p class="text-muted text-center">No hay datos disponibles para el período seleccionado.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- GENERAR REPORTE -->
    <div class="col-md-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold border-bottom">
                <i class="fas fa-file-export text-primary me-2"></i>Generar Reporte
            </div>
            <div class="card-body">
                <form action="../../controllers/AdminReporteController.php" method="POST">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Tipo de Reporte</label>
                            <select name="tipo_reporte" class="form-select">
                                <option value="productividad">Productividad</option>
                                <option value="ingresos">Ingresos</option>
                                <option value="consumo_materiales">Consumo de Materiales</option>
                                <option value="ventas">Ventas</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Formato</label>
                            <select name="formato" class="form-select">
                                <option value="pdf">PDF</option>
                                <option value="excel">Excel</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Fecha Inicio</label>
                            <input type="date" name="fecha_inicio" class="form-control"
                                   value="<?= date('Y-m-01') ?>">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Fecha Fin</label>
                            <input type="date" name="fecha_fin" class="form-control"
                                   value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-3 w-100">
                        <i class="fas fa-file-export me-2"></i>Generar y Descargar
                    </button>
                </form>
            </div>
        </div>

        <!-- ÚLTIMAS VENTAS -->
        <div class="card shadow-sm mt-3">
            <div class="card-header bg-white fw-semibold border-bottom">
                <i class="fas fa-cash-register text-primary me-2"></i>Últimas Ventas
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover small mb-0">
                        <thead class="table-light">
                            <tr><th>Cliente</th><th>Total</th><th>Pago</th><th>Fecha</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($ventas as $v): ?>
                        <tr>
                            <td><?= htmlspecialchars($v['cliente_nombre']) ?></td>
                            <td>$<?= number_format($v['total'], 2) ?></td>
                            <td><?= htmlspecialchars($v['metodo_pago']) ?></td>
                            <td><?= date('d/m/Y', strtotime($v['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($ventas)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">No hay ventas registradas.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
