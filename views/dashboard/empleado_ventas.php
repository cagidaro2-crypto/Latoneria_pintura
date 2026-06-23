<?php
$titulo = 'Ventas';
require_once __DIR__ . '/../../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario']) || (int)($_SESSION['usuario']['rol'] ?? 0) !== 2) {
    header("Location: ../usuarios/login.php"); exit;
}
require_once __DIR__ . '/../layouts/header.php';

$db    = (new Database())->conectar();
$alert = $_SESSION['alert'] ?? null; unset($_SESSION['alert']);

$ventasMes = $ventasHoy = 0; $totalFact = $pagadas = 0;
try {
    $ventasMes = (float)$db->query("SELECT COALESCE(SUM(total),0) FROM facturas WHERE MONTH(fecha)=MONTH(CURDATE()) AND YEAR(fecha)=YEAR(CURDATE())")->fetchColumn();
    $ventasHoy = (float)$db->query("SELECT COALESCE(SUM(total),0) FROM facturas WHERE fecha=CURDATE()")->fetchColumn();
    $totalFact = (int)$db->query("SELECT COUNT(*) FROM facturas WHERE MONTH(fecha)=MONTH(CURDATE()) AND YEAR(fecha)=YEAR(CURDATE())")->fetchColumn();
    $pagadas   = (int)$db->query("SELECT COUNT(*) FROM facturas WHERE estado_pago='Pagada' AND MONTH(fecha)=MONTH(CURDATE()) AND YEAR(fecha)=YEAR(CURDATE())")->fetchColumn();
} catch (Exception $e) {}

$facturas = [];
try {
    $s = $db->query(
        "SELECT f.id_factura, f.fecha, f.total, f.estado_pago,
                cl.nombres AS cliente_nombre,
                v.placa, v.marca, v.modelo,
                s.nombre  AS servicio_nombre
         FROM facturas f
         JOIN cotizaciones c    ON f.id_cotizacion  = c.id_cotizacion
         JOIN vehiculos v       ON c.id_vehiculo    = v.id_vehiculo
         JOIN clientes cl       ON v.id_cliente     = cl.id_cliente
         LEFT JOIN cotizacion_servicios ds ON ds.id_cotizacion = c.id_cotizacion
         LEFT JOIN servicios s  ON ds.id_servicio   = s.id_servicio
         ORDER BY f.fecha DESC, f.id_factura DESC
         LIMIT 30"
    );
    $facturas = $s ? $s->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (Exception $e) {}
?>

<!-- Tarjetas resumen -->
<div class="row g-3 mb-4">
    <?php $stats=[
        ['icon'=>'fa-calendar','bg'=>'bg-primary','val'=>'$'.number_format($ventasMes,0,',','.'),'label'=>'Ventas del Mes'],
        ['icon'=>'fa-dollar-sign','bg'=>'bg-success','val'=>'$'.number_format($ventasHoy,0,',','.'),'label'=>'Ventas Hoy'],
        ['icon'=>'fa-file-invoice','bg'=>'bg-info','val'=>$totalFact,'label'=>'Facturas del Mes'],
        ['icon'=>'fa-check-circle','bg'=>'bg-warning','val'=>$pagadas.' / '.$totalFact,'label'=>'Pagadas'],
    ];
    foreach($stats as $st): ?>
    <div class="col-sm-6 col-xl-3">
        <div class="card"><div class="card-body d-flex align-items-center gap-3">
            <div class="rounded-3 p-3 <?= $st['bg'] ?> bg-opacity-10">
                <i class="fas <?= $st['icon'] ?> fa-lg text-<?= explode('-',$st['bg'])[1] ?>"></i>
            </div>
            <div><div class="text-muted small"><?= $st['label'] ?></div><div class="fw-bold fs-5"><?= $st['val'] ?></div></div>
        </div></div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card mb-3"><div class="card-body py-2">
    <div class="input-group">
        <span class="input-group-text"><i class="fas fa-search text-muted"></i></span>
        <input type="text" id="buscador" class="form-control" placeholder="Buscar por cliente, placa o servicio…">
    </div>
</div></div>

<div class="card">
    <div class="card-header fw-semibold"><i class="fas fa-list me-2"></i>Facturas Recientes</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small" id="tablaVentas">
                <thead><tr><th>#</th><th>CLIENTE</th><th>VEHÍCULO</th><th>SERVICIO</th><th>TOTAL</th><th>ESTADO</th><th>FECHA</th></tr></thead>
                <tbody>
                <?php foreach($facturas as $f):
                    $ep=$f['estado_pago']??'Pendiente';
                    $epB=match($ep){'Pagada'=>'bg-success','Anulada'=>'bg-danger',default=>'bg-warning text-dark'};
                ?>
                <tr>
                    <td class="text-muted">#<?= $f['id_factura'] ?></td>
                    <td class="fw-semibold"><?= htmlspecialchars($f['cliente_nombre']) ?></td>
                    <td><div class="font-monospace fw-bold"><?= htmlspecialchars($f['placa']) ?></div><div class="text-muted" style="font-size:.72rem;"><?= htmlspecialchars($f['marca'].' '.$f['modelo']) ?></div></td>
                    <td><?= htmlspecialchars($f['servicio_nombre']??'–') ?></td>
                    <td class="fw-bold">$<?= number_format($f['total'],0,',','.') ?></td>
                    <td><span class="badge <?= $epB ?>"><?= $ep ?></span></td>
                    <td class="text-muted"><?= date('d/m/Y',strtotime($f['fecha'])) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($facturas)): ?><tr><td colspan="7" class="text-center text-muted py-4">No hay facturas registradas.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if($alert): ?><script>Swal.fire({icon:'<?= htmlspecialchars($alert['icon']) ?>',title:'<?= htmlspecialchars($alert['title']) ?>',text:'<?= htmlspecialchars($alert['text']) ?>',confirmButtonColor:'#000'});</script><?php endif; ?>
<script>document.getElementById('buscador').addEventListener('input',function(){const q=this.value.toLowerCase();document.querySelectorAll('#tablaVentas tbody tr').forEach(tr=>{tr.style.display=tr.textContent.toLowerCase().includes(q)?'':'none';});});</script>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
