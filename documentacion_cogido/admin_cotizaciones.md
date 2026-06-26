# Documentación: admin_cotizaciones.php

## Descripción general
Módulo de gestión de cotizaciones para el administrador. Permite listar cotizaciones con sus estados (Pendiente/Aceptada/Rechazada), ver detalles de servicios y repuestos de cada una, aprobar o rechazar cotizaciones, y crear nuevas.

## Dependencias
- `config/database.php`, `layouts/header.php` / `footer.php`
- `controllers/AdminCotizacionController.php` — acciones aprobar/rechazar/crear

## Flujo de ejecución
1. Verifica sesión rol 1
2. Consulta cotizaciones con JOIN a vehículos y clientes
3. Consulta vehículos disponibles para el select del modal
4. Consulta servicios disponibles
5. Si hay `?id=` en la URL, carga los detalles de esa cotización
6. Renderiza lista + panel de detalle lateral

## Código documentado por bloques

### Consulta de cotizaciones
```php
$stmt = $db->query(
    "SELECT c.*, cl.nombres AS cliente_nombre, v.placa
     FROM cotizaciones c
     JOIN vehiculos v  ON c.id_vehiculo = v.id_vehiculo
     JOIN clientes cl ON v.id_cliente   = cl.id_cliente
     -- No hay relación directa cotizacion→cliente, va a través del vehículo
     ORDER BY c.fecha DESC"
);
```

### Detalle de cotización seleccionada
```php
$idSeleccionada = (int)($_GET['id'] ?? 0);
// Lee el parámetro ?id= de la URL como entero
// (int) previene inyección SQL asegurando que sea número

if ($idSeleccionada) {
    $stmtCot = $db->prepare(
        "SELECT c.*, cl.nombres AS cliente_nombre, v.placa
         FROM cotizaciones c
         JOIN vehiculos v ON c.id_vehiculo = v.id_vehiculo
         JOIN clientes cl ON v.id_cliente  = cl.id_cliente
         WHERE c.id_cotizacion = :id"
    );
    $stmtCot->execute([':id' => $idSeleccionada]);
    // Parámetros preparados previenen SQL Injection
```

### Badge de estado
```php
$badgeColor = match($cot['estado'] ?? 'Pendiente') {
    'Aprobada'  => 'bg-success',
    'Rechazada' => 'bg-danger',
    'Expirada'  => 'bg-secondary',
    default     => 'bg-warning text-dark'  // Pendiente
};
```

### Botones aprobar/rechazar
```html
<form action="../../controllers/AdminCotizacionController.php" method="POST">
    <input type="hidden" name="accion" value="aprobar">
    <!-- Campo oculto que indica la acción al controlador -->
    <input type="hidden" name="id_cotizacion" value="<?= $cot['id_cotizacion'] ?>">
    <!-- ID de la cotización a aprobar -->
    <button type="submit" class="btn btn-success">Aprobar</button>
</form>
<!-- Formulario separado por cada acción para simplicidad -->
```

### Detalles de servicios
```php
$stmtDs = $db->prepare(
    "SELECT cs.*, s.nombre AS servicio_nombre
     FROM cotizacion_servicios cs     -- Tabla de relación cotización-servicio
     JOIN servicios s ON cs.id_servicio = s.id_servicio
     WHERE cs.id_cotizacion = :id"
);
// Trae los servicios incluidos en la cotización seleccionada
```

### Buscador de cotizaciones
```javascript
document.getElementById('buscador').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.cot-card').forEach(card => {
        card.style.display = card.dataset.search.includes(q) ? '' : 'none';
        // dataset.search tiene concatenado: nombre_cliente + placa (en minúsculas)
    });
});
```

### Agregar servicios dinámicamente
```javascript
function agregarServicio() {
    const container = document.getElementById('serviciosContainer');
    const first     = container.querySelector('.servicio-row');
    const clone     = first.cloneNode(true);
    // cloneNode(true) copia el elemento con todos sus hijos
    clone.querySelectorAll('input').forEach(i => i.value = '');
    // Limpia los valores del clon
    container.appendChild(clone);
    // Agrega el nuevo campo al final del contenedor
}
```

## Variables principales

| Variable | Tipo | Descripción |
|---|---|---|
| `$cotizaciones` | array | Todas las cotizaciones con cliente y vehículo |
| `$vehiculos` | array | Vehículos para el select del modal |
| `$servicios` | array | Servicios disponibles |
| `$idSeleccionada` | int | ID de cotización actualmente en el panel de detalle |
| `$cotSeleccionada` | array\|false | Datos de la cotización seleccionada |
| `$detallesServ` | array | Servicios de la cotización seleccionada |
| `$detallesRep` | array | Repuestos (productos) de la cotización seleccionada |
