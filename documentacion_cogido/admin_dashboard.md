# Documentación: admin_dashboard.php

## Descripción general
Panel principal del administrador. Muestra métricas en tiempo real del taller: vehículos en proceso, ventas del mes, órdenes activas, productos bajo stock, estructura de usuarios por rol, gráficas de ventas/órdenes y listas de vehículos recientes e inventario crítico. Se actualiza automáticamente cada 30 segundos via `fetch()`.

## Dependencias
- `config/database.php` — conexión PDO
- `layouts/header.php` / `footer.php` — layout compartido
- `shared_styles.php` — estilos base (incluido por header)
- `controllers/DashboardDataController.php` — endpoint AJAX para actualizaciones
- `Chart.js` — biblioteca para gráficas de línea y barras

## Flujo de ejecución
1. Verifica sesión y rol administrador (1)
2. Ejecuta 10+ queries para poblar las tarjetas y gráficas
3. Todas las queries van en `try/catch` para evitar errores fatales
4. Incluye `header.php` y `shared_styles.php`
5. Renderiza el dashboard con los datos obtenidos
6. Instancia Chart.js con los datos PHP embebidos como JSON
7. Inicia `setInterval` para actualizar cada 30 segundos

## Código documentado por bloques

### Guard de acceso
```php
if (!isset($_SESSION['usuario']) || (int)($_SESSION['usuario']['rol'] ?? 0) !== 1) {
    header("Location: ../usuarios/login.php"); exit;
}
// Solo el administrador (rol 1) puede acceder
```

### Tarjeta vehículos en proceso
```php
$vehiculosEnProceso = (int)$db->query(
    "SELECT COUNT(*) FROM vehiculos v
     JOIN estado_vehiculo ev ON v.id_estado = ev.id_estado_vehiculo
     WHERE ev.estado IN ('En reparación','En espera')"
)->fetchColumn();
// fetchColumn() retorna directamente el primer campo de la primera fila (el COUNT)
// Filtra solo los estados de trabajo activo
```

### Comparativa ventas mes anterior
```php
$pctVentas = $ventasMesAnt > 0
    ? round((($ventasMes - $ventasMesAnt) / $ventasMesAnt) * 100, 1)
    : 0;
// Fórmula: ((nuevo - anterior) / anterior) × 100
// round(..., 1) redondea a 1 decimal
// Si el mes anterior fue 0, evita división por cero con el ternario
```

### Panel de roles con barras de progreso
```php
$pctA = $totalUsuarios > 0 ? round($totalAdmins / $totalUsuarios * 100) : 0;
// Porcentaje de administradores sobre el total de usuarios
// round() sin segundo argumento redondea al entero más cercano
```

```html
<div style="height:5px; width:<?= $r['pct'] ?>%; background:<?= $r['bar'] ?>;">
<!-- Barra inline: ancho dinámico según porcentaje calculado -->
<!-- background: color específico por rol (rojo, ámbar, verde) -->
```

### Gráficas con Chart.js
```javascript
const initVentas = <?= json_encode(array_values($grafVentas)) ?>;
// json_encode convierte el array PHP a JSON válido para JavaScript
// array_values() re-indexa el array para evitar objetos en vez de arrays en JS

const salesChart = new Chart(document.getElementById('salesChart'), {
    type: 'line',       // Tipo de gráfica: línea
    data: {
        labels: initVentas.map(r => r.mes),
        // map() extrae solo el campo 'mes' de cada elemento para las etiquetas del eje X
        datasets: [{
            data: initVentas.map(r => parseFloat(r.total)),
            // parseFloat() convierte strings de la BD a números decimales
        }]
    }
});
```

### Actualización en tiempo real
```javascript
setInterval(() => {
    fetch('../../controllers/DashboardDataController.php')
    // fetch() hace una petición HTTP GET asíncrona al controlador
    .then(r => r.json())
    // .json() parsea la respuesta como JSON
    .then(data => {
        if (data.error) return;
        // Si el servidor retornó error, no actualiza la UI

        document.getElementById('stat-vehiculos').textContent = t.vehiculos_proceso;
        // Actualiza el DOM directamente sin recargar la página

        salesChart.data.datasets[0].data = data.grafica_ventas.map(...);
        salesChart.update('none');
        // Actualiza la gráfica sin animación ('none') para transición suave
    })
    .catch(() => {});
    // catch vacío: falla silenciosa para no interrumpir al usuario
}, 30000);
// 30000ms = 30 segundos entre cada actualización
```

### Lista de inventario bajo stock
```php
$inventarioBajo = $db->query(
    "SELECT p.nombre, i.cantidad, i.stock_minimo, i.unidad, cat.nombre AS categoria_nombre
     FROM inventario i
     JOIN productos p ON i.id_producto = p.id_producto
     LEFT JOIN categorias_productos cat ON p.id_categoria = cat.id_categoria
     WHERE i.cantidad <= i.stock_minimo     -- Solo productos en alerta
     ORDER BY (i.cantidad / NULLIF(i.stock_minimo,0)) ASC  -- Los más críticos primero
     LIMIT 5"                               -- Muestra los 5 más urgentes
);
// NULLIF(stock_minimo, 0) evita división por cero si stock_minimo es 0
```

## Resumen de variables de datos

| Variable | Consulta | Descripción |
|---|---|---|
| `$vehiculosEnProceso` | COUNT vehiculos en espera/reparación | Tarjeta 1 |
| `$ventasMes` | SUM facturas mes actual | Tarjeta 2 |
| `$pctVentas` | Calculado | % vs mes anterior |
| `$ordenesActivas` | COUNT ordenes_trabajo del mes | Tarjeta 3 |
| `$bajoStock` | COUNT inventario bajo mínimo | Tarjeta 4 |
| `$grafVentas` | GROUP BY mes últimos 6 meses | Gráfica de línea |
| `$grafOrdenes` | GROUP BY día últimos 7 días | Gráfica de barras |
| `$vehiculosRecientes` | 5 vehículos más recientes | Lista |
| `$inventarioBajo` | 5 productos más críticos | Lista |
