-- ============================================================
-- DATOS INICIALES REQUERIDOS PARA EL SISTEMA
-- Ejecutar en latoneria_pintura después del schema principal
-- ============================================================

USE `latoneria_pintura`;

-- ── Roles ─────────────────────────────────────────────────────────────────
INSERT IGNORE INTO `roles` (`id_rol`, `nombre_rol`) VALUES
(1, 'administrador'),
(2, 'cliente'),
(3, 'empleado');

-- ── Estados de vehículo ───────────────────────────────────────────────────
INSERT IGNORE INTO `estado_vehiculo` (`id_estado_vehiculo`, `estado`) VALUES
(1, 'Pendiente'),
(2, 'En reparación'),
(3, 'Pintura'),
(4, 'Finalizado'),
(5, 'Entregado');

-- ── Servicios base ────────────────────────────────────────────────────────
INSERT IGNORE INTO `servicio` (`id_servicio`, `nombre`, `tipo_servicio`) VALUES
(1, 'Latonería',        'Latonería'),
(2, 'Pintura completa', 'Pintura'),
(3, 'Pintura parcial',  'Pintura'),
(4, 'Enderezado',       'Latonería'),
(5, 'Pulida y brillada','Acabado'),
(6, 'Revisión general', 'Revisión');

-- ── Usuario administrador por defecto ─────────────────────────────────────
-- Contraseña: Admin1234!
INSERT IGNORE INTO `persona`
    (`id_persona`, `nombre`, `contraseña`, `correo`, `telefono`, `id_rol`, `activo`)
VALUES
    (1, 'Administrador', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
     'admin@taller.com', '3001234567', 1, 1);
