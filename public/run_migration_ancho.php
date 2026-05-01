<?php
/**
 * TEXTUM — Endpoint temporal de migración
 * Agrega columna `ancho` a la tabla `telas`
 * ELIMINAR DESPUÉS DE EJECUTAR
 */

// Protección mínima con token en query string
$token = getenv('MIGRATION_TOKEN') ?: 'textum_mig_ancho_2026';
if (($_GET['token'] ?? '') !== $token) {
    http_response_code(403);
    die('403 Forbidden');
}

require_once __DIR__ . '/../config/config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // migration v1.8 — columna ancho en telas
    $col = $pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'telas' AND COLUMN_NAME = 'ancho'")->fetchColumn();
    if (!$col) {
        $pdo->exec("ALTER TABLE `telas` ADD COLUMN `ancho` DECIMAL(6,3) NULL DEFAULT NULL AFTER `rinde`");
    }

    // migration v1.9 — columna rollo_id en movimientos_stock
    $col2 = $pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'movimientos_stock' AND COLUMN_NAME = 'rollo_id'")->fetchColumn();
    if (!$col2) {
        $pdo->exec("ALTER TABLE `movimientos_stock` ADD COLUMN `rollo_id` INT UNSIGNED NULL DEFAULT NULL AFTER `pedido_id`");
    }
    $pdo->exec("
        UPDATE rollos r
        INNER JOIN (
            SELECT pi.rollo_id, SUM(pi.cantidad) AS total_vendido
            FROM pedido_items pi
            JOIN pedidos p ON p.id = pi.pedido_id
            WHERE p.estado = 'confirmado' AND pi.rollo_id IS NOT NULL
            GROUP BY pi.rollo_id
        ) v ON v.rollo_id = r.id
        SET r.metros = GREATEST(0, r.metros - v.total_vendido)
    ");

    echo "<pre style='font-family:monospace;padding:20px'>";
    echo "✅ migration v1.8: columna `telas`.`ancho` OK\n";
    echo "✅ migration v1.9: columna `movimientos_stock`.`rollo_id` OK\n";
    echo "✅ migration v1.9: metros de rollos corregidos OK\n";
    echo "\n⚠️  ELIMINA este archivo ahora: public/run_migration_ancho.php\n";
    echo "</pre>";
} catch (Throwable $e) {
    http_response_code(500);
    echo "<pre style='color:red'>" . htmlspecialchars($e->getMessage()) . "</pre>";
}
