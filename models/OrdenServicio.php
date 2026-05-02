<?php
class OrdenServicio
{
    private $conn;
    private $tabla = "ordenes_servicio";

    public function __construct($db) { $this->conn = $db; }

    public function tieneOrdenActiva($idVehiculo) { return false; }
    public function crear($datos) { return false; }
    public function actualizarEstado($idOrden, $nuevoEstado, $observacion, $idResponsable) { return false; }
    public function obtenerTodas() { return []; }
    public function obtenerPorCliente($idCliente) { return []; }
    public function obtenerPorId($id) { return null; }
    public function obtenerHistorial($idOrden) { return []; }
    public function contarPorEstado($estado) { return 0; }
    public function contar() { return 0; }
}
?>
