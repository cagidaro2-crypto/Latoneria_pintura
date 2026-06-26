# Documentación: Vehiculo.php

## Descripción general
Modelo de la entidad Vehículo. Encapsula todas las operaciones CRUD sobre la tabla `vehiculos` y su historial de servicios en `historial_vehiculo`. Realiza JOINs automáticos con `clientes` y `estado_vehiculo` para retornar datos completos. Incluye utilidades como búsqueda por placa, verificación de duplicados y gestión del historial de reparaciones.

## Dependencias
- Clase `PDO` (PHP nativo) — Recibida por inyección de dependencias en el constructor

## Código documentado línea por línea

```php
// Líneas 1-7: Bloque de comentario PHPDoc que describe el schema de la BD relacionado
<?php
/**
 * Modelo Vehiculo — schema nuevo
 * Tabla: vehiculos  (PK: id_vehiculo)
 * Estado: id_estado → estado_vehiculo.id_estado_vehiculo
 * Cliente: id_cliente → clientes.id_cliente
 * Historial: historial_vehiculo.id_usuario → usuarios.id_usuario
 */

// Línea 8: Definición de la clase
class Vehiculo
{
    // Línea 10: Almacena la conexión PDO (privada)
    private $conn;

    // Línea 11: Nombre de la tabla principal
    private $tabla = "vehiculos";

    // Líneas 13-16: Constructor — recibe y almacena la conexión PDO
    public function __construct($db)
    {
        $this->conn = $db;
    }

    // ═══════════════════════════════════════════════════════════════════
    //  SECCIÓN: CRUD VEHÍCULO
    // ═══════════════════════════════════════════════════════════════════

    // ── MÉTODO: registrar ────────────────────────────────────────────────

    // Líneas 22-40: Inserta un nuevo vehículo en la tabla vehiculos
    // Normaliza la placa a mayúsculas y hace trim a todos los strings
    // Retorna true si fue exitoso o un string con el error
    public function registrar(array $datos): bool|string
    {
        try {
            $this->conn->prepare(
                "INSERT INTO {$this->tabla}
                    (placa, marca, modelo, anio, color, id_cliente, id_estado)
                 VALUES
                    (:placa, :marca, :modelo, :anio, :color, :id_cliente, :id_estado)"
            )->execute([
                ':placa'      => strtoupper(trim($datos['placa'])),  // Placa siempre en mayúsculas
                ':marca'      => trim($datos['marca']),
                ':modelo'     => trim($datos['modelo']),
                ':anio'       => $datos['anio'] ?? ($datos['año'] ?? null),  // Acepta 'anio' o 'año'
                ':color'      => trim($datos['color'] ?? ''),
                ':id_cliente' => (int)$datos['id_cliente'],
                ':id_estado'  => (int)($datos['id_estado_vehiculo'] ?? $datos['id_estado'] ?? 1),
            ]);
            return true;
        } catch (Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }

    // ── MÉTODO: obtenerTodos ─────────────────────────────────────────────

    // Líneas 42-58: Retorna todos los vehículos con datos del cliente y estado
    // JOIN a clientes para el nombre completo, correo y teléfono del propietario
    // JOIN a estado_vehiculo para el texto descriptivo del estado
    public function obtenerTodos(): array
    {
        $stmt = $this->conn->prepare(
            "SELECT v.*,
                    CONCAT(c.nombres, ' ', COALESCE(c.apellidos,'')) AS nombre_cliente,
                    c.correo   AS correo_cliente,
                    c.telefono AS telefono_cliente,
                    ev.estado
             FROM {$this->tabla} v
             LEFT JOIN clientes       c  ON v.id_cliente = c.id_cliente
             LEFT JOIN estado_vehiculo ev ON v.id_estado  = ev.id_estado_vehiculo
             ORDER BY v.id_vehiculo DESC"  -- Más recientes primero
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── MÉTODO: obtenerPorCliente ────────────────────────────────────────

    // Líneas 60-74: Retorna solo los vehículos de un cliente específico
    // Usado en el dashboard del cliente para mostrar sus propios vehículos
    public function obtenerPorCliente(int $idCliente): array
    {
        $stmt = $this->conn->prepare(
            "SELECT v.*,
                    CONCAT(c.nombres, ' ', COALESCE(c.apellidos,'')) AS nombre_cliente,
                    ev.estado
             FROM {$this->tabla} v
             LEFT JOIN clientes       c  ON v.id_cliente = c.id_cliente
             LEFT JOIN estado_vehiculo ev ON v.id_estado  = ev.id_estado_vehiculo
             WHERE v.id_cliente = :id
             ORDER BY v.id_vehiculo DESC"
        );
        $stmt->execute([':id' => $idCliente]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── MÉTODO: obtenerPorId ─────────────────────────────────────────────

    // Líneas 76-89: Retorna un vehículo específico con todos sus JOINs
    // Retorna false si no existe el vehículo
    public function obtenerPorId(int $id): array|false
    {
        $stmt = $this->conn->prepare(
            "SELECT v.*, CONCAT(c.nombres, ' ', COALESCE(c.apellidos,'')) AS nombre_cliente,
                    c.correo AS correo_cliente, ev.estado
             FROM {$this->tabla} v
             LEFT JOIN clientes c ON v.id_cliente = c.id_cliente
             LEFT JOIN estado_vehiculo ev ON v.id_estado = ev.id_estado_vehiculo
             WHERE v.id_vehiculo = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ── MÉTODO: obtenerPorPlaca ──────────────────────────────────────────

    // Líneas 91-103: Busca un vehículo por su placa (búsqueda exacta, normaliza a mayúsculas)
    public function obtenerPorPlaca(string $placa): array|false
    {
        $stmt = $this->conn->prepare(
            "SELECT v.*, CONCAT(c.nombres, ' ', COALESCE(c.apellidos,'')) AS nombre_cliente, ev.estado
             FROM {$this->tabla} v
             LEFT JOIN clientes c ON v.id_cliente = c.id_cliente
             LEFT JOIN estado_vehiculo ev ON v.id_estado = ev.id_estado_vehiculo
             WHERE v.placa = :placa LIMIT 1"
        );
        $stmt->execute([':placa' => strtoupper(trim($placa))]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ── MÉTODO: existePlaca ──────────────────────────────────────────────

    // Líneas 105-115: Verifica si una placa ya está registrada
    // $excluirId permite excluir el vehículo actual al editar (evita falsos positivos)
    public function existePlaca(string $placa, ?int $excluirId = null): bool
    {
        $sql    = "SELECT id_vehiculo FROM {$this->tabla} WHERE placa = :placa";
        $params = [':placa' => strtoupper(trim($placa))];
        if ($excluirId) {
            $sql .= " AND id_vehiculo != :excluir";  // Excluye el propio vehículo
            $params[':excluir'] = $excluirId;
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    // ── MÉTODO: actualizar ───────────────────────────────────────────────

    // Líneas 117-134: Actualiza los datos básicos de un vehículo
    // Incluye el estado si se proporcionó en los datos
    public function actualizar(int $id, array $datos): bool|string
    {
        try {
            $sets   = "marca=:marca, modelo=:modelo, anio=:anio, color=:color";
            $params = [':marca'=>..., ':modelo'=>..., ':anio'=>..., ':color'=>..., ':id'=>$id];
            if (!empty($datos['id_estado_vehiculo']) || !empty($datos['id_estado'])) {
                $sets .= ", id_estado=:id_estado";  // Agrega estado dinámicamente si existe
                $params[':id_estado'] = (int)($datos['id_estado_vehiculo'] ?? $datos['id_estado']);
            }
            $this->conn->prepare("UPDATE {$this->tabla} SET {$sets} WHERE id_vehiculo=:id")->execute($params);
            return true;
        } catch (Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }

    // ── MÉTODO: cambiarEstado ────────────────────────────────────────────

    // Líneas 136-144: Actualiza solo el campo id_estado del vehículo
    // Más eficiente que actualizar todos los campos cuando solo cambia el estado
    public function cambiarEstado(int $id, int $idEstado): bool|string
    {
        try {
            $this->conn->prepare(
                "UPDATE {$this->tabla} SET id_estado=:estado WHERE id_vehiculo=:id"
            )->execute([':estado' => $idEstado, ':id' => $id]);
            return true;
        } catch (Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    //  SECCIÓN: HISTORIAL
    // ═══════════════════════════════════════════════════════════════════

    // ── MÉTODO: obtenerHistorial ─────────────────────────────────────────

    // Líneas 147-161: Retorna el historial completo de servicios de un vehículo
    // JOIN a usuarios para mostrar el nombre del empleado que realizó el trabajo
    // Ordenado por fecha más reciente primero
    public function obtenerHistorial(int $idVehiculo): array
    {
        try {
            $stmt = $this->conn->prepare(
                "SELECT hv.*, u.nombres AS nombre_empleado
                 FROM historial_vehiculo hv
                 LEFT JOIN usuarios u ON hv.id_usuario = u.id_usuario
                 WHERE hv.id_vehiculo = :id
                 ORDER BY hv.fecha_registro DESC, hv.id_historial DESC"
            );
            $stmt->execute([':id' => $idVehiculo]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];  // Retorna array vacío si hay error (no interrumpe la vista)
        }
    }

    // ── MÉTODO: agregarHistorial ─────────────────────────────────────────

    // Líneas 163-181: Inserta una entrada en historial_vehiculo
    // Acepta id_usuario o id_empleado para compatibilidad con llamadas antiguas
    public function agregarHistorial(array $datos): bool|string
    {
        try {
            $this->conn->prepare(
                "INSERT INTO historial_vehiculo
                    (id_vehiculo, id_orden, id_usuario, fecha_registro, tipo_reparacion, descripcion)
                 VALUES
                    (:id_vehiculo, :id_orden, :id_usuario, :fecha, :tipo, :descripcion)"
            )->execute([
                ':id_vehiculo' => (int)$datos['id_vehiculo'],
                ':id_orden'    => $datos['id_orden'] ?? null,     // Opcional: orden de servicio relacionada
                ':id_usuario'  => (int)($datos['id_usuario'] ?? $datos['id_empleado'] ?? 0),
                ':fecha'       => $datos['fecha_registro'],
                ':tipo'        => trim($datos['tipo_reparacion']),
                ':descripcion' => trim($datos['descripcion']),
            ]);
            return true;
        } catch (Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    //  SECCIÓN: UTILIDADES
    // ═══════════════════════════════════════════════════════════════════

    // Líneas 183-185: Cuenta el total de vehículos registrados (para el dashboard)
    public function contar(): int
    {
        return (int)$this->conn->query("SELECT COUNT(*) FROM {$this->tabla}")->fetchColumn();
    }

    // Líneas 187-193: Retorna todos los estados disponibles de la tabla estado_vehiculo
    public function obtenerEstados(): array
    {
        try {
            return $this->conn->query("SELECT * FROM estado_vehiculo ORDER BY id_estado_vehiculo")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    // Líneas 195-208: Filtra vehículos por el texto del estado (Ej: "En reparación")
    public function obtenerPorFiltro(string $filtro): array { ... }

    // ── HELPERS CLIENTE ──────────────────────────────────────────────────

    // Líneas 210-218: Busca el id_cliente en la tabla clientes por correo electrónico
    // Retorna null si no existe ningún cliente con ese correo
    public function buscarIdClientePorCorreo(string $correo): ?int
    {
        $stmt = $this->conn->prepare("SELECT id_cliente FROM clientes WHERE correo = :correo LIMIT 1");
        $stmt->execute([':correo' => $correo]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['id_cliente'] : null;
    }

    // Líneas 220-231: Crea un registro en la tabla clientes desde los datos del usuario
    // Se usa cuando un usuario se registra y aún no tiene entrada en la tabla clientes
    public function crearClienteDesdePersona(array $datos): int
    {
        $this->conn->prepare(
            "INSERT INTO clientes (nombres, apellidos, correo, telefono) VALUES ..."
        )->execute([...]);
        return (int)$this->conn->lastInsertId();
    }
}
?>
```

