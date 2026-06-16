<?php
$titulo = 'Dashboard';
require_once __DIR__ . '/../../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario']) || (int)($_SESSION['usuario']['rol'] ?? 0) !== 1) {
    header("Location: ../usuarios/login.php"); exit;
}

$usuario = $_SESSION['usuario'];

$formatter = new IntlDateFormatter(
    'es_ES', IntlDateFormatter::FULL, IntlDateFormatter::NONE,
    null, null, "EEEE, dd 'de' MMMM 'de' yyyy"
);
$fecha_actual = $formatter->format(new DateTime());

$db = (new Database())->conectar();

$vehiculosEnProceso = 0;
try { $vehiculosEnProceso = (int)$db->query("SELECT COUNT(*) FROM vehiculos v JOIN estado_vehiculo ev ON v.id_estado=ev.id_estado_vehiculo WHERE ev.estado IN ('En reparación','En espera')")->fetchColumn(); } catch(Exception $e){}

$ventasMes = 0; $ventasMesAnt = 0;
try {
    $ventasMes    = (float)$db->query("SELECT COALESCE(SUM(total),0) FROM facturas WHERE MONTH(fecha)=MONTH(CURDATE()) AND YEAR(fecha)=YEAR(CURDATE())")->fetchColumn();
    $ventasMesAnt = (float)$db->query("SELECT COALESCE(SUM(total),0) FROM facturas WHERE MONTH(fecha)=MONTH(DATE_SUB(CURDATE(),INTERVAL 1 MONTH)) AND YEAR(fecha)=YEAR(DATE_SUB(CURDATE(),INTERVAL 1 MONTH))")->fetchColumn();
} catch(Exception $e){}
$pctVentas = $ventasMesAnt > 0 ? round((($ventasMes-$ventasMesAnt)/$ventasMesAnt)*100,1) : 0;

$ordenesActivas = 0;
try { $ordenesActivas = (int)$db->query("SELECT COUNT(*) FROM ordenes_trabajo WHERE MONTH(fecha_ingreso)=MONTH(CURDATE()) AND YEAR(fecha_ingreso)=YEAR(CURDATE())")->fetchColumn(); } catch(Exception $e){}

$bajoStock = 0;
try { $bajoStock = (int)$db->query("SELECT COUNT(*) FROM inventario WHERE cantidad<=stock_minimo")->fetchColumn(); } catch(Exception $e){}

$vehiculosRecientes = [];
try { $s=$db->query("SELECT v.placa,v.marca,v.modelo,CONCAT(c.nombres,' ',COALESCE(c.apellidos,'')) AS cliente_nombre,ev.estado FROM vehiculos v LEFT JOIN clientes c ON v.id_cliente=c.id_cliente LEFT JOIN estado_vehiculo ev ON v.id_estado=ev.id_estado_vehiculo ORDER BY v.id_vehiculo DESC LIMIT 5"); $vehiculosRecientes=$s?$s->fetchAll(PDO::FETCH_ASSOC):[]; } catch(Exception $e){}

