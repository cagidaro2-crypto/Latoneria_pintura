# Documentación: admin_clientes.php

## Descripción general
Módulo de gestión de clientes para el administrador. Permite listar, buscar, crear, editar, activar/desactivar y eliminar clientes (usuarios con `id_rol = 3`). Usa la tabla `usuarios` del schema nuevo.

## Dependencias
- `config/database.php` — conexión PDO
- `layouts/header.php` — sidebar, topbar, estilos compartidos
- `layouts/footer.php` — cierre HTML y Bootstrap JS
- `controllers/AdminClienteController.php` — procesa acciones CRUD

## Flujo de ejecución
1. Verifica sesión y que el rol sea `1` (administrador)
2. Incluye el header (genera sidebar y topbar)
3. Consulta todos los usuarios con `id_rol = 3` de la tabla `usuarios`
4. Renderiza tabla con los clientes
5. Muestra modales de creación y edición
6. Si hay alerta en sesión, la muestra con SweetAlert2

## Código documentado por bloques

```php
$titulo = 'Clientes';
// Define el título de la página (usado por header.php en el <title>)

require_once __DIR__ . '/../../config/database.php';
// Carga la clase Database para conectarse a la BD

if (session_status() === PHP_SESSION_NONE) session_start();
// Inicia sesión solo si no está ya activa (evita el error "headers already sent")

if (!isset($_SESSION['usuario']) || (int)($_SESSION['usuario']['rol'] ?? 0) !== 1) {
    header("Location: ../usuarios/login.php"); exit;
}
// Guard de seguridad: si no hay sesión o el rol no es admin (1), redirige al login
```

### Consulta de clientes
```php
$stmt = $db->query(
    "SELECT id_usuario, nombres, apellidos, correo, telefono, activo
     FROM usuarios
     WHERE id_rol = 3         -- Solo clientes (rol 3)
     ORDER BY nombres ASC"    -- Orden alfabético por nombre
);
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
// fetchAll con PDO::FETCH_ASSOC retorna array asociativo (claves = nombre de columna)
```

### Tabla HTML
```php
foreach ($clientes as $c):
    $nombreCompleto = trim(($c['nombres'] ?? '') . ' ' . ($c['apellidos'] ?? '')) ?: '–';
    // Concatena nombres y apellidos. trim() elimina espacios. ?: '–' muestra guión si está vacío
?>
<tr>
    <td><?= htmlspecialchars($nombreCompleto) ?></td>
    <!-- htmlspecialchars() convierte caracteres especiales a entidades HTML (previene XSS) -->
```

### Botones de acción
```php
// Botón editar: abre modal pasando datos del cliente como JSON
onclick="editarCliente(<?= htmlspecialchars(json_encode($c)) ?>)"
// json_encode($c) convierte el array PHP a objeto JSON
// htmlspecialchars() escapa las comillas del JSON para usarlo en atributo HTML

// Botón desactivar: enlace directo al controlador con parámetros por GET
href="../../controllers/AdminClienteController.php?accion=toggle&id=<?= $c['id_usuario'] ?>&estado=0"
// accion=toggle → cambia el estado activo del usuario
// id → id_usuario del cliente a modificar
// estado=0 → desactivar

// Botón eliminar: confirma antes de proceder
onclick="return confirm('¿Eliminar permanentemente este cliente?')"
// return confirm() muestra diálogo nativo del navegador. Si el usuario cancela, return false previene la navegación
```

### Modal Nuevo Cliente
```html
<form action="../../controllers/AdminClienteController.php" method="POST">
    <input type="hidden" name="accion" value="registrar">
    <!-- Campo oculto que indica qué acción ejecutar en el controlador -->

    <input type="text" name="nombres" required>
    <input type="text" name="apellidos" required>
    <input type="email" name="correo" required>
    <input type="tel" name="telefono" required>
    <input type="password" name="password" required>
    <!-- Los campos required HTML previenen envío si están vacíos -->
</form>
```

### Función JavaScript `editarCliente`
```javascript
function editarCliente(c) {
    // c es el objeto JSON con los datos del cliente

    document.getElementById('editIdCliente').value = c.id_usuario;
    // Pone el ID en el campo oculto del formulario de edición

    document.getElementById('editNombresCliente').value = c.nombres ?? '';
    // El operador ?? '' pone string vacío si nombres es null

    // ... misma lógica para apellidos, correo, teléfono

    new bootstrap.Modal(document.getElementById('modalEditarCliente')).show();
    // Abre el modal de edición programáticamente con Bootstrap 5
}
```

### Buscador en tiempo real
```javascript
document.getElementById('buscador').addEventListener('input', function () {
    const q = this.value.toLowerCase();
    // Convierte el texto buscado a minúsculas para comparación case-insensitive

    document.querySelectorAll('#tablaClientes tbody tr').forEach(tr => {
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
        // Muestra ('') u oculta ('none') cada fila según si contiene el texto buscado
    });
});
```

## Resumen de variables

| Variable | Tipo | Descripción |
|---|---|---|
| `$titulo` | string | Título de página para el header |
| `$db` | PDO | Conexión a la base de datos |
| `$alert` | array\|null | Mensaje de alerta de sesión |
| `$clientes` | array | Lista de clientes obtenidos de la BD |

## Acciones disponibles (via AdminClienteController)

| Acción | Método | Descripción |
|---|---|---|
| `registrar` | POST | Crea un nuevo usuario con rol 3 |
| `actualizar` | POST | Edita datos de un usuario existente |
| `toggle` | GET | Activa o desactiva un usuario |
| `eliminar` | GET | Elimina permanentemente un usuario |
