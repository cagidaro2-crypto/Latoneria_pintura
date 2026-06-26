# Documentación: empleado_inventario.php

## Descripción general
Módulo del empleado para consultar y descontar stock del inventario. El empleado puede ver todos los productos activos agrupados por categoría, filtrar por nombre/categoría/estado de stock, y descontar unidades con un motivo. Los cambios son inmediatos y se reflejan en el inventario del administrador.

## Dependencias
- `config/database.php`, `layouts/header.php` / `footer.php`
- `controllers/AdminInventarioController.php` — acción `descontar` (compartida con admin)

## Flujo de ejecución
1. Verifica sesión y rol empleado (2)
2. Consulta productos activos con inventario y categoría
3. Filtra arrays para alertas de bajo stock y sin stock
4. Renderiza tabla con barras de stock visuales
5. Modal de descuento al hacer click en "Descontar"

## Código documentado por bloques

### Consulta de inventario (solo productos activos)
```php
$s = $db->query(
    "SELECT i.id_inventario, i.cantidad, i.unidad, i.stock_minimo, i.updated_at,
            p.id_producto, p.nombre, p.descripcion, p.precio, p.activo,
            cat.nombre AS categoria_nombre
     FROM inventario i
     JOIN productos p ON i.id_producto = p.id_producto
     LEFT JOIN categorias_productos cat ON p.id_categoria = cat.id_categoria
     WHERE p.activo = 1    -- Solo productos activos (no eliminados lógicamente)
     ORDER BY cat.nombre, p.nombre"
     -- Ordenar por categoría y luego por nombre facilita la lectura
);
```

### Cálculo de porcentaje para barra visual
```php
$pct = $min > 0 ? min(100, round($cant / $min * 100)) : 100;
// Si stock_minimo > 0: calcula qué % del mínimo tiene actualmente
// min(100, ...): máximo 100% aunque haya más del mínimo
// Si stock_minimo = 0: muestra 100% (no hay alerta posible)

$barClr = $cant <= 0    ? '#ef4444'    // Rojo: sin stock
        : ($cant <= $min ? '#f59e0b'   // Ámbar: bajo mínimo
        : '#22c55e');                   // Verde: OK
```

### Atributos data para filtros
```html
<tr data-cat="<?= $catN ?>"
    data-stock="<?= $cant<=0 ? 'sin' : ($cant<=$min ? 'bajo' : 'ok') ?>">
<!-- data-cat: nombre de la categoría para el filtro de categoría -->
<!-- data-stock: estado del stock simplificado en 3 valores: 'sin', 'bajo', 'ok' -->
```

### Función abrirDescontar (JS)
```javascript
function abrirDescontar(item) {
    // item: objeto JSON con {id_inventario, nombre, cantidad, unidad}
    document.getElementById('descIdInv').value  = item.id_inventario;
    document.getElementById('descNombre').value = item.nombre;
    document.getElementById('descActual').value = item.cantidad + ' ' + item.unidad;
    // Concatena cantidad y unidad para mostrar: "5 litros"

    document.getElementById('descCantidad').value = '';
    // Limpia el campo de cantidad para que el empleado ingrese desde cero

    document.getElementById('descCantidad').max = item.cantidad;
    // Limita el input al stock disponible (validación en el cliente)
    // El servidor también valida para prevenir descuentos mayores al stock

    new bootstrap.Modal(document.getElementById('modalDescontar')).show();
}
```

### Modal de descuento
```html
<form action="../../controllers/AdminInventarioController.php" method="POST">
    <input type="hidden" name="accion" value="descontar">
    <!-- Acción 'descontar' en el controlador compartido con admin -->

    <input type="hidden" name="id_inventario" id="descIdInv">
    <!-- ID del registro en tabla inventario -->

    <input type="number" name="cantidad_descontar" step="0.01" min="0.01" required>
    <!-- step="0.01": permite decimales (litros de pintura, metros de cinta, etc.) -->
    <!-- min="0.01": no permite descontar 0 o negativo -->

    <input type="text" name="motivo" maxlength="120">
    <!-- Motivo opcional: "Usado en orden #45", "Pintura capó Toyota Corolla" -->
</form>
```

### Sincronización con el admin
El controlador `AdminInventarioController.php` es **compartido** entre ambos roles:
- Admin tiene acceso a: agregar, actualizar, nueva_categoria, descontar
- Empleado solo puede usar: descontar
- La validación de rol en el controlador permite roles `[1, 2]`
- Después del descuento, el controlador detecta el rol y redirige a la vista correcta

## Variables principales

| Variable | Tipo | Descripción |
|---|---|---|
| `$items` | array | Productos activos con inventario |
| `$categorias` | array | Categorías para el filtro |
| `$bajoStock` | array | Productos con cantidad ≤ stock_mínimo |
| `$sinStock` | array | Productos con cantidad ≤ 0 |
