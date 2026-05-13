# SRS — Especificación de Requisitos del Software
## Sistema de Gestión de Taller de Latonería y Pintura (TallerPro)

**Versión:** 1.0  
**Fecha:** Mayo 2026  
**Estado:** Producción

---

## 1. Introducción

### 1.1 Propósito
Este documento especifica los requisitos funcionales y no funcionales del Sistema de Gestión Integral para un taller de latonería y pintura automotriz. El sistema permite gestionar clientes, vehículos, órdenes de trabajo, cotizaciones, facturación, inventario, citas y reportes.

### 1.2 Alcance
**TallerPro** es una aplicación web desarrollada en PHP con base de datos MySQL, accesible desde navegador. Cubre los procesos operativos del taller desde el ingreso del vehículo hasta la entrega y facturación.

### 1.3 Definiciones
| Término | Definición |
|---|---|
| Admin | Usuario con rol 1, acceso total al sistema |
| Empleado | Usuario con rol 3, gestiona vehículos y órdenes |
| Cliente | Usuario con rol 2, accede a su panel personal |
| Orden | Registro de trabajo en `historial_vehiculo` |
| Cotización | Presupuesto generado para un vehículo |

---

## 2. Descripción General

### 2.1 Perspectiva del producto
Sistema web MVC en PHP 8.x + MySQL 8.0, sin frameworks externos. Interfaz Bootstrap 5.3.

### 2.2 Funciones principales
- Autenticación con bloqueo por intentos fallidos
- Gestión de usuarios por roles (admin/empleado/cliente)
- Registro y seguimiento de vehículos
- Órdenes de trabajo con historial
- Cotizaciones y facturación
- Inventario de productos y repuestos
- Agenda de citas con calendario
- Reportes y estadísticas

### 2.3 Roles de usuario
| Rol | ID | Capacidades |
|---|---|---|
| Administrador | 1 | Acceso total, gestión de usuarios, reportes |
| Empleado | 3 | Vehículos, órdenes, citas, historial, ventas |
| Cliente | 2 | Sus vehículos, citas, cotizaciones, estado servicio |

---

## 3. Requisitos Funcionales

### RF-01 Autenticación
- El sistema debe permitir login con correo y contraseña
- Bloquear cuenta tras 5 intentos fallidos por 15 minutos
- Redirigir al dashboard según rol tras login exitoso
- Permitir recuperación de contraseña por correo o teléfono

### RF-02 Gestión de Usuarios (Admin)
- CRUD completo de usuarios (nombre, correo, teléfono, rol, contraseña)
- Activar/desactivar cuentas sin eliminar datos
- Filtrar y buscar usuarios en tiempo real

### RF-03 Gestión de Clientes (Admin)
- Registrar clientes con nombre, correo, teléfono
- Editar y desactivar clientes
- Vincular automáticamente cliente de `persona` con tabla `cliente` al registrar vehículo

### RF-04 Gestión de Vehículos
- Registrar vehículo con placa, marca, modelo, año, cliente, estado
- Filtrar por estado: Pendiente, En reparación, Pintura, Finalizado, Entregado
- Ver historial completo por vehículo (timeline)
- Agregar entradas al historial con tipo de reparación y empleado asignado
- Cliente puede registrar su propio vehículo desde su panel

### RF-05 Órdenes de Trabajo
- Crear orden asignando vehículo, cliente, empleado y tipo de servicio
- Cambiar estado del vehículo automáticamente al crear orden
- Empleado puede ver y actualizar estado de órdenes asignadas

### RF-06 Cotizaciones
- Crear cotización con servicios y repuestos
- Aprobar cotización genera factura automáticamente
- Cliente puede ver sus cotizaciones con detalles

### RF-07 Facturación
- Listar facturas con estado de pago (Pendiente/Pagada/Anulada)
- Marcar factura como pagada
- Exportar factura en HTML (placeholder para PDF)

### RF-08 Inventario
- CRUD de productos con nombre, categoría, precio, cantidad, unidad, stock mínimo
- Alerta visual cuando cantidad ≤ stock mínimo
- Buscador en tiempo real

### RF-09 Citas
- Cliente agenda cita seleccionando vehículo, servicio, fecha/hora desde calendario
- Calendario muestra días ocupados (rojo) y disponibles (verde)
- Admin/Empleado confirma, rechaza, marca como realizada o cancela citas
- Tabs: Pendientes | Confirmadas | Todas

### RF-10 Reportes (Admin)
- Ingresos totales facturados
- Total de órdenes del período
- Servicios más realizados con barras de progreso
- Últimas facturas con estado

### RF-11 Dashboard en tiempo real
- Tarjetas con datos reales de BD
- Actualización automática cada 30 segundos via AJAX
- Gráficas de ventas mensuales y órdenes por día

---

## 4. Requisitos No Funcionales

### RNF-01 Rendimiento
- Tiempo de respuesta < 2 segundos para consultas normales
- Dashboard se actualiza sin recargar página

### RNF-02 Seguridad
- Contraseñas hasheadas con `password_hash()` (bcrypt)
- Verificación de sesión en cada página protegida
- Verificación de rol antes de cualquier output HTML
- Parámetros SQL con PDO prepared statements

### RNF-03 Usabilidad
- Interfaz responsive con Bootstrap 5.3
- Alertas con SweetAlert2
- Buscadores en tiempo real en todas las tablas

### RNF-04 Compatibilidad
- PHP 8.1+
- MySQL 8.0+
- Navegadores modernos (Chrome, Firefox, Edge)

---

## 5. Restricciones
- Sin framework PHP (MVC manual)
- Base de datos: `latoneria_pintura` en MySQL local (Laragon)
- Sin envío de correos (recuperación de contraseña por teléfono/correo en BD)
