# Documentación: AdminInventarioController.php

## Descripción general
Controlador para la gestión del inventario de productos y repuestos del taller. Permite crear categorías, agregar productos con su entrada de inventario, actualizar stock y precio, y descontar stock cuando se usa un material. Accesible tanto para administradores (rol 1) como para empleados (rol 2), con redirección diferenciada según el rol activo.

## Dependencias
- `config/database.php` — Conexión PDO a MySQL

## Código documentado línea por línea

```php
// Línea 1: Apertura del bloque PHP
<?php

// Línea 2: Inicia la sesión para leer $_SESSION
session_start();

// Línea 3: Incluye la conexión a la base de datos
require_once __DIR__ . '/../config/database.php';

// Líneas 5-7: Guard de acceso — permite solo roles 1 (admin) y 2 (empleado)
if (!isset($_SESSION['usuario']) || !in_array((int)($_SESSION['usuario']['rol'] ?? 0), [1, 2])) {
    header("Location: ../views/usuarios/login.php"); exit;
}

// Línea 9: Crea la conexión a la BD
$db     = (new Database())->conectar();

// Línea 10: Obtiene la acción a ejecutar (POST tiene prioridad sobre GET)
$accion = $_POST['accion'] ?? ($_GET['accion'] ?? '');

// Línea 11: Define la ruta de redirección por defecto (para admin)
$ruta   = '../views/dashboard/admin_inventario.php';

// Líneas 13-15: Si el usuario es empleado (rol 2), cambia la ruta de redirección
$rolActual = (int)($_SESSION['usuario']['rol'] ?? 0);
if ($rolActual === 2) {
    $ruta = '../views/dashboard/empleado_inventario.php';
}

// Línea 17: Inicio del switch de acciones
switch ($accion) {

    // ── CASO: NUEVA CATEGORÍA ────────────────────────────────────────────

    // Línea 20: Crea una nueva categoría de productos en la tabla categorias_productos
    case 'nueva_categoria':
        $nombre = trim($_POST['nombre_categoria']      ?? '');
        $desc   = trim($_POST['descripcion_categoria'] ?? '');

        // Líneas 23-26: Valida que el nombre no esté vacío
        if (empty($nombre)) {
            $_SESSION['alert'] = ['icon'=>'warning','title'=>'Nombre requerido',...];
            header("Location: {$ruta}"); exit;
        }

        // Líneas 27-31: Inserta la categoría en la BD
        try {
            $db->prepare("INSERT INTO categorias_productos (nombre, descripcion) VALUES (:n, :d)")
               ->execute([':n' => $nombre, ':d' => $desc]);
            $_SESSION['alert'] = ['icon'=>'success','title'=>'Categoría creada',...];
        } catch (Exception $e) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>$e->getMessage()];
        }
        header("Location: {$ruta}"); exit;

    // ── CASO: AGREGAR PRODUCTO ───────────────────────────────────────────

    // Línea 35: Crea un nuevo producto y su entrada de inventario en una transacción
    case 'agregar':
        // Líneas 36-43: Obtiene todos los campos del formulario de agregar producto
        $nombre      = trim($_POST['nombre']        ?? '');
        $descripcion = trim($_POST['descripcion']   ?? '');
        $idCat       = (int)($_POST['id_categoria'] ?? 0) ?: null;
        $idProv      = (int)($_POST['id_proveedor'] ?? 0) ?: null;
        $precio      = (float)($_POST['precio']     ?? 0);
        $cantidad    = (float)($_POST['cantidad']   ?? 0);
        $unidad      = trim($_POST['unidad']        ?? 'unidad');
        $stockMin    = (float)($_POST['stock_minimo'] ?? 5);

        // Líneas 45-48: Valida que el nombre esté presente
        if (empty($nombre)) {
            $_SESSION['alert'] = ['icon'=>'warning','title'=>'Nombre requerido',...];
            header("Location: {$ruta}"); exit;
        }

        // Líneas 50-54: Verifica que no exista otro producto con el mismo nombre
        $stmtChk = $db->prepare("SELECT id_producto FROM productos WHERE nombre = :n LIMIT 1");
        $stmtChk->execute([':n' => $nombre]);
        if ($stmtChk->rowCount() > 0) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Duplicado',...];
            header("Location: {$ruta}"); exit;
        }

        // Líneas 56-73: Transacción que inserta el producto y su inventario atómicamente
        try {
            $db->beginTransaction();

            // Inserta en tabla productos (con categoría, proveedor, precio)
            $db->prepare("INSERT INTO productos (id_categoria, id_proveedor, nombre, descripcion, precio, activo) VALUES ...")->execute([...]);
            $idProducto = (int)$db->lastInsertId();

            // Inserta en tabla inventario vinculado al producto recién creado
            $db->prepare("INSERT INTO inventario (id_producto, cantidad, unidad, stock_minimo) VALUES ...")->execute([...]);

            $db->commit();
            $_SESSION['alert'] = ['icon'=>'success','title'=>'Producto agregado',...];
        } catch (Exception $e) {
            // Línea 71: Si algo falla, revierte toda la transacción
            $db->rollBack();
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>$e->getMessage()];
        }
        header("Location: {$ruta}"); exit;

    // ── CASO: ACTUALIZAR STOCK/PRECIO ────────────────────────────────────

    // Línea 77: Actualiza cantidad, stock mínimo y precio de un producto existente
    case 'actualizar':
        $idInv    = (int)($_POST['id_inventario'] ?? 0);
        $cantidad = (float)($_POST['cantidad']    ?? 0);
        $stockMin = (float)($_POST['stock_minimo']?? 5);
        $precio   = (float)($_POST['precio']      ?? 0);

        // Líneas 82-85: Valida el ID del registro de inventario
        if ($idInv <= 0) {
            $_SESSION['alert'] = ['icon'=>'warning','title'=>'ID inválido',...];
            header("Location: {$ruta}"); exit;
        }

        // Líneas 87-96: Actualiza el inventario y, si se proporcionó precio > 0, también el producto
        try {
            $db->prepare("UPDATE inventario SET cantidad=:cant, stock_minimo=:min WHERE id_inventario=:id")
               ->execute([...]);

            if ($precio > 0) {
                // JOIN entre inventario y productos para actualizar precio
                $db->prepare("UPDATE productos p JOIN inventario i ON p.id_producto = i.id_producto SET p.precio=:precio WHERE i.id_inventario=:id")
                   ->execute([...]);
            }
            $_SESSION['alert'] = ['icon'=>'success','title'=>'Actualizado',...];
        } catch (Exception $e) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>$e->getMessage()];
        }
        header("Location: {$ruta}"); exit;

    // ── CASO: DESCONTAR STOCK ────────────────────────────────────────────

    // Línea 100: Descuenta unidades de un producto (usado por empleados y admin)
    case 'descontar':
        $idInv    = (int)($_POST['id_inventario']       ?? 0);
        $cantidad = (float)($_POST['cantidad_descontar'] ?? 0);
        $motivo   = trim($_POST['motivo']               ?? '');

        // Líneas 104-107: Valida que los valores sean positivos
        if ($idInv <= 0 || $cantidad <= 0) {
            $_SESSION['alert'] = ['icon'=>'warning','title'=>'Datos inválidos',...];
            header("Location: {$ruta}"); exit;
        }

        // Líneas 109-123: Verifica que hay suficiente stock antes de descontar
        try {
            $stmtCk = $db->prepare("SELECT cantidad FROM inventario WHERE id_inventario=:id");
            $stmtCk->execute([':id' => $idInv]);
            $stockActual = (float)($stmtCk->fetchColumn() ?: 0);

            // Si la cantidad a descontar supera el stock, muestra error específico
            if ($cantidad > $stockActual) {
                $_SESSION['alert'] = ['icon'=>'error','title'=>'Stock insuficiente',
                    'text'=>"Solo hay {$stockActual} unidades disponibles."];
                header("Location: {$ruta}"); exit;
            }

            // Descuenta restando la cantidad al stock actual
            $db->prepare("UPDATE inventario SET cantidad = cantidad - :cant WHERE id_inventario = :id")
               ->execute([':cant' => $cantidad, ':id' => $idInv]);

            $_SESSION['alert'] = ['icon'=>'success','title'=>'Stock descontado',...];
        } catch (Exception $e) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>$e->getMessage()];
        }
        header("Location: {$ruta}"); exit;

    // Línea 127: Caso por defecto — redirige a la vista correspondiente al rol
    default:
        header("Location: {$ruta}"); exit;
}
?>
```

