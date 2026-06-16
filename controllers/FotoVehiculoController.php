<?php
/**
 * FotoVehiculoController.php
 * Maneja subida y eliminación de fotos de vehículos.
 */
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['usuario'])) {
    header("Location: ../views/usuarios/login.php"); exit;
}

$rol    = (int)($_SESSION['usuario']['rol'] ?? 0);
$accion = $_POST['accion'] ?? ($_GET['accion'] ?? '');

// Solo admin (1) y empleado (3)
if ($rol !== 1 && $rol !== 3) {
    $_SESSION['alert'] = ['icon'=>'error','title'=>'Sin permiso','text'=>'No tiene permiso para esta acción.'];
    header("Location: ../views/dashboard/admin_vehiculos.php"); exit;
}

$db      = (new Database())->conectar();
$destino = $rol === 1
    ? '../views/dashboard/admin_vehiculos.php'
    : '../views/dashboard/empleado_vehiculos.php';

switch ($accion) {

    // ── SUBIR FOTO ───────────────────────────────────────────────────────
    case 'subir':
        $idVehiculo  = (int)($_POST['id_vehiculo'] ?? 0);
        $etapa       = $_POST['etapa']       ?? 'antes';
        $descripcion = trim($_POST['descripcion'] ?? '');
        $idUsuario   = (int)($_SESSION['usuario']['id_usuario'] ?? 0);

        if (!$idVehiculo || empty($_FILES['fotos']['name'][0])) {
            $_SESSION['alert'] = ['icon'=>'warning','title'=>'Sin archivos','text'=>'Selecciona al menos una imagen.'];
            header("Location: {$destino}"); exit;
        }

        $etapasValidas = ['antes', 'durante', 'despues'];
        if (!in_array($etapa, $etapasValidas)) $etapa = 'antes';

        $uploadDir  = __DIR__ . '/../public/uploads/vehiculos/';
        $permitidos = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $subidas    = 0;
        $errores    = [];

        $archivos = $_FILES['fotos'];
        $total    = count($archivos['name']);

        for ($i = 0; $i < $total; $i++) {
            if ($archivos['error'][$i] !== UPLOAD_ERR_OK) continue;

            $mime = mime_content_type($archivos['tmp_name'][$i]);
            if (!in_array($mime, $permitidos)) {
                $errores[] = $archivos['name'][$i] . ': tipo no permitido';
                continue;
            }

            if ($archivos['size'][$i] > 5 * 1024 * 1024) {
                $errores[] = $archivos['name'][$i] . ': supera 5 MB';
                continue;
            }

            $ext          = pathinfo($archivos['name'][$i], PATHINFO_EXTENSION);
            $nombreUnico  = 'veh_' . $idVehiculo . '_' . uniqid() . '.' . strtolower($ext);
            $rutaDestino  = $uploadDir . $nombreUnico;

            if (move_uploaded_file($archivos['tmp_name'][$i], $rutaDestino)) {
                $db->prepare(
                    "INSERT INTO vehiculo_fotos
                        (id_vehiculo, id_usuario, nombre_archivo, etapa, descripcion, fecha_subida)
                     VALUES (:idv, :id_usuario, :nombre, :etapa, :desc, NOW())"
                )->execute([
                    ':idv'       => $idVehiculo,
                    ':id_usuario'=> $idUsuario,
                    ':nombre'    => $nombreUnico,
                    ':etapa'     => $etapa,
                    ':desc'      => $descripcion ?: null,
                ]);
                $subidas++;
            }
        }

        if ($subidas > 0) {
            $msg = "{$subidas} foto(s) subida(s) correctamente.";
            if ($errores) $msg .= ' Errores: ' . implode(', ', $errores);
            $_SESSION['alert'] = ['icon'=>'success','title'=>'Fotos subidas','text'=>$msg];
        } else {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>'No se pudo subir ninguna foto. ' . implode(', ', $errores)];
        }
        header("Location: {$destino}"); exit;

    // ── ELIMINAR FOTO ────────────────────────────────────────────────────
    case 'eliminar':
        $idFoto = (int)($_GET['id'] ?? 0);
        if (!$idFoto) {
            header("Location: {$destino}"); exit;
        }

        $stmt = $db->prepare("SELECT nombre_archivo FROM vehiculo_fotos WHERE id_foto = :id LIMIT 1");
        $stmt->execute([':id' => $idFoto]);
        $foto = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($foto) {
            $ruta = __DIR__ . '/../public/uploads/vehiculos/' . $foto['nombre_archivo'];
            if (file_exists($ruta)) unlink($ruta);
            $db->prepare("DELETE FROM vehiculo_fotos WHERE id_foto = :id")->execute([':id' => $idFoto]);
            $_SESSION['alert'] = ['icon'=>'success','title'=>'Eliminada','text'=>'Foto eliminada correctamente.'];
        }
        header("Location: {$destino}"); exit;

    default:
        header("Location: {$destino}"); exit;
}
?>
