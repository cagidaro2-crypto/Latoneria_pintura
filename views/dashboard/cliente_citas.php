<?php
$titulo = 'Mis Citas';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 2) {
    header("Location: ../usuarios/login.php"); exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../layouts/header.php';

$db        = (new Database())->conectar();
$idPersona = (int)($_SESSION['usuario']['id_usuario'] ?? 0);
$correo    = $_SESSION['usuario']['correo'] ?? '';
$alert     = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);

// Buscar id_cliente en tabla cliente por correo de sesión
$stmtCli = $db->prepare("SELECT id_cliente FROM cliente WHERE correo = :correo LIMIT 1");
$stmtCli->execute([':correo' => $correo]);
$idCliente = (int)($stmtCli->fetchColumn() ?: 0);

// Vehículos del cliente
$vehiculos = [];
if ($idCliente) {
    $stmtVeh = $db->prepare(
        "SELECT v.id_vehiculo, v.placa, v.marca, v.modelo, v.`año`
         FROM vehiculo v
         WHERE v.id_cliente = :id
         ORDER BY v.placa ASC"
    );
    $stmtVeh->execute([':id' => $idCliente]);
    $vehiculos = $stmtVeh->fetchAll(PDO::FETCH_ASSOC);
}

// Citas del cliente (id_cliente = id_persona de sesión)
$stmtCitas = $db->prepare(
    "SELECT c.*, v.placa, v.marca, v.modelo
     FROM citas c
     LEFT JOIN vehiculo v ON c.id_vehiculo = v.id_vehiculo
     WHERE c.id_cliente = :id
     ORDER BY c.fecha_cita DESC"
);
$stmtCitas->execute([':id' => $idPersona]);
$citas = $stmtCitas->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- FullCalendar -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="fas fa-calendar-check text-primary me-2"></i>Mis Citas</h4>
        <p class="text-muted small mb-0">Agenda y gestiona tus citas en el taller</p>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAgendar"
            <?= empty($vehiculos) ? 'disabled title="Registra un vehículo primero"' : '' ?>>
        <i class="fas fa-calendar-plus me-1"></i> Agendar Cita
    </button>
</div>

<?php if (empty($vehiculos)): ?>
<div class="alert alert-info d-flex align-items-center gap-2 mb-4">
    <i class="fas fa-info-circle fa-lg"></i>
    <div>
        Necesitas tener un vehículo registrado para agendar una cita.
        <a href="cliente_vehiculos.php" class="alert-link ms-1">Registrar vehículo</a>
    </div>
</div>
<?php endif; ?>

<!-- ══ CALENDARIO ══════════════════════════════════════════════════════════ -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="fw-semibold mb-0"><i class="fas fa-calendar-alt text-primary me-2"></i>Disponibilidad del Taller</h6>
        <p class="text-muted small mb-0 mt-1">
            <span class="badge bg-danger me-1">●</span>Días ocupados &nbsp;
            <span class="badge bg-success me-1">●</span>Días disponibles — Haz clic en un día disponible para agendar
        </p>
    </div>
    <div class="card-body">
        <div id="calendario"></div>
    </div>
</div>

