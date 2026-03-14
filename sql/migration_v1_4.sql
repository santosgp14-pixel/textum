-- ============================================================
-- TEXTUM - Migración v1.4
    -- Agrega: rinde/barcode/precio/unidad/imagen en telas
--         sub-categorías de texto + tipo/parent_id en categorias
--         unidad 'rollo' en variantes
--         costo por rollo (barcode ya estaba en v1.3)
-- ============================================================

-- Nuevos campos en telas
ALTER TABLE `telas`
  ADD COLUMN `tipo`            ENUM('punto','plano') DEFAULT NULL
                               COMMENT 'Tipo de tejido: punto o plano'
                               AFTER `empresa_id`,
  ADD COLUMN `subcategoria`    ENUM('atemporal','invierno','verano') DEFAULT NULL
                               COMMENT 'Estación: atemporal, invierno, verano, primavera u otoño'
                               AFTER `categoria_id`,
  ADD COLUMN `rinde`           DECIMAL(8,3) DEFAULT NULL
                               COMMENT 'Metros que rinde 1 kilo de tela'
                               AFTER `subcategoria`,
  ADD COLUMN `precio`          DECIMAL(12,2) NOT NULL DEFAULT 0.00
                               COMMENT 'Precio de venta base por unidad'
                               AFTER `costo`,
  ADD COLUMN `unidad`          ENUM('metro','kilo','rollo') NOT NULL DEFAULT 'metro'
                               COMMENT 'Unidad de venta predeterminada'
                               AFTER `precio`,
  ADD COLUMN `imagen_url`      VARCHAR(500) DEFAULT NULL
                               COMMENT 'Ruta relativa de la imagen del producto'
                               AFTER `unidad`;

-- Sub-categorías y tipo en categorias
ALTER TABLE `categorias`
  ADD COLUMN `tipo`      ENUM('punto','plano') DEFAULT NULL
                         COMMENT 'Tipo de tejido de esta categoría'
                         AFTER `empresa_id`,
  ADD COLUMN `parent_id` INT UNSIGNED DEFAULT NULL
                         COMMENT 'Categoría padre para sub-categorías (NULL = categoría raíz)'
                         AFTER `tipo`,
  ADD CONSTRAINT `fk_cat_parent` FOREIGN KEY (`parent_id`) REFERENCES `categorias` (`id`);

-- Agregar 'rollo' como unidad válida en variantes
ALTER TABLE `variantes`
  MODIFY COLUMN `unidad` ENUM('metro','kilo','rollo') NOT NULL DEFAULT 'metro';

-- Costo de compra por rollo individual (barcode por rollo fue agregado en v1.3)
ALTER TABLE `rollos`
  ADD COLUMN `costo` DECIMAL(12,2) NOT NULL DEFAULT 0.00
                     COMMENT 'Costo de compra de este rollo'
                     AFTER `codigo_barras`;

-- Mínimo fraccionado configurable por producto
ALTER TABLE `telas`
  ADD COLUMN `minimo_venta` DECIMAL(10,3) NOT NULL DEFAULT 1.000
                            COMMENT 'Mínimo de venta fraccionado (metros o kilos según unidad)'
                            AFTER `unidad`;
