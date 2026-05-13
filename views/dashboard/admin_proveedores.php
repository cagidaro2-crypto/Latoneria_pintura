<?php
$titulo = 'Proveedores';
require_once __DIR__ . '/../../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario']) || (int)($_SESSION['usuario']['rol'] ?? 0) !== 1) {
    header("Location: ../usuarios/login.php"); exit;
}

require_once __DIR__ . '/../layouts/header.php';

$db    = (new Database())->conectar();
$alert = $_SESSION['alert'] ?? null; unset($_SESSION['alert']);

$proveedores = $db->query("SELECT * FROM proveedor ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Proveedores</h4>
        <p class="text-muted small mb-0">Gestión de proveedores y distribuidores</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevo">
        <i class="fas fa-plus me-1"></i> Nuevo Proveedor
    </button>
</div>

<!-- BUSCADOR -->
<div class="card shadow-sm mb-4">
    <div class="card-body py-2">
        <div class="input-group">
            <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
            <input type="text" id="buscador" class="form-control border-start-0" placeholder="Buscar proveedores...">
        </div>
    </div>
</div>

<!-- TARJETAS PROVEEDORES -->
<div class="row g-3" id="listaProveedores">
<?php foreach ($proveedores as $p): ?>
<div class="col-md-4 proveedor-card" data-search="<?= strtolower($p['nombre'] . ' ' . ($p['correo'] ?? '')) ?>">
    <div class="card shadow-sm h-100">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <h6 class="fw-bold mb-0"><?= htmlspecialchars($p['nombre']) ?></h6>
                <button class="btn btn-sm btn-outline-secondary"
                        onclick="editarProveedor(<?= htmlspecialchars(json_encode($p)) ?>)">
                    <i class="fas fa-edit"></i>
                </button>
            </div>
            <div class="small text-muted mb-1">
                <?php if ($p['correo']): ?>
                <div><i class="fas fa-envelope me-2"></i><?= htmlspecialchars($p['correo']) ?></div>
                <?php endif; ?>
                <div><i class="fas fa-phone me-2"></i><?= htmlspecialchars($p['telefono']) ?></div>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php if (empty($proveedores)): ?>
    <div class="col-12 text-center text-muted py-5">
        <i class="fas fa-truck fa-3x mb-3 opacity-25"></i>
        <p>No hay proveedores registrados.</p>
    </div>
<?php endif; ?>
</div>

<!-- MODAL NUEVO PROVEEDOR -->
<div class="modal fade" id="modalNuevo" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4">
            <div class="modal-header" style="background:linear-gradient(90deg,#1a3a6b,#2563eb);">
                <h5 class="modal-title text-white"><i class="fas fa-truck me-2"></i>Nuevo Proveedor</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="../../controllers/AdminProveedorController.php" method="POST">
                <input type="hidden" name="accion" value="registrar">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nombre *</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Teléfono *</label>
                        <input type="tel" name="telefono" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Correo</label>
                        <input type="email" name="correo" class="form-control">
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

<!-- MODAL EDITAR PROVEEDOR -->
<div class="modal fade" id="modalEditar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Editar Proveedor</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="../../controllers/AdminProveedorController.php" method="POST">
                <input type="hidden" name="accion" value="actualizar">
                <input type="hidden" name="id_proveedor" id="editId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nombre *</label>
                        <input type="text" name="nombre" id="editNombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Teléfono *</label>
                        <input type="tel" name="telefono" id="editTelefono" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Correo</label>
                        <input type="email" name="correo" id="editCorreo" class="form-control">
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
document.getElementById('buscador').addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.proveedor-card').forEach(card => {
        card.style.display = card.dataset.search.includes(q) ? '' : 'none';
    });
});

function editarProveedor(p) {
    document.getElementById('editId').value       = p.id_proveedor;
    document.getElementById('editNombre').value   = p.nombre;
    document.getElementById('editTelefono').value = p.telefono;
    document.getElementById('editCorreo').value   = p.correo ?? '';
    new bootstrap.Modal(document.getElementById('modalEditar')).show();
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
