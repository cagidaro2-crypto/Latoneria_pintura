<?php

class Usuario
{
    private $conn;
    private $tabla = "usuarios";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Verificar si existe correo
    public function existeCorreo($correo)
    {
        $stmt = $this->conn->prepare(
            "SELECT id_usuario FROM {$this->tabla} WHERE correo = :correo LIMIT 1"
        );
        $stmt->execute([':correo' => $correo]);
        return $stmt->rowCount() > 0;
    }

    // Verificar correo o teléfono (para registro)
    public function existeCorreoTelefono($correo, $telefono)
    {
        $stmt = $this->conn->prepare(
            "SELECT id_usuario FROM {$this->tabla}
             WHERE correo = :correo OR telefono = :telefono LIMIT 1"
        );
        $stmt->execute([':correo' => $correo, ':telefono' => $telefono]);
        return $stmt->rowCount() > 0;
    }

    // Obtener usuario por correo (login)
    public function obtenerPorEmail($correo)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM {$this->tabla} WHERE correo = :correo LIMIT 1"
        );
        $stmt->execute([':correo' => $correo]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Validar identidad para recuperación — acepta correo O teléfono
    public function validarRecuperacion($valor, $metodo = 'correo')
    {
        $campo = $metodo === 'telefono' ? 'telefono' : 'correo';
        $stmt  = $this->conn->prepare(
            "SELECT id_usuario FROM {$this->tabla}
             WHERE {$campo} = :valor AND activo = 1 LIMIT 1"
        );
        $stmt->execute([':valor' => $valor]);
        return $stmt->rowCount() > 0;
    }

    // Actualizar contraseña — busca por correo O teléfono
    public function actualizarPassword($valor, $nuevaPasswordHash, $metodo = 'correo')
    {
        $campo = $metodo === 'telefono' ? 'telefono' : 'correo';
        $stmt  = $this->conn->prepare(
            "UPDATE {$this->tabla}
             SET `password` = :password,
                 intentos_fallidos = 0,
                 bloqueado_hasta = NULL
             WHERE {$campo} = :valor"
        );
        return $stmt->execute([':password' => $nuevaPasswordHash, ':valor' => $valor]);
    }

    // Registrar intento fallido
    public function registrarIntentoFallido($id)
    {
        $stmt = $this->conn->prepare(
            "UPDATE {$this->tabla}
             SET intentos_fallidos = IFNULL(intentos_fallidos, 0) + 1,
                 bloqueado_hasta = IF(
                     IFNULL(intentos_fallidos, 0) + 1 >= 5,
                     DATE_ADD(NOW(), INTERVAL 15 MINUTE),
                     bloqueado_hasta
                 )
             WHERE id_usuario = :id"
        );
        $stmt->execute([':id' => $id]);
    }

    // Resetear intentos fallidos
    public function resetearIntentos($id)
    {
        $stmt = $this->conn->prepare(
            "UPDATE {$this->tabla}
             SET intentos_fallidos = 0, bloqueado_hasta = NULL
             WHERE id_usuario = :id"
        );
        $stmt->execute([':id' => $id]);
    }

    // Registrar nuevo usuario (cliente)
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
                ':password'  => $datos['password'],
                ':correo'    => $datos['correo'],
                ':telefono'  => $datos['telefono'],
                ':id_rol'    => 3, // 3 = Cliente según el SQL real
            ]);
            return true;
        } catch (Exception $e) {
            return "Error al registrar: " . $e->getMessage();
        }
    }

    // Obtener usuario por ID
    public function obtenerPorId($id)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM {$this->tabla} WHERE id_usuario = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Obtener todos los usuarios (con filtro opcional por rol)
    public function obtenerTodos($rol = null)
    {
        if ($rol) {
            $idRol = match($rol) {
                'administrador' => 1,
                'empleado'      => 2,
                'cliente'       => 3,
                default         => 3,
            };
            $stmt = $this->conn->prepare(
                "SELECT u.*, r.nombre_rol AS rol_nombre
                 FROM {$this->tabla} u
                 JOIN roles r ON u.id_rol = r.id_rol
                 WHERE u.id_rol = :id_rol
                 ORDER BY u.nombres"
            );
            $stmt->execute([':id_rol' => $idRol]);
        } else {
            $stmt = $this->conn->query(
                "SELECT u.*, r.nombre_rol AS rol_nombre
                 FROM {$this->tabla} u
                 JOIN roles r ON u.id_rol = r.id_rol
                 ORDER BY u.nombres"
            );
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Contar usuarios por rol
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
