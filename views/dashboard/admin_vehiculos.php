<?php
$titulo = 'Vehículos';
require_once __DIR__ . '/../../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario']) || (int)($_SESSION['usuario']['rol'] ?? 0) !== 1) {
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

// Empleados: todos con id_rol=3, con o sin registro en tabla empleado
$stmtEmpVeh = $db->query(
    "SELECT p.id_persona, p.nombre, p.telefono
     FROM persona p
     WHERE p.id_rol = 3 AND p.activo = 1
     ORDER BY p.nombre ASC"
);
$empleadosVeh = $stmtEmpVeh->fetchAll(PDO::FETCH_ASSOC);

// Clientes: unión de tabla cliente + personas con rol 2 que tengan registro en cliente
// Primero intentamos desde tabla cliente (que es la que referencia vehiculo)
// Si el cliente se registró por persona, buscamos su registro en cliente por correo
$stmtCli = $db->query(
    "SELECT cl.id_cliente, cl.nombre, cl.correo, cl.telefono
     FROM cliente cl
     ORDER BY cl.nombre ASC"
);
$clientes = $stmtCli->fetchAll(PDO::FETCH_ASSOC);

// Si no hay clientes en tabla cliente, intentar desde persona (rol 2)
// y mostrar aviso para que el admin sepa que debe registrar el vehículo
// desde el panel del cliente o crear el registro en cliente
if (empty($clientes)) {
    $stmtCli2 = $db->query(
        "SELECT p.id_persona AS id_cliente, p.nombre, p.correo, p.telefono
         FROM persona p
         WHERE p.id_rol = 2 AND p.activo = 1
         ORDER BY p.nombre ASC"
    );
    $clientes = $stmtCli2->fetchAll(PDO::FETCH_ASSOC);
    $clientesDesdePersona = true;
} else {
    $clientesDesdePersona = false;
}

// Filtro activo desde URL
$filtroActivo = $_GET['filtro'] ?? 'todos';
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
        <p class="text-muted small mb-0">Gestión y seguimiento de vehículos en el taller</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalRegistrar">
        <i class="fas fa-plus me-1"></i> Registrar Vehículo
    </button>
</div>

<!-- ── Filtros por estado ─────────────────────────────────────────────────── -->
<div class="d-flex flex-wrap gap-2 mb-3">
    <button class="btn btn-sm btn-outline-secondary filter-btn <?= $filtroActivo === 'todos' ? 'active' : '' ?>"
            data-filtro="todos">Todos</button>
    <?php foreach ($estados as $e): ?>
    <button class="btn btn-sm btn-outline-secondary filter-btn <?= $filtroActivo === $e['estado'] ? 'active' : '' ?>"
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
                        <div class="text-muted small"><?= htmlspecialchars($v['año'] ?? '') ?></div>
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
                        <button class="btn btn-sm btn-outline-primary me-1"
                                title="Editar vehículo"
                                onclick="editarVehiculo(<?= htmlspecialchars(json_encode($v)) ?>)">
                            <i class="fas fa-pencil"></i>
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
     MODAL: REGISTRAR VEHÍCULO
════════════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalRegistrar" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4">
            <div class="modal-header" style="background:linear-gradient(90deg,#1a3a6b,#2563eb);">
                <h5 class="modal-title text-white"><i class="fas fa-car me-2"></i>Registrar Vehículo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="../../controllers/VehiculoController.php" method="POST">
                <input type="hidden" name="accion" value="registrar">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Cliente *</label>
                            <select name="id_cliente" class="form-select" required>
                                <option value="">Seleccionar cliente…</option>
                                <?php foreach ($clientes as $c): ?>
                                    <option value="<?= $c['id_cliente'] ?>">
                                        <?= htmlspecialchars($c['nombre']) ?>
                                        <?= !empty($c['correo']) ? ' — ' . htmlspecialchars($c['correo']) : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($clientes)): ?>
                                <div class="form-text text-danger">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    No hay clientes registrados. Primero registra un cliente en el módulo de Clientes.
                                </div>
                            <?php elseif ($clientesDesdePersona ?? false): ?>
                                <div class="form-text text-warning">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Estos clientes aún no tienen vehículos. Al registrar se creará su perfil de cliente.
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Placa *</label>
                            <input type="text" name="placa" class="form-control text-uppercase" required
                                   placeholder="Ej: ABC-123" maxlength="20">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label fw-semibold">Marca *</label>
                            <input type="text" name="marca" class="form-control" required placeholder="Toyota">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label fw-semibold">Modelo *</label>
                            <input type="text" name="modelo" class="form-control" required placeholder="Corolla">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label fw-semibold">Año *</label>
                            <input type="text" name="anio" class="form-control" required
                                   placeholder="<?= date('Y') ?>" maxlength="4" pattern="\d{4}">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Estado inicial</label>
                            <select name="id_estado_vehiculo" class="form-select">
                                <?php foreach ($estados as $e): ?>
                                    <option value="<?= $e['id_estado_vehiculo'] ?>"><?= htmlspecialchars($e['estado']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Registrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════════════════════
     MODAL: EDITAR VEHÍCULO
════════════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalEditar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4">
            <div class="modal-header" style="background:linear-gradient(90deg,#1a3a6b,#2563eb);">
                <h5 class="modal-title text-white"><i class="fas fa-pencil me-2"></i>Editar Vehículo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="../../controllers/VehiculoController.php" method="POST">
                <input type="hidden" name="accion" value="actualizar">
                <input type="hidden" name="id_vehiculo" id="editId">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Placa</label>
                            <input type="text" id="editPlaca" class="form-control bg-light" readonly>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Marca *</label>
                            <input type="text" name="marca" id="editMarca" class="form-control" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Modelo *</label>
                            <input type="text" name="modelo" id="editModelo" class="form-control" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Año *</label>
                            <input type="text" name="anio" id="editAnio" class="form-control" required maxlength="4">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Estado</label>
                            <select name="id_estado_vehiculo" id="editEstado" class="form-select">
                                <?php foreach ($estados as $e): ?>
                                    <option value="<?= $e['id_estado_vehiculo'] ?>"><?= htmlspecialchars($e['estado']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Guardar Cambios</button>
                </div>
            </form>
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
                            <label class="form-label fw-semibold">Empleado Asignado *</label>
                            <select name="id_empleado_asignado" class="form-select" required>
                                <option value="">Seleccionar empleado…</option>
                                <?php foreach ($empleadosVeh as $e): ?>
                                    <option value="<?= $e['id_persona'] ?>">
                                        <?= htmlspecialchars($e['nombre']) ?>
                                        <?= !empty($e['telefono']) ? ' · ' . htmlspecialchars($e['telefono']) : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($empleadosVeh)): ?>
                                <div class="form-text text-warning">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    No hay empleados registrados. Registra empleados en el módulo de Usuarios.
                                </div>
                            <?php endif; ?>
                        </div>
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
        confirmButtonColor: '#2563eb'
    });
