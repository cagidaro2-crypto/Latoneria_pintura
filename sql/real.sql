-- --------------------------------------------------------
-- Base de datos: latoneria_pintura
-- Versión limpia y reestructurada
-- Charset: utf8mb4 / utf8mb4_general_ci (uniforme)
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

-- -----------------------------------------------------------
-- 1. ROLES
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `roles` (
  `id_rol`     INT          NOT NULL AUTO_INCREMENT,
  `nombre_rol` VARCHAR(50)  NOT NULL,
  PRIMARY KEY (`id_rol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `roles` (`id_rol`, `nombre_rol`) VALUES
  (1, 'Administrador'),
  (2, 'Empleado'),
  (3, 'Cliente');

-- -----------------------------------------------------------
-- 2. USUARIOS  (unifica persona + administrador + empleado + usuarios)
--    rol determina si es admin, empleado o cliente del sistema
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id_usuario`       INT          NOT NULL AUTO_INCREMENT,
  `id_rol`           INT          NOT NULL,
  `nombres`          VARCHAR(100) NOT NULL,
  `apellidos`        VARCHAR(100) DEFAULT NULL,
  `tipo_documento`   VARCHAR(20)  DEFAULT NULL,
  `documento`        VARCHAR(30)  DEFAULT NULL,
  `telefono`         VARCHAR(30)  DEFAULT NULL,
  `correo`           VARCHAR(120) NOT NULL,
  `password`         VARCHAR(255) NOT NULL,
  `activo`           TINYINT(1)   NOT NULL DEFAULT 1,
  `intentos_fallidos` INT         NOT NULL DEFAULT 0,
  `bloqueado_hasta`  DATETIME     DEFAULT NULL,
  `fecha_creacion`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `uk_documento` (`documento`),
  UNIQUE KEY `uk_correo`    (`correo`),
  KEY `fk_usuarios_rol` (`id_rol`),
  CONSTRAINT `fk_usuarios_rol` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------
-- 3. CLIENTES  (clientes del taller, pueden o no tener usuario)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `clientes` (
  `id_cliente`      INT          NOT NULL AUTO_INCREMENT,
  `id_usuario`      INT          DEFAULT NULL COMMENT 'NULL si el cliente no tiene acceso al sistema',
  `tipo_documento`  VARCHAR(20)  DEFAULT NULL,
  `documento`       VARCHAR(30)  DEFAULT NULL,
  `nombres`         VARCHAR(100) NOT NULL,
  `apellidos`       VARCHAR(100) DEFAULT NULL,
  `telefono`        VARCHAR(30)  DEFAULT NULL,
  `correo`          VARCHAR(100) DEFAULT NULL,
  `direccion`       VARCHAR(255) DEFAULT NULL,
  `fecha_registro`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_cliente`),
  UNIQUE KEY `uk_clientes_documento` (`documento`),
  KEY `fk_clientes_usuario` (`id_usuario`),
  CONSTRAINT `fk_clientes_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------
-- 4. PROVEEDORES
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `proveedores` (
  `id_proveedor` INT          NOT NULL AUTO_INCREMENT,
  `nit`          VARCHAR(50)  DEFAULT NULL,
  `nombre`       VARCHAR(150) NOT NULL,
  `telefono`     VARCHAR(30)  DEFAULT NULL,
  `correo`       VARCHAR(100) DEFAULT NULL,
  `direccion`    VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id_proveedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------
-- 5. CATEGORIAS_PRODUCTOS
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categorias_productos` (
  `id_categoria` INT          NOT NULL AUTO_INCREMENT,
  `nombre`       VARCHAR(100) NOT NULL,
  `descripcion`  TEXT         DEFAULT NULL,
  PRIMARY KEY (`id_categoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------
-- 6. PRODUCTOS
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `productos` (
  `id_producto`   INT           NOT NULL AUTO_INCREMENT,
  `id_categoria`  INT           DEFAULT NULL,
  `id_proveedor`  INT           DEFAULT NULL,
  `nombre`        VARCHAR(150)  NOT NULL,
  `descripcion`   TEXT          DEFAULT NULL,
  `precio`        DECIMAL(12,2) NOT NULL DEFAULT '0.00',
  `activo`        TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_producto`),
  KEY `fk_prod_categoria`  (`id_categoria`),
  KEY `fk_prod_proveedor`  (`id_proveedor`),
  CONSTRAINT `fk_prod_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categorias_productos` (`id_categoria`) ON DELETE SET NULL,
  CONSTRAINT `fk_prod_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------
-- 7. INVENTARIO
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `inventario` (
  `id_inventario` INT           NOT NULL AUTO_INCREMENT,
  `id_producto`   INT           NOT NULL,
  `cantidad`      DECIMAL(12,2) NOT NULL DEFAULT '0.00',
  `unidad`        VARCHAR(20)   NOT NULL DEFAULT 'unidad',
  `stock_minimo`  DECIMAL(12,2) NOT NULL DEFAULT '5.00',
  `updated_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_inventario`),
  UNIQUE KEY `uk_inv_producto` (`id_producto`),
  CONSTRAINT `fk_inv_prod` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------
-- 8. SERVICIOS
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `servicios` (
  `id_servicio`  INT           NOT NULL AUTO_INCREMENT,
  `nombre`       VARCHAR(150)  NOT NULL,
  `tipo_servicio` VARCHAR(100) DEFAULT NULL,
  `descripcion`  TEXT          DEFAULT NULL,
  `precio_base`  DECIMAL(12,2) DEFAULT NULL,
  PRIMARY KEY (`id_servicio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------
-- 9. ESTADO_VEHICULO
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `estado_vehiculo` (
  `id_estado_vehiculo` INT         NOT NULL AUTO_INCREMENT,
  `estado`             VARCHAR(50) NOT NULL,
  PRIMARY KEY (`id_estado_vehiculo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `estado_vehiculo` (`id_estado_vehiculo`, `estado`) VALUES
  (1, 'En espera'),
  (2, 'En reparación'),
  (3, 'Listo para entrega'),
  (4, 'Entregado'),
  (5, 'Cancelado');

-- -----------------------------------------------------------
-- 10. VEHICULOS
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `vehiculos` (
  `id_vehiculo`    INT          NOT NULL AUTO_INCREMENT,
  `id_cliente`     INT          NOT NULL,
  `id_estado`      INT          NOT NULL DEFAULT 1,
  `placa`          VARCHAR(20)  NOT NULL,
  `marca`          VARCHAR(50)  DEFAULT NULL,
  `modelo`         VARCHAR(50)  DEFAULT NULL,
  `anio`           YEAR         DEFAULT NULL,
  `color`          VARCHAR(50)  DEFAULT NULL,
  `tipo_vehiculo`  VARCHAR(50)  DEFAULT NULL,
  `numero_motor`   VARCHAR(100) DEFAULT NULL,
  `numero_chasis`  VARCHAR(100) DEFAULT NULL,
  `kilometraje`    INT          DEFAULT NULL,
  PRIMARY KEY (`id_vehiculo`),
  UNIQUE KEY `uk_placa` (`placa`),
  KEY `fk_veh_cliente` (`id_cliente`),
  KEY `fk_veh_estado`  (`id_estado`),
  CONSTRAINT `fk_veh_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes`       (`id_cliente`) ON DELETE RESTRICT,
  CONSTRAINT `fk_veh_estado`  FOREIGN KEY (`id_estado`)  REFERENCES `estado_vehiculo` (`id_estado_vehiculo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------
-- 11. VEHICULO_FOTOS
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `vehiculo_fotos` (
  `id_foto`        INT          NOT NULL AUTO_INCREMENT,
  `id_vehiculo`    INT          NOT NULL,
  `id_usuario`     INT          DEFAULT NULL COMMENT 'Quien subió la foto',
  `nombre_archivo` VARCHAR(255) DEFAULT NULL,
  `ruta_archivo`   VARCHAR(500) DEFAULT NULL,
  `etapa`          ENUM('antes','durante','despues') NOT NULL DEFAULT 'antes',
  `descripcion`    TEXT         DEFAULT NULL,
  `fecha_subida`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_foto`),
  KEY `fk_foto_vehiculo` (`id_vehiculo`),
  KEY `fk_foto_usuario`  (`id_usuario`),
  CONSTRAINT `fk_foto_vehiculo` FOREIGN KEY (`id_vehiculo`) REFERENCES `vehiculos` (`id_vehiculo`) ON DELETE CASCADE,
  CONSTRAINT `fk_foto_usuario`  FOREIGN KEY (`id_usuario`)  REFERENCES `usuarios`  (`id_usuario`)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------
-- 12. CITAS
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `citas` (
  `id_cita`      INT          NOT NULL AUTO_INCREMENT,
  `numero_ref`   VARCHAR(20)  NOT NULL,
  `id_cliente`   INT          NOT NULL,
  `id_vehiculo`  INT          DEFAULT NULL,
  `tipo_servicio` VARCHAR(100) NOT NULL,
  `fecha_cita`   DATETIME     NOT NULL,
  `estado`       ENUM('Pendiente','Confirmada','Cancelada','Realizada') NOT NULL DEFAULT 'Pendiente',
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_cita`),
  UNIQUE KEY `uk_numero_ref` (`numero_ref`),
  KEY `fk_cita_cliente`  (`id_cliente`),
  KEY `fk_cita_vehiculo` (`id_vehiculo`),
  CONSTRAINT `fk_cita_cliente`  FOREIGN KEY (`id_cliente`)  REFERENCES `clientes`  (`id_cliente`)  ON DELETE RESTRICT,
  CONSTRAINT `fk_cita_vehiculo` FOREIGN KEY (`id_vehiculo`) REFERENCES `vehiculos` (`id_vehiculo`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------
-- 13. ESTADOS_OT
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `estados_ot` (
  `id_estado` INT         NOT NULL AUTO_INCREMENT,
  `nombre`    VARCHAR(50) NOT NULL,
  PRIMARY KEY (`id_estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `estados_ot` (`id_estado`, `nombre`) VALUES
  (1, 'Recibido'),
  (2, 'En proceso'),
  (3, 'Pausado'),
  (4, 'Terminado'),
  (5, 'Entregado'),
  (6, 'Cancelado');

-- -----------------------------------------------------------
-- 14. ORDENES_TRABAJO
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ordenes_trabajo` (
  `id_orden`               INT           NOT NULL AUTO_INCREMENT,
  `id_cliente`             INT           NOT NULL,
  `id_vehiculo`            INT           NOT NULL,
  `id_estado`              INT           NOT NULL DEFAULT 1,
  `id_empleado`            INT           DEFAULT NULL COMMENT 'Empleado asignado',
  `fecha_ingreso`          DATETIME      DEFAULT CURRENT_TIMESTAMP,
  `fecha_entrega_estimada` DATETIME      DEFAULT NULL,
  `fecha_entrega_real`     DATETIME      DEFAULT NULL,
  `descripcion_danos`      TEXT          DEFAULT NULL,
  `observaciones`          TEXT          DEFAULT NULL,
  `total`                  DECIMAL(12,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id_orden`),
  KEY `fk_ot_cliente`  (`id_cliente`),
  KEY `fk_ot_vehiculo` (`id_vehiculo`),
  KEY `fk_ot_estado`   (`id_estado`),
  KEY `fk_ot_empleado` (`id_empleado`),
  CONSTRAINT `fk_ot_cliente`  FOREIGN KEY (`id_cliente`)  REFERENCES `clientes`    (`id_cliente`)  ON DELETE RESTRICT,
  CONSTRAINT `fk_ot_vehiculo` FOREIGN KEY (`id_vehiculo`) REFERENCES `vehiculos`   (`id_vehiculo`) ON DELETE RESTRICT,
  CONSTRAINT `fk_ot_estado`   FOREIGN KEY (`id_estado`)   REFERENCES `estados_ot`  (`id_estado`),
  CONSTRAINT `fk_ot_empleado` FOREIGN KEY (`id_empleado`) REFERENCES `usuarios`    (`id_usuario`)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------
-- 15. ORDEN_SERVICIOS  (servicios dentro de una OT)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `orden_servicios` (
  `id_detalle`    INT           NOT NULL AUTO_INCREMENT,
  `id_orden`      INT           NOT NULL,
  `id_servicio`   INT           NOT NULL,
  `cantidad`      INT           NOT NULL DEFAULT 1,
  `valor_unitario` DECIMAL(12,2) DEFAULT NULL,
  `subtotal`      DECIMAL(12,2) DEFAULT NULL,
  PRIMARY KEY (`id_detalle`),
  KEY `fk_os_orden`    (`id_orden`),
  KEY `fk_os_servicio` (`id_servicio`),
  CONSTRAINT `fk_os_orden`    FOREIGN KEY (`id_orden`)    REFERENCES `ordenes_trabajo` (`id_orden`)    ON DELETE CASCADE,
  CONSTRAINT `fk_os_servicio` FOREIGN KEY (`id_servicio`) REFERENCES `servicios`       (`id_servicio`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------
-- 16. ORDEN_PRODUCTOS  (repuestos/materiales usados en una OT)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `orden_productos` (
  `id_detalle`    INT           NOT NULL AUTO_INCREMENT,
  `id_orden`      INT           NOT NULL,
  `id_producto`   INT           NOT NULL,
  `cantidad`      DECIMAL(12,2) NOT NULL DEFAULT '1.00',
  `precio_unit`   DECIMAL(12,2) NOT NULL DEFAULT '0.00',
  `subtotal`      DECIMAL(12,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id_detalle`),
  KEY `fk_op_orden`    (`id_orden`),
  KEY `fk_op_producto` (`id_producto`),
  CONSTRAINT `fk_op_orden`    FOREIGN KEY (`id_orden`)    REFERENCES `ordenes_trabajo` (`id_orden`)    ON DELETE CASCADE,
  CONSTRAINT `fk_op_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos`       (`id_producto`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------
-- 17. COTIZACIONES
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cotizaciones` (
  `id_cotizacion` INT           NOT NULL AUTO_INCREMENT,
  `id_cliente`    INT           NOT NULL,
  `id_vehiculo`   INT           NOT NULL,
  `fecha`         DATE          NOT NULL,
  `pago_total`    DECIMAL(12,2) NOT NULL DEFAULT '0.00',
  `estado`        ENUM('Pendiente','Aceptada','Rechazada') NOT NULL DEFAULT 'Pendiente',
  `created_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_cotizacion`),
  KEY `fk_cot_cliente`  (`id_cliente`),
  KEY `fk_cot_vehiculo` (`id_vehiculo`),
  CONSTRAINT `fk_cot_cliente`  FOREIGN KEY (`id_cliente`)  REFERENCES `clientes`  (`id_cliente`)  ON DELETE RESTRICT,
  CONSTRAINT `fk_cot_vehiculo` FOREIGN KEY (`id_vehiculo`) REFERENCES `vehiculos` (`id_vehiculo`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------
-- 18. COTIZACION_SERVICIOS
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cotizacion_servicios` (
  `id_detalle`    INT           NOT NULL AUTO_INCREMENT,
  `id_cotizacion` INT           NOT NULL,
  `id_servicio`   INT           NOT NULL,
  `cantidad`      INT           NOT NULL DEFAULT 1,
  `precio`        DECIMAL(12,2) NOT NULL DEFAULT '0.00',
  `subtotal`      DECIMAL(12,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id_detalle`),
  KEY `fk_cs_cotizacion` (`id_cotizacion`),
  KEY `fk_cs_servicio`   (`id_servicio`),
  CONSTRAINT `fk_cs_cotizacion` FOREIGN KEY (`id_cotizacion`) REFERENCES `cotizaciones` (`id_cotizacion`) ON DELETE CASCADE,
  CONSTRAINT `fk_cs_servicio`   FOREIGN KEY (`id_servicio`)   REFERENCES `servicios`    (`id_servicio`)   ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------
-- 19. COTIZACION_PRODUCTOS
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cotizacion_productos` (
  `id_detalle`    INT           NOT NULL AUTO_INCREMENT,
  `id_cotizacion` INT           NOT NULL,
  `id_producto`   INT           NOT NULL,
  `cantidad`      DECIMAL(12,2) NOT NULL DEFAULT '1.00',
  `precio_unit`   DECIMAL(12,2) NOT NULL DEFAULT '0.00',
  `subtotal`      DECIMAL(12,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id_detalle`),
  KEY `fk_cp_cotizacion` (`id_cotizacion`),
  KEY `fk_cp_producto`   (`id_producto`),
  CONSTRAINT `fk_cp_cotizacion` FOREIGN KEY (`id_cotizacion`) REFERENCES `cotizaciones` (`id_cotizacion`) ON DELETE CASCADE,
  CONSTRAINT `fk_cp_producto`   FOREIGN KEY (`id_producto`)   REFERENCES `productos`    (`id_producto`)   ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------
-- 20. FACTURAS
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `facturas` (
  `id_factura`    INT           NOT NULL AUTO_INCREMENT,
  `id_cotizacion` INT           DEFAULT NULL COMMENT 'Opcional: puede generarse desde una cotización',
  `id_orden`      INT           DEFAULT NULL COMMENT 'Opcional: puede generarse desde una OT',
  `fecha`         DATE          NOT NULL,
  `total`         DECIMAL(12,2) NOT NULL DEFAULT '0.00',
  `estado_pago`   ENUM('Pendiente','Pagada','Anulada') NOT NULL DEFAULT 'Pendiente',
  `created_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_factura`),
  KEY `fk_fac_cotizacion` (`id_cotizacion`),
  KEY `fk_fac_orden`      (`id_orden`),
  CONSTRAINT `fk_fac_cotizacion` FOREIGN KEY (`id_cotizacion`) REFERENCES `cotizaciones`   (`id_cotizacion`) ON DELETE SET NULL,
  CONSTRAINT `fk_fac_orden`      FOREIGN KEY (`id_orden`)      REFERENCES `ordenes_trabajo` (`id_orden`)      ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------
-- 21. PAGOS
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pagos` (
  `id_pago`    INT           NOT NULL AUTO_INCREMENT,
  `id_factura` INT           NOT NULL,
  `fecha`      DATE          NOT NULL,
  `monto`      DECIMAL(12,2) NOT NULL,
  `metodo`     VARCHAR(50)   NOT NULL DEFAULT 'Efectivo',
  `referencia` VARCHAR(100)  DEFAULT NULL COMMENT 'Número de transacción / recibo',
  PRIMARY KEY (`id_pago`),
  KEY `fk_pago_factura` (`id_factura`),
  CONSTRAINT `fk_pago_factura` FOREIGN KEY (`id_factura`) REFERENCES `facturas` (`id_factura`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------
-- 22. VENTAS  (venta de productos en mostrador, sin OT)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ventas` (
  `id_venta`   INT           NOT NULL AUTO_INCREMENT,
  `id_cliente` INT           NOT NULL,
  `id_usuario` INT           DEFAULT NULL COMMENT 'Empleado que realizó la venta',
  `metodo_pago` VARCHAR(50)  NOT NULL DEFAULT 'Efectivo',
  `total`      DECIMAL(12,2) NOT NULL DEFAULT '0.00',
  `estado`     ENUM('Activa','Anulada') NOT NULL DEFAULT 'Activa',
  `observacion` TEXT         DEFAULT NULL,
  `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_venta`),
  KEY `fk_venta_cliente` (`id_cliente`),
  KEY `fk_venta_usuario` (`id_usuario`),
  CONSTRAINT `fk_venta_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes`  (`id_cliente`) ON DELETE RESTRICT,
  CONSTRAINT `fk_venta_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios`  (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------
-- 23. DETALLE_VENTA
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `detalle_venta` (
  `id_detalle`  INT           NOT NULL AUTO_INCREMENT,
  `id_venta`    INT           NOT NULL,
  `id_producto` INT           NOT NULL,
  `cantidad`    DECIMAL(12,2) NOT NULL DEFAULT '1.00',
  `precio_unit` DECIMAL(12,2) NOT NULL DEFAULT '0.00',
  `subtotal`    DECIMAL(12,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id_detalle`),
  KEY `fk_dv_venta`    (`id_venta`),
  KEY `fk_dv_producto` (`id_producto`),
  CONSTRAINT `fk_dv_venta`    FOREIGN KEY (`id_venta`)    REFERENCES `ventas`    (`id_venta`)    ON DELETE CASCADE,
  CONSTRAINT `fk_dv_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------
-- 24. HISTORIAL_VEHICULO
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `historial_vehiculo` (
  `id_historial`   INT          NOT NULL AUTO_INCREMENT,
  `id_vehiculo`    INT          NOT NULL,
  `id_orden`       INT          DEFAULT NULL COMMENT 'OT relacionada si aplica',
  `id_usuario`     INT          DEFAULT NULL COMMENT 'Empleado que registra',
  `fecha_registro` DATE         NOT NULL,
  `tipo_reparacion` VARCHAR(100) DEFAULT NULL,
  `descripcion`    TEXT         DEFAULT NULL,
  PRIMARY KEY (`id_historial`),
  KEY `fk_hv_vehiculo` (`id_vehiculo`),
  KEY `fk_hv_orden`    (`id_orden`),
  KEY `fk_hv_usuario`  (`id_usuario`),
  CONSTRAINT `fk_hv_vehiculo` FOREIGN KEY (`id_vehiculo`) REFERENCES `vehiculos`       (`id_vehiculo`) ON DELETE CASCADE,
  CONSTRAINT `fk_hv_orden`    FOREIGN KEY (`id_orden`)    REFERENCES `ordenes_trabajo`  (`id_orden`)    ON DELETE SET NULL,
  CONSTRAINT `fk_hv_usuario`  FOREIGN KEY (`id_usuario`)  REFERENCES `usuarios`         (`id_usuario`)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------
-- 25. PRODUCTO_FOTOS
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `producto_fotos` (
  `id_foto`        INT          NOT NULL AUTO_INCREMENT,
  `id_producto`    INT          NOT NULL,
  `id_usuario`     INT          DEFAULT NULL COMMENT 'Quien subió la foto',
  `nombre_archivo` VARCHAR(255) DEFAULT NULL,
  `ruta_archivo`   VARCHAR(500) DEFAULT NULL,
  `descripcion`    TEXT         DEFAULT NULL,
  `es_principal`   TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '1 = foto principal del producto',
  `fecha_subida`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_foto`),
  KEY `fk_pfoto_producto` (`id_producto`),
  KEY `fk_pfoto_usuario`  (`id_usuario`),
  CONSTRAINT `fk_pfoto_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE CASCADE,
  CONSTRAINT `fk_pfoto_usuario`  FOREIGN KEY (`id_usuario`)  REFERENCES `usuarios`  (`id_usuario`)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------
-- Restore settings
-- -----------------------------------------------------------
/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
