# Documentación: empleado_ordenes.php

## Descripción general
Módulo del empleado para ver y gestionar órdenes de servicio. Muestra el historial de trabajos realizados (tabla `historial_vehiculo`) con filtros por estado y buscador. Permite cambiar el estado del vehículo asociado a cada orden.

## Dependencias
- `config/database.php`, `layouts/header.php` / `footer.php`
- `controllers/VehiculoController.php` — cambiar estado

## Flujo de ejecución
1. Verifica sesión y rol empleado (2)
2. Consulta historial con JOIN a vehículos, clientes, usuarios y estado
3. Consulta estados disponibles para el filtro y el modal
4. Renderiza tabla con filtros y modal de cambio de estado

## Código documentado por bloques

### Consulta del historial como órdenes
```php
$s = $db->query(
    "SELECT hv.id_historial AS id_orden,
            -- Alias: el historial se trata como una 'orden' en esta vista
            hv.descripcion, hv.fecha_registro, hv.tipo_reparacion,
            v.id_vehiculo,
            -- Necesario para el modal de cambio de estado
            v.placa, v.marca, v.modelo,
            cl.nombres AS cliente_nombre,
            CONCAT(u.nombres,' ',COALESCE(u.apellidos,'')) AS empleado_nombre,
            ev.estado
     FROM historial_vehiculo hv
     JOIN vehiculos v      ON hv.id_vehiculo = v.id_vehiculo
     JOIN clientes cl      ON v.id_cliente   = cl.id_cliente
     LEFT JOIN usuarios u  ON hv.id_usuario  = u.id_usuario
     -- LEFT JOIN porque id_usuario puede ser NULL
     JOIN estado_vehiculo ev ON v.id_estado   = ev.id_estado_vehiculo
     ORDER BY hv.fecha_registro DESC"
);
```

### Filtros visuales de estado
```html
<button class="btn filter-btn active" data-filtro="todos">Todos</button>
<?php foreach ($estados as $e): ?>
<button class="btn filter-btn" data-filtro="<?= htmlspecialchars($e['estado']) ?>">
    <?= htmlspecialchars($e['estado']) ?>
</button>
<?php endforeach; ?>
<!-- Genera un botón por cada estado. data-filtro se usa en JS para comparar con data-estado de las filas -->
```

### Función cambiarEstado (JS)
```javascript
function cambiarEstado(idVehiculo, placa) {
    document.getElementById('estadoIdVehiculo').value = idVehiculo;
    // Pone el id del vehículo en el campo oculto del formulario

    document.getElementById('estadoPlaca').textContent = placa;
    // Muestra la placa en el modal para confirmación visual

    new bootstrap.Modal(document.getElementById('modalEstado')).show();
    // Abre el modal programáticamente
}
```

### Modal cambiar estado
```html
<form action="../../controllers/VehiculoController.php" method="POST">
    <input type="hidden" name="accion" value="cambiar_estado">
    <input type="hidden" name="id_vehiculo" id="estadoIdVehiculo">
    <!-- Poblado por cambiarEstado() -->

    <select name="id_estado_vehiculo" id="estadoSelect">
        <?php foreach($estados as $e): ?>
        <option value="<?= $e['id_estado_vehiculo'] ?>">
            <?= htmlspecialchars($e['estado']) ?>
        </option>
        <?php endforeach; ?>
    </select>
</form>
```

## Resumen de variables

| Variable | Tipo | Descripción |
|---|---|---|
| `$ordenes` | array | Registros del historial tratados como órdenes |
| `$estados` | array | Estados de vehículo para filtros y select |
