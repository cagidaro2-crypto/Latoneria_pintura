<?php
$titulo = 'Ventas';
require_once __DIR__ . '/../../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario']) || (int)($_SESSION['usuario']['rol'] ?? 0) !== 1) {
    header("Location: ../usuarios/login.php"); exit;
}

require_once __DIR__ . '/../layouts/header.php';

$db    = (new Database())->conectar();
$alert = $_SESSION['alert'] ?? null; unset($_SESSION['alert']);

// ── Tarjetas resumen ──────────────────────────────────────────────────────
$ventasHoy    = (float)$db->query("SELECT COALESCE(SUM(total),0) FROM factura WHERE fecha = CURDATE()")->fetchColumn();
$ventasSemana = (float)$db->query("SELECT COALESCE(SUM(total),0) FROM factura WHERE YEARWEEK(fecha,1) = YEARWEEK(CURDATE(),1)")->fetchColumn();
$ventasMes    = (float)$db->query("SELECT COALESCE(SUM(total),0) FROM factura WHERE MONTH(fecha)=MONTH(CURDATE()) AND YEAR(fecha)=YEAR(CURDATE())")->fetchColumn();
$ticketProm   = (float)$db->query("SELECT COALESCE(AVG(total),0) FROM factura WHERE MONTH(fecha)=MONTH(CURDATE()) AND YEAR(fecha)=YEAR(CURDATE())")->fetchColumn();

// ── Gráfica: ventas reales últimos 7 días ─────────────────────────────────
$stmtG7 = $db->query(
    "SELECT
        DATE_FORMAT(fecha, '%d/%m') AS dia,
        fecha,
        COALESCE(SUM(total), 0)    AS total
     FROM factura
     WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
     GROUP BY fecha
     ORDER BY fecha ASC"
);
$rawG7 = $stmtG7 ? $stmtG7->fetchAll(PDO::FETCH_ASSOC) : [];

// Rellenar días sin ventas con 0
$g7Labels = [];
$g7Data   = [];
$mapG7    = array_column($rawG7, 'total', 'dia');
for ($i = 6; $i >= 0; $i--) {
    $label = date('d/m', strtotime("-$i days"));
    $g7Labels[] = $label;
    $g7Data[]   = (float)($mapG7[$label] ?? 0);
}

// ── Servicios más vendidos (desde detalle_servicio) ───────────────────────
$stmtServ = $db->query(
    "SELECT s.nombre,
            COUNT(*)                    AS total,
            SUM(ds.precio * ds.cantidad) AS ingresos
     FROM detalle_servicio ds
     JOIN servicio s ON ds.id_servicio = s.id_servicio
     GROUP BY s.id_servicio, s.nombre
     ORDER BY ingresos DESC
     LIMIT 5"
);
$serviciosTop = $stmtServ ? $stmtServ->fetchAll(PDO::FETCH_ASSOC) : [];

// Fallback: si no hay detalle_servicio, usar historial_vehiculo
if (empty($serviciosTop)) {
    $stmtServFb = $db->query(
        "SELECT tipo_reparacion AS nombre,
                COUNT(*)        AS total,
                0               AS ingresos
         FROM historial_vehiculo
         GROUP BY tipo_reparacion
         ORDER BY total DESC
         LIMIT 5"
    );
    $serviciosTop = $stmtServFb ? $stmtServFb->fetchAll(PDO::FETCH_ASSOC) : [];
}
$maxServ = !empty($serviciosTop) ? max(array_column($serviciosTop, 'ingresos') ?: array_column($serviciosTop, 'total')) : 1;

// ── Ventas recientes ──────────────────────────────────────────────────────
$stmtVentas = $db->query(
    "SELECT f.id_factura, f.fecha, f.total, f.estado_pago,
            cl.nombre AS cliente_nombre,
            v.placa,
            s.nombre  AS servicio_nombre
     FROM factura f
     JOIN cotizacion c  ON f.id_cotizacion = c.id_cotizacion
     JOIN vehiculo v    ON c.id_vehiculo   = v.id_vehiculo
     JOIN cliente  cl   ON v.id_cliente    = cl.id_cliente
     LEFT JOIN detalle_servicio ds ON ds.id_cotizacion = c.id_cotizacion
     LEFT JOIN servicio s ON ds.id_servicio = s.id_servicio
     ORDER BY f.fecha DESC, f.id_factura DESC
     LIMIT 20"
);
$ventas = $stmtVentas ? $stmtVentas->fetchAll(PDO::FETCH_ASSOC) : [];
?>

