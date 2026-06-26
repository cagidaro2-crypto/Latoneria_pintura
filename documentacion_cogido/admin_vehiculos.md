# Documentación: admin_vehiculos.php

## Descripción general
Módulo de gestión completa de vehículos para el administrador. Permite listar, buscar, filtrar por estado, editar, cambiar estado, agregar historial y ver fotos de todos los vehículos del taller. Tiene botones de acción en cada fila de la tabla.

## Dependencias
- `config/database.php`, `models/Vehiculo.php`
- `layouts/header.php` / `footer.php`
- `controllers/VehiculoController.php` — procesa cambios de estado e historial
- `controllers/HistorialAjaxController.php` — endpoint AJAX para ver historial

## Flujo de ejecución
1. Verifica sesión rol 1 (admin)
2. `$vModel->obtenerTodos()` trae todos los vehículos con cliente y estado
3. `$vModel->obtenerEstados()` trae los estados disponibles
4. Renderiza tabla con filtros por estado
5. Modales: editar, cambiar estado, agregar historial, ver historial, ver fotos

## Código documentado por bloques

### Obtener todos los vehículos
```php
$vehiculos = $vModel->obtenerTodos();
// Llama al modelo que ejecuta:
// SELECT v.*, CONCAT(c.nombres,' ',COALESCE(c.apellidos,'')) AS nombre_cliente, ev.estado
// FROM vehiculos v
// LEFT JOIN clientes c ON v.id_cliente = c.id_cliente
// LEFT JOIN estado_vehiculo ev ON v.id_estado = ev.id_estado_vehiculo
// ORDER BY v.id_vehiculo DESC
```

### Botón cambiar estado
```html
<button onclick="cambiarEstado(<?= $v['id_vehiculo'] ?>, <?= $v['id_estado'] ?? 1 ?>)">
<!-- Pasa el id del vehículo y su estado actual para preseleccionar en el select del modal -->
<!-- ?? 1 como fallback si id_estado es NULL -->
```

### Botón ver historial (carga AJAX)
```javascript
function verHistorial(idVehiculo, placa) {
    document.getElementById('historialContenido').innerHTML =
        '<div class="spinner-border"></div>';
    // Muestra spinner mientras carga

    fetch('../../controllers/HistorialAjaxController.php?id_vehiculo=' + idVehiculo)
    .then(r => r.json())
    .then(data => {
        // Construye el HTML del timeline dinámicamente
        let html = '<div class="timeline">';
        data.forEach(h => {
            html += `<div class="timeline-item">
                <span>${escHtml(h.tipo_reparacion)}</span>
                <p>${escHtml(h.descripcion)}</p>
            </div>`;
        });
        document.getElementById('historialContenido').innerHTML = html + '</div>';
    });
}

function escHtml(str) {
    // Función de escape XSS para contenido dinámico en JS
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
```

### Filtros por estado
```javascript
let filtroEstadoActivo = 'todos';
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        filtroEstadoActivo = this.dataset.filtro;
        // Lee el valor del atributo data-filtro del botón clickeado
        filtrarTabla();
    });
});

function filtrarTabla() {
    document.querySelectorAll('#tablaVehiculos tbody tr[data-estado]').forEach(tr => {
        const pasaFiltro = filtroEstadoActivo === 'todos'
                        || tr.dataset.estado === filtroEstadoActivo;
        // dataset.estado lee el atributo data-estado del <tr>
    });
}
```

## Resumen de acciones disponibles

| Acción | Modal | Controlador destino |
|---|---|---|
| Editar vehículo | `#modalEditar` | VehiculoController (actualizar) |
| Cambiar estado | `#modalEstado` | VehiculoController (cambiar_estado) |
| Agregar historial | `#modalAgregarHistorial` | VehiculoController (agregar_historial) |
| Ver historial | `#modalHistorial` | HistorialAjaxController (AJAX) |
| Ver fotos | `#modalFotos` | Carga fotos estáticas |