$inventarioBajo = [];
try {
    $stmtIB = $db->query(
        "SELECT p.nombre, i.cantidad, i.stock_minimo, i.unidad,
                cat.nombre AS categoria_nombre
         FROM inventario i
         JOIN productos p ON i.id_producto = p.id_producto
         LEFT JOIN categorias_productos cat ON p.id_categoria = cat.id_categoria
         WHERE i.cantidad <= i.stock_minimo
         ORDER BY (i.cantidad / NULLIF(i.stock_minimo,0)) ASC LIMIT 5"
    );
    $inventarioBajo = $stmtIB ? $stmtIB->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (Exception $e) {}

$totalUsuarios=0;$totalAdmins=0;$totalEmpleados=0;$totalClientes=0;$totalVehiculos=0;$totalOrdenes=0;
try {
    $totalUsuarios  = (int)$db->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
    $totalAdmins    = (int)$db->query("SELECT COUNT(*) FROM usuarios WHERE id_rol=1")->fetchColumn();
    $totalEmpleados = (int)$db->query("SELECT COUNT(*) FROM usuarios WHERE id_rol=2")->fetchColumn();
    $totalClientes  = (int)$db->query("SELECT COUNT(*) FROM usuarios WHERE id_rol=3")->fetchColumn();
    $totalVehiculos = (int)$db->query("SELECT COUNT(*) FROM vehiculos")->fetchColumn();
    $totalOrdenes   = (int)$db->query("SELECT COUNT(*) FROM ordenes_trabajo")->fetchColumn();
} catch(Exception $e){}

$grafVentas=[];
try { $s=$db->query("SELECT DATE_FORMAT(fecha,'%b') AS mes,DATE_FORMAT(fecha,'%Y-%m') AS mes_key,COALESCE(SUM(total),0) AS total FROM facturas WHERE fecha>=DATE_SUB(CURDATE(),INTERVAL 6 MONTH) GROUP BY mes_key,mes ORDER BY mes_key ASC"); $grafVentas=$s?$s->fetchAll(PDO::FETCH_ASSOC):[]; } catch(Exception $e){}

$grafOrdenes=[];
try { $s=$db->query("SELECT DATE_FORMAT(fecha_ingreso,'%a') AS dia,COUNT(*) AS total FROM ordenes_trabajo WHERE fecha_ingreso>=DATE_SUB(CURDATE(),INTERVAL 7 DAY) GROUP BY DATE(fecha_ingreso) ORDER BY DATE(fecha_ingreso) ASC"); $grafOrdenes=$s?$s->fetchAll(PDO::FETCH_ASSOC):[]; } catch(Exception $e){}

require_once __DIR__ . '/../layouts/header.php';
?>

<style>
    .live-badge { display:inline-flex;align-items:center;gap:5px;font-size:.72rem;color:#22c55e; }
    .live-dot   { width:7px;height:7px;border-radius:50%;background:#22c55e;animation:pulse 1.5s infinite; }
    @keyframes pulse { 0%,100%{opacity:1;transform:scale(1);}50%{opacity:.4;transform:scale(1.3);} }
    .stat-change       { font-size:.75rem; }
    .stat-change.up    { color:#22c55e; }
    .stat-change.down  { color:#ef4444; }
    .stat-change.neutral { color:#94a3b8; }

    /* Tarjetas resumen */
    .summary-card {
        border-radius:14px; padding:1.5rem; color:#fff;
        position:relative; overflow:hidden;
        box-shadow:0 4px 12px rgba(0,0,0,.15);
    }
    .summary-card .icon-wrapper {
        width:40px;height:40px;border-radius:8px;
        display:flex;align-items:center;justify-content:center;
        background:rgba(255,255,255,.2);margin-bottom:1rem;font-size:1.25rem;
    }
    .summary-card h6 { font-size:.85rem;font-weight:500;margin-bottom:.5rem;opacity:.9; }
    .summary-card h3 { font-size:2rem;font-weight:700;margin-bottom:.25rem; }
    .summary-card p  { font-size:.75rem;margin:0;opacity:.8; }
    .summary-card .trend-icon { position:absolute;top:1.5rem;right:1.5rem;opacity:.5; }

    .card-1 { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
    .card-2 { background: linear-gradient(135deg, #10b981, #047857); }
    .card-3 { background: linear-gradient(135deg, #8b5cf6, #6d28d9); }
    .card-4 { background: linear-gradient(135deg, #f59e0b, #b45309); }

    /* Paneles */
    .dash-panel {
        background:#fff;border:1px solid #e2e8f0;
        border-radius:14px;box-shadow:0 1px 4px rgba(0,0,0,.06);
        overflow:hidden;
    }
    .dash-panel-head {
        padding:.9rem 1.4rem;border-bottom:1px solid #f1f5f9;
        font-weight:700;color:#1e293b;
        display:flex;align-items:center;justify-content:space-between;
        background:#fff;
    }
    .dash-panel-head i { color:#374151;background:#f1f5f9;padding:.4rem;border-radius:6px;margin-right:.5rem; }

    /* Listas */
    .dash-list { list-style:none;padding:0;margin:0; }
    .dash-list li { display:flex;justify-content:space-between;align-items:center;padding:.85rem 1.4rem;border-bottom:1px solid #f1f5f9; }
    .dash-list li:last-child { border-bottom:none; }
    .dash-list .item-title { font-weight:600;font-size:.88rem;color:#1e293b; }
    .dash-list .item-sub   { font-size:.75rem;color:#64748b; }

    /* Soft badges */
    .bs { padding:.3em .7em;border-radius:6px;font-size:.75rem;font-weight:600; }
    .bs-gray   { background:#f1f5f9;color:#475569; }
    .bs-yellow { background:#fefce8;color:#854d0e; }
    .bs-green  { background:#f0fdf4;color:#15803d; }
    .bs-red    { background:#fef2f2;color:#b91c1c; }
</style>

<!-- Encabezado -->
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h4 style="font-weight:700;color:#1e293b;margin-bottom:.2rem;">Dashboard</h4>
        <p style="color:#64748b;font-size:.88rem;margin:0;">Resumen general del taller — <?= ucfirst($fecha_actual) ?></p>
    </div>
    <div class="text-end">
        <div class="live-badge"><span class="live-dot"></span> En vivo · actualizado <span id="lastUpdate">ahora</span></div>
        <div style="font-size:.7rem;color:#94a3b8;">Se actualiza cada 30 seg.</div>
    </div>
</div>

<!-- Tarjetas resumen -->
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="summary-card card-1"><i class="fas fa-arrow-trend-up trend-icon"></i><div class="icon-wrapper"><i class="fas fa-car-side"></i></div><h6>Vehículos en Proceso</h6><h3 id="stat-vehiculos"><?= $vehiculosEnProceso ?></h3><p class="stat-change neutral" id="stat-vehiculos-sub">En reparación o espera</p></div></div>
    <div class="col-md-3"><div class="summary-card card-2"><i class="fas fa-arrow-trend-up trend-icon"></i><div class="icon-wrapper"><i class="fas fa-dollar-sign"></i></div><h6>Ventas del Mes</h6><h3 id="stat-ventas">$<?= number_format($ventasMes,0,',','.') ?></h3><p class="stat-change <?= $pctVentas>=0?'up':'down' ?>" id="stat-ventas-sub"><?= $pctVentas>=0?'+':'' ?><?= $pctVentas ?>% vs mes anterior</p></div></div>
    <div class="col-md-3"><div class="summary-card card-3"><i class="fas fa-arrow-trend-up trend-icon"></i><div class="icon-wrapper"><i class="fas fa-clipboard-list"></i></div><h6>Órdenes del Mes</h6><h3 id="stat-ordenes"><?= $ordenesActivas ?></h3><p class="stat-change neutral" id="stat-ordenes-sub">Registradas este mes</p></div></div>
    <div class="col-md-3"><div class="summary-card card-4"><i class="fas fa-exclamation-triangle trend-icon"></i><div class="icon-wrapper"><i class="fas fa-box"></i></div><h6>Productos Bajo Stock</h6><h3 id="stat-stock"><?= $bajoStock ?></h3><p class="stat-change <?= $bajoStock>0?'down':'up' ?>" id="stat-stock-sub"><?= $bajoStock>0?'Requiere atención':'Stock en orden' ?></p></div></div>
</div>

<!-- Panel roles -->
<?php
$pctA=$totalUsuarios>0?round($totalAdmins/$totalUsuarios*100):0;
$pctE=$totalUsuarios>0?round($totalEmpleados/$totalUsuarios*100):0;
$pctC=$totalUsuarios>0?round($totalClientes/$totalUsuarios*100):0;
$roles=[
    ['icon'=>'fa-user-shield','val'=>$totalAdmins,   'pct'=>$pctA,'label'=>'Administradores','sub'=>'Gestión total del sistema',  'bar'=>'#ef4444'],
    ['icon'=>'fa-user-tie',   'val'=>$totalEmpleados,'pct'=>$pctE,'label'=>'Empleados',       'sub'=>'Operaciones y órdenes',      'bar'=>'#f59e0b'],
    ['icon'=>'fa-users',      'val'=>$totalClientes, 'pct'=>$pctC,'label'=>'Clientes',        'sub'=>'Propietarios de vehículos',  'bar'=>'#10b981'],
];
?>
<div class="dash-panel mb-4">
    <div class="dash-panel-head"><span><i class="fas fa-sitemap"></i>Estructura del Sistema – Usuarios por Rol</span></div>
    <div style="padding:1.4rem;">
        <div class="row g-3 mb-3">
            <?php foreach($roles as $idx => $r): ?>
            <div class="col-md-4">
                <div class="rol-card" id="rol-card-<?= $idx ?>"
                     style="background:#1e293b;border-radius:14px;padding:1.5rem;text-align:center;color:#fff;box-shadow:0 4px 14px rgba(0,0,0,.15);transition:transform .2s,box-shadow .2s;">
                    <div style="width:58px;height:58px;border-radius:50%;background:rgba(255,255,255,.1);display:flex;align-items:center;justify-content:center;font-size:1.5rem;margin:0 auto .9rem;border:2px solid rgba(255,255,255,.15);">
                        <i class="fas <?= $r['icon'] ?>"></i>
                    </div>
                    <div style="font-size:2.8rem;font-weight:900;line-height:1;margin-bottom:.2rem;"><?= $r['val'] ?></div>
                    <div style="font-size:.92rem;font-weight:700;margin-bottom:.15rem;"><?= $r['label'] ?></div>
                    <div style="font-size:.75rem;opacity:.65;"><?= $r['sub'] ?></div>
                    <div style="height:5px;background:rgba(255,255,255,.12);border-radius:4px;margin-top:.9rem;overflow:hidden;">
                        <div style="height:100%;width:<?= $r['pct'] ?>%;background:<?= $r['bar'] ?>;border-radius:4px;transition:width .6s;"></div>
                    </div>
                    <div style="font-size:.7rem;opacity:.55;margin-top:.3rem;"><?= $r['pct'] ?>% del total</div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <!-- Métricas globales -->
        <div style="border-top:1px solid #e2e8f0;padding-top:1rem;">
            <div class="row text-center">
                <?php
                $metricas=[
                    ['val'=>$totalUsuarios,'label'=>'Usuarios totales','clr'=>'#3b82f6'],
                    ['val'=>$totalVehiculos,'label'=>'Vehículos','clr'=>'#8b5cf6'],
                    ['val'=>$totalOrdenes,'label'=>'Órdenes','clr'=>'#f59e0b'],
                ];
                foreach($metricas as $m): ?>
                <div class="col-4">
                    <div style="font-size:1.6rem;font-weight:800;color:<?= $m['clr'] ?>;"><?= $m['val'] ?></div>
                    <div style="font-size:.78rem;color:#64748b;"><?= $m['label'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Gráficas -->
<div class="row g-3 mb-4">
    <div class="col-md-7">
        <div class="dash-panel">
            <div class="dash-panel-head"><span><i class="fas fa-chart-line"></i>Ventas Mensuales</span></div>
            <div style="padding:1.2rem;"><div style="position:relative;height:240px;"><canvas id="salesChart"></canvas></div></div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="dash-panel">
            <div class="dash-panel-head"><span><i class="fas fa-file-alt"></i>Órdenes Últimos 7 Días</span></div>
            <div style="padding:1.2rem;"><div style="position:relative;height:240px;"><canvas id="ordersChart"></canvas></div></div>
        </div>
    </div>
</div>

<!-- Listas -->
<div class="row g-3">
    <div class="col-md-6">
        <div class="dash-panel">
            <div class="dash-panel-head">
                <span><i class="fas fa-car"></i>Vehículos Recientes</span>
                <a href="admin_vehiculos.php" style="font-size:.78rem;color:#374151;font-weight:600;text-decoration:none;">Ver todos →</a>
            </div>
            <ul class="dash-list" id="lista-vehiculos">
                <?php foreach($vehiculosRecientes as $v):
                    $e=$v['estado']??'';
                    $cls=match(true){stripos($e,'reparaci')!==false=>'bs-yellow',stripos($e,'listo')!==false=>'bs-green',stripos($e,'entregado')!==false=>'bs-green',default=>'bs-gray'};
                ?>
                <li>
                    <div><div class="item-title"><?= htmlspecialchars($v['placa']) ?></div><div class="item-sub"><?= htmlspecialchars($v['marca'].' '.$v['modelo'].' — '.$v['cliente_nombre']) ?></div></div>
                    <span class="bs <?= $cls ?>"><?= htmlspecialchars($e) ?></span>
                </li>
                <?php endforeach; ?>
                <?php if(empty($vehiculosRecientes)): ?><li><div class="item-sub text-center w-100 py-2">Sin vehículos registrados</div></li><?php endif; ?>
            </ul>
        </div>
    </div>
    <div class="col-md-6">
        <div class="dash-panel">
            <div class="dash-panel-head">
                <span><i class="fas fa-box-open"></i>Inventario Bajo Stock</span>
                <a href="admin_inventario.php" style="font-size:.78rem;color:#374151;font-weight:600;text-decoration:none;">Ver todo →</a>
            </div>
            <ul class="dash-list" id="lista-stock">
                <?php if(empty($inventarioBajo)): ?>
                <li><div class="item-sub text-center w-100 py-2"><?= $bajoStock===0?'Todo el stock está en orden ✓':'Sin datos de inventario' ?></div></li>
                <?php else: ?>
                <?php foreach($inventarioBajo as $item): ?>
                <li>
                    <div>
                        <div class="item-title"><?= htmlspecialchars($item['nombre']) ?></div>
                        <div class="item-sub">
                            <?= htmlspecialchars($item['categoria_nombre'] ?? '—') ?> ·
                            Mín: <?= $item['stock_minimo'] ?> <?= htmlspecialchars($item['unidad']) ?>
                        </div>
                    </div>
                    <span class="bs <?= (float)$item['cantidad'] <= 0 ? 'bs-red' : 'bs-yellow' ?>">
                        <?= $item['cantidad'] ?> <?= htmlspecialchars($item['unidad']) ?>
                    </span>
                </li>
                <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const initVentas  = <?= json_encode(array_values($grafVentas)) ?>;
const initOrdenes = <?= json_encode(array_values($grafOrdenes)) ?>;

const salesChart = new Chart(document.getElementById('salesChart'), {
    type:'line',
    data:{
        labels: initVentas.map(r=>r.mes),
        datasets:[{label:'Ventas ($)',data:initVentas.map(r=>parseFloat(r.total)),
            borderColor:'#1e293b',backgroundColor:'rgba(30,41,59,.08)',
            borderWidth:2,pointBackgroundColor:'#fff',pointBorderColor:'#1e293b',
            pointBorderWidth:2,pointRadius:4,fill:true,tension:.4}]
    },
    options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},
        scales:{y:{beginAtZero:true,grid:{borderDash:[5,5]}},x:{grid:{display:false}}}}
});

const ordersChart = new Chart(document.getElementById('ordersChart'), {
    type:'bar',
    data:{
        labels: initOrdenes.length?initOrdenes.map(r=>r.dia):['Sin datos'],
        datasets:[{label:'Órdenes',data:initOrdenes.length?initOrdenes.map(r=>parseInt(r.total)):[0],
            backgroundColor:'#374151',borderRadius:4}]
    },
    options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},
        scales:{y:{beginAtZero:true,ticks:{stepSize:1},grid:{borderDash:[5,5]}},x:{grid:{display:false}}}}
});

function esc(s){return s?String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'):'';}

setInterval(()=>{
    fetch('../../controllers/DashboardDataController.php').then(r=>r.json()).then(data=>{
        if(data.error)return;
        const t=data.tarjetas;
        document.getElementById('stat-vehiculos').textContent=t.vehiculos_proceso;
        document.getElementById('stat-ventas').textContent='$'+parseFloat(t.ventas_mes).toLocaleString('es-CO',{maximumFractionDigits:0});
        document.getElementById('stat-ordenes').textContent=t.ordenes_activas;
        document.getElementById('stat-stock').textContent=t.bajo_stock;
        document.getElementById('lastUpdate').textContent=data.timestamp;
    }).catch(()=>{});
},30000);
</script>

<style>
.rol-card { cursor:default; }
.rol-card:hover { transform:translateY(-4px) !important; box-shadow:0 8px 24px rgba(0,0,0,.25) !important; }
</style>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
