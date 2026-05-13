<?php
$titulo = 'Mis Cotizaciones';
require_once __DIR__ . '/../../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario']) || (int)($_SESSION['usuario']['rol'] ?? 0) !== 2) {
    header("Location: ../usuarios/login.php"); exit;
}

require_once __DIR__ . '/../layouts/header.php';

$db        = (new Database())->conectar();
$correo    = $_SESSION['usuario']['correo'] ?? '';
$alert     = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);

// Buscar id_cliente en tabla cliente por correo de sesión
$stmtCli = $db->prepare("SELECT id_cliente FROM cliente WHERE correo = :correo LIMIT 1");
$stmtCli->execute([':correo' => $correo]);
$idCliente = (int)($stmtCli->fetchColumn() ?: 0);

// Cotizaciones del cliente (via vehiculo)
$cotizaciones = [];
if ($idCliente) {
    $stmtCot = $db->query(
        "SELECT c.*,
                v.placa, v.marca, v.modelo,
                f.id_factura, f.estado_pago
         FROM cotizacion c
         JOIN vehiculo v ON c.id_vehiculo = v.id_vehiculo
         LEFT JOIN factura f ON f.id_cotizacion = c.id_cotizacion
         WHERE v.id_cliente = {$idCliente}
         ORDER BY c.fecha DESC"
    );
    $cotizaciones = $stmtCot ? $stmtCot->fetchAll(PDO::FETCH_ASSOC) : [];
}

// Cotización seleccionada para detalle
$idSel = (int)($_GET['id'] ?? 0);
$cotSel = null;
$detallesServ = [];
$detallesRep  = [];

