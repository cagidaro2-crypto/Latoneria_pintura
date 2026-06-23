<?php
$titulo = 'Inventario – Productos';
require_once __DIR__ . '/../../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario']) || (int)($_SESSION['usuario']['rol'] ?? 0) !== 2) {
    header("Location: ../usuarios/login.php"); exit;
}
require_once __DIR__ . '/../layouts/header.php';

$db    = (new Database())->conectar();
$alert = $_SESSION['alert'] ?? null; unset($_SESSION['alert']);

// ── Productos + inventario + categoría ───────────────────────────────────
$items = [];
try {
    $s = $db->query(
        "SELECT i.id_inventario, i.cantidad, i.unidad, i.stock_minimo, i.updated_at,
                p.id_producto, p.nombre, p.descripcion, p.precio, p.activo,
                cat.nombre AS categoria_nombre
         FROM inventario i
         JOIN productos p ON i.id_producto = p.id_producto
         LEFT JOIN categorias_productos cat ON p.id_categoria = cat.id_categoria
         WHERE p.activo = 1
         ORDER BY cat.nombre, p.nombre"
    );
    $items = $s ? $s->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (Exception $e) {}

$categorias = [];
try {
    $categorias = $db->query("SELECT * FROM categorias_productos ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Agrupar
$porCategoria = [];
foreach ($items as $item) {
    $cat = $item['categoria_nombre'] ?? 'Sin categoría';
    $porCategoria[$cat][] = $item;
}

$bajoStock = array_filter($items, fn($i) => (float)$i['cantidad'] <= (float)$i['stock_minimo']);
$sinStock  = array_filter($items, fn($i) => (float)$i['cantidad'] <= 0);
?>

<style>
    .stock-bar-wrap { height:5px;background:#e2e8f0;border-radius:4px;overflow:hidden;margin-top:3px; }
    .stock-bar      { height:100%;border-radius:4px;transition:width .4s; }
</style>

<!-- Encabezado -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="fas fa-boxes-stacked me-2"></i>Inventario de Productos</h4>
        <p class="text-muted small mb-0">Consulta y descuenta stock de productos usados en el taller</p>
    </div>
</div>

<!-- Alertas -->
<?php if (!empty($sinStock)): ?>
<div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
    <i class="fas fa-circle-xmark fa-lg"></i>
    <div><strong><?= count($sinStock) ?> producto(s) sin stock.</strong> Notifica al administrador.</div>
</div>
<?php elseif (!empty($bajoStock)): ?>
<div class="alert alert-warning d-flex align-items-center gap-2 mb-3">
    <i class="fas fa-triangle-exclamation fa-lg"></i>
    <div><strong><?= count($bajoStock) ?> producto(s)</strong> con stock bajo el mínimo.</div>
</div>
<?php endif; ?>

<!-- Tarjetas resumen -->
<div class="row g-3 mb-4">
    <?php foreach ([
        ['icon'=>'fa-boxes-stacked','bg'=>'#eff6ff','clr'=>'#1d4ed8','val'=>count($items),         'label'=>'Total Productos'],
        ['icon'=>'fa-exclamation-triangle','bg'=>'#fefce8','clr'=>'#92400e','val'=>count($bajoStock),'label'=>'Bajo Stock'],
        ['icon'=>'fa-ban',          'bg'=>'#fef2f2','clr'=>'#b91c1c','val'=>count($sinStock),       'label'=>'Sin Stock'],
        ['icon'=>'fa-tags',         'bg'=>'#f0fdf4','clr'=>'#15803d','val'=>count($categorias),     'label'=>'Categorías'],
    ] as $st): ?>
    <div class="col-6 col-xl-3">
        <div class="card" style="border-radius:12px;">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div style="width:44px;height:44px;border-radius:10px;background:<?= $st['bg'] ?>;display:flex;align-items:center;justify-content:center;color:<?= $st['clr'] ?>;flex-shrink:0;">
                    <i class="fas <?= $st['icon'] ?>"></i>
                </div>
                <div>
                    <div style="font-size:.72rem;color:#64748b;font-weight:700;text-transform:uppercase;"><?= $st['label'] ?></div>
                    <div style="font-size:1.7rem;font-weight:800;color:#1e293b;line-height:1.1;"><?= $st['val'] ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filtros -->
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

<!-- Tabla -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="tablaInv">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Stock actual</th>
                        <th>Unidad</th>
                        <th>Mín.</th>
                        <th>Precio</th>
                        <th>Actualizado</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item):
                    $cant   = (float)$item['cantidad'];
                    $min    = (float)$item['stock_minimo'];
                    $pct    = $min > 0 ? min(100, round($cant / $min * 100)) : 100;
                    $barClr = $cant <= 0 ? '#ef4444' : ($cant <= $min ? '#f59e0b' : '#22c55e');
                    $txClr  = $cant <= 0 ? 'text-danger fw-bold' : ($cant <= $min ? 'text-warning fw-bold' : '');
                    $catN   = htmlspecialchars($item['categoria_nombre'] ?? '—');
                ?>
                <tr data-cat="<?= $catN ?>"
                    data-stock="<?= $cant<=0?'sin':($cant<=$min?'bajo':'ok') ?>">
                    <td>
                        <div class="fw-semibold"><?= htmlspecialchars($item['nombre']) ?></div>
                        <?php if ($item['descripcion']): ?>
                        <div class="text-muted" style="font-size:.75rem;"><?= htmlspecialchars(mb_strimwidth($item['descripcion'],0,55,'…')) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><span style="background:#f1f5f9;color:#374151;padding:.2em .65em;border-radius:20px;font-size:.75rem;font-weight:600;"><?= $catN ?></span></td>
                    <td>
                        <div class="<?= $txClr ?>"><?= $cant ?></div>
                        <div class="stock-bar-wrap" style="width:70px;">
                            <div class="stock-bar" style="width:<?= $pct ?>%;background:<?= $barClr ?>;"></div>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($item['unidad']) ?></td>
                    <td><?= $min ?></td>
                    <td>$<?= number_format($item['precio'],0,',','.') ?></td>
                    <td style="font-size:.75rem;color:#94a3b8;"><?= date('d/m/Y',strtotime($item['updated_at'])) ?></td>
                    <td>
                        <button class="btn btn-sm btn-outline-dark"
                                title="Descontar stock"
                                onclick="abrirDescontar(<?= htmlspecialchars(json_encode([
                                    'id_inventario' => $item['id_inventario'],
                                    'nombre'        => $item['nombre'],
                                    'cantidad'      => $item['cantidad'],
                                    'unidad'        => $item['unidad'],
                                ])) ?>)">
                            <i class="fas fa-minus-circle me-1"></i>Descontar
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($items)): ?>
                <tr><td colspan="8" class="text-center text-muted py-5">
                    <i class="fas fa-boxes-stacked fa-2x d-block mb-2 opacity-25"></i>
                    No hay productos en el inventario.
                </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ══ MODAL DESCONTAR STOCK ════════════════════════════════════════════════ -->
