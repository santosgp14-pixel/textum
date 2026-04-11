-- ============================================================
-- TEXTUM — Migration v1.6 — Kyte features
-- Fecha: 2026-04-11
-- IMPORTANTE: Solo aditiva, no modifica datos existentes.
-- ============================================================

-- 1. Tabla de proveedores
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `proveedores` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `empresa_id`  INT UNSIGNED NOT NULL,
  `nombre`      VARCHAR(100) NOT NULL,
  `cuit`        VARCHAR(20)  NULL DEFAULT NULL,
  `telefono`    VARCHAR(30)  NULL DEFAULT NULL,
  `email`       VARCHAR(100) NULL DEFAULT NULL,
  `notas`       TEXT         NULL DEFAULT NULL,
  `activo`      TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_proveedores_empresa` (`empresa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Gastos recurrentes (nuevas columnas en gastos)
-- -------------------------------------------------------
ALTER TABLE `gastos`
  ADD COLUMN IF NOT EXISTS `es_recurrente` TINYINT(1) NOT NULL DEFAULT 0 AFTER `fecha`,
  ADD COLUMN IF NOT EXISTS `frecuencia`    ENUM('diario','semanal','mensual') NULL DEFAULT NULL AFTER `es_recurrente`,
  ADD COLUMN IF NOT EXISTS `dia_cobro`     TINYINT NULL DEFAULT NULL AFTER `frecuencia`;

-- 3. Configuración pública de la empresa (nuevas columnas en empresas)
-- -------------------------------------------------------
ALTER TABLE `empresas`
  ADD COLUMN IF NOT EXISTS `descripcion_catalogo` TEXT         NULL DEFAULT NULL AFTER `activa`,
  ADD COLUMN IF NOT EXISTS `whatsapp`              VARCHAR(20)  NULL DEFAULT NULL AFTER `descripcion_catalogo`,
  ADD COLUMN IF NOT EXISTS `logo_url`              VARCHAR(255) NULL DEFAULT NULL AFTER `whatsapp`;
