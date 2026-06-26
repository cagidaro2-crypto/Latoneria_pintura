# Documentación: empleado_citas.php

## Descripción general
Módulo del empleado para gestionar citas del taller. Organiza las citas en 3 pestañas (Pendientes, Confirmadas, Todas) con contadores. Permite confirmar, rechazar citas pendientes o marcar como realizadas las confirmadas. Incluye filtros por texto y fecha.

## Dependencias
- `config/database.php`, `layouts/header.php` / `footer.php`
- `controllers/AdminCitaController.php` — confirmar, rechazar, realizar citas

## Flujo de ejecución
1. Verifica sesión y rol empleado (2)
2. Consulta todas las citas con datos de cliente y vehículo
3. Filtra arrays para separar pendientes y confirmadas
4. Renderiza tabs con la función `tablaCitas()`
5. Filtros JS aplican en tiempo real sobre las tablas activas

## Código documentado por bloques

### Consulta de citas
```php
$s = $db->query(
    "SELECT c.*, cl.nombres AS cliente_nombre, cl.telefono AS cliente_telefono,
            v.placa, v.marca, v.modelo
     FROM citas c
     LEFT JOIN clientes cl ON c.id_cliente  = cl.id_cliente
     -- LEFT JOIN: la cita puede no tener cliente asignado en casos especiales
     LEFT JOIN vehiculos v ON c.id_vehiculo = v.id_vehiculo
     -- LEFT JOIN: la cita puede no tener vehículo si es consulta general
     ORDER BY c.fecha_cita DESC"
);
```

### Separación en arrays por estado
```php
$pendientes  = array_filter($todasCitas, fn($c) => $c['estado'] === 'Pendiente');
$confirmadas = array_filter($todasCitas, fn($c) => $c['estado'] === 'Confirmada');
// array_filter con arrow function retorna solo los elementos que cumplen la condición
// Comparación exacta (===) con el string del estado
```

### Función tablaCitas()
```php
function tablaCitas(array $citas): string {
    // Genera el HTML de la tabla como string para ser embebido con echo
    // ob_start() captura el output (todo lo que haría echo o print)
    ob_start();
    // ... renderiza la tabla con PHP/HTML mezclado ...
    return ob_get_clean();
    // ob_get_clean() retorna el buffer capturado y lo limpia
}
// Esta técnica (output buffering) permite mezclar PHP/HTML y retornarlo como string
```

### Badges por estado de cita
```php
$badges = [
    'Pendiente'  => 'bg-warning text-dark',
    'Confirmada' => 'bg-success',
    'Cancelada'  => 'bg-danger',
    'Realizada'  => 'bg-primary',
];
$eb = $badges[$c['estado']] ?? 'bg-secondary';
// Lookup en array es más limpio que múltiples if/else
// ?? 'bg-secondary' como fallback para estados desconocidos
```

### Atributo data-fecha para filtro
```html
<tr data-fecha="<?= substr($c['fecha_cita'], 0, 10) ?>">
<!-- substr($fecha, 0, 10) extrae solo "YYYY-MM-DD" de "YYYY-MM-DD HH:MM:SS" -->
<!-- Permite filtrar por fecha exacta con el input type="date" -->
```

### Botones de acción condicionales
```php
<?php if($c['estado'] === 'Pendiente'): ?>
    <!-- Muestra Confirmar y Rechazar -->
<?php elseif($c['estado'] === 'Confirmada'): ?>
    <!-- Muestra solo Marcar como Realizada -->
<?php else: ?>
    <span>–</span>
    <!-- Para Cancelada o Realizada, no hay acciones disponibles -->
<?php endif; ?>
```

### Filtro de citas (JS)
```javascript
function aplicarFiltros() {
    const q     = document.getElementById('buscador').value.toLowerCase();
    const fecha = document.getElementById('filtrFecha').value;
    // fecha tiene formato "YYYY-MM-DD" (nativo del input type="date")

    document.querySelectorAll('.tabla-citas tbody tr[data-fecha]').forEach(tr => {
        const textoOk = !q    || tr.textContent.toLowerCase().includes(q);
        const fechaOk = !fecha || tr.dataset.fecha === fecha;
        // Compara el data-fecha del tr con el valor del input de fecha
        tr.style.display = (textoOk && fechaOk) ? '' : 'none';
    });
}
// El selector '.tabla-citas tbody tr' aplica a todas las tablas de todas las pestañas simultáneamente
```

## Resumen de variables

| Variable | Tipo | Descripción |
|---|---|---|
| `$todasCitas` | array | Todas las citas ordenadas por fecha desc |
| `$pendientes` | array | Subset de citas con estado 'Pendiente' |
| `$confirmadas` | array | Subset de citas con estado 'Confirmada' |
