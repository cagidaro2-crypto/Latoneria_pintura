# Documentación: cliente_cotizaciones.php

## Descripción general
Módulo del cliente para consultar sus cotizaciones. Muestra la lista de cotizaciones con su estado y total, y un panel lateral de detalle con servicios y repuestos incluidos. El cliente hace clic en "Ver Detalles" para cargar el detalle de una cotización específica.

## Dependencias
- `config/database.php`, `layouts/header.php` / `footer.php`, `cliente_styles.php`

## Flujo de ejecución
1. Verifica sesión y rol cliente (3)
2. Busca `id_cliente` por correo
3. Consulta cotizaciones del cliente (via `id_cliente` directo)
4. Si hay `?id=` en la URL, carga servicios y repuestos de esa cotización
5. Renderiza lista + panel de detalle

## Código documentado por bloques

### Consulta de cotizaciones
```php
$stmtCot = $db->prepare(
    "SELECT c.*,
            v.placa, v.marca, v.modelo,
            f.id_factura, f.estado_pago
     FROM cotizaciones c
     JOIN vehiculos v ON c.id_vehiculo = v.id_vehiculo
     -- JOIN porque toda cotización debe tener un vehículo
     LEFT JOIN facturas f ON f.id_cotizacion = c.id_cotizacion
     -- LEFT JOIN: la cotización puede no haber generado factura aún
     WHERE c.id_cliente = :id
     -- Directamente por id_cliente (el schema nuevo lo tiene en cotizaciones)
     ORDER BY c.fecha DESC"
);
```

### Badge de estado de cotización
```php
$tieneFactura = !empty($cot['id_factura']);
// Si id_factura no es NULL, la cotización ya tiene factura generada
$estadoPago   = $cot['estado_pago'] ?? null;

[$bc, $bl] = $tieneFactura
    ? ($estadoPago === 'Pagada'
        ? ['est-listo', 'Pagada']
        : ['est-espera', 'Pago pendiente'])
    : ['est-reparacion', 'Cotización'];
// Lógica de 3 estados:
// Sin factura → "Cotización" (azul)
// Con factura Pendiente → "Pago pendiente" (ámbar)
// Con factura Pagada → "Pagada" (verde)
```

### Detalle de cotización (parámetro URL)
```php
$idSel = (int)($_GET['id'] ?? 0);
// (int) convierte a entero para prevenir SQL injection
// ?? 0: si no hay parámetro, usa 0

if ($idSel && $idCliente) {
    $stmtS = $db->prepare(
        "SELECT c.*, v.placa, v.marca, v.modelo
         FROM cotizaciones c
         JOIN vehiculos v ON c.id_vehiculo = v.id_vehiculo
         WHERE c.id_cotizacion = :id AND c.id_cliente = :cli"
         // AND c.id_cliente = :cli: verifica que la cotización pertenece al cliente logueado
         // Previene que un cliente vea cotizaciones de otro
    );
```

### Detalles de servicios
```php
$stmtDS = $db->prepare(
    "SELECT cs.*, s.nombre AS servicio_nombre
     FROM cotizacion_servicios cs
     JOIN servicios s ON cs.id_servicio = s.id_servicio
     WHERE cs.id_cotizacion = :id"
);
// cs.*: incluye cantidad y precio de la tabla cotizacion_servicios
// s.nombre: nombre legible del servicio
```

### Renderizado del detalle
```php
foreach ($detallesServ as $ds):
    // Fila de tabla: nombre servicio, cantidad, precio unitario, subtotal
    $subtotal = $ds['precio'] * $ds['cantidad'];
    // Subtotal calculado en PHP (no viene de la BD)
```

### Panel vacío (cuando no hay cotización seleccionada)
```html
<?php else: ?>
<div class="text-center py-5">
    <i class="fas fa-mouse-pointer fa-2x mb-3 opacity-25"></i>
    <p>Selecciona una cotización para ver los detalles</p>
</div>
<?php endif; ?>
<!-- Se muestra cuando $cotSel es false (no hay id en la URL o el id no existe) -->
```

## Variables principales

| Variable | Tipo | Descripción |
|---|---|---|
| `$idCliente` | int | ID del cliente |
| `$cotizaciones` | array | Lista de cotizaciones del cliente |
| `$idSel` | int | ID de cotización seleccionada (de URL) |
| `$cotSel` | array\|false | Datos de la cotización seleccionada |
| `$detallesServ` | array | Servicios de la cotización seleccionada |
| `$detallesRep` | array | Repuestos de la cotización seleccionada |
