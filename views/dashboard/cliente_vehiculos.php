<?php
$titulo = 'Mis Vehículos';
require_once __DIR__ . '/../../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario']) || (int)($_SESSION['usuario']['rol'] ?? 0) !== 3) {
    header("Location: ../usuarios/login.php"); exit;
}

require_once __DIR__ . '/../../models/Vehiculo.php';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/cliente_styles.php';

$db     = (new Database())->conectar();
$vModel = new Vehiculo($db);
$alert  = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);

$correoSesion = $usuario['correo'] ?? '';
$idCliente    = $vModel->buscarIdClientePorCorreo($correoSesion);
$estados      = $vModel->obtenerEstados();

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

$fotos = []; $historiales = [];
foreach ($vehiculos as $v) {
    $id = $v['id_vehiculo'];
    try {
        $sf = $db->prepare("SELECT ruta_archivo FROM vehiculo_fotos WHERE id_vehiculo=:id ORDER BY FIELD(etapa,'antes','durante','despues'),fecha_subida DESC LIMIT 1");
        $sf->execute([':id'=>$id]);
        $fotos[$id] = $sf->fetchColumn() ?: null;
    } catch (Exception $e) { $fotos[$id] = null; }

    try {
        $sh = $db->prepare("SELECT hv.*,u.nombres AS empleado_nombre FROM historial_vehiculo hv LEFT JOIN usuarios u ON hv.id_usuario=u.id_usuario WHERE hv.id_vehiculo=:id ORDER BY hv.fecha_registro DESC");
        $sh->execute([':id'=>$id]);
        $historiales[$id] = $sh->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { $historiales[$id] = []; }
}
?>
<style>
    /* Tarjeta vehículo — light */
    .veh-card { background:#fff; border:1px solid #e2e8f0; border-radius:16px; overflow:hidden; transition:transform .2s,box-shadow .2s; }
    .veh-card:hover { transform:translateY(-4px); box-shadow:0 10px 28px rgba(0,0,0,.12); }
    .veh-photo { width:100%; height:180px; object-fit:cover; display:block; }
    .veh-placeholder { width:100%; height:180px; background:#f1f5f9; display:flex; align-items:center; justify-content:center; font-size:3.5rem; color:#cbd5e1; }
    .veh-estado-badge { position:absolute; top:.7rem; right:.7rem; font-size:.72rem; font-weight:700; padding:.3em .75em; border-radius:20px; backdrop-filter:blur(4px); }
    .veh-body { padding:1rem 1.1rem .8rem; }
    .veh-placa { font-family:monospace; font-size:1rem; font-weight:800; color:#000000; letter-spacing:.5px; }
    .veh-nombre { font-size:.85rem; color:#64748b; }
    .veh-prog-bg { height:5px; border-radius:4px; background:#e2e8f0; overflow:hidden; margin-top:.85rem; }
    .veh-prog-fill { height:100%; border-radius:4px; transition:width .6s; }
    .veh-foot { padding:.7rem 1.1rem; border-top:1px solid #f1f5f9; background:#fafafa; }
    .veh-hist-panel { background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:.9rem 1rem; margin-top:.75rem; }
</style>

<!-- Encabezado -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <div class="cs-title"><i class="fas fa-car me-2" style="color:#000000;"></i>Mis Vehículos</div>
        <div class="cs-sub">Consulta el estado y seguimiento de tus vehículos</div>
    </div>
    <button class="cs-btn-dark" style="font-size:.8rem;padding:.38rem .9rem;"
            data-bs-toggle="modal" data-bs-target="#modalRegistrar">
        <i class="fas fa-plus"></i> Registrar mi Vehículo
    </button>
</div>

<?php if(empty($vehiculos)): ?>
<div class="cs-empty">
    <i class="fas fa-car fa-3x cs-empty-icon"></i>
    <h6 class="cs-empty-title">No tienes vehículos registrados</h6>
    <p class="cs-empty-sub">Registra tu vehículo para hacer seguimiento de su estado en el taller.</p>
    <button class="cs-btn-dark" data-bs-toggle="modal" data-bs-target="#modalRegistrar"><i class="fas fa-plus me-1"></i> Registrar Vehículo</button>
</div>
<?php else: ?>
<div class="row g-4">
<?php foreach($vehiculos as $v):
    $id=$v['id_vehiculo']; $estado=$v['estado']??'Sin estado';
    $hist=$historiales[$id]??[]; $ultimo=$hist[0]??null; $foto=$fotos[$id]??null;

    [$bgE,$clrE]=match(true){
        stripos($estado,'espera')   !==false=>['#fef9c3','#854d0e'],
        stripos($estado,'reparaci') !==false=>['#e2e8f0','#000000'],
        stripos($estado,'pintura')  !==false=>['#e0e7ff','#4338ca'],
        stripos($estado,'listo')    !==false=>['#dcfce7','#15803d'],
        stripos($estado,'cancelado')!==false=>['#fee2e2','#b91c1c'],
        default=>['#f1f5f9','#475569'],
    };
    $prog=match(true){
        stripos($estado,'espera')   !==false=>15,
        stripos($estado,'reparaci') !==false=>45,
        stripos($estado,'pintura')  !==false=>75,
        stripos($estado,'listo')    !==false=>100,
        stripos($estado,'cancelado')!==false=>0,
        default=>5,
    };
    $progClr=match(true){$prog>=100=>'#22c55e',$prog>=75=>'#000000',$prog>=45=>'#6366f1',$prog>=15=>'#f59e0b',default=>'#ef4444'};
?>
<div class="col-sm-6 col-xl-4">
<div class="veh-card">
    <div style="position:relative;">
        <?php if($foto && file_exists(__DIR__.'/../../public/'.$foto)): ?>
            <img src="../../public/<?= htmlspecialchars($foto) ?>" class="veh-photo" alt="Foto vehículo">
        <?php else: ?>
            <div class="veh-placeholder"><i class="fas fa-car"></i></div>
        <?php endif; ?>
        <span class="veh-estado-badge" style="background:<?= $bgE ?>;color:<?= $clrE ?>;border:1px solid <?= $clrE ?>44;">
            <?= htmlspecialchars($estado) ?>
        </span>
    </div>
    <div class="veh-body">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <div class="veh-placa"><?= htmlspecialchars($v['placa']) ?></div>
                <div class="veh-nombre"><?= htmlspecialchars(($v['marca']??'').' '.($v['modelo']??'')) ?><?php if(!empty($v['anio'])): ?> · <?= htmlspecialchars($v['anio']) ?><?php endif; ?></div>
            </div>
            <?php if(!empty($v['color'])): ?><div style="font-size:.75rem;color:#94a3b8;"><?= htmlspecialchars($v['color']) ?></div><?php endif; ?>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:.7rem;color:#94a3b8;margin-top:.85rem;margin-bottom:.3rem;"><span>Progreso en taller</span><span style="color:<?= $progClr ?>;"><?= $prog ?>%</span></div>
        <div class="veh-prog-bg"><div class="veh-prog-fill" style="width:<?= $prog ?>%;background:<?= $progClr ?>;"></div></div>
        <?php if($ultimo): ?>
        <div style="margin-top:.8rem;padding:.6rem .85rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;">
            <div style="font-size:.68rem;color:#94a3b8;margin-bottom:.15rem;"><i class="fas fa-clock me-1"></i>Último registro</div>
            <div style="font-size:.8rem;font-weight:600;color:#000000;"><?= htmlspecialchars($ultimo['tipo_reparacion']??'') ?></div>
            <div style="font-size:.7rem;color:#94a3b8;"><?= date('d/m/Y',strtotime($ultimo['fecha_registro'])) ?><?php if(!empty($ultimo['empleado_nombre'])): ?> · <i class="fas fa-user-tie"></i> <?= htmlspecialchars($ultimo['empleado_nombre']) ?><?php endif; ?></div>
        </div>
        <?php else: ?><div style="margin-top:.8rem;font-size:.78rem;color:#94a3b8;"><i class="fas fa-info-circle me-1"></i>Sin registros de trabajo aún.</div><?php endif; ?>
    </div>
    <div class="veh-foot">
        <button class="cs-btn-hist" type="button" data-bs-toggle="collapse" data-bs-target="#hist-<?= $id ?>">
            <i class="fas fa-history"></i> Historial (<?= count($hist) ?>)
        </button>
        <div class="collapse" id="hist-<?= $id ?>">
            <div class="veh-hist-panel">
                <div class="cs-tl-title"><i class="fas fa-history me-1"></i>Historial completo</div>
                <?php if(empty($hist)): ?>
                    <p style="font-size:.82rem;color:#94a3b8;text-align:center;margin:0;">Sin registros.</p>
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
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Modal registrar -->
<div class="modal fade cs-modal" id="modalRegistrar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-car me-2" style="color:#000000;"></i>Registrar mi Vehículo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="../../controllers/VehiculoController.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="accion" value="registrar">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <div id="fotoPreviewWrap" style="display:none;margin-bottom:.5rem;">
                                <img id="fotoPreviewImg" style="width:100%;height:155px;object-fit:cover;border-radius:10px;border:1px solid #e2e8f0;">
                            </div>
                            <label class="form-label">Foto del vehículo <span style="color:#94a3b8;font-weight:400;">(opcional)</span></label>
                            <label id="fotoLabel" style="display:flex;align-items:center;gap:.65rem;background:#f8fafc;border:1px dashed #cbd5e1;border-radius:9px;padding:.65rem 1rem;cursor:pointer;transition:border-color .2s;">
                                <i class="fas fa-camera" style="color:#000000;font-size:1.1rem;"></i>
                                <span id="fotoLabelText" style="font-size:.82rem;color:#94a3b8;">Seleccionar foto (JPG, PNG, WEBP)</span>
                                <input type="file" name="foto" id="fotoInput" accept=".jpg,.jpeg,.png,.webp" style="display:none;" onchange="previewFoto(this)">
                            </label>
                        </div>
                        <div class="col-12"><label class="form-label">Placa *</label><input type="text" name="placa" class="form-control text-uppercase" required placeholder="Ej: ABC-123" maxlength="20"><div class="form-text" style="color:#94a3b8;">Ingresa la placa tal como aparece en el documento.</div></div>
                        <div class="col-sm-6"><label class="form-label">Marca *</label><input type="text" name="marca" class="form-control" required placeholder="Toyota, Chevrolet…"></div>
                        <div class="col-sm-6"><label class="form-label">Modelo *</label><input type="text" name="modelo" class="form-control" required placeholder="Corolla, Spark…"></div>
                        <div class="col-sm-6"><label class="form-label">Año *</label><input type="text" name="anio" class="form-control" required placeholder="<?= date('Y') ?>" maxlength="4" pattern="\d{4}"></div>
                        <div class="col-sm-6"><label class="form-label">Color</label><input type="text" name="color" class="form-control" placeholder="Blanco, Negro…"></div>
                        <div class="col-12"><label class="form-label">Estado inicial</label>
                            <select name="id_estado_vehiculo" class="form-select">
                                <?php foreach($estados as $e): ?><option value="<?= $e['id_estado_vehiculo'] ?>"><?= htmlspecialchars($e['estado']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="cs-btn-dark"><i class="fas fa-save"></i> Registrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if($alert): ?>
<script>Swal.fire({icon:'<?= htmlspecialchars($alert['icon']) ?>',title:'<?= htmlspecialchars($alert['title']) ?>',text:'<?= htmlspecialchars($alert['text']) ?>',confirmButtonColor:'#000000'});</script>
<?php endif; ?>
<script>
function previewFoto(input){
    const wrap=document.getElementById('fotoPreviewWrap'),img=document.getElementById('fotoPreviewImg'),lbl=document.getElementById('fotoLabelText');
    if(input.files&&input.files[0]){const r=new FileReader();r.onload=e=>{img.src=e.target.result;wrap.style.display='block';lbl.textContent=input.files[0].name;};r.readAsDataURL(input.files[0]);}
}
document.getElementById('fotoLabel')?.addEventListener('mouseenter',function(){this.style.borderColor='#000000';});
document.getElementById('fotoLabel')?.addEventListener('mouseleave',function(){this.style.borderColor='#cbd5e1';});
</script>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
