# Documentación: admin_facturas.php

## Descripción general
Módulo de facturación para el administrador. Permite visualizar, filtrar y gestionar facturas. Muestra resúmenes de ventas del mes y permite cambiar el estado de pago (Pendiente → Pagada → Anulada).

## Dependencias
- `config/database.php` — conexión PDO
- `layouts/header.php` / `footer.php` — layout
- `controllers/FacturaController.php` — procesa cambios de estado

## Flujo de ejecución
1. Verifica sesión y rol administrador
2. Consulta facturas con JOIN a cotizaciones, vehículos y clientes
3. Calcula resumen del mes (total facturado, pagadas, pendientes)
4. Renderiza tabla con filtros y acciones

## Código documentado por bloques

### Consulta principal de facturas
```php
$stmtF = $db->query(
    "SELECT f.id_factura, f.fecha, f.total, f.estado_pago,
            cl.nombres AS cliente_nombre,
            v.placa, v.marca, v.modelo
     FROM facturas f
     JOIN cotizaciones c  ON f.id_cotizacion = c.id_cotizacion
     -- La factura nace de una cotización aprobada
     JOIN vehiculos v     ON c.id_vehiculo   = v.id_vehiculo
     -- El vehículo al que pertenece la cotización
     JOIN clientes cl     ON v.id_cliente    = cl.id_cliente
     -- El cliente dueño del vehículo
     ORDER BY f.fecha DESC, f.id_factura DESC"
     -- Más reciente primero
);
```

### Badge de estado de pago
```php
$epBadge = match($ep) {
    'Pagada'    => 'bg-success',   // Verde: pago confirmado
    'Anulada'   => 'bg-danger',    // Rojo: factura anulada
    default     => 'bg-warning text-dark'  // Amarillo: pendiente de pago
};
// match() es más limpio que switch() y lanza error si ningún caso coincide sin default
```

### Resumen del mes
```php
$ventasMes = $db->query(
    "SELECT COALESCE(SUM(total),0) FROM facturas
     WHERE MONTH(fecha)=MONTH(CURDATE()) AND YEAR(fecha)=YEAR(CURDATE())"
)->fetchColumn();
// COALESCE(SUM(total),0): si no hay facturas, SUM retorna NULL; COALESCE lo convierte a 0
// MONTH(CURDATE()): mes actual. YEAR(CURDATE()): año actual
```

### Filtro de estado
```javascript
document.getElementById('filtroEstado').addEventListener('change', function() {
    const estado = this.value;
    document.querySelectorAll('#tablaFacturas tbody tr').forEach(tr => {
        tr.style.display = !estado || tr.dataset.estado === estado ? '' : 'none';
        // !estado: si no hay filtro seleccionado, muestra todo
        // tr.dataset.estado: lee el atributo data-estado del <tr>
    });
});
```

## Variables principales

| Variable | Tipo | Descripción |
|---|---|---|
| `$facturas` | array | Lista de facturas con datos del cliente y vehículo |
| `$ventasMes` | float | Total facturado en el mes actual |
| `$pagadas` | int | Número de facturas con estado 'Pagada' |
| `$pendientes` | int | Número de facturas con estado 'Pendiente' |
