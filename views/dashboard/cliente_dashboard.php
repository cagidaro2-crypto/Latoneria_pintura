<?php
$titulo = 'Mi Panel – Cliente';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Vehiculo.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario']) || (int)($_SESSION['usuario']['rol'] ?? 0) !== 2) {
    header("Location: ../usuarios/login.php"); exit;
}

require_once __DIR__ . '/../layouts/header.php';

$db        = (new Database())->conectar();
$vModel    = new Vehiculo($db);

// id_usuario en sesión = id_persona en tabla persona
// Necesitamos id_cliente de tabla cliente (buscar por correo)
$correoSesion = $usuario['correo'] ?? '';
$idCliente    = $vModel->buscarIdClientePorCorreo($correoSesion);

// Vehículos del cliente
$vehiculos = $idCliente ? $vModel->obtenerPorCliente($idCliente) : [];

// Cotizaciones pendientes — tabla real: cotizacion (sin 's')
// cotizacion no tiene id_cliente directo, va por vehiculo
$cotPendientes = 0;
if ($idCliente) {
    $stmtCot = $db->prepare(
        "SELECT COUNT(*) FROM cotizacion c
         JOIN vehiculo v ON c.id_vehiculo = v.id_vehiculo
         WHERE v.id_cliente = :id"
    );
    $stmtCot->execute([':id' => $idCliente]);
    $cotPendientes = (int)$stmtCot->fetchColumn();
}

// Citas — tabla no existe en BD actual, usar 0 por defecto
$citasProximas = 0;
?>

<!-- TARJETAS -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card card-stat shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="fas fa-car"></i></div>
                <div>
                    <div class="fs-4 fw-bold"><?= count($vehiculos) ?></div>
                    <div class="text-muted small">Mis Vehículos</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card card-stat shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="fas fa-clipboard-list"></i></div>
                <div>
                    <div class="fs-4 fw-bold"><?= $cotPendientes ?></div>
                    <div class="text-muted small">Cotizaciones</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card card-stat shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="fas fa-file-invoice"></i></div>
                <div>
                    <div class="fs-4 fw-bold"><?= $cotPendientes ?></div>
                    <div class="text-muted small">Cotizaciones Pendientes</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card card-stat shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="fas fa-calendar-check"></i></div>
                <div>
                    <div class="fs-4 fw-bold"><?= $citasProximas ?></div>
                    <div class="text-muted small">Citas Próximas</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MIS VEHÍCULOS Y SEGUIMIENTO -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold border-bottom d-flex justify-content-between align-items-center">
                <span><i class="fas fa-car text-primary me-2"></i>Mis Vehículos</span>
                <a href="cliente_vehiculos.php" class="btn btn-sm btn-outline-primary">Ver todo</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>PLACA</th>
                                <th>VEHÍCULO</th>
                                <th>ESTADO</th>
                                <th>ÚLTIMO REGISTRO</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach (array_slice($vehiculos, 0, 5) as $v):
                            $estado = $v['estado'] ?? '';
                            $badge  = match(true) {
                                stripos($estado, 'pendiente')  !== false => 'bg-warning text-dark',
                                stripos($estado, 'reparacion')   !== false => 'bg-info text-dark',
                                stripos($estado, 'finalizado') !== false => 'bg-success',
                                stripos($estado, 'pintura')    !== false => 'bg-primary',
                                stripos($estado, 'entregado')  !== false => 'bg-success',
                                default                                  => 'bg-secondary',
                            };
                            // Último historial del vehículo
                            $stmtH = $db->prepare(
                                "SELECT descripcion, fecha_registro, tipo_reparacion
                                 FROM historial_vehiculo
                                 WHERE id_vehiculo = :id
                                 ORDER BY fecha_registro DESC LIMIT 1"
                            );
                            $stmtH->execute([':id' => $v['id_vehiculo']]);
                            $ultimoH = $stmtH->fetch(PDO::FETCH_ASSOC);
                        ?>
                            <tr>
                                <td class="fw-bold font-monospace"><?= htmlspecialchars($v['placa']) ?></td>
                                <td><?= htmlspecialchars($v['marca'] . ' ' . $v['modelo']) ?></td>
                                <td><span class="badge <?= $badge ?>"><?= htmlspecialchars($estado ?: 'Sin estado') ?></span></td>
                                <td>
                                    <?php if ($ultimoH): ?>
                                        <div class="small fw-semibold"><?= htmlspecialchars($ultimoH['tipo_reparacion']) ?></div>
                                        <div class="text-muted" style="font-size:.72rem;">
                                            <?= date('d/m/Y', strtotime($ultimoH['fecha_registro'])) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">Sin registros</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($vehiculos)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">
                                    No tienes vehículos registrados.
                                    <a href="cliente_vehiculos.php" class="ms-1">Registrar uno</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ACCESOS RÁPIDOS CLIENTE -->
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold border-bottom">
                <i class="fas fa-bolt text-primary me-2"></i>Accesos Rápidos
            </div>
            <div class="card-body d-flex flex-column gap-2">
                <a href="cliente_vehiculos.php" class="btn btn-outline-primary btn-sm text-start">
                    <i class="fas fa-car me-2"></i>Registrar Vehículo
                </a>
                <a href="cliente_citas.php" class="btn btn-outline-secondary btn-sm text-start">
                    <i class="fas fa-calendar-plus me-2"></i>Agendar Cita
                </a>
                <a href="cliente_cotizaciones.php" class="btn btn-outline-secondary btn-sm text-start">
                    <i class="fas fa-file-invoice me-2"></i>Revisar Cotizaciones
                </a>
                <a href="cliente_ordenes.php" class="btn btn-outline-secondary btn-sm text-start">
                    <i class="fas fa-search me-2"></i>Estado de mi Vehículo
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
