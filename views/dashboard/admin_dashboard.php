<?php
session_start();

if (!isset($_SESSION['usuario']) || (int)($_SESSION['usuario']['rol'] ?? 0) !== 1) {
    header("Location: ../usuarios/login.php");
    exit;
}

$usuario = $_SESSION['usuario'];

$formatter = new IntlDateFormatter(
    'es_ES', IntlDateFormatter::FULL, IntlDateFormatter::NONE,
    null, null, "EEEE, dd 'de' MMMM 'de' yyyy"
);
$fecha_actual = $formatter->format(new DateTime());

// ── Carga inicial de datos desde BD ──────────────────────────────────────
require_once __DIR__ . '/../../config/database.php';
$db = (new Database())->conectar();

// Tarjetas
$vehiculosEnProceso = (int)$db->query(
    "SELECT COUNT(*) FROM vehiculo v
     JOIN estado_vehiculo ev ON v.id_estado_vehiculo = ev.id_estado_vehiculo
     WHERE ev.estado IN ('En reparación','Pintura')"
)->fetchColumn();

$ventasMes = (float)$db->query(
    "SELECT COALESCE(SUM(total),0) FROM factura
     WHERE MONTH(fecha)=MONTH(CURDATE()) AND YEAR(fecha)=YEAR(CURDATE())"
)->fetchColumn();

$ventasMesAnt = (float)$db->query(
    "SELECT COALESCE(SUM(total),0) FROM factura
     WHERE MONTH(fecha)=MONTH(DATE_SUB(CURDATE(),INTERVAL 1 MONTH))
       AND YEAR(fecha)=YEAR(DATE_SUB(CURDATE(),INTERVAL 1 MONTH))"
)->fetchColumn();
$pctVentas = $ventasMesAnt > 0 ? round((($ventasMes - $ventasMesAnt) / $ventasMesAnt) * 100, 1) : 0;

$ordenesActivas = (int)$db->query(
    "SELECT COUNT(*) FROM historial_vehiculo
     WHERE MONTH(fecha_registro)=MONTH(CURDATE()) AND YEAR(fecha_registro)=YEAR(CURDATE())"
)->fetchColumn();

// Bajo stock (con fallback si tabla no tiene nueva estructura)
$bajoStock = 0;
try {
    $bajoStock = (int)$db->query(
        "SELECT COUNT(*) FROM inventario WHERE cantidad <= stock_minimo"
    )->fetchColumn();
} catch (Exception $e) { $bajoStock = 0; }

// Vehículos recientes
$stmtVR = $db->query(
    "SELECT v.placa, v.marca, v.modelo, cl.nombre AS cliente_nombre, ev.estado
     FROM vehiculo v
     JOIN cliente cl ON v.id_cliente = cl.id_cliente
     JOIN estado_vehiculo ev ON v.id_estado_vehiculo = ev.id_estado_vehiculo
     ORDER BY v.id_vehiculo DESC LIMIT 5"
);
$vehiculosRecientes = $stmtVR ? $stmtVR->fetchAll(PDO::FETCH_ASSOC) : [];

