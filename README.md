# TallerPro – Sistema de Gestión para Taller de Latonería y Pintura

Sistema PHP + Bootstrap 5 con arquitectura MVC, desarrollado según las Historias de Usuario TDLP-001 a TDLP-020.

## Estructura del Proyecto

```
systemTaller/
├── config/
│   └── database.php                 # Conexión PDO MySQL
├── controllers/
│   ├── AuthController.php           # Login, registro, logout (TDLP-001, 002, 004)
│   ├── AdminUsuarioController.php   # CRUD empleados/clientes (TDLP-003)
│   ├── AdminOrdenController.php     # Órdenes de servicio (TDLP-012)
│   ├── AdminInventarioController.php# Inventario (TDLP-013)
│   └── ClienteCitaController.php    # Agenda de citas (TDLP-020)
├── models/
│   ├── Usuario.php                  # Modelo de usuarios
│   ├── Vehiculo.php                 # Modelo de vehículos (TDLP-005)
│   └── OrdenServicio.php            # Órdenes + historial (TDLP-008)
├── views/
│   ├── layouts/
│   │   ├── header.php               # Sidebar + topbar (Bootstrap)
│   │   └── footer.php
│   ├── usuarios/
│   │   ├── login.php                # (TDLP-001)
│   │   └── registre.php             # (TDLP-004)
│   └── dashboard/
│       ├── admin_dashboard.php      # Panel administrador
│       ├── admin_usuarios.php       # Gestión usuarios (TDLP-003)
│       ├── admin_ordenes.php        # Órdenes de servicio (TDLP-008, 012)
│       ├── admin_inventario.php     # Inventario (TDLP-013)
│       ├── admin_reportes.php       # Reportes (TDLP-016)
│       ├── cliente_dashboard.php    # Panel cliente
│       ├── cliente_citas.php        # Citas (TDLP-020)
│       └── empleado_dashboard.php   # Panel empleado
├── public/
│   └── index.php                    # Landing page
└── sql/
    └── bdtaller.sql                 # Script BD completo
```

## Instalación

1. **Base de datos**: Ejecutar `sql/bdtaller.sql` en MySQL/MariaDB.
2. **Configurar conexión**: Editar `config/database.php` con tus credenciales.
3. **Servidor**: Apuntar el DocumentRoot a la carpeta `systemTaller/`.
4. **Acceso inicial**: `admin@taller.com` / `password` (cambiar inmediatamente).

## Historias de Usuario Cubiertas

| ID       | Funcionalidad                          | Módulo                            |
|----------|----------------------------------------|-----------------------------------|
| TDLP-001 | Inicio de sesión / Cierre de sesión    | AuthController + login.php        |
| TDLP-002 | Recuperar contraseña                   | AuthController                    |
| TDLP-003 | Gestión de empleados y clientes        | AdminUsuarioController            |
| TDLP-004 | Registro de clientes                   | AuthController (registro)         |
| TDLP-005 | Registro de vehículos                  | Vehiculo model                    |
| TDLP-006 | Historial de cliente                   | OrdenServicio model               |
| TDLP-007 | Historial de vehículo                  | Vehiculo::historialServicios      |
| TDLP-008 | Seguimiento estado vehículo            | admin_ordenes.php + controller    |
| TDLP-009 | Evidencias fotográficas                | (estructura SQL lista)            |
| TDLP-010 | Cotizaciones y facturación             | (estructura SQL lista)            |
| TDLP-011 | Aprobar/rechazar cotización            | (estructura SQL lista)            |
| TDLP-012 | Crear orden de servicio                | AdminOrdenController              |
| TDLP-013 | Gestión de inventario                  | AdminInventarioController         |
| TDLP-014 | Notificaciones a clientes              | (tabla notificaciones lista)      |
| TDLP-015 | Consulta estado vehículo               | cliente_ordenes (estructura)      |
| TDLP-016 | Reportes productividad/ingresos        | admin_reportes.php                |
| TDLP-017 | Catálogo de productos                  | (tabla productos lista)           |
| TDLP-018 | Registro de ventas                     | (tabla ventas lista)              |
| TDLP-019 | Gestión de proveedores                 | (tabla proveedores lista)         |
| TDLP-020 | Agenda de citas                        | ClienteCitaController + citas.php |

## Tecnologías

- **Backend**: PHP 8+ con PDO y patrón MVC
- **Frontend**: Bootstrap 5.3 + Font Awesome 6.5 + SweetAlert2
- **BD**: MySQL 8 / MariaDB
- **Colores**: Azul (#1a3a6b, #2563eb), Gris, Blanco
