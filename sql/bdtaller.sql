-- ============================================================
-- Base de datos: Sistema Taller Latonería y Pintura (TDLP)
-- ============================================================

CREATE DATABASE IF NOT EXISTS bdtaller
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bdtaller;

-- ============================================================
-- USUARIOS (administrador, empleado, cliente)
-- ============================================================
CREATE TABLE usuarios (
    id_usuario    INT AUTO_INCREMENT PRIMARY KEY,
    nombres       VARCHAR(100) NOT NULL,
    apellidos     VARCHAR(100) NOT NULL,
    identificacion VARCHAR(20) UNIQUE NOT NULL,
    email         VARCHAR(150) UNIQUE NOT NULL,
    telefono      VARCHAR(20),
    password_hash VARCHAR(255) NOT NULL,
    rol           ENUM('administrador','empleado','cliente') NOT NULL DEFAULT 'cliente',
    activo        TINYINT(1) NOT NULL DEFAULT 1,
    intentos_fallidos INT NOT NULL DEFAULT 0,
    bloqueado_hasta DATETIME NULL,
    created_at    DATETIME NOT NULL DEFAULT NOW(),
    updated_at    DATETIME NOT NULL DEFAULT NOW() ON UPDATE NOW()
);

-- ============================================================
-- VEHÍCULOS
-- ============================================================
CREATE TABLE vehiculos (
    id_vehiculo  INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente   INT NOT NULL,
    placa        VARCHAR(10) UNIQUE NOT NULL,
    marca        VARCHAR(60) NOT NULL,
    modelo       VARCHAR(60) NOT NULL,
    anio         YEAR NOT NULL,
    color        VARCHAR(40),
    created_at   DATETIME NOT NULL DEFAULT NOW(),
    FOREIGN KEY (id_cliente) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT
);

-- ============================================================
-- PROVEEDORES
-- ============================================================
CREATE TABLE proveedores (
    id_proveedor INT AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(150) NOT NULL,
    nit          VARCHAR(20) UNIQUE NOT NULL,
    contacto     VARCHAR(100),
    email        VARCHAR(150) UNIQUE,
    telefono     VARCHAR(20),
    productos_suministrados TEXT,
    activo       TINYINT(1) NOT NULL DEFAULT 1,
    created_at   DATETIME NOT NULL DEFAULT NOW()
);

-- ============================================================
-- CATÁLOGO DE PRODUCTOS / REPUESTOS
-- ============================================================
CREATE TABLE productos (
    id_producto  INT AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(150) NOT NULL,
    descripcion  TEXT,
    precio       DECIMAL(12,2) NOT NULL DEFAULT 0,
    categoria    VARCHAR(60),
    activo       TINYINT(1) NOT NULL DEFAULT 1,
    created_at   DATETIME NOT NULL DEFAULT NOW()
);

-- ============================================================
-- INVENTARIO
-- ============================================================
CREATE TABLE inventario (
    id_inventario INT AUTO_INCREMENT PRIMARY KEY,
    id_producto   INT NOT NULL,
    cantidad      DECIMAL(12,2) NOT NULL DEFAULT 0,
    unidad        VARCHAR(20) NOT NULL DEFAULT 'unidad',
    stock_minimo  DECIMAL(12,2) NOT NULL DEFAULT 5,
    updated_at    DATETIME NOT NULL DEFAULT NOW() ON UPDATE NOW(),
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto) ON DELETE CASCADE
);

-- ============================================================
-- ÓRDENES DE SERVICIO
-- ============================================================
CREATE TABLE ordenes_servicio (
    id_orden     INT AUTO_INCREMENT PRIMARY KEY,
    numero_orden VARCHAR(20) UNIQUE NOT NULL,
    id_cliente   INT NOT NULL,
    id_vehiculo  INT NOT NULL,
    id_empleado  INT,
    tipo_servicio VARCHAR(100) NOT NULL,
    descripcion  TEXT,
    estado       ENUM('Ingresado','En espera','En reparación','Finalizado','Entregado') NOT NULL DEFAULT 'Ingresado',
    fecha_ingreso DATETIME NOT NULL DEFAULT NOW(),
    fecha_entrega DATETIME NULL,
    created_at   DATETIME NOT NULL DEFAULT NOW(),
    FOREIGN KEY (id_cliente)  REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    FOREIGN KEY (id_vehiculo) REFERENCES vehiculos(id_vehiculo) ON DELETE RESTRICT,
    FOREIGN KEY (id_empleado) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
);

-- ============================================================
-- HISTORIAL DE ESTADOS DE ORDEN
-- ============================================================
CREATE TABLE historial_estados (
    id_historial INT AUTO_INCREMENT PRIMARY KEY,
    id_orden     INT NOT NULL,
    estado       VARCHAR(50) NOT NULL,
    observacion  TEXT,
    id_responsable INT,
    fecha_cambio DATETIME NOT NULL DEFAULT NOW(),
    FOREIGN KEY (id_orden)       REFERENCES ordenes_servicio(id_orden) ON DELETE CASCADE,
    FOREIGN KEY (id_responsable) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
);

-- ============================================================
-- EVIDENCIAS FOTOGRÁFICAS
-- ============================================================
CREATE TABLE evidencias (
    id_evidencia INT AUTO_INCREMENT PRIMARY KEY,
    id_orden     INT NOT NULL,
    id_vehiculo  INT NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    ruta_archivo VARCHAR(500) NOT NULL,
    etapa        ENUM('antes','durante','despues') NOT NULL DEFAULT 'antes',
    subido_por   INT,
    created_at   DATETIME NOT NULL DEFAULT NOW(),
    FOREIGN KEY (id_orden)    REFERENCES ordenes_servicio(id_orden) ON DELETE CASCADE,
    FOREIGN KEY (id_vehiculo) REFERENCES vehiculos(id_vehiculo) ON DELETE CASCADE,
    FOREIGN KEY (subido_por)  REFERENCES usuarios(id_usuario) ON DELETE SET NULL
);

