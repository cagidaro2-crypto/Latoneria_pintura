# Documentación: empleado_ventas.php

## Descripción general
Módulo del empleado para consultar las ventas (facturas) del taller. Muestra tarjetas resumen del mes (total ventas, ventas hoy, facturas, pagadas) y una tabla de las 30 facturas más recientes con buscador. Es vista de solo consulta para el empleado.

## Dependencias
- `config/database.php`, `layouts/header.php` / `footer.php`

## Flujo de ejecución
1. Verifica sesión y rol empleado (2)
2. Calcula 4 métricas del mes con queries individuales
3. Consulta las 30 facturas más recientes con datos de cliente y vehículo
4. Renderiza tarjetas resumen y tabla con buscador

## Código documentado por bloques

### Métricas del mes
```php
$ventasMes = (float)$db->query(
    "SELECT COALESCE(SUM(total),0) FROM facturas
     WHERE MONTH(fecha)=MONTH(CURDATE()) AND YEAR(fecha)=YEAR(CURDATE())"
)->fetchColumn();
// COALESCE(SUM(total),0): si no hay facturas, SUM retorna NULL → COALESCE lo convierte a 0
// MONTH(CURDATE()): función MySQL que retorna el número del mes actual (1-12)
// YEAR(CURDATE()): año actual para no mezclar con meses del año pasado

$ventasHoy = (float)$db->query(
    "SELECT COALESCE(SUM(total),0) FROM facturas WHERE fecha=CURDATE()"
)->fetchColumn();
// CURDATE() retorna la fecha actual 'YYYY-MM-DD' (sin hora)
// La columna 'fecha' en facturas también es DATE, por eso la comparación funciona
```

### Consulta de facturas recientes
```php
$s = $db->query(
    "SELECT f.id_factura, f.fecha, f.total, f.estado_pago,
            cl.nombres AS cliente_nombre,
            v.placa, v.marca, v.modelo,
            s.nombre AS servicio_nombre
     FROM facturas f
     JOIN cotizaciones c    ON f.id_cotizacion  = c.id_cotizacion
     -- Factura → Cotización (la factura se genera desde una cotización)
     JOIN vehiculos v       ON c.id_vehiculo    = v.id_vehiculo
     JOIN clientes cl       ON v.id_cliente     = cl.id_cliente
     LEFT JOIN cotizacion_servicios ds ON ds.id_cotizacion = c.id_cotizacion
     -- LEFT JOIN porque puede no haber servicios en la cotización
     LEFT JOIN servicios s  ON ds.id_servicio   = s.id_servicio
     ORDER BY f.fecha DESC, f.id_factura DESC
     LIMIT 30"
     -- Solo las 30 más recientes para no sobrecargar la tabla
);
```

### Tarjetas resumen dinámicas
```php
$stats = [
    ['icon'=>'fa-calendar','bg'=>'bg-primary','val'=>'$'.number_format($ventasMes,0,',','.'),'label'=>'Ventas del Mes'],
    // number_format(num, decimales, sep_decimal, sep_miles)
    // 0 decimales, ',' separador decimal, '.' separador de miles (formato colombiano)
    ...
];
foreach($stats as $st):
    // Itera el array para renderizar una tarjeta con valores dinámicos
    $color = explode('-', $st['bg'])[1];
    // explode('-', 'bg-primary') → ['bg', 'primary']
    // [1] toma 'primary' para usar en text-primary
```

### Badge de estado de pago
```php
$epB = match($ep) {
    'Pagada'  => 'bg-success',   // Verde: pago confirmado
    'Anulada' => 'bg-danger',    // Rojo: factura anulada
    default   => 'bg-warning text-dark'  // Amarillo: pendiente
};
```

### Buscador en tiempo real
```javascript
document.getElementById('buscador').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#tablaVentas tbody tr').forEach(tr => {
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
        // textContent incluye TODO el texto visible de la fila (nombre, placa, servicio, etc.)
        // Permite buscar por cualquier campo simultáneamente
    });
});
```

## Variables principales

| Variable | Tipo | Descripción |
|---|---|---|
| `$ventasMes` | float | Total facturado en el mes actual |
| `$ventasHoy` | float | Total facturado hoy |
| `$totalFact` | int | Número de facturas del mes |
| `$pagadas` | int | Número de facturas pagadas del mes |
| `$facturas` | array | Últimas 30 facturas con datos completos |
