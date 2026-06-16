<?php
$titulo = 'Mis Citas';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 3) {
    header("Location: ../usuarios/login.php"); exit;
}
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/cliente_styles.php';

$db        = (new Database())->conectar();
$correo    = $_SESSION['usuario']['correo'] ?? '';
$alert     = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);

$stmtCli = $db->prepare("SELECT id_cliente FROM clientes WHERE correo=:c LIMIT 1");
$stmtCli->execute([':c'=>$correo]);
$idCliente = (int)($stmtCli->fetchColumn() ?: 0);
$idUsuario = (int)($_SESSION['usuario']['id_usuario'] ?? 0);

$vehiculos = [];
if ($idCliente) {
    $s=$db->prepare("SELECT id_vehiculo,placa,marca,modelo,anio FROM vehiculos WHERE id_cliente=:id ORDER BY placa");
    $s->execute([':id'=>$idCliente]);
    $vehiculos=$s->fetchAll(PDO::FETCH_ASSOC);
}

$s=$db->prepare("SELECT c.*,v.placa,v.marca,v.modelo FROM citas c LEFT JOIN vehiculos v ON c.id_vehiculo=v.id_vehiculo WHERE c.id_cliente=:id ORDER BY c.fecha_cita DESC");
$s->execute([':id'=>$idCliente?:$idUsuario]);
$citas=$s->fetchAll(PDO::FETCH_ASSOC);
?>
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>

<!-- Encabezado -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <div class="cs-title"><i class="fas fa-calendar-check me-2 cs-icon-blue"></i>Mis Citas</div>
        <div class="cs-sub">Agenda y gestiona tus citas en el taller</div>
    </div>
    <button class="cs-btn-dark" data-bs-toggle="modal" data-bs-target="#modalAgendar" <?= empty($vehiculos)?'disabled':'' ?>>
        <i class="fas fa-calendar-plus"></i> Agendar Cita
    </button>
</div>

<?php if(empty($vehiculos)): ?>
<div class="cs-empty" style="margin-bottom:1.5rem;">
    <p style="color:#64748b;margin:0;">Necesitas un vehículo registrado para agendar.
    <a href="cliente_vehiculos.php" style="color:#000000;">Registrar vehículo</a></p>
</div>
<?php endif; ?>

<!-- Calendario -->
<div class="cs-cal-card">
    <div class="cs-cal-head">
        <h6><i class="fas fa-calendar-alt me-2 cs-icon-blue"></i>Disponibilidad del Taller</h6>
        <p class="cs-sub" style="margin:0;">
            <span style="color:#dc2626;">●</span> Días ocupados &nbsp;
            <span style="color:#16a34a;">●</span> Disponibles — haz clic para agendar
        </p>
    </div>
    <div style="padding:1rem;"><div id="calendario"></div></div>
</div>

<!-- Tabla citas -->
<div class="cs-card">
    <div class="cs-card-head"><span style="font-weight:700;color:#000000;"><i class="fas fa-list me-2 cs-icon-blue"></i>Mis Citas Agendadas</span></div>
    <div style="overflow-x:auto;">
        <table class="cs-table">
            <thead><tr><th>N° Ref</th><th>Vehículo</th><th>Servicio</th><th>Fecha y Hora</th><th>Estado</th><th>Acción</th></tr></thead>
            <tbody>
            <?php foreach($citas as $c):
                $eb=match($c['estado']){
                    'Pendiente' =>'est-espera',
                    'Confirmada'=>'est-listo',
                    'Cancelada' =>'est-cancelado',
                    'Realizada' =>'est-reparacion',
                    default     =>'est-default',
                };
            ?>
            <tr>
                <td style="font-weight:700;color:#000000;font-family:monospace;"><?= htmlspecialchars($c['numero_ref']) ?></td>
                <td><?php if($c['placa']): ?><div style="font-weight:700;font-family:monospace;font-size:.85rem;"><?= htmlspecialchars($c['placa']) ?></div><div class="cs-muted" style="font-size:.75rem;"><?= htmlspecialchars($c['marca'].' '.$c['modelo']) ?></div><?php else: ?>–<?php endif; ?></td>
                <td><?= htmlspecialchars($c['tipo_servicio']) ?></td>
                <td><?= date('d/m/Y H:i',strtotime($c['fecha_cita'])) ?></td>
                <td><span class="cs-badge <?= $eb ?>"><?= $c['estado'] ?></span></td>
                <td>
                    <?php if(in_array($c['estado'],['Pendiente','Confirmada'])): ?>
                    <button class="cs-btn-dark" style="background:#dc2626;font-size:.75rem;padding:.3rem .7rem;" onclick="confirmarCancelar(<?= $c['id_cita'] ?>)">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <?php else: ?><span class="cs-muted">–</span><?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($citas)): ?>
            <tr><td colspan="6" style="text-align:center;padding:3rem;color:#94a3b8;">
                <i class="fas fa-calendar fa-2x" style="display:block;margin-bottom:.75rem;opacity:.3;"></i>No tienes citas agendadas aún.
            </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal agendar -->
