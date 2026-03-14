-- ============================================================
-- TEXTUM - Migración v1.4
-- Agrega: tipo/barcode/costo/precio/unidad/imagen en telas
--         sub-categorías (parent_id en categorias)
--         unidad 'rollo' en variantes
-- ============================================================

-- Nuevos campos en telas
ALTER TABLE `telas`
  ADD COLUMN `tipo`            ENUM('punto','plano') DEFAULT NULL
                               COMMENT 'Tipo de tejido: punto (jersey, polar) o plano (denim, poplin)'
                               AFTER `categoria_id`,
  ADD COLUMN `subcategoria_id` INT UNSIGNED DEFAULT NULL
                               COMMENT 'Sub-categoría del producto'
                               AFTER `tipo`,
  ADD COLUMN `codigo_barras`   VARCHAR(100) DEFAULT NULL
                               COMMENT 'Código de barras del producto base'
                               AFTER `composicion`,
  ADD COLUMN `costo`           DECIMAL(12,2) NOT NULL DEFAULT 0.00
                               COMMENT 'Precio de costo por unidad'
                               AFTER `codigo_barras`,
  ADD COLUMN `precio`          DECIMAL(12,2) NOT NULL DEFAULT 0.00
                               COMMENT 'Precio de venta base por unidad'
                               AFTER `costo`,
  ADD COLUMN `unidad`          ENUM('metro','kilo','rollo') NOT NULL DEFAULT 'metro'
                               COMMENT 'Unidad de venta predeterminada'
                               AFTER `precio`,
  ADD COLUMN `imagen_url`      VARCHAR(500) DEFAULT NULL
                               COMMENT 'Ruta relativa de la imagen del producto'
                               AFTER `unidad`,
  ADD CONSTRAINT `fk_tela_subcategoria` FOREIGN KEY (`subcategoria_id`) REFERENCES `categorias` (`id`);

-- Sub-categorías: parent_id en categorias
ALTER TABLE `categorias`
  ADD COLUMN `parent_id` INT UNSIGNED DEFAULT NULL
                         COMMENT 'Categoría padre para sub-categorías (NULL = categoría raíz)'
                         AFTER `empresa_id`,
  ADD CONSTRAINT `fk_cat_parent` FOREIGN KEY (`parent_id`) REFERENCES `categorias` (`id`);

-- Agregar 'rollo' como unidad válida en variantes
ALTER TABLE `variantes`
  MODIFY COLUMN `unidad` ENUM('metro','kilo','rollo') NOT NULL DEFAULT 'metro';
