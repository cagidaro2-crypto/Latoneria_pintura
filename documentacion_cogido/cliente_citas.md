# Documentación: cliente_citas.php

## Descripción general
Módulo del cliente para agendar y gestionar sus citas en el taller. Integra un calendario FullCalendar que muestra disponibilidad (días ocupados/libres). Al hacer clic en un día disponible se abre el modal para agendar. Incluye tabla de citas agendadas con opción de cancelar las pendientes o confirmadas.

## Dependencias
- `config/database.php`, `layouts/header.php` / `footer.php`, `cliente_styles.php`
- `controllers/ClienteCitaController.php` — agendar y cancelar citas
- `controllers/CitasCalendarioController.php` — endpoint AJAX del calendario
- `FullCalendar 6.1.11` — biblioteca de calendario

## Flujo de ejecución
1. Verifica sesión y rol cliente (3)
2. Busca `id_cliente` por correo en tabla `clientes`
3. Consulta vehículos del cliente para el select del modal
4. Consulta citas del cliente ordenadas por fecha
5. Renderiza calendario + tabla de citas
6. FullCalendar carga eventos del servidor vía AJAX

## Código documentado por bloques

### Consulta de vehículos del cliente
```php
$stmtVeh = $db->prepare(
    "SELECT v.id_vehiculo, v.placa, v.marca, v.modelo, v.anio
     FROM vehiculos v
     WHERE v.id_cliente = :id ORDER BY v.placa ASC"
);
$stmtVeh->execute([':id' => $idCliente]);
// Solo los vehículos del cliente actual (no todos del sistema)
// Se usan en el select del modal de nueva cita
```

### Consulta de citas del cliente
```php
$stmtCitas = $db->prepare(
    "SELECT c.*, v.placa, v.marca, v.modelo
     FROM citas c
     LEFT JOIN vehiculos v ON c.id_vehiculo = v.id_vehiculo
     WHERE c.id_cliente = :id
     ORDER BY c.fecha_cita DESC"
);
$stmtCitas->execute([':id' => $idCliente ?: $idPersona]);
// $idCliente ?: $idPersona: si no hay id_cliente, usa el id_usuario de sesión como fallback
// Esto cubre casos donde el cliente no tiene registro en tabla clientes aún
```

### Inicialización del Calendario
```javascript
const cal = new FullCalendar.Calendar(calEl, {
    initialView: 'dayGridMonth',
    // Vista mensual por defecto

    locale: 'es',
    // Idioma español (nombres de meses y días en español)

    events: '../../controllers/CitasCalendarioController.php',
    // URL del endpoint que retorna las citas como JSON para pintar en el calendario

    eventColor: '#dc3545',
    // Color rojo para los eventos (días con citas ocupadas)

    dayCellDidMount: function(info) {
        const hoy = new Date(); hoy.setHours(0,0,0,0);
        if (info.date < hoy) return;
        // No colorear fechas pasadas
        info.el.style.backgroundColor = 'rgba(34,197,94,0.08)';
        info.el.style.cursor = 'pointer';
        // Verde muy claro: días disponibles futuros
    },

    dateClick: function(info) {
        const hoy = new Date(); hoy.setHours(0,0,0,0);
        if (info.date < hoy) {
            Swal.fire({ text: 'No puedes agendar en fechas anteriores.' });
            return;
        }
        document.getElementById('inputFechaCita').value = info.dateStr + 'T08:00';
        // info.dateStr formato 'YYYY-MM-DD', se agrega 'T08:00' para inicio del día
        new bootstrap.Modal(document.getElementById('modalAgendar')).show();
        // Abre el modal con la fecha preseleccionada al hacer clic en un día
    }
});
cal.render();
// render() es necesario para mostrar el calendario en el DOM
```

### Badges de estado de cita
```javascript
const eBadge = {
    'Pendiente':  'bg-warning text-dark',
    'Confirmada': 'bg-success',
    'Cancelada':  'bg-danger',
    'Realizada':  'bg-primary',
};
const eb = eBadge[$c['estado']] ?? 'bg-secondary';
```

### Formulario de agendar
```html
<form action="../../controllers/ClienteCitaController.php" method="POST">
    <input type="hidden" name="accion" value="agendar">
    <!-- Indica al controlador qué operación realizar -->

    <select name="id_vehiculo" required>
        <!-- Solo vehículos del cliente actual -->
    </select>

    <select name="tipo_servicio" required>
        <option>Latonería</option>
        <option>Pintura completa</option>
        <!-- Opciones predefinidas para estandarizar -->
    </select>

    <input type="datetime-local" name="fecha_cita"
           min="<?= date('Y-m-d\TH:i') ?>">
    <!-- datetime-local: input combinado de fecha y hora -->
    <!-- min: no permite seleccionar fechas/horas pasadas (validación en cliente) -->
</form>
```

## Variables principales

| Variable | Tipo | Descripción |
|---|---|---|
| `$idCliente` | int | ID en tabla clientes |
| `$idPersona` | int | ID usuario de sesión (fallback) |
| `$vehiculos` | array | Vehículos del cliente para el select |
| `$citas` | array | Citas del cliente ordenadas por fecha |
