# Documentación: admin_inventario.php

## Descripción general
Módulo completo de inventario para el administrador. Permite gestionar productos (pinturas, repuestos, materiales), categorías y stock. Muestra alertas automáticas de productos bajo mínimo o sin stock. Los cambios se sincronizan con el módulo de inventario del empleado.

## Dependencias
- `config/database.php` — conexión PDO
- `layouts/header.php` / `footer.php` — layout
- `controllers/AdminInventarioController.php` — procesa todas las acciones

## Flujo de ejecución
1. Verifica sesión y rol administrador
2. Consulta productos con inventario, categoría (JOIN) y proveedor
3. Consulta lista de categorías y proveedores para los selects
4. Filtra arrays para detectar bajo stock y sin stock
5. Renderiza tabla agrupada, modales y alertas

## Código documentado por bloques

### Consulta principal
```php
$s = $db->query(
    "SELECT i.id_inventario, i.cantidad, i.unidad, i.stock_minimo, i.updated_at,
            p.id_producto, p.nombre, p.descripcion, p.precio, p.activo,
            cat.nombre AS categoria_nombre
     FROM inventario i
     JOIN productos p ON i.id_producto = p.id_producto
     -- Cada registro de inventario corresponde a exactamente un producto (relación 1:1)
     LEFT JOIN categorias_productos cat ON p.id_categoria = cat.id_categoria
     -- LEFT JOIN porque la categoría puede ser NULL
     ORDER BY cat.nombre, p.nombre"
     -- Agrupa visualmente por categoría al ordenar
);
```

### Detección de alertas
```php
$bajoStock = array_filter($items, fn($i) => (float)$i['cantidad'] <= (float)$i['stock_minimo']);
// array_filter() retorna solo los elementos donde la función anónima retorna true
// fn($i) => es la sintaxis de arrow function de PHP 7.4+
// (float) castea a decimal para comparaciones correctas

$sinStock = array_filter($items, fn($i) => (float)$i['cantidad'] <= 0);
// Subset más crítico: productos completamente agotados
```

### Barra de stock visual
```php
$pct = $min > 0 ? min(100, round($cant / $min * 100)) : 100;
// Calcula qué porcentaje del mínimo representa la cantidad actual
// min(100, ...) evita que supere 100% (hay más stock del mínimo)
// Si stock_minimo es 0, muestra 100% para no generar alerta

$barClr = $cant <= 0 ? '#ef4444'           // Rojo: sin stock
        : ($cant <= $min ? '#f59e0b'        // Ámbar: bajo mínimo
        : '#22c55e');                        // Verde: OK
```

### Modal agregar producto
```html
<select name="id_categoria" class="form-select" required>
    <?php foreach ($categorias as $c): ?>
    <option value="<?= $c['id_categoria'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
    <?php endforeach; ?>
    <!-- Usa id_categoria (FK real) como value, no el nombre -->
    <!-- htmlspecialchars() previene XSS en nombres de categoría -->
</select>
```

### Función editarItem (JS)
```javascript
function editarItem(item) {
    // item es un objeto JSON generado con json_encode() en PHP

    document.getElementById('editIdInv').value       = item.id_inventario;
    // ID del registro de inventario para el UPDATE

    document.getElementById('editCatInv').value      = item.categoria_nombre ?? '—';
    // ?? '—' muestra guión si la categoría es null

    document.getElementById('descCantidad').max      = item.cantidad;
    // Limita el input a no descontar más del stock disponible
}
```

## Resumen de acciones del controlador

| Acción | Descripción |
|---|---|
| `nueva_categoria` | Inserta en `categorias_productos` |
| `agregar` | INSERT en `productos` + INSERT en `inventario` (transacción) |
| `actualizar` | UPDATE `inventario` (cantidad, stock_min) + UPDATE `productos` (precio) |
| `descontar` | Resta cantidad: `UPDATE inventario SET cantidad = cantidad - :cant` |

## Variables principales

| Variable | Tipo | Descripción |
|---|---|---|
| `$items` | array | Todos los productos con su inventario y categoría |
| `$categorias` | array | Categorías para los selects |
| `$proveedores` | array | Proveedores para el select |
| `$bajoStock` | array | Productos con cantidad ≤ stock_mínimo |
| `$sinStock` | array | Productos con cantidad ≤ 0 |
