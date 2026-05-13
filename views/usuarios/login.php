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
    <title>Iniciar Sesión – TallerPro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background: #f3f4f6; min-height: 100vh; }

        .auth-card {
            max-width: 640px; width: 100%;
            border-radius: 16px; overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.12);
        }

        .panel-left {
            background: linear-gradient(135deg, #1a3a6b 0%, #2563eb 100%);
            padding: 2rem 1.5rem;
            display: flex; flex-direction: column; justify-content: space-between;
            color: #fff;
        }
        .panel-left .brand-icon { font-size: 2.2rem; }
        .panel-left h5  { font-size: 1rem; font-weight: 700; }
        .panel-left p   { font-size: 0.78rem; color: rgba(255,255,255,.6); }
        .panel-left .shield { font-size: 0.7rem; color: rgba(255,255,255,.5); }

        .panel-right { background: #fff; padding: 2rem 1.8rem; }

        .form-label   { font-size: 0.78rem; font-weight: 600; color: #374151; margin-bottom: 0.25rem; }
        .form-control { font-size: 0.85rem; border-left: 0; }
        .form-control:focus { box-shadow: none; border-color: #2563eb; }
        .input-group-text { background: #e9ecef; border-right: 0; font-size: 0.82rem; }

        .btn-primary-custom {
            background: #2563eb; color: #fff; border: none;
            border-radius: 8px; font-size: 0.875rem; font-weight: 600;
            padding: 0.5rem; width: 100%; transition: background 0.2s;
        }
        .btn-primary-custom:hover { background: #1d4ed8; }

        .link-forgot {
            font-size: 0.75rem; color: #2563eb;
            background: none; border: none; padding: 0;
            cursor: pointer; text-decoration: none;
        }
        .link-forgot:hover { text-decoration: underline; }

        /* Modal recuperar */
        .modal-header-custom {
            background: linear-gradient(90deg, #1a3a6b, #2563eb);
            color: #fff; border-radius: 12px 12px 0 0;
        }
        #strengthBar  { height: 4px; border-radius: 4px; transition: width .3s, background .3s; }
        #strengthText { font-size: 0.7rem; color: #64748b; min-height: 1rem; }

        /* Botones selector de método */
        .metodo-btn {
            border: 1.5px solid #d1d5db;
            color: #374151;
            background: #f9fafb;
            font-size: 0.78rem;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .metodo-btn:hover    { border-color: #2563eb; color: #2563eb; background: #eff6ff; }
        .metodo-btn.active-metodo {
            border-color: #2563eb;
            background: #eff6ff;
            color: #2563eb;
            font-weight: 600;
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center p-3">

<!-- ══ Tarjeta login ══ -->
<div class="auth-card card border-0">
    <div class="row g-0">

        <!-- Panel izquierdo -->
        <div class="col-md-4 panel-left d-none d-md-flex flex-column">
            <div>
                <div class="brand-icon mb-3"><i class="fas fa-car-side"></i></div>
                <h5>¡Bienvenido de vuelta!</h5>
                <p>Accede a tu panel para gestionar órdenes, vehículos, inventario y mucho más.</p>
            </div>
            <div class="d-flex align-items-center gap-2 shield">
                <i class="fas fa-shield-halved"></i>
                <span>Acceso seguro con cifrado de datos.</span>
            </div>
        </div>

        <!-- Formulario login -->
        <div class="col-md-8 panel-right d-flex flex-column justify-content-center">
            <h5 class="fw-bold text-dark mb-1">Iniciar Sesión</h5>
            <p class="text-muted mb-3" style="font-size:0.78rem;">Ingresa tus credenciales para continuar</p>

            <form action="../../controllers/AuthController.php" method="POST">

                <div class="mb-3">
                    <label class="form-label">Correo Electrónico</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope text-secondary"></i></span>
                        <input type="email" name="email" class="form-control"
                               placeholder="usuario@taller.com" required autocomplete="username">
                    </div>
                </div>

                <div class="mb-2">
                    <label class="form-label">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock text-secondary"></i></span>
                        <input type="password" name="password" id="passLogin"
                               class="form-control" placeholder="••••••••" required autocomplete="current-password">
                        <button type="button" class="btn btn-outline-secondary border-start-0"
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
                    <i class="fas fa-right-to-bracket me-2"></i> Entrar al Sistema
                </button>

            </form>

            <p class="text-center text-muted mt-3 mb-0" style="font-size:0.75rem;">
                ¿No tienes cuenta?
                <a href="registre.php" class="text-primary fw-semibold text-decoration-none">Regístrate aquí</a>
            </p>
        </div>

    </div>
</div>

<!-- ══ Modal Recuperar Contraseña ══ -->
<div class="modal fade" id="modalRecuperar" tabindex="-1" aria-labelledby="modalRecuperarLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">

            <div class="modal-header modal-header-custom">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-key"></i>
                    <h6 class="modal-title mb-0 fw-bold" id="modalRecuperarLabel">Recuperar Contraseña</h6>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form action="../../controllers/AuthController.php?accion=recuperar" method="POST"
                  id="formRecuperar">
                <div class="modal-body px-4 py-3">

                    <p class="text-muted mb-3" style="font-size:0.8rem;">
                        Elige cómo verificar tu identidad y luego crea una nueva contraseña.
                    </p>

                    <!-- Selector de método -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:0.78rem;">Verificar con</label>
                        <div class="d-flex gap-2">
                            <button type="button" id="btnMetodoCorreo"
                                    class="btn btn-sm flex-fill metodo-btn active-metodo"
                                    onclick="setMetodo('correo')">
                                <i class="fas fa-envelope me-1"></i> Correo
                            </button>
                            <button type="button" id="btnMetodoTel"
                                    class="btn btn-sm flex-fill metodo-btn"
                                    onclick="setMetodo('telefono')">
                                <i class="fas fa-phone me-1"></i> Teléfono
                            </button>
                        </div>
                        <input type="hidden" name="metodo" id="metodoInput" value="correo">
                    </div>

                    <!-- Campo correo -->
                    <div class="mb-3" id="campoCorreo">
                        <label class="form-label fw-semibold" style="font-size:0.78rem;">Correo Electrónico *</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope text-secondary"></i></span>
                            <input type="email" name="correo" id="inputCorreo" class="form-control"
                                   placeholder="usuario@taller.com" autocomplete="off">
                        </div>
                    </div>

                    <!-- Campo teléfono -->
                    <div class="mb-3 d-none" id="campoTelefono">
                        <label class="form-label fw-semibold" style="font-size:0.78rem;">Teléfono Registrado *</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-phone text-secondary"></i></span>
                            <input type="tel" name="telefono" id="inputTelefono" class="form-control"
                                   placeholder="300 123 4567" autocomplete="off">
                        </div>
                    </div>

                    <hr class="my-3">

                    <!-- Nueva contraseña -->
                    <div class="mb-2">
                        <label class="form-label fw-semibold" style="font-size:0.78rem;">Nueva Contraseña *</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock text-secondary"></i></span>
                            <input type="password" name="password" id="passNueva"
                                   class="form-control" placeholder="Mín. 6 caracteres"
                                   required oninput="checkStrength(this.value)">
                            <button type="button" class="btn btn-outline-secondary border-start-0"
                                    onclick="togglePass('passNueva','eyeNueva')">
                                <i class="fas fa-eye" id="eyeNueva"></i>
                            </button>
                        </div>
                        <!-- Barra de fortaleza -->
                        <div class="mt-1">
                            <div class="progress" style="height:4px;">
                                <div id="strengthBar" style="width:0%;"></div>
                            </div>
                            <div id="strengthText"></div>
                        </div>
                    </div>

                    <!-- Confirmar contraseña -->
                    <div class="mb-1">
                        <label class="form-label fw-semibold" style="font-size:0.78rem;">Confirmar Contraseña *</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock text-secondary"></i></span>
                            <input type="password" name="password_confirm" id="passConfirm"
                                   class="form-control" placeholder="Repite la contraseña" required>
                            <button type="button" class="btn btn-outline-secondary border-start-0"
                                    onclick="togglePass('passConfirm','eyeConfirm')">
                                <i class="fas fa-eye" id="eyeConfirm"></i>
                            </button>
                        </div>
                        <div class="text-danger d-none mt-1" id="passError" style="font-size:0.72rem;">
                            Las contraseñas no coinciden.
                        </div>
                    </div>

                </div>

                <div class="modal-footer px-4 py-3 border-top">
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                            data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-semibold px-4">
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
        confirmButtonColor: '#2563eb'
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
