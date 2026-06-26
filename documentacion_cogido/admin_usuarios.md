# Documentación: admin_usuarios.php

## Descripción general
Módulo de gestión completa de usuarios del sistema. El administrador puede listar todos los usuarios (de cualquier rol), crear nuevos, editar sus datos y eliminarlos. Usa el modelo `Usuario` para obtener los datos.

## Dependencias
- `config/database.php` — conexión PDO
- `models/Usuario.php` — modelo de usuario
- `layouts/header.php` y `footer.php` — layout compartido
- `controllers/AdminUsuarioController.php` — procesa CRUD

## Flujo de ejecución
1. Verifica sesión y rol administrador (1)
2. Instancia el modelo `Usuario` y llama `obtenerTodos()`
3. Define mapa de roles: id_rol → texto y color de badge
4. Renderiza tabla con todos los usuarios
5. Modales de creación y edición disponibles

## Código documentado por bloques

```php
require_once __DIR__ . '/../../models/Usuario.php';
// Carga el modelo Usuario que tiene métodos para consultar la tabla usuarios

$model  = new Usuario($db);
// Instancia el modelo pasando la conexión PDO

$usuarios = $model->obtenerTodos();
// Llama al método que retorna todos los usuarios con JOIN a roles
```

### Mapa de roles
```php
$roles = [
    1 => ['texto' => 'Administrador', 'badge' => 'bg-dark'],
    // Rol 1: Administrador, badge negro oscuro

    2 => ['texto' => 'Cliente',       'badge' => 'bg-success'],
    // Rol 2: Empleado (nota: en el schema nuevo 2=Empleado, este archivo tiene el texto incorrecto)

    3 => ['texto' => 'Empleado',      'badge' => 'bg-info text-dark'],
    // Rol 3: Cliente (idem)
];
// Este mapa convierte el id_rol numérico en texto legible y clase CSS de Bootstrap para el badge
```

### Renderizado de tabla
```php
foreach ($usuarios as $i => $u):
    $idRol  = (int)($u['id_rol'] ?? 0);
    // Obtiene el id de rol y lo convierte a entero

    $rolInfo = $roles[$idRol] ?? ['texto' => 'Desconocido', 'badge' => 'bg-secondary'];
    // Busca el rol en el mapa. Si no existe, usa 'Desconocido' como fallback

    $nombreCompleto = trim(($u['nombres'] ?? '') . ' ' . ($u['apellidos'] ?? '')) ?: '–';
    // Combina nombres y apellidos. ?: '–' muestra guión si ambos están vacíos
```

### Modal de registro
```html
<select name="id_rol" class="form-select" required>
    <option value="2">Cliente</option>     <!-- id_rol=2 en BD -->
    <option value="3">Empleado</option>    <!-- id_rol=3 en BD -->
    <option value="1">Administrador</option> <!-- id_rol=1 en BD -->
</select>
<!-- El valor del option se envía en $_POST['id_rol'] al controlador -->
```

### Función `editarUsuario`
```javascript
function editarUsuario(u) {
    // Recibe objeto JSON con todos los datos del usuario

    document.getElementById('editId').value       = u.id_usuario;
    // Pone el ID en campo oculto para que el controlador sepa qué registro actualizar

    document.getElementById('editRol').value       = u.id_rol ?? 2;
    // Selecciona el rol actual en el dropdown. ?? 2 como valor por defecto

    new bootstrap.Modal(document.getElementById('modalEditar')).show();
    // Abre el modal de edición
}
```

## Resumen de métodos del modelo usados

| Método | Descripción |
|---|---|
| `obtenerTodos()` | Retorna todos los usuarios con JOIN a tabla roles |

## Acciones del controlador

| Acción | Método | Descripción |
|---|---|---|
| `registrar` | POST | Crea usuario con cualquier rol |
| `actualizar` | POST | Edita datos del usuario |
| `eliminar` | GET | Elimina el usuario por `id_usuario` |
