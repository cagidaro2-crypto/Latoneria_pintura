# Documentación: cliente_vehiculos.php

## Descripción general
Módulo del cliente para gestionar sus vehículos. Muestra una grilla de tarjetas con foto, estado, barra de progreso e historial colapsable. Permite registrar un nuevo vehículo con foto opcional.

## Dependencias
- `config/database.php` — conexión PDO
- `models/Vehiculo.php` — modelo Vehiculo
- `layouts/header.php` y `footer.php` — layout
- `cliente_styles.php` — estilos del módulo cliente
- `controllers/VehiculoController.php` — procesa el registro

## Flujo de ejecución
1. Verifica sesión y rol cliente (3)
2. Busca `id_cliente` por correo de sesión
3. Consulta vehículos del cliente con estado
4. Por cada vehículo: obtiene foto principal e historial
5. Renderiza grilla de tarjetas
6. Modal de registro disponible

## Código documentado por bloques

### Búsqueda de fotos
```php
$sf = $db->prepare("SELECT ruta_archivo FROM vehiculo_fotos
     WHERE id_vehiculo=:id
     ORDER BY FIELD(etapa,'antes','durante','despues'), fecha_subida DESC
     LIMIT 1");
// FIELD() define el orden de prioridad de etapas: primero 'antes', luego 'durante', luego 'despues'
// Si hay varias fotos 'antes', toma la más reciente (fecha_subida DESC)
// LIMIT 1 retorna solo la foto principal
```

### Cálculo de progreso
```php
$prog = match(true) {
    stripos($estado,'espera')    !==false => 15,  // Recién ingresado: 15%
    stripos($estado,'reparaci')  !==false => 45,  // En reparación: 45%
    stripos($estado,'pintura')   !==false => 75,  // En pintura: 75%
    stripos($estado,'listo')     !==false => 100, // Listo para entregar: 100%
    stripos($estado,'cancelado') !==false => 0,   // Cancelado: 0%
    default => 5,                                  // Estado desconocido: 5%
};
// match(true) evalúa cada condición booleana en orden y retorna el primer valor que coincida
// stripos() es case-insensitive, busca subcadenas sin importar mayúsculas
```

### Color del progreso
```php
$progClr = match(true) {
    $prog>=100 => '#22c55e',  // Verde: completado
    $prog>=75  => '#000000',  // Negro: pintura
    $prog>=45  => '#6366f1',  // Violeta: reparación
    $prog>=15  => '#f59e0b',  // Ámbar: espera
    default    => '#ef4444',  // Rojo: cancelado o sin estado
};
```

### Tarjeta de vehículo
```html
<!-- Imagen del vehículo o placeholder -->
<?php if($foto && file_exists(__DIR__.'/../../public/'.$foto)): ?>
    <img src="../../public/<?= htmlspecialchars($foto) ?>" ...>
    <!-- file_exists() verifica que el archivo realmente existe en disco -->
<?php else: ?>
    <div class="veh-placeholder"><i class="fas fa-car"></i></div>
    <!-- Si no hay foto, muestra ícono de carro -->
<?php endif; ?>

<!-- Badge de estado flotante sobre la foto -->
<span style="background:<?= $bgE ?>;color:<?= $clrE ?>;">
    <?= htmlspecialchars($estado) ?>
</span>
```

### Historial colapsable
```html
<button data-bs-toggle="collapse" data-bs-target="#hist-<?= $id ?>">
    <!-- data-bs-toggle="collapse" activa el componente Collapse de Bootstrap -->
    <!-- data-bs-target apunta al div colapsable por su ID único -->
    Historial (<?= count($hist) ?>)  <!-- Muestra cuántos registros hay -->
</button>

<div class="collapse" id="hist-<?= $id ?>">
    <!-- Se genera un ID único por vehículo para evitar conflictos si hay varios -->
```

### Modal de registro con foto
```html
<form action="../../controllers/VehiculoController.php" method="POST"
      enctype="multipart/form-data">
<!-- enctype="multipart/form-data" es OBLIGATORIO para poder subir archivos -->

<input type="file" name="foto" accept=".jpg,.jpeg,.png,.webp"
       onchange="previewFoto(this)">
<!-- accept filtra los tipos de archivo en el selector del sistema operativo -->
<!-- onchange ejecuta previewFoto() cuando el usuario selecciona un archivo -->
```

### Función previewFoto (JS)
```javascript
function previewFoto(input) {
    if (input.files && input.files[0]) {
        // Verifica que el usuario seleccionó al menos un archivo

        const reader = new FileReader();
        // FileReader permite leer el contenido de archivos en el cliente

        reader.onload = e => {
            img.src = e.target.result;
            // e.target.result contiene el archivo como Data URL (base64)
            // Se asigna al src de la imagen para mostrar preview instantáneo
            wrap.style.display = 'block'; // Muestra el preview
        };
        reader.readAsDataURL(input.files[0]);
        // Inicia la lectura del archivo como Data URL
    }
}
```

## Resumen de variables

| Variable | Tipo | Descripción |
|---|---|---|
| `$idCliente` | int\|null | ID del cliente |
| `$vehiculos` | array | Lista de vehículos del cliente |
| `$fotos` | array | Mapa id_vehiculo → ruta de foto principal |
| `$historiales` | array | Mapa id_vehiculo → array de registros del historial |
| `$estados` | array | Estados disponibles para el selector del modal |
