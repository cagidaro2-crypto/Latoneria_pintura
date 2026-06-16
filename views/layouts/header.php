<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../usuarios/login.php");
    exit;
}

$usuario = $_SESSION['usuario'];
$titulo  = $titulo ?? 'Dashboard';

$rolData = $usuario['rol'] ?? 3;
$rol = 'cliente';
if ($rolData == 1 || strtolower((string)$rolData) === 'administrador') {
    $rol = 'administrador';
} elseif ($rolData == 2 || strtolower((string)$rolData) === 'empleado') {
    $rol = 'empleado';
}

$nombreCompleto = $usuario['nombres'] ?? ($usuario['nombre'] ?? 'Usuario');
$paginaActual   = basename($_SERVER['PHP_SELF']);

// ── Paleta unificada para los 3 roles: sidebar/topbar/footer negro, contenido gris claro
$paleta = [
    'sidebar_bg'            => '#000000',
    'sidebar_border'        => 'rgba(255,255,255,.06)',
    'sidebar_link'          => '#9ca3af',
    'sidebar_hover'         => 'rgba(255,255,255,.07)',
    'sidebar_active'        => '#374151',
    'sidebar_active_shadow' => 'rgba(55,65,81,.6)',
    'section_color'         => '#4b5563',
    'brand_name'            => '#f9fafb',
    'brand_sub'             => '#6b7280',
    'topbar_bg'             => '#000000',
    'topbar_border'         => 'rgba(255,255,255,.06)',
    'topbar_name'           => '#f9fafb',
    'topbar_role'           => '#6b7280',
    'badge_bg'              => '#111111',
    'badge_border'          => 'rgba(255,255,255,.08)',
    'badge_icon'            => '#9ca3af',
    'content_bg'            => '#f1f4f8',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo) ?> – TallerPro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * { box-sizing: border-box; }

        body {
            background: <?= $paleta['content_bg'] ?>;
            min-height: 100vh;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            margin: 0; padding: 0;
        }

        /* ── SIDEBAR ─────────────────────────────────────────────────────── */
        #sidebar {
            width: 255px; min-width: 255px;
            background: <?= $paleta['sidebar_bg'] ?>;
            border-right: 1px solid <?= $paleta['sidebar_border'] ?>;
            min-height: 100vh;
            position: sticky; top: 0;
            display: flex; flex-direction: column;
            z-index: 100;
        }

        .sidebar-brand {
            padding: 1.4rem 1.2rem;
            border-bottom: 1px solid <?= $paleta['sidebar_border'] ?>;
            display: flex; align-items: center; gap: .85rem;
        }

        .brand-icon-box {
            width: 40px; height: 40px;
            background: #111111;
            border-radius: 9px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; overflow: hidden; padding: 0;
        }

        .brand-name {
            color: <?= $paleta['brand_name'] ?>;
            font-size: 1rem; font-weight: 700; line-height: 1.25;
        }
        .brand-sub {
            color: <?= $paleta['brand_sub'] ?>;
            font-size: .72rem; margin-top: 2px;
        }

        .sidebar-nav {
            padding: .8rem .6rem; flex: 1; overflow-y: auto;
        }

        .sidebar-section {
            color: <?= $paleta['section_color'] ?>;
            font-size: .7rem; font-weight: 700;
            letter-spacing: 1px; text-transform: uppercase;
            padding: .9rem 1rem .4rem;
        }

        .sidebar-nav .nav-link {
            color: <?= $paleta['sidebar_link'] ?>;
            padding: .7rem 1rem; border-radius: 8px; margin-bottom: 2px;
            font-size: .9rem; font-weight: 500;
            transition: all .18s ease;
            display: flex; align-items: center; gap: .75rem;
            text-decoration: none;
        }
        .sidebar-nav .nav-link i { width: 18px; text-align: center; font-size: 1rem; }

        .sidebar-nav .nav-link:hover {
            background: <?= $paleta['sidebar_active'] ?> !important;
            color: #fff !important;
        }
        .sidebar-nav .nav-link.active {
            background: <?= $paleta['sidebar_active'] ?> !important;
            color: #fff !important; font-weight: 600;
            box-shadow: 0 4px 12px <?= $paleta['sidebar_active_shadow'] ?>;
        }

        /* ── TOPBAR ──────────────────────────────────────────────────────── */
        #topbar {
            background: <?= $paleta['topbar_bg'] ?>;
            height: 64px; padding: 0 1.5rem;
            display: flex; align-items: center; justify-content: flex-end;
            border-bottom: 1px solid <?= $paleta['topbar_border'] ?>;
            flex-shrink: 0;
        }
        .topbar-name { color: <?= $paleta['topbar_name'] ?>; font-size: .88rem; font-weight: 700; }
        .topbar-role { color: <?= $paleta['topbar_role'] ?>; font-size: .75rem; }

        .user-badge {
            width: 38px; height: 38px; border-radius: 50%;
            background: <?= $paleta['badge_bg'] ?>;
            border: 1px solid <?= $paleta['badge_border'] ?>;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem; color: <?= $paleta['badge_icon'] ?>;
        }

        .btn-salir {
            background: transparent;
            border: 1px solid rgba(239,68,68,.45);
            color: #f87171;
            padding: .35rem .9rem; border-radius: 8px;
            font-size: .82rem; font-weight: 600;
            display: inline-flex; align-items: center; gap: .4rem;
            text-decoration: none; transition: all .2s;
        }
        .btn-salir:hover { background: rgba(239,68,68,.12); color: #fca5a5; border-color: #f87171; }

        /* ── LAYOUT ──────────────────────────────────────────────────────── */
        #mainContent { flex: 1; overflow-x: hidden; display: flex; flex-direction: column; }
        #pageContent  { padding: 2rem; flex: 1; background: <?= $paleta['content_bg'] ?>; }

        @media (max-width: 768px) { #sidebar { display: none; } }
    </style>
</head>
<body>
<div class="d-flex" style="min-height:100vh;">

<!-- ══ SIDEBAR ══════════════════════════════════════════════════════════════ -->
<nav id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon-box">
            <img src="../../imagen/logo.jpeg" alt="Logo"
                 style="width:40px;height:40px;object-fit:cover;border-radius:9px;display:block;">
        </div>
        <div>
            <div class="brand-name">Taller de Latonería<br>y Pintura</div>
            <div class="brand-sub">Sistema de Gestión Integral</div>
        </div>
    </div>

    <div class="sidebar-nav">
        <?php if ($rol === 'administrador'): ?>
            <a href="admin_dashboard.php"    class="nav-link <?= $paginaActual==='admin_dashboard.php'   ?'active':'' ?>"><i class="fas fa-border-all"></i> Dashboard</a>
            <a href="admin_usuarios.php"     class="nav-link <?= $paginaActual==='admin_usuarios.php'    ?'active':'' ?>"><i class="fas fa-user-tie"></i> Usuarios</a>
            <a href="admin_clientes.php"     class="nav-link <?= $paginaActual==='admin_clientes.php'    ?'active':'' ?>"><i class="fas fa-users"></i> Clientes</a>
            <a href="admin_vehiculos.php"    class="nav-link <?= $paginaActual==='admin_vehiculos.php'   ?'active':'' ?>"><i class="fas fa-car"></i> Vehículos</a>
            <a href="admin_ordenes.php"      class="nav-link <?= $paginaActual==='admin_ordenes.php'     ?'active':'' ?>"><i class="fas fa-clipboard-list"></i> Órdenes de Trabajo</a>
            <a href="admin_citas.php"        class="nav-link <?= $paginaActual==='admin_citas.php'       ?'active':'' ?>"><i class="fas fa-calendar-check"></i> Citas</a>
            <a href="admin_cotizaciones.php" class="nav-link <?= $paginaActual==='admin_cotizaciones.php'?'active':'' ?>"><i class="fas fa-file-invoice"></i> Cotizaciones</a>
            <a href="admin_facturas.php"     class="nav-link <?= $paginaActual==='admin_facturas.php'    ?'active':'' ?>"><i class="fas fa-file-invoice-dollar"></i> Facturación</a>
            <a href="admin_inventario.php"   class="nav-link <?= $paginaActual==='admin_inventario.php'  ?'active':'' ?>"><i class="fas fa-boxes-stacked"></i> Inventario</a>
            <a href="admin_reportes.php"     class="nav-link <?= $paginaActual==='admin_reportes.php'    ?'active':'' ?>"><i class="fas fa-chart-simple"></i> Reportes</a>
            <a href="admin_ventas.php"       class="nav-link <?= $paginaActual==='admin_ventas.php'      ?'active':'' ?>"><i class="fas fa-cart-shopping"></i> Ventas</a>
            <a href="admin_proveedores.php"  class="nav-link <?= $paginaActual==='admin_proveedores.php' ?'active':'' ?>"><i class="fas fa-truck"></i> Proveedores</a>
        <?php endif; ?>

        <?php if ($rol === 'empleado'): ?>
            <div class="sidebar-section">Principal</div>
            <a href="empleado_dashboard.php" class="nav-link <?= $paginaActual==='empleado_dashboard.php'?'active':'' ?>"><i class="fas fa-border-all"></i> Dashboard</a>
            <div class="sidebar-section">Trabajo</div>
            <a href="empleado_vehiculos.php" class="nav-link <?= $paginaActual==='empleado_vehiculos.php'?'active':'' ?>"><i class="fas fa-car"></i> Vehículos</a>
            <a href="empleado_ordenes.php"   class="nav-link <?= $paginaActual==='empleado_ordenes.php'  ?'active':'' ?>"><i class="fas fa-clipboard-list"></i> Mis Órdenes</a>
            <a href="empleado_citas.php"     class="nav-link <?= $paginaActual==='empleado_citas.php'    ?'active':'' ?>"><i class="fas fa-calendar-check"></i> Citas</a>
            <a href="empleado_historial.php" class="nav-link <?= $paginaActual==='empleado_historial.php'?'active':'' ?>"><i class="fas fa-history"></i> Historial</a>
            <a href="empleado_ventas.php"    class="nav-link <?= $paginaActual==='empleado_ventas.php'   ?'active':'' ?>"><i class="fas fa-cart-shopping"></i> Ventas</a>
        <?php endif; ?>

        <?php if ($rol === 'cliente'): ?>
            <div class="sidebar-section">Mi Panel</div>
            <a href="cliente_dashboard.php"    class="nav-link <?= $paginaActual==='cliente_dashboard.php'   ?'active':'' ?>"><i class="fas fa-border-all"></i> Inicio</a>
            <a href="cliente_vehiculos.php"    class="nav-link <?= $paginaActual==='cliente_vehiculos.php'   ?'active':'' ?>"><i class="fas fa-car"></i> Mis Vehículos</a>
            <a href="cliente_ordenes.php"      class="nav-link <?= $paginaActual==='cliente_ordenes.php'     ?'active':'' ?>"><i class="fas fa-clipboard-list"></i> Estado Servicio</a>
            <a href="cliente_citas.php"        class="nav-link <?= $paginaActual==='cliente_citas.php'       ?'active':'' ?>"><i class="fas fa-calendar-plus"></i> Agendar Cita</a>
            <a href="cliente_cotizaciones.php" class="nav-link <?= $paginaActual==='cliente_cotizaciones.php'?'active':'' ?>"><i class="fas fa-file-invoice"></i> Cotizaciones</a>
            <a href="cliente_catalogo.php"     class="nav-link <?= $paginaActual==='cliente_catalogo.php'    ?'active':'' ?>"><i class="fas fa-boxes-stacked"></i> Catálogo de Productos</a>
        <?php endif; ?>
    </div>
</nav>

<!-- ══ MAIN CONTENT ═════════════════════════════════════════════════════════ -->
<div id="mainContent">
    <header id="topbar">
        <div class="d-flex align-items-center gap-3">
            <div class="text-end">
                <div class="topbar-name"><?= htmlspecialchars(ucfirst($rol)) ?></div>
                <div class="topbar-role"><?= htmlspecialchars($nombreCompleto) ?></div>
            </div>
            <div class="user-badge"><i class="fas fa-user"></i></div>
            <div class="ms-1">
                <a href="../../controllers/AuthController.php?accion=logout" class="btn-salir">
                    <i class="fas fa-power-off"></i> Salir
                </a>
            </div>
        </div>
    </header>

    <section id="pageContent">
<?php require_once __DIR__ . '/../dashboard/shared_styles.php'; ?>