## Resumen de funciones/métodos

| Acción | Fuente | Tablas afectadas | Descripción |
|--------|--------|-----------------|-------------|
| `nueva_categoria` | `$_POST` | `categorias_productos` | Crea una categoría de productos |
| `agregar` | `$_POST` | `productos` + `inventario` | Crea producto y su entrada de inventario (transacción) |
| `actualizar` | `$_POST` | `inventario` + `productos` | Actualiza stock, mínimo y precio |
| `descontar` | `$_POST` | `inventario` | Descuenta stock con validación previa de disponibilidad |

## Flujo de ejecución
1. Inicia sesión y verifica que el rol sea 1 (admin) o 2 (empleado).
2. Determina la ruta de redirección según el rol activo.
3. Conecta a la BD y obtiene la acción solicitada.
4. El `switch` ejecuta la operación correspondiente:
   - **nueva_categoria**: valida nombre → inserta categoría.
   - **agregar**: valida nombre → verifica duplicado → transacción (INSERT productos + INSERT inventario).
   - **actualizar**: valida ID → UPDATE inventario → UPDATE precio si aplica.
   - **descontar**: valida cantidad → verifica stock disponible → UPDATE restando la cantidad.
5. Guarda alerta en sesión y redirige a la vista del módulo de inventario.
