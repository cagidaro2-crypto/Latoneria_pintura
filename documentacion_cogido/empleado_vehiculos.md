# Documentación: empleado_vehiculos.php

## Descripción general
Módulo del empleado para gestionar todos los vehículos del taller. Permite ver la lista completa con filtros por estado, cambiar el estado de un vehículo, agregar entradas al historial de trabajo y ver el historial completo de cada vehículo vía AJAX.

## Dependencias
- `config/database.php`, `models/Vehiculo.php`
- `layouts/header.php` / `footer.php`
- `controllers/VehiculoController.php` — cambiar estado, agregar historial
- `controllers/HistorialAjaxController.php` — carga historial por AJAX

## Flujo de ejecución
1. Verifica sesión y rol empleado (2)
2. Carga todos los vehículos con `obtenerTodos()`
3. Carga estados disponibles con `obtenerEstados()`
4. Renderiza tabla con botones de filtro y acciones
5. Modales: cambiar estado, ver historial (AJAX), agregar historial

## Código documentado por bloques

### Guard de rol
```php
if (!isset($_SESSION['usuario']) || (int)($_SESSION['usuario']['rol'] ?? 0) !== 2) {
    header("Location: ../usuarios/login.php"); exit;
}
// Solo empleados (rol=2) pueden acceder. Cualquier otro rol es redirigido al login
```

### Filtros de estado (JS)
```javascript
let filtroEstadoActivo = 'todos';
// Variable global que guarda el filtro activo

document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        // Quita 'active' de todos los botones y lo pone en el clickeado
        filtroEstadoActivo = this.dataset.filtro;
        // Lee el estado a filtrar desde data-filtro="En espera"
        filtrarTabla();
    });
});
```

### Función filtrarTabla
```javascript
function filtrarTabla() {
    const q = document.getElementById('buscador').value.toLowerCase();
    let visibles = 0;
    document.querySelectorAll('#tablaVehiculos tbody tr[data-estado]').forEach(tr => {
        const pasaBusq   = !q || tr.textContent.toLowerCase().includes(q);
        // !q: si no hay búsqueda, pasa todos
        const pasaFiltro = filtroEstadoActivo === 'todos'
                        || tr.dataset.estado === filtroEstadoActivo;
        // dataset.estado coincide exactamente con el estado del vehículo
        tr.style.display = (pasaBusq && pasaFiltro) ? '' : 'none';
        // '' muestra el elemento (valor CSS vacío = muestra)
        // 'none' oculta el elemento
        if (pasaBusq && pasaFiltro) visibles++;
    });
    const sinRes = document.getElementById('sinResultados');
    if (sinRes) sinRes.style.display = visibles === 0 ? '' : 'none';
    // Muestra fila "Sin resultados" solo cuando ningún elemento pasa el filtro
}
```

### Ver historial vía AJAX
```javascript
function verHistorial(idVehiculo, placa) {
    document.getElementById('historialPlaca').textContent = placa;
    document.getElementById('historialContenido').innerHTML =
        '<div class="text-center py-4"><div class="spinner-border"></div></div>';
    // Muestra spinner de carga mientras espera la respuesta

    fetch('../../controllers/HistorialAjaxController.php?id_vehiculo=' + idVehiculo)
    // fetch hace petición GET al controlador AJAX
    .then(r => r.json())
    // Parsea la respuesta JSON
    .then(data => {
        if (!data.length) {
            // Si el array está vacío, no hay historial
            document.getElementById('historialContenido').innerHTML =
                '<p class="text-muted text-center py-3">Sin entradas de historial.</p>';
            return;
        }
        let html = '<div class="timeline">';
        data.forEach(h => {
            // Construye cada item del timeline
            const fecha = h.fecha_registro
                        ? new Date(h.fecha_registro).toLocaleDateString('es-CO')
                        : '–';
            // toLocaleDateString('es-CO') formatea la fecha al estilo colombiano: d/m/yyyy
            html += `<div class="timeline-item">
                <span>${esc(h.tipo_reparacion)}</span>
                <p>${esc(h.descripcion)}</p>
                <small>${fecha}</small>
            </div>`;
        });
        document.getElementById('historialContenido').innerHTML = html + '</div>';
    })
    .catch(() => {
        // Si la petición falla (sin red, error servidor), muestra mensaje de error
        document.getElementById('historialContenido').innerHTML =
            '<p class="text-danger">Error al cargar el historial.</p>';
    });
}

function esc(s) {
    // Escape manual de HTML para prevenir XSS en contenido dinámico
    return s ? String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;') : '';
}
```

### Modal agregar historial
```html
<form action="../../controllers/VehiculoController.php" method="POST">
    <input type="hidden" name="accion" value="agregar_historial">
    <input type="hidden" name="id_vehiculo" id="ahIdVehiculo">
    <!-- ID poblado dinámicamente por abrirAgregarHistorial() -->

    <select name="tipo_reparacion" required>
        <option>Latonería</option>
        <option>Pintura</option>
        <option>Enderezado</option>
        <!-- Opciones predefinidas para estandarizar los tipos -->
    </select>

    <input type="date" name="fecha_registro" value="<?= date('Y-m-d') ?>">
    <!-- date('Y-m-d') da la fecha actual como valor por defecto (ISO 8601) -->
</form>
```

## Resumen de variables

| Variable | Tipo | Descripción |
|---|---|---|
| `$vehiculos` | array | Todos los vehículos con estado y cliente |
| `$estados` | array | Estados disponibles para los filtros y select |
