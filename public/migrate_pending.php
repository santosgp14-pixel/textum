<?php
/**
 * Migración pendiente: v1.4, v1.5, v1.6
 * ¡ELIMINAR después de ejecutar!
 */

// Seguridad mínima: token en la URL
$token = $_GET['token'] ?? '';
if ($token !== 'textum_mig_2026') {
    http_response_code(403);
    die('Forbidden');
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/core/Database.php';

$db = Database::getInstance()->getConnection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$log = [];

function runSafe(PDO $db, string $sql, string $label, array &$log): void {
    try {
        $db->exec($sql);
        $log[] = "✅ $label";
    } catch (PDOException $e) {
        // 1060 = Duplicate column name (ya existe)
        // 1061 = Duplicate key name
        // 1091 = Can't drop; column/key doesn't exist
        // 1826 = Duplicate foreign key
        $ignored = ['1060','1061','1062','1091','1826','42S21'];
        if (in_array($e->getCode(), $ignored) || strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), 'already exists') !== false) {
            $log[] = "⏭️  $label (ya existía, ignorado)";
        } else {
            $log[] = "❌ $label → " . $e->getMessage() . " [code:{$e->getCode()}]";
        }
    }
}

// ──────────────────────────────────────────────
// v1.4: nuevos campos en telas
// ──────────────────────────────────────────────
runSafe($db, "ALTER TABLE `telas` ADD COLUMN `tipo` ENUM('punto','plano') DEFAULT NULL COMMENT 'Tipo de tejido: punto o plano' AFTER `empresa_id`", 'v1.4 telas.tipo', $log);
runSafe($db, "ALTER TABLE `telas` ADD COLUMN `subcategoria` ENUM('atemporal','invierno','verano') DEFAULT NULL AFTER `categoria_id`", 'v1.4 telas.subcategoria', $log);
runSafe($db, "ALTER TABLE `telas` ADD COLUMN `rinde` DECIMAL(8,3) DEFAULT NULL COMMENT 'Metros que rinde 1 kilo de tela' AFTER `subcategoria`", 'v1.4 telas.rinde', $log);
runSafe($db, "ALTER TABLE `telas` ADD COLUMN `precio` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Precio de venta base' AFTER `rinde`", 'v1.4 telas.precio', $log);
runSafe($db, "ALTER TABLE `telas` ADD COLUMN `unidad` ENUM('metro','kilo','rollo') NOT NULL DEFAULT 'metro' AFTER `precio`", 'v1.4 telas.unidad', $log);
runSafe($db, "ALTER TABLE `telas` ADD COLUMN `imagen_url` VARCHAR(500) DEFAULT NULL AFTER `unidad`", 'v1.4 telas.imagen_url', $log);
runSafe($db, "ALTER TABLE `telas` ADD COLUMN `minimo_venta` DECIMAL(10,3) NOT NULL DEFAULT 1.000 AFTER `unidad`", 'v1.4 telas.minimo_venta', $log);

runSafe($db, "ALTER TABLE `categorias` ADD COLUMN `tipo` ENUM('punto','plano') DEFAULT NULL AFTER `empresa_id`", 'v1.4 categorias.tipo', $log);
runSafe($db, "ALTER TABLE `categorias` ADD COLUMN `parent_id` INT UNSIGNED DEFAULT NULL AFTER `tipo`", 'v1.4 categorias.parent_id', $log);
runSafe($db, "ALTER TABLE `categorias` ADD CONSTRAINT `fk_cat_parent` FOREIGN KEY (`parent_id`) REFERENCES `categorias` (`id`)", 'v1.4 categorias FK parent', $log);

runSafe($db, "ALTER TABLE `variantes` MODIFY COLUMN `unidad` ENUM('metro','kilo','rollo') NOT NULL DEFAULT 'metro'", 'v1.4 variantes.unidad MODIFY', $log);

runSafe($db, "ALTER TABLE `rollos` ADD COLUMN `costo` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Costo de compra de este rollo' AFTER `codigo_barras`", 'v1.4 rollos.costo', $log);

