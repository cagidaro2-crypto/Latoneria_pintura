# Documentación: empleado_dashboard.php

## Descripción general
Panel principal del empleado. Muestra 3 tarjetas resumen (total órdenes, vehículos en reparación, pendientes) y una tabla con las 8 órdenes de trabajo más recientes. Sirve como punto de entrada al panel del empleado.

## Dependencias
- `config/database.php`, `layouts/header.php` / `footer.php`

## Flujo de ejecución
1. Verifica sesión y rol empleado (2)
2. Cuenta total de órdenes de trabajo
3. Agrupa vehículos por estado para los contadores
4. Consulta las últimas 8 órdenes de trabajo con datos completos
5. Renderiza tarjetas y tabla

## Código documentado por bloques

### Contador de órdenes
```php
try {
    $totalOrdenes = (int)$db->query("SELECT COUNT(*) FROM ordenes_trabajo")->fetchColumn();
} catch (Exception $e) {}
// Cuenta todas las órdenes en el sistema (no solo del empleado)
// try/catch: si la tabla no existe, no rompe la página
```

### Contadores por estado
```php
$stmtEst = $db->query(
    "SELECT ev.estado, COUNT(*) AS total
     FROM vehiculos v
     JOIN estado_vehiculo ev ON v.id_estado = ev.id_estado_vehiculo
     GROUP BY ev.estado"
    // GROUP BY: agrupa las filas por estado y cuenta cuántos vehículos hay en cada uno
);
foreach ($stmtEst->fetchAll(PDO::FETCH_ASSOC) as $row) {
    if (stripos($row['estado'], 'reparaci') !== false) $enReparacion += (int)$row['total'];
    // stripos() busca 'reparaci' en el nombre del estado (funciona con 'En reparación')
    // += acumula por si hay múltiples estados que contengan 'reparaci'
}
```

### Consulta de órdenes recientes
```php
$stmtOrd = $db->query(
    "SELECT ot.id_orden,
            ot.descripcion_danos AS descripcion,
            -- AS descripcion: renombra la columna para usarla igual que el historial
            ot.fecha_ingreso     AS fecha_registro,
            'Orden de trabajo'   AS tipo_reparacion,
            -- Literal string como columna (no viene de la BD)
            v.placa, v.marca,
            CONCAT(c.nombres,' ',COALESCE(c.apellidos,'')) AS cliente_nombre,
            -- CONCAT: une nombres y apellidos en una sola cadena
            -- COALESCE: si apellidos es NULL, usa string vacío
            ev.estado
     FROM ordenes_trabajo ot
     JOIN vehiculos v       ON ot.id_vehiculo = v.id_vehiculo
     JOIN clientes c        ON ot.id_cliente  = c.id_cliente
     JOIN estado_vehiculo ev ON v.id_estado    = ev.id_estado_vehiculo
     ORDER BY ot.fecha_ingreso DESC
     LIMIT 8"  // Solo las 8 más recientes
);
```

### Badge de estado dinámico
```php
$badge = match(true) {
    stripos($estado, 'pendiente') !== false => 'bg-warning text-dark',
    stripos($estado, 'reparaci')  !== false => 'bg-info text-dark',
    stripos($estado, 'finalizado')!== false => 'bg-success',
    default                                 => 'bg-secondary',
};
// match(true) evalúa cada condición en orden, retorna el primer match
// Esto permite condiciones complejas (no solo igualdad) en el match
```

## Variables principales

| Variable | Tipo | Descripción |
|---|---|---|
| `$totalOrdenes` | int | Total de órdenes en el sistema |
| `$enReparacion` | int | Vehículos en estado de reparación |
| `$pendientes` | int | Vehículos en espera |
| `$ordenes` | array | Últimas 8 órdenes de trabajo |
