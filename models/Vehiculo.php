<?php
class Vehiculo
{
    private $conn;
    private $tabla = "vehiculo";

    public function __construct($db) { $this->conn = $db; }

    public function existePlaca($placa, $excluirId = null)
    {
        $sql = "SELECT id_vehiculo FROM {$this->tabla} WHERE placa = :placa";
        if ($excluirId) $sql .= " AND id_vehiculo != :excluir";
        $stmt = $this->conn->prepare($sql);
        $params = [':placa' => $placa];
        if ($excluirId) $params[':excluir'] = $excluirId;
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    public function registrar($datos)
    {
        try {
            $sql = "INSERT INTO {$this->tabla}
                    (id_cliente, id_estado_vehiculo, placa, marca, modelo, `año`)
                    VALUES (:id_cliente, :id_estado_vehiculo, :placa, :marca, :modelo, :año)";
            $this->conn->prepare($sql)->execute([
                ':id_cliente'         => $datos['id_cliente'],
                ':id_estado_vehiculo' => $datos['id_estado_vehiculo'] ?? 1,
                ':placa'              => strtoupper($datos['placa']),
                ':marca'              => $datos['marca'],
                ':modelo'             => $datos['modelo'],
                ':año'                => $datos['año'] ?? ($datos['anio'] ?? ''),
            ]);
            return true;
        } catch (Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }

    public function obtenerTodos()
    {
        $sql = "SELECT v.*, u.nombre
                FROM {$this->tabla} v
                JOIN persona u ON v.id_cliente = u.id_persona";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorCliente($idCliente)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM {$this->tabla} WHERE id_cliente = :id"
        );
        $stmt->execute([':id' => $idCliente]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->tabla} WHERE id_vehiculo = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerPorPlaca($placa)
    {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->tabla} WHERE placa = :placa LIMIT 1");
        $stmt->execute([':placa' => strtoupper($placa)]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function actualizar($id, $datos)
    {
        try {
            $sql = "UPDATE {$this->tabla}
                    SET marca=:marca, modelo=:modelo, `año`=:año
                    WHERE id_vehiculo=:id";
            $this->conn->prepare($sql)->execute([
                ':marca'  => $datos['marca'],
                ':modelo' => $datos['modelo'],
                ':año'    => $datos['año'] ?? ($datos['anio'] ?? ''),
                ':id'     => $id,
            ]);
            return true;
        } catch (Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }

    public function historialServicios($idVehiculo)
    {
        // TODO: Adaptar a la nueva estructura de servicios y ordenes
        return [];
    }

    public function contar()
    {
        return $this->conn->query("SELECT COUNT(*) FROM {$this->tabla}")->fetchColumn();
    }
}
?>
