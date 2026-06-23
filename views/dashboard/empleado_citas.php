<?php
$titulo = 'Citas del Taller';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario']) || (int)($_SESSION['usuario']['rol'] ?? 0) !== 2) {
    header("Location: ../usuarios/login.php"); exit;
}
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../layouts/header.php';

$db    = (new Database())->conectar();
$alert = $_SESSION['alert'] ?? null; unset($_SESSION['alert']);

$todasCitas = [];
try {
    $s = $db->query(
        "SELECT c.*, cl.nombres AS cliente_nombre, cl.telefono AS cliente_telefono,
                v.placa, v.marca, v.modelo
         FROM citas c
         LEFT JOIN clientes cl ON c.id_cliente  = cl.id_cliente
         LEFT JOIN vehiculos v ON c.id_vehiculo = v.id_vehiculo
         ORDER BY c.fecha_cita DESC"
    );
    $todasCitas = $s ? $s->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (Exception $e) {}

$pendientes  = array_filter($todasCitas, fn($c) => $c['estado'] === 'Pendiente');
$confirmadas = array_filter($todasCitas, fn($c) => $c['estado'] === 'Confirmada');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="fas fa-calendar-check me-2"></i>Citas del Taller</h4>
        <p class="text-muted small mb-0">Gestiona y actualiza el estado de las citas</p>
    </div>
</div>

<div class="card mb-3"><div class="card-body py-2">
    <div class="row g-2 align-items-center">
        <div class="col-sm-5">
            <div class="input-group input-group-sm">
                <span class="input-group-text"><i class="fas fa-search text-muted"></i></span>
                <input type="text" id="buscador" class="form-control" placeholder="Buscar por nombre, placa, servicio…">
            </div>
        </div>
        <div class="col-sm-3">
            <input type="date" id="filtrFecha" class="form-control form-control-sm">
        </div>
        <div class="col-sm-4 text-sm-end">
            <button class="btn btn-sm btn-outline-secondary" onclick="limpiarFiltros()"><i class="fas fa-times me-1"></i>Limpiar</button>
        </div>
    </div>
</div></div>

<ul class="nav nav-tabs mb-0">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabPend"><i class="fas fa-clock me-1"></i>Pendientes <span class="badge bg-warning text-dark ms-1"><?= count($pendientes) ?></span></button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabConf"><i class="fas fa-check-circle me-1"></i>Confirmadas <span class="badge bg-success ms-1"><?= count($confirmadas) ?></span></button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabTodas"><i class="fas fa-list me-1"></i>Todas <span class="badge bg-secondary ms-1"><?= count($todasCitas) ?></span></button></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="tabPend"><div class="card" style="border-top:0;"><?= tablaCitas($pendientes) ?></div></div>
    <div class="tab-pane fade" id="tabConf"><div class="card" style="border-top:0;"><?= tablaCitas($confirmadas) ?></div></div>
    <div class="tab-pane fade" id="tabTodas"><div class="card" style="border-top:0;"><?= tablaCitas($todasCitas) ?></div></div>
</div>

<?php
function tablaCitas(array $citas): string {
    $badges=['Pendiente'=>'bg-warning text-dark','Confirmada'=>'bg-success','Cancelada'=>'bg-danger','Realizada'=>'bg-primary'];
    ob_start(); ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 tabla-citas" style="font-size:.875rem;">
            <thead><tr><th>N° Ref</th><th>Cliente</th><th>Vehículo</th><th>Servicio</th><th>Fecha</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
            <?php foreach($citas as $c): $eb=$badges[$c['estado']]??'bg-secondary'; ?>
            <tr data-fecha="<?= substr($c['fecha_cita'],0,10) ?>">
                <td class="fw-semibold"><?= htmlspecialchars($c['numero_ref']) ?></td>
                <td><div class="fw-semibold small"><?= htmlspecialchars($c['cliente_nombre']??'–') ?></div><div class="text-muted" style="font-size:.75rem;"><?= htmlspecialchars($c['cliente_telefono']??'') ?></div></td>
                <td><?php if($c['placa']): ?><span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($c['placa']) ?></span><div class="text-muted" style="font-size:.75rem;"><?= htmlspecialchars($c['marca'].' '.$c['modelo']) ?></div><?php else: ?>–<?php endif; ?></td>
                <td><?= htmlspecialchars($c['tipo_servicio']) ?></td>
                <td><?= date('d/m/Y H:i',strtotime($c['fecha_cita'])) ?></td>
                <td><span class="badge <?= $eb ?>"><?= $c['estado'] ?></span></td>
                <td><div class="d-flex gap-1 flex-wrap">
                <?php if($c['estado']==='Pendiente'): ?>
                    <form action="../../controllers/AdminCitaController.php" method="POST" class="d-inline"><input type="hidden" name="accion" value="confirmar"><input type="hidden" name="id_cita" value="<?= $c['id_cita'] ?>"><button class="btn btn-sm btn-success"><i class="fas fa-check me-1"></i>Confirmar</button></form>
                    <form action="../../controllers/AdminCitaController.php" method="POST" class="d-inline"><input type="hidden" name="accion" value="rechazar"><input type="hidden" name="id_cita" value="<?= $c['id_cita'] ?>"><button class="btn btn-sm btn-danger" onclick="return confirm('¿Rechazar?')"><i class="fas fa-times me-1"></i>Rechazar</button></form>
                <?php elseif($c['estado']==='Confirmada'): ?>
                    <form action="../../controllers/AdminCitaController.php" method="POST" class="d-inline"><input type="hidden" name="accion" value="realizar"><input type="hidden" name="id_cita" value="<?= $c['id_cita'] ?>"><button class="btn btn-sm btn-primary"><i class="fas fa-flag-checkered me-1"></i>Realizada</button></form>
                <?php else: ?><span class="text-muted small">–</span><?php endif; ?>
                </div></td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($citas)): ?><tr><td colspan="7" class="text-center text-muted py-4">No hay citas.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php return ob_get_clean();
}
?>

<?php if($alert): ?><script>document.addEventListener('DOMContentLoaded',function(){Swal.fire({icon:'<?= addslashes($alert['icon']) ?>',title:'<?= addslashes($alert['title']) ?>',text:'<?= addslashes($alert['text']) ?>',confirmButtonColor:'#000'});});</script><?php endif; ?>
<style>.nav-tabs .nav-link{color:#64748b;}.nav-tabs .nav-link.active{color:#1e293b;font-weight:700;}</style>
<script>
function aplicarFiltros(){
    const q=document.getElementById('buscador').value.toLowerCase();
    const f=document.getElementById('filtrFecha').value;
    document.querySelectorAll('.tabla-citas tbody tr[data-fecha]').forEach(tr=>{
        tr.style.display=(!q||tr.textContent.toLowerCase().includes(q))&&(!f||tr.dataset.fecha===f)?'':'none';
    });
}
document.getElementById('buscador').addEventListener('input',aplicarFiltros);
document.getElementById('filtrFecha').addEventListener('change',aplicarFiltros);
function limpiarFiltros(){document.getElementById('buscador').value='';document.getElementById('filtrFecha').value='';aplicarFiltros();}
</script>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
