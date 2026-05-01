-- =============================================================
-- TEXTUM migration v1.9
-- 1) Agrega rollo_id a movimientos_stock (nullable)
-- 2) Corrige metros históricos de rollos para pedidos ya confirmados
--    EJECUTAR UNA SOLA VEZ en producción antes de confirmar nuevos pedidos
-- =============================================================

-- 1. Agregar columna rollo_id
ALTER TABLE movimientos_stock
  ADD COLUMN IF NOT EXISTS rollo_id INT UNSIGNED NULL DEFAULT NULL AFTER pedido_id;

-- 2. Corregir metros de rollos afectados por pedidos ya confirmados
--    (compensa pedidos confirmados ANTES de que el código actualizara rollos.metros)
UPDATE rollos r
INNER JOIN (
    SELECT pi.rollo_id, SUM(pi.cantidad) AS total_vendido
    FROM pedido_items pi
    JOIN pedidos p ON p.id = pi.pedido_id
    WHERE p.estado = 'confirmado'
      AND pi.rollo_id IS NOT NULL
    GROUP BY pi.rollo_id
) v ON v.rollo_id = r.id
SET r.metros = GREATEST(0, r.metros - v.total_vendido);
