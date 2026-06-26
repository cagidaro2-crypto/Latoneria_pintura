# Documentación: AuthController.php

## Descripción general
Controlador principal de autenticación del sistema. Gestiona el inicio de sesión, registro de nuevos usuarios (clientes), recuperación de contraseña y cierre de sesión. Actúa como dispatcher: detecta la acción solicitada por URL (`?accion=`) y ejecuta el método correspondiente. Protege el sistema verificando credenciales, intentos fallidos y bloqueos temporales.

## Dependencias
- `config/database.php` — Conexión PDO a MySQL
- `models/Usuario.php` — Modelo de usuarios con métodos de consulta y actualización

## Código documentado línea por línea

```php
// Línea 1: Apertura del bloque PHP
<?php

// Líneas 2-4: Inicia sesión solo si no estaba iniciada (evita el error "session already started")
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Líneas 6-7: Incluye la configuración de BD y el modelo Usuario
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Usuario.php';

// Línea 9: Definición de la clase controladora de autenticación
class AuthController
{
    // ── MÉTODO LOGIN ──────────────────────────────────────────────────────

    // Línea 12: Método público para gestionar el inicio de sesión
    public function login()
    {
        // Líneas 13-15: Si no es una petición POST, redirige al formulario de login
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: ../views/usuarios/login.php");
            exit;
        }

        // Líneas 17-18: Obtiene y limpia las entradas del formulario
        $correo   = trim($_POST['email']    ?? '');
        $password = trim($_POST['password'] ?? '');

        // Líneas 20-23: Valida que no estén vacíos los campos
        if (empty($correo) || empty($password)) {
            $_SESSION['alert'] = ['icon'=>'warning','title'=>'Campos incompletos','text'=>'Completa todos los campos.'];
            header("Location: ../views/usuarios/login.php"); exit;
        }

        // Líneas 25-27: Crea la conexión y el modelo de usuario
        $db      = (new Database())->conectar();
        $model   = new Usuario($db);

        // Línea 28: Busca al usuario por su correo en la base de datos
        $usuario = $model->obtenerPorEmail($correo);

        // Líneas 30-33: Si el usuario no existe, muestra error genérico (seguridad)
        if (!$usuario) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Usuario no encontrado','text'=>'Correo o contraseña incorrectos.'];
            header("Location: ../views/usuarios/login.php"); exit;
        }

        // Líneas 35-38: Verifica si la cuenta está desactivada por el administrador
        if (isset($usuario['activo']) && $usuario['activo'] == 0) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Cuenta inactiva','text'=>'Su cuenta está inactiva. Contacte al administrador.'];
            header("Location: ../views/usuarios/login.php"); exit;
        }

        // Líneas 40-43: Verifica si la cuenta está bloqueada temporalmente (15 min tras 5 intentos)
        if (!empty($usuario['bloqueado_hasta']) && strtotime($usuario['bloqueado_hasta']) > time()) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Cuenta bloqueada','text'=>'Demasiados intentos fallidos. Intente en 15 minutos.'];
            header("Location: ../views/usuarios/login.php"); exit;
        }

        // Líneas 45-49: Verifica la contraseña con password_verify() (bcrypt)
        if (!password_verify($password, $usuario['password'])) {
            $model->registrarIntentoFallido($usuario['id_usuario']);
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Contraseña incorrecta','text'=>'Correo o contraseña incorrectos.'];
            header("Location: ../views/usuarios/login.php"); exit;
        }

        // Línea 51: Limpia el contador de intentos fallidos al lograr acceso exitoso
        $model->resetearIntentos($usuario['id_usuario']);

        // Línea 52: Regenera el ID de sesión para prevenir ataques de fijación de sesión
        session_regenerate_id(true);

        // Líneas 54-59: Guarda los datos esenciales del usuario en la sesión
        $_SESSION['usuario'] = [
            'id_usuario' => $usuario['id_usuario'],
            'nombres'    => $usuario['nombres'],
            'correo'     => $usuario['correo'],
            'rol'        => $usuario['id_rol'],
        ];

        // Líneas 61-65: Redirige al dashboard según el rol (1=admin, 2=empleado, 3+=cliente)
        match ((int)$usuario['id_rol']) {
            1       => header("Location: ../views/dashboard/admin_dashboard.php"),
            2       => header("Location: ../views/dashboard/empleado_dashboard.php"),
            default => header("Location: ../views/dashboard/cliente_dashboard.php"),
        };
        exit;
    }

    // ── MÉTODO REGISTRO ───────────────────────────────────────────────────

    // Línea 69: Método para registrar nuevos clientes desde el formulario público
    public function registro()
    {
        // Líneas 70-72: Si no es POST, redirige al formulario de registro
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: ../views/usuarios/registre.php"); exit;
        }

        // Líneas 74-79: Obtiene y limpia todos los campos del formulario de registro
        $nombre      = trim($_POST['nombres']         ?? '');
        $apellidos   = trim($_POST['apellidos']       ?? '');
        $correo      = trim($_POST['correo']          ?? '');
        $telefono    = trim($_POST['telefono']        ?? '');
        $password    = trim($_POST['password']        ?? '');
        $passConfirm = trim($_POST['password_confirm']?? '');

        // Líneas 81-84: Verifica que todos los campos obligatorios estén completos
        if (empty($nombre) || empty($apellidos) || empty($correo) || empty($telefono) || empty($password)) {
            $_SESSION['alert'] = ['icon'=>'warning','title'=>'Campos incompletos','text'=>'Completa todos los campos obligatorios.'];
            header("Location: ../views/usuarios/registre.php"); exit;
        }

        // Líneas 86-89: Valida longitud mínima de la contraseña
        if (strlen($password) < 6) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Contraseña corta','text'=>'La contraseña debe tener al menos 6 caracteres.'];
            header("Location: ../views/usuarios/registre.php"); exit;
        }

        // Líneas 91-94: Verifica que ambas contraseñas coincidan
        if ($password !== $passConfirm) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Contraseñas no coinciden','text'=>'Las contraseñas ingresadas no son iguales.'];
            header("Location: ../views/usuarios/registre.php"); exit;
        }

        // Líneas 96-97: Conecta a la BD y crea el modelo
        $db    = (new Database())->conectar();
        $model = new Usuario($db);

        // Líneas 99-102: Verifica que el correo o teléfono no estén ya registrados
        if ($model->existeCorreoTelefono($correo, $telefono)) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Ya registrado','text'=>'Ya existe una cuenta con ese correo o teléfono.'];
            header("Location: ../views/usuarios/registre.php"); exit;
        }

        // Líneas 104-111: Intenta registrar al usuario (hashea la contraseña con bcrypt)
        $res = $model->registrar([
            'nombre'    => $nombre,
            'apellidos' => $apellidos,
            'correo'    => $correo,
            'telefono'  => $telefono,
            'password'  => password_hash($password, PASSWORD_DEFAULT),
        ]);

        // Líneas 113-116: Si el registro fue exitoso, redirige al login con mensaje
        if ($res === true) {
            $_SESSION['alert'] = ['icon'=>'success','title'=>'¡Registro exitoso!','text'=>'Ya puedes iniciar sesión.'];
            header("Location: ../views/usuarios/login.php"); exit;
        }

        // Líneas 118-119: Si falló el registro, muestra el mensaje de error
        $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>$res];
        header("Location: ../views/usuarios/registre.php"); exit;
    }

    // ── MÉTODO RECUPERAR ──────────────────────────────────────────────────

    // Línea 123: Método para restablecer la contraseña usando correo o teléfono
    public function recuperar()
    {
        // Líneas 124-125: Si no es POST, redirige al login con el modal de recuperación abierto
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: ../views/usuarios/login.php?panel=recuperar"); exit;
        }

        // Líneas 127-131: Obtiene los campos del formulario de recuperación
        $metodo      = $_POST['metodo']           ?? 'correo';
        $correo      = trim($_POST['correo']       ?? '');
        $telefono    = trim($_POST['telefono']     ?? '');
        $password    = trim($_POST['password']     ?? '');
        $passConfirm = trim($_POST['password_confirm'] ?? '');

        // Línea 133: Determina el valor identificador (correo o teléfono) según el método elegido
        $valor = $metodo === 'telefono' ? $telefono : $correo;

        // Líneas 135-138: Valida que existan los datos requeridos
        if (empty($valor) || empty($password)) {
            $_SESSION['alert'] = ['icon'=>'warning','title'=>'Campos incompletos','text'=>'Completa todos los campos obligatorios.'];
            header("Location: ../views/usuarios/login.php?panel=recuperar"); exit;
        }

        // Líneas 140-143: Valida longitud mínima de la nueva contraseña
        if (strlen($password) < 6) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Contraseña corta','text'=>'La contraseña debe tener al menos 6 caracteres.'];
            header("Location: ../views/usuarios/login.php?panel=recuperar"); exit;
        }

        // Líneas 145-148: Verifica que las contraseñas coincidan
        if ($password !== $passConfirm) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Contraseñas no coinciden','text'=>'Las contraseñas no son iguales.'];
            header("Location: ../views/usuarios/login.php?panel=recuperar"); exit;
        }

        // Líneas 150-151: Conecta a la BD y crea el modelo
        $db    = (new Database())->conectar();
        $model = new Usuario($db);

        // Líneas 153-157: Verifica que exista una cuenta activa con ese correo o teléfono
        if (!$model->validarRecuperacion($valor, $metodo)) {
            $campo = $metodo === 'telefono' ? 'teléfono' : 'correo';
            $_SESSION['alert'] = ['icon'=>'error','title'=>'No encontrado','text'=>"No existe una cuenta con ese {$campo}."];
            header("Location: ../views/usuarios/login.php?panel=recuperar"); exit;
        }

        // Líneas 159-163: Si la validación pasa, actualiza la contraseña con hash bcrypt
        if ($model->actualizarPassword($valor, password_hash($password, PASSWORD_DEFAULT), $metodo)) {
            $_SESSION['alert'] = ['icon'=>'success','title'=>'¡Contraseña restablecida!','text'=>'Ya puedes iniciar sesión con tu nueva contraseña.'];
            header("Location: ../views/usuarios/login.php"); exit;
        }

        // Líneas 165-166: Si falla la actualización, informa al usuario
        $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>'No se pudo actualizar la contraseña. Intenta de nuevo.'];
        header("Location: ../views/usuarios/login.php?panel=recuperar"); exit;
    }

    // ── MÉTODO LOGOUT ─────────────────────────────────────────────────────

    // Línea 170: Método que cierra la sesión del usuario
    public function logout()
    {
        // Línea 172: Elimina todas las variables de sesión
        session_unset();

        // Línea 173: Destruye la sesión completamente
        session_destroy();

        // Línea 174: Redirige al formulario de login
        header("Location: ../views/usuarios/login.php"); exit;
    }
}

// ── DISPATCHER ────────────────────────────────────────────────────────────

// Líneas 178-186: Solo ejecuta el dispatcher si este archivo es el script principal (no incluido)
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    $controller = new AuthController();

    // Obtiene la acción de la URL (?accion=...), por defecto 'login'
    $accion     = $_GET['accion'] ?? 'login';

    // Despacha al método correspondiente usando match()
    match ($accion) {
        'logout'    => $controller->logout(),
        'registro'  => $controller->registro(),
        'recuperar' => $controller->recuperar(),
        default     => $controller->login(),
    };
}
?>
```

