# Documentación: cliente_ordenes.php

## Descripción general
Vista del cliente para hacer seguimiento del estado de sus vehículos en el taller. Muestra una tarjeta por cada vehículo con hitos visuales de progreso (Ingresado → En reparación → Pintura → Finalizado), el último registro del historial y el historial completo colapsable.

## Dependencias
- `config/database.php` — conexión PDO
- `layouts/header.php` y `footer.php` — layout
- `cliente_styles.php` — estilos compartidos

## Flujo de ejecución
1. Verifica sesión y rol cliente (3)
2. Busca `id_cliente` en tabla `clientes` por correo
3. Consulta todos los vehículos del cliente con su estado actual
4. Por cada vehículo, carga su historial completo
5. Renderiza tarjetas con hitos, barra de progreso, último registro e historial

## Código documentado por bloques

### Consulta de historial por vehículo
```php
$sh = $db->prepare(
    "SELECT hv.*, u.nombres AS empleado_nombre
     FROM historial_vehiculo hv
     LEFT JOIN usuarios u ON hv.id_usuario = u.id_usuario
     WHERE hv.id_vehiculo = :id ORDER BY hv.fecha_registro DESC"
);
// LEFT JOIN usuarios: permite obtener el nombre del empleado que hizo el trabajo
// LEFT JOIN (no JOIN normal) porque hv.id_usuario puede ser NULL
// ORDER BY DESC: el registro más reciente primero
```

### Configuración de hitos
```php
$hitosConfig = [
    ['label'=>'Ingresado',     'match'=>'espera',   'pct'=>15],
    ['label'=>'En reparación', 'match'=>'reparaci',  'pct'=>45],
    ['label'=>'Pintura',       'match'=>'pintura',   'pct'=>75],
    ['label'=>'Finalizado',    'match'=>'listo',     'pct'=>100],
];
// Cada hito tiene:
// label: texto visible bajo el punto
// match: substring para detectar el estado actual con stripos()
// pct: porcentaje de progreso cuando el vehículo está en ese estado
```

### Renderizado de hitos
```php
foreach($hitosConfig as $i => $h):
    $done   = $progreso >= $h['pct'];
    // done: el progreso actual supera o iguala el porcentaje de este hito (ya pasó)

    $active = !$done && ($i===0 || $progreso >= $hitosConfig[$i-1]['pct']);
    // active: no está completado PERO el hito anterior sí (es el hito actual)
    // $i===0: el primer hito siempre es activo si el progreso > 0
?>
<div class="cs-hito-dot <?= $done?'done':($active?'active':'') ?>">
<!-- dot.done = verde (completado), dot.active = negro con glow (actual), sin clase = gris (futuro) -->
```

### Bloque "Último registro"
```php
<?php if($ultimo): ?>
<div class="cs-last-reg">
    <div class="cs-reg-tipo"><?= htmlspecialchars($ultimo['tipo_reparacion']??'') ?></div>
    <!-- ?? '' evita notice si la clave no existe en el array -->

    <div class="cs-reg-meta">
        <?= date('d/m/Y', strtotime($ultimo['fecha_registro'])) ?>
        <!-- date() formatea la fecha al estilo colombiano: dd/mm/YYYY -->
        <!-- strtotime() convierte la fecha string de la BD a timestamp Unix -->
    </div>
</div>
```

### Historial colapsable con timeline
```html
<button class="cs-btn-hist" data-bs-toggle="collapse" data-bs-target="#<?= $colId ?>">
    <!-- $colId = 'hist-' . $v['id_vehiculo'] → ID único por vehículo -->

<div class="collapse" id="<?= $colId ?>">
    <div class="cs-tl">
        <!-- cs-tl tiene border-left para crear la línea vertical de la timeline -->

        <?php foreach($hist as $h): ?>
        <div class="cs-tl-item">
            <div class="cs-tl-dot"></div>
            <!-- Punto circular que marca cada entrada en la línea de tiempo -->

            <div class="cs-tl-tipo"><?= htmlspecialchars($h['tipo_reparacion']??'Registro') ?></div>
            <!-- Tipo de reparación como etiqueta visual -->

            <div class="cs-tl-meta">
                <?= date('d/m/Y', strtotime($h['fecha_registro'])) ?>
                <!-- Fecha formateada del registro -->
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
```

## Resumen de variables

| Variable | Tipo | Descripción |
|---|---|---|
| `$idCliente` | int | ID del cliente en tabla clientes |
| `$vehiculos` | array | Vehículos con estado actual |
| `$historiales` | array | Mapa id_vehiculo → registros historial |
| `$hitosConfig` | array | Configuración de los 4 hitos de progreso |
| `$progreso` | int | Porcentaje calculado según el estado (0-100) |
| `$progColor` | string | Color hex de la barra de progreso |
| `$ultimo` | array\|null | Registro más reciente del historial |
