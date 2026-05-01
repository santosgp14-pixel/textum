-- ============================================================
-- TEXTUM — Migration v1.8 — Campo ancho en telas
-- Fecha: 2026-05-01
-- IMPORTANTE: Solo aditiva, no modifica datos existentes.
-- ============================================================

-- Agrega el ancho de la tela (en metros) a la tabla telas.
ALTER TABLE `telas`
  ADD COLUMN IF NOT EXISTS `ancho` DECIMAL(6,3) NULL DEFAULT NULL
    COMMENT 'Ancho de la tela en metros, ej: 1.500'
    AFTER `rinde`;
