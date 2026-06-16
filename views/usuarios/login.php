<?php
session_start();
$alert        = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);
$abrirModal   = ($_GET['panel'] ?? '') === 'recuperar';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión – Latoneria y Pintura 371</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --color-bg: #181c14; /* very dark, almost black */
            --color-panel: #3c3d37; /* dark grayish brown */
            --color-accent: #697565; /* olive grayish green */
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
            max-width: 900px; 
            width: 100%;
            border-radius: 20px; 
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
            background: var(--color-panel);
            border: 1px solid rgba(255,255,255,0.05);
        }

        .panel-left {
            position: relative;
            background-image: url('../../public/img/car_login.png');
            background-size: cover;
            background-position: center;
            min-height: 500px;
        }

        .panel-left::before {
            content: '';
            position: absolute;
            top:0; left:0; right:0; bottom:0;
            background: linear-gradient(to right, rgba(24, 28, 20, 0.8) 0%, rgba(60, 61, 55, 0.4) 100%);
        }

        .panel-left-content {
            position: relative;
            z-index: 1;
            padding: 3rem;
            display: flex; 
            flex-direction: column; 
            justify-content: center;
            height: 100%;
            color: #fff;
        }

        .panel-left-content h2 { font-weight: 800; font-size: 2.5rem; letter-spacing: -1px; margin-bottom: 1rem; text-shadow: 2px 2px 4px rgba(0,0,0,0.5);}
        .panel-left-content p { font-size: 1.1rem; color: rgba(255,255,255,.8); max-width: 80%; }
        
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
            margin-bottom: auto;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255,255,255,0.1);
        }

        .panel-right { 
            background: var(--color-panel); 
            padding: 3rem 4rem; 
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
            border-left: 0; 
            color: #fff;
            padding: 0.75rem 1rem;
        }
        .form-control:focus { 
            background: rgba(24, 28, 20, 0.8);
            box-shadow: none; 
            border-color: var(--color-accent); 
            color: #fff;
        }
        .form-control::placeholder {
            color: #6b7280;
        }

        .btn-eye {
            background: rgba(24, 28, 20, 0.6); 
            border: 1px solid rgba(255,255,255,0.1); 
            border-left: 0;
            color: var(--color-text-muted);
        }
        .btn-eye:hover {
            color: #fff;
            background: rgba(24, 28, 20, 0.8); 
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

        .link-forgot {
            font-size: 0.8rem; 
            color: var(--color-accent);
            background: none; 
            border: none; 
            padding: 0;
            cursor: pointer; 
            text-decoration: none;
            transition: color 0.2s;
        }
        .link-forgot:hover { color: #fff; text-decoration: underline; }

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

        /* Modal recuperar */
        .modal-content {
            background: var(--color-panel);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .modal-header-custom {
            background: var(--color-bg);
            color: var(--color-accent); 
            border-bottom: 1px solid rgba(255,255,255,0.05);
            border-radius: 12px 12px 0 0;
        }
        .modal-footer {
            border-top: 1px solid rgba(255,255,255,0.05);
        }
        
        .metodo-btn {
            border: 1px solid rgba(255,255,255,0.1);
            color: var(--color-text-muted);
            background: rgba(24, 28, 20, 0.4);
            font-size: 0.85rem;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .metodo-btn:hover { 
            border-color: var(--color-accent); 
            color: #fff; 
            background: rgba(24, 28, 20, 0.8); 
        }
        .metodo-btn.active-metodo {
            border-color: var(--color-accent);
            background: rgba(105, 117, 101, 0.15);
            color: var(--color-accent);
            font-weight: 600;
        }
        
        .modal-body .form-control, .modal-body .input-group-text, .modal-body .btn-eye {
             background: rgba(24, 28, 20, 0.4); 
        }
        
        .btn-close-white { filter: invert(1) grayscale(100%) brightness(200%); }

        @media (max-width: 768px) {
            .panel-right { padding: 2rem; }
            .panel-left { min-height: 250px; }
            .panel-left-content { padding: 2rem; }
            .panel-left-content h2 { font-size: 2rem; }
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center p-3">

<!-- ══ Tarjeta login ══ -->
<div class="auth-card">
    <div class="row g-0 h-100">

        <!-- Panel izquierdo (Imagen) -->
        <div class="col-md-6 panel-left d-none d-md-block">
            <div class="panel-left-content">
                <div class="brand-logo">
                    <img src="../../imagen/logo.jpeg" alt="Logo" style="width:28px;height:28px;border-radius:6px;object-fit:cover;"> 371 Taller
                </div>
                <div>
                    <h2>Latonería y Pintura 371</h2>
                    <p>Excelencia y precisión en cada detalle. Deja tu vehículo como nuevo con nuestros especialistas.</p>
                </div>
            </div>
        </div>

        <!-- Formulario login -->
        <div class="col-md-6 panel-right">
            
            <div class="d-md-none mb-4 text-center">
                 <div class="brand-logo mx-auto" style="margin-bottom:0;">
                    <img src="../../imagen/logo.jpeg" alt="Logo" style="width:28px;height:28px;border-radius:6px;object-fit:cover;"> Latonería y Pintura 371
                </div>
            </div>

            <h5 class="auth-title">Iniciar Sesión</h5>
            <p class="auth-subtitle">Ingresa tus credenciales para acceder al sistema</p>

            <form action="../../controllers/AuthController.php" method="POST">

                <div class="mb-4">
                    <label class="form-label">Correo Electrónico</label>
                    <div class="input-group shadow-sm">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" name="email" class="form-control"
                               placeholder="usuario@taller371.com" required autocomplete="username">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Contraseña</label>
                    <div class="input-group shadow-sm">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password" id="passLogin"
                               class="form-control" placeholder="••••••••" required autocomplete="current-password">
                        <button type="button" class="btn btn-eye"
                                onclick="togglePass('passLogin','eyeLogin')">
                            <i class="fas fa-eye" id="eyeLogin"></i>
                        </button>
                    </div>
                </div>

                <div class="text-end mb-4">
                    <button type="button" class="link-forgot"
                            data-bs-toggle="modal" data-bs-target="#modalRecuperar">
                        ¿Olvidaste tu contraseña?
                    </button>
                </div>

                <button type="submit" class="btn-primary-custom">
                    <i class="fas fa-right-to-bracket me-2"></i> Ingresar al Sistema
                </button>

            </form>

            <p class="text-center mt-4 mb-0" style="font-size:0.85rem; color: var(--color-text-muted);">
                ¿No tienes cuenta?
                <a href="registre.php" class="fw-semibold text-decoration-none" style="color: var(--color-accent);">Regístrate aquí</a>
            </p>
        </div>

    </div>
</div>

<!-- ══ Modal Recuperar Contraseña ══ -->
<div class="modal fade" id="modalRecuperar" tabindex="-1" aria-labelledby="modalRecuperarLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">

            <div class="modal-header modal-header-custom">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-key"></i>
                    <h6 class="modal-title mb-0 fw-bold" id="modalRecuperarLabel">Recuperar Contraseña</h6>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form action="../../controllers/AuthController.php?accion=recuperar" method="POST"
                  id="formRecuperar">
                <div class="modal-body px-4 py-4">

                    <p class="mb-4" style="font-size:0.9rem; color: var(--color-text-muted);">
                        Elige cómo verificar tu identidad y luego crea una nueva contraseña.
                    </p>

                    <!-- Selector de método -->
                    <div class="mb-4">
                        <label class="form-label">Verificar con</label>
                        <div class="d-flex gap-2">
                            <button type="button" id="btnMetodoCorreo"
                                    class="btn flex-fill metodo-btn active-metodo"
                                    onclick="setMetodo('correo')">
                                <i class="fas fa-envelope me-1"></i> Correo
                            </button>
                            <button type="button" id="btnMetodoTel"
                                    class="btn flex-fill metodo-btn"
                                    onclick="setMetodo('telefono')">
                                <i class="fas fa-phone me-1"></i> Teléfono
                            </button>
                        </div>
                        <input type="hidden" name="metodo" id="metodoInput" value="correo">
                    </div>

                    <!-- Campo correo -->
                    <div class="mb-4" id="campoCorreo">
                        <label class="form-label">Correo Electrónico *</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" name="correo" id="inputCorreo" class="form-control"
                                   placeholder="usuario@taller371.com" autocomplete="off">
                        </div>
                    </div>

                    <!-- Campo teléfono -->
                    <div class="mb-4 d-none" id="campoTelefono">
                        <label class="form-label">Teléfono Registrado *</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                            <input type="tel" name="telefono" id="inputTelefono" class="form-control"
                                   placeholder="300 123 4567" autocomplete="off">
                        </div>
                    </div>

                    <hr class="my-4" style="border-color: rgba(255,255,255,0.1);">

                    <!-- Nueva contraseña -->
                    <div class="mb-3">
                        <label class="form-label">Nueva Contraseña *</label>
                        <div class="input-group mb-2">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" name="password" id="passNueva"
                                   class="form-control" placeholder="Mín. 6 caracteres"
                                   required oninput="checkStrength(this.value)">
                            <button type="button" class="btn btn-eye"
                                    onclick="togglePass('passNueva','eyeNueva')">
                                <i class="fas fa-eye" id="eyeNueva"></i>
                            </button>
                        </div>
                        <!-- Barra de fortaleza -->
                        <div>
                            <div class="progress" style="height:4px; background: rgba(24,28,20,0.6);">
                                <div id="strengthBar" style="width:0%; border-radius:4px; transition: all 0.3s;"></div>
                            </div>
                            <div id="strengthText" style="font-size: 0.75rem; min-height: 1.2rem; margin-top:4px; color: var(--color-text-muted);"></div>
                        </div>
                    </div>

                    <!-- Confirmar contraseña -->
                    <div class="mb-1">
                        <label class="form-label">Confirmar Contraseña *</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" name="password_confirm" id="passConfirm"
                                   class="form-control" placeholder="Repite la contraseña" required>
                            <button type="button" class="btn btn-eye"
                                    onclick="togglePass('passConfirm','eyeConfirm')">
                                <i class="fas fa-eye" id="eyeConfirm"></i>
                            </button>
                        </div>
                        <div class="text-danger d-none mt-2" id="passError" style="font-size:0.8rem;">
                            <i class="fas fa-circle-exclamation me-1"></i> Las contraseñas no coinciden.
                        </div>
                    </div>

                </div>

                <div class="modal-footer px-4 py-3">
                    <button type="button" class="btn btn-outline-light btn-sm" style="border-color: rgba(255,255,255,0.2); color: #d1d5db;"
                            data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-semibold px-4" style="background: var(--color-accent); border:none;">
                        <i class="fas fa-rotate-right me-1"></i> Restablecer
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<?php if ($alert): ?>
<script>
    Swal.fire({
        icon:  '<?= htmlspecialchars($alert['icon'])  ?>',
        title: '<?= htmlspecialchars($alert['title']) ?>',
        text:  '<?= htmlspecialchars($alert['text'])  ?>',
        confirmButtonText:  'Aceptar',
        confirmButtonColor: '#697565',
        background: '#3c3d37',
        color: '#f5f5f5'
    });
</script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Abrir modal automáticamente si el controlador redirigió con ?panel=recuperar
<?php if ($abrirModal): ?>
document.addEventListener('DOMContentLoaded', function () {
    new bootstrap.Modal(document.getElementById('modalRecuperar')).show();
});
<?php endif; ?>

// Selector de método de recuperación
function setMetodo(metodo) {
    document.getElementById('metodoInput').value = metodo;

    const campoCorreo   = document.getElementById('campoCorreo');
    const campoTelefono = document.getElementById('campoTelefono');
    const inputCorreo   = document.getElementById('inputCorreo');
    const inputTelefono = document.getElementById('inputTelefono');
    const btnCorreo     = document.getElementById('btnMetodoCorreo');
    const btnTel        = document.getElementById('btnMetodoTel');

    if (metodo === 'correo') {
        campoCorreo.classList.remove('d-none');
        campoTelefono.classList.add('d-none');
        inputCorreo.required   = true;
        inputTelefono.required = false;
        inputTelefono.value    = '';
        btnCorreo.classList.add('active-metodo');
        btnTel.classList.remove('active-metodo');
    } else {
        campoTelefono.classList.remove('d-none');
        campoCorreo.classList.add('d-none');
        inputTelefono.required = true;
        inputCorreo.required   = false;
        inputCorreo.value      = '';
        btnTel.classList.add('active-metodo');
        btnCorreo.classList.remove('active-metodo');
    }
}

// Mostrar/ocultar contraseña
function togglePass(inputId, iconId) {
    const inp = document.getElementById(inputId);
    const ico = document.getElementById(iconId);
    inp.type = inp.type === 'password' ? 'text' : 'password';
    ico.classList.toggle('fa-eye');
    ico.classList.toggle('fa-eye-slash');
}

// Indicador de fortaleza
function checkStrength(val) {
    const bar  = document.getElementById('strengthBar');
    const text = document.getElementById('strengthText');
    let score  = 0;
    if (val.length >= 6)           score++;
    if (val.length >= 10)          score++;
    if (/[A-Z]/.test(val))         score++;
    if (/[0-9]/.test(val))         score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const levels = [
        { pct:'20%',  color:'#ef4444', label:'Muy débil'  },
        { pct:'40%',  color:'#f97316', label:'Débil'      },
        { pct:'60%',  color:'#eab308', label:'Regular'    },
        { pct:'80%',  color:'#22c55e', label:'Fuerte'     },
        { pct:'100%', color:'#16a34a', label:'Muy fuerte' },
    ];
    const lvl = levels[Math.max(0, score - 1)];
    bar.style.width           = val.length ? lvl.pct   : '0%';
    bar.style.backgroundColor = val.length ? lvl.color : '';
    text.textContent          = val.length ? lvl.label : '';
}

// Validar que las contraseñas coincidan antes de enviar
document.getElementById('formRecuperar').addEventListener('submit', function (e) {
    const p1  = document.getElementById('passNueva').value;
    const p2  = document.getElementById('passConfirm').value;
    const err = document.getElementById('passError');
    if (p1 !== p2) {
        e.preventDefault();
        err.classList.remove('d-none');
        document.getElementById('passConfirm').focus();
    } else {
        err.classList.add('d-none');
    }
});

// Feedback en tiempo real
document.getElementById('passConfirm').addEventListener('input', function () {
    const p1 = document.getElementById('passNueva').value;
    document.getElementById('passError')
            .classList.toggle('d-none', this.value === p1 || this.value === '');
});
</script>
</body>
</html>