</script>
<?php endif; ?>

<!-- ── Scripts ────────────────────────────────────────────────────────────── -->
<script>
/* ── Buscador ─────────────────────────────────────────────────────────────── */
document.getElementById('buscador').addEventListener('input', function () {
    filtrarTabla();
});

/* ── Filtros por estado ───────────────────────────────────────────────────── */
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
        const textoFila  = tr.textContent.toLowerCase();
        const estadoFila = tr.dataset.estado;
        const pasaBusq   = !q || textoFila.includes(q);
        const pasaFiltro = filtroEstadoActivo === 'todos' || estadoFila === filtroEstadoActivo;
        tr.style.display = (pasaBusq && pasaFiltro) ? '' : 'none';
        if (pasaBusq && pasaFiltro) visibles++;
    });
    const sinRes = document.getElementById('sinResultados');
    if (sinRes) sinRes.style.display = visibles === 0 ? '' : 'none';
}

/* ── Editar vehículo ─────────────────────────────────────────────────────── */
function editarVehiculo(v) {
    document.getElementById('editId').value     = v.id_vehiculo;
    document.getElementById('editPlaca').value  = v.placa;
    document.getElementById('editMarca').value  = v.marca;
    document.getElementById('editModelo').value = v.modelo;
    document.getElementById('editAnio').value   = v['año'] ?? '';
    document.getElementById('editEstado').value = v.id_estado_vehiculo ?? 1;
    new bootstrap.Modal(document.getElementById('modalEditar')).show();
}

/* ── Cambiar estado ──────────────────────────────────────────────────────── */
function cambiarEstado(idVehiculo, idEstadoActual) {
    document.getElementById('estadoIdVehiculo').value = idVehiculo;
    document.getElementById('estadoSelect').value     = idEstadoActual;
    new bootstrap.Modal(document.getElementById('modalEstado')).show();
}

/* ── Ver historial (AJAX) ────────────────────────────────────────────────── */
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

/* ── Agregar historial ───────────────────────────────────────────────────── */
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
