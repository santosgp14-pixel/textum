-- ============================================================
-- TEXTUM - Migración v1.5
-- Agrega: rollo_id en pedido_items para rastrear qué rollo
--         físico fue vendido y marcarlo como agotado al confirmar
-- ============================================================

ALTER TABLE `pedido_items`
  ADD COLUMN `rollo_id` INT UNSIGNED DEFAULT NULL
             COMMENT 'Rollo físico vendido (NULL si se vendió fraccionado sin rollo específico)'
             AFTER `variante_id`,
  ADD CONSTRAINT `fk_item_rollo` FOREIGN KEY (`rollo_id`) REFERENCES `rollos` (`id`);
