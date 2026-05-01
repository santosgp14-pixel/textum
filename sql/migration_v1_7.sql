-- ============================================================
-- TEXTUM — Migration v1.7 — Precio fraccionado en variantes
-- Fecha: 2026-05-01
-- IMPORTANTE: Solo aditiva, no modifica datos existentes.
-- ============================================================

-- Agrega precio_fraccionado a variantes.
-- Representa el precio de venta cuando se vende de a metros/kilos (fraccionado).
-- Valor sugerido: precio_base + 15%. Se puede editar manualmente.
ALTER TABLE `variantes`
  ADD COLUMN IF NOT EXISTS `precio_fraccionado` DECIMAL(12,2) NOT NULL DEFAULT 0.00
  AFTER `precio`;

-- Inicializar con precio * 1.15 para filas existentes que tengan precio > 0
UPDATE `variantes`
  SET `precio_fraccionado` = ROUND(`precio` * 1.15, 2)
  WHERE `precio` > 0 AND `precio_fraccionado` = 0;
