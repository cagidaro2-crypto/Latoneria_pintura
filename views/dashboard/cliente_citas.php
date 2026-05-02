<?php
$titulo = 'Agenda de Citas';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Vehiculo.php';
require_once __DIR__ . '/../layouts/header.php';

$db        = (new Database())->conectar();
$idCliente = $usuario['id_usuario'];
$vModel    = new Vehiculo($db);
$vehiculos = $vModel->obtenerPorCliente($idCliente);
$alert     = $_SESSION['alert'] ?? null; unset($_SESSION['alert']);

$stmtCitas = $db->prepare(
    "SELECT c.*, v.placa, v.marca FROM citas c
     LEFT JOIN vehiculos v ON c.id_vehiculo = v.id_vehiculo
     WHERE c.id_cliente = :id ORDER BY c.fecha_cita DESC"
);
$stmtCitas->execute([':id' => $idCliente]);
$citas = $stmtCitas->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 fw-semibold text-secondary">Mis Citas</h5>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAgendar">
        <i class="fas fa-calendar-plus me-1"></i> Agendar Cita
    </button>
</div>

<?php if (empty($vehiculos)): ?>
<div class="alert alert-info">
    <i class="fas fa-info-circle me-2"></i>Debe registrarse e iniciar sesión y tener un vehículo registrado antes de agendar una cita.
    <a href="cliente_vehiculos.php" class="alert-link ms-2">Registrar vehículo</a>
</div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>N° Ref</th>
                        <th>Vehículo</th>
                        <th>Tipo Servicio</th>
                        <th>Fecha y Hora</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($citas as $c): ?>
                <?php
                $eBadge = ['Pendiente'=>'bg-warning text-dark','Confirmada'=>'bg-success','Cancelada'=>'bg-danger','Realizada'=>'bg-primary'];
                $eb = $eBadge[$c['estado']] ?? 'bg-secondary';
                ?>
                <tr>
                    <td class="fw-semibold text-primary"><?= htmlspecialchars($c['numero_ref']) ?></td>
                    <td><?= $c['placa'] ? htmlspecialchars($c['placa'] . ' – ' . $c['marca']) : '–' ?></td>
                    <td><?= htmlspecialchars($c['tipo_servicio']) ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($c['fecha_cita'])) ?></td>
                    <td><span class="badge <?= $eb ?>"><?= $c['estado'] ?></span></td>
                    <td>
                        <?php if (in_array($c['estado'], ['Pendiente','Confirmada'])): ?>
                        <a href="../../controllers/ClienteCitaController.php?accion=cancelar&id=<?= $c['id_cita'] ?>"
                           class="btn btn-sm btn-outline-danger"
                           onclick="return confirm('¿Cancelar esta cita?')">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                        <?php else: ?>
                        <span class="text-muted small">–</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($citas)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No tienes citas agendadas.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL AGENDAR -->
<div class="modal fade" id="modalAgendar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4">
            <div class="modal-header" style="background:linear-gradient(90deg,#1a3a6b,#2563eb);">
                <h5 class="modal-title text-white"><i class="fas fa-calendar-plus me-2"></i>Agendar Cita</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="../../controllers/ClienteCitaController.php" method="POST">
                <input type="hidden" name="accion" value="agendar">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Vehículo *</label>
                        <select name="id_vehiculo" class="form-select" required>
                            <option value="">Seleccionar vehículo…</option>
                            <?php foreach ($vehiculos as $v): ?>
                                <option value="<?= $v['id_vehiculo'] ?>">
                                    <?= htmlspecialchars($v['placa'] . ' – ' . $v['marca'] . ' ' . $v['modelo']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Tipo de Servicio *</label>
                        <select name="tipo_servicio" class="form-select" required>
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
                        <label class="form-label small fw-semibold">Fecha y Hora *</label>
                        <input type="datetime-local" name="fecha_cita" class="form-control"
                               min="<?= date('Y-m-d\TH:i') ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Agendar Cita</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($alert): ?>
<script>
    Swal.fire({ icon:'<?= $alert['icon'] ?>', title:'<?= $alert['title'] ?>', text:'<?= $alert['text'] ?>', confirmButtonColor:'#2563eb' });
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
