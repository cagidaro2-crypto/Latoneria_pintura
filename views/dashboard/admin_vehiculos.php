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

// Fechas de entrada y salida por vehículo desde historial_vehiculo
$stmtFechas = $db->query(
    "SELECT
        id_vehiculo,
        MIN(fecha_registro) AS fecha_entrada,
        MAX(CASE WHEN tipo_reparacion = 'Entrega' THEN fecha_registro END) AS fecha_salida
     FROM historial_vehiculo
     GROUP BY id_vehiculo"
);
$fechasMap = [];
if ($stmtFechas) {
    foreach ($stmtFechas->fetchAll(PDO::FETCH_ASSOC) as $f) {
        $fechasMap[$f['id_vehiculo']] = $f;
    }
}

// Fotos de vehículos: una por vehículo (la más reciente)
$stmtFotos = $db->query(
    "SELECT id_vehiculo, nombre_archivo, descripcion
     FROM vehiculo_fotos
     WHERE id_foto IN (
        SELECT MAX(id_foto) FROM vehiculo_fotos GROUP BY id_vehiculo
     )"
);
$fotosMap = [];
if ($stmtFotos) {
    foreach ($stmtFotos->fetchAll(PDO::FETCH_ASSOC) as $f) {
        $fotosMap[$f['id_vehiculo']] = $f;
    }
}

// Empleados: todos con rol cliente/usuario activo
$stmtEmpVeh = $db->query(
    "SELECT u.id_usuario, u.nombres, u.apellidos, u.telefono
     FROM usuarios u
     WHERE u.id_rol = 2 AND u.activo = 1
     ORDER BY u.nombres ASC"
);
$empleadosVeh = $stmtEmpVeh->fetchAll(PDO::FETCH_ASSOC);

// Clientes: tabla clientes asociada directamente a vehiculos
$stmtCli = $db->query(
    "SELECT cl.id_cliente, cl.nombres, cl.apellidos, cl.correo, cl.telefono
     FROM clientes cl
     ORDER BY cl.nombres ASC"
);
$clientes = $stmtCli->fetchAll(PDO::FETCH_ASSOC);

