-- ============================================================
-- MIGRACIÓN SEGURA v3 — latoneria_pintura
-- Compatible con MySQL 8.0 (Laragon)
-- SIN usar ADD COLUMN IF NOT EXISTS (no soportado en MySQL)
-- ============================================================

USE `latoneria_pintura`;
SET FOREIGN_KEY_CHECKS = 0;

-- ── 1. Ampliar correo en persona (varchar 20 → 100) ───────────────────────
ALTER TABLE `persona`
  MODIFY COLUMN `correo` varchar(100) COLLATE utf8mb4_general_ci NOT NULL;

-- ── 2. Agregar estado_pago a factura ──────────────────────────────────────
-- Usamos procedimiento para verificar si ya existe antes de agregar
DROP PROCEDURE IF EXISTS `sp_add_estado_pago`;
DELIMITER $$
CREATE PROCEDURE `sp_add_estado_pago`()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'factura'
      AND COLUMN_NAME  = 'estado_pago'
  ) THEN
    ALTER TABLE `factura`
      ADD COLUMN `estado_pago`
      ENUM('Pendiente','Pagada','Anulada') NOT NULL DEFAULT 'Pendiente'
      AFTER `total`;
  END IF;
END$$
DELIMITER ;
CALL `sp_add_estado_pago`();
DROP PROCEDURE IF EXISTS `sp_add_estado_pago`;

-- ── 3. Tabla productos ────────────────────────────────────────────────────
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

-- ── 4. Migrar inventario (vieja estructura → nueva) ───────────────────────
DROP PROCEDURE IF EXISTS `sp_migrar_inventario`;
DELIMITER $$
CREATE PROCEDURE `sp_migrar_inventario`()
BEGIN
  -- Verificar si inventario ya tiene la nueva estructura
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'inventario'
      AND COLUMN_NAME  = 'id_producto'
  ) THEN

    -- Crear tabla nueva con estructura correcta
    CREATE TABLE `inventario_nuevo` (
      `id_inventario` int           NOT NULL AUTO_INCREMENT,
      `id_producto`   int           NOT NULL,
      `cantidad`      decimal(12,2) NOT NULL DEFAULT '0.00',
      `unidad`        varchar(20)   COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'unidad',
      `stock_minimo`  decimal(12,2) NOT NULL DEFAULT '5.00',
      `updated_at`    datetime      NOT NULL DEFAULT NOW() ON UPDATE NOW(),
      PRIMARY KEY (`id_inventario`),
      KEY `fk_inv_prod` (`id_producto`),
      CONSTRAINT `fk_inv_prod`
        FOREIGN KEY (`id_producto`) REFERENCES `productos`(`id_producto`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

    -- Migrar datos: cada fila de inventario viejo → un producto + stock
    INSERT INTO `productos` (`nombre`, `descripcion`, `precio`, `categoria`, `activo`, `created_at`)
    SELECT
      CASE WHEN TRIM(COALESCE(`tipo`,'')) = ''
           THEN CONCAT('Producto-', `id_inventario`)
           ELSE `tipo`
      END,
      CONCAT('Migrado. Stock:', COALESCE(`stock`,''),
             ' | Entrada:', COALESCE(`entrada`,''),
             ' | Salida:',  COALESCE(`salida`,'')),
      0.00,
      `tipo`,
      1,
      NOW()
    FROM `inventario`;

    -- Insertar en inventario_nuevo
    INSERT INTO `inventario_nuevo` (`id_producto`, `cantidad`, `unidad`, `stock_minimo`)
    SELECT
      p.id_producto,
      COALESCE(i.cantidad, 0),
      'unidad',
      5
    FROM `inventario` i
    JOIN `productos` p
      ON p.nombre = CASE WHEN TRIM(COALESCE(i.`tipo`,'')) = ''
                         THEN CONCAT('Producto-', i.`id_inventario`)
                         ELSE i.`tipo`
                    END;

    -- Renombrar: vieja → backup, nueva → inventario
    RENAME TABLE `inventario` TO `inventario_backup_old`,
                 `inventario_nuevo` TO `inventario`;

  END IF;
END$$
DELIMITER ;
CALL `sp_migrar_inventario`();
DROP PROCEDURE IF EXISTS `sp_migrar_inventario`;

-- ── 5. Tabla ventas ───────────────────────────────────────────────────────
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
  KEY `fk_venta_cli` (`id_cliente`),
  KEY `fk_venta_emp` (`id_empleado`),
  CONSTRAINT `fk_venta_cli`
    FOREIGN KEY (`id_cliente`)  REFERENCES `cliente`  (`id_cliente`)  ON DELETE RESTRICT,
  CONSTRAINT `fk_venta_emp`
    FOREIGN KEY (`id_empleado`) REFERENCES `empleado` (`id_empleado`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── 6. Tabla detalle_venta ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `detalle_venta` (
  `id_detalle`  int           NOT NULL AUTO_INCREMENT,
  `id_venta`    int           NOT NULL,
  `id_producto` int           NOT NULL,
  `cantidad`    decimal(12,2) NOT NULL DEFAULT '1.00',
  `precio_unit` decimal(12,2) NOT NULL DEFAULT '0.00',
  `subtotal`    decimal(12,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id_detalle`),
  KEY `fk_dv_venta` (`id_venta`),
  KEY `fk_dv_prod`  (`id_producto`),
  CONSTRAINT `fk_dv_venta`
    FOREIGN KEY (`id_venta`)    REFERENCES `ventas`    (`id_venta`)    ON DELETE CASCADE,
  CONSTRAINT `fk_dv_prod`
    FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── 7. Tabla citas ────────────────────────────────────────────────────────
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
  KEY `fk_cita_cli` (`id_cliente`),
  KEY `fk_cita_veh` (`id_vehiculo`),
  CONSTRAINT `fk_cita_cli`
    FOREIGN KEY (`id_cliente`)  REFERENCES `persona`  (`id_persona`)  ON DELETE RESTRICT,
  CONSTRAINT `fk_cita_veh`
    FOREIGN KEY (`id_vehiculo`) REFERENCES `vehiculo` (`id_vehiculo`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── 8. Datos iniciales (INSERT IGNORE = no duplica) ───────────────────────
INSERT IGNORE INTO `roles` (`id_rol`, `nombre_rol`) VALUES
  (1,'administrador'),(2,'cliente'),(3,'empleado');

INSERT IGNORE INTO `estado_vehiculo` (`id_estado_vehiculo`, `estado`) VALUES
  (1,'Pendiente'),(2,'En reparación'),(3,'Pintura'),(4,'Finalizado'),(5,'Entregado');

INSERT IGNORE INTO `servicio` (`id_servicio`, `nombre`, `tipo_servicio`) VALUES
  (1,'Latonería','Latonería'),(2,'Pintura completa','Pintura'),
  (3,'Pintura parcial','Pintura'),(4,'Enderezado','Latonería'),
  (5,'Pulida y brillada','Acabado'),(6,'Revisión general','Revisión');

SET FOREIGN_KEY_CHECKS = 1;

-- ── 9. Verificación ───────────────────────────────────────────────────────
SELECT TABLE_NAME AS `Tabla`, TABLE_ROWS AS `Filas`
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'latoneria_pintura'
ORDER BY TABLE_NAME;
