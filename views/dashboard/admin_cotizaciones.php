<?php
$titulo = 'Cotizaciones';
require_once __DIR__ . '/../../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario']) || (int)($_SESSION['usuario']['rol'] ?? 0) !== 1) {
    header("Location: ../usuarios/login.php"); exit;
}

require_once __DIR__ . '/../layouts/header.php';

$db    = (new Database())->conectar();
$alert = $_SESSION['alert'] ?? null; unset($_SESSION['alert']);

// ── Cotizaciones: cotizacion → vehiculo → cliente ─────────────────────────
$stmt = $db->query(
    "SELECT c.*,
            cl.nombres AS cliente_nombre,
            v.placa
     FROM cotizaciones c
     JOIN vehiculos v  ON c.id_vehiculo = v.id_vehiculo
     JOIN clientes cl ON v.id_cliente   = cl.id_cliente
     ORDER BY c.fecha DESC"
);
$cotizaciones = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

// ── Vehículos para el select: vehiculo → cliente ──────────────────────────
$vehiculos = $db->query(
    "SELECT v.id_vehiculo,
            v.placa,
            v.marca,
            v.modelo,
            cl.nombres AS cliente_nombre
     FROM vehiculos v
     JOIN clientes cl ON v.id_cliente = cl.id_cliente
     ORDER BY v.placa"
)->fetchAll(PDO::FETCH_ASSOC);

