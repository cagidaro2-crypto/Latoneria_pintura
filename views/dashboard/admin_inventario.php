<?php
$titulo = 'Gestión de Inventario';
require_once __DIR__ . '/../../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario']) || (int)($_SESSION['usuario']['rol'] ?? 0) !== 1) {
    header("Location: ../usuarios/login.php"); exit;
}

require_once __DIR__ . '/../layouts/header.php';

$db    = (new Database())->conectar();
$alert = $_SESSION['alert'] ?? null; unset($_SESSION['alert']);

$stmt = $db->query(
    "SELECT i.*, p.nombre, p.descripcion, p.categoria, p.precio, p.activo
     FROM inventario i
     JOIN productos p ON i.id_producto = p.id_producto
     ORDER BY p.nombre ASC"
);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Alertas de stock bajo
$bajoStock = array_filter($items, fn($i) => $i['cantidad'] <= $i['stock_minimo']);
?>

<?php if (!empty($bajoStock)): ?>
<div class="alert alert-warning alert-dismissible fade show d-flex align-items-center gap-2 mb-3" role="alert">
    <i class="fas fa-triangle-exclamation"></i>
    <div>
        <strong><?= count($bajoStock) ?> producto(s)</strong> con bajo stock. Revisa y reabastece el inventario.
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 fw-semibold text-secondary">Inventario de Repuestos y Materiales</h5>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAgregar">
        <i class="fas fa-plus me-1"></i> Agregar Producto
    </button>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <input type="text" id="buscador" class="form-control form-control-sm" placeholder="Buscar producto…">
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="tablaInventario">
                <thead class="table-light">
                    <tr>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Cantidad</th>
                        <th>Unidad</th>
                        <th>Stock Mín.</th>
                        <th>Precio</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td class="fw-semibold"><?= htmlspecialchars($item['nombre']) ?></td>
                    <td><?= htmlspecialchars($item['categoria'] ?? '–') ?></td>
                    <td>
                        <?php if ($item['cantidad'] <= $item['stock_minimo']): ?>
                            <span class="text-danger fw-bold"><?= $item['cantidad'] ?></span>
                            <i class="fas fa-exclamation-circle text-danger ms-1" title="Stock bajo"></i>
                        <?php else: ?>
                            <?= $item['cantidad'] ?>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($item['unidad']) ?></td>
                    <td><?= $item['stock_minimo'] ?></td>
                    <td>$<?= number_format($item['precio'], 2) ?></td>
                    <td>
                        <span class="badge <?= $item['activo'] ? 'bg-success' : 'bg-danger' ?>">
                            <?= $item['activo'] ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary"
                                onclick="editarItem(<?= htmlspecialchars(json_encode($item)) ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($items)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No hay productos en el inventario.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL AGREGAR -->
<div class="modal fade" id="modalAgregar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4">
            <div class="modal-header" style="background:linear-gradient(90deg,#1a3a6b,#2563eb);">
                <h5 class="modal-title text-white"><i class="fas fa-plus me-2"></i>Agregar Producto</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="../../controllers/AdminInventarioController.php" method="POST">
                <input type="hidden" name="accion" value="agregar">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nombre *</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Categoría</label>
                            <input type="text" name="categoria" class="form-control" placeholder="Ej: Pintura, Repuesto…">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Precio *</label>
                            <input type="number" name="precio" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label small fw-semibold">Cantidad *</label>
                            <input type="number" name="cantidad" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label small fw-semibold">Unidad</label>
                            <select name="unidad" class="form-select">
                                <option>unidad</option>
                                <option>litro</option>
                                <option>kg</option>
                                <option>metro</option>
                                <option>galón</option>
                            </select>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label small fw-semibold">Stock Mín.</label>
                            <input type="number" name="stock_minimo" class="form-control" value="5" min="0">
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

<!-- MODAL EDITAR CANTIDAD -->
<div class="modal fade" id="modalEditar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Actualizar Producto</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="../../controllers/AdminInventarioController.php" method="POST">
                <input type="hidden" name="accion" value="actualizar">
                <input type="hidden" name="id_inventario" id="editIdInv">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Producto</label>
                        <input type="text" id="editNombreInv" class="form-control" readonly>
                    </div>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Cantidad</label>
                            <input type="number" name="cantidad" id="editCantidadInv" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Stock Mínimo</label>
                            <input type="number" name="stock_minimo" id="editStockMin" class="form-control" min="0">
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
document.getElementById('buscador').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#tablaInventario tbody tr').forEach(tr => {
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});

function editarItem(item) {
    document.getElementById('editIdInv').value       = item.id_inventario;
    document.getElementById('editNombreInv').value   = item.nombre;
    document.getElementById('editCantidadInv').value = item.cantidad;
    document.getElementById('editStockMin').value    = item.stock_minimo;
    new bootstrap.Modal(document.getElementById('modalEditar')).show();
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
