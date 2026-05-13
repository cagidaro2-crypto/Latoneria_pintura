<?php
$titulo = 'Facturación';
require_once __DIR__ . '/../../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario']) || (int)($_SESSION['usuario']['rol'] ?? 0) !== 1) {
    header("Location: ../usuarios/login.php"); exit;
}

require_once __DIR__ . '/../layouts/header.php';

$db    = (new Database())->conectar();
$alert = $_SESSION['alert'] ?? null; unset($_SESSION['alert']);

// Facturas: factura → cotizacion → vehiculo → cliente
$stmt = $db->query(
    "SELECT f.*,
            cl.nombre AS cliente_nombre,
            v.placa,
            c.fecha   AS fecha_cotizacion
     FROM factura f
     JOIN cotizacion c ON f.id_cotizacion = c.id_cotizacion
     JOIN vehiculo v   ON c.id_vehiculo   = v.id_vehiculo
     JOIN cliente  cl  ON v.id_cliente    = cl.id_cliente
     ORDER BY f.fecha DESC"
);
$facturas = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Facturación</h4>
        <p class="text-muted small mb-0">Gestión de facturas y pagos</p>
    </div>
</div>

<!-- BUSCADOR -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <div class="input-group">
            <span class="input-group-text bg-white border-end-0">
                <i class="fas fa-search text-muted"></i>
            </span>
            <input type="text" id="buscador" class="form-control border-start-0"
                   placeholder="Buscar por cliente o placa...">
        </div>
    </div>
</div>

<!-- LISTA DE FACTURAS -->
<div id="listaFacturas">
<?php foreach ($facturas as $f):
    $estadoPago = $f['estado_pago'] ?? 'Pendiente';
    $badgeColor = match($estadoPago) {
        'Pagada'  => 'bg-success',
        'Anulada' => 'bg-danger',
        default   => 'bg-warning text-dark'
    };
    $iva      = $f['total'] * 0.19;
    $subtotal = $f['total'] - $iva;
?>
<div class="card shadow-sm mb-3 factura-card"
     data-search="<?= strtolower(($f['cliente_nombre'] ?? '') . ' ' . ($f['placa'] ?? '')) ?>">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <span class="fw-bold">Factura #<?= $f['id_factura'] ?></span>
                <span class="badge <?= $badgeColor ?> ms-2"><?= htmlspecialchars($estadoPago) ?></span>
            </div>
            <div class="text-end">
                <div class="text-muted small">Total</div>
                <div class="text-primary fw-bold fs-5">$<?= number_format($f['total'], 0, ',', '.') ?></div>
            </div>
        </div>

        <div class="row small text-muted mb-3">
            <div class="col-sm-3">
                <div class="fw-semibold text-dark">Cliente</div>
                <div><?= htmlspecialchars($f['cliente_nombre'] ?? '–') ?></div>
            </div>
            <div class="col-sm-3">
                <div class="fw-semibold text-dark">Vehículo</div>
                <div><?= htmlspecialchars($f['placa'] ?? '–') ?></div>
            </div>
            <div class="col-sm-3">
                <div class="fw-semibold text-dark">Fecha Emisión</div>
                <div><?= date('d/m/Y', strtotime($f['fecha'])) ?></div>
            </div>
        </div>

        <div class="row small bg-light rounded p-2 mb-3">
            <div class="col-4">
                <div class="text-muted">Subtotal</div>
                <div class="fw-semibold">$<?= number_format($subtotal, 0, ',', '.') ?></div>
            </div>
            <div class="col-4">
                <div class="text-muted">IVA (19%)</div>
                <div class="fw-semibold">$<?= number_format($iva, 0, ',', '.') ?></div>
            </div>
            <div class="col-4">
                <div class="text-muted">Total</div>
                <div class="fw-bold text-primary">$<?= number_format($f['total'], 0, ',', '.') ?></div>
            </div>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-sm btn-outline-primary"
                    onclick="verDetalle(<?= htmlspecialchars(json_encode($f)) ?>)">
                <i class="fas fa-eye me-1"></i> Ver Detalles
            </button>
            <a href="../../controllers/FacturaController.php?accion=pdf&id=<?= $f['id_factura'] ?>"
               class="btn btn-sm btn-outline-secondary" target="_blank">
                <i class="fas fa-file-pdf me-1"></i> Descargar PDF
            </a>
            <?php if ($estadoPago === 'Pendiente'): ?>
            <form action="../../controllers/FacturaController.php" method="POST" class="ms-auto">
                <input type="hidden" name="accion" value="marcar_pagada">
                <input type="hidden" name="id_factura" value="<?= $f['id_factura'] ?>">
                <button type="submit" class="btn btn-sm btn-success">
                    <i class="fas fa-check me-1"></i> Marcar Pagada
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php if (empty($facturas)): ?>
    <div class="text-center text-muted py-5">
        <i class="fas fa-file-invoice-dollar fa-3x mb-3 opacity-25"></i>
        <p>No hay facturas registradas.</p>
    </div>
<?php endif; ?>
</div>

<!-- MODAL VER DETALLE -->
<div class="modal fade" id="modalDetalle" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="fas fa-file-invoice me-2"></i>Detalle de Factura</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">N° Factura</dt> <dd class="col-sm-7" id="dFactura">–</dd>
                    <dt class="col-sm-5">Cliente</dt>    <dd class="col-sm-7" id="dCliente">–</dd>
                    <dt class="col-sm-5">Vehículo</dt>   <dd class="col-sm-7" id="dVehiculo">–</dd>
                    <dt class="col-sm-5">Fecha</dt>      <dd class="col-sm-7" id="dFecha">–</dd>
                    <dt class="col-sm-5">Total</dt>      <dd class="col-sm-7 text-primary fw-bold" id="dTotal">–</dd>
                    <dt class="col-sm-5">Estado</dt>     <dd class="col-sm-7" id="dEstado">–</dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<?php if ($alert): ?>
<script>
    Swal.fire({
        icon:  '<?= $alert['icon'] ?>',
        title: '<?= $alert['title'] ?>',
        text:  '<?= $alert['text'] ?>',
        confirmButtonColor: '#2563eb'
    });
</script>
<?php endif; ?>

<script>
document.getElementById('buscador').addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.factura-card').forEach(card => {
        card.style.display = card.dataset.search.includes(q) ? '' : 'none';
    });
});

function verDetalle(f) {
    document.getElementById('dFactura').textContent  = '#' + f.id_factura;
    document.getElementById('dCliente').textContent  = f.cliente_nombre ?? '–';
    document.getElementById('dVehiculo').textContent = f.placa ?? '–';
    document.getElementById('dFecha').textContent    = f.fecha;
    document.getElementById('dTotal').textContent    = '$' + parseFloat(f.total).toLocaleString('es-CO');
    document.getElementById('dEstado').textContent   = f.estado_pago ?? 'Pendiente';
    new bootstrap.Modal(document.getElementById('modalDetalle')).show();
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
