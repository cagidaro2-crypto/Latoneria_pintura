# Documentación: login.php

## Descripción general
Vista de inicio de sesión del sistema. Presenta un diseño de dos paneles: izquierdo con imagen de fondo del taller y texto de marca, derecho con el formulario de login. Incluye un modal de recuperación de contraseña que acepta verificación por correo o teléfono. Muestra alertas mediante SweetAlert2 cuando vuelve de acciones del controlador. Puede abrir el modal automáticamente si la URL contiene `?panel=recuperar`.

## Dependencias
- `controllers/AuthController.php` — Procesa el login y la recuperación de contraseña
- Bootstrap 5.3.3 (CDN)
- Font Awesome 6.5.0 (CDN)
- Google Fonts Inter (CDN)
- SweetAlert2 v11 (CDN)

## Código documentado línea por línea

```php
// ── BLOQUE PHP INICIAL ───────────────────────────────────────────────────

// Línea 1: Apertura del bloque PHP
<?php

// Línea 2: Inicia la sesión para leer y limpiar alertas almacenadas
session_start();

// Línea 3: Lee la alerta que pudo haber dejado AuthController tras una acción
$alert = $_SESSION['alert'] ?? null;

// Línea 4: Elimina la alerta de la sesión para que no se repita en recargas
unset($_SESSION['alert']);

// Línea 5: Verifica si la URL contiene ?panel=recuperar para abrir el modal automáticamente
// Usado cuando AuthController redirige de vuelta con el modal ya abierto
$abrirModal = ($_GET['panel'] ?? '') === 'recuperar';
?>

<!-- ── HEAD DEL DOCUMENTO ─────────────────────────────────────────────── -->

<!-- DOCTYPE y HTML con idioma español -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión – Latoneria y Pintura 371</title>

    <!-- Bootstrap 5 CSS para el sistema de grillas y componentes -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome para iconos de candado, usuario, ojo, etc. -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Fuente Inter de Google Fonts — tipografía del diseño oscuro -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- SweetAlert2 para las notificaciones popup estilizadas -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* ── Variables CSS del tema oscuro ────────────────────────────── */
        :root {
            --color-bg: #181c14;      /* Fondo casi negro con tono verde */
            --color-panel: #3c3d37;   /* Fondo del panel gris oscuro cálido */
            --color-accent: #697565;  /* Color acento: verde oliva grisáceo */
            --color-text-main: #f5f5f5;
            --color-text-muted: #a1a1aa;
        }

        /* Cuerpo: fondo oscuro con gradiente radial en esquina superior derecha */
        body { background-color: var(--color-bg); background-image: radial-gradient(circle at top right, #2a2e25, var(--color-bg)); }

        /* .auth-card: tarjeta centrada de máximo 1100px con borde sutil */
        .auth-card { max-width: 1100px; border-radius: 20px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,.7); }

        /* .panel-left: panel izquierdo con imagen de auto de fondo */
        .panel-left { background-image: url('../../public/img/car_login.png'); background-size: cover; min-height: 560px; }

        /* .panel-left::before: overlay semitransparente sobre la imagen */
        .panel-left::before { content:''; position:absolute; background: linear-gradient(to right, rgba(24,28,20,.8) 0%, rgba(60,61,55,.4) 100%); }

        /* .panel-left-content: contenido del panel izquierdo posicionado sobre el overlay */
        .panel-left-content { position: relative; z-index: 1; padding: 3rem; }

        /* .brand-logo: pill de marca con backdrop-filter blur */
        .brand-logo { background: rgba(24,28,20,.7); backdrop-filter: blur(5px); border-radius: 30px; }

        /* .panel-right: panel derecho con el formulario */
        .panel-right { background: var(--color-panel); padding: 3rem 4rem; }

        /* .form-control: inputs con fondo oscuro y borde sutil */
        .form-control { background: rgba(24,28,20,.6); border: 1px solid rgba(255,255,255,.1); color: #fff; }

        /* .btn-primary-custom: botón principal con sombra del color acento */
        .btn-primary-custom { background: var(--color-accent); box-shadow: 0 4px 15px rgba(105,117,101,.4); }
        .btn-primary-custom:hover { transform: translateY(-2px); }  /* Levita 2px al hover */

        /* Modal de recuperación con tema oscuro */
        .modal-content { background: var(--color-panel); color: #fff; }

        /* .metodo-btn: botones de selección de método (correo/teléfono) */
        .metodo-btn.active-metodo { border-color: var(--color-accent); background: rgba(105,117,101,.15); }
    </style>
</head>

<!-- ── CUERPO: centrado con flexbox ──────────────────────────────────── -->
<body class="d-flex align-items-center justify-content-center p-3">

<!-- ══ TARJETA DE LOGIN ════════════════════════════════════════════════ -->
<div class="auth-card">
    <div class="row g-0 h-100">

        <!-- Panel izquierdo: solo visible en pantallas md+ (d-none d-md-block) -->
        <div class="col-md-6 panel-left d-none d-md-block">
            <div class="panel-left-content">
                <!-- Logo y nombre del taller como "pill" en la esquina superior -->
                <div class="brand-logo">
                    <img src="../../imagen/logo.jpeg" alt="Logo" style="width:28px;..."> 371 Taller
                </div>
                <div>
                    <!-- Título principal del panel izquierdo -->
                    <h2>Latonería y Pintura 371</h2>
                    <!-- Subtítulo descriptivo del taller -->
                    <p>Excelencia y precisión en cada detalle...</p>
                </div>
            </div>
        </div>

        <!-- Panel derecho: formulario de login -->
        <div class="col-md-6 panel-right">

            <!-- Logo visible solo en móvil (d-md-none) cuando el panel izquierdo está oculto -->
            <div class="d-md-none mb-4 text-center">
                <div class="brand-logo mx-auto">...</div>
            </div>

            <!-- Título y subtítulo del formulario -->
            <h5 class="auth-title">Iniciar Sesión</h5>
            <p class="auth-subtitle">Ingresa tus credenciales para acceder al sistema</p>

            <!-- Formulario POST que envía a AuthController (acción por defecto: login) -->
            <form action="../../controllers/AuthController.php" method="POST">

                <!-- Campo de correo electrónico con ícono -->
                <div class="mb-4">
                    <label class="form-label">Correo Electrónico</label>
                    <div class="input-group shadow-sm">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="usuario@taller371.com" required autocomplete="username">
                    </div>
                </div>

                <!-- Campo de contraseña con botón para mostrar/ocultar -->
                <div class="mb-3">
                    <label class="form-label">Contraseña</label>
                    <div class="input-group shadow-sm">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password" id="passLogin" class="form-control" required autocomplete="current-password">
                        <!-- Botón que llama a togglePass() para alternar entre text/password -->
                        <button type="button" class="btn btn-eye" onclick="togglePass('passLogin','eyeLogin')">
                            <i class="fas fa-eye" id="eyeLogin"></i>
                        </button>
                    </div>
                </div>

                <!-- Enlace que abre el modal de recuperación de contraseña -->
                <div class="text-end mb-4">
                    <button type="button" class="link-forgot" data-bs-toggle="modal" data-bs-target="#modalRecuperar">
                        ¿Olvidaste tu contraseña?
                    </button>
                </div>

                <!-- Botón de envío del formulario -->
                <button type="submit" class="btn-primary-custom">
                    <i class="fas fa-right-to-bracket me-2"></i> Ingresar al Sistema
                </button>
            </form>

            <!-- Enlace hacia la vista de registro -->
            <p class="text-center mt-4">
                ¿No tienes cuenta?
                <a href="registre.php" style="color: var(--color-accent);">Regístrate aquí</a>
            </p>
        </div>
    </div>
</div>

<!-- ══ MODAL RECUPERAR CONTRASEÑA ════════════════════════════════════ -->
<div class="modal fade" id="modalRecuperar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">

            <!-- Cabecera del modal con ícono de llave -->
            <div class="modal-header modal-header-custom">
                <h6 class="modal-title"><i class="fas fa-key"></i> Recuperar Contraseña</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <!-- Formulario POST a AuthController con acción 'recuperar' -->
            <form action="../../controllers/AuthController.php?accion=recuperar" method="POST" id="formRecuperar">
                <div class="modal-body px-4 py-4">

                    <!-- Selector de método: botones Correo / Teléfono -->
                    <!-- Al hacer clic llama a setMetodo() que muestra/oculta los campos -->
                    <div class="mb-4">
                        <label class="form-label">Verificar con</label>
                        <div class="d-flex gap-2">
                            <button type="button" id="btnMetodoCorreo" class="btn flex-fill metodo-btn active-metodo" onclick="setMetodo('correo')">
                                <i class="fas fa-envelope me-1"></i> Correo
                            </button>
                            <button type="button" id="btnMetodoTel" class="btn flex-fill metodo-btn" onclick="setMetodo('telefono')">
                                <i class="fas fa-phone me-1"></i> Teléfono
                            </button>
                        </div>
                        <!-- Input oculto que envía el método seleccionado al servidor -->
                        <input type="hidden" name="metodo" id="metodoInput" value="correo">
                    </div>

                    <!-- Campo de correo (visible por defecto) -->
                    <div class="mb-4" id="campoCorreo">
                        <input type="email" name="correo" id="inputCorreo" class="form-control" placeholder="usuario@taller371.com">
                    </div>

                    <!-- Campo de teléfono (oculto por defecto con d-none) -->
                    <div class="mb-4 d-none" id="campoTelefono">
                        <input type="tel" name="telefono" id="inputTelefono" class="form-control" placeholder="300 123 4567">
                    </div>

                    <!-- Nueva contraseña con indicador de fortaleza -->
                    <div class="mb-3">
                        <input type="password" name="password" id="passNueva" class="form-control"
                               oninput="checkStrength(this.value)">  <!-- Llama checkStrength en cada tecla -->
                        <!-- Barra visual de fortaleza (0-100%) -->
                        <div class="progress" style="height:4px;">
                            <div id="strengthBar" style="width:0%;..."></div>
                        </div>
                        <!-- Texto de nivel: Muy débil / Débil / Regular / Fuerte / Muy fuerte -->
                        <div id="strengthText"></div>
                    </div>

                    <!-- Confirmar contraseña con validación en tiempo real -->
                    <div class="mb-1">
                        <input type="password" name="password_confirm" id="passConfirm" class="form-control">
                        <!-- Mensaje de error oculto que aparece si las contraseñas no coinciden -->
                        <div class="text-danger d-none" id="passError">Las contraseñas no coinciden.</div>
                    </div>
                </div>

                <!-- Botones del modal -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-light btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm" style="background: var(--color-accent);">
                        <i class="fas fa-rotate-right me-1"></i> Restablecer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── ALERT DINÁMICO DESDE SESIÓN ────────────────────────────────── -->
<!-- Si AuthController dejó una alerta en sesión, se muestra con SweetAlert2 -->
<?php if ($alert): ?>
<script>
    Swal.fire({
        icon:  '<?= htmlspecialchars($alert['icon'])  ?>',
        title: '<?= htmlspecialchars($alert['title']) ?>',
        text:  '<?= htmlspecialchars($alert['text'])  ?>',
        confirmButtonText:  'Aceptar',
        confirmButtonColor: '#697565',  // Color del botón de confirmación = acento
        background: '#3c3d37',          // Fondo oscuro del popup
        color: '#f5f5f5'                // Texto claro
    });
</script>
<?php endif; ?>

<!-- Bootstrap 5 JS Bundle para el modal -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Abre el modal automáticamente si la URL tiene ?panel=recuperar
<?php if ($abrirModal): ?>
document.addEventListener('DOMContentLoaded', function () {
    new bootstrap.Modal(document.getElementById('modalRecuperar')).show();
});
<?php endif; ?>

// setMetodo(): alterna entre los campos de correo y teléfono
// Actualiza el campo hidden 'metodo', muestra/oculta los campos y marca el botón activo
function setMetodo(metodo) { ... }

// togglePass(): alterna la visibilidad de un campo password
// Cambia type="password" a type="text" y actualiza el ícono del ojo
function togglePass(inputId, iconId) {
    const inp = document.getElementById(inputId);
    inp.type = inp.type === 'password' ? 'text' : 'password';
    document.getElementById(iconId).classList.toggle('fa-eye');
    document.getElementById(iconId).classList.toggle('fa-eye-slash');
}

// checkStrength(): evalúa la fortaleza de la contraseña (5 niveles)
// Criterios: longitud ≥6, longitud ≥10, mayúscula, número, carácter especial
function checkStrength(val) {
    // Actualiza la barra de progreso y el texto de nivel según el puntaje (0-5)
}

// Validación al enviar: verifica que las contraseñas coincidan
document.getElementById('formRecuperar').addEventListener('submit', function (e) {
    if (p1 !== p2) { e.preventDefault(); /* muestra error */ }
});

// Feedback en tiempo real al escribir en "confirmar contraseña"
document.getElementById('passConfirm').addEventListener('input', function () {
    document.getElementById('passError').classList.toggle('d-none', this.value === p1 || this.value === '');
});
</script>
```

## Resumen de funciones JavaScript

| Función | Parámetros | Descripción |
|---------|-----------|-------------|
| `setMetodo(metodo)` | `'correo'`\|`'telefono'` | Alterna entre campos de verificación en el modal |
| `togglePass(inputId, iconId)` | string, string | Muestra/oculta contraseña alternando el tipo del input |
| `checkStrength(val)` | string | Evalúa la fortaleza de la contraseña y actualiza la barra visual |
| Event `submit` en formRecuperar | — | Previene envío si las contraseñas no coinciden |
| Event `input` en passConfirm | — | Muestra/oculta el error de contraseñas en tiempo real |

## Flujo de ejecución
1. PHP lee y limpia la alerta de sesión.
2. Detecta si debe abrir el modal (parámetro `?panel=recuperar`).
3. Se renderiza el documento HTML con Bootstrap, FA e Inter.
4. Se muestra el layout de dos paneles (imagen izquierda + formulario derecho).
5. Si hay alerta, SweetAlert2 la muestra tras cargar la página.
6. Si `$abrirModal` es true, JavaScript abre el modal al cargar el DOM.
7. El usuario interactúa: login estándar o recuperación de contraseña con modal.
8. Los formularios hacen POST a `AuthController.php` que procesa y redirige.
