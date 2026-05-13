<?php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Usuario.php';

class AuthController
{
    // ── LOGIN ──────────────────────────────────────────────────────────────
    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: ../views/usuarios/login.php");
            exit;
        }

        $correo   = trim($_POST['email']    ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($correo) || empty($password)) {
            $_SESSION['alert'] = ['icon'=>'warning','title'=>'Campos incompletos','text'=>'Completa todos los campos.'];
            header("Location: ../views/usuarios/login.php"); exit;
        }

        $db      = (new Database())->conectar();
        $model   = new Usuario($db);
        $usuario = $model->obtenerPorEmail($correo);

        if (!$usuario) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Usuario no encontrado','text'=>'Correo o contraseña incorrectos.'];
            header("Location: ../views/usuarios/login.php"); exit;
        }

        if (isset($usuario['activo']) && $usuario['activo'] == 0) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Cuenta inactiva','text'=>'Su cuenta está inactiva. Contacte al administrador.'];
            header("Location: ../views/usuarios/login.php"); exit;
        }

        if (!empty($usuario['bloqueado_hasta']) && strtotime($usuario['bloqueado_hasta']) > time()) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Cuenta bloqueada','text'=>'Demasiados intentos fallidos. Intente en 15 minutos.'];
            header("Location: ../views/usuarios/login.php"); exit;
        }

        if (!password_verify($password, $usuario['contraseña'])) {
            $model->registrarIntentoFallido($usuario['id_persona']);
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Contraseña incorrecta','text'=>'Correo o contraseña incorrectos.'];
            header("Location: ../views/usuarios/login.php"); exit;
        }

        $model->resetearIntentos($usuario['id_persona']);
        session_regenerate_id(true);

        $_SESSION['usuario'] = [
            'id_usuario' => $usuario['id_persona'],
            'nombres'    => $usuario['nombre'],
            'correo'     => $usuario['correo'],
            'rol'        => $usuario['id_rol'],
        ];

        match ((int)$usuario['id_rol']) {
            1       => header("Location: ../views/dashboard/admin_dashboard.php"),
            3       => header("Location: ../views/dashboard/empleado_dashboard.php"),
            default => header("Location: ../views/dashboard/cliente_dashboard.php"),
        };
        exit;
    }

    // ── REGISTRO ───────────────────────────────────────────────────────────
    public function registro()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: ../views/usuarios/registre.php"); exit;
        }

        $nombre      = trim($_POST['nombre']          ?? '');
        $correo      = trim($_POST['correo']          ?? '');
        $telefono    = trim($_POST['telefono']        ?? '');
        $password    = trim($_POST['password']        ?? '');
        $passConfirm = trim($_POST['password_confirm']?? '');

        if (empty($nombre) || empty($correo) || empty($telefono) || empty($password)) {
            $_SESSION['alert'] = ['icon'=>'warning','title'=>'Campos incompletos','text'=>'Completa todos los campos obligatorios.'];
            header("Location: ../views/usuarios/registre.php"); exit;
        }

        if (strlen($password) < 6) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Contraseña corta','text'=>'La contraseña debe tener al menos 6 caracteres.'];
            header("Location: ../views/usuarios/registre.php"); exit;
        }

        if ($password !== $passConfirm) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Contraseñas no coinciden','text'=>'Las contraseñas ingresadas no son iguales.'];
            header("Location: ../views/usuarios/registre.php"); exit;
        }

        $db    = (new Database())->conectar();
        $model = new Usuario($db);

        if ($model->existeCorreoTelefono($correo, $telefono)) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Ya registrado','text'=>'Ya existe una cuenta con ese correo o teléfono.'];
            header("Location: ../views/usuarios/registre.php"); exit;
        }

        $res = $model->registrar([
            'nombre'   => $nombre,
            'correo'   => $correo,
            'telefono' => $telefono,
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        if ($res === true) {
            $_SESSION['alert'] = ['icon'=>'success','title'=>'¡Registro exitoso!','text'=>'Ya puedes iniciar sesión.'];
            header("Location: ../views/usuarios/login.php"); exit;
        }

        $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>$res];
        header("Location: ../views/usuarios/registre.php"); exit;
    }

    // ── RECUPERAR CONTRASEÑA ───────────────────────────────────────────────
    public function recuperar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: ../views/usuarios/login.php?panel=recuperar"); exit;
        }

        $metodo      = $_POST['metodo']           ?? 'correo';   // 'correo' | 'telefono'
        $correo      = trim($_POST['correo']       ?? '');
        $telefono    = trim($_POST['telefono']     ?? '');
        $password    = trim($_POST['password']     ?? '');
        $passConfirm = trim($_POST['password_confirm'] ?? '');

        // Determinar el valor identificador según el método elegido
        $valor = $metodo === 'telefono' ? $telefono : $correo;

        if (empty($valor) || empty($password)) {
            $_SESSION['alert'] = ['icon'=>'warning','title'=>'Campos incompletos','text'=>'Completa todos los campos obligatorios.'];
            header("Location: ../views/usuarios/login.php?panel=recuperar"); exit;
        }

        if (strlen($password) < 6) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Contraseña corta','text'=>'La contraseña debe tener al menos 6 caracteres.'];
            header("Location: ../views/usuarios/login.php?panel=recuperar"); exit;
        }

        if ($password !== $passConfirm) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Contraseñas no coinciden','text'=>'Las contraseñas no son iguales.'];
            header("Location: ../views/usuarios/login.php?panel=recuperar"); exit;
        }

        $db    = (new Database())->conectar();
        $model = new Usuario($db);

        if (!$model->validarRecuperacion($valor, $metodo)) {
            $campo = $metodo === 'telefono' ? 'teléfono' : 'correo';
            $_SESSION['alert'] = ['icon'=>'error','title'=>'No encontrado','text'=>"No existe una cuenta con ese {$campo}."];
            header("Location: ../views/usuarios/login.php?panel=recuperar"); exit;
        }

        if ($model->actualizarPassword($valor, password_hash($password, PASSWORD_DEFAULT), $metodo)) {
            $_SESSION['alert'] = ['icon'=>'success','title'=>'¡Contraseña restablecida!','text'=>'Ya puedes iniciar sesión con tu nueva contraseña.'];
            header("Location: ../views/usuarios/login.php"); exit;
        }

        $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>'No se pudo actualizar la contraseña. Intenta de nuevo.'];
        header("Location: ../views/usuarios/login.php?panel=recuperar"); exit;
    }

    // ── LOGOUT ─────────────────────────────────────────────────────────────
    public function logout()
    {
        session_unset();
        session_destroy();
        header("Location: ../views/usuarios/login.php"); exit;
    }
}

// ── Dispatcher ─────────────────────────────────────────────────────────────
$controller = new AuthController();
$accion     = $_GET['accion'] ?? 'login';

match ($accion) {
    'logout'    => $controller->logout(),
    'registro'  => $controller->registro(),
    'recuperar' => $controller->recuperar(),
    default     => $controller->login(),
};
?>
