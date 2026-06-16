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
    <title>Registro – Latoneria y Pintura 371</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --color-bg: #181c14;
            --color-panel: #3c3d37;
            --color-accent: #697565;
            --color-text-main: #f5f5f5;
            --color-text-muted: #a1a1aa;
        }
        body { 
            background-color: var(--color-bg); 
            min-height: 100vh; 
            font-family: 'Inter', sans-serif;
            color: var(--color-text-main);
            background-image: radial-gradient(circle at top right, #2a2e25, var(--color-bg));
        }

        .auth-card {
            max-width: 550px; 
            width: 100%;
            border-radius: 20px; 
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
            background: var(--color-panel);
            border: 1px solid rgba(255,255,255,0.05);
            margin: auto;
        }

        .brand-logo {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--color-accent);
            background: rgba(24, 28, 20, 0.7);
            padding: 8px 16px;
            border-radius: 30px;
            margin-bottom: 2rem;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255,255,255,0.1);
        }

        .panel-right { 
            background: var(--color-panel); 
            padding: 3rem; 
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-label { font-size: 0.85rem; font-weight: 600; color: #d1d5db; margin-bottom: 0.4rem; }
        
        .input-group-text { 
            background: rgba(24, 28, 20, 0.6); 
            border: 1px solid rgba(255,255,255,0.1); 
            border-right: 0; 
            color: var(--color-accent);
        }
        
        .form-control { 
            background: rgba(24, 28, 20, 0.6); 
            border: 1px solid rgba(255,255,255,0.1); 
            color: #fff;
            padding: 0.75rem 1rem;
        }
        .form-control.no-left-border { border-left: 0; }
        .form-control:focus { 
            background: rgba(24, 28, 20, 0.8);
            box-shadow: none; 
            border-color: var(--color-accent); 
            color: #fff;
        }
        .form-control::placeholder {
            color: #6b7280;
        }

        .btn-primary-custom {
            background: var(--color-accent); 
            color: #fff; 
            border: none;
            border-radius: 8px; 
            font-size: 1rem; 
            font-weight: 600;
            padding: 0.75rem; 
            width: 100%; 
            transition: all 0.3s;
            margin-top: 1rem;
            box-shadow: 0 4px 15px rgba(105, 117, 101, 0.4);
        }
        .btn-primary-custom:hover { 
            background: #7a8a76; 
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(105, 117, 101, 0.6);
        }

        .auth-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 0.5rem;
        }
        .auth-subtitle {
            color: var(--color-text-muted);
            font-size: 0.9rem;
            margin-bottom: 2rem;
        }

        @media (max-width: 768px) {
            .panel-right { padding: 2rem; }
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center p-3">

<div class="auth-card">
    <div class="panel-right">
        
        <div class="text-center">
             <div class="brand-logo mx-auto">
                <img src="../../imagen/logo.jpeg" alt="Logo" style="width:28px;height:28px;border-radius:6px;object-fit:cover;"> Latonería y Pintura 371
            </div>
        </div>

        <h5 class="auth-title text-center">Registro de Cliente</h5>
        <p class="auth-subtitle text-center">Completa todos los campos para crear tu cuenta</p>

        <form action="../../controllers/AuthController.php?accion=registro" method="POST">

            <div class="row g-3">
                <div class="col-sm-6">
                    <label class="form-label">Nombres *</label>
                    <input type="text" name="nombres" class="form-control" placeholder="Juan" required>
                </div>
                <div class="col-sm-6">
                    <label class="form-label">Apellidos *</label>
                    <input type="text" name="apellidos" class="form-control" placeholder="Pérez García" required>
                </div>
                <div class="col-sm-6">
                    <label class="form-label">Teléfono *</label>
                    <input type="tel" name="telefono" class="form-control" placeholder="300 123 4567" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Correo Electrónico *</label>
                    <div class="input-group shadow-sm">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" name="correo" class="form-control no-left-border" placeholder="usuario@taller371.com" required>
                    </div>
                </div>
                <div class="col-sm-6">
                    <label class="form-label">Contraseña *</label>
                    <input type="password" name="password" class="form-control" placeholder="Mín. 8 caracteres" required>
                    <div class="form-text mt-2" style="font-size: 0.75rem; color: var(--color-text-muted) !important;">
                        Al menos 8 caracteres, 1 mayúscula, 1 número y 1 símbolo.
                    </div>
                </div>
                <div class="col-sm-6">
                    <label class="form-label">Confirmar Contraseña *</label>
                    <input type="password" name="password_confirm" class="form-control" placeholder="Repite la contraseña" required>
                </div>
            </div>

            <button type="submit" class="btn-primary-custom mt-4">
                <i class="fas fa-user-plus me-2"></i> Crear Cuenta
            </button>

        </form>

        <p class="text-center mt-4 mb-0" style="font-size:0.85rem; color: var(--color-text-muted);">
            ¿Ya tienes cuenta? 
            <a href="login.php" class="fw-semibold text-decoration-none" style="color: var(--color-accent);">Inicia sesión</a>
        </p>
        <div class="text-center mt-3">
            <a href="../../index.php" class="text-decoration-none" style="font-size:0.85rem; color: var(--color-text-muted);">
                <i class="fas fa-arrow-left me-1"></i> Volver al inicio
            </a>
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
        confirmButtonColor: '#697565',
        background: '#3c3d37',
        color: '#f5f5f5'
    });
</script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
