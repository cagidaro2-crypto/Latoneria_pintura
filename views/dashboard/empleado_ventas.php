<?php
$titulo = 'Ventas';
require_once __DIR__ . '/../../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario']) || (int)($_SESSION['usuario']['rol'] ?? 0) !== 3) {
    header("Location: ../usuarios/login.php"); exit;
}

require_once __DIR__ . '/../layouts/header.php';

$db    = (new Database())->conectar();
$alert = $_SESSION['alert'] ?? null; unset($_SESSION['alert']);

// Resumen del mes
$ventasMes  = (float)$db->query("SELECT COALESCE(SUM(total),0) FROM facturas WHERE MONTH(fecha)=MONTH(CURDATE()) AND YEAR(fecha)=YEAR(CURDATE())")->fetchColumn();
$ventasHoy  = (float)$db->query("SELECT COALESCE(SUM(total),0) FROM facturas WHERE fecha=CURDATE()")->fetchColumn();
$totalFact  = (int)$db->query("SELECT COUNT(*) FROM facturas WHERE MONTH(fecha)=MONTH(CURDATE()) AND YEAR(fecha)=YEAR(CURDATE())")->fetchColumn();
$pagadas    = (int)$db->query("SELECT COUNT(*) FROM facturas WHERE estado_pago='Pagada' AND MONTH(fecha)=MONTH(CURDATE()) AND YEAR(fecha)=YEAR(CURDATE())")->fetchColumn();

// Facturas recientes con cliente y vehículo
$stmtF = $db->query(
    "SELECT f.id_factura, f.fecha, f.total, f.estado_pago,
            cl.nombres AS cliente_nombre,
            v.placa, v.marca, v.modelo,
            s.nombre  AS servicio_nombre
     FROM facturas f
     JOIN cotizaciones c  ON f.id_cotizacion = c.id_cotizacion
     JOIN vehiculos v     ON c.id_vehiculo   = v.id_vehiculo
     JOIN clientes cl     ON v.id_cliente    = cl.id_cliente
     LEFT JOIN cotizacion_servicios ds ON ds.id_cotizacion = c.id_cotizacion
     LEFT JOIN servicios s ON ds.id_servicio = s.id_servicio
     ORDER BY f.fecha DESC, f.id_factura DESC
     LIMIT 30"
);
$facturas = $stmtF ? $stmtF->fetchAll(PDO::FETCH_ASSOC) : [];
?>

<!-- Tarjetas resumen -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 p-3 bg-primary bg-opacity-10">
                    <i class="fas fa-calendar fa-lg text-primary"></i>
                </div>
                <div>
                    <div class="text-muted small">Ventas del Mes</div>
                    <div class="fw-bold fs-5">$<?= number_format($ventasMes, 0, ',', '.') ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 p-3 bg-success bg-opacity-10">
                    <i class="fas fa-dollar-sign fa-lg text-success"></i>
                </div>
                <div>
                    <div class="text-muted small">Ventas Hoy</div>
                    <div class="fw-bold fs-5">$<?= number_format($ventasHoy, 0, ',', '.') ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 p-3 bg-info bg-opacity-10">
                    <i class="fas fa-file-invoice fa-lg text-info"></i>
                </div>
                <div>
                    <div class="text-muted small">Facturas del Mes</div>
                    <div class="fw-bold fs-5"><?= $totalFact ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 p-3 bg-warning bg-opacity-10">
                    <i class="fas fa-check-circle fa-lg text-warning"></i>
                </div>
                <div>
                    <div class="text-muted small">Pagadas</div>
                    <div class="fw-bold fs-5"><?= $pagadas ?> / <?= $totalFact ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Buscador -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <div class="input-group">
            <span class="input-group-text bg-white border-end-0">
                <i class="fas fa-search text-muted"></i>
            </span>
            <input type="text" id="buscador" class="form-control border-start-0"
                   placeholder="Buscar por cliente, placa o servicio…">
        </div>
    </div>
</div>

<!-- Tabla facturas -->
<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold border-bottom">
        <i class="fas fa-list text-primary me-2"></i>Facturas Recientes
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small" id="tablaVentas">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>CLIENTE</th>
                        <th>VEHÍCULO</th>
                        <th>SERVICIO</th>
                        <th>TOTAL</th>
                        <th>ESTADO</th>
                        <th>FECHA</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($facturas as $f):
                    $ep = $f['estado_pago'] ?? 'Pendiente';
                    $epBadge = match($ep) {
                        'Pagada'  => 'bg-success',
                        'Anulada' => 'bg-danger',
                        default   => 'bg-warning text-dark'
                    };
                ?>
                <tr>
                    <td class="text-muted">#<?= $f['id_factura'] ?></td>
                    <td class="fw-semibold"><?= htmlspecialchars($f['cliente_nombre']) ?></td>
                    <td>
                        <div class="font-monospace fw-bold"><?= htmlspecialchars($f['placa']) ?></div>
                        <div class="text-muted" style="font-size:.72rem;">
                            <?= htmlspecialchars($f['marca'] . ' ' . $f['modelo']) ?>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($f['servicio_nombre'] ?? '–') ?></td>
                    <td class="text-primary fw-bold">$<?= number_format($f['total'], 0, ',', '.') ?></td>
                    <td><span class="badge <?= $epBadge ?>"><?= $ep ?></span></td>
                    <td class="text-muted"><?= date('d/m/Y', strtotime($f['fecha'])) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($facturas)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="fas fa-file-invoice fa-2x mb-2 opacity-25 d-block"></i>
                            No hay facturas registradas.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($alert): ?>
<script>
    Swal.fire({
        icon:  '<?= $alert['icon'] ?>',
        title: '<?= $alert['title'] ?>',
        text:  '<?= $alert['text'] ?>',
        confirmButtonColor: '#000000'
    });
</script>
<?php endif; ?>

<script>
document.getElementById('buscador').addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#tablaVentas tbody tr').forEach(tr => {
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
