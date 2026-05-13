# Documentación Backend — TallerPro
## Sistema de Gestión de Taller de Latonería y Pintura

**Stack:** PHP 8.1+ · MySQL 8.0 · PDO · Bootstrap 5.3  
**Arquitectura:** MVC manual sin framework

---

## Estructura del Proyecto

```
systemTaller/
├── config/
│   └── database.php          # Conexión PDO a MySQL
├── controllers/              # Lógica de negocio y redirecciones
├── models/                   # Acceso a datos (PDO)
├── views/
│   ├── dashboard/            # Vistas por rol
│   ├── layouts/              # header.php + footer.php
│   └── usuarios/             # login, registro, recuperar
├── public/
│   ├── css/dashboard.css
│   └── index.php
├── database/
│   ├── latoneria_pintura.sql          # Esquema original
│   ├── latoneria_pintura_completo.sql # Esquema completo
│   └── migracion_segura.sql          # Migración sin pérdida de datos
└── documentacion/
```

---

## Módulo 1: Autenticación

### `controllers/AuthController.php`
| Acción | Método | Descripción |
|---|---|---|
| `login` | POST | Verifica credenciales, bloquea tras 5 intentos, crea sesión |
| `registro` | POST | Registra cliente en tabla `persona` |
| `recuperar` | POST | Actualiza contraseña por correo o teléfono |
| `logout` | GET | Destruye sesión y redirige a login |

**Sesión creada:**
```php
$_SESSION['usuario'] = [
    'id_usuario' => $persona->id_persona,
    'nombres'    => $persona->nombre,
    'correo'     => $persona->correo,
    'rol'        => $persona->id_rol,  // INT: 1=admin, 2=cliente, 3=empleado
];
```

### `models/Usuario.php`
| Método | Descripción |
|---|---|
| `obtenerPorEmail($correo)` | Busca usuario por correo para login |
| `registrar($datos)` | INSERT en tabla persona |
| `actualizarPassword($valor, $hash, $metodo)` | UPDATE por correo o teléfono |
| `registrarIntentoFallido($id)` | Incrementa contador, bloquea a los 5 |
| `resetearIntentos($id)` | Limpia intentos tras login exitoso |
| `obtenerTodos($rol)` | Lista usuarios filtrados por rol |
| `contarPorRol($rolNombre)` | Cuenta usuarios por rol |

---

## Módulo 2: Vehículos

### `controllers/VehiculoController.php`
Controlador unificado para los 3 roles.

| Acción | Roles | Descripción |
|---|---|---|
| `registrar` | 1, 2 | Crea vehículo. Si cliente no existe en tabla `cliente`, lo crea automáticamente |
| `actualizar` | 1, 3 | Edita marca, modelo, año, estado |
| `cambiar_estado` | 1, 3 | UPDATE `id_estado_vehiculo` |
| `agregar_historial` | 1, 3 | INSERT en `historial_vehiculo`. Crea registro en `empleado` si no existe |

**Flujo registro cliente:**
```
persona.correo → buscar en cliente.correo
  ├── Existe → usar id_cliente
  └── No existe → INSERT en cliente → usar nuevo id_cliente
```

### `models/Vehiculo.php`
| Método | Descripción |
|---|---|
| `registrar($datos)` | INSERT en vehiculo |
| `obtenerTodos()` | JOIN vehiculo+cliente+estado_vehiculo |
| `obtenerPorCliente($id)` | Vehículos de un cliente |
| `obtenerHistorial($id)` | JOIN historial_vehiculo+empleado+persona |
| `agregarHistorial($datos)` | INSERT en historial_vehiculo |
| `cambiarEstado($id, $idEstado)` | UPDATE estado |
| `obtenerEstados()` | SELECT estado_vehiculo |
| `buscarIdClientePorCorreo($correo)` | Busca en tabla cliente |
| `crearClienteDesdePersona($datos)` | INSERT en tabla cliente |

---

## Módulo 3: Órdenes de Trabajo

### `controllers/AdminOrdenController.php`
| Acción | Descripción |
|---|---|
| `crear` | INSERT en `historial_vehiculo` + cambia estado vehículo a "En reparación" |
| `actualizar_estado` | UPDATE estado del vehículo asociado |

> **Nota:** La BD no tiene tabla `ordenes_servicio`. Las órdenes se registran en `historial_vehiculo`.

---

## Módulo 4: Cotizaciones

### `controllers/AdminCotizacionController.php`
| Acción | Descripción |
|---|---|
| `crear` | INSERT en `cotizacion` + `detalle_servicio` (transacción) |
| `aprobar` | INSERT en `factura` con total de la cotización |
| `rechazar` | Marca cotización como rechazada |

