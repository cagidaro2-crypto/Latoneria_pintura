-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Versión del servidor:         8.0.30 - MySQL Community Server - GPL
-- SO del servidor:              Win64
-- HeidiSQL Versión:             12.1.0.6537
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Volcando estructura de base de datos para latoneria_pintura
CREATE DATABASE IF NOT EXISTS `latoneria_pintura` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `latoneria_pintura`;

-- Volcando estructura para tabla latoneria_pintura.administrador
CREATE TABLE IF NOT EXISTS `administrador` (
  `id_administrador` int NOT NULL AUTO_INCREMENT,
  `id_persona` int NOT NULL,
  PRIMARY KEY (`id_administrador`),
  UNIQUE KEY `id_persona` (`id_persona`),
  CONSTRAINT `fk_admin_persona` FOREIGN KEY (`id_persona`) REFERENCES `persona` (`id_persona`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla latoneria_pintura.cliente
CREATE TABLE IF NOT EXISTS `cliente` (
  `id_cliente` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `direccion` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `correo` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id_cliente`),
  UNIQUE KEY `correo` (`correo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla latoneria_pintura.cotizacion
CREATE TABLE IF NOT EXISTS `cotizacion` (
  `id_cotizacion` int NOT NULL AUTO_INCREMENT,
  `fecha` date NOT NULL,
  `pago_total` decimal(10,2) DEFAULT '0.00',
  `id_vehiculo` int NOT NULL,
  PRIMARY KEY (`id_cotizacion`),
  KEY `fk_cotizacion_vehiculo` (`id_vehiculo`),
  CONSTRAINT `fk_cotizacion_vehiculo` FOREIGN KEY (`id_vehiculo`) REFERENCES `vehiculo` (`id_vehiculo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla latoneria_pintura.detalle_repuesto
CREATE TABLE IF NOT EXISTS `detalle_repuesto` (
  `id_detalle_repuesto` int NOT NULL AUTO_INCREMENT,
  `cantidad` int NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `id_cotizacion` int NOT NULL,
  `id_repuesto` int NOT NULL,
  PRIMARY KEY (`id_detalle_repuesto`),
  KEY `fk_detalle_repuesto` (`id_repuesto`),
  KEY `fk_detalle_cotizacion_repuesto` (`id_cotizacion`),
  CONSTRAINT `fk_detalle_cotizacion_repuesto` FOREIGN KEY (`id_cotizacion`) REFERENCES `cotizacion` (`id_cotizacion`),
  CONSTRAINT `fk_detalle_repuesto` FOREIGN KEY (`id_repuesto`) REFERENCES `repuesto` (`id_repuesto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla latoneria_pintura.detalle_servicio
CREATE TABLE IF NOT EXISTS `detalle_servicio` (
  `id_detalle_servicio` int NOT NULL AUTO_INCREMENT,
  `precio` decimal(10,2) NOT NULL,
  `cantidad` int NOT NULL,
  `id_servicio` int NOT NULL,
  `id_cotizacion` int NOT NULL,
  PRIMARY KEY (`id_detalle_servicio`),
  KEY `fk_detalle_servicio` (`id_servicio`),
  KEY `fk_detalle_cotizacion_servicio` (`id_cotizacion`),
  CONSTRAINT `fk_detalle_cotizacion_servicio` FOREIGN KEY (`id_cotizacion`) REFERENCES `cotizacion` (`id_cotizacion`),
  CONSTRAINT `fk_detalle_servicio` FOREIGN KEY (`id_servicio`) REFERENCES `servicio` (`id_servicio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla latoneria_pintura.empleado
CREATE TABLE IF NOT EXISTS `empleado` (
  `id_empleado` int NOT NULL AUTO_INCREMENT,
  `id_persona` int NOT NULL,
  PRIMARY KEY (`id_empleado`),
  UNIQUE KEY `id_persona` (`id_persona`),
  CONSTRAINT `fk_empleado_persona` FOREIGN KEY (`id_persona`) REFERENCES `persona` (`id_persona`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla latoneria_pintura.estado_vehiculo
CREATE TABLE IF NOT EXISTS `estado_vehiculo` (
  `id_estado_vehiculo` int NOT NULL AUTO_INCREMENT,
  `estado` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_estado_vehiculo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla latoneria_pintura.factura
CREATE TABLE IF NOT EXISTS `factura` (
  `id_factura` int NOT NULL AUTO_INCREMENT,
  `fecha` date NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `id_cotizacion` int NOT NULL,
  PRIMARY KEY (`id_factura`),
  KEY `fk_factura_cotizacion` (`id_cotizacion`),
  CONSTRAINT `fk_factura_cotizacion` FOREIGN KEY (`id_cotizacion`) REFERENCES `cotizacion` (`id_cotizacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla latoneria_pintura.historial_servicio
CREATE TABLE IF NOT EXISTS `historial_servicio` (
  `id_historial_servicio` int NOT NULL AUTO_INCREMENT,
  `descripcion` text COLLATE utf8mb4_general_ci,
  `fecha_registro` date NOT NULL,
  `tipo_reparacion` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_historial_vehiculo` int NOT NULL,
  `id_servicio` int NOT NULL,
  PRIMARY KEY (`id_historial_servicio`),
  KEY `fk_historial_servicio_historial` (`id_historial_vehiculo`),
  KEY `fk_historial_servicio_servicio` (`id_servicio`),
  CONSTRAINT `fk_historial_servicio_historial` FOREIGN KEY (`id_historial_vehiculo`) REFERENCES `historial_vehiculo` (`id_historial_vehiculo`),
  CONSTRAINT `fk_historial_servicio_servicio` FOREIGN KEY (`id_servicio`) REFERENCES `servicio` (`id_servicio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla latoneria_pintura.historial_vehiculo
CREATE TABLE IF NOT EXISTS `historial_vehiculo` (
  `id_historial_vehiculo` int NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_registro` date NOT NULL,
  `tipo_reparacion` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `id_empleado` int NOT NULL,
  `id_vehiculo` int NOT NULL,
  PRIMARY KEY (`id_historial_vehiculo`),
  KEY `fk_historial_empleado` (`id_empleado`),
  KEY `fk_historial_vehiculo` (`id_vehiculo`),
  CONSTRAINT `fk_historial_empleado` FOREIGN KEY (`id_empleado`) REFERENCES `empleado` (`id_empleado`),
  CONSTRAINT `fk_historial_vehiculo` FOREIGN KEY (`id_vehiculo`) REFERENCES `vehiculo` (`id_vehiculo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla latoneria_pintura.inventario
CREATE TABLE IF NOT EXISTS `inventario` (
  `id_inventario` int NOT NULL AUTO_INCREMENT,
  `stock` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cantidad` int DEFAULT NULL,
  `tipo` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `entrada` varchar(40) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `salida` varchar(40) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id_inventario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla latoneria_pintura.pago
CREATE TABLE IF NOT EXISTS `pago` (
  `id_pago` int NOT NULL AUTO_INCREMENT,
  `fecha` date NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `metodo` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `id_factura` int NOT NULL,
  PRIMARY KEY (`id_pago`),
  KEY `fk_pago_factura` (`id_factura`),
  CONSTRAINT `fk_pago_factura` FOREIGN KEY (`id_factura`) REFERENCES `factura` (`id_factura`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla latoneria_pintura.persona
CREATE TABLE IF NOT EXISTS `persona` (
  `id_persona` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `contraseña` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `correo` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `id_rol` int NOT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `intentos_fallidos` int DEFAULT '0',
  `bloqueado_hasta` datetime DEFAULT NULL,
  PRIMARY KEY (`id_persona`),
  KEY `fk_persona_rol` (`id_rol`),
  CONSTRAINT `fk_persona_rol` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla latoneria_pintura.proveedor
CREATE TABLE IF NOT EXISTS `proveedor` (
  `id_proveedor` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `correo` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id_proveedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla latoneria_pintura.repuesto
CREATE TABLE IF NOT EXISTS `repuesto` (
  `id_repuesto` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `cantidad` int NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `id_inventario` int NOT NULL,
  `id_proveedor` int NOT NULL,
  PRIMARY KEY (`id_repuesto`),
  KEY `fk_repuesto_inventario` (`id_inventario`),
  KEY `fk_repuesto_proveedor` (`id_proveedor`),
  CONSTRAINT `fk_repuesto_inventario` FOREIGN KEY (`id_inventario`) REFERENCES `inventario` (`id_inventario`),
  CONSTRAINT `fk_repuesto_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedor` (`id_proveedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla latoneria_pintura.roles
CREATE TABLE IF NOT EXISTS `roles` (
  `id_rol` int NOT NULL AUTO_INCREMENT,
  `nombre_rol` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_rol`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla latoneria_pintura.servicio
CREATE TABLE IF NOT EXISTS `servicio` (
  `id_servicio` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `tipo_servicio` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_servicio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla latoneria_pintura.vehiculo
CREATE TABLE IF NOT EXISTS `vehiculo` (
  `id_vehiculo` int NOT NULL AUTO_INCREMENT,
  `placa` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `modelo` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `marca` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `año` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `id_cliente` int NOT NULL,
  `id_estado_vehiculo` int NOT NULL,
  PRIMARY KEY (`id_vehiculo`),
  UNIQUE KEY `placa` (`placa`),
  KEY `fk_vehiculo_cliente` (`id_cliente`),
  KEY `fk_vehiculo_estado` (`id_estado_vehiculo`),
  CONSTRAINT `fk_vehiculo_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`),
  CONSTRAINT `fk_vehiculo_estado` FOREIGN KEY (`id_estado_vehiculo`) REFERENCES `estado_vehiculo` (`id_estado_vehiculo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;


-- ============================================================
-- TABLAS ADICIONALES REQUERIDAS POR EL SISTEMA
-- ============================================================

-- ── Ampliar correo en persona (varchar 20 → 100) ─────────────────────────
ALTER TABLE `persona` MODIFY COLUMN `correo` varchar(100) COLLATE utf8mb4_general_ci NOT NULL;

-- ── Agregar estado_pago a factura ─────────────────────────────────────────
ALTER TABLE `factura`
    ADD COLUMN IF NOT EXISTS `estado_pago`
    ENUM('Pendiente','Pagada','Anulada') NOT NULL DEFAULT 'Pendiente'
    AFTER `total`;

-- ── Tabla: productos ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `productos` (
  `id_producto`  int           NOT NULL AUTO_INCREMENT,
  `nombre`       varchar(150)  COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion`  text          COLLATE utf8mb4_general_ci DEFAULT NULL,
  `precio`       decimal(12,2) NOT NULL DEFAULT '0.00',
  `categoria`    varchar(60)   COLLATE utf8mb4_general_ci DEFAULT NULL,
  `activo`       tinyint(1)    NOT NULL DEFAULT '1',
  `created_at`   datetime      NOT NULL DEFAULT NOW(),
  PRIMARY KEY (`id_producto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── Redefinir inventario para soportar productos ──────────────────────────
-- (la tabla original solo tenía stock/tipo/entrada/salida sin FK)
DROP TABLE IF EXISTS `inventario`;
CREATE TABLE `inventario` (
  `id_inventario` int           NOT NULL AUTO_INCREMENT,
  `id_producto`   int           NOT NULL,
  `cantidad`      decimal(12,2) NOT NULL DEFAULT '0.00',
  `unidad`        varchar(20)   COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'unidad',
  `stock_minimo`  decimal(12,2) NOT NULL DEFAULT '5.00',
  `updated_at`    datetime      NOT NULL DEFAULT NOW() ON UPDATE NOW(),
  PRIMARY KEY (`id_inventario`),
  KEY `fk_inv_producto` (`id_producto`),
  CONSTRAINT `fk_inv_producto`
    FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── Tabla: ventas ─────────────────────────────────────────────────────────
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

-- ── Tabla: detalle_venta ──────────────────────────────────────────────────
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

-- ── Tabla: citas ──────────────────────────────────────────────────────────
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

-- ── Datos iniciales ───────────────────────────────────────────────────────
INSERT IGNORE INTO `roles` (`id_rol`, `nombre_rol`) VALUES
  (1, 'administrador'),
  (2, 'cliente'),
  (3, 'empleado');

INSERT IGNORE INTO `estado_vehiculo` (`id_estado_vehiculo`, `estado`) VALUES
  (1, 'Pendiente'),
  (2, 'En reparación'),
  (3, 'Pintura'),
  (4, 'Finalizado'),
  (5, 'Entregado');

INSERT IGNORE INTO `servicio` (`id_servicio`, `nombre`, `tipo_servicio`) VALUES
  (1, 'Latonería',        'Latonería'),
  (2, 'Pintura completa', 'Pintura'),
  (3, 'Pintura parcial',  'Pintura'),
  (4, 'Enderezado',       'Latonería'),
  (5, 'Pulida y brillada','Acabado'),
  (6, 'Revisión general', 'Revisión');