// Si no hay clientes en tabla clientes, mostrar mensaje para que el admin registre clientes primero.
if (empty($clientes)) {
    $clientesDesdePersona = false;
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

<!-- ── Tarjetas ─────────────────────────────────────────────────────────────── -->
<div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3" id="vehiculosCards">
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
        $fechas   = $fechasMap[$v['id_vehiculo']] ?? null;
        $entrada  = $fechas['fecha_entrada'] ?? null;
        $salida   = $fechas['fecha_salida']  ?? null;
        $search   = strtolower(trim($v['placa'] . ' ' . $v['marca'] . ' ' . $v['modelo'] . ' ' . ($v['nombre_cliente'] ?? '') . ' ' . ($v['correo_cliente'] ?? '') . ' ' . $estado));
    ?>
    <div class="col" data-estado="<?= htmlspecialchars($estado) ?>" data-search="<?= htmlspecialchars($search) ?>">
        <div class="card shadow-sm h-100">
            <?php $foto = $fotosMap[$v['id_vehiculo']] ?? null; ?>
            <?php if ($foto): ?>
                <div style="height: 200px; overflow: hidden; background-color: #f0f0f0;">
                    <img src="../../public/uploads/vehiculos/<?= htmlspecialchars($foto['nombre_archivo']) ?>"
                         alt="<?= htmlspecialchars($v['placa']) ?>"
                         style="width: 100%; height: 100%; object-fit: cover; cursor: pointer;"
                         onclick="abrirFotos(<?= $v['id_vehiculo'] ?>, '<?= htmlspecialchars(addslashes($v['placa'])) ?>')">
                </div>
            <?php else: ?>
                <div style="height: 200px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center;">
                    <div class="text-center text-white">
                        <i class="fas fa-car fa-3x mb-2 opacity-50"></i>
                        <p class="small">Sin fotos</p>
                    </div>
                </div>
            <?php endif; ?>
            <div class="card-body d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="text-muted small">Placa</div>
                        <div class="fw-bold text-uppercase"><?= htmlspecialchars($v['placa']) ?></div>
                    </div>
                    <span class="badge <?= $badge ?>"><?= htmlspecialchars($estado) ?></span>
                </div>
                <h5 class="card-title mb-1"><?= htmlspecialchars($v['marca'] . ' ' . $v['modelo']) ?></h5>
                <p class="card-text text-muted small mb-3">Año: <?= htmlspecialchars($v['anio'] ?? '–') ?></p>
                <div class="mb-3">
                    <div class="fw-semibold">Cliente</div>
                    <div class="small text-muted"><?= htmlspecialchars($v['nombre_cliente'] ?? '–') ?></div>
                    <div class="small text-muted"><?= htmlspecialchars($v['correo_cliente'] ?? '–') ?></div>
                </div>
                <div class="mt-auto">
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="text-muted small">Entrada</div>
                            <div class="small"><?= $entrada ? date('d/m/Y', strtotime($entrada)) : '–' ?></div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">Salida</div>
                            <div class="small">
                                <?php if ($salida): ?>
                                    <?= date('d/m/Y', strtotime($salida)) ?>
                                <?php elseif ($entrada): ?>
                                    <span class="badge bg-warning text-dark" style="font-size:.7rem;">En taller</span>
                                <?php else: ?>
                                    –
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white border-top-0 pt-0">
                <div class="d-flex flex-wrap gap-1">
                    <button class="btn btn-sm btn-outline-secondary flex-grow-1"
                            title="Ver historial"
                            onclick="verHistorial(<?= $v['id_vehiculo'] ?>, '<?= htmlspecialchars(addslashes($v['placa'])) ?>')">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-primary flex-grow-1"
                            title="Editar vehículo"
                            onclick="editarVehiculo(<?= htmlspecialchars(json_encode($v)) ?>)">
                        <i class="fas fa-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-success flex-grow-1"
                            title="Cambiar estado"
                            onclick="cambiarEstado(<?= $v['id_vehiculo'] ?>, <?= $v['id_estado'] ?? 1 ?>)">
                        <i class="fas fa-arrows-rotate"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-info flex-grow-1"
                            title="Agregar historial"
                            onclick="abrirAgregarHistorial(<?= $v['id_vehiculo'] ?>, '<?= htmlspecialchars(addslashes($v['placa'])) ?>')">
                        <i class="fas fa-plus-circle"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-warning flex-grow-1"
                            title="Fotos del vehículo"
                            onclick="abrirFotos(<?= $v['id_vehiculo'] ?>, '<?= htmlspecialchars(addslashes($v['placa'])) ?>')">
                        <i class="fas fa-camera"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<div id="sinResultados" class="text-center text-muted py-4" style="display: none;">No hay vehículos registrados.</div>

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
                                    <option value="<?= $e['id_usuario'] ?>">
                                        <?= htmlspecialchars($e['nombres'] . ' ' . ($e['apellidos'] ?? '')) ?>
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
        confirmButtonColor: '#000000'
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
    document.querySelectorAll('#vehiculosCards .col[data-estado]').forEach(card => {
        const textoCard  = card.dataset.search.toLowerCase();
        const estadoCard = card.dataset.estado;
        const pasaBusq   = !q || textoCard.includes(q);
        const pasaFiltro = filtroEstadoActivo === 'todos' || estadoCard === filtroEstadoActivo;
        card.style.display = (pasaBusq && pasaFiltro) ? '' : 'none';
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
    document.getElementById('editAnio').value   = v['anio'] ?? '';
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

/* ── Fotos del vehículo ──────────────────────────────────────────────────── */
function abrirFotos(idVehiculo, placa) {
    document.getElementById('fotosIdVehiculo').value = idVehiculo;
    document.getElementById('fotosPlaca').textContent = placa;
    document.getElementById('previewContainer').innerHTML = '';
    cargarGaleria(idVehiculo);
    new bootstrap.Modal(document.getElementById('modalFotos')).show();
}

function cargarGaleria(idVehiculo) {
    const galeria = document.getElementById('galeriaFotos');
    galeria.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';

    fetch('../../controllers/FotosAjaxController.php?id_vehiculo=' + idVehiculo)
        .then(r => r.json())
        .then(data => {
            if (!data.length) {
                galeria.innerHTML = '<p class="text-muted text-center py-3"><i class="fas fa-images me-2 opacity-25"></i>No hay fotos para este vehículo.</p>';
                return;
            }

            // Agrupar por etapa
            const etapas = { antes: [], durante: [], despues: [] };
            data.forEach(f => { if (etapas[f.etapa]) etapas[f.etapa].push(f); });

            const etiquetas = { antes: '📷 Antes', durante: '🔧 Durante', despues: '✅ Después' };
            let html = '';

            Object.entries(etapas).forEach(([etapa, fotos]) => {
                if (!fotos.length) return;
                html += `<h6 class="fw-semibold mt-3 mb-2">${etiquetas[etapa]}</h6>`;
                html += '<div class="row g-2">';
                fotos.forEach(f => {
                    html += `
                    <div class="col-6 col-md-3 col-lg-2" id="foto-${f.id_foto}">
                        <div class="card h-100 shadow-sm">
                            <a href="../../public/uploads/vehiculos/${escHtml(f.nombre_archivo)}"
                               target="_blank">
                                <img src="../../public/uploads/vehiculos/${escHtml(f.nombre_archivo)}"
                                     class="card-img-top"
                                     style="height:110px;object-fit:cover;"
                                     alt="${escHtml(f.descripcion || 'Foto')}">
                            </a>
                            <div class="card-body p-1">
                                <p class="card-text" style="font-size:.7rem;color:#64748b;">
                                    ${escHtml(f.descripcion || '')}
                                </p>
                                <p class="card-text" style="font-size:.65rem;color:#94a3b8;">
                                    ${f.created_at ? f.created_at.substring(0,10) : ''}
                                </p>
                            </div>
                            <div class="card-footer p-1 text-center">
                                <button class="btn btn-sm btn-outline-danger w-100"
                                        style="font-size:.7rem;"
                                        onclick="eliminarFoto(${f.id_foto}, ${f.id_vehiculo})">
                                    <i class="fas fa-trash me-1"></i>Eliminar
                                </button>
                            </div>
                        </div>
                    </div>`;
                });
                html += '</div>';
            });

            galeria.innerHTML = html;
        })
        .catch(() => {
            galeria.innerHTML = '<p class="text-danger text-center py-3">Error al cargar las fotos.</p>';
        });
}

function eliminarFoto(idFoto, idVehiculo) {
    Swal.fire({
        icon: 'warning',
        title: '¿Eliminar foto?',
        text: 'Esta acción no se puede deshacer.',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d'
    }).then(result => {
        if (result.isConfirmed) {
            fetch('../../controllers/FotoVehiculoController.php?accion=eliminar&id=' + idFoto)
                .then(() => cargarGaleria(idVehiculo));
        }
    });
}

function previsualizarFotos(input) {
    const container = document.getElementById('previewContainer');
    container.innerHTML = '';
    Array.from(input.files).forEach(file => {
        const reader = new FileReader();
        reader.onload = e => {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.style.cssText = 'width:80px;height:80px;object-fit:cover;border-radius:6px;border:2px solid #e2e8f0;';
            container.appendChild(img);
        };
        reader.readAsDataURL(file);
    });
}
</script>

<!-- ════════════════════════════════════════════════════════════════════════════
     MODAL: FOTOS DEL VEHÍCULO
════════════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalFotos" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content rounded-4">
            <div class="modal-header" style="background:linear-gradient(90deg,#1a3a6b,#2563eb);">
                <h5 class="modal-title text-white">
                    <i class="fas fa-camera me-2"></i>Fotos — <span id="fotosPlaca"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <!-- Subir nuevas fotos -->
                <form action="../../controllers/FotoVehiculoController.php"
                      method="POST" enctype="multipart/form-data" class="mb-4">
                    <input type="hidden" name="accion" value="subir">
                    <input type="hidden" name="id_vehiculo" id="fotosIdVehiculo">
                    <div class="card border-dashed border-2 border-primary bg-light">
                        <div class="card-body">
                            <h6 class="fw-semibold mb-3">
                                <i class="fas fa-upload text-primary me-2"></i>Subir nuevas fotos
                            </h6>
                            <div class="row g-3">
                                <div class="col-sm-4">
                                    <label class="form-label small fw-semibold">Etapa *</label>
                                    <select name="etapa" class="form-select form-select-sm">
                                        <option value="antes">📷 Antes del trabajo</option>
                                        <option value="durante">🔧 Durante el trabajo</option>
                                        <option value="despues">✅ Después del trabajo</option>
                                    </select>
                                </div>
                                <div class="col-sm-4">
                                    <label class="form-label small fw-semibold">Descripción</label>
                                    <input type="text" name="descripcion" class="form-control form-control-sm"
                                           placeholder="Ej: Daño en puerta izquierda">
                                </div>
                                <div class="col-sm-4">
                                    <label class="form-label small fw-semibold">Imágenes * (máx. 5 MB c/u)</label>
                                    <input type="file" name="fotos[]" class="form-control form-control-sm"
                                           accept="image/*" multiple required
                                           onchange="previsualizarFotos(this)">
                                </div>
                            </div>
                            <!-- Previsualización -->
                            <div id="previewContainer" class="d-flex flex-wrap gap-2 mt-3"></div>
                            <button type="submit" class="btn btn-primary btn-sm mt-3">
                                <i class="fas fa-upload me-1"></i> Subir Fotos
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Galería de fotos existentes -->
                <div id="galeriaFotos">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary"></div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
