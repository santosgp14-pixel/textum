<?php
/**
 * TEXTUM — Migration endpoint v1.7
 * TEMPORAL: eliminar después de ejecutar.
 */

// Clave de seguridad mínima para evitar ejecución accidental
$secret = $_GET['key'] ?? '';
if ($secret !== 'textum_mig_v17_2026') {
    http_response_code(403);
    exit('Forbidden');
}

$host   = getenv('DB_HOST')     ?: 'localhost';
$port   = getenv('DB_PORT')     ?: '3306';
$dbname = getenv('DB_NAME')     ?: '';
$user   = getenv('DB_USER')     ?: '';
$pass   = getenv('DB_PASSWORD') ?: '';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    exit('DB connection failed: ' . htmlspecialchars($e->getMessage()));
}

$steps = [];

// 1. Agregar columna precio_fraccionado
try {
    $pdo->exec("ALTER TABLE `variantes`
        ADD COLUMN IF NOT EXISTS `precio_fraccionado` DECIMAL(12,2) NOT NULL DEFAULT 0.00
        AFTER `precio`");
    $steps[] = '✅ ALTER TABLE variantes: precio_fraccionado agregado.';
} catch (Exception $e) {
    $steps[] = '⚠️ ALTER TABLE variantes: ' . htmlspecialchars($e->getMessage());
}

// 2. Inicializar precio_fraccionado para filas existentes
try {
    $affected = $pdo->exec("UPDATE `variantes`
        SET `precio_fraccionado` = ROUND(`precio` * 1.15, 2)
        WHERE `precio` > 0 AND `precio_fraccionado` = 0");
    $steps[] = "✅ UPDATE variantes: $affected filas inicializadas con precio * 1.15.";
} catch (Exception $e) {
    $steps[] = '⚠️ UPDATE variantes: ' . htmlspecialchars($e->getMessage());
}

header('Content-Type: text/plain; charset=utf-8');
echo "=== Migration v1.7 ===\n\n";
echo implode("\n", $steps) . "\n\n";
echo "Listo. Elimina este archivo del servidor.\n";
