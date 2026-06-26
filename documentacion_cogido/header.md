# Documentación: header.php

## Descripción general
Layout de cabecera compartido por todas las vistas del dashboard. Verifica la sesión activa, determina el rol del usuario, define una paleta de colores unificada para los tres roles y renderiza el HTML completo con: `<head>` con CDNs, sidebar de navegación adaptativo según rol, y topbar con nombre de usuario y botón de cierre de sesión. Es incluido al inicio de cada vista del dashboard.

## Dependencias
- `views/dashboard/shared_styles.php` — Estilos CSS compartidos entre dashboards (incluido al final)

## Código documentado línea por línea

```php
// ── BLOQUE PHP INICIAL ────────────────────────────────────────────────────

// Línea 1: Apertura del bloque PHP
<?php

// Líneas 2-3: Inicia la sesión solo si no está iniciada
if (session_status() === PHP_SESSION_NONE) session_start();

// Líneas 5-8: Guard de autenticación — si no hay sesión activa, redirige al login
if (!isset($_SESSION['usuario'])) {
    header("Location: ../usuarios/login.php");
    exit;
}

// Línea 10: Extrae los datos del usuario desde la sesión
$usuario = $_SESSION['usuario'];

// Línea 11: Usa el operador null coalescing para definir el título de la página
// Las vistas definen $titulo antes de incluir este header
$titulo  = $titulo ?? 'Dashboard';

// Línea 13: Obtiene el valor del rol (puede ser número o texto)
$rolData = $usuario['rol'] ?? 3;

// Línea 14: Valor por defecto del rol (cliente)
$rol = 'cliente';

// Líneas 15-19: Determina el rol en texto basándose en el valor de la sesión
// Acepta tanto el ID numérico (1, 2, 3) como el nombre en texto ('administrador')
if ($rolData == 1 || strtolower((string)$rolData) === 'administrador') {
    $rol = 'administrador';
} elseif ($rolData == 2 || strtolower((string)$rolData) === 'empleado') {
    $rol = 'empleado';
}

// Línea 21: Obtiene el nombre del usuario para mostrarlo en el topbar
$nombreCompleto = $usuario['nombres'] ?? ($usuario['nombre'] ?? 'Usuario');

// Línea 22: Obtiene el nombre del archivo actual para marcar el enlace activo en el sidebar
$paginaActual   = basename($_SERVER['PHP_SELF']);

// Líneas 24-44: Define la paleta de colores unificada para los 3 roles
// Sidebar y topbar negros, contenido gris claro, sin distinción por rol
$paleta = [
    'sidebar_bg'            => '#000000',  // Fondo del sidebar en negro puro
    'sidebar_border'        => 'rgba(255,255,255,.06)',  // Borde sutil blanco semitransparente
    'sidebar_link'          => '#9ca3af',  // Color de los enlaces inactivos (gris medio)
    'sidebar_hover'         => 'rgba(255,255,255,.07)',  // Fondo al pasar el mouse
    'sidebar_active'        => '#374151',  // Fondo del enlace activo (gris oscuro)
    'sidebar_active_shadow' => 'rgba(55,65,81,.6)',
    'section_color'         => '#4b5563',  // Color de los separadores de sección
    'brand_name'            => '#f9fafb',  // Color del nombre del taller (blanco)
    'brand_sub'             => '#6b7280',  // Color del subtítulo del taller (gris)
    'topbar_bg'             => '#000000',  // Fondo de la barra superior negro
    'topbar_border'         => 'rgba(255,255,255,.06)',
    'topbar_name'           => '#f9fafb',  // Color del nombre de usuario en topbar
    'topbar_role'           => '#6b7280',  // Color del rol en topbar
    'badge_bg'              => '#111111',  // Fondo del ícono de usuario
    'badge_border'          => 'rgba(255,255,255,.08)',
    'badge_icon'            => '#9ca3af',
    'content_bg'            => '#f1f4f8',  // Fondo del área de contenido (gris claro)
];
?>

<!-- ── HEAD DEL DOCUMENTO HTML ──────────────────────────────────────────── -->

<!-- Líneas 46-56: Inicia el documento HTML5 con idioma español -->
<!DOCTYPE html>
<html lang="es">
<head>
    <!-- Charset UTF-8 para soporte de caracteres especiales -->
    <meta charset="UTF-8">
    <!-- Meta viewport para diseño responsive en móviles -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Título dinámico: usa $titulo definido en la vista + nombre del sistema -->
    <title><?= htmlspecialchars($titulo) ?> – TallerPro</title>

    <!-- Bootstrap 5.3.3 desde CDN — framework CSS/JS principal -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6.5.0 — biblioteca de iconos vectoriales -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- SweetAlert2 — librería para alertas modales estilizadas -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Bloque <style> con todos los CSS del layout -->
    <style>
        /* Reset: aplica box-sizing a todos los elementos */
        * { box-sizing: border-box; }

        /* Body: fondo gris claro, altura mínima de ventana completa, tipografía Segoe UI */
        body { background: $paleta['content_bg']; min-height: 100vh; font-family: 'Segoe UI', ...; }

        /* ── SIDEBAR ─────────────────────────────────────────────────── */
        /* #sidebar: columna fija de 255px, negra, sticky al top */
        #sidebar { width: 255px; background: $paleta['sidebar_bg']; min-height: 100vh; position: sticky; top: 0; ... }

        /* .sidebar-brand: área del logo y nombre del taller */
        .sidebar-brand { padding: 1.4rem 1.2rem; border-bottom: 1px solid ...; }

        /* .brand-icon-box: caja de 40x40 para el logo */
        .brand-icon-box { width: 40px; height: 40px; background: #111111; border-radius: 9px; ... }

        /* .sidebar-nav .nav-link: estilo de los enlaces de navegación */
        /* :hover y .active: cambio de fondo a gris oscuro y texto blanco */

        /* ── TOPBAR ──────────────────────────────────────────────────── */
        /* #topbar: barra superior negra de 64px con contenido alineado a la derecha */
        #topbar { background: $paleta['topbar_bg']; height: 64px; padding: 0 1.5rem; ... }

        /* .user-badge: círculo de 38px con ícono de usuario */
        .user-badge { width: 38px; height: 38px; border-radius: 50%; ... }

        /* .btn-salir: botón de cierre de sesión con borde rojo sutil */
        .btn-salir { background: transparent; border: 1px solid rgba(239,68,68,.45); color: #f87171; ... }

        /* Responsive: oculta el sidebar en pantallas menores a 768px */
        @media (max-width: 768px) { #sidebar { display: none; } }
    </style>
</head>

<!-- ── CUERPO DEL DOCUMENTO ──────────────────────────────────────────────── -->
<body>
<!-- Contenedor flex principal: sidebar + contenido ocupan toda la ventana -->
<div class="d-flex" style="min-height:100vh;">

<!-- ══ SIDEBAR ══════════════════════════════════════════════════════════ -->
<nav id="sidebar">
    <!-- Marca del taller: logo + nombre + subtítulo -->
    <div class="sidebar-brand">
        <div class="brand-icon-box">
            <!-- Logo del taller con object-fit cover para no deformarse -->
            <img src="../../imagen/logo.jpeg" alt="Logo" style="width:40px;height:40px;...">
        </div>
        <div>
            <div class="brand-name">Taller de Latonería<br>y Pintura</div>
            <div class="brand-sub">Sistema de Gestión Integral</div>
        </div>
    </div>

    <!-- Área de navegación con overflow para scroll si hay muchos ítems -->
    <div class="sidebar-nav">

        <!-- Menú para ADMINISTRADOR (rol 1) — 12 secciones de gestión total -->
        <?php if ($rol === 'administrador'): ?>
            <!-- Cada enlace verifica si es la página actual para marcar 'active' -->
            <a href="admin_dashboard.php" class="nav-link <?= $paginaActual==='admin_dashboard.php' ?'active':'' ?>">
                <i class="fas fa-border-all"></i> Dashboard
            </a>
            <!-- ... más enlaces: usuarios, clientes, vehículos, órdenes, citas, cotizaciones, facturas, inventario, reportes, ventas, proveedores -->
        <?php endif; ?>

        <!-- Menú para EMPLEADO (rol 2) — secciones de trabajo operativo -->
        <?php if ($rol === 'empleado'): ?>
            <div class="sidebar-section">Principal</div>
            <a href="empleado_dashboard.php" class="nav-link ...">Dashboard</a>
            <div class="sidebar-section">Trabajo</div>
            <!-- ... vehículos, órdenes, citas, historial, ventas, inventario -->
        <?php endif; ?>

        <!-- Menú para CLIENTE (rol 3) — secciones de autoservicio del cliente -->
        <?php if ($rol === 'cliente'): ?>
            <div class="sidebar-section">Mi Panel</div>
            <!-- ... inicio, mis vehículos, estado servicio, agendar cita, cotizaciones, catálogo -->
        <?php endif; ?>
    </div>
</nav>

<!-- ══ CONTENIDO PRINCIPAL ══════════════════════════════════════════════ -->
<div id="mainContent">

    <!-- Barra superior (topbar) con info del usuario y botón de salida -->
    <header id="topbar">
        <div class="d-flex align-items-center gap-3">
            <!-- Nombre del rol (capitalizado) y nombre completo del usuario -->
            <div class="text-end">
                <div class="topbar-name"><?= htmlspecialchars(ucfirst($rol)) ?></div>
                <div class="topbar-role"><?= htmlspecialchars($nombreCompleto) ?></div>
            </div>
            <!-- Ícono de usuario circular -->
            <div class="user-badge"><i class="fas fa-user"></i></div>
            <!-- Botón de cerrar sesión que llama a AuthController con acción logout -->
            <div class="ms-1">
                <a href="../../controllers/AuthController.php?accion=logout" class="btn-salir">
                    <i class="fas fa-power-off"></i> Salir
                </a>
            </div>
        </div>
    </header>

    <!-- Sección de contenido de la página — las vistas inyectan su HTML aquí -->
    <section id="pageContent">

    <!-- Incluye estilos CSS compartidos entre todas las vistas del dashboard -->
    <?php require_once __DIR__ . '/../dashboard/shared_styles.php'; ?>
```

