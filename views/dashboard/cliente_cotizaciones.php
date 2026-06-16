<?php
$titulo = 'Mis Cotizaciones';
require_once __DIR__ . '/../../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario']) || (int)($_SESSION['usuario']['rol']??0) !== 3) { header("Location: ../usuarios/login.php"); exit; }
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/cliente_styles.php';

$db=$db=(new Database())->conectar();
$correo=$_SESSION['usuario']['correo']??'';
$alert=$_SESSION['alert']??null; unset($_SESSION['alert']);

$s=$db->prepare("SELECT id_cliente FROM clientes WHERE correo=:c LIMIT 1");
$s->execute([':c'=>$correo]); $idCliente=(int)($s->fetchColumn()?:0);

$cotizaciones=[];
if($idCliente){
    try{$s=$db->prepare("SELECT c.*,v.placa,v.marca,v.modelo,f.id_factura,f.estado_pago FROM cotizaciones c JOIN vehiculos v ON c.id_vehiculo=v.id_vehiculo LEFT JOIN facturas f ON f.id_cotizacion=c.id_cotizacion WHERE c.id_cliente=:id ORDER BY c.fecha DESC");$s->execute([':id'=>$idCliente]);$cotizaciones=$s->fetchAll(PDO::FETCH_ASSOC);}catch(Exception $e){}
}

$idSel=(int)($_GET['id']??0); $cotSel=null; $detallesServ=[]; $detallesRep=[];
if($idSel&&$idCliente){
    $s=$db->prepare("SELECT c.*,v.placa,v.marca,v.modelo FROM cotizaciones c JOIN vehiculos v ON c.id_vehiculo=v.id_vehiculo WHERE c.id_cotizacion=:id AND c.id_cliente=:cli");
    $s->execute([':id'=>$idSel,':cli'=>$idCliente]); $cotSel=$s->fetch(PDO::FETCH_ASSOC);
    if($cotSel){
        try{$s=$db->prepare("SELECT cs.*,s.nombre AS servicio_nombre FROM cotizacion_servicios cs JOIN servicios s ON cs.id_servicio=s.id_servicio WHERE cs.id_cotizacion=:id");$s->execute([':id'=>$idSel]);$detallesServ=$s->fetchAll(PDO::FETCH_ASSOC);}catch(Exception $e){}
        try{$s=$db->prepare("SELECT cp.*,p.nombre AS repuesto_nombre FROM cotizacion_productos cp JOIN productos p ON cp.id_producto=p.id_producto WHERE cp.id_cotizacion=:id");$s->execute([':id'=>$idSel]);$detallesRep=$s->fetchAll(PDO::FETCH_ASSOC);}catch(Exception $e){}
    }
}
?>

<!-- Encabezado -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <div class="cs-title"><i class="fas fa-file-invoice me-2 cs-icon-blue"></i>Mis Cotizaciones</div>
        <div class="cs-sub">Consulta los presupuestos de tus vehículos</div>
    </div>
</div>

<div class="row g-3">
<!-- Lista -->
<div class="col-lg-7">
    <?php if(empty($cotizaciones)): ?>
    <div class="cs-empty">
        <i class="fas fa-file-invoice fa-3x cs-empty-icon"></i>
        <h6 class="cs-empty-title">No tienes cotizaciones aún</h6>
        <p class="cs-empty-sub">Cuando el taller genere un presupuesto para tu vehículo, aparecerá aquí.</p>
    </div>
    <?php else: ?>
    <?php foreach($cotizaciones as $cot):
        $tieneFac=!empty($cot['id_factura']);
        $ep=$cot['estado_pago']??null;
        [$bc,$bl]=$tieneFac?($ep==='Pagada'?['est-listo','Pagada']:['est-espera','Pago pendiente']):['est-reparacion','Cotización'];
    ?>
    <div class="cs-card mb-3" style="<?= $idSel===(int)$cot['id_cotizacion']?'border-color:#000000;':'' ?>">
        <div class="cs-card-body">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <span style="font-weight:700;color:#000000;">Cotización #<?= $cot['id_cotizacion'] ?></span>
                    <span class="cs-badge <?= $bc ?> ms-2"><?= $bl ?></span>
                </div>
                <span style="font-weight:800;color:#000000;font-size:1.05rem;">$<?= number_format($cot['pago_total']??0,0,',','.') ?></span>
            </div>
            <div class="row" style="font-size:.82rem;color:#64748b;margin-bottom:.85rem;">
                <div class="col-6"><div style="font-weight:600;color:#000000;margin-bottom:.1rem;">Vehículo</div><?= htmlspecialchars($cot['placa'].' – '.$cot['marca'].' '.$cot['modelo']) ?></div>
                <div class="col-6"><div style="font-weight:600;color:#000000;margin-bottom:.1rem;">Fecha</div><?= date('d/m/Y',strtotime($cot['fecha'])) ?></div>
            </div>
            <a href="?id=<?= $cot['id_cotizacion'] ?>" class="cs-btn-dark" style="font-size:.78rem;padding:.32rem .8rem;">
                <i class="fas fa-eye"></i> Ver Detalles
            </a>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Detalle -->
