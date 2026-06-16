<?php
$titulo = 'Órdenes de Servicio';
require_once __DIR__ . '/../../config/database.php';

// Verificar sesión y rol ANTES de incluir el header (evita "headers already sent")
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../usuarios/login.php"); exit;
}
$rolActual = (int)($_SESSION['usuario']['rol'] ?? 0);
if ($rolActual !== 3 && $rolActual !== 1) {
    header("Location: ../usuarios/login.php"); exit;
}

require_once __DIR__ . '/../layouts/header.php';

$db    = (new Database())->conectar();
$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);

// Vehículos con dueño para el modal de cambio de estado
$stmtVeh = $db->query(
    "SELECT v.id_vehiculo, v.placa, v.marca, v.modelo,
            CONCAT(cl.nombres, ' ', COALESCE(cl.apellidos, '')) AS nombre_dueno
     FROM vehiculos v
     JOIN clientes cl ON v.id_cliente = cl.id_cliente
     ORDER BY v.placa ASC"
);
$vehiculos = $stmtVeh ? $stmtVeh->fetchAll(PDO::FETCH_ASSOC) : [];

// Todas las órdenes con datos de cliente y vehículo
// Como OrdenServicio es un stub, consultamos directo
// Adaptado a la BD real: historial_vehiculo como proxy de órdenes
$stmtOrd = $db->query(
    "SELECT hv.id_historial AS id_orden,
            hv.descripcion,
            hv.fecha_registro,
            hv.tipo_reparacion,
            v.placa,
            v.marca,
            v.modelo,
            cl.nombres AS cliente_nombre,
            CONCAT(u.nombres, ' ', COALESCE(u.apellidos, '')) AS empleado_nombre,
            ev.estado
     FROM historial_vehiculo hv
     JOIN vehiculos      v   ON hv.id_vehiculo  = v.id_vehiculo
     JOIN clientes       cl  ON v.id_cliente    = cl.id_cliente
     LEFT JOIN usuarios  u   ON hv.id_usuario   = u.id_usuario
     JOIN estado_vehiculo ev ON v.id_estado_vehiculo = ev.id_estado_vehiculo
     ORDER BY hv.fecha_registro DESC"
);
$ordenes = $stmtOrd ? $stmtOrd->fetchAll(PDO::FETCH_ASSOC) : [];

// Estados disponibles
$stmtEst = $db->query("SELECT * FROM estado_vehiculo ORDER BY id_estado_vehiculo");
$estados = $stmtEst->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">
            <i class="fas fa-clipboard-list text-primary me-2"></i>Órdenes de Servicio
        </h4>
        <p class="text-muted small mb-0">Gestión y seguimiento de órdenes asignadas</p>
    </div>
</div>

<!-- Filtros de estado -->
<div class="d-flex flex-wrap gap-2 mb-3">
    <button class="btn btn-sm btn-outline-secondary filter-btn active" data-filtro="todos">
        Todos
    </button>
    <?php foreach ($estados as $e): ?>
    <button class="btn btn-sm btn-outline-secondary filter-btn"
            data-filtro="<?= htmlspecialchars($e['estado']) ?>">
        <?= htmlspecialchars($e['estado']) ?>
    </button>
    <?php endforeach; ?>
</div>

<!-- Buscador -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <div class="input-group">
            <span class="input-group-text bg-white border-end-0">
                <i class="fas fa-search text-muted"></i>
            </span>
            <input type="text" id="buscador" class="form-control border-start-0"
                   placeholder="Buscar por placa, cliente, tipo de servicio…">
        </div>
    </div>
</div>

