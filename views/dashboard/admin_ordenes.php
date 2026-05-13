<?php
$titulo = 'Órdenes de Servicio';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Usuario.php';
require_once __DIR__ . '/../../models/Vehiculo.php';
require_once __DIR__ . '/../../models/OrdenServicio.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario']) || !in_array((int)($_SESSION['usuario']['rol'] ?? 0), [1, 3])) {
    header("Location: ../usuarios/login.php"); exit;
}

require_once __DIR__ . '/../layouts/header.php';

$db     = (new Database())->conectar();
$oModel = new OrdenServicio($db);
$uModel = new Usuario($db);
$alert  = $_SESSION['alert'] ?? null; unset($_SESSION['alert']);

$ordenes   = $oModel->obtenerTodas();
$clientes  = $uModel->obtenerTodos('cliente');

// Empleados: todos los que tienen id_rol=3 en persona, con o sin registro en tabla empleado
$stmtEmp = $db->query(
    "SELECT p.id_persona, p.nombre, p.telefono,
            e.id_empleado
     FROM persona p
     LEFT JOIN empleado e ON e.id_persona = p.id_persona
     WHERE p.id_rol = 3 AND p.activo = 1
     ORDER BY p.nombre ASC"
);
$empleados = $stmtEmp->fetchAll(PDO::FETCH_ASSOC);

// Vehículos con nombre del dueño: vehiculo → cliente
$stmtVeh = $db->query(
    "SELECT v.id_vehiculo, v.placa, v.marca, v.modelo, v.`año`,
            cl.nombre AS nombre_dueno
     FROM vehiculo v
     JOIN cliente cl ON v.id_cliente = cl.id_cliente
     ORDER BY v.placa ASC"
);
$vehiculos = $stmtVeh->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 fw-semibold text-secondary">Listado de Órdenes</h5>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCrear">
        <i class="fas fa-plus me-1"></i> Crear Orden
    </button>
</div>

<!-- FILTRO -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <input type="text" id="buscador" class="form-control form-control-sm" placeholder="Buscar por número, cliente, placa, estado…">
    </div>
</div>

<!-- TABLA -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="tablaOrdenes">
                <thead class="table-light">
                    <tr>
                        <th>N° Orden</th>
                        <th>Cliente</th>
                        <th>Vehículo</th>
                        <th>Tipo Servicio</th>
                        <th>Estado</th>
                        <th>Fecha Ingreso</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($ordenes as $o): ?>
                <tr>
                    <td class="fw-semibold text-primary"><?= htmlspecialchars($o['numero_orden']) ?></td>
                    <td><?= htmlspecialchars($o['cliente_nombre']) ?></td>
                    <td><?= htmlspecialchars($o['placa'] . ' – ' . $o['marca'] . ' ' . $o['modelo']) ?></td>
                    <td><?= htmlspecialchars($o['tipo_servicio']) ?></td>
                    <td>
                        <?php
                        $badges = [
                            'Ingresado'     => 'bg-secondary',
                            'En espera'     => 'bg-warning text-dark',
                            'En reparación' => 'bg-info text-dark',
                            'Finalizado'    => 'bg-success',
                            'Entregado'     => 'bg-primary',
                        ];
                        $b = $badges[$o['estado']] ?? 'bg-secondary';
                        ?>
                        <span class="badge <?= $b ?>"><?= htmlspecialchars($o['estado']) ?></span>
                    </td>
                    <td><?= date('d/m/Y H:i', strtotime($o['fecha_ingreso'])) ?></td>
                    <td class="d-flex gap-1">
                        <button class="btn btn-sm btn-outline-primary"
                                onclick="actualizarEstado(<?= $o['id_orden'] ?>, '<?= htmlspecialchars($o['estado']) ?>')">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <a href="admin_orden_detalle.php?id=<?= $o['id_orden'] ?>"
                           class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($ordenes)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No hay órdenes registradas.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL CREAR ORDEN -->
<div class="modal fade" id="modalCrear" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4">
            <div class="modal-header" style="background:linear-gradient(90deg,#1a3a6b,#2563eb);">
                <h5 class="modal-title text-white"><i class="fas fa-plus me-2"></i>Crear Orden de Servicio</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="../../controllers/AdminOrdenController.php" method="POST">
                <input type="hidden" name="accion" value="crear">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Cliente *</label>
                            <select name="id_cliente" class="form-select" required>
                                <option value="">Seleccionar cliente…</option>
                                <?php foreach ($clientes as $c): ?>
                                    <option value="<?= $c['id_persona'] ?>">
                                        <?= htmlspecialchars($c['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Vehículo (Placa) *</label>
                            <select name="id_vehiculo" class="form-select" required>
                                <option value="">Seleccionar vehículo…</option>
                                <?php foreach ($vehiculos as $v): ?>
                                    <option value="<?= $v['id_vehiculo'] ?>">
                                        <?= htmlspecialchars($v['placa'] . ' – ' . $v['marca'] . ' ' . $v['modelo'] . ' | Dueño: ' . $v['nombre_dueno']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Empleado Asignado</label>
                            <select name="id_empleado" class="form-select">
                                <option value="">— Sin asignar —</option>
                                <?php foreach ($empleados as $e): ?>
                                    <option value="<?= $e['id_persona'] ?>">
                                        <?= htmlspecialchars($e['nombre']) ?>
                                        <?= !empty($e['telefono']) ? ' · ' . htmlspecialchars($e['telefono']) : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($empleados)): ?>
                                <div class="form-text text-warning">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    No hay empleados registrados en el sistema.
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Tipo de Servicio *</label>
                            <select name="tipo_servicio" class="form-select" required>
                                <option value="">Seleccionar…</option>
                                <option>Latonería</option>
                                <option>Pintura completa</option>
                                <option>Pintura parcial</option>
                                <option>Enderezado</option>
                                <option>Pulida y brillada</option>
                                <option>Otros</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="3" placeholder="Descripción del daño o servicio requerido…"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Orden</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL CAMBIAR ESTADO -->
<div class="modal fade" id="modalEstado" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title"><i class="fas fa-sync-alt me-2"></i>Actualizar Estado</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="../../controllers/AdminOrdenController.php" method="POST">
                <input type="hidden" name="accion" value="actualizar_estado">
                <input type="hidden" name="id_orden" id="estadoIdOrden">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nuevo Estado *</label>
                        <select name="estado" id="selectEstado" class="form-select" required>
                            <option value="Ingresado">Ingresado</option>
                            <option value="En espera">En espera</option>
                            <option value="En reparación">En reparación</option>
                            <option value="Finalizado">Finalizado</option>
                            <option value="Entregado">Entregado</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Observación</label>
                        <textarea name="observacion" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Actualizar</button>
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

<script>
document.getElementById('buscador').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#tablaOrdenes tbody tr').forEach(tr => {
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});

function actualizarEstado(idOrden, estadoActual) {
    document.getElementById('estadoIdOrden').value = idOrden;
    document.getElementById('selectEstado').value  = estadoActual;
    new bootstrap.Modal(document.getElementById('modalEstado')).show();
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