<!-- TARJETAS RESUMEN -->
<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['icon'=>'fas fa-dollar-sign','color'=>'text-success','bg'=>'bg-success','label'=>'Ventas Hoy',    'val'=>$ventasHoy],
        ['icon'=>'fas fa-cart-shopping','color'=>'text-primary','bg'=>'bg-primary','label'=>'Ventas Semana','val'=>$ventasSemana],
        ['icon'=>'fas fa-calendar','color'=>'text-info','bg'=>'bg-info',    'label'=>'Ventas Mes',    'val'=>$ventasMes],
        ['icon'=>'fas fa-receipt','color'=>'text-warning','bg'=>'bg-warning','label'=>'Ticket Promedio','val'=>$ticketProm],
    ];
    foreach ($cards as $card): ?>
    <div class="col-sm-6 col-xl-3">
        <div class="card shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 p-3 <?= $card['bg'] ?> bg-opacity-10">
                    <i class="<?= $card['icon'] ?> fa-lg <?= $card['color'] ?>"></i>
                </div>
                <div>
                    <div class="text-muted small"><?= $card['label'] ?></div>
                    <div class="fw-bold fs-5">$<?= number_format($card['val'], 0, ',', '.') ?></div>
                </div>
                <i class="fas fa-arrow-trend-up text-success ms-auto opacity-50"></i>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
    <!-- GRÁFICA VENTAS 7 DÍAS -->
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold border-bottom">
                <i class="fas fa-chart-line text-primary me-2"></i>Ventas Últimos 7 Días
            </div>
            <div class="card-body">
                <div style="position:relative; height:220px;">
                    <canvas id="chartVentas7"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- SERVICIOS MÁS VENDIDOS -->
    <div class="col-lg-5">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold border-bottom">
                <i class="fas fa-star text-primary me-2"></i>Servicios Más Vendidos
            </div>
            <div class="card-body">
                <?php foreach ($serviciosTop as $s):
                    $valor    = (float)$s['ingresos'] > 0 ? (float)$s['ingresos'] : (int)$s['total'];
                    $maxValor = $maxServ > 0 ? $maxServ : 1;
                    $pct      = round($valor / $maxValor * 100);
                ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span><?= htmlspecialchars($s['nombre']) ?></span>
                        <strong>
                            <?= (float)$s['ingresos'] > 0
                                ? '$' . number_format($s['ingresos'], 0, ',', '.')
                                : $s['total'] . ' orden(es)' ?>
                        </strong>
                    </div>
                    <div class="progress" style="height:8px;">
                        <div class="progress-bar bg-primary" style="width:<?= $pct ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($serviciosTop)): ?>
                    <p class="text-muted text-center small">Sin datos disponibles.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- VENTAS RECIENTES -->
<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold border-bottom d-flex justify-content-between align-items-center">
        <span><i class="fas fa-list text-primary me-2"></i>Ventas Recientes</span>
        <a href="admin_facturas.php" class="btn btn-sm btn-outline-primary" style="font-size:.75rem;">Ver facturas</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>CLIENTE</th>
                        <th>VEHÍCULO</th>
                        <th>SERVICIO</th>
                        <th>MONTO</th>
                        <th>ESTADO</th>
                        <th>FECHA</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($ventas as $v):
                    $ep = $v['estado_pago'] ?? 'Pendiente';
                    $epBadge = match($ep) {
                        'Pagada'  => 'bg-success',
                        'Anulada' => 'bg-danger',
                        default   => 'bg-warning text-dark'
                    };
                ?>
                <tr>
                    <td class="fw-semibold"><?= htmlspecialchars($v['cliente_nombre']) ?></td>
                    <td class="font-monospace"><?= htmlspecialchars($v['placa']) ?></td>
                    <td><?= htmlspecialchars($v['servicio_nombre'] ?? '–') ?></td>
                    <td class="text-primary fw-bold">$<?= number_format($v['total'], 0, ',', '.') ?></td>
                    <td><span class="badge <?= $epBadge ?>"><?= $ep ?></span></td>
                    <td><?= date('d/m/Y', strtotime($v['fecha'])) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($ventas)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No hay ventas registradas.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($alert): ?>
<script>
    Swal.fire({ icon:'<?= $alert['icon'] ?>', title:'<?= $alert['title'] ?>', text:'<?= $alert['text'] ?>', confirmButtonColor:'#2563eb' });
</script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// ── Gráfica ventas últimos 7 días — datos REALES desde BD ─────────────────
const ctx7 = document.getElementById('chartVentas7').getContext('2d');
new Chart(ctx7, {
    type: 'line',
    data: {
        labels: <?= json_encode($g7Labels) ?>,
        datasets: [{
            label: 'Ventas ($)',
            data:  <?= json_encode($g7Data) ?>,
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59,130,246,0.08)',
            borderWidth: 2,
            pointBackgroundColor: '#fff',
            pointBorderColor: '#3b82f6',
            pointBorderWidth: 2,
            pointRadius: 5,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => '$' + ctx.parsed.y.toLocaleString('es-CO')
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { borderDash: [5,5] },
                ticks: {
                    callback: val => '$' + val.toLocaleString('es-CO')
                }
            },
            x: { grid: { display: false } }
        }
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
