<?php
$titulo = 'Estado de mis Vehículos';
require_once __DIR__ . '/../../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario']) || (int)($_SESSION['usuario']['rol'] ?? 0) !== 3) {
    header("Location: ../usuarios/login.php"); exit;
}

require_once __DIR__ . '/../layouts/header.php';

$db     = (new Database())->conectar();
$correo = $_SESSION['usuario']['correo'] ?? '';

$stmtCli = $db->prepare("SELECT id_cliente FROM clientes WHERE correo = :c LIMIT 1");
$stmtCli->execute([':c' => $correo]);
$idCliente = (int)($stmtCli->fetchColumn() ?: 0);

$vehiculos = [];
if ($idCliente) {
    try {
        $s = $db->prepare(
            "SELECT v.*, ev.estado
             FROM vehiculos v
             LEFT JOIN estado_vehiculo ev ON v.id_estado = ev.id_estado_vehiculo
             WHERE v.id_cliente = :id ORDER BY v.id_vehiculo DESC"
        );
        $s->execute([':id' => $idCliente]);
        $vehiculos = $s->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

$historiales = [];
foreach ($vehiculos as $v) {
    try {
        $sh = $db->prepare(
            "SELECT hv.*, u.nombres AS empleado_nombre
             FROM historial_vehiculo hv
             LEFT JOIN usuarios u ON hv.id_usuario = u.id_usuario
             WHERE hv.id_vehiculo = :id ORDER BY hv.fecha_registro DESC"
        );
        $sh->execute([':id' => $v['id_vehiculo']]);
        $historiales[$v['id_vehiculo']] = $sh->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { $historiales[$v['id_vehiculo']] = []; }
}
?>
<?php require_once __DIR__ . '/cliente_styles.php'; ?>

<!-- Encabezado -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <div class="cs-title"><i class="fas fa-search me-2 cs-icon-blue"></i>Estado de mis Vehículos</div>
        <div class="cs-sub">Consulta el seguimiento y estado actual de tus vehículos en el taller</div>
    </div>
    <a href="cliente_vehiculos.php" class="cs-btn-dark">
        <i class="fas fa-car"></i> Mis Vehículos
    </a>
</div>

<?php if (empty($vehiculos)): ?>
<div class="cs-empty">
    <i class="fas fa-car fa-3x cs-empty-icon"></i>
    <h6 class="cs-empty-title">No tienes vehículos registrados</h6>
    <p class="cs-empty-sub">Registra tu vehículo para hacer seguimiento de su estado en el taller.</p>
    <a href="cliente_vehiculos.php" class="cs-btn-dark"><i class="fas fa-plus me-1"></i> Registrar Vehículo</a>
</div>
<?php else: ?>

<?php
$hitosConfig = [
    ['label'=>'Ingresado',    'match'=>'espera',   'pct'=>15],
    ['label'=>'En reparación','match'=>'reparaci',  'pct'=>45],
    ['label'=>'Pintura',      'match'=>'pintura',   'pct'=>75],
    ['label'=>'Finalizado',   'match'=>'listo',     'pct'=>100],
];

foreach ($vehiculos as $v):
    $estado = $v['estado'] ?? '';
    $hist   = $historiales[$v['id_vehiculo']] ?? [];
    $ultimo = $hist[0] ?? null;
    $colId  = 'hist-'.$v['id_vehiculo'];

    $estClass = match(true) {
        stripos($estado,'espera')    !==false => 'est-espera',
        stripos($estado,'reparaci')  !==false => 'est-reparacion',
        stripos($estado,'pintura')   !==false => 'est-pintura',
        stripos($estado,'listo')     !==false => 'est-listo',
        stripos($estado,'cancelado') !==false => 'est-cancelado',
        default                               => 'est-default',
    };

    $progreso = match(true) {
        stripos($estado,'espera')    !==false => 15,
        stripos($estado,'reparaci')  !==false => 45,
        stripos($estado,'pintura')   !==false => 75,
        stripos($estado,'listo')     !==false => 100,
        stripos($estado,'cancelado') !==false => 0,
        default                               => 5,
    };
    $progColor = match(true) {
        $progreso>=100 => '#22c55e',
        $progreso>=75  => '#000000',
        $progreso>=45  => '#6366f1',
        $progreso>=15  => '#f59e0b',
        default        => '#ef4444',
    };
?>
<div class="cs-card mb-4">
    <!-- Cabecera -->
    <div class="cs-card-head">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="cs-placa"><?= htmlspecialchars($v['placa']) ?></span>
            <span class="cs-info">
                <?= htmlspecialchars(($v['marca']??'').' '.($v['modelo']??'')) ?>
                <?php if(!empty($v['anio'])): ?> · <span class="cs-muted"><?= htmlspecialchars($v['anio']) ?></span><?php endif; ?>
                <?php if(!empty($v['color'])): ?> · <?= htmlspecialchars($v['color']) ?><?php endif; ?>
            </span>
        </div>
        <span class="cs-badge <?= $estClass ?>"><?= htmlspecialchars($estado ?: 'Sin estado') ?></span>
    </div>

    <!-- Progreso con hitos -->
    <div class="cs-card-body">
        <div class="cs-hitos">
            <?php foreach($hitosConfig as $i => $h):
                $done   = $progreso >= $h['pct'];
                $active = !$done && ($i===0 || $progreso >= $hitosConfig[$i-1]['pct']);
            ?>
            <div class="cs-hito">
                <div class="cs-hito-dot <?= $done?'done':($active?'active':'') ?>"></div>
                <div class="cs-hito-label <?= $done?'done':($active?'active':'') ?>"><?= $h['label'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="cs-prog-track">
            <div class="cs-prog-fill" style="width:<?= $progreso ?>%;background:<?= $progColor ?>;"></div>
        </div>

        <!-- Último registro -->
        <?php if($ultimo): ?>
        <div class="cs-last-reg">
            <div class="cs-reg-icon"><i class="fas fa-wrench"></i></div>
            <div>
                <div class="cs-reg-label">Último registro</div>
                <div class="cs-reg-tipo"><?= htmlspecialchars($ultimo['tipo_reparacion']??'') ?></div>
                <?php if(!empty($ultimo['descripcion'])): ?><div class="cs-reg-desc"><?= htmlspecialchars($ultimo['descripcion']) ?></div><?php endif; ?>
                <div class="cs-reg-meta">
                    <i class="fas fa-calendar-alt me-1"></i><?= date('d/m/Y',strtotime($ultimo['fecha_registro'])) ?>
                    <?php if(!empty($ultimo['empleado_nombre'])): ?> · <i class="fas fa-user-tie me-1"></i><?= htmlspecialchars($ultimo['empleado_nombre']) ?><?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Footer historial -->
    <div class="cs-card-foot">
        <button class="cs-btn-hist" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $colId ?>">
            <i class="fas fa-history"></i> Ver historial completo (<?= count($hist) ?> registro<?= count($hist)!==1?'s':'' ?>)
        </button>
        <div class="collapse" id="<?= $colId ?>">
            <div class="cs-timeline-wrap">
                <div class="cs-tl-title"><i class="fas fa-history me-1"></i>Historial</div>
                <?php if(empty($hist)): ?>
                    <p class="cs-muted" style="font-size:.82rem;margin:0;text-align:center;">Sin registros.</p>
                <?php else: ?>
                <div class="cs-tl">
                    <?php foreach($hist as $h): ?>
                    <div class="cs-tl-item">
                        <div class="cs-tl-dot"></div>
                        <div class="cs-tl-tipo"><?= htmlspecialchars($h['tipo_reparacion']??'Registro') ?></div>
                        <?php if(!empty($h['descripcion'])): ?><div class="cs-tl-desc"><?= htmlspecialchars($h['descripcion']) ?></div><?php endif; ?>
                        <div class="cs-tl-meta"><i class="fas fa-calendar-alt me-1"></i><?= date('d/m/Y',strtotime($h['fecha_registro'])) ?><?php if(!empty($h['empleado_nombre'])): ?> · <i class="fas fa-user-tie me-1"></i><?= htmlspecialchars($h['empleado_nombre']) ?><?php endif; ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
