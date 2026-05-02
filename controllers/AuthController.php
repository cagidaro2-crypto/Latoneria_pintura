<?php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Usuario.php';

class AuthController
{
    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: ../views/usuarios/login.php");
            exit;
        }

        $email    = trim($_POST['email']    ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($email) || empty($password)) {
            $_SESSION['alert'] = ['icon'=>'warning','title'=>'Campos incompletos','text'=>'Los campos marcados son obligatorios.'];
            header("Location: ../views/usuarios/login.php");
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Correo inválido','text'=>'Ingrese un correo electrónico válido.'];
            header("Location: ../views/usuarios/login.php");
            exit;
        }

        $db           = (new Database())->conectar();
        $usuarioModel = new Usuario($db);
        $usuario      = $usuarioModel->obtenerPorEmail($email);

        if (!$usuario) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Usuario no encontrado','text'=>'Correo o contraseña incorrectos. Verifique sus datos e intente nuevamente.'];
            header("Location: ../views/usuarios/login.php");
            exit;
        }

        if (isset($usuario['activo']) && $usuario['activo'] == 0) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Cuenta inactiva','text'=>'Su cuenta se encuentra inactiva. Contacte al administrador.'];
            header("Location: ../views/usuarios/login.php");
            exit;
        }

        if (isset($usuario['bloqueado_hasta']) && $usuario['bloqueado_hasta'] && strtotime($usuario['bloqueado_hasta']) > time()) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Cuenta bloqueada','text'=>'Cuenta bloqueada temporalmente. Intente en 15 minutos o recupere su contraseña.'];
            header("Location: ../views/usuarios/login.php");
            exit;
        }

        if (!password_verify($password, $usuario['contraseña'])) {
            $usuarioModel->registrarIntentoFallido($usuario['id_persona']);
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Contraseña incorrecta','text'=>'Correo o contraseña incorrectos. Verifique sus datos e intente nuevamente.'];
            header("Location: ../views/usuarios/login.php");
            exit;
        }

        $usuarioModel->resetearIntentos($usuario['id_persona']);

        session_regenerate_id(true);

        $_SESSION['usuario'] = [
            'id_usuario' => $usuario['id_persona'],
            'nombres'    => $usuario['nombre'],
            'documento'  => $usuario['documento'],
            'rol'        => $usuario['id_rol']
        ];

        switch ($usuario['id_rol']) {
            case 1: // Administrador
                header("Location: ../views/dashboard/admin_dashboard.php");
                break;
            case 3: // Empleado
                header("Location: ../views/dashboard/empleado_dashboard.php");
                break;
            case 2: // Cliente
            default:
                header("Location: ../views/dashboard/cliente_dashboard.php");
                break;
        }
        exit;
    }

    public function registro()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: ../views/usuarios/registre.php");
            exit;
        }

        $nombres        = trim($_POST['nombres']         ?? '');
        $apellidos      = trim($_POST['apellidos']       ?? '');
        $email          = trim($_POST['email']           ?? '');
        $telefono       = trim($_POST['telefono']        ?? '');
        $password       = trim($_POST['password']        ?? '');
        $passConfirm    = trim($_POST['password_confirm']?? '');

        // Validaciones
        if (empty($nombres) || empty($apellidos) || empty($email) || empty($password)) {
            $_SESSION['alert'] = ['icon'=>'warning','title'=>'Campos incompletos','text'=>'Por favor complete todos los campos obligatorios.'];
            header("Location: ../views/usuarios/registre.php"); exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Correo inválido','text'=>'Ingrese un correo electrónico válido.'];
            header("Location: ../views/usuarios/registre.php"); exit;
        }

        if (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password)) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Contraseña débil','text'=>'La contraseña debe tener al menos 8 caracteres, una mayúscula, un número y un carácter especial.'];
            header("Location: ../views/usuarios/registre.php"); exit;
        }

        if ($password !== $passConfirm) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Contraseñas no coinciden','text'=>'Las contraseñas ingresadas no son iguales.'];
            header("Location: ../views/usuarios/registre.php"); exit;
        }

        $db           = (new Database())->conectar();
        $usuarioModel = new Usuario($db);

        if ($usuarioModel->existeCorreoTelefono($email, $telefono)) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Cuenta duplicada','text'=>'Ya existe una cuenta registrada con este correo o numero de telefono.'];
            header("Location: ../views/usuarios/registre.php"); exit;
        }

        $resultado = $usuarioModel->registrar([
            'nombre'     => $nombres . ' ' . $apellidos,
            'documento'  => $email,
            'telefono'   => $telefono,
            'password'   => password_hash($password, PASSWORD_DEFAULT),
        ]);

        if ($resultado === true) {
            $_SESSION['alert'] = ['icon'=>'success','title'=>'¡Registro exitoso!','text'=>'Registro exitoso. Ya puede iniciar sesión con sus credenciales.'];
            header("Location: ../views/usuarios/login.php"); exit;
        } else {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>$resultado];
            header("Location: ../views/usuarios/registre.php"); exit;
        }
    }

    public function logout()
    {
        session_unset();
        session_destroy();
        header("Location: ../views/usuarios/login.php");
        exit;
    }
}

// ----- dispatcher -----
$controller = new AuthController();
$accion     = $_GET['accion'] ?? 'login';

match ($accion) {
    'logout'   => $controller->logout(),
    'registro' => $controller->registro(),
    default    => $controller->login(),
};
?>
