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
    <title>Recuperar Contraseña – TallerPro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background: #f3f4f6; min-height: 100vh; }

        .recover-panel-left {
            background: linear-gradient(135deg, #1a3a6b 0%, #2563eb 100%);
            min-height: 100%;
        }

        .input-group-text { background: #e9ecef; border-right: 0; font-size: 0.85rem; }
        .form-control      { border-left: 0; font-size: 0.875rem; }
        .form-control:focus { box-shadow: none; border-color: #2563eb; }
        .form-label        { font-size: 0.8rem; }

        .btn-recuperar { background: #2563eb; border: none; font-weight: 600; font-size: 0.875rem; }
        .btn-recuperar:hover { background: #1a3a6b; }

        /* Indicador de pasos */
        .steps { display: flex; gap: 0.5rem; margin-bottom: 1.4rem; }
        .step {
            flex: 1;
            height: 4px;
            border-radius: 4px;
            background: #e2e8f0;
            transition: background 0.3s;
        }
        .step.active   { background: #2563eb; }
        .step.done     { background: #22c55e; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center p-3">

<div class="card shadow-lg border-0 rounded-4 overflow-hidden" style="max-width:640px; width:100%;">
    <div class="row g-0">

        <!-- Panel izquierdo -->
        <div class="col-md-4 recover-panel-left d-none d-md-flex flex-column justify-content-between p-4 text-white">
            <div>
                <div class="mb-3" style="font-size:2.2rem;"><i class="fas fa-key"></i></div>
                <h5 class="fw-bold">Recuperar acceso</h5>
                <p class="text-white-50 small mt-2">
                    Verifica tu identidad con tu documento y teléfono registrados para restablecer tu contraseña.
                </p>
            </div>
            <div class="bg-white bg-opacity-10 rounded-3 p-2 d-flex align-items-center gap-2">
                <i class="fas fa-shield-halved"></i>
                <p class="mb-0" style="font-size:0.72rem;">Proceso seguro de verificación.</p>
            </div>
        </div>

        <!-- Formulario -->
        <div class="col-md-8 p-4 d-flex flex-column justify-content-center bg-white">

            <h5 class="fw-bold text-dark mb-1">Restablecer Contraseña</h5>
            <p class="text-muted mb-3" style="font-size:0.8rem;">
                Ingresa tu documento, teléfono y la nueva contraseña.
            </p>

            <!-- Barra de pasos visual -->
            <div class="steps">
                <div class="step active" id="step1bar"></div>
                <div class="step active" id="step2bar"></div>
                <div class="step" id="step3bar"></div>
            </div>

            <form action="../../controllers/AuthController.php?accion=recuperar" method="POST"
                  id="formRecuperar">

                <!-- Paso 1: Verificación de identidad -->
                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary">Número de Documento</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-id-card text-secondary"></i></span>
                        <input type="text"
                               name="email"
                               class="form-control"
                               placeholder="Tu número de documento"
                               required
                               autocomplete="off">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary">Teléfono Registrado</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-phone text-secondary"></i></span>
                        <input type="tel"
                               name="telefono"
                               class="form-control"
                               placeholder="Número de teléfono"
                               required
                               autocomplete="off">
                    </div>
                </div>

                <hr class="my-3">

                <!-- Paso 2: Nueva contraseña -->
                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary">Nueva Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock text-secondary"></i></span>
                        <input type="password"
                               name="password"
                               id="passInput"
                               class="form-control"
                               placeholder="Mín. 6 caracteres"
                               required
                               oninput="checkStrength(this.value)">
                        <button type="button"
                                class="btn btn-outline-secondary border-start-0"
                                onclick="togglePass('passInput','eyeIcon1')">
                            <i class="fas fa-eye" id="eyeIcon1"></i>
                        </button>
                    </div>
                    <!-- Indicador de fortaleza -->
                    <div class="mt-1">
                        <div class="progress" style="height:4px;">
                            <div class="progress-bar" id="strengthBar" style="width:0%; transition:width 0.3s;"></div>
                        </div>
                        <div class="form-text" id="strengthText"></div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold text-secondary">Confirmar Nueva Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock text-secondary"></i></span>
                        <input type="password"
                               name="password_confirm"
                               id="passConfirm"
                               class="form-control"
                               placeholder="Repite la contraseña"
                               required>
                        <button type="button"
                                class="btn btn-outline-secondary border-start-0"
                                onclick="togglePass('passConfirm','eyeIcon2')">
                            <i class="fas fa-eye" id="eyeIcon2"></i>
                        </button>
                    </div>
                    <div class="form-text text-danger d-none" id="passError">
                        Las contraseñas no coinciden.
                    </div>
                </div>

                <button type="submit" class="btn btn-recuperar btn-primary w-100 py-2 rounded-3">
                    <i class="fas fa-rotate-right me-2"></i> Restablecer Contraseña
                </button>

            </form>

            <p class="text-center text-muted small mt-3 mb-0">
                ¿Recordaste tu contraseña?
                <a href="login.php" class="text-primary fw-semibold text-decoration-none">Inicia sesión</a>
            </p>

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
// Mostrar/ocultar contraseña
function togglePass(inputId, iconId) {
    const inp = document.getElementById(inputId);
    const ico = document.getElementById(iconId);
    if (inp.type === 'password') {
        inp.type = 'text';
        ico.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        inp.type = 'password';
        ico.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

// Indicador de fortaleza de contraseña
function checkStrength(val) {
    const bar  = document.getElementById('strengthBar');
    const text = document.getElementById('strengthText');
    let score  = 0;
    if (val.length >= 6)                        score++;
    if (val.length >= 10)                       score++;
    if (/[A-Z]/.test(val))                      score++;
    if (/[0-9]/.test(val))                      score++;
    if (/[^A-Za-z0-9]/.test(val))              score++;

    const levels = [
        { pct: '20%',  color: '#ef4444', label: 'Muy débil'  },
        { pct: '40%',  color: '#f97316', label: 'Débil'      },
        { pct: '60%',  color: '#eab308', label: 'Regular'    },
        { pct: '80%',  color: '#22c55e', label: 'Fuerte'     },
        { pct: '100%', color: '#16a34a', label: 'Muy fuerte' },
    ];

    const lvl = levels[Math.max(0, score - 1)] ?? levels[0];
    bar.style.width           = val.length ? lvl.pct   : '0%';
    bar.style.backgroundColor = val.length ? lvl.color : '';
    text.textContent          = val.length ? lvl.label : '';

    // Actualizar barra de pasos
    document.getElementById('step3bar').classList.toggle('active', score >= 3);
}

// Validar que las contraseñas coincidan antes de enviar
document.getElementById('formRecuperar').addEventListener('submit', function (e) {
    const p1  = document.getElementById('passInput').value;
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

// Feedback en tiempo real al confirmar
document.getElementById('passConfirm').addEventListener('input', function () {
    const p1  = document.getElementById('passInput').value;
    const err = document.getElementById('passError');
    err.classList.toggle('d-none', this.value === p1 || this.value === '');
});
</script>
</body>
</html>
