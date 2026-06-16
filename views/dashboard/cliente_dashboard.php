<?php
$titulo = 'Mi Panel – Cliente';
require_once __DIR__ . '/../../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario']) || (int)($_SESSION['usuario']['rol'] ?? 0) !== 3) {
    header("Location: ../usuarios/login.php"); exit;
}

$usuario = $_SESSION['usuario'];
$db      = (new Database())->conectar();

// ── id_cliente ────────────────────────────────────────────────────────────
$idCliente = null;
try {
    $s = $db->prepare("SELECT id_cliente FROM clientes WHERE correo = :c LIMIT 1");
    $s->execute([':c' => $usuario['correo'] ?? '']);
    $r = $s->fetch(PDO::FETCH_ASSOC);
    $idCliente = $r ? (int)$r['id_cliente'] : null;
} catch (Exception $e) {}

// ── Vehículos ─────────────────────────────────────────────────────────────
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

// ── Cotizaciones ──────────────────────────────────────────────────────────
$cotTotal = 0; $cotPendientes = 0;
if ($idCliente) {
    try {
        $s = $db->prepare("SELECT COUNT(*) AS total, SUM(CASE WHEN estado='Pendiente' THEN 1 ELSE 0 END) AS pendientes FROM cotizaciones WHERE id_cliente=:id");
        $s->execute([':id' => $idCliente]);
        $r = $s->fetch(PDO::FETCH_ASSOC);
        $cotTotal      = (int)($r['total']      ?? 0);
        $cotPendientes = (int)($r['pendientes'] ?? 0);
    } catch (Exception $e) {}
}

// ── Citas próximas ────────────────────────────────────────────────────────
$citasProximas = 0;
if ($idCliente) {
    try {
        $s = $db->prepare("SELECT COUNT(*) FROM citas WHERE id_cliente=:id AND fecha_cita>=NOW() AND estado NOT IN ('Cancelada','Realizada')");
        $s->execute([':id' => $idCliente]);
        $citasProximas = (int)$s->fetchColumn();
    } catch (Exception $e) {}
}

// ── Contadores globales (panel de roles) ─────────────────────────────────
$totalAdmins = 0; $totalEmpleados = 0; $totalClientes = 0;
try {
    $s = $db->query("SELECT id_rol, COUNT(*) AS cnt FROM usuarios GROUP BY id_rol");
    foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if ((int)$row['id_rol'] === 1) $totalAdmins    = (int)$row['cnt'];
        if ((int)$row['id_rol'] === 2) $totalEmpleados = (int)$row['cnt'];
        if ((int)$row['id_rol'] === 3) $totalClientes  = (int)$row['cnt'];
    }
} catch (Exception $e) {}

$totalVehiculos = 0; $totalOrdenes = 0;
try {
    $totalVehiculos = (int)$db->query("SELECT COUNT(*) FROM vehiculos")->fetchColumn();
    $totalOrdenes   = (int)$db->query("SELECT COUNT(*) FROM ordenes_trabajo")->fetchColumn();
} catch (Exception $e) {}

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/cliente_styles.php';
?>

