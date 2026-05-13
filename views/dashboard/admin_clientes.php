<?php
$titulo = 'Clientes';
require_once __DIR__ . '/../../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario']) || (int)($_SESSION['usuario']['rol'] ?? 0) !== 1) {
    header("Location: ../usuarios/login.php"); exit;
}

require_once __DIR__ . '/../layouts/header.php';

$db    = (new Database())->conectar();
$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);

// Columnas reales de tabla persona: id_persona, nombre, contraseña, correo, telefono, id_rol, activo
$stmt = $db->query(
    "SELECT id_persona, nombre, correo, telefono, activo
     FROM persona
     WHERE id_rol = 2
     ORDER BY nombre ASC"
);
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Clientes</h4>
        <p class="text-muted small mb-0">Gestión de clientes del taller</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoCliente">
        <i class="fas fa-plus me-1"></i> Nuevo Cliente
    </button>
</div>

<!-- BUSCADOR -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <div class="input-group">
            <span class="input-group-text bg-white border-end-0">
                <i class="fas fa-search text-muted"></i>
            </span>
            <input type="text" id="buscador" class="form-control border-start-0"
                   placeholder="Buscar por nombre, correo o teléfono...">
        </div>
    </div>
</div>

<!-- TABLA -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tablaClientes">
                <thead class="table-light">
                    <tr>
                        <th>CLIENTE</th>
                        <th>CONTACTO</th>
                        <th>CORREO</th>
                        <th>ESTADO</th>
                        <th>ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($clientes as $c): ?>
                <tr>
                    <td class="fw-semibold"><?= htmlspecialchars($c['nombre'] ?? '–') ?></td>
                    <td>
                        <div class="small">
                            <i class="fas fa-phone text-muted me-1"></i>
                            <?= htmlspecialchars($c['telefono'] ?? '–') ?>
                        </div>
                    </td>
                    <td class="text-muted small"><?= htmlspecialchars($c['correo'] ?? '–') ?></td>
                    <td>
                        <span class="badge <?= ($c['activo'] ?? 1) ? 'bg-success' : 'bg-danger' ?>">
                            <?= ($c['activo'] ?? 1) ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary me-1"
                                onclick="editarCliente(<?= htmlspecialchars(json_encode($c)) ?>)"
                                title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <?php if (($c['activo'] ?? 1)): ?>
                            <a href="../../controllers/AdminClienteController.php?accion=toggle&id=<?= $c['id_persona'] ?>&estado=0"
                               class="btn btn-sm btn-outline-warning me-1"
                               onclick="return confirm('¿Desactivar este cliente?')"
                               title="Desactivar">
                                <i class="fas fa-ban"></i>
                            </a>
                        <?php else: ?>
                            <a href="../../controllers/AdminClienteController.php?accion=toggle&id=<?= $c['id_persona'] ?>&estado=1"
                               class="btn btn-sm btn-outline-success me-1"
                               onclick="return confirm('¿Activar este cliente?')"
                               title="Activar">
                                <i class="fas fa-check-circle"></i>
                            </a>
                        <?php endif; ?>
                        <a href="../../controllers/AdminClienteController.php?accion=eliminar&id=<?= $c['id_persona'] ?>"
                           class="btn btn-sm btn-outline-danger"
                           onclick="return confirm('¿Eliminar permanentemente este cliente?')"
                           title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($clientes)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No hay clientes registrados.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ══ MODAL NUEVO CLIENTE ══════════════════════════════════════════════════ -->
<div class="modal fade" id="modalNuevoCliente" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4">
            <div class="modal-header" style="background:linear-gradient(90deg,#1a3a6b,#2563eb);">
                <h5 class="modal-title text-white">
                    <i class="fas fa-user-plus me-2"></i>Nuevo Cliente
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="../../controllers/AdminClienteController.php" method="POST">
                <input type="hidden" name="accion" value="registrar">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Nombre completo *</label>
                            <input type="text" name="nombre" class="form-control" required
                                   placeholder="Ej: Juan Pérez">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Correo *</label>
                            <input type="email" name="correo" class="form-control" required
                                   placeholder="correo@ejemplo.com">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Teléfono *</label>
                            <input type="tel" name="telefono" class="form-control" required
                                   placeholder="300 123 4567">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Contraseña *</label>
                            <input type="password" name="password" class="form-control" required
                                   placeholder="Mín. 6 caracteres">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ══ MODAL EDITAR CLIENTE ═════════════════════════════════════════════════ -->
<div class="modal fade" id="modalEditarCliente" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>Editar Cliente
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="../../controllers/AdminClienteController.php" method="POST">
                <input type="hidden" name="accion" value="actualizar">
                <input type="hidden" name="id_persona" id="editIdCliente">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Nombre completo *</label>
                            <input type="text" name="nombre" id="editNombreCliente" class="form-control" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Correo *</label>
                            <input type="email" name="correo" id="editCorreoCliente" class="form-control" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Teléfono *</label>
                            <input type="tel" name="telefono" id="editTelefonoCliente" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">
                                Nueva Contraseña
                                <small class="text-muted fw-normal">(dejar vacío para no cambiar)</small>
                            </label>
                            <input type="password" name="password" class="form-control"
                                   placeholder="••••••••">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Guardar Cambios
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
        confirmButtonColor: '#2563eb'
    });
</script>
<?php endif; ?>

<script>
document.getElementById('buscador').addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#tablaClientes tbody tr').forEach(tr => {
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});

function editarCliente(c) {
    document.getElementById('editIdCliente').value      = c.id_persona;
    document.getElementById('editNombreCliente').value  = c.nombre   ?? '';
    document.getElementById('editCorreoCliente').value  = c.correo   ?? '';
    document.getElementById('editTelefonoCliente').value= c.telefono ?? '';
    new bootstrap.Modal(document.getElementById('modalEditarCliente')).show();
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
