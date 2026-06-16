<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['usuario']) || (int)($_SESSION['usuario']['rol'] ?? 0) !== 1) {
    header("Location: ../views/usuarios/login.php"); exit;
}

$db     = (new Database())->conectar();
$accion = $_POST['accion'] ?? ($_GET['accion'] ?? '');
$ruta   = '../views/dashboard/admin_inventario.php';

switch ($accion) {

    // ── NUEVA CATEGORÍA ──────────────────────────────────────────────────────
    case 'nueva_categoria':
        $nombre = trim($_POST['nombre_categoria']      ?? '');
        $desc   = trim($_POST['descripcion_categoria'] ?? '');

        if (empty($nombre)) {
            $_SESSION['alert'] = ['icon'=>'warning','title'=>'Nombre requerido','text'=>'Ingresa el nombre de la categoría.'];
            header("Location: {$ruta}"); exit;
        }
        try {
            $db->prepare("INSERT INTO categorias_productos (nombre, descripcion) VALUES (:n, :d)")
               ->execute([':n' => $nombre, ':d' => $desc]);
            $_SESSION['alert'] = ['icon'=>'success','title'=>'Categoría creada','text'=>"Categoría '{$nombre}' agregada correctamente."];
        } catch (Exception $e) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>$e->getMessage()];
        }
        header("Location: {$ruta}"); exit;

    // ── AGREGAR PRODUCTO + INVENTARIO ────────────────────────────────────────
    case 'agregar':
        $nombre      = trim($_POST['nombre']        ?? '');
        $descripcion = trim($_POST['descripcion']   ?? '');
        $idCat       = (int)($_POST['id_categoria'] ?? 0) ?: null;
        $idProv      = (int)($_POST['id_proveedor'] ?? 0) ?: null;
        $precio      = (float)($_POST['precio']     ?? 0);
        $cantidad    = (float)($_POST['cantidad']   ?? 0);
        $unidad      = trim($_POST['unidad']        ?? 'unidad');
        $stockMin    = (float)($_POST['stock_minimo'] ?? 5);

        if (empty($nombre)) {
            $_SESSION['alert'] = ['icon'=>'warning','title'=>'Nombre requerido','text'=>'El nombre del producto es obligatorio.'];
            header("Location: {$ruta}"); exit;
        }

        // Verificar duplicado
        $stmtChk = $db->prepare("SELECT id_producto FROM productos WHERE nombre = :n LIMIT 1");
        $stmtChk->execute([':n' => $nombre]);
        if ($stmtChk->rowCount() > 0) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Duplicado','text'=>'Ya existe un producto con ese nombre.'];
            header("Location: {$ruta}"); exit;
        }

        try {
            $db->beginTransaction();

            // Insertar en productos (schema nuevo: id_categoria, id_proveedor)
            $db->prepare(
                "INSERT INTO productos (id_categoria, id_proveedor, nombre, descripcion, precio, activo)
                 VALUES (:cat, :prov, :nombre, :desc, :precio, 1)"
            )->execute([
                ':cat'    => $idCat,
                ':prov'   => $idProv,
                ':nombre' => $nombre,
                ':desc'   => $descripcion,
                ':precio' => $precio,
            ]);
            $idProducto = (int)$db->lastInsertId();

            // Insertar en inventario
            $db->prepare(
                "INSERT INTO inventario (id_producto, cantidad, unidad, stock_minimo)
                 VALUES (:id, :cant, :unidad, :min)"
            )->execute([
                ':id'    => $idProducto,
                ':cant'  => $cantidad,
                ':unidad'=> $unidad,
                ':min'   => $stockMin,
            ]);

            $db->commit();
            $_SESSION['alert'] = ['icon'=>'success','title'=>'Producto agregado','text'=>"'{$nombre}' registrado correctamente."];
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>$e->getMessage()];
        }
        header("Location: {$ruta}"); exit;

    // ── ACTUALIZAR STOCK / PRECIO ────────────────────────────────────────────
    case 'actualizar':
        $idInv    = (int)($_POST['id_inventario'] ?? 0);
        $cantidad = (float)($_POST['cantidad']    ?? 0);
        $stockMin = (float)($_POST['stock_minimo']?? 5);
        $precio   = (float)($_POST['precio']      ?? 0);

        if ($idInv <= 0) {
            $_SESSION['alert'] = ['icon'=>'warning','title'=>'ID inválido','text'=>'No se encontró el registro.'];
            header("Location: {$ruta}"); exit;
        }

        try {
            // Actualizar inventario
            $db->prepare(
                "UPDATE inventario SET cantidad=:cant, stock_minimo=:min WHERE id_inventario=:id"
            )->execute([':cant' => $cantidad, ':min' => $stockMin, ':id' => $idInv]);

            // Actualizar precio en productos
            if ($precio > 0) {
                $db->prepare(
                    "UPDATE productos p
                     JOIN inventario i ON p.id_producto = i.id_producto
                     SET p.precio = :precio
                     WHERE i.id_inventario = :id"
                )->execute([':precio' => $precio, ':id' => $idInv]);
            }

            $_SESSION['alert'] = ['icon'=>'success','title'=>'Actualizado','text'=>'Stock actualizado correctamente.'];
        } catch (Exception $e) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>$e->getMessage()];
        }
        header("Location: {$ruta}"); exit;

    default:
        header("Location: {$ruta}"); exit;
}
?>
