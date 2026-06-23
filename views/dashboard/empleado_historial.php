<?php
$titulo = 'Historial de Servicios';
require_once __DIR__ . '/../../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario']) || (int)($_SESSION['usuario']['rol'] ?? 0) !== 2) {
    header("Location: ../usuarios/login.php"); exit;
}
require_once __DIR__ . '/../layouts/header.php';

$db    = (new Database())->conectar();
$alert = $_SESSION['alert'] ?? null; unset($_SESSION['alert']);

$historial = [];
try {
    $s = $db->query(
        "SELECT hv.id_historial, hv.descripcion, hv.fecha_registro, hv.tipo_reparacion,
                v.placa, v.marca, v.modelo,
                cl.nombres AS cliente_nombre,
                CONCAT(u.nombres,' ',COALESCE(u.apellidos,'')) AS empleado_nombre,
                ev.estado AS estado_vehiculo
         FROM historial_vehiculo hv
         JOIN vehiculos v       ON hv.id_vehiculo = v.id_vehiculo
         JOIN clientes cl       ON v.id_cliente   = cl.id_cliente
         LEFT JOIN usuarios u   ON hv.id_usuario  = u.id_usuario
         JOIN estado_vehiculo ev ON v.id_estado   = ev.id_estado_vehiculo
         ORDER BY hv.fecha_registro DESC, hv.id_historial DESC"
    );
    $historial = $s ? $s->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (Exception $e) {}

$estados = [];
try { $estados = $db->query("SELECT * FROM estado_vehiculo ORDER BY id_estado_vehiculo")->fetchAll(PDO::FETCH_ASSOC); } catch(Exception $e){}

$tipos = array_values(array_unique(array_column($historial, 'tipo_reparacion')));
sort($tipos);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-0"><i class="fas fa-history me-2"></i>Historial de Servicios</h4>
        <p class="text-muted small mb-0">Registro completo de trabajos realizados</p>
    </div>
    <span class="badge bg-secondary"><?= count($historial) ?> registros</span>
</div>

<div class="card mb-3"><div class="card-body py-2">
    <div class="row g-2 align-items-center">
        <div class="col-md-5">
            <div class="input-group input-group-sm"><span class="input-group-text"><i class="fas fa-search text-muted"></i></span>
            <input type="text" id="buscador" class="form-control" placeholder="Buscar por placa, cliente, descripción…"></div>
        </div>
        <div class="col-md-3">
            <select id="filtroTipo" class="form-select form-select-sm">
                <option value="">Todos los tipos</option>
                <?php foreach($tipos as $t): ?><option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select id="filtroEstado" class="form-select form-select-sm">
                <option value="">Todos los estados</option>
                <?php foreach($estados as $e): ?><option value="<?= htmlspecialchars($e['estado']) ?>"><?= htmlspecialchars($e['estado']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-1 text-end">
            <button class="btn btn-sm btn-outline-secondary" onclick="limpiarFiltros()"><i class="fas fa-times"></i></button>
        </div>
    </div>
</div></div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small" id="tablaHistorial">
                <thead><tr><th>FECHA</th><th>VEHÍCULO</th><th>CLIENTE</th><th>TIPO</th><th>DESCRIPCIÓN</th><th>EMPLEADO</th><th>ESTADO</th></tr></thead>
                <tbody>
                <?php foreach($historial as $h):
                    $estado=$h['estado_vehiculo']??'';
                    $badge=match(true){
                        stripos($estado,'espera')    !==false=>'bg-warning text-dark',
                        stripos($estado,'reparaci')  !==false=>'bg-info text-dark',
                        stripos($estado,'listo')     !==false=>'bg-success',
                        stripos($estado,'entregado') !==false=>'bg-primary',
                        default=>'bg-secondary',
                    };
                ?>
                <tr data-tipo="<?= htmlspecialchars($h['tipo_reparacion']) ?>" data-estado="<?= htmlspecialchars($estado) ?>">
                    <td class="text-muted"><?= date('d/m/Y',strtotime($h['fecha_registro'])) ?></td>
                    <td><div class="fw-bold font-monospace"><?= htmlspecialchars($h['placa']) ?></div><div class="text-muted" style="font-size:.72rem;"><?= htmlspecialchars($h['marca'].' '.$h['modelo']) ?></div></td>
                    <td><?= htmlspecialchars($h['cliente_nombre']) ?></td>
                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($h['tipo_reparacion']) ?></span></td>
                    <td style="max-width:200px;"><?= htmlspecialchars(mb_strimwidth($h['descripcion']??'',0,80,'…')) ?></td>
                    <td><?= htmlspecialchars($h['empleado_nombre']) ?></td>
                    <td><span class="badge <?= $badge ?>"><?= htmlspecialchars($estado) ?></span></td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($historial)): ?><tr id="sinResultados"><td colspan="7" class="text-center text-muted py-4">No hay registros.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if($alert): ?><script>Swal.fire({icon:'<?= htmlspecialchars($alert['icon']) ?>',title:'<?= htmlspecialchars($alert['title']) ?>',text:'<?= htmlspecialchars($alert['text']) ?>',confirmButtonColor:'#000'});</script><?php endif; ?>
<script>
const bus=document.getElementById('buscador'),fTipo=document.getElementById('filtroTipo'),fEst=document.getElementById('filtroEstado');
function filtrar(){
    const q=bus.value.toLowerCase(),t=fTipo.value,e=fEst.value; let v=0;
    document.querySelectorAll('#tablaHistorial tbody tr[data-tipo]').forEach(tr=>{
        const ok=(!q||tr.textContent.toLowerCase().includes(q))&&(!t||tr.dataset.tipo===t)&&(!e||tr.dataset.estado===e);
        tr.style.display=ok?'':'none'; if(ok)v++;
    });
    const s=document.getElementById('sinResultados'); if(s)s.style.display=v===0?'':'none';
}
function limpiarFiltros(){bus.value='';fTipo.value='';fEst.value='';filtrar();}
bus.addEventListener('input',filtrar); fTipo.addEventListener('change',filtrar); fEst.addEventListener('change',filtrar);
</script>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
