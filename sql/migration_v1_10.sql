-- ============================================================
-- TEXTUM — Migration v1.10 — Método de pago y seña en pedidos
-- Fecha: 2026-06-06
-- IMPORTANTE: Solo aditiva, no modifica datos existentes.
-- ============================================================

ALTER TABLE `pedidos`
  ADD COLUMN IF NOT EXISTS `metodo_pago` ENUM('efectivo','transferencia','tarjeta','cuenta_corriente','otro') NULL DEFAULT NULL
    COMMENT 'Método de pago al confirmar el pedido'
    AFTER `total`,
  ADD COLUMN IF NOT EXISTS `seña` DECIMAL(14,2) NOT NULL DEFAULT 0.00
    COMMENT 'Adelanto o seña abonada por el cliente'
    AFTER `metodo_pago`;