<!-- ── Banner bienvenida ─────────────────────────────────────────────────── -->
<div style="
    background: linear-gradient(135deg,#000000 0%,#334155 100%);
    border-radius:16px; padding:1.8rem 2rem; margin-bottom:1.5rem;
    position:relative; overflow:hidden;">
    <div style="position:absolute;right:2rem;top:50%;transform:translateY(-50%);font-size:6rem;opacity:.06;color:#fff;font-family:'Font Awesome 6 Free';font-weight:900;">&#xf1b9;</div>
    <h4 style="font-size:1.5rem;font-weight:700;color:#fff;margin-bottom:.3rem;">
        Bienvenido, <?= htmlspecialchars($usuario['nombres'] ?? 'Cliente') ?> 👋
    </h4>
    <p style="color:#94a3b8;margin:0;font-size:.9rem;">
        Consulta el estado de tus vehículos, citas y cotizaciones en tiempo real.
    </p>
</div>

<!-- ── Tarjetas resumen ───────────────────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <?php
    $stats = [
        ['icon'=>'fa-car',           'color'=>'#000000','bg'=>'#f1f5f9', 'label'=>'Mis Vehículos',  'value'=>count($vehiculos)],
        ['icon'=>'fa-file-invoice',  'color'=>'#f59e0b','bg'=>'#fefce8', 'label'=>'Cotizaciones',   'value'=>$cotTotal],
        ['icon'=>'fa-clock',         'color'=>'#ef4444','bg'=>'#fef2f2', 'label'=>'Pendientes',     'value'=>$cotPendientes],
        ['icon'=>'fa-calendar-check','color'=>'#22c55e','bg'=>'#f0fdf4', 'label'=>'Citas Próximas', 'value'=>$citasProximas],
    ];
    foreach ($stats as $st): ?>
    <div class="col-6 col-xl-3">
        <div class="cs-card" style="transition:transform .2s,box-shadow .2s;" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 6px 20px rgba(0,0,0,.1)';" onmouseout="this.style.transform='';this.style.boxShadow='';">
            <div class="cs-card-body d-flex align-items-center gap-3">
                <div style="width:50px;height:50px;border-radius:12px;background:<?= $st['bg'] ?>;display:flex;align-items:center;justify-content:center;font-size:1.2rem;color:<?= $st['color'] ?>;flex-shrink:0;">
                    <i class="fas <?= $st['icon'] ?>"></i>
                </div>
                <div>
                    <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#64748b;"><?= $st['label'] ?></div>
                    <div style="font-size:1.9rem;font-weight:800;color:#000000;line-height:1.1;"><?= $st['value'] ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ── Mis vehículos + accesos rápidos ───────────────────────────────────── -->
<div class="row g-3">

    <!-- Tabla vehículos -->
    <div class="col-lg-8">
        <div class="cs-card">
            <div class="cs-card-head">
                <span style="font-weight:700;color:#000000;"><i class="fas fa-car me-2 cs-icon-blue"></i>Mis Vehículos</span>
                <a href="cliente_vehiculos.php" class="cs-btn-dark" style="font-size:.78rem;padding:.3rem .8rem;">
                    Ver todos <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
            <div style="overflow-x:auto;">
                <table class="cs-table">
                    <thead><tr><th>Placa</th><th>Vehículo</th><th>Estado</th><th>Último registro</th></tr></thead>
                    <tbody>
                    <?php foreach (array_slice($vehiculos, 0, 6) as $v):
                        $estado = $v['estado'] ?? '';
                        $estCls = match(true) {
                            stripos($estado,'espera')   !==false => 'est-espera',
                            stripos($estado,'reparaci') !==false => 'est-reparacion',
                            stripos($estado,'pintura')  !==false => 'est-pintura',
                            stripos($estado,'listo')    !==false => 'est-listo',
                            stripos($estado,'cancelado')!==false => 'est-cancelado',
                            default                              => 'est-default',
                        };
                        $ultimoH = null;
                        try {
                            $sh = $db->prepare("SELECT tipo_reparacion, fecha_registro FROM historial_vehiculo WHERE id_vehiculo=:id ORDER BY fecha_registro DESC LIMIT 1");
                            $sh->execute([':id' => $v['id_vehiculo']]);
                            $ultimoH = $sh->fetch(PDO::FETCH_ASSOC);
                        } catch (Exception $e) {}
                    ?>
                    <tr>
                        <td><span style="font-family:monospace;font-weight:800;color:#000000;"><?= htmlspecialchars($v['placa']) ?></span></td>
                        <td>
                            <div style="font-weight:600;color:#000000;"><?= htmlspecialchars(($v['marca']??'').' '.($v['modelo']??'')) ?></div>
                            <?php if(!empty($v['anio'])): ?><div style="font-size:.72rem;color:#94a3b8;"><?= htmlspecialchars($v['anio']) ?></div><?php endif; ?>
                        </td>
                        <td><span class="cs-badge <?= $estCls ?>"><?= htmlspecialchars($estado ?: 'Sin estado') ?></span></td>
                        <td>
                            <?php if ($ultimoH): ?>
                                <div style="font-size:.82rem;font-weight:600;color:#000000;"><?= htmlspecialchars($ultimoH['tipo_reparacion']??'') ?></div>
                                <div style="font-size:.7rem;color:#94a3b8;"><?= date('d/m/Y',strtotime($ultimoH['fecha_registro'])) ?></div>
                            <?php else: ?>
                                <span style="font-size:.82rem;color:#94a3b8;">Sin registros</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($vehiculos)): ?>
                    <tr><td colspan="4" style="text-align:center;padding:2.5rem;color:#94a3b8;">
                        <i class="fas fa-car" style="display:block;font-size:2rem;margin-bottom:.75rem;opacity:.3;"></i>
                        No tienes vehículos registrados.
                        <a href="cliente_vehiculos.php" style="color:#000000;display:block;margin-top:.5rem;font-size:.85rem;">Registrar uno</a>
                    </td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Accesos rápidos -->
    <div class="col-lg-4">
        <div class="cs-card h-100">
            <div class="cs-card-head">
                <span style="font-weight:700;color:#000000;"><i class="fas fa-bolt me-2" style="color:#f59e0b;"></i>Accesos Rápidos</span>
            </div>
            <div class="cs-card-body d-flex flex-column gap-2">
                <?php
                $accesos = [
                    ['href'=>'cliente_vehiculos.php',  'icon'=>'fa-car',            'bg'=>'#f1f5f9','clr'=>'#000000','title'=>'Mis Vehículos',      'sub'=>'Registrar o consultar'],
                    ['href'=>'cliente_citas.php',       'icon'=>'fa-calendar-plus',  'bg'=>'#f0fdf4','clr'=>'#16a34a','title'=>'Agendar Cita',       'sub'=>'Reserva tu próxima visita'],
                    ['href'=>'cliente_cotizaciones.php','icon'=>'fa-file-invoice',   'bg'=>'#fefce8','clr'=>'#d97706','title'=>'Mis Cotizaciones',    'sub'=>'Revisa tus presupuestos'],
                    ['href'=>'cliente_ordenes.php',     'icon'=>'fa-magnifying-glass','bg'=>'#fdf4ff','clr'=>'#9333ea','title'=>'Estado del Servicio','sub'=>'Seguimiento en tiempo real'],
                    ['href'=>'cliente_catalogo.php',    'icon'=>'fa-boxes-stacked',  'bg'=>'#f0f9ff','clr'=>'#0284c7','title'=>'Catálogo',           'sub'=>'Ver productos disponibles'],
                ];
                foreach ($accesos as $a): ?>
                <a href="<?= $a['href'] ?>" style="display:flex;align-items:center;gap:.85rem;padding:.9rem 1rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;color:#000000;text-decoration:none;font-size:.88rem;font-weight:500;transition:all .18s;" onmouseover="this.style.background='#f1f5f9';this.style.borderColor='#bfdbfe';" onmouseout="this.style.background='#f8fafc';this.style.borderColor='#e2e8f0';">
                    <div style="width:36px;height:36px;border-radius:8px;background:<?= $a['bg'] ?>;display:flex;align-items:center;justify-content:center;color:<?= $a['clr'] ?>;font-size:.9rem;flex-shrink:0;">
                        <i class="fas <?= $a['icon'] ?>"></i>
                    </div>
                    <div>
                        <div style="font-weight:600;color:#000000;"><?= $a['title'] ?></div>
                        <div style="font-size:.75rem;color:#94a3b8;"><?= $a['sub'] ?></div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
