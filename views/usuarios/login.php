<?php
session_start();
$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión – TallerPro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background: #f3f4f6; min-height: 100vh; }
        .login-panel-left {
            background: linear-gradient(135deg, #1a3a6b 0%, #2563eb 100%);
            min-height: 560px;
        }
        .input-group-text { background: #e9ecef; border-right: 0; }
        .form-control { border-left: 0; }
        .form-control:focus { box-shadow: none; border-color: #2563eb; }
        .btn-ingresar { background: #2563eb; border: none; font-weight: 600; }
        .btn-ingresar:hover { background: #1a3a6b; }
        .brand-icon { font-size: 3rem; color: rgba(255,255,255,.9); }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center p-3">

<div class="card shadow-lg border-0 rounded-4 overflow-hidden" style="max-width:900px; width:100%;">
    <div class="row g-0">

        <!-- Panel izquierdo -->
        <div class="col-md-5 login-panel-left d-none d-md-flex flex-column justify-content-between p-5 text-white">
            <div>
                <div class="brand-icon mb-3"><i class="fas fa-car-side"></i></div>
                <h3 class="fw-bold">¡Bienvenido de vuelta!</h3>
                <p class="text-white-50 mt-2">Accede a tu panel para gestionar órdenes, vehículos, inventario y mucho más.</p>
            </div>
            <div class="bg-white bg-opacity-10 rounded-3 p-3 d-flex align-items-center gap-3">
                <i class="fas fa-shield-halved fa-2x"></i>
                <p class="mb-0 small">Acceso seguro con cifrado de datos institucionales.</p>
            </div>
        </div>

        <!-- Formulario -->
        <div class="col-md-7 p-5 d-flex flex-column justify-content-center bg-white">
            <h4 class="fw-bold text-dark mb-1">Iniciar Sesión</h4>
            <p class="text-muted small mb-4">Ingresa tus credenciales para continuar</p>

            <form action="../../controllers/AuthController.php" method="POST">

                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary small">Correo Electrónico</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope text-secondary"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="ejemplo@correo.com" required>
                    </div>
                </div>

                <div class="mb-2">
                    <label class="form-label fw-semibold text-secondary small">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock text-secondary"></i></span>
                        <input type="password" name="password" id="passInput" class="form-control" placeholder="••••••••" required>
                        <button type="button" class="btn btn-outline-secondary border-start-0"
                                onclick="togglePass()">
                            <i class="fas fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="text-end mb-3">
                    <a href="recuperar.php" class="small text-primary text-decoration-none">¿Olvidaste tu contraseña?</a>
                </div>

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" id="remember">
                    <label class="form-check-label text-muted small" for="remember">Recordar mi sesión</label>
                </div>

                <button type="submit" class="btn btn-ingresar btn-primary w-100 py-2 rounded-3">
                    <i class="fas fa-right-to-bracket me-2"></i> Entrar al Sistema
                </button>

            </form>

            <p class="text-center text-muted small mt-4 mb-0">
                ¿No tienes cuenta? <a href="registre.php" class="text-primary fw-semibold text-decoration-none">Regístrate aquí</a>
            </p>
            <div class="text-center mt-3">
                <a href="../../public/index.php" class="text-muted text-decoration-none small"><i class="fas fa-arrow-left me-1"></i> Volver al inicio</a>
            </div>
        </div>

    </div>
</div>

<?php if ($alert): ?>
<script>
    Swal.fire({
        icon: '<?= htmlspecialchars($alert['icon']) ?>',
        title: '<?= htmlspecialchars($alert['title']) ?>',
        text: '<?= htmlspecialchars($alert['text']) ?>',
        confirmButtonText: 'Aceptar',
        confirmButtonColor: '#2563eb'
    });
</script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePass() {
    const inp = document.getElementById('passInput');
    const ico = document.getElementById('eyeIcon');
    if (inp.type === 'password') {
        inp.type = 'text';
        ico.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        inp.type = 'password';
        ico.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>
</body>
</html>
