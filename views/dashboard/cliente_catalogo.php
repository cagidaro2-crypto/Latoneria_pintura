<?php
$titulo = 'Catálogo de Productos';
require_once __DIR__ . '/../../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario']) || (int)($_SESSION['usuario']['rol']??0) !== 3) { header("Location: ../usuarios/login.php"); exit; }

$db=(new Database())->conectar();
$categorias=[];
try{$categorias=$db->query("SELECT id_categoria,nombre FROM categorias_productos ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);}catch(Exception $e){}

$catSel=(int)($_GET['categoria']??0); $q=trim($_GET['q']??'');
$productos=[];
try{
    $sql="SELECT p.id_producto,p.nombre,p.descripcion,p.precio,cat.nombre AS cat_nombre,i.cantidad,i.unidad FROM productos p LEFT JOIN categorias_productos cat ON p.id_categoria=cat.id_categoria LEFT JOIN inventario i ON i.id_producto=p.id_producto WHERE p.activo=1";
    $params=[];
    if($catSel>0){$sql.=" AND p.id_categoria=:cat";$params[':cat']=$catSel;}
    if($q!==''){$sql.=" AND (p.nombre LIKE :q OR p.descripcion LIKE :q2)";$params[':q']="%$q%";$params[':q2']="%$q%";}
    $sql.=" ORDER BY cat.nombre,p.nombre";
    $s=$db->prepare($sql);$s->execute($params);$productos=$s->fetchAll(PDO::FETCH_ASSOC);
}catch(Exception $e){}

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/cliente_styles.php';
?>

<!-- Encabezado -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <div class="cs-title"><i class="fas fa-boxes-stacked me-2 cs-icon-blue"></i>Catálogo de Productos</div>
        <div class="cs-sub">Consulta los productos y repuestos disponibles en el taller</div>
    </div>
    <span style="background:#fff;border:1px solid #e2e8f0;border-radius:20px;padding:.3rem .9rem;font-size:.78rem;color:#64748b;">
        <?= count($productos) ?> producto<?= count($productos)!==1?'s':'' ?>
    </span>
</div>

<!-- Filtros -->
<div class="cs-filter-bar">
    <form method="GET">
        <div class="row g-3 align-items-end">
            <div class="col-md-5">
                <label style="font-size:.72rem;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:.35rem;">Buscar</label>
                <div style="position:relative;">
                    <i class="fas fa-search" style="position:absolute;left:.9rem;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.82rem;"></i>
                    <input type="text" name="q" class="form-control" value="<?= htmlspecialchars($q) ?>" placeholder="Nombre o descripción…" style="padding-left:2.2rem;">
                </div>
            </div>
            <div class="col-md-4">
                <label style="font-size:.72rem;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:.35rem;">Categoría</label>
                <select name="categoria" class="form-select" onchange="this.form.submit()">
                    <option value="0">Todas las categorías</option>
                    <?php foreach($categorias as $c): ?><option value="<?= $c['id_categoria'] ?>" <?= $catSel===(int)$c['id_categoria']?'selected':'' ?>><?= htmlspecialchars($c['nombre']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="cs-btn-orange flex-fill"><i class="fas fa-filter"></i> Filtrar</button>
                <a href="cliente_catalogo.php" class="btn btn-sm btn-outline-secondary flex-fill"><i class="fas fa-rotate-right"></i> Limpiar</a>
            </div>
        </div>
    </form>
    <?php if(!empty($categorias)): ?>
    <div class="d-flex gap-2 flex-wrap mt-3" style="border-top:1px solid #e2e8f0;padding-top:.9rem;">
        <a href="cliente_catalogo.php" class="cs-chip <?= $catSel===0&&$q===''?'active':'' ?>"><i class="fas fa-th me-1"></i> Todas</a>
        <?php foreach($categorias as $c): ?>
        <a href="?categoria=<?= $c['id_categoria'] ?>" class="cs-chip <?= $catSel===(int)$c['id_categoria']?'active':'' ?>"><?= htmlspecialchars($c['nombre']) ?></a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Grid productos -->
<?php if(empty($productos)): ?>
<div class="cs-empty">
    <i class="fas fa-box-open fa-3x cs-empty-icon"></i>
    <h6 class="cs-empty-title">Sin productos encontrados</h6>
    <p class="cs-empty-sub"><?= ($q||$catSel)?'Intenta con otros filtros.':'El catálogo no tiene productos activos aún.' ?></p>
    <?php if($q||$catSel): ?><a href="cliente_catalogo.php" class="cs-btn-dark" style="font-size:.82rem;"><i class="fas fa-arrow-left me-1"></i> Ver todos</a><?php endif; ?>
</div>
<?php else:
    $porCat=[];
    foreach($productos as $p){$cat=$p['cat_nombre']??'Sin categoría';$porCat[$cat][]=$p;}
?>
<?php foreach($porCat as $catN=>$items): ?>
<div class="cs-sep" style="margin-top:<?= $catN===array_key_first($porCat)?'0':'1.8rem' ?>;">
    <span class="label"><i class="fas fa-tag me-1"></i><?= htmlspecialchars($catN) ?></span>
    <span class="line"></span>
    <span class="count"><?= count($items) ?> ítem<?= count($items)!==1?'s':'' ?></span>
</div>
<div class="row g-3 mb-2">
<?php foreach($items as $p):
    $qty=(float)($p['cantidad']??0);
    [$sc,$sl]=$qty<=0?['cs-stock-out','Sin stock']:($qty<=5?['cs-stock-low','Pocas unidades']:['cs-stock-ok','Disponible']);
    $ico=match(true){stripos($catN,'pintura')!==false=>'fa-fill-drip',stripos($catN,'latonería')!==false=>'fa-hammer',stripos($catN,'repuest')!==false=>'fa-screwdriver-wrench',stripos($catN,'lubric')!==false=>'fa-oil-can',stripos($catN,'eléctr')!==false=>'fa-bolt',default=>'fa-box'};
?>
<div class="col-sm-6 col-lg-4 col-xl-3">
    <div class="cs-product-card">
        <div class="cs-product-icon"><i class="fas <?= $ico ?>"></i></div>
        <div class="cs-product-body">
            <div class="cs-product-cat"><?= htmlspecialchars($catN) ?></div>
            <div class="cs-product-name"><?= htmlspecialchars($p['nombre']) ?></div>
            <div class="cs-product-desc"><?= htmlspecialchars($p['descripcion']?:'Sin descripción.') ?></div>
        </div>
        <div class="cs-product-foot">
            <div class="cs-product-price">$<?= number_format((float)$p['precio'],0,',','.') ?> <span style="font-size:.7rem;color:#94a3b8;font-weight:400;">/ <?= htmlspecialchars($p['unidad']??'unidad') ?></span></div>
            <span class="cs-stock <?= $sc ?>"><?= $sl ?></span>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
