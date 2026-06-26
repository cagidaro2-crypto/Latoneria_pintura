# Documentación: Usuario.php

## Descripción general
Modelo de la entidad Usuario. Encapsula todas las operaciones sobre la tabla `usuarios` de la base de datos: autenticación, registro, gestión de intentos fallidos, bloqueo de cuentas, recuperación de contraseña y consultas de listado. Implementa seguridad contra ataques de fuerza bruta bloqueando la cuenta por 15 minutos tras 5 intentos fallidos.

## Dependencias
- Clase `PDO` (PHP nativo) — Recibida por inyección de dependencias en el constructor

## Código documentado línea por línea

```php
// Línea 1: Apertura del bloque PHP
<?php

// Línea 3: Definición de la clase modelo Usuario
class Usuario
{
    // Línea 5: Almacena la conexión PDO (acceso privado)
    private $conn;

    // Línea 6: Nombre de la tabla en la BD
    private $tabla = "usuarios";

    // Líneas 8-11: Constructor — recibe la conexión PDO y la almacena
    public function __construct($db)
    {
        $this->conn = $db;
    }

    // ── MÉTODO: existeCorreo ─────────────────────────────────────────────

    // Líneas 13-19: Verifica si ya existe un registro con ese correo (para login)
    // Retorna true si existe al menos un usuario con ese correo
    public function existeCorreo($correo)
    {
        $stmt = $this->conn->prepare(
            "SELECT id_usuario FROM {$this->tabla} WHERE correo = :correo LIMIT 1"
        );
        $stmt->execute([':correo' => $correo]);
        return $stmt->rowCount() > 0;
    }

    // ── MÉTODO: existeCorreoTelefono ─────────────────────────────────────

    // Líneas 21-29: Verifica si existe un usuario con ese correo O ese teléfono
    // Usado en el registro para evitar duplicados por ambos campos
    public function existeCorreoTelefono($correo, $telefono)
    {
        $stmt = $this->conn->prepare(
            "SELECT id_usuario FROM {$this->tabla}
             WHERE correo = :correo OR telefono = :telefono LIMIT 1"
        );
        $stmt->execute([':correo' => $correo, ':telefono' => $telefono]);
        return $stmt->rowCount() > 0;
    }

    // ── MÉTODO: obtenerPorEmail ──────────────────────────────────────────

    // Líneas 31-39: Retorna todos los datos del usuario por su correo
    // Usado en el proceso de login para obtener el hash de contraseña y verificarlo
    public function obtenerPorEmail($correo)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM {$this->tabla} WHERE correo = :correo LIMIT 1"
        );
        $stmt->execute([':correo' => $correo]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ── MÉTODO: validarRecuperacion ──────────────────────────────────────

    // Líneas 41-50: Verifica si existe una cuenta ACTIVA con ese correo o teléfono
    // El parámetro $metodo determina si busca por 'correo' o 'telefono'
    public function validarRecuperacion($valor, $metodo = 'correo')
    {
        // Determina la columna de búsqueda según el método elegido
        $campo = $metodo === 'telefono' ? 'telefono' : 'correo';
        $stmt  = $this->conn->prepare(
            "SELECT id_usuario FROM {$this->tabla}
             WHERE {$campo} = :valor AND activo = 1 LIMIT 1"
        );
        $stmt->execute([':valor' => $valor]);
        return $stmt->rowCount() > 0;
    }

    // ── MÉTODO: actualizarPassword ───────────────────────────────────────

    // Líneas 52-63: Actualiza la contraseña del usuario y resetea el bloqueo
    // Busca el usuario por correo O teléfono según el método indicado
    public function actualizarPassword($valor, $nuevaPasswordHash, $metodo = 'correo')
    {
        $campo = $metodo === 'telefono' ? 'telefono' : 'correo';
        $stmt  = $this->conn->prepare(
            "UPDATE {$this->tabla}
             SET `password` = :password,
                 intentos_fallidos = 0,          -- Resetea el contador de intentos
                 bloqueado_hasta = NULL           -- Quita el bloqueo temporal
             WHERE {$campo} = :valor"
        );
        return $stmt->execute([':password' => $nuevaPasswordHash, ':valor' => $valor]);
    }

    // ── MÉTODO: registrarIntentoFallido ──────────────────────────────────

    // Líneas 65-77: Incrementa el contador de intentos fallidos
    // Si llega a 5, bloquea la cuenta por 15 minutos usando lógica SQL condicional
    public function registrarIntentoFallido($id)
    {
        $stmt = $this->conn->prepare(
            "UPDATE {$this->tabla}
             SET intentos_fallidos = IFNULL(intentos_fallidos, 0) + 1,
                 bloqueado_hasta = IF(
                     IFNULL(intentos_fallidos, 0) + 1 >= 5,
                     DATE_ADD(NOW(), INTERVAL 15 MINUTE),  -- Bloquea 15 min si llega a 5
                     bloqueado_hasta
                 )
             WHERE id_usuario = :id"
        );
        $stmt->execute([':id' => $id]);
    }

    // ── MÉTODO: resetearIntentos ─────────────────────────────────────────

    // Líneas 79-86: Limpia el contador de intentos y quita el bloqueo tras login exitoso
    public function resetearIntentos($id)
    {
        $stmt = $this->conn->prepare(
            "UPDATE {$this->tabla}
             SET intentos_fallidos = 0, bloqueado_hasta = NULL
             WHERE id_usuario = :id"
        );
        $stmt->execute([':id' => $id]);
    }

    // ── MÉTODO: registrar ────────────────────────────────────────────────

    // Líneas 88-105: Inserta un nuevo usuario con rol 3 (Cliente) en la tabla
    // Retorna true si fue exitoso o un string con el mensaje de error
    public function registrar($datos)
    {
        try {
            $stmt = $this->conn->prepare(
                "INSERT INTO {$this->tabla} (nombres, apellidos, `password`, correo, telefono, id_rol)
                 VALUES (:nombres, :apellidos, :password, :correo, :telefono, :id_rol)"
            );
            $stmt->execute([
                ':nombres'   => $datos['nombre'],
                ':apellidos' => $datos['apellidos'],
                ':password'  => $datos['password'],    // Ya debe llegar hasheado
                ':correo'    => $datos['correo'],
                ':telefono'  => $datos['telefono'],
                ':id_rol'    => 3,                     // Siempre cliente en auto-registro
            ]);
            return true;
        } catch (Exception $e) {
            return "Error al registrar: " . $e->getMessage();
        }
    }

    // ── MÉTODO: obtenerPorId ─────────────────────────────────────────────

    // Líneas 107-113: Retorna todos los datos de un usuario por su ID numérico
    public function obtenerPorId($id)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM {$this->tabla} WHERE id_usuario = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ── MÉTODO: obtenerTodos ─────────────────────────────────────────────

    // Líneas 115-135: Lista todos los usuarios con JOIN a la tabla roles
    // Acepta filtro opcional por rol ('administrador', 'empleado', 'cliente')
    public function obtenerTodos($rol = null)
    {
        if ($rol) {
            // Convierte nombre de rol a ID numérico usando match()
            $idRol = match($rol) {
                'administrador' => 1,
                'empleado'      => 2,
                'cliente'       => 3,
                default         => 3,
            };
            // Consulta filtrada por id_rol con JOIN a roles
            $stmt = $this->conn->prepare("SELECT u.*, r.nombre_rol AS rol_nombre FROM ... WHERE u.id_rol = :id_rol ORDER BY u.nombres");
            $stmt->execute([':id_rol' => $idRol]);
        } else {
            // Consulta sin filtro — todos los usuarios ordenados por nombre
            $stmt = $this->conn->query("SELECT u.*, r.nombre_rol AS rol_nombre FROM ... ORDER BY u.nombres");
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── MÉTODO: contarPorRol ─────────────────────────────────────────────

    // Líneas 137-146: Cuenta cuántos usuarios existen para un rol específico
    // Útil para los contadores del dashboard
    public function contarPorRol($rolNombre)
    {
        $idRol = match($rolNombre) {
            'administrador' => 1,
            'empleado'      => 2,
            default         => 3,
        };
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) FROM {$this->tabla} WHERE id_rol = :id_rol"
        );
        $stmt->execute([':id_rol' => $idRol]);
        return $stmt->fetchColumn();
    }
}
?>
```

