# Documentación: empleado_historial.php

## Descripción general
Módulo del empleado para consultar el historial completo de trabajos realizados en todos los vehículos del taller. Incluye filtros por texto libre, tipo de reparación y estado del vehículo. Es una vista de solo lectura (no permite editar).

## Dependencias
- `config/database.php`, `layouts/header.php` / `footer.php`

## Flujo de ejecución
1. Verifica sesión y rol empleado (2)
2. Consulta todo el historial con JOIN a vehículos, clientes y usuarios
3. Extrae tipos únicos de reparación para el filtro
4. Renderiza tabla con 3 filtros combinables
5. JS aplica los filtros en tiempo real sin recargar

## Código documentado por bloques

### Consulta del historial
```php
$s = $db->query(
    "SELECT hv.id_historial, hv.descripcion, hv.fecha_registro, hv.tipo_reparacion,
            v.placa, v.marca, v.modelo,
            cl.nombres AS cliente_nombre,
            CONCAT(u.nombres,' ',COALESCE(u.apellidos,'')) AS empleado_nombre,
            -- CONCAT une nombres y apellidos del empleado que registró el trabajo
            -- COALESCE retorna '' si apellidos es NULL (evita 'Juan null')
            ev.estado AS estado_vehiculo
     FROM historial_vehiculo hv
     JOIN vehiculos v        ON hv.id_vehiculo = v.id_vehiculo
     JOIN clientes cl        ON v.id_cliente   = cl.id_cliente
     LEFT JOIN usuarios u    ON hv.id_usuario  = u.id_usuario
     -- LEFT JOIN: el empleado puede no estar en la tabla si fue registrado manualmente
     JOIN estado_vehiculo ev ON v.id_estado    = ev.id_estado_vehiculo
     ORDER BY hv.fecha_registro DESC, hv.id_historial DESC"
     -- Doble ORDER: por fecha (más reciente), luego por id para ordenar registros del mismo día
);
```

### Extracción de tipos únicos
```php
$tipos = array_values(array_unique(array_column($historial, 'tipo_reparacion')));
sort($tipos);
// array_column extrae solo el campo 'tipo_reparacion' de cada fila
// array_unique elimina duplicados
// array_values re-indexa el array (array_unique puede dejar índices no consecutivos)
// sort ordena alfabéticamente
```

### Atributos data para filtros JS
```html
<tr data-tipo="<?= htmlspecialchars($h['tipo_reparacion']) ?>"
    data-estado="<?= htmlspecialchars($estado) ?>">
<!-- Cada fila tiene 2 atributos data- para los filtros JS -->
<!-- data-tipo: tipo de reparación exacto (ej: "Pintura") -->
<!-- data-estado: estado actual del vehículo (ej: "En espera") -->
```

### Truncado de descripción
```php
<?= htmlspecialchars(mb_strimwidth($h['descripcion'] ?? '', 0, 80, '…')) ?>
// mb_strimwidth: recorta un string multibyte (soporta tildes y ñ) a 80 caracteres
// '…' se agrega al final si se truncó
// ?? '': fallback si descripcion es NULL
```

### Función de filtrado JS (3 filtros combinados)
```javascript
function filtrar() {
    const q      = bus.value.toLowerCase();      // Texto libre
    const tipo   = fTipo.value;                   // Tipo de reparación seleccionado
    const estado = fEst.value;                    // Estado seleccionado
    let visibles = 0;

    document.querySelectorAll('#tablaHistorial tbody tr[data-tipo]').forEach(tr => {
        const pasaBusq   = !q     || tr.textContent.toLowerCase().includes(q);
        // !q: si está vacío, todos pasan
        const pasaTipo   = !tipo  || tr.dataset.tipo   === tipo;
        // Comparación exacta con dataset.tipo
        const pasaEstado = !estado || tr.dataset.estado === estado;
        // Los 3 filtros deben cumplirse simultáneamente (AND lógico)
        const visible = pasaBusq && pasaTipo && pasaEstado;
        tr.style.display = visible ? '' : 'none';
        if (visible) visibles++;
    });
    // Muestra/oculta la fila "Sin resultados"
    const sinRes = document.getElementById('sinResultados');
    if (sinRes) sinRes.style.display = visibles === 0 ? '' : 'none';
}
```

## Variables principales

| Variable | Tipo | Descripción |
|---|---|---|
| `$historial` | array | Todos los registros de historial_vehiculo |
| `$estados` | array | Estados de vehículo para el filtro |
| `$tipos` | array | Tipos únicos de reparación para el filtro |
