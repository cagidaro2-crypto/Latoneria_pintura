<?php
$titulo = 'Órdenes de Servicio';
require_once __DIR__ . '/../../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario']) || (int)($_SESSION['usuario']['rol'] ?? 0) !== 2) {
    header("Location: ../usuarios/login.php"); exit;
}

require_once __DIR__ . '/../layouts/header.php';

$db    = (new Database())->conectar();
$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);

$ordenes = [];
try {
    $s = $db->query(
        "SELECT hv.id_historial AS id_orden,
                hv.descripcion, hv.fecha_registro, hv.tipo_reparacion,
                v.id_vehiculo, v.placa, v.marca, v.modelo,
                cl.nombres AS cliente_nombre,
                CONCAT(u.nombres,' ',COALESCE(u.apellidos,'')) AS empleado_nombre,
                ev.estado
         FROM historial_vehiculo hv
         JOIN vehiculos v      ON hv.id_vehiculo = v.id_vehiculo
         JOIN clientes cl      ON v.id_cliente   = cl.id_cliente
         LEFT JOIN usuarios u  ON hv.id_usuario  = u.id_usuario
         JOIN estado_vehiculo ev ON v.id_estado   = ev.id_estado_vehiculo
         ORDER BY hv.fecha_registro DESC"
    );
    $ordenes = $s ? $s->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (Exception $e) {}

$estados = [];
try {
    $estados = $db->query("SELECT * FROM estado_vehiculo ORDER BY id_estado_vehiculo")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="fas fa-clipboard-list me-2"></i>Órdenes de Servicio</h4>
        <p class="text-muted small mb-0">Gestión y seguimiento de órdenes asignadas</p>
    </div>
</div>

<div class="d-flex flex-wrap gap-2 mb-3">
    <button class="btn btn-sm btn-outline-secondary filter-btn active" data-filtro="todos">Todos</button>
    <?php foreach ($estados as $e): ?>
    <button class="btn btn-sm btn-outline-secondary filter-btn" data-filtro="<?= htmlspecialchars($e['estado']) ?>">
        <?= htmlspecialchars($e['estado']) ?>
    </button>
    <?php endforeach; ?>
</div>

<div class="card mb-3"><div class="card-body py-2">
    <div class="input-group">
        <span class="input-group-text"><i class="fas fa-search text-muted"></i></span>
        <input type="text" id="buscador" class="form-control" placeholder="Buscar por placa, cliente, tipo de servicio…">
    </div>
</div></div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tablaOrdenes">
                <thead><tr><th>VEHÍCULO</th><th>CLIENTE</th><th>TIPO</th><th>DESCRIPCIÓN</th><th>EMPLEADO</th><th>ESTADO</th><th>FECHA</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($ordenes as $o):
                    $estado = $o['estado'] ?? '';
                    $badge = match(true) {
                        stripos($estado,'espera')    !==false => 'bg-warning text-dark',
                        stripos($estado,'reparaci')  !==false => 'bg-info text-dark',
                        stripos($estado,'listo')     !==false => 'bg-success',
                        stripos($estado,'entregado') !==false => 'bg-primary',
                        default                               => 'bg-secondary',
                    };
                ?>
                <tr data-estado="<?= htmlspecialchars($estado) ?>">
                    <td><div class="fw-bold font-monospace"><?= htmlspecialchars($o['placa']) ?></div><div class="text-muted small"><?= htmlspecialchars($o['marca'].' '.$o['modelo']) ?></div></td>
                    <td><?= htmlspecialchars($o['cliente_nombre']) ?></td>
                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($o['tipo_reparacion']) ?></span></td>
                    <td class="small text-muted" style="max-width:180px;"><?= htmlspecialchars(mb_strimwidth($o['descripcion']??'',0,60,'…')) ?></td>
                    <td class="small"><?= htmlspecialchars($o['empleado_nombre']??'–') ?></td>
                    <td><span class="badge <?= $badge ?>"><?= htmlspecialchars($estado) ?></span></td>
                    <td class="small text-muted"><?= date('d/m/Y',strtotime($o['fecha_registro'])) ?></td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary" title="Cambiar estado"
                                onclick="cambiarEstado(<?= (int)$o['id_vehiculo'] ?>,'<?= htmlspecialchars(addslashes($o['placa'])) ?>')">
                            <i class="fas fa-arrows-rotate"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($ordenes)): ?>
                <tr id="sinResultados"><td colspan="8" class="text-center text-muted py-4">No hay órdenes registradas.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal cambiar estado -->
<div class="modal fade" id="modalEstado" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header" style="background:#1e293b;">
                <h5 class="modal-title text-white"><i class="fas fa-arrows-rotate me-2"></i>Cambiar Estado</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="../../controllers/VehiculoController.php" method="POST">
                <input type="hidden" name="accion" value="cambiar_estado">
                <input type="hidden" name="id_vehiculo" id="estadoIdVehiculo">
                <div class="modal-body">
                    <p class="text-muted small mb-2">Vehículo: <strong id="estadoPlaca"></strong></p>
                    <select name="id_estado_vehiculo" id="estadoSelect" class="form-select">
                        <?php foreach($estados as $e): ?>
                        <option value="<?= $e['id_estado_vehiculo'] ?>"><?= htmlspecialchars($e['estado']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-dark btn-sm"><i class="fas fa-check me-1"></i>Aplicar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if($alert): ?><script>Swal.fire({icon:'<?= htmlspecialchars($alert['icon']) ?>',title:'<?= htmlspecialchars($alert['title']) ?>',text:'<?= htmlspecialchars($alert['text']) ?>',confirmButtonColor:'#000'});</script><?php endif; ?>

<style>.filter-btn.active{background:#000!important;color:#fff!important;border-color:#000!important;}</style>
<script>
let filtroActivo='todos';
document.querySelectorAll('.filter-btn').forEach(b=>b.addEventListener('click',function(){
    document.querySelectorAll('.filter-btn').forEach(x=>x.classList.remove('active'));
    this.classList.add('active'); filtroActivo=this.dataset.filtro; filtrar();
}));
document.getElementById('buscador').addEventListener('input',filtrar);
function filtrar(){
    const q=document.getElementById('buscador').value.toLowerCase(); let v=0;
    document.querySelectorAll('#tablaOrdenes tbody tr[data-estado]').forEach(tr=>{
        const ok=(!q||tr.textContent.toLowerCase().includes(q))&&(filtroActivo==='todos'||tr.dataset.estado===filtroActivo);
        tr.style.display=ok?'':'none'; if(ok)v++;
    });
    const s=document.getElementById('sinResultados'); if(s)s.style.display=v===0?'':'none';
}
function cambiarEstado(idV,placa){
    document.getElementById('estadoIdVehiculo').value=idV;
    document.getElementById('estadoPlaca').textContent=placa;
    new bootstrap.Modal(document.getElementById('modalEstado')).show();
}
</script>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
