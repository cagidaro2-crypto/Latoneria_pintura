<?php
$titulo = 'Dashboard';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../layouts/header.php';

// Formato de fecha en español usando IntlDateFormatter (recomendado en PHP 8+)
if (class_exists('IntlDateFormatter')) {
    $fmt = new IntlDateFormatter('es_ES', IntlDateFormatter::FULL, IntlDateFormatter::NONE);
    $fechaHoy = ucfirst($fmt->format(new DateTime()));
} else {
    $fechaHoy = date('l, d \d\e F \d\e Y');
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1 text-dark">Dashboard</h2>
        <p class="text-muted mb-0">Resumen general del taller - <?= $fechaHoy ?></p>
    </div>
</div>

<!-- TARJETAS -->
<div class="row g-4 mb-4">
    <!-- Vehículos en Proceso -->
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 rounded-4 text-white p-4 h-100 shadow-sm" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="bg-white bg-opacity-25 rounded p-2 d-inline-flex">
                    <i class="fas fa-car fs-5"></i>
                </div>
                <i class="fas fa-arrow-trend-up text-white-50"></i>
            </div>
            <div class="small text-white-50 mb-1 fw-semibold">Vehículos en Proceso</div>
            <h1 class="fw-bold mb-1">3</h1>
            <div class="small text-white-50">+2 desde ayer</div>
        </div>
    </div>

    <!-- Ventas del Mes -->
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 rounded-4 text-white p-4 h-100 shadow-sm" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="bg-white bg-opacity-25 rounded p-2 d-inline-flex">
                    <i class="fas fa-dollar-sign fs-5 px-1"></i>
                </div>
                <i class="fas fa-arrow-trend-up text-white-50"></i>
            </div>
            <div class="small text-white-50 mb-1 fw-semibold">Ventas del Mes</div>
            <h1 class="fw-bold mb-1">$63.070</h1>
            <div class="small text-white-50">+15% vs mes anterior</div>
        </div>
    </div>

    <!-- Órdenes Activas -->
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 rounded-4 text-white p-4 h-100 shadow-sm" style="background: linear-gradient(135deg, #a855f7 0%, #9333ea 100%);">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="bg-white bg-opacity-25 rounded p-2 d-inline-flex">
                    <i class="fas fa-clipboard-list fs-5"></i>
                </div>
                <i class="fas fa-arrow-trend-up text-white-50"></i>
            </div>
            <div class="small text-white-50 mb-1 fw-semibold">Órdenes Activas</div>
            <h1 class="fw-bold mb-1">3</h1>
            <div class="small text-white-50">En progreso</div>
        </div>
    </div>

    <!-- Productos Bajo Stock -->
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 rounded-4 text-white p-4 h-100 shadow-sm" style="background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="bg-white bg-opacity-25 rounded p-2 d-inline-flex">
                    <i class="fas fa-box-open fs-5"></i>
                </div>
                <i class="fas fa-triangle-exclamation text-white-50"></i>
            </div>
            <div class="small text-white-50 mb-1 fw-semibold">Productos Bajo Stock</div>
            <h1 class="fw-bold mb-1">2</h1>
            <div class="small text-white-50">Requiere atención</div>
        </div>
    </div>
</div>

<!-- GRÁFICAS -->
<div class="row g-4 mb-4">
    <!-- Ventas Mensuales (Línea) -->
    <div class="col-lg-6">
        <div class="card border-0 rounded-4 shadow-sm h-100 p-4">
            <div class="d-flex align-items-center mb-4">
                <div class="bg-primary bg-opacity-10 rounded p-2 d-inline-flex me-3">
                    <i class="fas fa-chart-line text-primary"></i>
                </div>
                <h6 class="fw-bold text-dark mb-0">Ventas Mensuales</h6>
            </div>
            <div style="position: relative; height:280px; width: 100%;">
                <canvas id="chartVentas"></canvas>
            </div>
        </div>
    </div>

    <!-- Órdenes por Día (Barras) -->
    <div class="col-lg-6">
        <div class="card border-0 rounded-4 shadow-sm h-100 p-4">
            <div class="d-flex align-items-center mb-4">
                <div class="bg-purple bg-opacity-10 rounded p-2 d-inline-flex me-3" style="background-color: rgba(168, 85, 247, 0.1);">
                    <i class="fas fa-clipboard-check" style="color: #a855f7;"></i>
                </div>
                <h6 class="fw-bold text-dark mb-0">Órdenes por Día</h6>
            </div>
            <div style="position: relative; height:280px; width: 100%;">
                <canvas id="chartOrdenes"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Incluir Chart.js desde CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    
    // Configuración global de fuentes para Chart.js
    Chart.defaults.font.family = "'Segoe UI', 'Roboto', 'Helvetica Neue', sans-serif";
    Chart.defaults.color = '#94a3b8'; // text-muted

    // Gráfica de Ventas Mensuales (Línea)
    const ctxVentas = document.getElementById('chartVentas').getContext('2d');
    new Chart(ctxVentas, {
        type: 'line',
        data: {
            labels: ['Ene', 'Feb', 'Mar', 'Abr'],
            datasets: [{
                label: 'Ventas',
                data: [45000, 52000, 48000, 63070],
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                borderWidth: 2,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#3b82f6',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1e293b',
                    padding: 12,
                    titleFont: { size: 13 },
                    bodyFont: { size: 14, weight: 'bold' },
                    callbacks: {
                        label: function(context) {
                            return '$' + context.parsed.y.toLocaleString('es-ES');
                        }
                    }
                }
            },
            scales: {
                y: { 
                    beginAtZero: true, 
                    max: 80000, 
                    ticks: { stepSize: 20000, padding: 10 },
                    border: { display: false },
                    grid: { color: '#f1f5f9', drawBorder: false }
                },
                x: { 
                    grid: { display: false, drawBorder: false },
                    ticks: { padding: 10 }
                }
            }
        }
    });

    // Gráfica de Órdenes por Día (Barras)
    const ctxOrdenes = document.getElementById('chartOrdenes').getContext('2d');
    new Chart(ctxOrdenes, {
        type: 'bar',
        data: {
            labels: ['Lun', 'Mar', 'Mié', 'Jue', 'Vie'],
            datasets: [{
                label: 'Órdenes',
                data: [5, 8, 6, 9, 7],
                backgroundColor: '#3b82f6',
                borderRadius: 4,
                barThickness: 35
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1e293b',
                    padding: 12,
                    titleFont: { size: 13 },
                    bodyFont: { size: 14, weight: 'bold' }
                }
            },
            scales: {
                y: { 
                    beginAtZero: true, 
                    max: 12, 
                    ticks: { stepSize: 3, padding: 10 },
                    border: { display: false },
                    grid: { color: '#f1f5f9', drawBorder: false }
                },
                x: { 
                    grid: { display: false, drawBorder: false },
                    ticks: { padding: 10 }
                }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