## Resumen de funciones/métodos

| Método | Parámetros | Retorno | Descripción |
|--------|-----------|---------|-------------|
| `existeCorreo($correo)` | string | bool | Verifica si el correo ya está registrado |
| `existeCorreoTelefono($correo, $telefono)` | string, string | bool | Verifica duplicado por correo O teléfono |
| `obtenerPorEmail($correo)` | string | array\|false | Retorna datos completos del usuario por correo |
| `validarRecuperacion($valor, $metodo)` | string, string | bool | Verifica cuenta activa por correo o teléfono |
| `actualizarPassword($valor, $hash, $metodo)` | string, string, string | bool | Actualiza contraseña y resetea bloqueo |
| `registrarIntentoFallido($id)` | int | void | Incrementa contador y bloquea a los 5 intentos |
| `resetearIntentos($id)` | int | void | Limpia contador y bloqueo tras login exitoso |
| `registrar($datos)` | array | bool\|string | Inserta nuevo cliente; retorna true o mensaje de error |
| `obtenerPorId($id)` | int | array\|false | Retorna datos del usuario por ID |
| `obtenerTodos($rol)` | string\|null | array | Lista todos los usuarios con filtro opcional por rol |
| `contarPorRol($rolNombre)` | string | int | Cuenta usuarios de un rol específico |

## Flujo de ejecución
El modelo no tiene un flujo propio de ejecución; es invocado por los controladores. El patrón típico es:
1. El controlador crea una instancia: `$model = new Usuario($db)`.
2. Llama al método necesario pasando los datos del formulario o sesión.
3. El modelo ejecuta la consulta SQL parametrizada con PDO (prevención de SQL injection).
4. Retorna el resultado al controlador para que decida la siguiente acción.