<div class="col-lg-5">
    <div class="cs-card h-100">
        <div class="cs-card-head"><span style="font-weight:700;color:#000000;"><i class="fas fa-file-invoice me-2 cs-icon-blue"></i>Detalle de Cotización</span></div>
        <div class="cs-card-body">
            <?php if($cotSel): ?>
            <div class="mb-3 d-flex justify-content-between align-items-start">
                <div>
                    <div style="font-weight:700;color:#000000;">Cotización #<?= $cotSel['id_cotizacion'] ?></div>
                    <div class="cs-muted" style="font-size:.82rem;"><?= htmlspecialchars($cotSel['placa'].' – '.$cotSel['marca'].' '.$cotSel['modelo']) ?></div>
                    <div class="cs-muted" style="font-size:.8rem;"><?= date('d/m/Y',strtotime($cotSel['fecha'])) ?></div>
                </div>
                <span style="font-weight:800;font-size:1.1rem;color:#000000;">$<?= number_format($cotSel['pago_total']??0,0,',','.') ?></span>
            </div>
            <?php if(!empty($detallesServ)): ?>
            <div style="font-weight:700;font-size:.8rem;color:#000000;margin-bottom:.5rem;">Servicios</div>
            <table class="cs-table" style="margin-bottom:1rem;font-size:.8rem;">
                <thead><tr><th>Servicio</th><th>Cant.</th><th>Precio</th><th>Subtotal</th></tr></thead>
                <tbody><?php foreach($detallesServ as $ds): ?><tr><td><?= htmlspecialchars($ds['servicio_nombre']) ?></td><td><?= $ds['cantidad'] ?></td><td>$<?= number_format($ds['precio'],0,',','.') ?></td><td>$<?= number_format($ds['precio']*$ds['cantidad'],0,',','.') ?></td></tr><?php endforeach; ?></tbody>
            </table>
            <?php endif; ?>
            <?php if(!empty($detallesRep)): ?>
            <div style="font-weight:700;font-size:.8rem;color:#000000;margin-bottom:.5rem;">Repuestos</div>
            <table class="cs-table" style="margin-bottom:1rem;font-size:.8rem;">
                <thead><tr><th>Repuesto</th><th>Cant.</th><th>P.Unit.</th><th>Subtotal</th></tr></thead>
                <tbody><?php foreach($detallesRep as $dr): ?><tr><td><?= htmlspecialchars($dr['repuesto_nombre']) ?></td><td><?= $dr['cantidad'] ?></td><td>$<?= number_format($dr['precio_unit'],0,',','.') ?></td><td>$<?= number_format($dr['precio_unit']*$dr['cantidad'],0,',','.') ?></td></tr><?php endforeach; ?></tbody>
            </table>
            <?php endif; ?>
            <?php if(empty($detallesServ)&&empty($detallesRep)): ?><p class="cs-muted" style="text-align:center;font-size:.85rem;">Sin detalles registrados.</p><?php endif; ?>
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:.75rem 1rem;display:flex;justify-content:space-between;align-items:center;">
                <span style="font-weight:700;color:#000000;">Total</span>
                <span style="font-weight:800;font-size:1.05rem;color:#000000;">$<?= number_format($cotSel['pago_total']??0,0,',','.') ?></span>
            </div>
            <?php else: ?>
            <div style="text-align:center;padding:3rem 1rem;color:#94a3b8;">
                <i class="fas fa-mouse-pointer fa-2x" style="display:block;margin-bottom:.75rem;opacity:.3;"></i>
                <p style="font-size:.88rem;margin:0;">Selecciona una cotización para ver los detalles</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>

<?php if($alert): ?><script>Swal.fire({icon:'<?= addslashes($alert['icon']) ?>',title:'<?= addslashes($alert['title']) ?>',text:'<?= addslashes($alert['text']) ?>',confirmButtonColor:'#000000'});</script><?php endif; ?>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
