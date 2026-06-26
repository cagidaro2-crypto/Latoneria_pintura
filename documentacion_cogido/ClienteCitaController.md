# Documentación: ClienteCitaController.php

## Descripción general
Controlador para que los clientes gestionen sus citas en el taller. Permite agendar nuevas citas (validando disponibilidad de horario y que la fecha no sea pasada) y cancelar citas existentes (solo si están en estado Pendiente o Confirmada). Genera automáticamente un número de referencia único con formato `CIT-00001`.

## Dependencias
- `config/database.php` — Conexión PDO a MySQL

## Código documentado línea por línea

```php
// Línea 1: Apertura del bloque PHP
<?php

// Línea 2: Inicia la sesión
session_start();

// Línea 3: Incluye la conexión a la base de datos
require_once __DIR__ . '/../config/database.php';

// Líneas 5-7: Guard de autenticación — redirige al login si no hay sesión activa
if (!isset($_SESSION['usuario'])) {
    header("Location: ../views/usuarios/login.php"); exit;
}

// Líneas 9-11: Prepara variables base: conexión, ID del usuario y destino de redirección
$db        = (new Database())->conectar();
$idUsuario = (int)($_SESSION['usuario']['id_usuario'] ?? 0);
$accion    = $_POST['accion'] ?? ($_GET['accion'] ?? '');
$destino   = '../views/dashboard/cliente_citas.php';

// Líneas 13-17: Función auxiliar que genera el siguiente número de referencia
// Lee el MAX(id_cita) actual y devuelve "CIT-" seguido del número siguiente con 5 dígitos
function generarRef(PDO $db): string {
    $stmt  = $db->query("SELECT COALESCE(MAX(id_cita), 0) FROM citas");
    $ultimo = (int)$stmt->fetchColumn();
    return 'CIT-' . str_pad($ultimo + 1, 5, '0', STR_PAD_LEFT);
}

// Línea 19: Inicio del switch de acciones
switch ($accion) {

    // ── CASO: AGENDAR ────────────────────────────────────────────────────

    // Línea 22: Acción para crear una nueva cita
    case 'agendar':
        // Líneas 23-26: Obtiene y limpia los campos del formulario
        $idVehiculo   = (int)trim($_POST['id_vehiculo']   ?? '');
        $tipoServicio = trim($_POST['tipo_servicio']      ?? '');
        $fechaCita    = trim($_POST['fecha_cita']         ?? '');
        $notas        = trim($_POST['notas']              ?? '');

        // Líneas 28-33: Valida que los campos obligatorios estén completos
        if (!$idVehiculo || !$tipoServicio || !$fechaCita) {
            $_SESSION['alert'] = [
                'icon'  => 'warning',
                'title' => 'Campos incompletos',
                'text'  => 'Completa todos los campos obligatorios.',
            ];
            header("Location: {$destino}"); exit;
        }

        // Líneas 35-41: Valida que la fecha de la cita sea futura (no pasada)
        // strtotime convierte la fecha a timestamp UNIX para comparar con time()
        if (strtotime($fechaCita) < time()) {
            $_SESSION['alert'] = [
                'icon'  => 'warning',
                'title' => 'Fecha inválida',
                'text'  => 'No puedes agendar una cita en una fecha pasada.',
            ];
            header("Location: {$destino}"); exit;
        }

        // Líneas 43-52: Verifica disponibilidad del horario exacto
        // Busca si ya existe una cita NO cancelada para la misma fecha/hora
        $stmtCheck = $db->prepare(
            "SELECT id_cita FROM citas
             WHERE fecha_cita = :fecha AND estado NOT IN ('Cancelada') LIMIT 1"
        );
        $stmtCheck->execute([':fecha' => $fechaCita]);
        if ($stmtCheck->rowCount() > 0) {
            $_SESSION['alert'] = [
                'icon'  => 'error',
                'title' => 'Horario no disponible',
                'text'  => 'El horario seleccionado ya está ocupado. Por favor elige otro.',
            ];
            header("Location: {$destino}"); exit;
        }

        // Líneas 54-68: Inserta la cita en la tabla con estado inicial 'Pendiente'
        // Genera el número de referencia único antes de insertar
        try {
            $ref = generarRef($db);
            $db->prepare(
                "INSERT INTO citas
                    (numero_ref, id_cliente, id_vehiculo, tipo_servicio, fecha_cita, estado, created_at)
                 VALUES
                    (:ref, :id_cliente, :id_vehiculo, :tipo_servicio, :fecha_cita, 'Pendiente', NOW())"
            )->execute([
                ':ref'          => $ref,
                ':id_cliente'   => $idUsuario,   // Usa el ID del usuario como id_cliente
                ':id_vehiculo'  => $idVehiculo,
                ':tipo_servicio'=> $tipoServicio,
                ':fecha_cita'   => $fechaCita,
            ]);

            // Formatea la fecha para mostrarla amigablemente en el mensaje de éxito
            $fechaFmt = date('d/m/Y', strtotime($fechaCita));
            $hora     = date('H:i',   strtotime($fechaCita));
            $_SESSION['alert'] = [
                'icon'  => 'success',
                'title' => '¡Cita agendada!',
                'text'  => "Tu cita para el {$fechaFmt} a las {$hora} fue registrada. Ref: {$ref}.",
            ];
        } catch (Exception $e) {
            $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Error al agendar', 'text' => $e->getMessage()];
        }
        header("Location: {$destino}"); exit;

    // ── CASO: CANCELAR ───────────────────────────────────────────────────

    // Línea 72: Acción para cancelar una cita existente
    case 'cancelar':
        // Línea 73: Obtiene el ID de la cita desde POST o GET
        $idCita = (int)($_POST['id_cita'] ?? $_GET['id'] ?? 0);

        // Líneas 75-77: Valida que el ID sea válido
        if (!$idCita) {
            $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Error', 'text' => 'ID de cita no válido.'];
            header("Location: {$destino}"); exit;
        }

        // Líneas 79-95: Actualiza el estado a 'Cancelada' con doble seguridad:
        // 1) Verifica que la cita pertenece al cliente de sesión (id_cliente = $idUsuario)
        // 2) Solo cancela si está en estado 'Pendiente' o 'Confirmada'
        try {
            $stmt = $db->prepare(
                "UPDATE citas SET estado = 'Cancelada'
                 WHERE id_cita = :id AND id_cliente = :cliente
                   AND estado IN ('Pendiente', 'Confirmada')"
            );
            $stmt->execute([':id' => $idCita, ':cliente' => $idUsuario]);

            // rowCount() > 0 indica que sí se modificó alguna fila
            if ($stmt->rowCount() > 0) {
                $_SESSION['alert'] = ['icon' => 'success', 'title' => 'Cita cancelada',...];
            } else {
                // La cita no se encontró o ya estaba cancelada/realizada
                $_SESSION['alert'] = ['icon' => 'warning', 'title' => 'Sin cambios',...];
            }
        } catch (Exception $e) {
            $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Error', 'text' => $e->getMessage()];
        }
        header("Location: {$destino}"); exit;

    // Línea 99: Por defecto redirige a la vista de citas del cliente
    default:
        header("Location: {$destino}"); exit;
}
?>
```

