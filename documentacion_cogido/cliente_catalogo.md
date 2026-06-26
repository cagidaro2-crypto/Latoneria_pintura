# Documentación: cliente_catalogo.php

## Descripción general
Módulo del cliente para explorar el catálogo de productos disponibles en el taller (pinturas, repuestos, materiales). El cliente puede filtrar por nombre, categoría o estado de stock. Es una vista de solo lectura — el cliente no puede modificar nada.

## Dependencias
- `config/database.php`, `layouts/header.php` / `footer.php`, `cliente_styles.php`

## Flujo de ejecución
1. Verifica sesión y rol cliente (3)
2. Consulta categorías para los chips de filtro
3. Lee parámetros de URL: `?q=` (búsqueda) y `?categoria=` (filtro)
4. Construye query dinámica según los filtros activos
5. Agrupa productos por categoría
6. Renderiza filtros, separadores de categoría y grilla de tarjetas

## Código documentado por bloques

### Lectura de parámetros de filtro
```php
$catSel = (int)($_GET['categoria'] ?? 0);
// Lee el id de categoría de la URL
// (int) asegura que sea número (previene inyección)
// ?? 0: sin filtro de categoría por defecto

$q = trim($_GET['q'] ?? '');
// Texto de búsqueda libre
// trim() elimina espacios al inicio/fin
```

### Query dinámica con filtros opcionales
```php
$sql = "SELECT p.id_producto, p.nombre, p.descripcion, p.precio,
               cat.nombre AS cat_nombre, i.cantidad, i.unidad
        FROM productos p
        LEFT JOIN categorias_productos cat ON p.id_categoria = cat.id_categoria
        LEFT JOIN inventario i ON i.id_producto = p.id_producto
        WHERE p.activo = 1";
// Base de la query con solo productos activos

$params = [];

if ($catSel > 0) {
    $sql .= " AND p.id_categoria = :cat";
    $params[':cat'] = $catSel;
    // Solo agrega el filtro si hay categoría seleccionada
}

if ($q !== '') {
    $sql .= " AND (p.nombre LIKE :q OR p.descripcion LIKE :q2)";
    $params[':q']  = "%{$q}%";
    $params[':q2'] = "%{$q}%";
    // LIKE con % en ambos extremos busca la cadena en cualquier posición
    // Se usa dos parámetros distintos (:q y :q2) porque PDO no permite reutilizar el mismo nombre
}

$sql .= " ORDER BY cat.nombre, p.nombre";
// Siempre ordenar para agrupar por categoría
```

### Agrupación por categoría
```php
$porCat = [];
foreach ($productos as $p) {
    $cat = $p['cat_nombre'] ?? 'Sin categoría';
    $porCat[$cat][] = $p;
    // Construye array asociativo: clave = nombre categoría, valor = array de productos
    // Permite renderizar un bloque por categoría con separador visual
}
```

### Chips de filtro rápido
```html
<a href="cliente_catalogo.php" class="cs-chip <?= $catSel===0 && $q==='' ? 'active' : '' ?>">
    Todas
</a>
<!-- Activo solo si no hay ningún filtro aplicado -->

<?php foreach ($categorias as $c): ?>
<a href="?categoria=<?= $c['id_categoria'] ?>"
   class="cs-chip <?= $catSel === (int)$c['id_categoria'] ? 'active' : '' ?>">
    <?= htmlspecialchars($c['nombre']) ?>
</a>
<!-- Cada chip es un enlace que recarga la página con el filtro en la URL -->
<!-- Clase 'active' si ese chip está seleccionado actualmente -->
<?php endforeach; ?>
```

### Separador de categoría
```html
<div class="cs-sep" style="margin-top:<?= $catN===array_key_first($porCat)?'0':'1.8rem' ?>;">
    <span class="label"><?= htmlspecialchars($catN) ?></span>
    <span class="line"></span>
    <span class="count"><?= count($items) ?> ítem<?= count($items)!==1?'s':'' ?></span>
</div>
<!-- array_key_first() obtiene la primera clave del array (primera categoría) -->
<!-- No agrega margen superior a la primera categoría, pero sí a las siguientes -->
<!-- Plural condicional: "1 ítem" vs "3 ítems" -->
```

### Badge de stock
```php
[$sc, $sl] = $qty <= 0
    ? ['cs-stock-out', 'Sin stock']
    : ($qty <= 5
        ? ['cs-stock-low', 'Pocas unidades']
        : ['cs-stock-ok',  'Disponible']);
// Asignación de array destructurado: $sc = clase CSS, $sl = texto del badge
// 3 niveles: Sin stock (rojo), Pocas unidades (<= 5, ámbar), Disponible (verde)
```

### Tarjeta de producto
```html
<div class="cs-product-icon">
    <i class="fas <?= $ico ?>"></i>
    <!-- $ico se determina por el nombre de la categoría:
         'pintura' → fa-fill-drip
         'latonería' → fa-hammer
         'repuest' → fa-screwdriver-wrench
         default → fa-box
    -->
</div>
<div class="cs-product-name"><?= htmlspecialchars($p['nombre']) ?></div>
<div class="cs-product-price">
    $<?= number_format((float)$p['precio'], 0, ',', '.') ?>
    <span>/ <?= htmlspecialchars($p['unidad'] ?? 'unidad') ?></span>
</div>
```

## Variables principales

| Variable | Tipo | Descripción |
|---|---|---|
| `$categorias` | array | Todas las categorías para chips y select |
| `$catSel` | int | ID de categoría seleccionada (0 = todas) |
| `$q` | string | Texto de búsqueda libre |
| `$productos` | array | Productos filtrados por la query dinámica |
| `$porCat` | array | Productos agrupados por nombre de categoría |
