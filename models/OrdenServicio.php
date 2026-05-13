<?php
/**
 * OrdenServicio — trabaja sobre la tabla historial_vehiculo de latoneria_pintura
 * ya que no existe tabla ordenes_servicio en la BD real.
 * Usamos historial_vehiculo como registro de órdenes de trabajo.
 */
class OrdenServicio
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /* ─────────────────────────────────────────────────────────────────────────
     *  CREAR ORDEN  (inserta en historial_vehiculo)
     * ───────────────────────────────────────────────────────────────────────── */
    public function crear(array $datos)
    {
        try {
            // Número de orden: ORD-YYYYMMDD-XXXX
            $numero = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

            $this->conn->prepare(
                "INSERT INTO historial_vehiculo
                    (descripcion, fecha_registro, tipo_reparacion, id_empleado, id_vehiculo)
                 VALUES
                    (:descripcion, CURDATE(), :tipo, :id_empleado, :id_vehiculo)"
            )->execute([
                ':descripcion' => ($datos['descripcion'] ?: 'Orden: ' . $datos['tipo_servicio']),
                ':tipo'        => $datos['tipo_servicio'],
                ':id_empleado' => $datos['id_empleado'] ?? null,
                ':id_vehiculo' => (int)$datos['id_vehiculo'],
            ]);

            return true;
        } catch (Exception $e) {
            return "Error al crear orden: " . $e->getMessage();
        }
    }

    /* ─────────────────────────────────────────────────────────────────────────
     *  OBTENER TODAS LAS ÓRDENES
     * ───────────────────────────────────────────────────────────────────────── */
    public function obtenerTodas(): array
    {
        $sql = "SELECT
                    hv.id_historial_vehiculo  AS id_orden,
                    CONCAT('ORD-', LPAD(hv.id_historial_vehiculo, 4, '0')) AS numero_orden,
                    hv.descripcion,
                    hv.fecha_registro         AS fecha_ingreso,
                    hv.tipo_reparacion        AS tipo_servicio,
                    v.id_vehiculo,
                    v.placa,
                    v.marca,
                    v.modelo,
                    c.nombre                  AS cliente_nombre,
                    c.correo                  AS cliente_correo,
                    p.nombre                  AS empleado_nombre,
                    e.id_empleado,
                    ev.estado
                FROM historial_vehiculo hv
                JOIN vehiculo           v  ON hv.id_vehiculo  = v.id_vehiculo
                JOIN cliente            c  ON v.id_cliente    = c.id_cliente
                LEFT JOIN empleado      e  ON hv.id_empleado  = e.id_empleado
                LEFT JOIN persona       p  ON e.id_persona    = p.id_persona
                JOIN estado_vehiculo    ev ON v.id_estado_vehiculo = ev.id_estado_vehiculo
                ORDER BY hv.fecha_registro DESC, hv.id_historial_vehiculo DESC";

        return $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ─────────────────────────────────────────────────────────────────────────
     *  OBTENER POR CLIENTE
     * ───────────────────────────────────────────────────────────────────────── */
    public function obtenerPorCliente(int $idCliente): array
    {
        $sql = "SELECT
                    hv.id_historial_vehiculo AS id_orden,
                    CONCAT('ORD-', LPAD(hv.id_historial_vehiculo, 4, '0')) AS numero_orden,
                    hv.descripcion,
                    hv.fecha_registro        AS fecha_ingreso,
                    hv.tipo_reparacion       AS tipo_servicio,
                    v.placa, v.marca, v.modelo,
                    ev.estado
                FROM historial_vehiculo hv
                JOIN vehiculo        v  ON hv.id_vehiculo = v.id_vehiculo
                JOIN cliente         c  ON v.id_cliente   = c.id_cliente
                JOIN estado_vehiculo ev ON v.id_estado_vehiculo = ev.id_estado_vehiculo
                WHERE c.id_cliente = :id
                ORDER BY hv.fecha_registro DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $idCliente]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ─────────────────────────────────────────────────────────────────────────
     *  OBTENER POR ID
     * ───────────────────────────────────────────────────────────────────────── */
    public function obtenerPorId(int $id): array|false
    {
        $sql = "SELECT
                    hv.*,
                    CONCAT('ORD-', LPAD(hv.id_historial_vehiculo, 4, '0')) AS numero_orden,
                    v.placa, v.marca, v.modelo,
                    c.nombre AS cliente_nombre,
                    p.nombre AS empleado_nombre,
                    ev.estado
                FROM historial_vehiculo hv
                JOIN vehiculo        v  ON hv.id_vehiculo = v.id_vehiculo
                JOIN cliente         c  ON v.id_cliente   = c.id_cliente
                LEFT JOIN empleado   e  ON hv.id_empleado = e.id_empleado
                LEFT JOIN persona    p  ON e.id_persona   = p.id_persona
                JOIN estado_vehiculo ev ON v.id_estado_vehiculo = ev.id_estado_vehiculo
                WHERE hv.id_historial_vehiculo = :id
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /* ─────────────────────────────────────────────────────────────────────────
     *  ACTUALIZAR ESTADO (cambia estado_vehiculo del vehículo asociado)
     * ───────────────────────────────────────────────────────────────────────── */
    public function actualizarEstado(int $idOrden, string $nuevoEstado, string $obs, $idResp): bool|string
    {
        try {
            // Obtener id_vehiculo de la orden
            $stmt = $this->conn->prepare(
                "SELECT id_vehiculo FROM historial_vehiculo WHERE id_historial_vehiculo = :id LIMIT 1"
            );
            $stmt->execute([':id' => $idOrden]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) return "Orden no encontrada.";

            // Buscar id_estado_vehiculo por nombre
            $stmtE = $this->conn->prepare(
                "SELECT id_estado_vehiculo FROM estado_vehiculo WHERE estado = :estado LIMIT 1"
            );
            $stmtE->execute([':estado' => $nuevoEstado]);
            $estado = $stmtE->fetch(PDO::FETCH_ASSOC);
            if (!$estado) return "Estado no válido.";

            // Actualizar estado del vehículo
            $this->conn->prepare(
                "UPDATE vehiculo SET id_estado_vehiculo = :id_estado WHERE id_vehiculo = :id_v"
            )->execute([
                ':id_estado' => $estado['id_estado_vehiculo'],
                ':id_v'      => $row['id_vehiculo'],
            ]);

            // Registrar en historial si hay observación
            if (!empty($obs)) {
                $this->conn->prepare(
                    "INSERT INTO historial_vehiculo (descripcion, fecha_registro, tipo_reparacion, id_empleado, id_vehiculo)
                     VALUES (:desc, CURDATE(), :tipo, :emp, :veh)"
                )->execute([
                    ':desc' => $obs,
                    ':tipo' => $nuevoEstado,
                    ':emp'  => $idResp ?: null,
                    ':veh'  => $row['id_vehiculo'],
                ]);
            }

            return true;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /* ─────────────────────────────────────────────────────────────────────────
     *  REASIGNAR EMPLEADO
     * ───────────────────────────────────────────────────────────────────────── */
    public function reasignarEmpleado(int $idOrden, ?int $idEmpleado): bool|string
    {
        try {
            $this->conn->prepare(
                "UPDATE historial_vehiculo SET id_empleado = :emp WHERE id_historial_vehiculo = :id"
            )->execute([':emp' => $idEmpleado, ':id' => $idOrden]);
            return true;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /* ─────────────────────────────────────────────────────────────────────────
     *  HELPERS
     * ───────────────────────────────────────────────────────────────────────── */
    public function tieneOrdenActiva(int $idVehiculo): bool
    {
        // Considera activa si el vehículo NO está en estado Finalizado/Entregado
        $stmt = $this->conn->prepare(
            "SELECT v.id_vehiculo
             FROM vehiculo v
             JOIN estado_vehiculo ev ON v.id_estado_vehiculo = ev.id_estado_vehiculo
             WHERE v.id_vehiculo = :id
               AND ev.estado NOT IN ('Finalizado','Entregado')
             LIMIT 1"
        );
        $stmt->execute([':id' => $idVehiculo]);
        return $stmt->rowCount() > 0;
    }

    public function contarPorEstado(string $estado): int
    {
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) FROM vehiculo v
             JOIN estado_vehiculo ev ON v.id_estado_vehiculo = ev.id_estado_vehiculo
             WHERE ev.estado = :estado"
        );
        $stmt->execute([':estado' => $estado]);
        return (int)$stmt->fetchColumn();
    }

    public function contar(): int
    {
        return (int)$this->conn->query("SELECT COUNT(*) FROM historial_vehiculo")->fetchColumn();
    }

    public function obtenerHistorial(int $idOrden): array { return []; }
}
?>