## Resumen de funciones/métodos

| Función/Acción | Parámetros | Retorno | Descripción |
|---------------|-----------|---------|-------------|
| `generarRef(PDO $db)` | PDO connection | `string` | Genera número de referencia único en formato `CIT-00001` |
| `agendar` (case) | `$_POST` | void | Valida y crea una nueva cita con estado Pendiente |
| `cancelar` (case) | `$_POST` o `$_GET` | void | Cancela una cita del cliente si está en estado válido |

## Flujo de ejecución
1. Inicia sesión y verifica que el usuario esté autenticado.
2. Obtiene la conexión, el ID del usuario activo y la acción solicitada.
3. **Agendar**:
   - Valida campos obligatorios (vehículo, tipo, fecha).
   - Verifica que la fecha sea futura.
   - Consulta disponibilidad del horario (no debe haber otra cita no cancelada).
   - Genera el número de referencia incremental.
   - Inserta la cita en estado 'Pendiente'.
   - Muestra confirmación con fecha formateada y número de referencia.
4. **Cancelar**:
   - Obtiene el ID de la cita.
   - Ejecuta UPDATE con cláusula `AND id_cliente = $idUsuario` para seguridad.
   - Solo cancela si la cita está en 'Pendiente' o 'Confirmada'.
   - Informa si hubo cambio o no.
5. En todos los casos guarda alerta en sesión y redirige a `cliente_citas.php`.