## Resumen de componentes renderizados

| Componente | Elemento HTML | Descripción |
|-----------|--------------|-------------|
| Sesión y rol | PHP | Verifica autenticación y determina rol textual |
| Paleta de colores | PHP array | Variables CSS para sidebar y topbar negros |
| `<head>` | HTML | Bootstrap, Font Awesome, SweetAlert2 CDN + CSS inline |
| Sidebar | `<nav id="sidebar">` | Navegación adaptativa según rol con ítem activo marcado |
| Topbar | `<header id="topbar">` | Barra con nombre, rol y botón de logout |
| Inicio de contenido | `<section id="pageContent">` | Apertura del área donde cada vista inyecta su contenido |

## Flujo de ejecución
1. La vista define `$titulo` (ej: `$titulo = 'Dashboard'`) antes de incluir este archivo.
2. Se incluye con `require_once __DIR__ . '/../layouts/header.php'`.
3. Verifica sesión activa; si no existe, redirige al login.
4. Determina el rol del usuario en formato texto.
5. Define la paleta de colores.
6. Renderiza el `<head>` con CDNs y estilos CSS.
7. Renderiza el sidebar con los menús correspondientes al rol.
8. Renderiza el topbar con nombre y botón de logout.
9. Abre la etiqueta `<section id="pageContent">` donde la vista injectará su contenido.
10. Al final de la vista se incluye `footer.php` que cierra todas las etiquetas abiertas aquí.
