# Documentación: registre.php

## Descripción general
Página de registro de nuevos clientes. Muestra un formulario HTML con campos para nombres, apellidos, teléfono, correo y contraseña. Envía los datos al `AuthController` para crear el usuario en la base de datos.

## Dependencias
- `Bootstrap 5.3.3` — componentes visuales
- `Font Awesome 6.5.0` — íconos
- `Google Fonts (Inter)` — tipografía
- `SweetAlert2` — alertas de éxito/error

## Flujo de ejecución
1. Se inicia sesión PHP para leer mensajes de alerta
2. Se lee `$_SESSION['alert']` y se limpia
3. Se renderiza el HTML del formulario
4. Si existe alerta, se muestra con SweetAlert2 vía JS

## Código documentado por bloques

```php
<?php
session_start();
// Inicia la sesión para poder leer alertas guardadas por el controlador

$alert = $_SESSION['alert'] ?? null;
// Lee el mensaje de alerta de la sesión (éxito, error, etc.)
// El operador ?? retorna null si la clave no existe

unset($_SESSION['alert']);
// Elimina la alerta de la sesión para que no vuelva a mostrarse en la próxima recarga
?>
```

### Sección `<head>`
```html
<!-- Define el charset UTF-8 para soportar caracteres especiales -->
<meta charset="UTF-8">

<!-- Hace la página responsiva en dispositivos móviles -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Título visible en la pestaña del navegador -->
<title>Registro – Latoneria y Pintura 371</title>
```

### Variables CSS (`:root`)
```css
--color-bg: #181c14;       /* Fondo principal oscuro verdoso */
--color-panel: #3c3d37;    /* Fondo de la tarjeta del formulario */
--color-accent: #697565;   /* Color de acento (verde oliva) para botones */
--color-text-main: #f5f5f5; /* Texto principal blanco claro */
--color-text-muted: #a1a1aa; /* Texto secundario gris */
```

### `.auth-card`
Tarjeta central que contiene el formulario. Tiene `max-width: 550px` para no expandirse en pantallas grandes, `border-radius: 20px` para esquinas redondeadas y `box-shadow` para dar profundidad.

### `.brand-logo`
Componente visual del logo de marca. Usa `backdrop-filter: blur(5px)` para efecto de vidrio esmerilado sobre fondos.

### `.btn-primary-custom`
Botón principal del formulario. Usa la variable `--color-accent` para el color de fondo. Al hacer hover (`transition: all 0.3s`) sube 2px con `transform: translateY(-2px)`.

### Formulario HTML
```html
<form action="../../controllers/AuthController.php?accion=registro" method="POST">
<!-- Envía los datos al AuthController con la acción "registro" vía POST -->

    <input type="text" name="nombres" ...>
    <!-- Campo para nombres. `name="nombres"` es la clave en $_POST -->

    <input type="text" name="apellidos" ...>
    <!-- Campo para apellidos -->

    <input type="tel" name="telefono" ...>
    <!-- Campo de teléfono. `type="tel"` activa teclado numérico en móvil -->

    <input type="email" name="correo" ...>
    <!-- Campo de correo. Valida formato de email automáticamente -->

    <input type="password" name="password" ...>
    <!-- Campo de contraseña. Oculta los caracteres escritos -->

    <input type="password" name="password_confirm" ...>
    <!-- Confirmación de contraseña para verificar que coincidan -->
</form>
```

### Bloque SweetAlert2
```php
<?php if ($alert): ?>
<script>
    Swal.fire({
        icon: '<?= htmlspecialchars($alert['icon']) ?>',
        // Tipo de ícono: 'success', 'error', 'warning'
        // htmlspecialchars() previene inyección XSS

        title: '<?= htmlspecialchars($alert['title']) ?>',
        // Título del modal de alerta

        text: '<?= htmlspecialchars($alert['text']) ?>',
        // Mensaje descriptivo

        confirmButtonText: 'Aceptar',
        confirmButtonColor: '#697565',
        // Color del botón de confirmación (verde oliva)

        background: '#3c3d37',
        color: '#f5f5f5'
        // Fondo y texto del modal siguiendo la paleta del sistema
    });
</script>
<?php endif; ?>
```

## Resumen de variables

| Variable | Tipo | Descripción |
|---|---|---|
| `$alert` | array\|null | Mensaje de alerta de la sesión anterior |

## Campos del formulario

| Campo `name` | Tipo HTML | Descripción |
|---|---|---|
| `nombres` | text | Nombres del cliente |
| `apellidos` | text | Apellidos del cliente |
| `telefono` | tel | Teléfono de contacto |
| `correo` | email | Correo electrónico |
| `password` | password | Contraseña |
| `password_confirm` | password | Confirmación de contraseña |
