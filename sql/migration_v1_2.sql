-- ============================================================
-- TEXTUM - Migración v1.2
-- Agrega: tabla clientes + cliente_id en pedidos
-- ============================================================

-- Tabla: clientes
CREATE TABLE IF NOT EXISTS `clientes` (
  `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `empresa_id`  INT UNSIGNED    NOT NULL,
  `nombre`      VARCHAR(120)    NOT NULL,
  `telefono`    VARCHAR(30)     DEFAULT NULL,
  `email`       VARCHAR(100)    DEFAULT NULL,
  `notas`       TEXT            DEFAULT NULL,
  `activo`      TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_cliente_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Clientes de cada empresa para seguimiento de compras';

-- Agregar cliente_id a pedidos (era Fase 2 en el schema original)
ALTER TABLE `pedidos`
  ADD COLUMN `cliente_id` INT UNSIGNED DEFAULT NULL COMMENT 'Cliente asociado. NULL = venta sin identificar' AFTER `usuario_id`,
  ADD CONSTRAINT `fk_pedido_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`);