-- ============================================================
-- COTIZACIONES
-- ============================================================
CREATE TABLE cotizaciones (
    id_cotizacion INT AUTO_INCREMENT PRIMARY KEY,
    numero_cotizacion VARCHAR(20) UNIQUE NOT NULL,
    id_orden      INT NOT NULL,
    id_cliente    INT NOT NULL,
    subtotal      DECIMAL(12,2) NOT NULL DEFAULT 0,
    impuesto      DECIMAL(12,2) NOT NULL DEFAULT 0,
    total         DECIMAL(12,2) NOT NULL DEFAULT 0,
    estado        ENUM('Pendiente','Aprobada','Rechazada','Expirada') NOT NULL DEFAULT 'Pendiente',
    fecha_validez DATETIME NOT NULL,
    motivo_rechazo TEXT,
    created_at    DATETIME NOT NULL DEFAULT NOW(),
    FOREIGN KEY (id_orden)   REFERENCES ordenes_servicio(id_orden) ON DELETE RESTRICT,
    FOREIGN KEY (id_cliente) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT
);

-- ============================================================
-- DETALLES DE COTIZACIÓN
-- ============================================================
CREATE TABLE cotizacion_detalles (
    id_detalle    INT AUTO_INCREMENT PRIMARY KEY,
    id_cotizacion INT NOT NULL,
    id_producto   INT,
    descripcion   VARCHAR(255) NOT NULL,
    cantidad      DECIMAL(12,2) NOT NULL DEFAULT 1,
    precio_unit   DECIMAL(12,2) NOT NULL,
    subtotal      DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (id_cotizacion) REFERENCES cotizaciones(id_cotizacion) ON DELETE CASCADE,
    FOREIGN KEY (id_producto)   REFERENCES productos(id_producto) ON DELETE SET NULL
);

-- ============================================================
-- FACTURAS
-- ============================================================
CREATE TABLE facturas (
    id_factura    INT AUTO_INCREMENT PRIMARY KEY,
    numero_factura VARCHAR(20) UNIQUE NOT NULL,
    id_cotizacion INT NOT NULL,
    id_cliente    INT NOT NULL,
    subtotal      DECIMAL(12,2) NOT NULL,
    impuesto      DECIMAL(12,2) NOT NULL,
    total         DECIMAL(12,2) NOT NULL,
    estado_pago   ENUM('Pendiente','Pagada','Anulada') NOT NULL DEFAULT 'Pendiente',
    created_at    DATETIME NOT NULL DEFAULT NOW(),
    FOREIGN KEY (id_cotizacion) REFERENCES cotizaciones(id_cotizacion) ON DELETE RESTRICT,
    FOREIGN KEY (id_cliente)    REFERENCES usuarios(id_usuario) ON DELETE RESTRICT
);

-- ============================================================
-- VENTAS
-- ============================================================
CREATE TABLE ventas (
    id_venta     INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente   INT NOT NULL,
    id_empleado  INT,
    metodo_pago  VARCHAR(50) NOT NULL DEFAULT 'Efectivo',
    total        DECIMAL(12,2) NOT NULL,
    estado       ENUM('Activa','Anulada') NOT NULL DEFAULT 'Activa',
    observacion  TEXT,
    created_at   DATETIME NOT NULL DEFAULT NOW(),
    FOREIGN KEY (id_cliente)  REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    FOREIGN KEY (id_empleado) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
);

CREATE TABLE venta_detalles (
    id_detalle   INT AUTO_INCREMENT PRIMARY KEY,
    id_venta     INT NOT NULL,
    id_producto  INT NOT NULL,
    cantidad     DECIMAL(12,2) NOT NULL,
    precio_unit  DECIMAL(12,2) NOT NULL,
    subtotal     DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (id_venta)    REFERENCES ventas(id_venta) ON DELETE CASCADE,
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto) ON DELETE RESTRICT
);

-- ============================================================
-- CITAS
-- ============================================================
CREATE TABLE citas (
    id_cita      INT AUTO_INCREMENT PRIMARY KEY,
    numero_ref   VARCHAR(20) UNIQUE NOT NULL,
    id_cliente   INT NOT NULL,
    id_vehiculo  INT,
    tipo_servicio VARCHAR(100) NOT NULL,
    fecha_cita   DATETIME NOT NULL,
    estado       ENUM('Pendiente','Confirmada','Cancelada','Realizada') NOT NULL DEFAULT 'Pendiente',
    created_at   DATETIME NOT NULL DEFAULT NOW(),
    FOREIGN KEY (id_cliente)  REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    FOREIGN KEY (id_vehiculo) REFERENCES vehiculos(id_vehiculo) ON DELETE SET NULL
);

-- ============================================================
-- NOTIFICACIONES
-- ============================================================
CREATE TABLE notificaciones (
    id_notificacion INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario   INT NOT NULL,
    titulo       VARCHAR(200) NOT NULL,
    mensaje      TEXT NOT NULL,
    leida        TINYINT(1) NOT NULL DEFAULT 0,
    created_at   DATETIME NOT NULL DEFAULT NOW(),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
);

-- ============================================================
-- USUARIO ADMIN POR DEFECTO (password: Admin@1234)
-- ============================================================
INSERT INTO usuarios (nombres, apellidos, identificacion, email, telefono, password_hash, rol)
VALUES ('Super', 'Admin', '1000000001', 'admin@taller.com', '3001234567',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'administrador');
