<?php
$titulo = 'Gestión de Citas';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 1) {
    header("Location: ../usuarios/login.php"); exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../layouts/header.php';

$db    = (new Database())->conectar();
$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);

// Obtener todas las citas con datos de cliente y vehículo
$stmtCitas = $db->query(
    "SELECT c.*,
            p.nombre  AS cliente_nombre,
            p.telefono AS cliente_telefono,
            v.placa, v.marca, v.modelo
     FROM citas c
     LEFT JOIN persona  p ON c.id_cliente  = p.id_persona
     LEFT JOIN vehiculo v ON c.id_vehiculo = v.id_vehiculo
     ORDER BY c.fecha_cita DESC"
);
$todasCitas = $stmtCitas ? $stmtCitas->fetchAll(PDO::FETCH_ASSOC) : [];

$pendientes  = array_filter($todasCitas, fn($c) => $c['estado'] === 'Pendiente');
$confirmadas = array_filter($todasCitas, fn($c) => $c['estado'] === 'Confirmada');
$cntPend     = count($pendientes);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="fas fa-calendar-check text-primary me-2"></i>Gestión de Citas</h4>
        <p class="text-muted small mb-0">Administra y confirma las citas del taller</p>
    </div>
</div>

<!-- ══ FILTROS ══════════════════════════════════════════════════════════════ -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-center">
            <div class="col-sm-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="buscador" class="form-control border-start-0"
                           placeholder="Buscar por nombre, placa, servicio…" style="font-size:0.875rem;">
                </div>
            </div>
            <div class="col-sm-3">
                <input type="date" id="filtrFecha" class="form-control form-control-sm"
                       style="font-size:0.875rem;" title="Filtrar por fecha">
            </div>
            <div class="col-sm-4 text-sm-end">
                <button class="btn btn-sm btn-outline-secondary" onclick="limpiarFiltros()">
                    <i class="fas fa-times me-1"></i>Limpiar filtros
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══ TABS ══════════════════════════════════════════════════════════════════ -->
<ul class="nav nav-tabs mb-0" id="tabsCitas">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabPendientes">
            <i class="fas fa-clock me-1"></i>Pendientes
            <?php if ($cntPend > 0): ?>
                <span class="badge bg-warning text-dark ms-1"><?= $cntPend ?></span>
            <?php endif; ?>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabConfirmadas">
            <i class="fas fa-check-circle me-1"></i>Confirmadas
            <span class="badge bg-success ms-1"><?= count($confirmadas) ?></span>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabTodas">
            <i class="fas fa-list me-1"></i>Todas
            <span class="badge bg-secondary ms-1"><?= count($todasCitas) ?></span>
        </button>
    </li>
</ul>

<div class="tab-content">

    <!-- TAB PENDIENTES -->
    <div class="tab-pane fade show active" id="tabPendientes">
        <div class="card shadow-sm rounded-top-0">
            <div class="card-body p-0">
                <?= renderTablaCitas($pendientes, 'pendientes', true) ?>
            </div>
        </div>
    </div>

    <!-- TAB CONFIRMADAS -->
    <div class="tab-pane fade" id="tabConfirmadas">
        <div class="card shadow-sm rounded-top-0">
            <div class="card-body p-0">
                <?= renderTablaCitas($confirmadas, 'confirmadas', true) ?>
            </div>
        </div>
    </div>

    <!-- TAB TODAS -->
    <div class="tab-pane fade" id="tabTodas">
        <div class="card shadow-sm rounded-top-0">
            <div class="card-body p-0">
                <?= renderTablaCitas($todasCitas, 'todas', true) ?>
            </div>
        </div>
    </div>

</div><!-- /tab-content -->

