-- ============================================================
-- BASE DE DATOS COMPLETA: latoneria_pintura
-- Sistema de Gestión de Taller de Latonería y Pintura
-- Importar completo en HeidiSQL / phpMyAdmin
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `latoneria_pintura`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `latoneria_pintura`;

-- ── 1. roles ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `roles` (
  `id_rol`     int         NOT NULL AUTO_INCREMENT,
  `nombre_rol` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_rol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `roles` VALUES
  (1, 'administrador'),
  (2, 'cliente'),
  (3, 'empleado');

-- ── 2. persona ───────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `persona` (
  `id_persona`        int          NOT NULL AUTO_INCREMENT,
  `nombre`            varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `contraseña`        varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `correo`            varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `telefono`          varchar(20)  COLLATE utf8mb4_general_ci NOT NULL,
  `id_rol`            int          NOT NULL,
  `activo`            tinyint(1)   NOT NULL DEFAULT 1,
  `intentos_fallidos` int          NOT NULL DEFAULT 0,
  `bloqueado_hasta`   datetime     DEFAULT NULL,
  PRIMARY KEY (`id_persona`),
  UNIQUE KEY `correo` (`correo`),
  KEY `fk_persona_rol` (`id_rol`),
  CONSTRAINT `fk_persona_rol`
    FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Usuario admin por defecto  (contraseña: Admin1234!)
INSERT IGNORE INTO `persona`
  (`id_persona`,`nombre`,`contraseña`,`correo`,`telefono`,`id_rol`,`activo`)
VALUES
  (1,'Administrador',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'admin@taller.com','3001234567',1,1);

-- ── 3. administrador ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `administrador` (
  `id_administrador` int NOT NULL AUTO_INCREMENT,
  `id_persona`       int NOT NULL,
  PRIMARY KEY (`id_administrador`),
  UNIQUE KEY `id_persona` (`id_persona`),
  CONSTRAINT `fk_admin_persona`
    FOREIGN KEY (`id_persona`) REFERENCES `persona` (`id_persona`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `administrador` (`id_persona`) VALUES (1);

-- ── 4. empleado ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `empleado` (
  `id_empleado` int NOT NULL AUTO_INCREMENT,
  `id_persona`  int NOT NULL,
  PRIMARY KEY (`id_empleado`),
  UNIQUE KEY `id_persona` (`id_persona`),
  CONSTRAINT `fk_empleado_persona`
    FOREIGN KEY (`id_persona`) REFERENCES `persona` (`id_persona`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── 5. cliente ───────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `cliente` (
  `id_cliente` int          NOT NULL AUTO_INCREMENT,
  `nombre`     varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `direccion`  varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `correo`     varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `telefono`   varchar(20)  COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id_cliente`),
  UNIQUE KEY `correo` (`correo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── 6. estado_vehiculo ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `estado_vehiculo` (
  `id_estado_vehiculo` int         NOT NULL AUTO_INCREMENT,
  `estado`             varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_estado_vehiculo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `estado_vehiculo` VALUES
  (1,'Pendiente'),
  (2,'En reparación'),
  (3,'Pintura'),
  (4,'Finalizado'),
  (5,'Entregado');

-- ── 7. vehiculo ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `vehiculo` (
  `id_vehiculo`        int         NOT NULL AUTO_INCREMENT,
  `placa`              varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `marca`              varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `modelo`             varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `año`                varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `id_cliente`         int         NOT NULL,
  `id_estado_vehiculo` int         NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_vehiculo`),
  UNIQUE KEY `placa` (`placa`),
  KEY `fk_vehiculo_cliente` (`id_cliente`),
  KEY `fk_vehiculo_estado`  (`id_estado_vehiculo`),
  CONSTRAINT `fk_vehiculo_cliente`
    FOREIGN KEY (`id_cliente`)         REFERENCES `cliente`       (`id_cliente`)        ON DELETE RESTRICT,
  CONSTRAINT `fk_vehiculo_estado`
    FOREIGN KEY (`id_estado_vehiculo`) REFERENCES `estado_vehiculo`(`id_estado_vehiculo`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── 8. historial_vehiculo ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `historial_vehiculo` (
  `id_historial_vehiculo` int          NOT NULL AUTO_INCREMENT,
  `descripcion`           varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_registro`        date         NOT NULL,
  `tipo_reparacion`       varchar(50)  COLLATE utf8mb4_general_ci NOT NULL,
  `id_empleado`           int          NOT NULL,
  `id_vehiculo`           int          NOT NULL,
  PRIMARY KEY (`id_historial_vehiculo`),
  KEY `fk_hv_empleado` (`id_empleado`),
  KEY `fk_hv_vehiculo` (`id_vehiculo`),
  CONSTRAINT `fk_hv_empleado`
    FOREIGN KEY (`id_empleado`) REFERENCES `empleado` (`id_empleado`) ON DELETE RESTRICT,
  CONSTRAINT `fk_hv_vehiculo`
    FOREIGN KEY (`id_vehiculo`) REFERENCES `vehiculo` (`id_vehiculo`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── 9. servicio ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `servicio` (
  `id_servicio`  int         NOT NULL AUTO_INCREMENT,
  `nombre`       varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `tipo_servicio`varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_servicio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `servicio` VALUES
  (1,'Latonería',        'Latonería'),
  (2,'Pintura completa', 'Pintura'),
  (3,'Pintura parcial',  'Pintura'),
  (4,'Enderezado',       'Latonería'),
  (5,'Pulida y brillada','Acabado'),
  (6,'Revisión general', 'Revisión');

-- ── 10. historial_servicio ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `historial_servicio` (
  `id_historial_servicio` int  NOT NULL AUTO_INCREMENT,
  `descripcion`           text COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fecha_registro`        date NOT NULL,
  `tipo_reparacion`       varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_historial_vehiculo` int  NOT NULL,
  `id_servicio`           int  NOT NULL,
  PRIMARY KEY (`id_historial_servicio`),
  KEY `fk_hs_historial` (`id_historial_vehiculo`),
  KEY `fk_hs_servicio`  (`id_servicio`),
  CONSTRAINT `fk_hs_historial`
    FOREIGN KEY (`id_historial_vehiculo`) REFERENCES `historial_vehiculo` (`id_historial_vehiculo`) ON DELETE CASCADE,
  CONSTRAINT `fk_hs_servicio`
    FOREIGN KEY (`id_servicio`) REFERENCES `servicio` (`id_servicio`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── 11. cotizacion ───────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `cotizacion` (
  `id_cotizacion` int           NOT NULL AUTO_INCREMENT,
  `fecha`         date          NOT NULL,
  `pago_total`    decimal(10,2) NOT NULL DEFAULT '0.00',
  `id_vehiculo`   int           NOT NULL,
  PRIMARY KEY (`id_cotizacion`),
  KEY `fk_cot_vehiculo` (`id_vehiculo`),
  CONSTRAINT `fk_cot_vehiculo`
    FOREIGN KEY (`id_vehiculo`) REFERENCES `vehiculo` (`id_vehiculo`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── 12. detalle_servicio ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `detalle_servicio` (
  `id_detalle_servicio` int           NOT NULL AUTO_INCREMENT,
  `precio`              decimal(10,2) NOT NULL,
  `cantidad`            int           NOT NULL DEFAULT 1,
  `id_servicio`         int           NOT NULL,
  `id_cotizacion`       int           NOT NULL,
  PRIMARY KEY (`id_detalle_servicio`),
  KEY `fk_ds_servicio`   (`id_servicio`),
  KEY `fk_ds_cotizacion` (`id_cotizacion`),
  CONSTRAINT `fk_ds_servicio`
    FOREIGN KEY (`id_servicio`)   REFERENCES `servicio`   (`id_servicio`)   ON DELETE RESTRICT,
  CONSTRAINT `fk_ds_cotizacion`
    FOREIGN KEY (`id_cotizacion`) REFERENCES `cotizacion` (`id_cotizacion`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── 13. proveedor ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `proveedor` (
  `id_proveedor` int         NOT NULL AUTO_INCREMENT,
  `nombre`       varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `telefono`     varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `correo`       varchar(100)COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id_proveedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── 14. productos ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `productos` (
  `id_producto` int           NOT NULL AUTO_INCREMENT,
  `nombre`      varchar(150)  COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` text          COLLATE utf8mb4_general_ci DEFAULT NULL,
  `precio`      decimal(12,2) NOT NULL DEFAULT '0.00',
  `categoria`   varchar(60)   COLLATE utf8mb4_general_ci DEFAULT NULL,
  `activo`      tinyint(1)    NOT NULL DEFAULT 1,
  `created_at`  datetime      NOT NULL DEFAULT NOW(),
  PRIMARY KEY (`id_producto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── 15. inventario ───────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `inventario` (
  `id_inventario` int           NOT NULL AUTO_INCREMENT,
  `id_producto`   int           NOT NULL,
  `cantidad`      decimal(12,2) NOT NULL DEFAULT '0.00',
  `unidad`        varchar(20)   COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'unidad',
  `stock_minimo`  decimal(12,2) NOT NULL DEFAULT '5.00',
  `updated_at`    datetime      NOT NULL DEFAULT NOW() ON UPDATE NOW(),
  PRIMARY KEY (`id_inventario`),
  KEY `fk_inv_producto` (`id_producto`),
  CONSTRAINT `fk_inv_producto`
    FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── 16. repuesto ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `repuesto` (
  `id_repuesto`  int           NOT NULL AUTO_INCREMENT,
  `nombre`       varchar(100)  COLLATE utf8mb4_general_ci NOT NULL,
  `cantidad`     int           NOT NULL DEFAULT 0,
  `precio`       decimal(10,2) NOT NULL DEFAULT '0.00',
  `id_inventario`int           NOT NULL,
  `id_proveedor` int           NOT NULL,
  PRIMARY KEY (`id_repuesto`),
  KEY `fk_rep_inventario` (`id_inventario`),
  KEY `fk_rep_proveedor`  (`id_proveedor`),
  CONSTRAINT `fk_rep_inventario`
    FOREIGN KEY (`id_inventario`) REFERENCES `inventario` (`id_inventario`) ON DELETE RESTRICT,
  CONSTRAINT `fk_rep_proveedor`
    FOREIGN KEY (`id_proveedor`)  REFERENCES `proveedor`  (`id_proveedor`)  ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── 17. detalle_repuesto ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `detalle_repuesto` (
  `id_detalle_repuesto` int           NOT NULL AUTO_INCREMENT,
  `cantidad`            int           NOT NULL DEFAULT 1,
  `precio_unitario`     decimal(10,2) NOT NULL DEFAULT '0.00',
  `id_cotizacion`       int           NOT NULL,
  `id_repuesto`         int           NOT NULL,
  PRIMARY KEY (`id_detalle_repuesto`),
  KEY `fk_dr_cotizacion` (`id_cotizacion`),
  KEY `fk_dr_repuesto`   (`id_repuesto`),
  CONSTRAINT `fk_dr_cotizacion`
    FOREIGN KEY (`id_cotizacion`) REFERENCES `cotizacion` (`id_cotizacion`) ON DELETE CASCADE,
  CONSTRAINT `fk_dr_repuesto`
    FOREIGN KEY (`id_repuesto`)   REFERENCES `repuesto`   (`id_repuesto`)   ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── 18. factura ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `factura` (
  `id_factura`   int           NOT NULL AUTO_INCREMENT,
  `fecha`        date          NOT NULL,
  `total`        decimal(10,2) NOT NULL DEFAULT '0.00',
  `estado_pago`  ENUM('Pendiente','Pagada','Anulada') NOT NULL DEFAULT 'Pendiente',
  `id_cotizacion`int           NOT NULL,
  PRIMARY KEY (`id_factura`),
  KEY `fk_fac_cotizacion` (`id_cotizacion`),
  CONSTRAINT `fk_fac_cotizacion`
    FOREIGN KEY (`id_cotizacion`) REFERENCES `cotizacion` (`id_cotizacion`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── 19. pago ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `pago` (
  `id_pago`   int           NOT NULL AUTO_INCREMENT,
  `fecha`     date          NOT NULL,
  `monto`     decimal(10,2) NOT NULL DEFAULT '0.00',
  `metodo`    varchar(50)   COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Efectivo',
  `id_factura`int           NOT NULL,
  PRIMARY KEY (`id_pago`),
  KEY `fk_pago_factura` (`id_factura`),
  CONSTRAINT `fk_pago_factura`
    FOREIGN KEY (`id_factura`) REFERENCES `factura` (`id_factura`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── 20. ventas ───────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `ventas` (
  `id_venta`    int           NOT NULL AUTO_INCREMENT,
  `id_cliente`  int           NOT NULL,
  `id_empleado` int           DEFAULT NULL,
  `metodo_pago` varchar(50)   COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Efectivo',
  `total`       decimal(12,2) NOT NULL DEFAULT '0.00',
  `estado`      ENUM('Activa','Anulada') NOT NULL DEFAULT 'Activa',
  `observacion` text          COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at`  datetime      NOT NULL DEFAULT NOW(),
  PRIMARY KEY (`id_venta`),
  KEY `fk_venta_cliente`  (`id_cliente`),
  KEY `fk_venta_empleado` (`id_empleado`),
  CONSTRAINT `fk_venta_cliente`
    FOREIGN KEY (`id_cliente`)  REFERENCES `cliente`  (`id_cliente`)  ON DELETE RESTRICT,
  CONSTRAINT `fk_venta_empleado`
    FOREIGN KEY (`id_empleado`) REFERENCES `empleado` (`id_empleado`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── 21. detalle_venta ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `detalle_venta` (
  `id_detalle`  int           NOT NULL AUTO_INCREMENT,
  `id_venta`    int           NOT NULL,
  `id_producto` int           NOT NULL,
  `cantidad`    decimal(12,2) NOT NULL DEFAULT '1.00',
  `precio_unit` decimal(12,2) NOT NULL DEFAULT '0.00',
  `subtotal`    decimal(12,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id_detalle`),
  KEY `fk_dv_venta`    (`id_venta`),
  KEY `fk_dv_producto` (`id_producto`),
  CONSTRAINT `fk_dv_venta`
    FOREIGN KEY (`id_venta`)    REFERENCES `ventas`    (`id_venta`)    ON DELETE CASCADE,
  CONSTRAINT `fk_dv_producto`
    FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── 22. citas ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `citas` (
  `id_cita`       int          NOT NULL AUTO_INCREMENT,
  `numero_ref`    varchar(20)  COLLATE utf8mb4_general_ci NOT NULL,
  `id_cliente`    int          NOT NULL,
  `id_vehiculo`   int          DEFAULT NULL,
  `tipo_servicio` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_cita`    datetime     NOT NULL,
  `estado`        ENUM('Pendiente','Confirmada','Cancelada','Realizada') NOT NULL DEFAULT 'Pendiente',
  `created_at`    datetime     NOT NULL DEFAULT NOW(),
  PRIMARY KEY (`id_cita`),
  UNIQUE KEY `numero_ref` (`numero_ref`),
  KEY `fk_cita_cliente`  (`id_cliente`),
  KEY `fk_cita_vehiculo` (`id_vehiculo`),
  CONSTRAINT `fk_cita_cliente`
    FOREIGN KEY (`id_cliente`)  REFERENCES `persona`  (`id_persona`)  ON DELETE RESTRICT,
  CONSTRAINT `fk_cita_vehiculo`
    FOREIGN KEY (`id_vehiculo`) REFERENCES `vehiculo` (`id_vehiculo`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;