if ($idSel) {
    $stmtS = $db->prepare(
        "SELECT c.*, v.placa, v.marca, v.modelo
         FROM cotizacion c JOIN vehiculo v ON c.id_vehiculo = v.id_vehiculo
         WHERE c.id_cotizacion = :id"
    );
    $stmtS->execute([':id' => $idSel]);
    $cotSel = $stmtS->fetch(PDO::FETCH_ASSOC);

    if ($cotSel) {
        $stmtDS = $db->prepare(
            "SELECT ds.*, s.nombre AS servicio_nombre
             FROM detalle_servicio ds JOIN servicio s ON ds.id_servicio = s.id_servicio
             WHERE ds.id_cotizacion = :id"
        );
        $stmtDS->execute([':id' => $idSel]);
        $detallesServ = $stmtDS->fetchAll(PDO::FETCH_ASSOC);

        $stmtDR = $db->prepare(
            "SELECT dr.*, r.nombre AS repuesto_nombre
             FROM detalle_repuesto dr JOIN repuesto r ON dr.id_repuesto = r.id_repuesto
             WHERE dr.id_cotizacion = :id"
        );
        $stmtDR->execute([':id' => $idSel]);
        $detallesRep = $stmtDR->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">
            <i class="fas fa-file-invoice text-primary me-2"></i>Mis Cotizaciones
        </h4>
        <p class="text-muted small mb-0">Consulta los presupuestos de tus vehículos</p>
    </div>
</div>

<div class="row g-3">

    <!-- ── Lista de cotizaciones ─────────────────────────────────────────── -->
    <div class="col-lg-7">

        <?php if (empty($cotizaciones)): ?>
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fas fa-file-invoice fa-3x text-muted mb-3 opacity-25"></i>
                <h6 class="text-muted">No tienes cotizaciones aún</h6>
                <p class="text-muted small">Cuando el taller genere un presupuesto para tu vehículo, aparecerá aquí.</p>
            </div>
        </div>
        <?php else: ?>

        <?php foreach ($cotizaciones as $cot):
            $tieneFactura = !empty($cot['id_factura']);
            $estadoPago   = $cot['estado_pago'] ?? null;
            $badgeCot = $tieneFactura
                ? ($estadoPago === 'Pagada' ? ['bg-success','Pagada'] : ['bg-warning text-dark','Pendiente pago'])
                : ['bg-info text-dark','Cotización'];
        ?>
        <div class="card shadow-sm mb-3 <?= $idSel === (int)$cot['id_cotizacion'] ? 'border-primary' : '' ?>">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="fw-bold">Cotización #<?= $cot['id_cotizacion'] ?></span>
                        <span class="badge <?= $badgeCot[0] ?> ms-2"><?= $badgeCot[1] ?></span>
                    </div>
                    <div class="text-primary fw-bold fs-5">
                        $<?= number_format($cot['pago_total'] ?? 0, 0, ',', '.') ?>
                    </div>
                </div>
                <div class="row small text-muted mb-3">
                    <div class="col-6">
                        <div class="fw-semibold text-dark">Vehículo</div>
                        <div><?= htmlspecialchars($cot['placa'] . ' – ' . $cot['marca'] . ' ' . $cot['modelo']) ?></div>
                    </div>
                    <div class="col-6">
                        <div class="fw-semibold text-dark">Fecha</div>
                        <div><?= date('d/m/Y', strtotime($cot['fecha'])) ?></div>
                    </div>
                </div>
                <a href="?id=<?= $cot['id_cotizacion'] ?>"
                   class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-eye me-1"></i> Ver Detalles
                </a>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

    </div>

    <!-- ── Panel detalle ─────────────────────────────────────────────────── -->
    <div class="col-lg-5">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold border-bottom">
                <i class="fas fa-file-invoice text-primary me-2"></i>Detalle de Cotización
            </div>
            <div class="card-body">
                <?php if ($cotSel): ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold">Cotización #<?= $cotSel['id_cotizacion'] ?></span>
                            <span class="text-primary fw-bold">
                                $<?= number_format($cotSel['pago_total'] ?? 0, 0, ',', '.') ?>
                            </span>
                        </div>
                        <div class="text-muted small">
                            Vehículo: <?= htmlspecialchars($cotSel['placa'] . ' – ' . $cotSel['marca'] . ' ' . $cotSel['modelo']) ?>
                        </div>
                        <div class="text-muted small">
                            Fecha: <?= date('d/m/Y', strtotime($cotSel['fecha'])) ?>
                        </div>
                    </div>

                    <?php if (!empty($detallesServ)): ?>
                    <h6 class="fw-semibold mt-3 small">Servicios incluidos</h6>
                    <table class="table table-sm small mb-3">
                        <thead class="table-light">
                            <tr><th>Servicio</th><th>Cant.</th><th>Precio</th><th>Subtotal</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($detallesServ as $ds): ?>
                            <tr>
                                <td><?= htmlspecialchars($ds['servicio_nombre']) ?></td>
                                <td><?= $ds['cantidad'] ?></td>
                                <td>$<?= number_format($ds['precio'], 0, ',', '.') ?></td>
                                <td>$<?= number_format($ds['precio'] * $ds['cantidad'], 0, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>

                    <?php if (!empty($detallesRep)): ?>
                    <h6 class="fw-semibold mt-3 small">Repuestos</h6>
                    <table class="table table-sm small mb-3">
                        <thead class="table-light">
                            <tr><th>Repuesto</th><th>Cant.</th><th>P. Unit.</th><th>Subtotal</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($detallesRep as $dr): ?>
                            <tr>
                                <td><?= htmlspecialchars($dr['repuesto_nombre']) ?></td>
                                <td><?= $dr['cantidad'] ?></td>
                                <td>$<?= number_format($dr['precio_unitario'], 0, ',', '.') ?></td>
                                <td>$<?= number_format($dr['precio_unitario'] * $dr['cantidad'], 0, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>

                    <?php if (empty($detallesServ) && empty($detallesRep)): ?>
                        <p class="text-muted small text-center py-3">Esta cotización no tiene detalles registrados.</p>
                    <?php endif; ?>

                    <div class="bg-light rounded p-3 mt-2">
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Total</span>
                            <span class="text-primary">$<?= number_format($cotSel['pago_total'] ?? 0, 0, ',', '.') ?></span>
                        </div>
                    </div>

                <?php else: ?>
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-mouse-pointer fa-2x mb-3 opacity-25"></i>
                        <p class="small">Selecciona una cotización para ver los detalles</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<?php if ($alert): ?>
<script>
    Swal.fire({
        icon:  '<?= addslashes($alert['icon']) ?>',
        title: '<?= addslashes($alert['title']) ?>',
        text:  '<?= addslashes($alert['text']) ?>',
        confirmButtonColor: '#2563eb'
    });
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