## Resumen de funciones/métodos

| Método | Parámetros | Retorno | Descripción |
|--------|-----------|---------|-------------|
| `login()` | Ninguno (lee `$_POST`) | void | Valida credenciales, verifica bloqueo, inicia sesión y redirige por rol |
| `registro()` | Ninguno (lee `$_POST`) | void | Valida datos, hashea contraseña y registra nuevo cliente |
| `recuperar()` | Ninguno (lee `$_POST`) | void | Verifica identidad por correo/teléfono y actualiza la contraseña |
| `logout()` | Ninguno | void | Destruye la sesión y redirige al login |

## Flujo de ejecución
1. El archivo recibe una petición HTTP directa (desde un formulario o enlace).
2. El dispatcher evalúa `$_GET['accion']` para determinar qué método ejecutar.
3. **Login**: valida campos → busca usuario → verifica estado activo → verifica bloqueo → compara contraseña → guarda sesión → redirige según rol.
4. **Registro**: valida campos → verifica contraseñas → verifica duplicado → inserta usuario → redirige al login.
5. **Recuperar**: valida datos → verifica existencia de cuenta → actualiza contraseña hasheada → redirige al login.
6. **Logout**: destruye sesión → redirige al login.
7. En caso de error en cualquier paso, guarda un alert en sesión y redirige de vuelta al formulario correspondiente.
