<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TallerPro – Sistema de Gestión para Taller</title>
    <link rel="shortcut icon" type="image/png" href="../img/ico.png">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --blue-dark:  #1a3a6b;
            --blue-mid:   #2563eb;
            --blue-light: #3b82f6;
            --gray-dark:  #374151;
            --gray-mid:   #6b7280;
            --gray-light: #f3f4f6;
        }
        body { background: #fff; font-family: 'Segoe UI', sans-serif; color: var(--gray-dark); }

        /* NAVBAR */
        .navbar-brand span { color: var(--blue-mid); font-weight: 800; }
        .navbar { border-bottom: 2px solid #e5e7eb; }
        .nav-link { color: var(--gray-dark) !important; font-weight: 500; }
        .nav-link:hover { color: var(--blue-mid) !important; }

        /* HERO CAROUSEL */
        .hero-slide { min-height: 520px; background-size: cover; background-position: center; position: relative; }
        .hero-slide::after { content:''; position:absolute; inset:0; background:rgba(26,58,107,.60); }
        .hero-content { position:relative; z-index:2; }

        /* SECTIONS */
        .section-title::after { content:''; display:block; width:60px; height:4px; background:var(--blue-mid); margin:10px auto 0; border-radius:2px; }
        .feature-icon { width:64px; height:64px; border-radius:50%; background:linear-gradient(135deg,var(--blue-dark),var(--blue-light)); display:flex; align-items:center; justify-content:center; font-size:1.6rem; color:#fff; margin:0 auto 1rem; }
        .card-border-top { border-top: 4px solid var(--blue-mid) !important; }
        .card-border-top-2 { border-top: 4px solid var(--gray-mid) !important; }

        /* FOOTER */
        footer { background: #111827; }
        footer a:hover { color: var(--blue-light) !important; }

        /* BTN CUSTOM */
        .btn-primary-custom { background: var(--blue-mid); border: none; color: #fff; font-weight: 600; }
        .btn-primary-custom:hover { background: var(--blue-dark); color: #fff; }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg bg-white sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="#">
            <i class="fas fa-car-side text-primary fs-3"></i>
            <span>TallerPro</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav ms-auto align-items-center gap-2">
                <li class="nav-item"><a class="nav-link" href="#inicio">Inicio</a></li>
                <li class="nav-item"><a class="nav-link" href="#nosotros">Nosotros</a></li>
                <li class="nav-item"><a class="nav-link" href="#servicios">Servicios</a></li>
                <li class="nav-item">
                    <a class="btn btn-primary-custom px-4 py-2 rounded-3" href="../views/usuarios/login.php">
                        <i class="fas fa-sign-in-alt me-1"></i> Ingresar
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- HERO CAROUSEL -->
<section id="inicio">
    <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner">

            <div class="carousel-item active">
                <div class="hero-slide d-flex align-items-center"
                     style="background-image:url('https://images.unsplash.com/photo-1558618666-fcd25c85cd64?auto=format&fit=crop&w=1600&q=80')">
                    <div class="container hero-content text-white py-5">
                        <h1 class="display-4 fw-bold mb-3">Gestión Total para Tu Taller</h1>
                        <p class="lead mb-4">Control de órdenes, inventario, clientes y vehículos en una sola plataforma.</p>
                        <a href="../views/usuarios/login.php" class="btn btn-light btn-lg fw-bold px-5">
                            Comenzar <i class="fas fa-arrow-right ms-2"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="carousel-item">
                <div class="hero-slide d-flex align-items-center"
                     style="background-image:url('https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=1600&q=80')">
                    <div class="container hero-content text-white py-5">
                        <h1 class="display-4 fw-bold mb-3">Seguimiento en Tiempo Real</h1>
                        <p class="lead mb-4">Tus clientes saben el estado de su vehículo en cada etapa del proceso.</p>
                        <a href="../views/usuarios/registre.php" class="btn btn-light btn-lg fw-bold px-5">
                            Regístrate <i class="fas fa-user-plus ms-2"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="carousel-item">
                <div class="hero-slide d-flex align-items-center"
                     style="background-image:url('https://images.unsplash.com/photo-1568605117036-5fe5e7bab0b7?auto=format&fit=crop&w=1600&q=80')">
                    <div class="container hero-content text-white py-5">
                        <h1 class="display-4 fw-bold mb-3">Cotizaciones y Facturación</h1>
                        <p class="lead mb-4">Genera cotizaciones profesionales y facturas en segundos.</p>
                        <a href="#servicios" class="btn btn-light btn-lg fw-bold px-5">
                            Ver servicios <i class="fas fa-chevron-down ms-2"></i>
                        </a>
                    </div>
                </div>
            </div>

        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon"></span>
        </button>
    </div>
</section>

<!-- MISIÓN / VISIÓN -->
<section id="nosotros" class="py-5 bg-white">
    <div class="container py-4">
        <h2 class="text-center fw-bold section-title mb-5">Sobre Nosotros</h2>
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm card-border-top p-4">
                    <div class="text-primary fs-2 mb-3"><i class="fas fa-bullseye"></i></div>
                    <h4 class="fw-bold">Nuestra Misión</h4>
                    <p class="text-muted">Proveer una solución tecnológica integral que optimice la operación de talleres
                        de latonería y pintura, facilitando la gestión de vehículos, clientes, inventario y facturación
                        de manera eficiente y segura.</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm card-border-top-2 p-4">
                    <div class="text-secondary fs-2 mb-3"><i class="fas fa-eye"></i></div>
                    <h4 class="fw-bold">Nuestra Visión</h4>
                    <p class="text-muted">Ser la plataforma de gestión de talleres automotrices más confiable de la región,
                        reconocida por su innovación, trazabilidad y capacidad de transformar los procesos del taller en
                        ventajas competitivas reales.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SERVICIOS / MÓDULOS -->
<section id="servicios" class="py-5" style="background:var(--gray-light)">
    <div class="container py-4">
        <h2 class="text-center fw-bold section-title mb-5">Módulos del Sistema</h2>
        <div class="row g-4 text-center">

            <div class="col-md-4 col-sm-6">
                <div class="feature-icon"><i class="fas fa-clipboard-list"></i></div>
                <h5 class="fw-bold">Órdenes de Servicio</h5>
                <p class="text-muted small">Crea y gestiona órdenes con seguimiento de estado en tiempo real.</p>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="feature-icon"><i class="fas fa-users"></i></div>
                <h5 class="fw-bold">Clientes y Empleados</h5>
                <p class="text-muted small">Registra y administra toda la información de clientes y personal.</p>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="feature-icon"><i class="fas fa-car"></i></div>
                <h5 class="fw-bold">Vehículos</h5>
                <p class="text-muted small">Gestiona el historial completo de cada vehículo en el taller.</p>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="feature-icon"><i class="fas fa-boxes-stacked"></i></div>
                <h5 class="fw-bold">Inventario</h5>
                <p class="text-muted small">Control de repuestos y materiales con alertas de stock bajo.</p>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="feature-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                <h5 class="fw-bold">Cotizaciones y Facturas</h5>
                <p class="text-muted small">Genera documentos profesionales y controla los pagos.</p>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="feature-icon"><i class="fas fa-calendar-check"></i></div>
                <h5 class="fw-bold">Agenda de Citas</h5>
                <p class="text-muted small">Permite a los clientes agendar y gestionar sus citas online.</p>
            </div>

        </div>
    </div>
</section>

<!-- FOOTER -->
<footer class="text-light py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <h5 class="fw-bold text-white mb-3"><i class="fas fa-car-side me-2 text-primary"></i>TallerPro</h5>
                <p class="small text-secondary">Solución integral para talleres de latonería y pintura. Gestión eficiente, clientes satisfechos.</p>
            </div>
            <div class="col-md-4">
                <h6 class="fw-bold text-white mb-3">Enlaces Rápidos</h6>
                <ul class="list-unstyled small">
                    <li><a href="#" class="text-secondary text-decoration-none">Soporte Técnico</a></li>
                    <li><a href="#" class="text-secondary text-decoration-none">Manual de Usuario</a></li>
                    <li><a href="#" class="text-secondary text-decoration-none">Política de Privacidad</a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h6 class="fw-bold text-white mb-3">Contacto</h6>
                <p class="small text-secondary"><i class="fas fa-envelope me-2"></i>soporte@tallerpro.com</p>
                <p class="small text-secondary"><i class="fas fa-phone me-2"></i>+57 300 123 4567</p>
                <div class="d-flex gap-3 mt-3">
                    <a href="#" class="text-secondary fs-5"><i class="fab fa-facebook"></i></a>
                    <a href="#" class="text-secondary fs-5"><i class="fab fa-whatsapp"></i></a>
                    <a href="#" class="text-secondary fs-5"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </div>
        <hr class="border-secondary mt-4">
        <p class="text-center small text-secondary mb-0">&copy; <?= date('Y') ?> TallerPro – Sistema de Gestión Automotriz. Todos los derechos reservados.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
