<?php
$titulo = 'Gestión de Usuarios';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Usuario.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario']) || (int)($_SESSION['usuario']['rol'] ?? 0) !== 1) {
    header("Location: ../usuarios/login.php"); exit;
}

require_once __DIR__ . '/../layouts/header.php';

$db     = (new Database())->conectar();
$model  = new Usuario($db);
$alert  = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);

$usuarios = $model->obtenerTodos();

// Mapa de roles: id_rol → texto y badge
$roles = [
    1 => ['texto' => 'Administrador', 'badge' => 'bg-dark'],
    2 => ['texto' => 'Cliente',       'badge' => 'bg-success'],
    3 => ['texto' => 'Empleado',      'badge' => 'bg-info text-dark'],
];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Usuarios</h4>
        <p class="text-muted small mb-0">Gestión de usuarios del sistema</p>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalRegistrar">
        <i class="fas fa-user-plus me-1"></i> Nuevo Usuario
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
                   placeholder="Buscar por nombre, correo o teléfono…">
        </div>
    </div>
</div>

<!-- TABLA -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tablaUsuarios">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>NOMBRE</th>
                        <th>EMAIL</th>
                        <th>TELÉFONO</th>
                        <th>ROL</th>
                        <th>ESTADO</th>
                        <th>ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($usuarios as $i => $u):
                    $idRol  = (int)($u['id_rol'] ?? 0);
                    $rolInfo = $roles[$idRol] ?? ['texto' => 'Desconocido', 'badge' => 'bg-secondary'];
                    $nombreCompleto = trim(($u['nombres'] ?? '') . ' ' . ($u['apellidos'] ?? '')) ?: '–';
                ?>
                <tr>
                    <td class="text-muted small"><?= $i + 1 ?></td>
                    <td class="fw-semibold"><?= htmlspecialchars($nombreCompleto) ?></td>
                    <td class="text-muted small"><?= htmlspecialchars($u['correo'] ?? '–') ?></td>
                    <td><?= htmlspecialchars($u['telefono'] ?? '–') ?></td>
                    <td>
                        <span class="badge <?= $rolInfo['badge'] ?>">
                            <?= $rolInfo['texto'] ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge <?= ($u['activo'] ?? 1) ? 'bg-success' : 'bg-danger' ?>">
                            <?= ($u['activo'] ?? 1) ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary me-1"
                                onclick="editarUsuario(<?= htmlspecialchars(json_encode($u)) ?>)"
                                title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <a href="../../controllers/AdminUsuarioController.php?accion=eliminar&id=<?= $u['id_usuario'] ?>"
                           class="btn btn-sm btn-outline-danger"
                           onclick="return confirm('¿Eliminar este usuario?')"
                           title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($usuarios)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No hay usuarios registrados.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ══ MODAL REGISTRAR ══════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalRegistrar" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4">
            <div class="modal-header" style="background:linear-gradient(90deg,#1a3a6b,#2563eb);">
                <h5 class="modal-title text-white">
                    <i class="fas fa-user-plus me-2"></i>Registrar Usuario
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="../../controllers/AdminUsuarioController.php" method="POST">
                <input type="hidden" name="accion" value="registrar">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Nombres *</label>
                            <input type="text" name="nombres" class="form-control" required
                                   placeholder="Ej: Carlos" />
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Apellidos *</label>
                            <input type="text" name="apellidos" class="form-control" required
                                   placeholder="Ej: Rodríguez" />
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Correo *</label>
                            <input type="email" name="correo" class="form-control" required
                                   placeholder="correo@taller.com">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Teléfono *</label>
                            <input type="tel" name="telefono" class="form-control" required
                                   placeholder="300 123 4567">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Contraseña *</label>
                            <input type="password" name="password" class="form-control" required
                                   placeholder="Mín. 6 caracteres">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Rol *</label>
                            <select name="id_rol" class="form-select" required>
                                <option value="2">Cliente</option>
                                <option value="3">Empleado</option>
                                <option value="1">Administrador</option>
                            </select>
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

<!-- ══ MODAL EDITAR ═════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalEditar" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>Editar Usuario
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="../../controllers/AdminUsuarioController.php" method="POST">
                <input type="hidden" name="accion" value="actualizar">
                <input type="hidden" name="id_usuario" id="editId">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Nombres *</label>
                            <input type="text" name="nombres" id="editNombres" class="form-control" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Apellidos *</label>
                            <input type="text" name="apellidos" id="editApellidos" class="form-control" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Correo *</label>
                            <input type="email" name="correo" id="editCorreo" class="form-control" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Teléfono *</label>
                            <input type="tel" name="telefono" id="editTelefono" class="form-control" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Rol *</label>
                            <select name="id_rol" id="editRol" class="form-select" required>
                                <option value="2">Cliente</option>
                                <option value="3">Empleado</option>
                                <option value="1">Administrador</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
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
        confirmButtonColor: '#000000'
    });
</script>
<?php endif; ?>

<script>
// Buscador en tiempo real
document.getElementById('buscador').addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#tablaUsuarios tbody tr').forEach(tr => {
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});

// Poblar modal editar
function editarUsuario(u) {
    document.getElementById('editId').value       = u.id_usuario;
    document.getElementById('editNombres').value   = u.nombres   ?? '';
    document.getElementById('editApellidos').value = u.apellidos ?? '';
    document.getElementById('editCorreo').value    = u.correo    ?? '';
    document.getElementById('editTelefono').value  = u.telefono  ?? '';
    document.getElementById('editRol').value       = u.id_rol    ?? 2;
    new bootstrap.Modal(document.getElementById('modalEditar')).show();
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
