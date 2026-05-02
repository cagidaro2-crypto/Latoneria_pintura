<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../usuarios/login.php");
    exit;
}

$usuario = $_SESSION['usuario'];
$titulo  = $titulo ?? 'Dashboard';

// Convertir ID de rol a texto si es numérico
$rolData = $usuario['rol'] ?? 2;
$rol = 'cliente';
if ($rolData == 1 || strtolower($rolData) === 'administrador') {
    $rol = 'administrador';
} elseif ($rolData == 3 || strtolower($rolData) === 'empleado') {
    $rol = 'empleado';
}

$nombreCompleto = $usuario['nombres'] ?? 'Usuario';

// Determinar ítem activo
$paginaActual = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo) ?> – TallerPro</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --sidebar-bg:    #1e293b; /* Color oscuro basado en la imagen */
            --sidebar-hover: rgba(255, 255, 255, 0.1);
            --sidebar-active: #3b82f6; /* Color azul activo */
            --topbar-bg:     #ffffff;
        }
        body { background: #f8fafc; min-height: 100vh; font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }

        /* SIDEBAR */
        #sidebar {
            width: 260px; min-width: 260px;
            background: var(--sidebar-bg);
            min-height: 100vh;
            position: sticky; top: 0;
            display: flex; flex-direction: column;
            z-index: 100;
            color: #fff;
        }
        .sidebar-brand { padding: 1.5rem 1.2rem; border-bottom: 1px solid rgba(255,255,255,.05); }
        
        .brand-icon-box {
            width: 40px; height: 40px; 
            background: #3b82f6; 
            border-radius: 8px; 
            display: flex; align-items: center; justify-content: center;
        }
        
        .sidebar-brand .brand-name { color: #fff; font-size: 1.1rem; font-weight: 700; line-height: 1.2; }
        .sidebar-brand .brand-sub  { color: #94a3b8; font-size: 0.75rem; }

        .sidebar-nav {
            padding: 1rem 0.5rem;
        }
        
        .sidebar-nav .nav-link {
            color: #cbd5e1;
            padding: 0.75rem 1.2rem;
            border-radius: 8px;
            margin-bottom: 4px;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.2s ease;
            display: flex; align-items: center; gap: 0.8rem;
        }
        
        .sidebar-nav .nav-link:hover {
            background: var(--sidebar-hover);
            color: #fff;
        }
        
        .sidebar-nav .nav-link.active {
            background: var(--sidebar-active);
            color: #fff;
            box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3);
        }
        
        .sidebar-nav .nav-link i { 
            width: 20px; 
            text-align: center; 
            font-size: 1.1rem;
        }
        
        .sidebar-section { 
            color: #64748b; 
            font-size: 0.75rem; 
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase; 
            padding: 1rem 1.2rem 0.5rem; 
        }

        /* TOPBAR */
        #topbar {
            background: var(--topbar-bg);
            height: 70px;
            padding: 0 1.5rem;
            display: flex; align-items: center; justify-content: flex-end; /* A la derecha */
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }

        .user-badge {
            width: 40px; height: 40px; border-radius: 50%;
            background: #f1f5f9;
            color: #64748b;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem;
        }

        /* CONTENT */
        #mainContent { flex: 1; overflow-x: hidden; }
        #pageContent { padding: 2rem; }

        @media (max-width: 768px) {
            #sidebar { display: none; }
        }
    </style>
</head>
<body>
<div class="d-flex">