---

## Módulo 5: Facturación

### `controllers/FacturaController.php`
| Acción | Descripción |
|---|---|
| `marcar_pagada` | UPDATE `factura.estado_pago = 'Pagada'` |
| `pdf` | Genera HTML de factura (placeholder para TCPDF/Dompdf) |

**Columna `estado_pago`:** Requiere ejecutar `migracion_segura.sql` para agregarla.

---

## Módulo 6: Inventario

### `controllers/AdminInventarioController.php`
| Acción | Descripción |
|---|---|
| `agregar` | INSERT en `productos` + INSERT en `inventario` (transacción) |
| `actualizar` | UPDATE `inventario.cantidad` y `stock_minimo` |

**Estructura nueva de inventario:**
```sql
inventario (id_inventario, id_producto FK, cantidad, unidad, stock_minimo, updated_at)
productos  (id_producto, nombre, descripcion, precio, categoria, activo, created_at)
```

---

## Módulo 7: Citas

### `controllers/ClienteCitaController.php`
| Acción | Descripción |
|---|---|
| `agendar` | Valida fecha futura, verifica disponibilidad, INSERT en `citas` con ref CIT-XXXXX |
| `cancelar` | UPDATE estado='Cancelada' solo para citas propias |

### `controllers/AdminCitaController.php`
| Acción | Roles | Descripción |
|---|---|---|
| `confirmar` | 1, 3 | UPDATE estado='Confirmada' |
| `rechazar` | 1, 3 | UPDATE estado='Cancelada' |
| `realizar` | 1, 3 | UPDATE estado='Realizada' |
| `cancelar` | 1, 3 | UPDATE estado='Cancelada' |

### `controllers/CitasCalendarioController.php`
Endpoint AJAX para FullCalendar. Devuelve JSON:
```json
[{"id":1,"title":"ABC-123 – Latonería","start":"2026-05-15 10:00:00","color":"#dc3545"}]
```

---

## Módulo 8: Dashboard en Tiempo Real

### `controllers/DashboardDataController.php`
Endpoint AJAX llamado cada 30 segundos desde `admin_dashboard.php`.

**Respuesta JSON:**
```json
{
  "tarjetas": {
    "vehiculos_proceso": 3,
    "ventas_mes": 63070,
    "pct_ventas": 15.2,
    "ordenes_activas": 8,
    "bajo_stock": 2,
    "citas_hoy": 1
  },
  "grafica_ventas": [{"mes":"Ene","total":"42000"}],
  "grafica_ordenes": [{"dia":"Lun","total":"5"}],
  "vehiculos_recientes": [...],
  "inventario_bajo": [...],
  "timestamp": "14:32:05"
}
```

---

## Base de Datos

### Tablas principales
| Tabla | Descripción |
|---|---|
| `roles` | 1=admin, 2=cliente, 3=empleado |
| `persona` | Usuarios del sistema (login) |
| `cliente` | Clientes con vehículos (tabla separada) |
| `empleado` | Relación persona-empleado |
| `vehiculo` | Vehículos registrados |
| `estado_vehiculo` | Pendiente, En reparación, Pintura, Finalizado, Entregado |
| `historial_vehiculo` | Órdenes/trabajos realizados |
| `cotizacion` | Presupuestos |
| `detalle_servicio` | Líneas de cotización (servicios) |
| `factura` | Facturas con estado_pago |
| `productos` | Catálogo de repuestos/materiales |
| `inventario` | Stock de productos |
| `citas` | Agenda de citas |
| `ventas` | Ventas directas |

### Relación persona ↔ cliente
```
persona (id_persona, correo) ←→ cliente (id_cliente, correo)
vehiculo.id_cliente → cliente.id_cliente  (NO persona)
citas.id_cliente    → persona.id_persona
```

---

## Convenciones de Código

### Verificación de rol (SIEMPRE antes del header)
```php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 1) {
    header("Location: ../usuarios/login.php"); exit;
}
require_once __DIR__ . '/../layouts/header.php';
```

### Alertas con SweetAlert2
```php
$_SESSION['alert'] = ['icon'=>'success','title'=>'OK','text'=>'Mensaje'];
// En la vista:
Swal.fire({ icon:'...', title:'...', text:'...', confirmButtonColor:'#2563eb' });
```

### Queries seguras con PDO
```php
$stmt = $db->prepare("SELECT * FROM tabla WHERE id = :id");
$stmt->execute([':id' => $id]);
$resultado = $stmt->fetch(PDO::FETCH_ASSOC);
```
