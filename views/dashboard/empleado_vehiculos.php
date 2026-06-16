<?php
$titulo = 'Vehículos';
require_once __DIR__ . '/../../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario']) || (int)($_SESSION['usuario']['rol'] ?? 0) !== 3) {
    header("Location: ../usuarios/login.php"); exit;
}

require_once __DIR__ . '/../../models/Vehiculo.php';
require_once __DIR__ . '/../layouts/header.php';

$db     = (new Database())->conectar();
$vModel = new Vehiculo($db);
$alert  = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);

$vehiculos = $vModel->obtenerTodos();
$estados   = $vModel->obtenerEstados();
?>

<style>
    .badge-pintura { background-color: #1d4ed8; color: #fff; }
    .filter-btn.active { background: #2563eb !important; color: #fff !important; border-color: #2563eb !important; }
    .timeline { position: relative; padding-left: 1.5rem; }
    .timeline::before { content:''; position:absolute; left:.45rem; top:0; bottom:0; width:2px; background:#e2e8f0; }
    .timeline-item { position: relative; margin-bottom: 1.25rem; }
    .timeline-dot { position:absolute; left:-1.5rem; top:.25rem; width:14px; height:14px; border-radius:50%; background:#2563eb; border:2px solid #fff; box-shadow:0 0 0 2px #2563eb; }
    .timeline-date { font-size:.72rem; color:#64748b; }
    .form-label { font-size: .78rem; }
    .form-control, .form-select { font-size: .875rem; }
</style>

<!-- ── Encabezado ─────────────────────────────────────────────────────────── -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-0"><i class="fas fa-car text-primary me-2"></i>Vehículos</h4>
        <p class="text-muted small mb-0">Seguimiento y actualización de vehículos en taller</p>
    </div>
</div>

<!-- ── Filtros por estado ─────────────────────────────────────────────────── -->
<div class="d-flex flex-wrap gap-2 mb-3">
    <button class="btn btn-sm btn-outline-secondary filter-btn active" data-filtro="todos">Todos</button>
    <?php foreach ($estados as $e): ?>
    <button class="btn btn-sm btn-outline-secondary filter-btn"
            data-filtro="<?= htmlspecialchars($e['estado']) ?>">
        <?= htmlspecialchars($e['estado']) ?>
    </button>
    <?php endforeach; ?>
</div>

<!-- ── Buscador ──────────────────────────────────────────────────────────── -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <div class="input-group">
            <span class="input-group-text bg-white border-end-0">
                <i class="fas fa-search text-muted"></i>
            </span>
            <input type="text" id="buscador" class="form-control border-start-0"
                   placeholder="Buscar por placa, cliente o marca…">
        </div>
    </div>
</div>

<!-- ── Tabla ──────────────────────────────────────────────────────────────── -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tablaVehiculos">
                <thead class="table-light">
                    <tr>
                        <th>PLACA</th>
                        <th>VEHÍCULO</th>
                        <th>CLIENTE</th>
                        <th>ESTADO</th>
                        <th>ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($vehiculos as $v):
                    $estado = $v['estado'] ?? '';
                    $badge  = match(true) {
                        stripos($estado, 'pendiente')    !== false => 'bg-warning text-dark',
                        stripos($estado, 'reparaci')     !== false => 'bg-info text-dark',
                        stripos($estado, 'finalizado')   !== false => 'bg-success',
                        stripos($estado, 'pintura')      !== false => 'badge-pintura',
                        stripos($estado, 'entregado')    !== false => 'bg-primary',
                        default                                    => 'bg-secondary',
                    };
                ?>
                <tr data-estado="<?= htmlspecialchars($estado) ?>">
                    <td class="fw-bold font-monospace"><?= htmlspecialchars($v['placa']) ?></td>
                    <td>
                        <div class="fw-semibold"><?= htmlspecialchars($v['marca'] . ' ' . $v['modelo']) ?></div>
                        <div class=\"text-muted small\"><?= htmlspecialchars($v['anio'] ?? '') ?></div>
                    </td>
                    <td>
                        <div><?= htmlspecialchars($v['nombre_cliente'] ?? '–') ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($v['correo_cliente'] ?? '') ?></div>
                    </td>
                    <td><span class="badge <?= $badge ?>"><?= htmlspecialchars($estado) ?></span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary me-1"
                                title="Ver historial"
                                onclick="verHistorial(<?= $v['id_vehiculo'] ?>, '<?= htmlspecialchars(addslashes($v['placa'])) ?>')">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-success me-1"
                                title="Cambiar estado"
                                onclick="cambiarEstado(<?= $v['id_vehiculo'] ?>, <?= $v['id_estado_vehiculo'] ?>)">
                            <i class="fas fa-arrows-rotate"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-info"
                                title="Agregar historial"
                                onclick="abrirAgregarHistorial(<?= $v['id_vehiculo'] ?>, '<?= htmlspecialchars(addslashes($v['placa'])) ?>')">
                            <i class="fas fa-plus-circle"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($vehiculos)): ?>
                    <tr id="sinResultados">
                        <td colspan="5" class="text-center text-muted py-4">No hay vehículos registrados.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════════════════════
     MODAL: CAMBIAR ESTADO
════════════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalEstado" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content rounded-4">
            <div class="modal-header" style="background:linear-gradient(90deg,#1a3a6b,#2563eb);">
                <h5 class="modal-title text-white"><i class="fas fa-arrows-rotate me-2"></i>Cambiar Estado</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="../../controllers/VehiculoController.php" method="POST">
                <input type="hidden" name="accion" value="cambiar_estado">
                <input type="hidden" name="id_vehiculo" id="estadoIdVehiculo">
                <div class="modal-body">
                    <label class="form-label fw-semibold">Nuevo estado</label>
                    <select name="id_estado_vehiculo" id="estadoSelect" class="form-select">
                        <?php foreach ($estados as $e): ?>
                            <option value="<?= $e['id_estado_vehiculo'] ?>"><?= htmlspecialchars($e['estado']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-check me-1"></i>Aplicar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════════════════════
     MODAL: VER HISTORIAL
════════════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalHistorial" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4">
            <div class="modal-header" style="background:linear-gradient(90deg,#1a3a6b,#2563eb);">
                <h5 class="modal-title text-white"><i class="fas fa-history me-2"></i>Historial — <span id="historialPlaca"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="historialContenido">
                <div class="text-center py-4"><div class="spinner-border text-primary"></div></div>
            </div>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════════════════════
     MODAL: AGREGAR HISTORIAL
════════════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalAgregarHistorial" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4">
            <div class="modal-header" style="background:linear-gradient(90deg,#1a3a6b,#2563eb);">
                <h5 class="modal-title text-white"><i class="fas fa-plus-circle me-2"></i>Agregar al Historial — <span id="ahPlaca"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="../../controllers/VehiculoController.php" method="POST">
                <input type="hidden" name="accion" value="agregar_historial">
                <input type="hidden" name="id_vehiculo" id="ahIdVehiculo">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Descripción *</label>
                            <textarea name="descripcion" class="form-control" rows="3" required
                                      placeholder="Describa el trabajo realizado…" maxlength="100"></textarea>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Tipo de reparación *</label>
                            <select name="tipo_reparacion" class="form-select" required>
                                <option value="">Seleccionar…</option>
                                <option value="Latonería">Latonería</option>
                                <option value="Pintura">Pintura</option>
                                <option value="Enderezado">Enderezado</option>
                                <option value="Revisión">Revisión</option>
                                <option value="Entrega">Entrega</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Fecha *</label>
                            <input type="date" name="fecha_registro" class="form-control"
                                   value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── SweetAlert ─────────────────────────────────────────────────────────── -->
<?php if ($alert): ?>
<script>
    Swal.fire({
        icon: '<?= htmlspecialchars($alert['icon']) ?>',
        title: '<?= htmlspecialchars($alert['title']) ?>',
        text: '<?= htmlspecialchars($alert['text']) ?>',
        confirmButtonColor: '#000000'
    });
</script>
<?php endif; ?>

<!-- ── Scripts ────────────────────────────────────────────────────────────── -->
<script>
document.getElementById('buscador').addEventListener('input', filtrarTabla);

let filtroEstadoActivo = 'todos';
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        filtroEstadoActivo = this.dataset.filtro;
        filtrarTabla();
    });
});

function filtrarTabla() {
    const q = document.getElementById('buscador').value.toLowerCase();
    let visibles = 0;
    document.querySelectorAll('#tablaVehiculos tbody tr[data-estado]').forEach(tr => {
        const pasaBusq   = !q || tr.textContent.toLowerCase().includes(q);
        const pasaFiltro = filtroEstadoActivo === 'todos' || tr.dataset.estado === filtroEstadoActivo;
        tr.style.display = (pasaBusq && pasaFiltro) ? '' : 'none';
        if (pasaBusq && pasaFiltro) visibles++;
    });
    const sinRes = document.getElementById('sinResultados');
    if (sinRes) sinRes.style.display = visibles === 0 ? '' : 'none';
}

function cambiarEstado(idVehiculo, idEstadoActual) {
    document.getElementById('estadoIdVehiculo').value = idVehiculo;
    document.getElementById('estadoSelect').value     = idEstadoActual;
    new bootstrap.Modal(document.getElementById('modalEstado')).show();
}

function verHistorial(idVehiculo, placa) {
    document.getElementById('historialPlaca').textContent = placa;
    document.getElementById('historialContenido').innerHTML =
        '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';
    new bootstrap.Modal(document.getElementById('modalHistorial')).show();

    fetch('../../controllers/HistorialAjaxController.php?id_vehiculo=' + idVehiculo)
        .then(r => r.json())
        .then(data => {
            if (!data.length) {
                document.getElementById('historialContenido').innerHTML =
                    '<p class="text-muted text-center py-3">Sin entradas de historial.</p>';
                return;
            }
            let html = '<div class="timeline">';
            data.forEach(h => {
                const fecha = h.fecha_registro ? new Date(h.fecha_registro).toLocaleDateString('es-CO') : '–';
                html += `
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="badge bg-primary bg-opacity-10 text-primary">${escHtml(h.tipo_reparacion)}</span>
                        <span class="timeline-date">${fecha}</span>
                    </div>
                    <p class="mb-1 mt-1 small">${escHtml(h.descripcion)}</p>
                    ${h.nombre_empleado ? `<small class="text-muted"><i class="fas fa-user-tie me-1"></i>${escHtml(h.nombre_empleado)}</small>` : ''}
                </div>`;
            });
            html += '</div>';
            document.getElementById('historialContenido').innerHTML = html;
        })
        .catch(() => {
            document.getElementById('historialContenido').innerHTML =
                '<p class="text-danger text-center py-3">Error al cargar el historial.</p>';
        });
}

function abrirAgregarHistorial(idVehiculo, placa) {
    document.getElementById('ahIdVehiculo').value = idVehiculo;
    document.getElementById('ahPlaca').textContent = placa;
    new bootstrap.Modal(document.getElementById('modalAgregarHistorial')).show();
}

function escHtml(str) {
    if (!str) return '';
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