<!-- Tabla de órdenes -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tablaOrdenes">
                <thead class="table-light">
                    <tr>
                        <th>VEHÍCULO</th>
                        <th>CLIENTE</th>
                        <th>TIPO SERVICIO</th>
                        <th>DESCRIPCIÓN</th>
                        <th>EMPLEADO</th>
                        <th>ESTADO</th>
                        <th>FECHA</th>
                        <th>ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($ordenes as $o):
                    $estado = $o['estado'] ?? '';
                    $badge  = match(true) {
                        stripos($estado, 'pendiente')  !== false => 'bg-warning text-dark',
                        stripos($estado, 'reparaci')   !== false => 'bg-info text-dark',
                        stripos($estado, 'finalizado') !== false => 'bg-success',
                        stripos($estado, 'pintura')    !== false => 'bg-primary',
                        stripos($estado, 'entregado')  !== false => 'bg-success',
                        default                                  => 'bg-secondary',
                    };
                ?>
                <tr data-estado="<?= htmlspecialchars($estado) ?>">
                    <td>
                        <div class="fw-bold font-monospace"><?= htmlspecialchars($o['placa']) ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($o['marca'] . ' ' . $o['modelo']) ?></div>
                    </td>
                    <td><?= htmlspecialchars($o['cliente_nombre']) ?></td>
                    <td>
                        <span class="badge bg-light text-dark border">
                            <?= htmlspecialchars($o['tipo_reparacion']) ?>
                        </span>
                    </td>
                    <td class="small text-muted" style="max-width:180px;">
                        <?= htmlspecialchars(mb_strimwidth($o['descripcion'] ?? '', 0, 60, '…')) ?>
                    </td>
                    <td class="small"><?= htmlspecialchars($o['empleado_nombre'] ?? '–') ?></td>
                    <td><span class="badge <?= $badge ?>"><?= htmlspecialchars($estado) ?></span></td>
                    <td class="small text-muted">
                        <?= date('d/m/Y', strtotime($o['fecha_registro'])) ?>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"
                                title="Cambiar estado del vehículo"
                                onclick="cambiarEstado(<?= $o['id_orden'] ?>, '<?= htmlspecialchars(addslashes($o['placa'])) ?>')">
                            <i class="fas fa-arrows-rotate"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($ordenes)): ?>
                    <tr id="sinResultados">
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="fas fa-clipboard-list fa-2x mb-2 opacity-25 d-block"></i>
                            No hay órdenes registradas.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ══ MODAL CAMBIAR ESTADO ══════════════════════════════════════════════════ -->
<div class="modal fade" id="modalEstado" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content rounded-4">
            <div class="modal-header" style="background:linear-gradient(90deg,#1a3a6b,#2563eb);">
                <h5 class="modal-title text-white">
                    <i class="fas fa-arrows-rotate me-2"></i>Cambiar Estado
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="../../controllers/VehiculoController.php" method="POST">
                <input type="hidden" name="accion" value="cambiar_estado">
                <input type="hidden" name="id_vehiculo" id="estadoIdVehiculo">
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        Vehículo: <strong id="estadoPlaca"></strong>
                    </p>
                    <label class="form-label fw-semibold small">Nuevo estado *</label>
                    <select name="id_estado_vehiculo" id="estadoSelect" class="form-select">
                        <?php foreach ($estados as $e): ?>
                            <option value="<?= $e['id_estado_vehiculo'] ?>">
                                <?= htmlspecialchars($e['estado']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-1"></i> Aplicar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($alert): ?>
<script>
    Swal.fire({
        icon:  '<?= $alert['icon'] ?>',
        title: '<?= $alert['title'] ?>',
        text:  '<?= $alert['text'] ?>',
        confirmButtonColor: '#000000'
    });
</script>
<?php endif; ?>

<script>
/* ── Filtros ─────────────────────────────────────────────────────────────── */
let filtroActivo = 'todos';

document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        filtroActivo = this.dataset.filtro;
        filtrar();
    });
});

document.getElementById('buscador').addEventListener('input', filtrar);

function filtrar() {
    const q = document.getElementById('buscador').value.toLowerCase();
    let visibles = 0;
    document.querySelectorAll('#tablaOrdenes tbody tr[data-estado]').forEach(tr => {
        const pasaBusq   = !q || tr.textContent.toLowerCase().includes(q);
        const pasaFiltro = filtroActivo === 'todos' || tr.dataset.estado === filtroActivo;
        tr.style.display = (pasaBusq && pasaFiltro) ? '' : 'none';
        if (pasaBusq && pasaFiltro) visibles++;
    });
    const sinRes = document.getElementById('sinResultados');
    if (sinRes) sinRes.style.display = visibles === 0 ? '' : 'none';
}

/* ── Cambiar estado ──────────────────────────────────────────────────────── */
// Mapa historial_id → id_vehiculo para el modal
const vehiculosPorOrden = <?= json_encode(
    array_column($ordenes, 'id_vehiculo', 'id_orden') ?? []
) ?>;

function cambiarEstado(idOrden, placa) {
    // Buscar id_vehiculo desde la lista de órdenes
    const idVehiculo = vehiculosPorOrden[idOrden] ?? 0;
    document.getElementById('estadoIdVehiculo').value = idVehiculo || idOrden;
    document.getElementById('estadoPlaca').textContent = placa;
    new bootstrap.Modal(document.getElementById('modalEstado')).show();
}
</script>

<style>
    .filter-btn.active {
        background: #2563eb !important;
        color: #fff !important;
        border-color: #2563eb !important;
    }
</style>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