## Resumen de funciones/métodos

| Método | Parámetros | Retorno | Descripción |
|--------|-----------|---------|-------------|
| `registrar(array $datos)` | array | bool\|string | Inserta nuevo vehículo con placa normalizada |
| `obtenerTodos()` | — | array | Lista todos los vehículos con JOINs |
| `obtenerPorCliente(int $idCliente)` | int | array | Vehículos de un cliente específico |
| `obtenerPorId(int $id)` | int | array\|false | Un vehículo por ID con JOINs |
| `obtenerPorPlaca(string $placa)` | string | array\|false | Búsqueda exacta por placa |
| `existePlaca(string $placa, ?int $excluirId)` | string, int? | bool | Verifica si la placa existe |
| `actualizar(int $id, array $datos)` | int, array | bool\|string | Edita datos del vehículo |
| `cambiarEstado(int $id, int $idEstado)` | int, int | bool\|string | Cambia solo el estado |
| `obtenerHistorial(int $idVehiculo)` | int | array | Historial de servicios del vehículo |
| `agregarHistorial(array $datos)` | array | bool\|string | Registra un trabajo en el historial |
| `contar()` | — | int | Total de vehículos en el sistema |
| `obtenerEstados()` | — | array | Lista de estados disponibles |
| `buscarIdClientePorCorreo(string $correo)` | string | int\|null | Busca id_cliente por correo |
| `crearClienteDesdePersona(array $datos)` | array | int | Crea entrada en tabla clientes |

## Flujo de ejecución
El modelo es instanciado por los controladores que lo necesiten:
1. `$vModel = new Vehiculo($db)` — crea la instancia con la conexión activa.
2. Según la operación requerida, se llama al método apropiado.
3. Todos los métodos usan consultas PDO preparadas (prevención de SQL injection).
4. Los métodos de escritura retornan `true` o un string de error; los de lectura retornan arrays o false.
5. Los errores se capturan con try/catch y se retorna un valor seguro (array vacío o false) para no interrumpir la vista.