<?php
function renderTablaCitas(array $citas, string $tabId, bool $esAdmin): string {
    $eBadge = [
        'Pendiente'  => 'bg-warning text-dark',
        'Confirmada' => 'bg-success',
        'Cancelada'  => 'bg-danger',
        'Realizada'  => 'bg-primary',
    ];
    ob_start();
    ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 tabla-citas" style="font-size:0.875rem;">
            <thead class="table-light">
                <tr>
                    <th style="font-size:0.78rem;">N° Ref</th>
                    <th style="font-size:0.78rem;">Cliente</th>
                    <th style="font-size:0.78rem;">Vehículo</th>
                    <th style="font-size:0.78rem;">Servicio</th>
                    <th style="font-size:0.78rem;">Fecha Solicitada</th>
                    <th style="font-size:0.78rem;">Estado</th>
                    <th style="font-size:0.78rem;">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($citas as $c):
                $eb = $eBadge[$c['estado']] ?? 'bg-secondary';
            ?>
            <tr data-fecha="<?= substr($c['fecha_cita'], 0, 10) ?>">
                <td class="fw-semibold text-primary"><?= htmlspecialchars($c['numero_ref']) ?></td>
                <td>
                    <div class="fw-semibold small"><?= htmlspecialchars($c['cliente_nombre'] ?? '–') ?></div>
                    <div class="text-muted" style="font-size:0.78rem;"><?= htmlspecialchars($c['cliente_telefono'] ?? '') ?></div>
                </td>
                <td>
                    <?php if ($c['placa']): ?>
                        <span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($c['placa']) ?></span>
                        <div class="text-muted" style="font-size:0.78rem;"><?= htmlspecialchars($c['marca'] . ' ' . $c['modelo']) ?></div>
                    <?php else: ?>–<?php endif; ?>
                </td>
                <td><?= htmlspecialchars($c['tipo_servicio']) ?></td>
                <td><?= date('d/m/Y H:i', strtotime($c['fecha_cita'])) ?></td>
                <td><span class="badge <?= $eb ?>"><?= $c['estado'] ?></span></td>
                <td>
                    <div class="d-flex gap-1 flex-wrap">
                    <?php if ($c['estado'] === 'Pendiente'): ?>
                        <form action="../../controllers/AdminCitaController.php" method="POST" class="d-inline">
                            <input type="hidden" name="accion" value="confirmar">
                            <input type="hidden" name="id_cita" value="<?= $c['id_cita'] ?>">
                            <button type="submit" class="btn btn-sm btn-success" title="Confirmar">
                                <i class="fas fa-check me-1"></i>Confirmar
                            </button>
                        </form>
                        <form action="../../controllers/AdminCitaController.php" method="POST" class="d-inline">
                            <input type="hidden" name="accion" value="rechazar">
                            <input type="hidden" name="id_cita" value="<?= $c['id_cita'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger" title="Rechazar"
                                    onclick="return confirm('¿Rechazar esta cita?')">
                                <i class="fas fa-times me-1"></i>Rechazar
                            </button>
                        </form>
                    <?php elseif ($c['estado'] === 'Confirmada'): ?>
                        <form action="../../controllers/AdminCitaController.php" method="POST" class="d-inline">
                            <input type="hidden" name="accion" value="realizar">
                            <input type="hidden" name="id_cita" value="<?= $c['id_cita'] ?>">
                            <button type="submit" class="btn btn-sm btn-primary" title="Marcar como realizada">
                                <i class="fas fa-flag-checkered me-1"></i>Realizada
                            </button>
                        </form>
                        <form action="../../controllers/AdminCitaController.php" method="POST" class="d-inline">
                            <input type="hidden" name="accion" value="cancelar">
                            <input type="hidden" name="id_cita" value="<?= $c['id_cita'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Cancelar"
                                    onclick="return confirm('¿Cancelar esta cita confirmada?')">
                                <i class="fas fa-ban me-1"></i>Cancelar
                            </button>
                        </form>
                    <?php else: ?>
                        <span class="text-muted small">–</span>
                    <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($citas)): ?>
            <tr>
                <td colspan="7" class="text-center text-muted py-5">
                    <i class="fas fa-calendar fa-2x mb-2 opacity-25 d-block"></i>
                    No hay citas en esta categoría.
                </td>
            </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
    return ob_get_clean();
}
?>

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
// ── Buscador y filtro de fecha ────────────────────────────────────────────
function aplicarFiltros() {
    const q     = document.getElementById('buscador').value.toLowerCase();
    const fecha = document.getElementById('filtrFecha').value;

    document.querySelectorAll('.tabla-citas tbody tr[data-fecha]').forEach(function(tr) {
        const textoOk = !q    || tr.textContent.toLowerCase().includes(q);
        const fechaOk = !fecha || tr.dataset.fecha === fecha;
        tr.style.display = (textoOk && fechaOk) ? '' : 'none';
    });
}

document.getElementById('buscador').addEventListener('input', aplicarFiltros);
document.getElementById('filtrFecha').addEventListener('change', aplicarFiltros);

function limpiarFiltros() {
    document.getElementById('buscador').value  = '';
    document.getElementById('filtrFecha').value = '';
    aplicarFiltros();
}
</script>

<style>
.nav-tabs .nav-link { color: #64748b; font-size: 0.875rem; }
.nav-tabs .nav-link.active { color: #2563eb; font-weight: 600; }
.rounded-top-0 { border-top-left-radius: 0 !important; border-top-right-radius: 0 !important; }
</style>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
