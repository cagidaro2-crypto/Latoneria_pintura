<?php
$titulo = 'Mi Panel – Cliente';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Vehiculo.php';
require_once __DIR__ . '/../../models/OrdenServicio.php';
require_once __DIR__ . '/../layouts/header.php';

$db      = (new Database())->conectar();
$idCliente = $usuario['id_usuario'];

$vModel    = new Vehiculo($db);
$oModel    = new OrdenServicio($db);

$vehiculos = $vModel->obtenerPorCliente($idCliente);
$ordenes   = $oModel->obtenerPorCliente($idCliente);

// Cotizaciones pendientes
$stmtCot = $db->prepare("SELECT COUNT(*) FROM cotizaciones WHERE id_cliente=:id AND estado='Pendiente'");
$stmtCot->execute([':id' => $idCliente]);
$cotPendientes = $stmtCot->fetchColumn();

// Próximas citas
$stmtCita = $db->prepare("SELECT COUNT(*) FROM citas WHERE id_cliente=:id AND estado IN('Pendiente','Confirmada') AND fecha_cita >= NOW()");
$stmtCita->execute([':id' => $idCliente]);
$citasProximas = $stmtCita->fetchColumn();
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
                    <div class="fs-4 fw-bold"><?= count($ordenes) ?></div>
                    <div class="text-muted small">Órdenes</div>
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

<!-- ESTADO DE ÓRDENES ACTIVAS -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold border-bottom d-flex justify-content-between align-items-center">
                <span><i class="fas fa-clipboard-list text-primary me-2"></i>Estado de mis Servicios</span>
                <a href="cliente_ordenes.php" class="btn btn-sm btn-outline-primary">Ver todo</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>N° Orden</th>
                                <th>Vehículo</th>
                                <th>Servicio</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach (array_slice($ordenes, 0, 5) as $o): ?>
                            <?php
                            $badges = [
                                'Ingresado'     => 'bg-secondary',
                                'En espera'     => 'bg-warning text-dark',
                                'En reparación' => 'bg-info text-dark',
                                'Finalizado'    => 'bg-success',
                                'Entregado'     => 'bg-primary',
                            ];
                            $b = $badges[$o['estado']] ?? 'bg-secondary';
                            ?>
                            <tr>
                                <td class="fw-semibold text-primary"><?= htmlspecialchars($o['numero_orden']) ?></td>
                                <td><?= htmlspecialchars($o['placa'] . ' – ' . $o['marca']) ?></td>
                                <td><?= htmlspecialchars($o['tipo_servicio']) ?></td>
                                <td><span class="badge <?= $b ?>"><?= htmlspecialchars($o['estado']) ?></span></td>
                                <td><?= date('d/m/Y', strtotime($o['fecha_ingreso'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($ordenes)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-3">Tu vehículo no tiene órdenes de servicio activas en este momento.</td></tr>
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