<div class="modal fade cs-modal" id="modalAgendar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-calendar-plus me-2 cs-icon-blue"></i>Agendar Cita</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="../../controllers/ClienteCitaController.php" method="POST">
                <input type="hidden" name="accion" value="agendar">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Vehículo *</label>
                        <select name="id_vehiculo" class="form-select" required>
                            <option value="">Seleccionar vehículo…</option>
                            <?php foreach($vehiculos as $v): ?><option value="<?= $v['id_vehiculo'] ?>"><?= htmlspecialchars($v['placa'].' – '.$v['marca'].' '.$v['modelo'].' ('.($v['anio']??')')) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo de Servicio *</label>
                        <select name="tipo_servicio" class="form-select" required>
                            <option value="">Seleccionar…</option>
                            <option>Latonería</option><option>Pintura completa</option><option>Pintura parcial</option>
                            <option>Enderezado</option><option>Pulida y brillada</option><option>Revisión general</option><option>Otros</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fecha y Hora *</label>
                        <input type="datetime-local" name="fecha_cita" id="inputFechaCita" class="form-control" min="<?= date('Y-m-d\TH:i') ?>" required>
                    </div>
                    <div class="mb-1">
                        <label class="form-label">Notas adicionales</label>
                        <textarea name="notas" class="form-control" rows="2" placeholder="Describe el problema o servicio requerido…"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="cs-btn-dark"><i class="fas fa-calendar-check"></i> Agendar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<form id="formCancelar" action="../../controllers/ClienteCitaController.php" method="POST" style="display:none;">
    <input type="hidden" name="accion" value="cancelar">
    <input type="hidden" name="id_cita" id="cancelarIdCita">
</form>

<?php if($alert): ?>
<script>document.addEventListener('DOMContentLoaded',function(){Swal.fire({icon:'<?= addslashes($alert['icon']) ?>',title:'<?= addslashes($alert['title']) ?>',text:'<?= addslashes($alert['text']) ?>',confirmButtonColor:'#000000'});});</script>
<?php endif; ?>
<script>
document.addEventListener('DOMContentLoaded',function(){
    const cal=new FullCalendar.Calendar(document.getElementById('calendario'),{
        initialView:'dayGridMonth',locale:'es',height:400,
        headerToolbar:{left:'prev,next today',center:'title',right:'dayGridMonth,timeGridWeek'},
        events:'../../controllers/CitasCalendarioController.php',eventColor:'#dc2626',
        dayCellDidMount:function(i){const h=new Date();h.setHours(0,0,0,0);if(i.date<h)return;i.el.style.background='rgba(34,197,94,.06)';i.el.style.cursor='pointer';},
        eventDidMount:function(i){const d=i.event.startStr.substring(0,10);document.querySelectorAll('[data-date="'+d+'"]').forEach(c=>c.style.background='rgba(220,53,69,.1)');},
        dateClick:function(i){const h=new Date();h.setHours(0,0,0,0);if(i.date<h){Swal.fire({icon:'info',title:'Fecha pasada',text:'No puedes agendar en fechas anteriores.',confirmButtonColor:'#000000'});return;}document.getElementById('inputFechaCita').value=i.dateStr+'T08:00';new bootstrap.Modal(document.getElementById('modalAgendar')).show();}
    });
    cal.render();
});
function confirmarCancelar(id){Swal.fire({icon:'warning',title:'¿Cancelar cita?',text:'Esta acción no se puede deshacer.',showCancelButton:true,confirmButtonText:'Sí, cancelar',cancelButtonText:'No',confirmButtonColor:'#dc2626',cancelButtonColor:'#6c757d'}).then(r=>{if(r.isConfirmed){document.getElementById('cancelarIdCita').value=id;document.getElementById('formCancelar').submit();}});}
</script>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
