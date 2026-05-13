<?php
$titulo = 'Historial de Servicios';
require_once __DIR__ . '/../../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario']) || (int)($_SESSION['usuario']['rol'] ?? 0) !== 3) {
    header("Location: ../usuarios/login.php"); exit;
}

require_once __DIR__ . '/../layouts/header.php';

$db    = (new Database())->conectar();
$alert = $_SESSION['alert'] ?? null; unset($_SESSION['alert']);

// Historial completo con datos de vehículo y cliente
$stmtH = $db->query(
    "SELECT hv.id_historial_vehiculo,
            hv.descripcion,
            hv.fecha_registro,
            hv.tipo_reparacion,
            v.placa,
            v.marca,
            v.modelo,
            cl.nombre  AS cliente_nombre,
            p.nombre   AS empleado_nombre,
            ev.estado  AS estado_vehiculo
     FROM historial_vehiculo hv
     JOIN vehiculo       v   ON hv.id_vehiculo  = v.id_vehiculo
     JOIN cliente        cl  ON v.id_cliente    = cl.id_cliente
     JOIN empleado       e   ON hv.id_empleado  = e.id_empleado
     JOIN persona        p   ON e.id_persona    = p.id_persona
     JOIN estado_vehiculo ev ON v.id_estado_vehiculo = ev.id_estado_vehiculo
     ORDER BY hv.fecha_registro DESC, hv.id_historial_vehiculo DESC"
);
$historial = $stmtH ? $stmtH->fetchAll(PDO::FETCH_ASSOC) : [];

// Estados para filtros
$stmtEst = $db->query("SELECT * FROM estado_vehiculo ORDER BY id_estado_vehiculo");
$estados = $stmtEst->fetchAll(PDO::FETCH_ASSOC);

// Tipos de reparación únicos para filtro
$tipos = array_unique(array_column($historial, 'tipo_reparacion'));
sort($tipos);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-0">
            <i class="fas fa-history text-primary me-2"></i>Historial de Servicios
        </h4>
        <p class="text-muted small mb-0">Registro completo de trabajos realizados</p>
    </div>
    <span class="badge bg-primary"><?= count($historial) ?> registros</span>
</div>

<!-- Filtros -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="fas fa-search text-muted"></i>
                    </span>
                    <input type="text" id="buscador" class="form-control border-start-0"
                           placeholder="Buscar por placa, cliente, descripción…">
                </div>
            </div>
            <div class="col-md-3">
                <select id="filtroTipo" class="form-select form-select-sm">
                    <option value="">Todos los tipos</option>
                    <?php foreach ($tipos as $t): ?>
                        <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select id="filtroEstado" class="form-select form-select-sm">
                    <option value="">Todos los estados</option>
                    <?php foreach ($estados as $e): ?>
                        <option value="<?= htmlspecialchars($e['estado']) ?>"><?= htmlspecialchars($e['estado']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-1 text-end">
                <button class="btn btn-sm btn-outline-secondary" onclick="limpiarFiltros()" title="Limpiar filtros">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Tabla historial -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small" id="tablaHistorial">
                <thead class="table-light">
                    <tr>
                        <th>FECHA</th>
                        <th>VEHÍCULO</th>
                        <th>CLIENTE</th>
                        <th>TIPO</th>
                        <th>DESCRIPCIÓN</th>
                        <th>EMPLEADO</th>
                        <th>ESTADO</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($historial as $h):
                    $estado = $h['estado_vehiculo'] ?? '';
                    $badge  = match(true) {
                        stripos($estado, 'pendiente')  !== false => 'bg-warning text-dark',
                        stripos($estado, 'reparaci')   !== false => 'bg-info text-dark',
                        stripos($estado, 'finalizado') !== false => 'bg-success',
                        stripos($estado, 'pintura')    !== false => 'bg-primary',
                        stripos($estado, 'entregado')  !== false => 'bg-success',
                        default                                  => 'bg-secondary',
                    };
                ?>
                <tr data-tipo="<?= htmlspecialchars($h['tipo_reparacion']) ?>"
                    data-estado="<?= htmlspecialchars($estado) ?>">
                    <td class="text-muted">
                        <?= date('d/m/Y', strtotime($h['fecha_registro'])) ?>
                    </td>
                    <td>
                        <div class="fw-bold font-monospace"><?= htmlspecialchars($h['placa']) ?></div>
                        <div class="text-muted" style="font-size:.72rem;">
                            <?= htmlspecialchars($h['marca'] . ' ' . $h['modelo']) ?>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($h['cliente_nombre']) ?></td>
                    <td>
                        <span class="badge bg-light text-dark border">
                            <?= htmlspecialchars($h['tipo_reparacion']) ?>
                        </span>
                    </td>
                    <td style="max-width:220px;">
                        <?= htmlspecialchars(mb_strimwidth($h['descripcion'] ?? '', 0, 80, '…')) ?>
                    </td>
                    <td><?= htmlspecialchars($h['empleado_nombre']) ?></td>
                    <td><span class="badge <?= $badge ?>"><?= htmlspecialchars($estado) ?></span></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($historial)): ?>
                    <tr id="sinResultados">
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="fas fa-history fa-2x mb-2 opacity-25 d-block"></i>
                            No hay registros de historial.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($alert): ?>
<script>
    Swal.fire({
        icon:  '<?= $alert['icon'] ?>',
        title: '<?= $alert['title'] ?>',
        text:  '<?= $alert['text'] ?>',
        confirmButtonColor: '#2563eb'
    });
</script>
<?php endif; ?>

<script>
const buscador    = document.getElementById('buscador');
const filtroTipo  = document.getElementById('filtroTipo');
const filtroEstado= document.getElementById('filtroEstado');

function filtrar() {
    const q      = buscador.value.toLowerCase();
    const tipo   = filtroTipo.value;
    const estado = filtroEstado.value;
    let visibles = 0;

    document.querySelectorAll('#tablaHistorial tbody tr[data-tipo]').forEach(tr => {
        const pasaBusq   = !q    || tr.textContent.toLowerCase().includes(q);
        const pasaTipo   = !tipo  || tr.dataset.tipo   === tipo;
        const pasaEstado = !estado|| tr.dataset.estado === estado;
        const visible    = pasaBusq && pasaTipo && pasaEstado;
        tr.style.display = visible ? '' : 'none';
        if (visible) visibles++;
    });

    const sinRes = document.getElementById('sinResultados');
    if (sinRes) sinRes.style.display = visibles === 0 ? '' : 'none';
}

function limpiarFiltros() {
    buscador.value     = '';
    filtroTipo.value   = '';
    filtroEstado.value = '';
    filtrar();
}

buscador.addEventListener('input', filtrar);
filtroTipo.addEventListener('change', filtrar);
filtroEstado.addEventListener('change', filtrar);
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
