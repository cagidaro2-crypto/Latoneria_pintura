-- ============================================================
-- TABLAS FALTANTES Y CORRECCIONES DE ESQUEMA
-- Ejecutar en latoneria_pintura
-- ============================================================

USE `latoneria_pintura`;

-- ── 1. Ampliar correo en persona (varchar 20 → 100) ──────────────────────
ALTER TABLE `persona` MODIFY COLUMN `correo` varchar(100) COLLATE utf8mb4_general_ci NOT NULL;

-- ── 2. Agregar estado_pago a factura ─────────────────────────────────────
ALTER TABLE `factura`
    ADD COLUMN `estado_pago` ENUM('Pendiente','Pagada','Anulada') NOT NULL DEFAULT 'Pendiente'
    AFTER `total`;

-- ── 3. Tabla productos (para inventario de repuestos/materiales) ──────────
CREATE TABLE IF NOT EXISTS `productos` (
    `id_producto`  int          NOT NULL AUTO_INCREMENT,
    `nombre`       varchar(150) NOT NULL,
    `descripcion`  text         DEFAULT NULL,
    `precio`       decimal(12,2) NOT NULL DEFAULT 0,
    `categoria`    varchar(60)  DEFAULT NULL,
    `activo`       tinyint(1)   NOT NULL DEFAULT 1,
    `created_at`   datetime     NOT NULL DEFAULT NOW(),
    PRIMARY KEY (`id_producto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── 4. Redefinir inventario para soportar productos ───────────────────────
-- Primero eliminar la tabla vieja (sin datos útiles) y recrearla
DROP TABLE IF EXISTS `inventario`;
CREATE TABLE `inventario` (
    `id_inventario` int          NOT NULL AUTO_INCREMENT,
    `id_producto`   int          NOT NULL,
    `cantidad`      decimal(12,2) NOT NULL DEFAULT 0,
    `unidad`        varchar(20)  NOT NULL DEFAULT 'unidad',
    `stock_minimo`  decimal(12,2) NOT NULL DEFAULT 5,
    `updated_at`    datetime     NOT NULL DEFAULT NOW() ON UPDATE NOW(),
    PRIMARY KEY (`id_inventario`),
    FOREIGN KEY (`id_producto`) REFERENCES `productos`(`id_producto`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── 5. Tabla citas ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `citas` (
    `id_cita`       int          NOT NULL AUTO_INCREMENT,
    `numero_ref`    varchar(20)  NOT NULL,
    `id_cliente`    int          NOT NULL,
    `id_vehiculo`   int          DEFAULT NULL,
    `tipo_servicio` varchar(100) NOT NULL,
    `fecha_cita`    datetime     NOT NULL,
    `estado`        ENUM('Pendiente','Confirmada','Cancelada','Realizada') NOT NULL DEFAULT 'Pendiente',
    `created_at`    datetime     NOT NULL DEFAULT NOW(),
    PRIMARY KEY (`id_cita`),
    UNIQUE KEY `numero_ref` (`numero_ref`),
    FOREIGN KEY (`id_cliente`)  REFERENCES `persona`(`id_persona`) ON DELETE RESTRICT,
    FOREIGN KEY (`id_vehiculo`) REFERENCES `vehiculo`(`id_vehiculo`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── 6. Datos iniciales si no existen ─────────────────────────────────────
INSERT IGNORE INTO `roles` (`id_rol`, `nombre_rol`) VALUES
(1, 'administrador'), (2, 'cliente'), (3, 'empleado');

INSERT IGNORE INTO `estado_vehiculo` (`id_estado_vehiculo`, `estado`) VALUES
(1, 'Pendiente'), (2, 'En reparación'), (3, 'Pintura'), (4, 'Finalizado'), (5, 'Entregado');

INSERT IGNORE INTO `servicio` (`id_servicio`, `nombre`, `tipo_servicio`) VALUES
(1, 'Latonería',        'Latonería'),
(2, 'Pintura completa', 'Pintura'),
(3, 'Pintura parcial',  'Pintura'),
(4, 'Enderezado',       'Latonería'),
(5, 'Pulida y brillada','Acabado'),
(6, 'Revisión general', 'Revisión');