<!-- ══ LISTA DE CITAS ══════════════════════════════════════════════════════ -->
<div class="card shadow-sm">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="fw-semibold mb-0"><i class="fas fa-list text-primary me-2"></i>Mis Citas Agendadas</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:0.875rem;">
                <thead class="table-light">
                    <tr>
                        <th style="font-size:0.78rem;">N° Ref</th>
                        <th style="font-size:0.78rem;">Vehículo</th>
                        <th style="font-size:0.78rem;">Servicio</th>
                        <th style="font-size:0.78rem;">Fecha y Hora</th>
                        <th style="font-size:0.78rem;">Estado</th>
                        <th style="font-size:0.78rem;">Acción</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($citas as $c):
                    $eBadge = [
                        'Pendiente'  => 'bg-warning text-dark',
                        'Confirmada' => 'bg-success',
                        'Cancelada'  => 'bg-danger',
                        'Realizada'  => 'bg-primary',
                    ];
                    $eb = $eBadge[$c['estado']] ?? 'bg-secondary';
                ?>
                <tr>
                    <td class="fw-semibold text-primary"><?= htmlspecialchars($c['numero_ref']) ?></td>
                    <td>
                        <?php if ($c['placa']): ?>
                            <div class="fw-semibold font-monospace small"><?= htmlspecialchars($c['placa']) ?></div>
                            <div class="text-muted" style="font-size:0.78rem;"><?= htmlspecialchars($c['marca'] . ' ' . $c['modelo']) ?></div>
                        <?php else: ?>–<?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($c['tipo_servicio']) ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($c['fecha_cita'])) ?></td>
                    <td><span class="badge <?= $eb ?>"><?= $c['estado'] ?></span></td>
                    <td>
                        <?php if (in_array($c['estado'], ['Pendiente', 'Confirmada'])): ?>
                        <button class="btn btn-sm btn-outline-danger"
                                onclick="confirmarCancelar(<?= $c['id_cita'] ?>)">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </button>
                        <?php else: ?>
                        <span class="text-muted">–</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($citas)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-5">
                        <i class="fas fa-calendar fa-2x mb-2 opacity-25 d-block"></i>
                        No tienes citas agendadas aún.
                    </td>
                </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ══ MODAL AGENDAR ════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalAgendar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4">
            <div class="modal-header" style="background:linear-gradient(90deg,#1a3a6b,#2563eb);">
                <h5 class="modal-title text-white">
                    <i class="fas fa-calendar-plus me-2"></i>Agendar Cita
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="../../controllers/ClienteCitaController.php" method="POST">
                <input type="hidden" name="accion" value="agendar">
                <input type="hidden" name="fecha_preseleccionada" id="fechaPreseleccionada">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:0.78rem;">Vehículo *</label>
                        <select name="id_vehiculo" class="form-select" style="font-size:0.875rem;" required>
                            <option value="">Seleccionar vehículo…</option>
                            <?php foreach ($vehiculos as $v): ?>
                                <option value="<?= $v['id_vehiculo'] ?>">
                                    <?= htmlspecialchars($v['placa'] . ' – ' . $v['marca'] . ' ' . $v['modelo'] . ' (' . $v['año'] . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:0.78rem;">Tipo de Servicio *</label>
                        <select name="tipo_servicio" class="form-select" style="font-size:0.875rem;" required>
                            <option value="">Seleccionar…</option>
                            <option>Latonería</option>
                            <option>Pintura completa</option>
                            <option>Pintura parcial</option>
                            <option>Enderezado</option>
                            <option>Pulida y brillada</option>
                            <option>Revisión general</option>
                            <option>Otros</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:0.78rem;">Fecha y Hora *</label>
                        <input type="datetime-local" name="fecha_cita" id="inputFechaCita"
                               class="form-control" style="font-size:0.875rem;"
                               min="<?= date('Y-m-d\TH:i') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:0.78rem;">Notas adicionales</label>
                        <textarea name="notas" class="form-control" style="font-size:0.875rem;" rows="2"
                                  placeholder="Describe brevemente el problema o servicio requerido…"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-calendar-check me-1"></i>Agendar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Formulario oculto para cancelar -->
<form id="formCancelar" action="../../controllers/ClienteCitaController.php" method="POST" style="display:none;">
    <input type="hidden" name="accion" value="cancelar">
    <input type="hidden" name="id_cita" id="cancelarIdCita">
</form>

<?php if ($alert): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        icon:  '<?= addslashes($alert['icon']) ?>',
        title: '<?= addslashes($alert['title']) ?>',
        text:  '<?= addslashes($alert['text']) ?>',
        confirmButtonColor: '#2563eb'
    });
});
</script>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Calendario FullCalendar ──────────────────────────────────────────
    const calEl = document.getElementById('calendario');
    const cal   = new FullCalendar.Calendar(calEl, {
        initialView:   'dayGridMonth',
        locale:        'es',
        height:        420,
        headerToolbar: {
            left:   'prev,next today',
            center: 'title',
            right:  'dayGridMonth,timeGridWeek'
        },
        events: '../../controllers/CitasCalendarioController.php',
        eventColor: '#dc3545',

        // Colorear días: ocupados=rojo, disponibles=verde
        dayCellDidMount: function(info) {
            const hoy = new Date();
            hoy.setHours(0,0,0,0);
            if (info.date < hoy) return; // pasados sin color
            info.el.style.backgroundColor = 'rgba(34,197,94,0.08)';
            info.el.style.cursor = 'pointer';
        },

        eventDidMount: function(info) {
            // Marcar el día del evento como ocupado (rojo)
            const dateStr = info.event.startStr.substring(0,10);
            document.querySelectorAll('[data-date="' + dateStr + '"]').forEach(function(cell) {
                cell.style.backgroundColor = 'rgba(220,53,69,0.12)';
            });
        },

        dateClick: function(info) {
            const hoy = new Date();
            hoy.setHours(0,0,0,0);
            if (info.date < hoy) {
                Swal.fire({ icon:'info', title:'Fecha pasada', text:'No puedes agendar en fechas anteriores.', confirmButtonColor:'#2563eb' });
                return;
            }
            // Precargar fecha en el modal
            const fechaLocal = info.dateStr + 'T08:00';
            document.getElementById('inputFechaCita').value = fechaLocal;
            new bootstrap.Modal(document.getElementById('modalAgendar')).show();
        }
    });
    cal.render();
});

// ── Cancelar cita con SweetAlert2 ───────────────────────────────────────
function confirmarCancelar(idCita) {
    Swal.fire({
        icon:              'warning',
        title:             '¿Cancelar cita?',
        text:              'Esta acción no se puede deshacer.',
        showCancelButton:  true,
        confirmButtonText: 'Sí, cancelar',
        cancelButtonText:  'No',
        confirmButtonColor:'#dc3545',
        cancelButtonColor: '#6c757d'
    }).then(function(result) {
        if (result.isConfirmed) {
            document.getElementById('cancelarIdCita').value = idCita;
            document.getElementById('formCancelar').submit();
        }
    });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
