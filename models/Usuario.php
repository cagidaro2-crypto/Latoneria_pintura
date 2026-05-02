<?php

class Usuario
{
    private $conn;

    // CAMBIADO A LA TABLA persona
    private $tabla = "persona";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Verificar si existe documento
    public function existeCorreo($documento)
    {
        $sql = "SELECT id_persona
                FROM {$this->tabla}
                WHERE documento = :documento
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);

        $stmt->execute([
            ':documento' => $documento
        ]);

        return $stmt->rowCount() > 0;
    }

    // Verificar documento o telefono
    public function existeCorreoTelefono($documento, $telefono)
    {
        $sql = "SELECT id_persona
                FROM {$this->tabla}
                WHERE documento = :documento
                OR telefono = :telefono
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);

        $stmt->execute([
            ':documento' => $documento,
            ':telefono'  => $telefono
        ]);

        return $stmt->rowCount() > 0;
    }

    // Obtener usuario por documento
    public function obtenerPorEmail($documento)
    {
        $sql = "SELECT *
                FROM {$this->tabla}
                WHERE documento = :documento
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);

        $stmt->execute([
            ':documento' => $documento
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Registrar intento fallido
    public function registrarIntentoFallido($id)
    {
        $sql = "UPDATE {$this->tabla}
                SET intentos_fallidos = IFNULL(intentos_fallidos, 0) + 1,
                    bloqueado_hasta = IF(IFNULL(intentos_fallidos, 0) + 1 >= 5, DATE_ADD(NOW(), INTERVAL 15 MINUTE), bloqueado_hasta)
                WHERE id_persona = :id";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
    }

    // Resetear intentos
    public function resetearIntentos($id)
    {
        $sql = "UPDATE {$this->tabla} 
                SET intentos_fallidos = 0, 
                    bloqueado_hasta = NULL 
                WHERE id_persona = :id";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
    }

    // Registrar usuario
    public function registrar($datos)
    {
        try {

            $sql = "INSERT INTO {$this->tabla}
                    (
                        nombre,
                        contraseña,
                        documento,
                        telefono,
                        id_rol
                    )
                    VALUES
                    (
                        :nombre,
                        :password,
                        :documento,
                        :telefono,
                        :id_rol
                    )";

            $stmt = $this->conn->prepare($sql);

            $stmt->execute([
                ':nombre'     => $datos['nombre'],
                ':password'   => $datos['password'],
                ':documento'  => $datos['documento'],
                ':telefono'   => $datos['telefono'],
                ':id_rol'     => 2
            ]);

            return true;

        } catch (Exception $e) {

            return "Error al registrar: " . $e->getMessage();
        }
    }

    // Obtener usuario por ID
    public function obtenerPorId($id)
    {
        $sql = "SELECT *
                FROM {$this->tabla}
                WHERE id_persona = :id
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);

        $stmt->execute([
            ':id' => $id
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function contarPorRol($rolNombre)
    {
        $idRol = 2; // Default a cliente
        if ($rolNombre === 'empleado') $idRol = 3;
        if ($rolNombre === 'administrador') $idRol = 1;

        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM {$this->tabla} WHERE id_rol = :id_rol");
        $stmt->execute([':id_rol' => $idRol]);
        return $stmt->fetchColumn();
    }
}
?>