// ──────────────────────────────────────────────
// v1.5: rollo_id en pedido_items
// ──────────────────────────────────────────────
runSafe($db, "ALTER TABLE `pedido_items` ADD COLUMN `rollo_id` INT UNSIGNED DEFAULT NULL COMMENT 'Rollo físico vendido' AFTER `variante_id`", 'v1.5 pedido_items.rollo_id', $log);
runSafe($db, "ALTER TABLE `pedido_items` ADD CONSTRAINT `fk_item_rollo` FOREIGN KEY (`rollo_id`) REFERENCES `rollos` (`id`)", 'v1.5 pedido_items FK rollo', $log);

// ──────────────────────────────────────────────
// v1.6: proveedores, gastos, empresas, remember_tokens
// ──────────────────────────────────────────────
runSafe($db, "CREATE TABLE IF NOT EXISTS `proveedores` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `empresa_id`  INT UNSIGNED NOT NULL,
  `nombre`      VARCHAR(100) NOT NULL,
  `cuit`        VARCHAR(20)  NULL DEFAULT NULL,
  `telefono`    VARCHAR(30)  NULL DEFAULT NULL,
  `email`       VARCHAR(100) NULL DEFAULT NULL,
  `notas`       TEXT         NULL DEFAULT NULL,
  `activo`      TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_proveedores_empresa` (`empresa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'v1.6 tabla proveedores', $log);

runSafe($db, "ALTER TABLE `gastos` ADD COLUMN IF NOT EXISTS `es_recurrente` TINYINT(1) NOT NULL DEFAULT 0 AFTER `fecha`", 'v1.6 gastos.es_recurrente', $log);
runSafe($db, "ALTER TABLE `gastos` ADD COLUMN IF NOT EXISTS `frecuencia` ENUM('diario','semanal','mensual') NULL DEFAULT NULL AFTER `es_recurrente`", 'v1.6 gastos.frecuencia', $log);
runSafe($db, "ALTER TABLE `gastos` ADD COLUMN IF NOT EXISTS `dia_cobro` TINYINT NULL DEFAULT NULL AFTER `frecuencia`", 'v1.6 gastos.dia_cobro', $log);

runSafe($db, "ALTER TABLE `empresas` ADD COLUMN IF NOT EXISTS `descripcion_catalogo` TEXT NULL DEFAULT NULL AFTER `activa`", 'v1.6 empresas.descripcion_catalogo', $log);
runSafe($db, "ALTER TABLE `empresas` ADD COLUMN IF NOT EXISTS `whatsapp` VARCHAR(20) NULL DEFAULT NULL AFTER `descripcion_catalogo`", 'v1.6 empresas.whatsapp', $log);
runSafe($db, "ALTER TABLE `empresas` ADD COLUMN IF NOT EXISTS `logo_url` VARCHAR(255) NULL DEFAULT NULL AFTER `whatsapp`", 'v1.6 empresas.logo_url', $log);

runSafe($db, "CREATE TABLE IF NOT EXISTS `remember_tokens` (
  `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED  NOT NULL,
  `selector`   VARCHAR(24)   NOT NULL,
  `token_hash` VARCHAR(64)   NOT NULL,
  `expires_at` DATETIME      NOT NULL,
  `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_selector` (`selector`),
  KEY `idx_remember_user` (`user_id`),
  KEY `idx_remember_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'v1.6 tabla remember_tokens', $log);

?><!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Migración pendiente</title>
<style>body{font-family:monospace;padding:2rem;max-width:720px}
li{margin:.3rem 0;font-size:.9rem}</style>
</head>
<body>
<h2>Migración v1.4 → v1.6 completada</h2>
<ul>
<?php foreach ($log as $line): ?>
  <li><?= htmlspecialchars($line) ?></li>
<?php endforeach; ?>
</ul>
<p><strong>⚠️ Eliminá este archivo del servidor cuando termines.</strong></p>
</body>
</html>
