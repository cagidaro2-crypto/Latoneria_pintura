<?php
$titulo = 'Dashboard – Empleado';
require_once __DIR__ . '/../../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario']) || (int)($_SESSION['usuario']['rol'] ?? 0) !== 2) {
    header("Location: ../usuarios/login.php"); exit;
}

require_once __DIR__ . '/../layouts/header.php';

$db = (new Database())->conectar();

// Contadores
$totalOrdenes = 0;
$enReparacion = 0;
$pendientes   = 0;

try {
    $totalOrdenes = (int)$db->query("SELECT COUNT(*) FROM ordenes_trabajo")->fetchColumn();
} catch (Exception $e) {}

try {
    $stmtEst = $db->query(
        "SELECT ev.estado, COUNT(*) AS total
         FROM vehiculos v
         JOIN estado_vehiculo ev ON v.id_estado = ev.id_estado_vehiculo
         GROUP BY ev.estado"
    );
    foreach ($stmtEst->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if (stripos($row['estado'], 'reparaci')  !== false) $enReparacion += (int)$row['total'];
        if (stripos($row['estado'], 'espera')     !== false) $pendientes   += (int)$row['total'];
    }
} catch (Exception $e) {}

// Últimas 8 órdenes de trabajo
$ordenes = [];
try {
    $stmtOrd = $db->query(
        "SELECT ot.id_orden,
                ot.descripcion_danos AS descripcion,
                ot.fecha_ingreso     AS fecha_registro,
                'Orden de trabajo'   AS tipo_reparacion,
                v.placa, v.marca,
                CONCAT(c.nombres,' ',COALESCE(c.apellidos,'')) AS cliente_nombre,
                ev.estado
         FROM ordenes_trabajo ot
         JOIN vehiculos       v  ON ot.id_vehiculo = v.id_vehiculo
         JOIN clientes        c  ON ot.id_cliente  = c.id_cliente
         JOIN estado_vehiculo ev ON v.id_estado     = ev.id_estado_vehiculo
         ORDER BY ot.fecha_ingreso DESC
         LIMIT 8"
    );
    $ordenes = $stmtOrd ? $stmtOrd->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (Exception $e) {}
?>

<!-- Tarjetas resumen -->
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="card shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 p-3 bg-secondary bg-opacity-10">
                    <i class="fas fa-list fa-lg text-secondary"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold"><?= $totalOrdenes ?></div>
                    <div class="text-muted small">Total Registros</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 p-3 bg-info bg-opacity-10">
                    <i class="fas fa-wrench fa-lg text-info"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold"><?= $enReparacion ?></div>
                    <div class="text-muted small">En Reparación</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 p-3 bg-warning bg-opacity-10">
                    <i class="fas fa-clock fa-lg text-warning"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold"><?= $pendientes ?></div>
                    <div class="text-muted small">Pendientes</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de órdenes recientes -->
<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold border-bottom d-flex justify-content-between align-items-center">
        <span><i class="fas fa-clipboard-list text-primary me-2"></i>Órdenes Recientes</span>
        <a href="empleado_ordenes.php" class="btn btn-sm btn-outline-primary">
            Ver todas <i class="fas fa-arrow-right ms-1"></i>
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle small mb-0">
                <thead class="table-light">
                    <tr>
                        <th>VEHÍCULO</th>
                        <th>CLIENTE</th>
                        <th>TIPO SERVICIO</th>
                        <th>ESTADO</th>
                        <th>FECHA</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($ordenes as $o):
                    $estado = $o['estado'] ?? '';
                    $badge  = match(true) {
                        stripos($estado, 'pendiente')  !== false => 'bg-warning text-dark',
                        stripos($estado, 'reparaci')   !== false => 'bg-info text-dark',
                        stripos($estado, 'finalizado') !== false => 'bg-success',
                        stripos($estado, 'pintura')    !== false => 'bg-primary',
                        stripos($estado, 'entregado')  !== false => 'bg-success',
                        default                                  => 'bg-secondary',
                    };
                ?>
                <tr>
                    <td>
                        <div class="fw-bold font-monospace"><?= htmlspecialchars($o['placa']) ?></div>
                        <div class="text-muted" style="font-size:.72rem;"><?= htmlspecialchars($o['marca']) ?></div>
                    </td>
                    <td><?= htmlspecialchars($o['cliente_nombre']) ?></td>
                    <td>
                        <span class="badge bg-light text-dark border">
                            <?= htmlspecialchars($o['tipo_reparacion']) ?>
                        </span>
                    </td>
                    <td><span class="badge <?= $badge ?>"><?= htmlspecialchars($estado) ?></span></td>
                    <td class="text-muted"><?= date('d/m/Y', strtotime($o['fecha_registro'])) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($ordenes)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            <i class="fas fa-clipboard-list fa-2x mb-2 opacity-25 d-block"></i>
                            No hay órdenes registradas aún.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
