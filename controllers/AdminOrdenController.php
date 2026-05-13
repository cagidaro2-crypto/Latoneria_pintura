<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['usuario'])) {
    header("Location: ../views/usuarios/login.php"); exit;
}

$rol    = (int)($_SESSION['usuario']['rol'] ?? 0); // 1=admin, 2=cliente, 3=empleado
$db     = (new Database())->conectar();
$accion = $_POST['accion'] ?? ($_GET['accion'] ?? '');

// Solo admin (1) y empleado (3)
if ($rol !== 1 && $rol !== 3) {
    $_SESSION['alert'] = ['icon'=>'error','title'=>'Sin permiso','text'=>'No tiene permiso para esta acción.'];
    header("Location: ../views/dashboard/admin_ordenes.php"); exit;
}

switch ($accion) {

    // ── CREAR ORDEN ──────────────────────────────────────────────────────────
    case 'crear':
        $idVehiculo  = (int)($_POST['id_vehiculo']  ?? 0);
        $idCliente   = (int)($_POST['id_cliente']   ?? 0);
        $idEmpleado  = (int)($_POST['id_empleado']  ?? 0) ?: null;
        $tipo        = trim($_POST['tipo_servicio'] ?? '');
        $descripcion = trim($_POST['descripcion']   ?? '');

        if (!$idVehiculo || !$idCliente || empty($tipo)) {
            $_SESSION['alert'] = ['icon'=>'warning','title'=>'Campos incompletos','text'=>'Complete vehículo, cliente y tipo de servicio.'];
            header("Location: ../views/dashboard/admin_ordenes.php"); exit;
        }

        // Verificar que el vehículo existe en la BD
        $stmtV = $db->prepare("SELECT id_vehiculo FROM vehiculo WHERE id_vehiculo = :id LIMIT 1");
        $stmtV->execute([':id' => $idVehiculo]);
        if (!$stmtV->fetch()) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Vehículo no encontrado','text'=>'El vehículo seleccionado no existe.'];
            header("Location: ../views/dashboard/admin_ordenes.php"); exit;
        }

        // Registrar en historial_vehiculo (tabla real de la BD)
        // Primero obtener o crear id_empleado para el responsable
        $idPersonaActual = (int)($_SESSION['usuario']['id_usuario'] ?? 0);
        $idEmpResponsable = $idEmpleado; // si se asignó un empleado, usar ese

        if (!$idEmpResponsable) {
            // Usar el usuario actual como responsable
            $stmtEmp = $db->prepare("SELECT id_empleado FROM empleado WHERE id_persona = :id LIMIT 1");
            $stmtEmp->execute([':id' => $idPersonaActual]);
            $empRow = $stmtEmp->fetch(PDO::FETCH_ASSOC);

            if (!$empRow) {
                // Crear registro en empleado si no existe
                $db->prepare("INSERT INTO empleado (id_persona) VALUES (:id)")
                   ->execute([':id' => $idPersonaActual]);
                $idEmpResponsable = (int)$db->lastInsertId();
            } else {
                $idEmpResponsable = (int)$empRow['id_empleado'];
            }
        } else {
            // Verificar que el empleado asignado tiene registro en tabla empleado
            $stmtEmp2 = $db->prepare("SELECT id_empleado FROM empleado WHERE id_persona = :id LIMIT 1");
            $stmtEmp2->execute([':id' => $idEmpleado]);
            $empRow2 = $stmtEmp2->fetch(PDO::FETCH_ASSOC);

            if (!$empRow2) {
                $db->prepare("INSERT INTO empleado (id_persona) VALUES (:id)")
                   ->execute([':id' => $idEmpleado]);
                $idEmpResponsable = (int)$db->lastInsertId();
            } else {
                $idEmpResponsable = (int)$empRow2['id_empleado'];
            }
        }

        try {
            $db->prepare(
                "INSERT INTO historial_vehiculo
                    (descripcion, fecha_registro, tipo_reparacion, id_empleado, id_vehiculo)
                 VALUES
                    (:desc, CURDATE(), :tipo, :id_emp, :id_veh)"
            )->execute([
                ':desc'   => $descripcion ?: "Orden de servicio: {$tipo}",
                ':tipo'   => $tipo,
                ':id_emp' => $idEmpResponsable,
                ':id_veh' => $idVehiculo,
            ]);

            // Cambiar estado del vehículo a "En reparación" (id 2) si existe
            $stmtEst = $db->query("SELECT id_estado_vehiculo FROM estado_vehiculo WHERE estado LIKE '%reparaci%' LIMIT 1");
            $estRow  = $stmtEst ? $stmtEst->fetch(PDO::FETCH_ASSOC) : null;
            if ($estRow) {
                $db->prepare("UPDATE vehiculo SET id_estado_vehiculo = :est WHERE id_vehiculo = :id")
                   ->execute([':est' => $estRow['id_estado_vehiculo'], ':id' => $idVehiculo]);
            }

            $_SESSION['alert'] = ['icon'=>'success','title'=>'¡Orden creada!','text'=>'Orden de servicio registrada correctamente.'];
        } catch (Exception $e) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>$e->getMessage()];
        }

        header("Location: ../views/dashboard/admin_ordenes.php"); exit;

    // ── ACTUALIZAR ESTADO ────────────────────────────────────────────────────
    case 'actualizar_estado':
        $idOrden     = (int)($_POST['id_orden']    ?? 0);
        $estado      = trim($_POST['estado']       ?? '');
        $observacion = trim($_POST['observacion']  ?? '');

        if (!$idOrden || empty($estado)) {
            $_SESSION['alert'] = ['icon'=>'warning','title'=>'Datos incompletos','text'=>'Seleccione un estado válido.'];
            header("Location: ../views/dashboard/admin_ordenes.php"); exit;
        }

        // id_orden aquí es id_historial_vehiculo
        try {
            // Actualizar descripción con observación si viene
            if ($observacion) {
                $db->prepare(
                    "UPDATE historial_vehiculo SET descripcion = :obs WHERE id_historial_vehiculo = :id"
                )->execute([':obs' => $observacion, ':id' => $idOrden]);
            }

            // Cambiar estado del vehículo asociado
            $stmtH = $db->prepare("SELECT id_vehiculo FROM historial_vehiculo WHERE id_historial_vehiculo = :id LIMIT 1");
            $stmtH->execute([':id' => $idOrden]);
            $hRow = $stmtH->fetch(PDO::FETCH_ASSOC);

            if ($hRow) {
                $stmtEst = $db->prepare("SELECT id_estado_vehiculo FROM estado_vehiculo WHERE estado = :est LIMIT 1");
                $stmtEst->execute([':est' => $estado]);
                $estRow = $stmtEst->fetch(PDO::FETCH_ASSOC);

                if ($estRow) {
                    $db->prepare("UPDATE vehiculo SET id_estado_vehiculo = :est WHERE id_vehiculo = :id")
                       ->execute([':est' => $estRow['id_estado_vehiculo'], ':id' => $hRow['id_vehiculo']]);
                }
            }

            $_SESSION['alert'] = ['icon'=>'success','title'=>'Estado actualizado','text'=>'El estado se actualizó correctamente.'];
        } catch (Exception $e) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>$e->getMessage()];
        }

        header("Location: ../views/dashboard/admin_ordenes.php"); exit;

    default:
        header("Location: ../views/dashboard/admin_ordenes.php"); exit;
}
?>
