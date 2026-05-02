<?php
$titulo = 'Dashboard – Empleado';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/OrdenServicio.php';
require_once __DIR__ . '/../layouts/header.php';

$db      = (new Database())->conectar();
$oModel  = new OrdenServicio($db);
$idEmpleado = $usuario['id_usuario'];

$misOrdenes = array_filter($oModel->obtenerTodas(), fn($o) => true); // Se pueden filtrar por empleado
$enReparacion = $oModel->contarPorEstado('En reparación');
$ingresadas   = $oModel->contarPorEstado('Ingresado');
$enEspera     = $oModel->contarPorEstado('En espera');
?>

<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="card card-stat shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-secondary bg-opacity-10 text-secondary"><i class="fas fa-list"></i></div>
                <div>
                    <div class="fs-4 fw-bold"><?= $ingresadas ?></div>
                    <div class="text-muted small">Ingresadas</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card card-stat shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="fas fa-wrench"></i></div>
                <div>
                    <div class="fs-4 fw-bold"><?= $enReparacion ?></div>
                    <div class="text-muted small">En Reparación</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card card-stat shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="fas fa-clock"></i></div>
                <div>
                    <div class="fs-4 fw-bold"><?= $enEspera ?></div>
                    <div class="text-muted small">En Espera</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold border-bottom d-flex justify-content-between">
        <span><i class="fas fa-clipboard-list text-primary me-2"></i>Órdenes Activas</span>
        <a href="empleado_ordenes.php" class="btn btn-sm btn-outline-primary">Ver todas</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover small mb-0">
                <thead class="table-light">
                    <tr>
                        <th>N° Orden</th>
                        <th>Cliente</th>
                        <th>Vehículo</th>
                        <th>Servicio</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach (array_slice($misOrdenes, 0, 10) as $o): ?>
                <?php
                $badges = [
                    'Ingresado'=>'bg-secondary','En espera'=>'bg-warning text-dark',
                    'En reparación'=>'bg-info text-dark','Finalizado'=>'bg-success','Entregado'=>'bg-primary'
                ];
                $b = $badges[$o['estado']] ?? 'bg-secondary';
                ?>
                <tr>
                    <td class="fw-semibold text-primary"><?= htmlspecialchars($o['numero_orden']) ?></td>
                    <td><?= htmlspecialchars($o['cliente_nombre']) ?></td>
                    <td><?= htmlspecialchars($o['placa']) ?></td>
                    <td><?= htmlspecialchars($o['tipo_servicio']) ?></td>
                    <td><span class="badge <?= $b ?>"><?= $o['estado'] ?></span></td>
                    <td><?= date('d/m/Y', strtotime($o['fecha_ingreso'])) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($misOrdenes)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-3">No hay órdenes activas.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