// Inventario bajo stock
$inventarioBajo = [];
try {
    $stmtIB = $db->query(
        "SELECT p.nombre, i.cantidad, i.stock_minimo, i.unidad
         FROM inventario i JOIN productos p ON i.id_producto = p.id_producto
         WHERE i.cantidad <= i.stock_minimo
         ORDER BY (i.cantidad / i.stock_minimo) ASC LIMIT 5"
    );
    $inventarioBajo = $stmtIB ? $stmtIB->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (Exception $e) { $inventarioBajo = []; }

// Gráfica ventas últimos 6 meses
$stmtGV = $db->query(
    "SELECT DATE_FORMAT(fecha,'%b') AS mes,
            DATE_FORMAT(fecha,'%Y-%m') AS mes_key,
            COALESCE(SUM(total),0) AS total
     FROM factura
     WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
     GROUP BY mes_key, mes ORDER BY mes_key ASC"
);
$grafVentas = $stmtGV ? $stmtGV->fetchAll(PDO::FETCH_ASSOC) : [];

// Gráfica órdenes últimos 7 días
$stmtGO = $db->query(
    "SELECT DATE_FORMAT(fecha_registro,'%a') AS dia, COUNT(*) AS total
     FROM historial_vehiculo
     WHERE fecha_registro >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
     GROUP BY fecha_registro ORDER BY fecha_registro ASC"
);
$grafOrdenes = $stmtGO ? $stmtGO->fetchAll(PDO::FETCH_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard – TallerPro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../public/css/dashboard.css">
    <style>
        .live-badge {
            display: inline-flex; align-items: center; gap: 5px;
            font-size: .72rem; color: #22c55e;
        }
        .live-dot {
            width: 7px; height: 7px; border-radius: 50%;
            background: #22c55e;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0%,100% { opacity: 1; transform: scale(1); }
            50%      { opacity: .4; transform: scale(1.3); }
        }
        .stat-change { font-size: .75rem; }
        .stat-change.up   { color: #22c55e; }
        .stat-change.down { color: #ef4444; }
        .stat-change.neutral { color: #94a3b8; }
    </style>
</head>
<body>

<div class="dashboard-container">

    <!-- ══ Sidebar ══════════════════════════════════════════════════════════ -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo-icon"><i class="fas fa-car text-white"></i></div>
            <div>
                <h5>Taller de Latonería<br>y Pintura</h5>
                <small>Sistema de Gestión Integral</small>
            </div>
        </div>

        <ul class="sidebar-menu">
            <li class="active"><a href="admin_dashboard.php"><i class="fas fa-border-all fa-fw"></i> Dashboard</a></li>
            <li><a href="admin_usuarios.php"><i class="fas fa-user-tie fa-fw"></i> Usuarios</a></li>
            <li><a href="admin_clientes.php"><i class="fas fa-users fa-fw"></i> Clientes</a></li>
            <li><a href="admin_vehiculos.php"><i class="fas fa-car-side fa-fw"></i> Vehículos</a></li>
            <li><a href="admin_ordenes.php"><i class="fas fa-clipboard-list fa-fw"></i> Órdenes de Trabajo</a></li>
            <li><a href="admin_cotizaciones.php"><i class="fas fa-file-invoice fa-fw"></i> Cotizaciones</a></li>
            <li><a href="admin_facturas.php"><i class="fas fa-file-invoice-dollar fa-fw"></i> Facturación</a></li>
            <li><a href="admin_inventario.php"><i class="fas fa-boxes-stacked fa-fw"></i> Inventario</a></li>
            <li><a href="admin_reportes.php"><i class="fas fa-chart-line fa-fw"></i> Reportes</a></li>
            <li><a href="admin_ventas.php"><i class="fas fa-shopping-cart fa-fw"></i> Ventas</a></li>
            <li><a href="admin_proveedores.php"><i class="fas fa-truck-field fa-fw"></i> Proveedores</a></li>
        </ul>

        <div class="sidebar-footer">
            <div class="user-profile-card">
                <strong><?= htmlspecialchars($usuario['nombres'] ?? $usuario['nombre'] ?? 'Admin') ?></strong>
                <small><?= htmlspecialchars($usuario['correo'] ?? '') ?></small>
            </div>
            <a href="../../controllers/AuthController.php?accion=logout" class="btn-logout">
                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
            </a>
        </div>
    </aside>

    <!-- ══ Main Content ══════════════════════════════════════════════════════ -->
    <main class="main-content">

        <!-- Header -->
        <div class="page-header d-flex justify-content-between align-items-start">
            <div>
                <h2>Dashboard</h2>
                <p>Resumen general del taller — <?= ucfirst($fecha_actual) ?></p>
            </div>
            <div class="text-end">
                <div class="live-badge">
                    <span class="live-dot"></span>
                    En vivo · actualizado <span id="lastUpdate">ahora</span>
                </div>
                <div class="text-muted" style="font-size:.7rem;">Se actualiza cada 30 seg.</div>
            </div>
        </div>

        <!-- ── Tarjetas resumen ─────────────────────────────────────────── -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="summary-card card-blue">
                    <i class="fas fa-arrow-trend-up trend-icon"></i>
                    <div class="icon-wrapper"><i class="fas fa-car-side"></i></div>
                    <h6>Vehículos en Proceso</h6>
                    <h3 id="stat-vehiculos"><?= $vehiculosEnProceso ?></h3>
                    <p class="stat-change neutral" id="stat-vehiculos-sub">En reparación o pintura</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card card-blue">
                    <i class="fas fa-arrow-trend-up trend-icon"></i>
                    <div class="icon-wrapper"><i class="fas fa-dollar-sign"></i></div>
                    <h6>Ventas del Mes</h6>
                    <h3 id="stat-ventas">$<?= number_format($ventasMes, 0, ',', '.') ?></h3>
                    <p class="stat-change <?= $pctVentas >= 0 ? 'up' : 'down' ?>" id="stat-ventas-sub">
                        <?= $pctVentas >= 0 ? '+' : '' ?><?= $pctVentas ?>% vs mes anterior
                    </p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card card-dark-gray">
                    <i class="fas fa-arrow-trend-up trend-icon"></i>
                    <div class="icon-wrapper"><i class="fas fa-clipboard-list"></i></div>
                    <h6>Órdenes del Mes</h6>
                    <h3 id="stat-ordenes"><?= $ordenesActivas ?></h3>
                    <p class="stat-change neutral" id="stat-ordenes-sub">Registradas este mes</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card card-dark-blue">
                    <i class="fas fa-exclamation-triangle trend-icon"></i>
                    <div class="icon-wrapper"><i class="fas fa-box"></i></div>
                    <h6>Productos Bajo Stock</h6>
                    <h3 id="stat-stock"><?= $bajoStock ?></h3>
                    <p class="stat-change <?= $bajoStock > 0 ? 'down' : 'up' ?>" id="stat-stock-sub">
                        <?= $bajoStock > 0 ? 'Requiere atención' : 'Stock en orden' ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- ── Gráficas ────────────────────────────────────────────────── -->
        <div class="row g-4 mb-4">
            <div class="col-md-7">
                <div class="panel">
                    <div class="panel-header">
                        <i class="fas fa-chart-line"></i> Ventas Mensuales
                    </div>
                    <div style="position:relative; height:250px;">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="panel">
                    <div class="panel-header">
                        <i class="fas fa-file-alt"></i> Órdenes Últimos 7 Días
                    </div>
                    <div style="position:relative; height:250px;">
                        <canvas id="ordersChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Listas ──────────────────────────────────────────────────── -->
        <div class="row g-4">

            <!-- Vehículos recientes -->
            <div class="col-md-6">
                <div class="panel">
                    <div class="panel-header d-flex justify-content-between">
                        <span><i class="fas fa-car"></i> Vehículos Recientes</span>
                        <a href="admin_vehiculos.php" class="btn btn-sm btn-outline-primary" style="font-size:.75rem;">Ver todos</a>
                    </div>
                    <ul class="custom-list" id="lista-vehiculos">
                    <?php foreach ($vehiculosRecientes as $v):
                        $estado = $v['estado'] ?? '';
                        $cls = match(true) {
                            stripos($estado,'reparaci') !== false => 'badge-soft-yellow',
                            stripos($estado,'finalizado') !== false => 'badge-soft-green',
                            stripos($estado,'pintura') !== false => 'badge-soft-blue',
                            stripos($estado,'entregado') !== false => 'badge-soft-green',
                            default => 'badge-soft-gray'
                        };
                    ?>
                        <li>
                            <div>
                                <div class="list-item-title"><?= htmlspecialchars($v['placa']) ?></div>
                                <div class="list-item-subtitle"><?= htmlspecialchars($v['marca'].' '.$v['modelo'].' — '.$v['cliente_nombre']) ?></div>
                            </div>
                            <span class="badge-soft <?= $cls ?>"><?= htmlspecialchars($estado) ?></span>
                        </li>
                    <?php endforeach; ?>
                    <?php if (empty($vehiculosRecientes)): ?>
                        <li><div class="text-muted small text-center py-2">Sin vehículos registrados</div></li>
                    <?php endif; ?>
                    </ul>
                </div>
            </div>

            <!-- Inventario bajo stock -->
            <div class="col-md-6">
                <div class="panel">
                    <div class="panel-header d-flex justify-content-between">
                        <span><i class="fas fa-box-open"></i> Inventario Bajo Stock</span>
                        <a href="admin_inventario.php" class="btn btn-sm btn-outline-primary" style="font-size:.75rem;">Ver todo</a>
                    </div>
                    <ul class="custom-list" id="lista-stock">
                    <?php if (empty($inventarioBajo)): ?>
                        <li><div class="text-muted small text-center py-2">
                            <?= $bajoStock === 0 ? 'Todo el stock está en orden ✓' : 'Ejecuta la migración de BD para ver el inventario' ?>
                        </div></li>
                    <?php else: ?>
                        <?php foreach ($inventarioBajo as $item): ?>
                        <li>
                            <div>
                                <div class="list-item-title"><?= htmlspecialchars($item['nombre']) ?></div>
                                <div class="list-item-subtitle">Mínimo: <?= $item['stock_minimo'] ?> <?= htmlspecialchars($item['unidad']) ?></div>
                            </div>
                            <span class="badge-soft badge-soft-red"><?= $item['cantidad'] ?> <?= htmlspecialchars($item['unidad']) ?></span>
                        </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </ul>
                </div>
            </div>

        </div>
    </main>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// ── Datos iniciales desde PHP ─────────────────────────────────────────────
const initVentas  = <?= json_encode(array_values($grafVentas)) ?>;
const initOrdenes = <?= json_encode(array_values($grafOrdenes)) ?>;

// ── Gráfica Ventas Mensuales ──────────────────────────────────────────────
const ctxSales = document.getElementById('salesChart').getContext('2d');
const salesChart = new Chart(ctxSales, {
    type: 'line',
    data: {
        labels:   initVentas.map(r => r.mes),
        datasets: [{
            label: 'Ventas ($)',
            data:  initVentas.map(r => parseFloat(r.total)),
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59,130,246,0.1)',
            borderWidth: 2,
            pointBackgroundColor: '#fff',
            pointBorderColor: '#3b82f6',
            pointBorderWidth: 2,
            pointRadius: 4,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { borderDash: [5,5] } },
            x: { grid: { display: false } }
        }
    }
});

// ── Gráfica Órdenes por Día ───────────────────────────────────────────────
const ctxOrders = document.getElementById('ordersChart').getContext('2d');
const ordersChart = new Chart(ctxOrders, {
    type: 'bar',
    data: {
        labels:   initOrdenes.length ? initOrdenes.map(r => r.dia) : ['Sin datos'],
        datasets: [{
            label: 'Órdenes',
            data:  initOrdenes.length ? initOrdenes.map(r => parseInt(r.total)) : [0],
            backgroundColor: '#3b82f6',
            borderRadius: 4
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { borderDash: [5,5] } },
            x: { grid: { display: false } }
        }
    }
});

// ── Actualización en tiempo real cada 30 segundos ─────────────────────────
function actualizarDashboard() {
    fetch('../../controllers/DashboardDataController.php')
        .then(r => r.json())
        .then(data => {
            if (data.error) return;

            const t = data.tarjetas;

            // Tarjetas
            document.getElementById('stat-vehiculos').textContent = t.vehiculos_proceso;
            document.getElementById('stat-ventas').textContent =
                '$' + parseFloat(t.ventas_mes).toLocaleString('es-CO', {maximumFractionDigits:0});
            document.getElementById('stat-ordenes').textContent = t.ordenes_activas;
            document.getElementById('stat-stock').textContent   = t.bajo_stock;

            // Subtexto ventas
            const pct = t.pct_ventas;
            const subVentas = document.getElementById('stat-ventas-sub');
            subVentas.textContent = (pct >= 0 ? '+' : '') + pct + '% vs mes anterior';
            subVentas.className = 'stat-change ' + (pct >= 0 ? 'up' : 'down');

            // Subtexto stock
            const subStock = document.getElementById('stat-stock-sub');
            subStock.textContent = t.bajo_stock > 0 ? 'Requiere atención' : 'Stock en orden';
            subStock.className = 'stat-change ' + (t.bajo_stock > 0 ? 'down' : 'up');

            // Gráfica ventas
            if (data.grafica_ventas.length) {
                salesChart.data.labels  = data.grafica_ventas.map(r => r.mes);
                salesChart.data.datasets[0].data = data.grafica_ventas.map(r => parseFloat(r.total));
                salesChart.update('none');
            }

            // Gráfica órdenes
            if (data.grafica_ordenes.length) {
                ordersChart.data.labels  = data.grafica_ordenes.map(r => r.dia);
                ordersChart.data.datasets[0].data = data.grafica_ordenes.map(r => parseInt(r.total));
                ordersChart.update('none');
            }

            // Lista vehículos recientes
            if (data.vehiculos_recientes.length) {
                const lista = document.getElementById('lista-vehiculos');
                lista.innerHTML = data.vehiculos_recientes.map(v => {
                    const cls = v.estado.toLowerCase().includes('reparaci') ? 'badge-soft-yellow'
                              : v.estado.toLowerCase().includes('finalizado') ? 'badge-soft-green'
                              : v.estado.toLowerCase().includes('pintura') ? 'badge-soft-blue'
                              : v.estado.toLowerCase().includes('entregado') ? 'badge-soft-green'
                              : 'badge-soft-gray';
                    return `<li>
                        <div>
                            <div class="list-item-title">${esc(v.placa)}</div>
                            <div class="list-item-subtitle">${esc(v.marca)} ${esc(v.modelo)} — ${esc(v.cliente_nombre)}</div>
                        </div>
                        <span class="badge-soft ${cls}">${esc(v.estado)}</span>
                    </li>`;
                }).join('');
            }

            // Lista inventario bajo stock
            if (data.inventario_bajo.length) {
                const listaStock = document.getElementById('lista-stock');
                listaStock.innerHTML = data.inventario_bajo.map(item => `
                    <li>
                        <div>
                            <div class="list-item-title">${esc(item.nombre)}</div>
                            <div class="list-item-subtitle">Mínimo: ${item.stock_minimo} ${esc(item.unidad)}</div>
                        </div>
                        <span class="badge-soft badge-soft-red">${item.cantidad} ${esc(item.unidad)}</span>
                    </li>`).join('');
            }

            // Timestamp
            document.getElementById('lastUpdate').textContent = data.timestamp;
        })
        .catch(() => {
            // Silencioso — no interrumpir al usuario si falla una actualización
        });
}

function esc(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Primera actualización a los 30 segundos, luego cada 30s
setInterval(actualizarDashboard, 30000);
</script>

</body>
</html>