// ── Servicios disponibles ─────────────────────────────────────────────────
$servicios = [];
try { $servicios = $db->query("SELECT * FROM servicios ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC); } catch(Exception $e) {}

// ── Cotización seleccionada para detalle ──────────────────────────────────
$idSeleccionada  = (int)($_GET['id'] ?? 0);
$cotSeleccionada = null;
$detallesServ    = [];
$detallesRep     = [];

if ($idSeleccionada) {
    $stmtCot = $db->prepare(
        "SELECT c.*, cl.nombres AS cliente_nombre, v.placa
         FROM cotizaciones c
         JOIN vehiculos v  ON c.id_vehiculo = v.id_vehiculo
         JOIN clientes cl ON v.id_cliente  = cl.id_cliente
         WHERE c.id_cotizacion = :id"
    );
    $stmtCot->execute([':id' => $idSeleccionada]);
    $cotSeleccionada = $stmtCot->fetch(PDO::FETCH_ASSOC);

    $stmtDs = $db->prepare(
        "SELECT ds.*, s.nombre AS servicio_nombre
         FROM cotizacion_servicios ds
         JOIN servicios s ON ds.id_servicio = s.id_servicio
         WHERE ds.id_cotizacion = :id"
    );
    $stmtDs->execute([':id' => $idSeleccionada]);
    $detallesServ = $stmtDs->fetchAll(PDO::FETCH_ASSOC);

    $stmtDr = $db->prepare(
        "SELECT dr.*, p.nombre AS repuesto_nombre
         FROM cotizacion_productos dr
         JOIN productos p ON dr.id_producto = p.id_producto
         WHERE dr.id_cotizacion = :id"
    );
    $stmtDr->execute([':id' => $idSeleccionada]);
    $detallesRep = $stmtDr->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Cotizaciones</h4>
        <p class="text-muted small mb-0">Gestión de cotizaciones y presupuestos</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevaCot">
        <i class="fas fa-plus me-1"></i> Nueva Cotización
    </button>
</div>

<div class="row g-3">
    <!-- LISTA COTIZACIONES -->
    <div class="col-lg-7">
        <!-- Buscador -->
        <div class="input-group mb-3">
            <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
            <input type="text" id="buscador" class="form-control border-start-0" placeholder="Buscar cotizaciones...">
        </div>

        <div id="listaCotizaciones">
        <?php foreach ($cotizaciones as $cot): ?>
        <?php
        $badgeColor = match($cot['estado'] ?? 'Pendiente') {
            'Aprobada'  => 'bg-success',
            'Rechazada' => 'bg-danger',
            'Expirada'  => 'bg-secondary',
            default     => 'bg-warning text-dark'
        };
        ?>
        <div class="card shadow-sm mb-3 cot-card" data-search="<?= strtolower($cot['cliente_nombre'] . ' ' . $cot['placa']) ?>">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="fw-bold">Cotización #<?= $cot['id_cotizacion'] ?></span>
                        <span class="badge <?= $badgeColor ?> ms-2"><?= htmlspecialchars($cot['estado'] ?? 'Pendiente') ?></span>
                    </div>
                    <div class="text-primary fw-bold fs-5">$<?= number_format($cot['pago_total'] ?? 0, 0, ',', '.') ?></div>
                </div>
                <div class="row small text-muted mb-3">
                    <div class="col-6">
                        <div><strong>Cliente</strong></div>
                        <div><?= htmlspecialchars($cot['cliente_nombre']) ?></div>
                    </div>
                    <div class="col-6">
                        <div><strong>Vehículo</strong></div>
                        <div><?= htmlspecialchars($cot['placa']) ?></div>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <form action="../../controllers/AdminCotizacionController.php" method="POST" class="d-inline">
                        <input type="hidden" name="accion" value="aprobar">
                        <input type="hidden" name="id_cotizacion" value="<?= $cot['id_cotizacion'] ?>">
                        <button type="submit" class="btn btn-sm btn-success">
                            <i class="fas fa-check me-1"></i> Aprobar
                        </button>
                    </form>
                    <form action="../../controllers/AdminCotizacionController.php" method="POST" class="d-inline">
                        <input type="hidden" name="accion" value="rechazar">
                        <input type="hidden" name="id_cotizacion" value="<?= $cot['id_cotizacion'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">
                            <i class="fas fa-times me-1"></i> Rechazar
                        </button>
                    </form>
                    <a href="?id=<?= $cot['id_cotizacion'] ?>" class="btn btn-sm btn-outline-primary ms-auto">
                        <i class="fas fa-eye me-1"></i> Ver Detalles
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($cotizaciones)): ?>
            <div class="text-center text-muted py-5">
                <i class="fas fa-file-invoice fa-3x mb-3 opacity-25"></i>
                <p>No hay cotizaciones registradas.</p>
            </div>
        <?php endif; ?>
        </div>
    </div>

    <!-- PANEL DETALLE -->
    <div class="col-lg-5">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold border-bottom">
                <i class="fas fa-file-invoice text-primary me-2"></i>Detalles de Cotización
            </div>
            <div class="card-body">
                <?php if ($cotSeleccionada): ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold">Cotización #<?= $cotSeleccionada['id_cotizacion'] ?></span>
                            <span class="text-primary fw-bold">$<?= number_format($cotSeleccionada['pago_total'] ?? 0, 0, ',', '.') ?></span>
                        </div>
                        <div class="text-muted small">Cliente: <?= htmlspecialchars($cotSeleccionada['cliente_nombre']) ?> | Vehículo: <?= htmlspecialchars($cotSeleccionada['placa']) ?></div>
                        <div class="text-muted small">Fecha: <?= date('d/m/Y', strtotime($cotSeleccionada['fecha'])) ?></div>
                    </div>
                    <?php if (!empty($detallesServ)): ?>
                    <h6 class="fw-semibold mt-3">Servicios</h6>
                    <table class="table table-sm small">
                        <thead class="table-light"><tr><th>Servicio</th><th>Cant.</th><th>Precio</th></tr></thead>
                        <tbody>
                        <?php foreach ($detallesServ as $ds): ?>
                            <tr>
                                <td><?= htmlspecialchars($ds['servicio_nombre']) ?></td>
                                <td><?= $ds['cantidad'] ?></td>
                                <td>$<?= number_format($ds['precio'], 0, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                    <?php if (!empty($detallesRep)): ?>
                    <h6 class="fw-semibold mt-3">Repuestos</h6>
                    <table class="table table-sm small">
                        <thead class="table-light"><tr><th>Repuesto</th><th>Cant.</th><th>P. Unit.</th></tr></thead>
                        <tbody>
                        <?php foreach ($detallesRep as $dr): ?>
                            <tr>
                                <td><?= htmlspecialchars($dr['repuesto_nombre']) ?></td>
                                <td><?= $dr['cantidad'] ?></td>
                                <td>$<?= number_format($dr['precio_unitario'], 0, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-mouse-pointer fa-2x mb-3 opacity-25"></i>
                        <p>Selecciona una cotización para ver los detalles</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- MODAL NUEVA COTIZACIÓN -->
<div class="modal fade" id="modalNuevaCot" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4">
            <div class="modal-header" style="background:linear-gradient(90deg,#1a3a6b,#2563eb);">
                <h5 class="modal-title text-white"><i class="fas fa-plus me-2"></i>Nueva Cotización</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="../../controllers/AdminCotizacionController.php" method="POST">
                <input type="hidden" name="accion" value="crear">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Vehículo *</label>
                            <select name="id_vehiculo" class="form-select" required>
                                <option value="">Seleccionar vehículo…</option>
                                <?php foreach ($vehiculos as $v): ?>
                                    <option value="<?= $v['id_vehiculo'] ?>">
                                        <?= htmlspecialchars($v['placa'] . ' – ' . $v['marca'] . ' ' . $v['modelo'] . ' (' . $v['cliente_nombre'] . ')') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Fecha *</label>
                            <input type="date" name="fecha" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Servicios</label>
                            <div id="serviciosContainer">
                                <div class="row g-2 mb-2 servicio-row">
                                    <div class="col-6">
                                        <select name="servicios[]" class="form-select form-select-sm">
                                            <option value="">Seleccionar servicio…</option>
                                            <?php foreach ($servicios as $s): ?>
                                                <option value="<?= $s['id_servicio'] ?>"><?= htmlspecialchars($s['nombre']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-3">
                                        <input type="number" name="serv_cantidad[]" class="form-control form-control-sm" placeholder="Cant." min="1" value="1">
                                    </div>
                                    <div class="col-3">
                                        <input type="number" name="serv_precio[]" class="form-control form-control-sm" placeholder="Precio" min="0" step="0.01">
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary mt-1" onclick="agregarServicio()">
                                <i class="fas fa-plus me-1"></i> Agregar servicio
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Cotización</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($alert): ?>
<script>
    Swal.fire({ icon:'<?= $alert['icon'] ?>', title:'<?= $alert['title'] ?>', text:'<?= $alert['text'] ?>', confirmButtonColor:'#2563eb' });
</script>
<?php endif; ?>

<script>
document.getElementById('buscador').addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.cot-card').forEach(card => {
        card.style.display = card.dataset.search.includes(q) ? '' : 'none';
    });
});

function agregarServicio() {
    const container = document.getElementById('serviciosContainer');
    const first = container.querySelector('.servicio-row');
    const clone = first.cloneNode(true);
    clone.querySelectorAll('input').forEach(i => i.value = '');
    clone.querySelectorAll('select').forEach(s => s.selectedIndex = 0);
    container.appendChild(clone);
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
