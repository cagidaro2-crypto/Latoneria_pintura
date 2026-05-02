<?php
$titulo = 'Gestión de Usuarios';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Usuario.php';
require_once __DIR__ . '/../layouts/header.php';

$db     = (new Database())->conectar();
$model  = new Usuario($db);
$alert  = $_SESSION['alert'] ?? null; unset($_SESSION['alert']);

$usuarios = $model->obtenerTodos();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 fw-semibold text-secondary">Listado de Usuarios</h5>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalRegistrar">
        <i class="fas fa-user-plus me-1"></i> Registrar Usuario
    </button>
</div>

<!-- FILTRO -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <input type="text" id="buscador" class="form-control form-control-sm" placeholder="Buscar por nombre, correo, identificación…">
    </div>
</div>

<!-- TABLA -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="tablaUsuarios">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Identificación</th>
                        <th>Correo</th>
                        <th>Teléfono</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($usuarios as $i => $u): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td class="fw-semibold"><?= htmlspecialchars($u['nombres'] . ' ' . $u['apellidos']) ?></td>
                    <td><?= htmlspecialchars($u['identificacion']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars($u['telefono'] ?? '–') ?></td>
                    <td>
                        <?php
                        $rolBadge = ['administrador'=>'bg-primary','empleado'=>'bg-info text-dark','cliente'=>'bg-secondary'];
                        $b = $rolBadge[$u['rol']] ?? 'bg-secondary';
                        ?>
                        <span class="badge <?= $b ?>"><?= ucfirst($u['rol']) ?></span>
                    </td>
                    <td>
                        <span class="badge <?= $u['activo'] ? 'bg-success' : 'bg-danger' ?>">
                            <?= $u['activo'] ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary" onclick="editarUsuario(<?= htmlspecialchars(json_encode($u)) ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <a href="../../controllers/AdminUsuarioController.php?accion=eliminar&id=<?= $u['id_usuario'] ?>"
                           class="btn btn-sm btn-outline-danger"
                           onclick="return confirm('¿Eliminar usuario?')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($usuarios)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No hay usuarios registrados.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL REGISTRAR -->
<div class="modal fade" id="modalRegistrar" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4">
            <div class="modal-header" style="background:linear-gradient(90deg,#1a3a6b,#2563eb);">
                <h5 class="modal-title text-white"><i class="fas fa-user-plus me-2"></i>Registrar Usuario</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="../../controllers/AdminUsuarioController.php" method="POST">
                <input type="hidden" name="accion" value="registrar">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Nombres *</label>
                            <input type="text" name="nombres" class="form-control" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Apellidos *</label>
                            <input type="text" name="apellidos" class="form-control" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Identificación *</label>
                            <input type="text" name="identificacion" class="form-control" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Teléfono</label>
                            <input type="tel" name="telefono" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Correo *</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Contraseña *</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Rol *</label>
                            <select name="rol" class="form-select" required>
                                <option value="cliente">Cliente</option>
                                <option value="empleado">Empleado</option>
                                <option value="administrador">Administrador</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL EDITAR -->
<div class="modal fade" id="modalEditar" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Editar Usuario</h5>
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
                            <label class="form-label small fw-semibold">Identificación *</label>
                            <input type="text" name="identificacion" id="editIdentificacion" class="form-control" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Teléfono</label>
                            <input type="tel" name="telefono" id="editTelefono" class="form-control">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Rol *</label>
                            <select name="rol" id="editRol" class="form-select" required>
                                <option value="cliente">Cliente</option>
                                <option value="empleado">Empleado</option>
                                <option value="administrador">Administrador</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Nueva Contraseña <small class="text-muted">(dejar vacío para no cambiar)</small></label>
                            <input type="password" name="password" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
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
// Filtro en tiempo real
document.getElementById('buscador').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#tablaUsuarios tbody tr').forEach(tr => {
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});

function editarUsuario(u) {
    document.getElementById('editId').value          = u.id_usuario;
    document.getElementById('editNombres').value     = u.nombres;
    document.getElementById('editApellidos').value   = u.apellidos;
    document.getElementById('editIdentificacion').value = u.identificacion;
    document.getElementById('editTelefono').value    = u.telefono ?? '';
    document.getElementById('editRol').value         = u.rol;
    new bootstrap.Modal(document.getElementById('modalEditar')).show();
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
