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
    <title>Registro – TallerPro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background: #f3f4f6; }
        .register-left { background: linear-gradient(135deg, #1a3a6b 0%, #2563eb 100%); min-height: 600px; }
        .form-control:focus, .form-select:focus { border-color: #2563eb; box-shadow: none; }
        .btn-registrar { background: #2563eb; border: none; font-weight: 600; }
        .btn-registrar:hover { background: #1a3a6b; }
        .input-group-text { background: #e9ecef; border-right: 0; }
        .form-control.no-left-border, .form-select.no-left-border { border-left: 0; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center p-3 min-vh-100">

<div class="card shadow-lg border-0 rounded-4 overflow-hidden" style="max-width:900px; width:100%;">
    <div class="row g-0">

        <!-- Panel izquierdo -->
        <div class="col-md-4 register-left d-none d-md-flex flex-column justify-content-between p-5 text-white">
            <div>
                <i class="fas fa-car-side fa-3x mb-3 opacity-90"></i>
                <h4 class="fw-bold">Crea tu cuenta</h4>
                <p class="text-white-50 small mt-2">Accede a todos los servicios del taller, agenda citas, revisa el estado de tu vehículo y más.</p>
            </div>
            <div class="small text-white-50">
                <i class="fas fa-lock me-1"></i> Tus datos están protegidos.
            </div>
        </div>

        <!-- Formulario -->
        <div class="col-md-8 bg-white p-5">
            <h4 class="fw-bold mb-1">Registro de Cliente</h4>
            <p class="text-muted small mb-4">Completa todos los campos para crear tu cuenta</p>

            <form action="../../controllers/AuthController.php?accion=registro" method="POST">

                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="form-label fw-semibold small text-secondary">Nombres *</label>
                        <input type="text" name="nombres" class="form-control" placeholder="Juan" required>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label fw-semibold small text-secondary">Apellidos *</label>
                        <input type="text" name="apellidos" class="form-control" placeholder="Pérez" required>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label fw-semibold small text-secondary">Teléfono</label>
                        <input type="tel" name="telefono" class="form-control" placeholder="300 123 4567">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold small text-secondary">Correo Electrónico *</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope text-secondary"></i></span>
                            <input type="email" name="email" class="form-control no-left-border" placeholder="correo@ejemplo.com" required>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label fw-semibold small text-secondary">Contraseña *</label>
                        <input type="password" name="password" class="form-control" placeholder="Mín. 8 caracteres" required>
                        <div class="form-text small text-muted">Al menos 8 caracteres, 1 mayúscula, 1 número y 1 símbolo.</div>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label fw-semibold small text-secondary">Confirmar Contraseña *</label>
                        <input type="password" name="password_confirm" class="form-control" placeholder="Repite la contraseña" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-registrar btn-primary w-100 py-2 rounded-3 mt-4">
                    <i class="fas fa-user-plus me-2"></i> Crear Cuenta
                </button>

            </form>

            <p class="text-center text-muted small mt-3 mb-0">
                ¿Ya tienes cuenta? <a href="login.php" class="text-primary fw-semibold text-decoration-none">Inicia sesión</a>
            </p>
            <div class="text-center mt-3">
                <a href="../../index.php" class="text-muted text-decoration-none small"><i class="fas fa-arrow-left me-1"></i> Volver al inicio</a>
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
        confirmButtonColor: '#2563eb'
    });
</script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
