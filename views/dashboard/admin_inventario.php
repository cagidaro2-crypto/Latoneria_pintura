<?php
$titulo = 'Inventario';
require_once __DIR__ . '/../../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario']) || (int)($_SESSION['usuario']['rol'] ?? 0) !== 1) {
    header("Location: ../usuarios/login.php"); exit;
}

require_once __DIR__ . '/../layouts/header.php';

$db    = (new Database())->conectar();
$alert = $_SESSION['alert'] ?? null; unset($_SESSION['alert']);

// ── Datos ─────────────────────────────────────────────────────────────────
$items = [];
try {
    $s = $db->query(
        "SELECT i.id_inventario, i.cantidad, i.unidad, i.stock_minimo, i.updated_at,
                p.id_producto, p.nombre, p.descripcion, p.precio, p.activo,
                cat.id_categoria, cat.nombre AS categoria_nombre
         FROM inventario i
         JOIN productos p          ON i.id_producto  = p.id_producto
         LEFT JOIN categorias_productos cat ON p.id_categoria = cat.id_categoria
         ORDER BY cat.nombre, p.nombre"
    );
    $items = $s ? $s->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (Exception $e) {}

$categorias = [];
try {
    $categorias = $db->query("SELECT * FROM categorias_productos ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$proveedores = [];
try {
    $proveedores = $db->query("SELECT id_proveedor, nombre FROM proveedores ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Agrupar por categoría
$porCategoria = [];
foreach ($items as $item) {
    $cat = $item['categoria_nombre'] ?? 'Sin categoría';
    $porCategoria[$cat][] = $item;
}

// Productos bajo stock
$bajoStock = array_filter($items, fn($i) => (float)$i['cantidad'] <= (float)$i['stock_minimo']);
$sinStock  = array_filter($items, fn($i) => (float)$i['cantidad'] <= 0);
?>

<style>
    /* Barra de stock */
    .stock-bar-wrap { height:6px;background:#e2e8f0;border-radius:4px;overflow:hidden;margin-top:3px; }
    .stock-bar      { height:100%;border-radius:4px;transition:width .4s; }
    /* Separador categoría */
    .cat-sep { display:flex;align-items:center;gap:.75rem;margin:1.5rem 0 .75rem; }
    .cat-sep span { font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#374151;white-space:nowrap; }
    .cat-sep hr   { flex:1;margin:0;border-color:#e2e8f0; }
</style>

<!-- ── Encabezado ──────────────────────────────────────────────────────────── -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Inventario</h4>
        <p class="text-muted small mb-0">Productos, pinturas y repuestos del taller</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalCategoria">
            <i class="fas fa-tags me-1"></i> Categorías
        </button>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAgregar">
            <i class="fas fa-plus me-1"></i> Agregar Producto
        </button>
    </div>
</div>

<!-- ── Alertas de stock ────────────────────────────────────────────────────── -->
<?php if (!empty($sinStock)): ?>
<div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
    <i class="fas fa-circle-xmark fa-lg"></i>
    <div><strong><?= count($sinStock) ?> producto(s) sin stock.</strong> Requieren reabastecimiento urgente.</div>
</div>
<?php elseif (!empty($bajoStock)): ?>
<div class="alert alert-warning d-flex align-items-center gap-2 mb-3">
    <i class="fas fa-triangle-exclamation fa-lg"></i>
    <div><strong><?= count($bajoStock) ?> producto(s)</strong> con stock bajo el mínimo.</div>
</div>
<?php endif; ?>

<!-- ── Tarjetas resumen ────────────────────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <?php
    $totalProductos = count($items);
    $activos        = count(array_filter($items, fn($i) => $i['activo']));
    $bajoBadge      = count($bajoStock);
    $sinBadge       = count($sinStock);
    $stats = [
        ['icon'=>'fa-boxes-stacked','bg'=>'#eff6ff','clr'=>'#1d4ed8','val'=>$totalProductos,'label'=>'Total Productos'],
        ['icon'=>'fa-check-circle', 'bg'=>'#f0fdf4','clr'=>'#15803d','val'=>$activos,       'label'=>'Activos'],
        ['icon'=>'fa-exclamation-triangle','bg'=>'#fefce8','clr'=>'#92400e','val'=>$bajoBadge,'label'=>'Bajo Stock'],
        ['icon'=>'fa-ban',          'bg'=>'#fef2f2','clr'=>'#b91c1c','val'=>$sinBadge,      'label'=>'Sin Stock'],
    ];
    foreach ($stats as $st): ?>
    <div class="col-6 col-xl-3">
        <div class="card" style="border-radius:12px;">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div style="width:46px;height:46px;border-radius:10px;background:<?= $st['bg'] ?>;display:flex;align-items:center;justify-content:center;color:<?= $st['clr'] ?>;font-size:1.1rem;flex-shrink:0;">
                    <i class="fas <?= $st['icon'] ?>"></i>
                </div>
                <div>
                    <div style="font-size:.72rem;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.4px;"><?= $st['label'] ?></div>
                    <div style="font-size:1.7rem;font-weight:800;color:#1e293b;line-height:1.1;"><?= $st['val'] ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ── Filtros ─────────────────────────────────────────────────────────────── -->
<div class="card mb-3">
    <div class="card-body py-2">
        <div class="row g-2">
            <div class="col-md-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" id="buscador" class="form-control" placeholder="Buscar producto…">
                </div>
            </div>
            <div class="col-md-3">
                <select id="filtroCategoria" class="form-select form-select-sm">
                    <option value="">Todas las categorías</option>
                    <?php foreach ($categorias as $c): ?>
                        <option value="<?= htmlspecialchars($c['nombre']) ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select id="filtroStock" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="bajo">Bajo stock</option>
                    <option value="sin">Sin stock</option>
                    <option value="ok">Stock OK</option>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- ── Tabla de inventario ────────────────────────────────────────────────── -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="tablaInventario">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Stock</th>
                        <th>Unidad</th>
                        <th>Mín.</th>
                        <th>Precio</th>
                        <th>Estado</th>
                        <th>Actualizado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item):
                    $cant  = (float)$item['cantidad'];
                    $min   = (float)$item['stock_minimo'];
                    $pct   = $min > 0 ? min(100, round($cant / $min * 100)) : 100;
                    $barClr = $cant <= 0 ? '#ef4444' : ($cant <= $min ? '#f59e0b' : '#22c55e');
                    $stockCls = $cant <= 0 ? 'text-danger fw-bold' : ($cant <= $min ? 'text-warning fw-bold' : 'text-dark');
                    $catName = htmlspecialchars($item['categoria_nombre'] ?? '—');
                ?>
                <tr data-cat="<?= $catName ?>"
                    data-stock="<?= $cant <= 0 ? 'sin' : ($cant <= $min ? 'bajo' : 'ok') ?>">
                    <td>
                        <div class="fw-semibold"><?= htmlspecialchars($item['nombre']) ?></div>
                        <?php if ($item['descripcion']): ?>
                            <div class="text-muted" style="font-size:.75rem;"><?= htmlspecialchars(mb_strimwidth($item['descripcion'],0,60,'…')) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span style="background:#f1f5f9;color:#374151;padding:.2em .65em;border-radius:20px;font-size:.75rem;font-weight:600;">
                            <?= $catName ?>
                        </span>
                    </td>
                    <td>
                        <div class="<?= $stockCls ?>"><?= $cant ?></div>
                        <div class="stock-bar-wrap" style="width:80px;">
                            <div class="stock-bar" style="width:<?= $pct ?>%;background:<?= $barClr ?>;"></div>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($item['unidad']) ?></td>
                    <td><?= $min ?></td>
                    <td>$<?= number_format($item['precio'], 0, ',', '.') ?></td>
                    <td>
                        <span class="badge <?= $item['activo'] ? 'bg-success' : 'bg-secondary' ?>">
                            <?= $item['activo'] ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </td>
                    <td style="font-size:.75rem;color:#94a3b8;"><?= date('d/m/Y', strtotime($item['updated_at'])) ?></td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary"
                                onclick="editarItem(<?= htmlspecialchars(json_encode($item)) ?>)"
                                title="Editar / ajustar stock">
                            <i class="fas fa-edit"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($items)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-5">
                        <i class="fas fa-boxes-stacked fa-2x d-block mb-2 opacity-25"></i>
                        No hay productos en el inventario.
                    </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ══ MODAL AGREGAR PRODUCTO ═══════════════════════════════════════════════ -->
<div class="modal fade" id="modalAgregar" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background:#1e293b;">
                <h5 class="modal-title text-white"><i class="fas fa-plus me-2"></i>Agregar Producto al Inventario</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="../../controllers/AdminInventarioController.php" method="POST">
                <input type="hidden" name="accion" value="agregar">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Nombre del producto *</label>
                            <input type="text" name="nombre" class="form-control" required placeholder="Ej: Pintura base blanca, Masilla epóxica…">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="2" placeholder="Descripción breve del producto…"></textarea>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Categoría *</label>
                            <select name="id_categoria" class="form-select" required>
                                <option value="">Seleccionar categoría…</option>
                                <?php foreach ($categorias as $c): ?>
                                    <option value="<?= $c['id_categoria'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Proveedor</label>
                            <select name="id_proveedor" class="form-select">
                                <option value="">Sin proveedor</option>
                                <?php foreach ($proveedores as $p): ?>
                                    <option value="<?= $p['id_proveedor'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label small fw-semibold">Precio *</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" name="precio" class="form-control" step="0.01" min="0" required placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label small fw-semibold">Cantidad inicial *</label>
                            <input type="number" name="cantidad" class="form-control" step="0.01" min="0" required placeholder="0">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label small fw-semibold">Unidad</label>
                            <select name="unidad" class="form-select">
                                <option value="unidad">Unidad</option>
                                <option value="litro">Litro</option>
                                <option value="galón">Galón</option>
                                <option value="kg">Kilogramo</option>
                                <option value="metro">Metro</option>
                                <option value="rollo">Rollo</option>
                                <option value="par">Par</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Stock mínimo</label>
                            <input type="number" name="stock_minimo" class="form-control" value="5" min="0">
                            <div class="form-text">Alerta cuando la cantidad baje de este valor.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-dark btn-sm"><i class="fas fa-save me-1"></i>Guardar producto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ══ MODAL EDITAR / AJUSTAR STOCK ════════════════════════════════════════ -->
<div class="modal fade" id="modalEditar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background:#374151;">
                <h5 class="modal-title text-white"><i class="fas fa-edit me-2"></i>Actualizar Stock</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="../../controllers/AdminInventarioController.php" method="POST">
                <input type="hidden" name="accion" value="actualizar">
                <input type="hidden" name="id_inventario" id="editIdInv">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Producto</label>
                        <input type="text" id="editNombreInv" class="form-control" readonly style="background:#f8fafc;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Categoría</label>
                        <input type="text" id="editCatInv" class="form-control" readonly style="background:#f8fafc;">
                    </div>
                    <div class="row g-3">
                        <div class="col-sm-4">
                            <label class="form-label small fw-semibold">Cantidad actual</label>
                            <input type="number" name="cantidad" id="editCantidadInv" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label small fw-semibold">Stock mínimo</label>
                            <input type="number" name="stock_minimo" id="editStockMin" class="form-control" min="0">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label small fw-semibold">Precio</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" name="precio" id="editPrecioInv" class="form-control" step="0.01" min="0">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-dark btn-sm"><i class="fas fa-save me-1"></i>Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ══ MODAL GESTIONAR CATEGORÍAS ══════════════════════════════════════════ -->
<div class="modal fade" id="modalCategoria" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background:#1e293b;">
                <h5 class="modal-title text-white"><i class="fas fa-tags me-2"></i>Gestionar Categorías</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Crear nueva categoría -->
                <form action="../../controllers/AdminInventarioController.php" method="POST" class="mb-4">
                    <input type="hidden" name="accion" value="nueva_categoria">
                    <label class="form-label small fw-semibold">Nueva categoría</label>
                    <div class="input-group input-group-sm">
                        <input type="text" name="nombre_categoria" class="form-control" required placeholder="Ej: Pintura, Masilla, Repuesto…">
                        <input type="text" name="descripcion_categoria" class="form-control" placeholder="Descripción (opcional)">
                        <button type="submit" class="btn btn-dark"><i class="fas fa-plus"></i></button>
                    </div>
                </form>
                <!-- Lista categorías existentes -->
                <table class="table table-sm">
                    <thead><tr><th>Nombre</th><th>Descripción</th></tr></thead>
                    <tbody>
                    <?php foreach ($categorias as $c): ?>
                        <tr><td><?= htmlspecialchars($c['nombre']) ?></td><td class="text-muted small"><?= htmlspecialchars($c['descripcion'] ?? '—') ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (empty($categorias)): ?>
                        <tr><td colspan="2" class="text-muted text-center py-3">No hay categorías creadas.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if ($alert): ?>
<script>
    Swal.fire({
        icon:'<?= htmlspecialchars($alert['icon']) ?>',
        title:'<?= htmlspecialchars($alert['title']) ?>',
        text:'<?= htmlspecialchars($alert['text']) ?>',
        confirmButtonColor:'#1e293b'
    });
</script>
<?php endif; ?>

<script>
// Buscador + filtros
function filtrar() {
    const q    = document.getElementById('buscador').value.toLowerCase();
    const cat  = document.getElementById('filtroCategoria').value.toLowerCase();
    const stk  = document.getElementById('filtroStock').value;
    document.querySelectorAll('#tablaInventario tbody tr').forEach(tr => {
        const txt  = tr.textContent.toLowerCase();
        const trCat = (tr.dataset.cat || '').toLowerCase();
        const trStk = tr.dataset.stock || '';
        const okQ   = !q   || txt.includes(q);
        const okCat = !cat || trCat.includes(cat);
        const okStk = !stk || trStk === stk;
        tr.style.display = okQ && okCat && okStk ? '' : 'none';
    });
}
document.getElementById('buscador').addEventListener('input', filtrar);
document.getElementById('filtroCategoria').addEventListener('change', filtrar);
document.getElementById('filtroStock').addEventListener('change', filtrar);

function editarItem(item) {
    document.getElementById('editIdInv').value       = item.id_inventario;
    document.getElementById('editNombreInv').value   = item.nombre;
    document.getElementById('editCatInv').value      = item.categoria_nombre ?? '—';
    document.getElementById('editCantidadInv').value = item.cantidad;
    document.getElementById('editStockMin').value    = item.stock_minimo;
    document.getElementById('editPrecioInv').value   = item.precio;
    new bootstrap.Modal(document.getElementById('modalEditar')).show();
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
