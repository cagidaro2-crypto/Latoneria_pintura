<?php
$titulo = 'Mis Vehículos';
require_once __DIR__ . '/../../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario']) || (int)($_SESSION['usuario']['rol'] ?? 0) !== 2) {
    header("Location: ../usuarios/login.php"); exit;
}

require_once __DIR__ . '/../../models/Vehiculo.php';
require_once __DIR__ . '/../layouts/header.php';

$db     = (new Database())->conectar();
$vModel = new Vehiculo($db);
$alert  = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);

// Buscar id_cliente en tabla cliente por correo de sesión
$correoSesion = $usuario['correo'] ?? '';
$idCliente    = $vModel->buscarIdClientePorCorreo($correoSesion);

$vehiculos = $idCliente ? $vModel->obtenerPorCliente($idCliente) : [];
$estados   = $vModel->obtenerEstados();

// Historial de todos los vehículos del cliente (para el accordion)
$historiales = [];
foreach ($vehiculos as $v) {
    $historiales[$v['id_vehiculo']] = $vModel->obtenerHistorial((int)$v['id_vehiculo']);
}
?>

<style>
    .badge-pintura { background-color: #1d4ed8; color: #fff; }
    .timeline { position: relative; padding-left: 1.5rem; }
    .timeline::before { content:''; position:absolute; left:.45rem; top:0; bottom:0; width:2px; background:#e2e8f0; }
    .timeline-item { position: relative; margin-bottom: 1rem; }
    .timeline-dot { position:absolute; left:-1.5rem; top:.25rem; width:12px; height:12px; border-radius:50%; background:#2563eb; border:2px solid #fff; box-shadow:0 0 0 2px #2563eb; }
    .timeline-date { font-size:.7rem; color:#64748b; }
    .form-label { font-size: .78rem; }
    .form-control, .form-select { font-size: .875rem; }
    .vehiculo-row { cursor: pointer; }
    .vehiculo-row:hover td { background-color: #f0f7ff; }
    .historial-collapse td { background: #f8fafc; }
</style>

<!-- ── Encabezado ─────────────────────────────────────────────────────────── -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-0"><i class="fas fa-car text-primary me-2"></i>Mis Vehículos</h4>
        <p class="text-muted small mb-0">Consulta el estado y seguimiento de tus vehículos</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalRegistrar">
        <i class="fas fa-plus me-1"></i> Registrar mi Vehículo
    </button>
</div>

<?php if (empty($vehiculos)): ?>
<!-- Estado vacío -->
<div class="card shadow-sm">
    <div class="card-body text-center py-5">
        <i class="fas fa-car fa-3x text-muted mb-3"></i>
        <h5 class="text-muted">No tienes vehículos registrados</h5>
        <p class="text-muted small">Registra tu vehículo para hacer seguimiento de su estado en el taller.</p>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalRegistrar">
            <i class="fas fa-plus me-1"></i> Registrar Vehículo
        </button>
    </div>
</div>
<?php else: ?>

<!-- ── Tabla con accordion de historial ──────────────────────────────────── -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tablaVehiculos">
                <thead class="table-light">
                    <tr>
                        <th style="width:30px;"></th>
                        <th>PLACA</th>
                        <th>VEHÍCULO</th>
                        <th>ESTADO</th>
                        <th>ÚLTIMO SEGUIMIENTO</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($vehiculos as $v):
                    $estado  = $v['estado'] ?? '';
                    $badge   = match(true) {
                        stripos($estado, 'pendiente')    !== false => 'bg-warning text-dark',
                        stripos($estado, 'reparaci')     !== false => 'bg-info text-dark',
                        stripos($estado, 'finalizado')   !== false => 'bg-success',
                        stripos($estado, 'pintura')      !== false => 'badge-pintura',
                        stripos($estado, 'entregado')    !== false => 'bg-primary',
                        default                                    => 'bg-secondary',
                    };
                    $hist    = $historiales[$v['id_vehiculo']] ?? [];
                    $ultimo  = $hist[0] ?? null;
                    $colId   = 'hist-' . $v['id_vehiculo'];
                ?>
                <!-- Fila principal -->
                <tr class="vehiculo-row" data-bs-toggle="collapse"
                    data-bs-target="#<?= $colId ?>" aria-expanded="false">
                    <td class="text-center text-muted">
                        <i class="fas fa-chevron-right toggle-icon" style="transition:.2s;font-size:.75rem;"></i>
                    </td>
                    <td class="fw-bold font-monospace"><?= htmlspecialchars($v['placa']) ?></td>
                    <td>
                        <div class="fw-semibold"><?= htmlspecialchars($v['marca'] . ' ' . $v['modelo']) ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($v['año'] ?? '') ?></div>
                    </td>
                    <td><span class="badge <?= $badge ?>"><?= htmlspecialchars($estado) ?></span></td>
                    <td>
                        <?php if ($ultimo): ?>
                            <div class="small fw-semibold"><?= htmlspecialchars($ultimo['tipo_reparacion']) ?></div>
                            <div class="text-muted" style="font-size:.72rem;">
                                <?= htmlspecialchars($ultimo['descripcion']) ?>
                                — <?= date('d/m/Y', strtotime($ultimo['fecha_registro'])) ?>
                            </div>
                        <?php else: ?>
                            <span class="text-muted small">Sin registros aún</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <!-- Fila de historial (collapse) -->
                <tr class="collapse historial-collapse" id="<?= $colId ?>">
                    <td colspan="5" class="p-0">
                        <div class="p-3">
                            <?php if (empty($hist)): ?>
                                <p class="text-muted small mb-0 text-center py-2">
                                    <i class="fas fa-info-circle me-1"></i>No hay historial para este vehículo.
                                </p>
                            <?php else: ?>
                                <p class="fw-semibold small mb-2 text-primary">
                                    <i class="fas fa-history me-1"></i>Historial completo
                                </p>
                                <div class="timeline">
                                <?php foreach ($hist as $h): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-dot"></div>
                                        <div class="d-flex justify-content-between align-items-start">
                                            <span class="badge bg-primary bg-opacity-10 text-primary small">
                                                <?= htmlspecialchars($h['tipo_reparacion']) ?>
                                            </span>
                                            <span class="timeline-date">
                                                <?= date('d/m/Y', strtotime($h['fecha_registro'])) ?>
                                            </span>
                                        </div>
                                        <p class="mb-0 mt-1 small text-dark"><?= htmlspecialchars($h['descripcion']) ?></p>
                                        <?php if (!empty($h['nombre_empleado'])): ?>
                                            <small class="text-muted">
                                                <i class="fas fa-user-tie me-1"></i><?= htmlspecialchars($h['nombre_empleado']) ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php endif; ?>

<!-- ════════════════════════════════════════════════════════════════════════════
     MODAL: REGISTRAR VEHÍCULO (cliente)
════════════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalRegistrar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4">
            <div class="modal-header" style="background:linear-gradient(90deg,#1a3a6b,#2563eb);">
                <h5 class="modal-title text-white"><i class="fas fa-car me-2"></i>Registrar mi Vehículo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="../../controllers/VehiculoController.php" method="POST">
                <input type="hidden" name="accion" value="registrar">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Placa *</label>
                            <input type="text" name="placa" class="form-control text-uppercase" required
                                   placeholder="Ej: ABC-123" maxlength="20">
                            <div class="form-text">Ingresa la placa tal como aparece en el documento del vehículo.</div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Marca *</label>
                            <input type="text" name="marca" class="form-control" required placeholder="Toyota, Chevrolet…">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Modelo *</label>
                            <input type="text" name="modelo" class="form-control" required placeholder="Corolla, Spark…">
                        </div>
                        <div class="col-sm-6">
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
/* Rotar ícono chevron al expandir/colapsar */
document.querySelectorAll('.collapse').forEach(colEl => {
    colEl.addEventListener('show.bs.collapse', () => {
        const row = colEl.previousElementSibling;
        if (row) row.querySelector('.toggle-icon')?.style.setProperty('transform', 'rotate(90deg)');
    });
    colEl.addEventListener('hide.bs.collapse', () => {
        const row = colEl.previousElementSibling;
        if (row) row.querySelector('.toggle-icon')?.style.setProperty('transform', 'rotate(0deg)');
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
