<?php
/**
 * Modelo Vehiculo — schema nuevo
 * Tabla: vehiculos  (PK: id_vehiculo)
 * Estado: id_estado → estado_vehiculo.id_estado_vehiculo
 * Cliente: id_cliente → clientes.id_cliente
 * Historial: historial_vehiculo.id_usuario → usuarios.id_usuario
 */
class Vehiculo
{
    private $conn;
    private $tabla = "vehiculos";   // ← tabla correcta del schema nuevo

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /* ══════════════════════════════════════════════
     *  CRUD VEHÍCULO
     * ══════════════════════════════════════════════ */

    /**
     * Inserta un nuevo vehículo.
     * Columnas reales: placa, marca, modelo, anio, color, id_cliente, id_estado
     */
    public function registrar(array $datos): bool|string
    {
        try {
            $this->conn->prepare(
                "INSERT INTO {$this->tabla}
                    (placa, marca, modelo, anio, color, id_cliente, id_estado)
                 VALUES
                    (:placa, :marca, :modelo, :anio, :color, :id_cliente, :id_estado)"
            )->execute([
                ':placa'      => strtoupper(trim($datos['placa'])),
                ':marca'      => trim($datos['marca']),
                ':modelo'     => trim($datos['modelo']),
                ':anio'       => $datos['anio'] ?? ($datos['año'] ?? null),
                ':color'      => trim($datos['color'] ?? ''),
                ':id_cliente' => (int)$datos['id_cliente'],
                ':id_estado'  => (int)($datos['id_estado_vehiculo'] ?? $datos['id_estado'] ?? 1),
            ]);
            return true;
        } catch (Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }

    /**
     * Todos los vehículos con JOIN a clientes y estado_vehiculo.
     */
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
             ORDER BY v.id_vehiculo DESC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Vehículos de un cliente específico.
     */
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

    /**
     * Un vehículo por ID con JOINs.
     */
    public function obtenerPorId(int $id): array|false
    {
        $stmt = $this->conn->prepare(
            "SELECT v.*,
                    CONCAT(c.nombres, ' ', COALESCE(c.apellidos,'')) AS nombre_cliente,
                    c.correo AS correo_cliente,
                    ev.estado
             FROM {$this->tabla} v
             LEFT JOIN clientes       c  ON v.id_cliente = c.id_cliente
             LEFT JOIN estado_vehiculo ev ON v.id_estado  = ev.id_estado_vehiculo
             WHERE v.id_vehiculo = :id
             LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar por placa exacta.
     */
    public function obtenerPorPlaca(string $placa): array|false
    {
        $stmt = $this->conn->prepare(
            "SELECT v.*,
                    CONCAT(c.nombres, ' ', COALESCE(c.apellidos,'')) AS nombre_cliente,
                    ev.estado
             FROM {$this->tabla} v
             LEFT JOIN clientes       c  ON v.id_cliente = c.id_cliente
             LEFT JOIN estado_vehiculo ev ON v.id_estado  = ev.id_estado_vehiculo
             WHERE v.placa = :placa
             LIMIT 1"
        );
        $stmt->execute([':placa' => strtoupper(trim($placa))]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Verifica si una placa ya existe.
     */
    public function existePlaca(string $placa, ?int $excluirId = null): bool
    {
        $sql    = "SELECT id_vehiculo FROM {$this->tabla} WHERE placa = :placa";
        $params = [':placa' => strtoupper(trim($placa))];
        if ($excluirId) {
            $sql .= " AND id_vehiculo != :excluir";
            $params[':excluir'] = $excluirId;
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    /**
     * Actualiza datos del vehículo.
     */
    public function actualizar(int $id, array $datos): bool|string
    {
        try {
            $sets   = "marca=:marca, modelo=:modelo, anio=:anio, color=:color";
            $params = [
                ':marca'  => trim($datos['marca']),
                ':modelo' => trim($datos['modelo']),
                ':anio'   => $datos['anio'] ?? ($datos['año'] ?? null),
                ':color'  => trim($datos['color'] ?? ''),
                ':id'     => $id,
            ];
            if (!empty($datos['id_estado_vehiculo']) || !empty($datos['id_estado'])) {
                $sets .= ", id_estado=:id_estado";
                $params[':id_estado'] = (int)($datos['id_estado_vehiculo'] ?? $datos['id_estado']);
            }
            $this->conn->prepare("UPDATE {$this->tabla} SET {$sets} WHERE id_vehiculo=:id")
                       ->execute($params);
            return true;
        } catch (Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }

    /**
     * Cambia solo el estado de un vehículo.
     */
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

    /* ══════════════════════════════════════════════
     *  HISTORIAL
     * ══════════════════════════════════════════════ */

    /**
     * Historial completo de un vehículo.
     * historial_vehiculo.id_usuario → usuarios (schema nuevo, sin tabla empleado)
     */
    public function obtenerHistorial(int $idVehiculo): array
    {
        try {
            $stmt = $this->conn->prepare(
                "SELECT hv.*,
                        u.nombres AS nombre_empleado
                 FROM historial_vehiculo hv
                 LEFT JOIN usuarios u ON hv.id_usuario = u.id_usuario
                 WHERE hv.id_vehiculo = :id
                 ORDER BY hv.fecha_registro DESC, hv.id_historial DESC"
            );
            $stmt->execute([':id' => $idVehiculo]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Inserta una entrada en historial_vehiculo.
     * Usa id_usuario (no id_empleado).
     */
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
                ':id_orden'    => $datos['id_orden'] ?? null,
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

    /* ══════════════════════════════════════════════
     *  UTILIDADES
     * ══════════════════════════════════════════════ */

    public function contar(): int
    {
        return (int)$this->conn->query("SELECT COUNT(*) FROM {$this->tabla}")->fetchColumn();
    }

    /** Todos los estados disponibles. */
    public function obtenerEstados(): array
    {
        try {
            return $this->conn->query(
                "SELECT * FROM estado_vehiculo ORDER BY id_estado_vehiculo"
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Filtra vehículos por nombre de estado.
     */
    public function obtenerPorFiltro(string $filtro): array
    {
        $stmt = $this->conn->prepare(
            "SELECT v.*,
                    CONCAT(c.nombres, ' ', COALESCE(c.apellidos,'')) AS nombre_cliente,
                    ev.estado
             FROM {$this->tabla} v
             LEFT JOIN clientes       c  ON v.id_cliente = c.id_cliente
             LEFT JOIN estado_vehiculo ev ON v.id_estado  = ev.id_estado_vehiculo
             WHERE ev.estado = :filtro
             ORDER BY v.id_vehiculo DESC"
        );
        $stmt->execute([':filtro' => $filtro]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ══════════════════════════════════════════════
     *  HELPERS CLIENTE
     * ══════════════════════════════════════════════ */

    /**
     * Busca el id_cliente en tabla clientes por correo.
     */
    public function buscarIdClientePorCorreo(string $correo): ?int
    {
        $stmt = $this->conn->prepare(
            "SELECT id_cliente FROM clientes WHERE correo = :correo LIMIT 1"
        );
        $stmt->execute([':correo' => $correo]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['id_cliente'] : null;
    }

    /**
     * Crea un registro en tabla clientes si no existe.
     * Retorna el id_cliente.
     */
    public function crearClienteDesdePersona(array $datos): int
    {
        $this->conn->prepare(
            "INSERT INTO clientes (nombres, apellidos, correo, telefono)
             VALUES (:nombres, :apellidos, :correo, :telefono)"
        )->execute([
            ':nombres'   => $datos['nombres']   ?? ($datos['nombre']   ?? ''),
            ':apellidos' => $datos['apellidos'] ?? '',
            ':correo'    => $datos['correo'],
            ':telefono'  => $datos['telefono']  ?? '',
        ]);
        return (int)$this->conn->lastInsertId();
    }
}
?>
