# TallerPro — Sistema de Gestión de Taller de Latonería y Pintura

[![PHP](https://img.shields.io/badge/PHP-8.1+-blue)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-orange)](https://mysql.com)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-purple)](https://getbootstrap.com)

Sistema web integral para la gestión de un taller de latonería y pintura automotriz.

---

## Módulos del Sistema

| Módulo | Admin | Empleado | Cliente |
|---|:---:|:---:|:---:|
| Dashboard en tiempo real | ✅ | ✅ | ✅ |
| Gestión de Usuarios | ✅ | — | — |
| Gestión de Clientes | ✅ | — | — |
| Vehículos | ✅ | ✅ | ✅ |
| Órdenes de Trabajo | ✅ | ✅ | — |
| Cotizaciones | ✅ | — | ✅ (ver) |
| Facturación | ✅ | ✅ (ver) | — |
| Inventario | ✅ | — | — |
| Citas (calendario) | ✅ | ✅ | ✅ |
| Reportes | ✅ | — | — |
| Ventas | ✅ | ✅ | — |
| Proveedores | ✅ | — | — |

---

## Instalación

### Requisitos
- Laragon (Apache + PHP 8.1+ + MySQL 8.0)
- Navegador moderno

### Pasos

1. **Clonar/copiar** el proyecto en `C:\laragon\www\systemTaller`

2. **Importar la base de datos** en HeidiSQL:
   ```
   Archivo → Ejecutar archivo SQL → database/latoneria_pintura_completo.sql
   ```

3. **Si ya tienes datos** (migración sin pérdida):
   ```
   Archivo → Ejecutar archivo SQL → database/migracion_segura.sql
   ```

4. **Verificar configuración** en `config/database.php`:
   ```php
   private $host     = "127.0.0.1";
   private $db_name  = "latoneria_pintura";
   private $username = "root";
   private $password = "";
   ```

5. **Acceder** en el navegador:
   ```
   http://127.0.0.1/SystemTaller/views/usuarios/login.php
   ```

### Credenciales por defecto
| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | admin@taller.com | Admin1234! |

---

## Estructura del Proyecto

```
systemTaller/
├── config/           # Conexión a BD
├── controllers/      # Lógica de negocio
├── models/           # Acceso a datos
├── views/
│   ├── dashboard/    # Vistas por rol
│   ├── layouts/      # Header y footer compartidos
│   └── usuarios/     # Login, registro, recuperar
├── public/           # CSS y assets
├── database/         # Scripts SQL
└── documentacion/    # SRS, UML, Backend docs
```

---

## Documentación

| Documento | Archivo |
|---|---|
| Especificación de Requisitos (SRS) | `documentacion/SRS.md` |
| Diagramas UML | `documentacion/UML.md` |
| Documentación Backend | `documentacion/BACKEND.md` |

---

## Tecnologías

- **Backend:** PHP 8.1+ (MVC manual, PDO)
- **Base de datos:** MySQL 8.0
- **Frontend:** Bootstrap 5.3, FontAwesome 6.5, SweetAlert2
- **Gráficas:** Chart.js
- **Calendario:** FullCalendar 6.1
- **Servidor local:** Laragon (Apache)
