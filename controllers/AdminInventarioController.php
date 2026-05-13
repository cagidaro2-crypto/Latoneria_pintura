<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['usuario']) || (int)($_SESSION['usuario']['rol'] ?? 0) !== 1) {
    header("Location: ../views/usuarios/login.php"); exit;
}

$db     = (new Database())->conectar();
$accion = $_POST['accion'] ?? '';

switch ($accion) {

    // ── AGREGAR PRODUCTO ─────────────────────────────────────────────────────
    case 'agregar':
        $nombre      = trim($_POST['nombre']       ?? '');
        $descripcion = trim($_POST['descripcion']  ?? '');
        $categoria   = trim($_POST['categoria']    ?? '');
        $precio      = (float)($_POST['precio']    ?? 0);
        $cantidad    = (float)($_POST['cantidad']  ?? 0);
        $unidad      = trim($_POST['unidad']       ?? 'unidad');
        $stockMin    = (float)($_POST['stock_minimo'] ?? 5);

        if (empty($nombre) || $precio < 0) {
            $_SESSION['alert'] = ['icon'=>'warning','title'=>'Datos inválidos','text'=>'Nombre y precio son obligatorios.'];
            header("Location: ../views/dashboard/admin_inventario.php"); exit;
        }

        // Verificar duplicado en productos
        $stmt = $db->prepare("SELECT id_producto FROM productos WHERE nombre = :nombre LIMIT 1");
        $stmt->execute([':nombre' => $nombre]);
        if ($stmt->rowCount() > 0) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Duplicado','text'=>'Ya existe un producto con ese nombre.'];
            header("Location: ../views/dashboard/admin_inventario.php"); exit;
        }

        try {
            $db->beginTransaction();

            $db->prepare(
                "INSERT INTO productos (nombre, descripcion, precio, categoria, activo, created_at)
                 VALUES (:nombre, :descripcion, :precio, :categoria, 1, NOW())"
            )->execute([
                ':nombre'      => $nombre,
                ':descripcion' => $descripcion,
                ':precio'      => $precio,
                ':categoria'   => $categoria,
            ]);
            $idProducto = (int)$db->lastInsertId();

            $db->prepare(
                "INSERT INTO inventario (id_producto, cantidad, unidad, stock_minimo)
                 VALUES (:id, :cantidad, :unidad, :stock_min)"
            )->execute([
                ':id'        => $idProducto,
                ':cantidad'  => $cantidad,
                ':unidad'    => $unidad,
                ':stock_min' => $stockMin,
            ]);

            $db->commit();
            $_SESSION['alert'] = ['icon'=>'success','title'=>'Agregado','text'=>'Producto registrado correctamente.'];
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>$e->getMessage()];
        }
        header("Location: ../views/dashboard/admin_inventario.php"); exit;

    // ── ACTUALIZAR STOCK ─────────────────────────────────────────────────────
    case 'actualizar':
        $idInv    = (int)($_POST['id_inventario'] ?? 0);
        $cantidad = (float)($_POST['cantidad']    ?? 0);
        $stockMin = (float)($_POST['stock_minimo']?? 5);

        if ($cantidad < 0) {
            $_SESSION['alert'] = ['icon'=>'warning','title'=>'Cantidad inválida','text'=>'La cantidad no puede ser negativa.'];
            header("Location: ../views/dashboard/admin_inventario.php"); exit;
        }

        try {
            $db->prepare(
                "UPDATE inventario
                 SET cantidad=:cantidad, stock_minimo=:stock
                 WHERE id_inventario=:id"
            )->execute([':cantidad' => $cantidad, ':stock' => $stockMin, ':id' => $idInv]);
            $_SESSION['alert'] = ['icon'=>'success','title'=>'Actualizado','text'=>'Stock actualizado correctamente.'];
        } catch (Exception $e) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>$e->getMessage()];
        }
        header("Location: ../views/dashboard/admin_inventario.php"); exit;

    default:
        header("Location: ../views/dashboard/admin_inventario.php"); exit;
}
?>
