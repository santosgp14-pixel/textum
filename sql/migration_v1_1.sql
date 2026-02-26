-- ============================================================
-- TEXTUM - Migración v1.1
-- Agrega: costo, precio_rollo en variantes + tabla rollos
-- ============================================================

-- Nuevos campos en variantes
ALTER TABLE `variantes`
  ADD COLUMN `costo`        DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Costo de compra' AFTER `precio`,
  ADD COLUMN `precio_rollo` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Precio de venta por rollo completo' AFTER `costo`;

-- Tabla: rollos (sub-variantes: rollos físicos individuales)
CREATE TABLE IF NOT EXISTS `rollos` (
  `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `variante_id` INT UNSIGNED    NOT NULL,
  `empresa_id`  INT UNSIGNED    NOT NULL,
  `nro_rollo`   VARCHAR(60)     DEFAULT NULL COMMENT 'Número o código identificatorio del rollo',
  `metros`      DECIMAL(10,3)   NOT NULL DEFAULT 0.000 COMMENT 'Metros o kilos de este rollo',
  `estado`      ENUM('disponible','agotado') NOT NULL DEFAULT 'disponible',
  `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_rollo_variante` FOREIGN KEY (`variante_id`) REFERENCES `variantes` (`id`),
  CONSTRAINT `fk_rollo_empresa`  FOREIGN KEY (`empresa_id`)  REFERENCES `empresas`  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Rollos físicos individuales de cada variante. Gestiona stock real.';
