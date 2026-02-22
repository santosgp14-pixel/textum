-- ============================================================
-- TEXTUM - Sistema de Gestión Textil
-- Schema v1.0 - Fase 1
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Tabla: empresas (multi-tenant base)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `empresas` (
  `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `nombre`      VARCHAR(120)    NOT NULL,
  `cuit`        VARCHAR(20)     DEFAULT NULL,
  `email`       VARCHAR(100)    DEFAULT NULL,
  `telefono`    VARCHAR(30)     DEFAULT NULL,
  `direccion`   VARCHAR(200)    DEFAULT NULL,
  `activa`      TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Multi-tenant: cada empresa es un tenant independiente';

-- ------------------------------------------------------------
-- Tabla: usuarios
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id`           INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `empresa_id`   INT UNSIGNED   NOT NULL,
  `nombre`       VARCHAR(80)    NOT NULL,
  `email`        VARCHAR(100)   NOT NULL,
  `password`     VARCHAR(255)   NOT NULL,
  -- Roles: admin | vendedor | supervisor (Fase 2: más roles)
  `rol`          ENUM('admin','vendedor','supervisor') NOT NULL DEFAULT 'vendedor',
  `activo`       TINYINT(1)     NOT NULL DEFAULT 1,
  `ultimo_login` DATETIME       DEFAULT NULL,
  `created_at`   DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email_empresa` (`email`, `empresa_id`),
  CONSTRAINT `fk_usuario_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Tabla: telas (producto padre)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `telas` (
  `id`          INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `empresa_id`  INT UNSIGNED   NOT NULL,
  `nombre`      VARCHAR(120)   NOT NULL,
  `descripcion` TEXT           DEFAULT NULL,
  `composicion` VARCHAR(200)   DEFAULT NULL  COMMENT 'Ej: 100% algodón, 60/40 polyester/algodón',
  `activa`      TINYINT(1)     NOT NULL DEFAULT 1,
  `created_at`  DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_tela_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Producto textil padre. Las variantes son los SKUs reales.';

-- ------------------------------------------------------------
-- Tabla: variantes (SKU real con código de barras)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `variantes` (
  `id`              INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `tela_id`         INT UNSIGNED     NOT NULL,
  `empresa_id`      INT UNSIGNED     NOT NULL,
  `descripcion`     VARCHAR(200)     NOT NULL  COMMENT 'Ej: Azul marino, ancho 1.50m',
  `codigo_barras`   VARCHAR(60)      NOT NULL,
  -- unidad: metro | kilo
  `unidad`          ENUM('metro','kilo') NOT NULL DEFAULT 'metro',
  `minimo_venta`    DECIMAL(10,3)    NOT NULL DEFAULT 0.100 COMMENT 'Mínimo en metros o kilos',
  `precio`          DECIMAL(12,2)    NOT NULL DEFAULT 0.00,
  `stock`           DECIMAL(12,3)    NOT NULL DEFAULT 0.000 COMMENT 'Stock en metros o kilos',
  `activa`          TINYINT(1)       NOT NULL DEFAULT 1,
  `created_at`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_codigo_barras_empresa` (`codigo_barras`, `empresa_id`),
  CONSTRAINT `fk_variante_tela`    FOREIGN KEY (`tela_id`)    REFERENCES `telas`    (`id`),
  CONSTRAINT `fk_variante_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Cada variante = color/talle/rollo con su propio barcode y stock';

-- ------------------------------------------------------------
-- Tabla: pedidos
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pedidos` (
  `id`              INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `empresa_id`      INT UNSIGNED   NOT NULL,
  `usuario_id`      INT UNSIGNED   NOT NULL,
  -- Estados: abierto → confirmado | anulado
  `estado`          ENUM('abierto','confirmado','anulado') NOT NULL DEFAULT 'abierto',
  `total`           DECIMAL(14,2)  NOT NULL DEFAULT 0.00,
  `observaciones`   TEXT           DEFAULT NULL,
  -- Anulación
  `anulado_por`     INT UNSIGNED   DEFAULT NULL,
  `motivo_anulacion` TEXT          DEFAULT NULL,
  `anulado_at`      DATETIME       DEFAULT NULL,
  -- Fase 2: cliente_id INT UNSIGNED DEFAULT NULL
  `created_at`      DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `confirmado_at`   DATETIME       DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_pedido_empresa`  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`  (`id`),
  CONSTRAINT `fk_pedido_usuario`  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Pedido/venta. Solo impacta stock y balance al confirmar.';

-- ------------------------------------------------------------
-- Tabla: pedido_items
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pedido_items` (
  `id`            INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `pedido_id`     INT UNSIGNED   NOT NULL,
  `variante_id`   INT UNSIGNED   NOT NULL,
  `cantidad`      DECIMAL(10,3)  NOT NULL,
  `precio_unit`   DECIMAL(12,2)  NOT NULL COMMENT 'Precio al momento del pedido (histórico)',
  `subtotal`      DECIMAL(14,2)  NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_item_pedido`   FOREIGN KEY (`pedido_id`)   REFERENCES `pedidos`   (`id`),
  CONSTRAINT `fk_item_variante` FOREIGN KEY (`variante_id`) REFERENCES `variantes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Tabla: movimientos_stock
-- Registro inmutable de cada cambio de stock
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `movimientos_stock` (
  `id`            INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `empresa_id`    INT UNSIGNED   NOT NULL,
  `variante_id`   INT UNSIGNED   NOT NULL,
  `pedido_id`     INT UNSIGNED   DEFAULT NULL,
  `usuario_id`    INT UNSIGNED   NOT NULL,
  -- tipo: venta | ajuste_entrada | ajuste_salida | anulacion_venta
  `tipo`          VARCHAR(30)    NOT NULL,
  `cantidad`      DECIMAL(10,3)  NOT NULL COMMENT 'Positivo = entrada, negativo = salida',
  `stock_antes`   DECIMAL(12,3)  NOT NULL,
  `stock_despues` DECIMAL(12,3)  NOT NULL,
  `created_at`    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_mov_empresa`  FOREIGN KEY (`empresa_id`)  REFERENCES `empresas`  (`id`),
  CONSTRAINT `fk_mov_variante` FOREIGN KEY (`variante_id`) REFERENCES `variantes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Auditoría inmutable de stock. Nunca se borra.';

-- ------------------------------------------------------------
-- Tabla: balance_movimientos
-- Registro de ingresos/egresos monetarios
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `balance_movimientos` (
  `id`            INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `empresa_id`    INT UNSIGNED   NOT NULL,
  `pedido_id`     INT UNSIGNED   DEFAULT NULL,
  `usuario_id`    INT UNSIGNED   NOT NULL,
  -- tipo: ingreso_venta | gasto | anulacion_venta | ajuste
  `tipo`          VARCHAR(30)    NOT NULL,
  `monto`         DECIMAL(14,2)  NOT NULL COMMENT 'Positivo = ingreso, negativo = egreso',
  `descripcion`   VARCHAR(200)   DEFAULT NULL,
  `created_at`    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_bal_empresa`  FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Libro diario simplificado. Fase 2: expandir con gastos, cuentas.';

-- ------------------------------------------------------------
-- Tabla: gastos (Fase 1 básico, expandible en Fase 2)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `gastos` (
  `id`            INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `empresa_id`    INT UNSIGNED   NOT NULL,
  `usuario_id`    INT UNSIGNED   NOT NULL,
  `descripcion`   VARCHAR(200)   NOT NULL,
  `monto`         DECIMAL(12,2)  NOT NULL,
  `fecha`         DATE           NOT NULL,
  `created_at`    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_gasto_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Fase 2: categorías, comprobantes, proveedores';

-- ============================================================
-- DATOS INICIALES DE DEMO
-- ============================================================

INSERT INTO `empresas` (`nombre`, `cuit`, `email`) VALUES
  ('Textiles Del Sur S.A.', '30-12345678-9', 'admin@textilesdelsur.com'),
  ('Telas & Colores SRL',   '30-98765432-1', 'admin@telasycolores.com');

-- Contraseña para todos: "password" (hash bcrypt cost=10)
INSERT INTO `usuarios` (`empresa_id`, `nombre`, `email`, `password`, `rol`) VALUES
  (1, 'Administrador Demo', 'admin@textilesdelsur.com', '$2y$10$Dbks.r5wpCCgcEMihtGl3e72h.6rN8xLOX7V.QyFT6fs7eTWBjRha', 'admin'),
  (1, 'Vendedor Demo',      'vendedor@textilesdelsur.com', '$2y$10$Dbks.r5wpCCgcEMihtGl3e72h.6rN8xLOX7V.QyFT6fs7eTWBjRha', 'vendedor'),
  (2, 'Admin Empresa 2',    'admin@telasycolores.com', '$2y$10$Dbks.r5wpCCgcEMihtGl3e72h.6rN8xLOX7V.QyFT6fs7eTWBjRha', 'admin');

-- Telas demo empresa 1
INSERT INTO `telas` (`empresa_id`, `nombre`, `descripcion`, `composicion`) VALUES
  (1, 'Gabardina Premium',   'Tela resistente para pantalones y sacos', '65% polyester / 35% algodón'),
  (1, 'Seda Natural',        'Seda pura importada de primera calidad',  '100% seda natural'),
  (1, 'Lino Artesanal',      'Lino de origen europeo, textura natural', '100% lino'),
  (1, 'Jersey Algodón',      'Punto jersey suave para remeras y ropa interior', '100% algodón peinado'),
  (1, 'Polar Soft',          'Tela polar doble vista para abrigos',     '100% polyester');

-- Variantes demo empresa 1
INSERT INTO `variantes` (`tela_id`, `empresa_id`, `descripcion`, `codigo_barras`, `unidad`, `minimo_venta`, `precio`, `stock`) VALUES
  (1, 1, 'Gabardina Negro - 1.50m ancho',   '7790001100001', 'metro', 0.10, 1850.00, 120.500),
  (1, 1, 'Gabardina Marino - 1.50m ancho',  '7790001100002', 'metro', 0.10, 1850.00,  85.000),
  (1, 1, 'Gabardina Beige - 1.50m ancho',   '7790001100003', 'metro', 0.10, 1750.00,  45.200),
  (2, 1, 'Seda Blanca - 1.10m ancho',       '7790002200001', 'metro', 0.25, 4200.00,  18.750),
  (2, 1, 'Seda Marfil - 1.10m ancho',       '7790002200002', 'metro', 0.25, 4200.00,  12.300),
  (3, 1, 'Lino Natural - 1.40m ancho',      '7790003300001', 'metro', 0.10, 2100.00,  60.000),
  (3, 1, 'Lino Blanco - 1.40m ancho',       '7790003300002', 'metro', 0.10, 2100.00,  38.000),
  (4, 1, 'Jersey Blanco - 1.80m ancho',     '7790004400001', 'metro', 0.10, 980.00,   200.000),
  (4, 1, 'Jersey Negro - 1.80m ancho',      '7790004400002', 'metro', 0.10, 980.00,   175.500),
  (5, 1, 'Polar Gris - venta por kilo',     '7790005500001', 'kilo',  0.25, 3500.00,  45.200),
  (5, 1, 'Polar Azul - venta por kilo',     '7790005500002', 'kilo',  0.25, 3500.00,  32.800);

SET FOREIGN_KEY_CHECKS = 1;