<!-- ===== SIDEBAR ===== -->
<nav id="sidebar">
    <div class="sidebar-brand d-flex align-items-center gap-3">
        <div class="brand-icon-box shadow-sm">
            <i class="fas fa-car-side text-white fs-5"></i>
        </div>
        <div>
            <div class="brand-name">Taller de Latonería<br>y Pintura</div>
            <div class="brand-sub mt-1">Sistema de Gestión Integral</div>
        </div>
    </div>

    <div class="sidebar-nav flex-column" style="overflow-y:auto; flex:1;">

        <?php if ($rol === 'administrador'): ?>
            <!-- Menú plano idéntico a la imagen -->
            <a href="admin_dashboard.php" class="nav-link <?= $paginaActual=='admin_dashboard.php'?'active':'' ?>">
                <i class="fas fa-border-all"></i> Dashboard
            </a>
            <a href="admin_usuarios.php" class="nav-link <?= $paginaActual=='admin_usuarios.php'?'active':'' ?>">
                <i class="fas fa-user-tie"></i> Usuarios
            </a>
            <a href="#" class="nav-link">
                <i class="fas fa-users"></i> Clientes
            </a>
            <a href="admin_vehiculos.php" class="nav-link <?= $paginaActual=='admin_vehiculos.php'?'active':'' ?>">
                <i class="fas fa-car"></i> Vehículos
            </a>
            <a href="admin_ordenes.php" class="nav-link <?= $paginaActual=='admin_ordenes.php'?'active':'' ?>">
                <i class="fas fa-clipboard-list"></i> Órdenes de Trabajo
            </a>
            <a href="admin_cotizaciones.php" class="nav-link <?= $paginaActual=='admin_cotizaciones.php'?'active':'' ?>">
                <i class="fas fa-file-invoice"></i> Cotizaciones
            </a>
            <a href="admin_facturas.php" class="nav-link <?= $paginaActual=='admin_facturas.php'?'active':'' ?>">
                <i class="fas fa-file-invoice-dollar"></i> Facturación
            </a>
            <a href="admin_inventario.php" class="nav-link <?= $paginaActual=='admin_inventario.php'?'active':'' ?>">
                <i class="fas fa-boxes-stacked"></i> Inventario
            </a>
            <a href="admin_reportes.php" class="nav-link <?= $paginaActual=='admin_reportes.php'?'active':'' ?>">
                <i class="fas fa-chart-simple"></i> Reportes
            </a>
            <a href="admin_ventas.php" class="nav-link <?= $paginaActual=='admin_ventas.php'?'active':'' ?>">
                <i class="fas fa-cart-shopping"></i> Ventas
            </a>
            <a href="admin_proveedores.php" class="nav-link <?= $paginaActual=='admin_proveedores.php'?'active':'' ?>">
                <i class="fas fa-truck"></i> Proveedores
            </a>
        <?php endif; ?>

        <?php if ($rol === 'empleado'): ?>
            <div class="sidebar-section">Principal</div>
            <a href="empleado_dashboard.php" class="nav-link <?= $paginaActual=='empleado_dashboard.php'?'active':'' ?>">
                <i class="fas fa-border-all"></i> Dashboard
            </a>

            <div class="sidebar-section">Trabajo</div>
            <a href="empleado_ordenes.php" class="nav-link <?= $paginaActual=='empleado_ordenes.php'?'active':'' ?>">
                <i class="fas fa-clipboard-list"></i> Mis Órdenes
            </a>
            <a href="empleado_historial.php" class="nav-link <?= $paginaActual=='empleado_historial.php'?'active':'' ?>">
                <i class="fas fa-history"></i> Historial
            </a>
            <a href="empleado_ventas.php" class="nav-link <?= $paginaActual=='empleado_ventas.php'?'active':'' ?>">
                <i class="fas fa-cart-shopping"></i> Ventas
            </a>
        <?php endif; ?>

        <?php if ($rol === 'cliente'): ?>
            <div class="sidebar-section">Mi Panel</div>
            <a href="cliente_dashboard.php" class="nav-link <?= $paginaActual=='cliente_dashboard.php'?'active':'' ?>">
                <i class="fas fa-border-all"></i> Inicio
            </a>
            <a href="cliente_vehiculos.php" class="nav-link <?= $paginaActual=='cliente_vehiculos.php'?'active':'' ?>">
                <i class="fas fa-car"></i> Mis Vehículos
            </a>
            <a href="cliente_ordenes.php" class="nav-link <?= $paginaActual=='cliente_ordenes.php'?'active':'' ?>">
                <i class="fas fa-clipboard-list"></i> Estado Servicio
            </a>
            <a href="cliente_citas.php" class="nav-link <?= $paginaActual=='cliente_citas.php'?'active':'' ?>">
                <i class="fas fa-calendar-plus"></i> Agendar Cita
            </a>
            <a href="cliente_cotizaciones.php" class="nav-link <?= $paginaActual=='cliente_cotizaciones.php'?'active':'' ?>">
                <i class="fas fa-file-invoice"></i> Cotizaciones
            </a>
        <?php endif; ?>

    </div><!-- /sidebar-nav -->
</nav><!-- /sidebar -->

<!-- ===== MAIN CONTENT ===== -->
<div id="mainContent" class="d-flex flex-column flex-grow-1">

    <!-- TOPBAR (Blanca) -->
    <header id="topbar">
        <div class="d-flex align-items-center gap-3">
            <div class="text-end">
                <div class="text-dark fw-bold small"><?= htmlspecialchars(ucfirst($rol)) ?></div>
                <div class="text-muted" style="font-size:.78rem;"><?= htmlspecialchars($nombreCompleto) ?></div>
            </div>
            <div class="user-badge shadow-sm border">
                <i class="fas fa-user text-primary"></i>
            </div>
            <div class="ms-2 border-start ps-3">
                <a href="../../controllers/AuthController.php?accion=logout"
                   class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-2">
                    <i class="fas fa-power-off"></i>
                    <span>Salir</span>
                </a>
            </div>
        </div>
    </header>

    <!-- PAGE CONTENT -->
    <section id="pageContent">