<div class="modal fade" id="modalDescontar" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background:#1e293b;">
                <h5 class="modal-title text-white"><i class="fas fa-minus-circle me-2"></i>Descontar Stock</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="../../controllers/AdminInventarioController.php" method="POST">
                <input type="hidden" name="accion" value="descontar">
                <input type="hidden" name="id_inventario" id="descIdInv">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Producto</label>
                        <input type="text" id="descNombre" class="form-control form-control-sm" readonly style="background:#f8fafc;">
                    </div>
                    <div class="mb-1">
                        <label class="form-label small fw-semibold">Stock actual</label>
                        <input type="text" id="descActual" class="form-control form-control-sm" readonly style="background:#f8fafc;">
                    </div>
                    <hr class="my-3">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Cantidad a descontar *</label>
                        <input type="number" name="cantidad_descontar" id="descCantidad"
                               class="form-control" step="0.01" min="0.01" required
                               placeholder="Ej: 2">
                        <div class="form-text">Ingresa cuántas unidades usaste.</div>
                    </div>
                    <div class="mb-1">
                        <label class="form-label small fw-semibold">Motivo (opcional)</label>
                        <input type="text" name="motivo" class="form-control form-control-sm"
                               placeholder="Ej: Usado en orden #45, reparación de capó…" maxlength="120">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-dark"><i class="fas fa-check me-1"></i>Confirmar descuento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($alert): ?>
<script>
    Swal.fire({
        icon:  '<?= htmlspecialchars($alert['icon']) ?>',
        title: '<?= htmlspecialchars($alert['title']) ?>',
        text:  '<?= htmlspecialchars($alert['text']) ?>',
        confirmButtonColor: '#000'
    });
</script>
<?php endif; ?>

<script>
function filtrar() {
    const q   = document.getElementById('buscador').value.toLowerCase();
    const cat = document.getElementById('filtroCategoria').value.toLowerCase();
    const stk = document.getElementById('filtroStock').value;
    document.querySelectorAll('#tablaInv tbody tr[data-cat]').forEach(tr => {
        const ok = (!q   || tr.textContent.toLowerCase().includes(q))
                && (!cat || (tr.dataset.cat||'').toLowerCase().includes(cat))
                && (!stk || tr.dataset.stock === stk);
        tr.style.display = ok ? '' : 'none';
    });
}
document.getElementById('buscador').addEventListener('input', filtrar);
document.getElementById('filtroCategoria').addEventListener('change', filtrar);
document.getElementById('filtroStock').addEventListener('change', filtrar);

function abrirDescontar(item) {
    document.getElementById('descIdInv').value  = item.id_inventario;
    document.getElementById('descNombre').value = item.nombre;
    document.getElementById('descActual').value = item.cantidad + ' ' + item.unidad;
    document.getElementById('descCantidad').value = '';
    document.getElementById('descCantidad').max = item.cantidad;
    new bootstrap.Modal(document.getElementById('modalDescontar')).show();
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
