-- ============================================================================
-- BancoTrack - Esquema inicial para MySQL / MariaDB (XAMPP)
-- Codificación: utf8mb4_unicode_ci
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `bancotrack`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `bancotrack`;

-- ----------------------------------------------------------------------------
-- Tabla: roles
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `roles` (
  `id` TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(30) NOT NULL,
  `descripcion` VARCHAR(150) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_roles_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Tabla: usuarios
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `email` VARCHAR(120) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `rol_id` TINYINT UNSIGNED NOT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_usuarios_email` (`email`),
  KEY `idx_usuarios_rol` (`rol_id`),
  CONSTRAINT `fk_usuarios_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Tabla: clientes
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `clientes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `rut` VARCHAR(12) NOT NULL,
  `nombre_completo` VARCHAR(150) NOT NULL,
  `email` VARCHAR(120) DEFAULT NULL,
  `telefono` VARCHAR(25) DEFAULT NULL,
  `comuna` VARCHAR(80) DEFAULT NULL,
  `region` VARCHAR(80) DEFAULT NULL,
  `segmento` ENUM('Retail', 'Preferente', 'Pyme', 'Corporativo') NOT NULL DEFAULT 'Retail',
  `estado` ENUM('Activo', 'En Evaluación', 'Inactivo') NOT NULL DEFAULT 'Activo',
  `ejecutivo_id` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_clientes_rut` (`rut`),
  KEY `idx_clientes_segmento` (`segmento`),
  KEY `idx_clientes_ejecutivo` (`ejecutivo_id`),
  CONSTRAINT `fk_clientes_ejecutivo` FOREIGN KEY (`ejecutivo_id`) REFERENCES `usuarios` (`id`)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Tabla: gestiones
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `gestiones` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `cliente_id` INT UNSIGNED NOT NULL,
  `usuario_id` INT UNSIGNED NOT NULL,
  `tipo_gestion` ENUM('Llamada', 'Reunión', 'Evaluación Crediticia', 'Seguimiento') NOT NULL,
  `estado` ENUM('Pendiente', 'En Proceso', 'Cerrada') NOT NULL DEFAULT 'Pendiente',
  `monto_estimado_clp` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `observaciones` TEXT,
  `fecha_gestion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_gestiones_cliente` (`cliente_id`),
  KEY `idx_gestiones_usuario` (`usuario_id`),
  CONSTRAINT `fk_gestiones_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_gestiones_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Tabla: importaciones_csv
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `importaciones_csv` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED NOT NULL,
  `nombre_archivo` VARCHAR(180) NOT NULL,
  `registros_procesados` INT UNSIGNED NOT NULL DEFAULT 0,
  `registros_error` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_importaciones_usuario` (`usuario_id`),
  CONSTRAINT `fk_importaciones_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Datos semilla: roles y usuarios demo
-- Contraseña demo de referencia: "BancoTrack2026!" (hash bcrypt)
-- ----------------------------------------------------------------------------
INSERT INTO `roles` (`id`, `nombre`, `descripcion`) VALUES
  (1, 'admin', 'Administrador del sistema con acceso total a configuración, usuarios y auditoría'),
  (2, 'ejecutivo', 'Ejecutivo comercial encargado de cartera de clientes, gestiones e importación CSV'),
  (3, 'auditor', 'Auditor interno con acceso de solo lectura a reportes, gestiones y trazabilidad')
ON DUPLICATE KEY UPDATE `descripcion` = VALUES(`descripcion`);

INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password_hash`, `rol_id`, `activo`) VALUES
  (1, 'Administrador Demo', 'admin@bancotrack.cl', '$2y$12$TBvxlirFjzakFbwf3E76v..evfkMa/9fGV2zMpOvVNgaR7I/NgibC', 1, 1),
  (2, 'Ejecutivo Comercial Demo', 'ejecutivo@bancotrack.cl', '$2y$12$TBvxlirFjzakFbwf3E76v..evfkMa/9fGV2zMpOvVNgaR7I/NgibC', 2, 1),
  (3, 'Auditor Interno Demo', 'auditor@bancotrack.cl', '$2y$12$TBvxlirFjzakFbwf3E76v..evfkMa/9fGV2zMpOvVNgaR7I/NgibC', 3, 1)
ON DUPLICATE KEY UPDATE
  `nombre` = VALUES(`nombre`),
  `password_hash` = VALUES(`password_hash`),
  `rol_id` = VALUES(`rol_id`);
