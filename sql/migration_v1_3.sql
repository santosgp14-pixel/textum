-- ============================================================
-- TEXTUM - Migración v1.3
-- Agrega: tabla categorias + categoria_id en telas
--         + codigo_barras individual por rollo
-- ============================================================

-- Tabla: categorias
CREATE TABLE IF NOT EXISTS `categorias` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `empresa_id`  INT UNSIGNED  NOT NULL,
  `nombre`      VARCHAR(80)   NOT NULL,
  `orden`       TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Orden visual en la lista',
  `activa`      TINYINT(1)    NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_cat_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Agrupador de productos textiles (ej: Bengalinas, Denim, Lisos)';

-- Vincular productos a categoría
ALTER TABLE `telas`
  ADD COLUMN `categoria_id` INT UNSIGNED DEFAULT NULL COMMENT 'Categoría del producto' AFTER `empresa_id`,
  ADD CONSTRAINT `fk_tela_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`);

-- Código de barras individual por rollo físico
ALTER TABLE `rollos`
  ADD COLUMN `codigo_barras` VARCHAR(60) DEFAULT NULL COMMENT 'Código único por rollo físico para escaneo en pedidos' AFTER `nro_rollo`,
  ADD UNIQUE KEY `uq_rollo_barras_empresa` (`codigo_barras`, `empresa_id`);
