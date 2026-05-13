<?php
/**
 * Modelo Vehiculo
 * Tabla principal: vehiculo — referencia tabla cliente (NO persona)
 */
class Vehiculo
{
    private $conn;
    private $tabla = "vehiculo";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /* ─────────────────────────────────────────────
     *  CRUD VEHÍCULO
     * ───────────────────────────────────────────── */

    /**
     * Inserta un nuevo vehículo.
     * $datos: placa, marca, modelo, año|anio, id_cliente, id_estado_vehiculo
     */
    public function registrar(array $datos)
    {
        try {
            $sql = "INSERT INTO {$this->tabla}
                        (placa, marca, modelo, `año`, id_cliente, id_estado_vehiculo)
                    VALUES
                        (:placa, :marca, :modelo, :anio, :id_cliente, :id_estado)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':placa'     => strtoupper(trim($datos['placa'])),
                ':marca'     => trim($datos['marca']),
                ':modelo'    => trim($datos['modelo']),
                ':anio'      => $datos['año'] ?? ($datos['anio'] ?? ''),
                ':id_cliente'=> (int)$datos['id_cliente'],
                ':id_estado' => (int)($datos['id_estado_vehiculo'] ?? 1),
            ]);
            return true;
        } catch (Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }

    /**
     * Todos los vehículos con JOIN a cliente y estado_vehiculo.
     */
    public function obtenerTodos(): array
    {
        $sql = "SELECT v.*,
                       c.nombre   AS nombre_cliente,
                       c.correo   AS correo_cliente,
                       c.telefono AS telefono_cliente,
                       e.estado
                FROM {$this->tabla} v
                JOIN cliente        c ON v.id_cliente        = c.id_cliente
                JOIN estado_vehiculo e ON v.id_estado_vehiculo = e.id_estado_vehiculo
                ORDER BY v.id_vehiculo DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Vehículos de un cliente específico (por id_cliente de tabla cliente).
     */
    public function obtenerPorCliente(int $idCliente): array
    {
        $sql = "SELECT v.*,
                       c.nombre   AS nombre_cliente,
                       e.estado
                FROM {$this->tabla} v
                JOIN cliente        c ON v.id_cliente        = c.id_cliente
                JOIN estado_vehiculo e ON v.id_estado_vehiculo = e.id_estado_vehiculo
                WHERE v.id_cliente = :id
                ORDER BY v.id_vehiculo DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $idCliente]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Un vehículo por ID con JOINs.
     */
    public function obtenerPorId(int $id): array|false
    {
        $sql = "SELECT v.*,
                       c.nombre   AS nombre_cliente,
                       c.correo   AS correo_cliente,
                       e.estado
                FROM {$this->tabla} v
                JOIN cliente        c ON v.id_cliente        = c.id_cliente
                JOIN estado_vehiculo e ON v.id_estado_vehiculo = e.id_estado_vehiculo
                WHERE v.id_vehiculo = :id
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar por placa exacta.
     */
    public function obtenerPorPlaca(string $placa): array|false
    {
        $sql = "SELECT v.*,
                       c.nombre AS nombre_cliente,
                       e.estado
                FROM {$this->tabla} v
                JOIN cliente        c ON v.id_cliente        = c.id_cliente
                JOIN estado_vehiculo e ON v.id_estado_vehiculo = e.id_estado_vehiculo
                WHERE v.placa = :placa
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':placa' => strtoupper(trim($placa))]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Verifica si una placa ya existe (opcionalmente excluyendo un ID).
     */
    public function existePlaca(string $placa, ?int $excluirId = null): bool
    {
        $sql = "SELECT id_vehiculo FROM {$this->tabla} WHERE placa = :placa";
        if ($excluirId) {
            $sql .= " AND id_vehiculo != :excluir";
        }
        $stmt = $this->conn->prepare($sql);
        $params = [':placa' => strtoupper(trim($placa))];
        if ($excluirId) {
            $params[':excluir'] = $excluirId;
        }
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    /**
     * Actualiza datos del vehículo.
     * $datos: marca, modelo, año|anio, id_estado_vehiculo (opcional)
     */
    public function actualizar(int $id, array $datos)
    {
        try {
            $sets   = "marca=:marca, modelo=:modelo, `año`=:anio";
            $params = [
                ':marca'  => trim($datos['marca']),
                ':modelo' => trim($datos['modelo']),
                ':anio'   => $datos['año'] ?? ($datos['anio'] ?? ''),
                ':id'     => $id,
            ];
            if (!empty($datos['id_estado_vehiculo'])) {
                $sets .= ", id_estado_vehiculo=:id_estado";
                $params[':id_estado'] = (int)$datos['id_estado_vehiculo'];
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
    public function cambiarEstado(int $id, int $idEstado)
    {
        try {
            $this->conn->prepare(
                "UPDATE {$this->tabla} SET id_estado_vehiculo=:estado WHERE id_vehiculo=:id"
            )->execute([':estado' => $idEstado, ':id' => $id]);
            return true;
        } catch (Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }

    /* ─────────────────────────────────────────────
     *  HISTORIAL
     * ───────────────────────────────────────────── */

    /**
     * Historial completo de un vehículo (historial_vehiculo + historial_servicio).
     */
    public function obtenerHistorial(int $idVehiculo): array
    {
        $sql = "SELECT hv.*,
                       p.nombre AS nombre_empleado,
                       hs.descripcion   AS serv_descripcion,
                       hs.tipo_reparacion AS serv_tipo,
                       s.nombre         AS nombre_servicio
                FROM historial_vehiculo hv
                LEFT JOIN empleado emp ON hv.id_empleado = emp.id_empleado
                LEFT JOIN persona  p   ON emp.id_persona = p.id_persona
                LEFT JOIN historial_servicio hs ON hs.id_historial_vehiculo = hv.id_historial_vehiculo
                LEFT JOIN servicio s ON hs.id_servicio = s.id_servicio
                WHERE hv.id_vehiculo = :id
                ORDER BY hv.fecha_registro DESC, hv.id_historial_vehiculo DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $idVehiculo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Inserta una entrada en historial_vehiculo.
     * $datos: descripcion, fecha_registro, tipo_reparacion, id_empleado, id_vehiculo
     */
    public function agregarHistorial(array $datos)
    {
        try {
            $sql = "INSERT INTO historial_vehiculo
                        (descripcion, fecha_registro, tipo_reparacion, id_empleado, id_vehiculo)
                    VALUES
                        (:descripcion, :fecha, :tipo, :id_empleado, :id_vehiculo)";
            $this->conn->prepare($sql)->execute([
                ':descripcion' => trim($datos['descripcion']),
                ':fecha'       => $datos['fecha_registro'],
                ':tipo'        => $datos['tipo_reparacion'],
                ':id_empleado' => (int)$datos['id_empleado'],
                ':id_vehiculo' => (int)$datos['id_vehiculo'],
            ]);
            return true;
        } catch (Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }

    /* ─────────────────────────────────────────────
     *  UTILIDADES
     * ───────────────────────────────────────────── */

    /** Total de vehículos registrados. */
    public function contar(): int
    {
        return (int)$this->conn->query("SELECT COUNT(*) FROM {$this->tabla}")->fetchColumn();
    }

    /** Todos los estados disponibles. */
    public function obtenerEstados(): array
    {
        return $this->conn->query("SELECT * FROM estado_vehiculo ORDER BY id_estado_vehiculo")
                          ->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Filtra vehículos por nombre de estado.
     * $filtro: 'Pendiente' | 'En reparación' | 'Finalizado' | 'Pintura' | etc.
     */
    public function obtenerPorFiltro(string $filtro): array
    {
        $sql = "SELECT v.*,
                       c.nombre AS nombre_cliente,
                       e.estado
                FROM {$this->tabla} v
                JOIN cliente        c ON v.id_cliente        = c.id_cliente
                JOIN estado_vehiculo e ON v.id_estado_vehiculo = e.id_estado_vehiculo
                WHERE e.estado = :filtro
                ORDER BY v.id_vehiculo DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':filtro' => $filtro]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ─────────────────────────────────────────────
     *  HELPERS CLIENTE
     * ───────────────────────────────────────────── */

    /**
     * Busca el id_cliente en tabla cliente por correo.
     * Retorna el id_cliente o null si no existe.
     */
    public function buscarIdClientePorCorreo(string $correo): ?int
    {
        $stmt = $this->conn->prepare(
            "SELECT id_cliente FROM cliente WHERE correo = :correo LIMIT 1"
        );
        $stmt->execute([':correo' => $correo]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['id_cliente'] : null;
    }

    /**
     * Crea un registro en tabla cliente si no existe.
     * Retorna el id_cliente.
     */
    public function crearClienteDesdePersona(array $datos): int
    {
        $this->conn->prepare(
            "INSERT INTO cliente (nombre, correo, telefono, direccion)
             VALUES (:nombre, :correo, :telefono, '')"
        )->execute([
            ':nombre'   => $datos['nombre'],
            ':correo'   => $datos['correo'],
            ':telefono' => $datos['telefono'] ?? '',
        ]);
        return (int)$this->conn->lastInsertId();
    }
}
?>
