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

$url = getenv('MYSQL_URL') ?: ($_ENV['MYSQL_URL'] ?? ($_SERVER['MYSQL_URL'] ?? ''));

// Debug: mostrar qué variables están disponibles (solo con key correcta)
if (isset($_GET['debug'])) {
    header('Content-Type: text/plain');
    $keys = ['MYSQL_URL','DB_HOST','DB_PORT','DB_NAME','DB_USER','DB_PASS','DB_PASSWORD'];
    foreach ($keys as $k) {
        $v = getenv($k) ?: ($_ENV[$k] ?? ($_SERVER[$k] ?? ''));
        echo "$k = " . ($v ? '[set: ' . substr($v,0,4) . '...]' : '[empty]') . "\n";
    }
    exit;
}

try {
    if ($url) {
        $p   = parse_url($url);
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $p['host'], $p['port'] ?? 3306, ltrim($p['path'], '/'));
        $user = $p['user'] ?? '';
        $pass = isset($p['pass']) ? urldecode($p['pass']) : '';
    } else {
        $e_get = fn($k) => getenv($k) ?: ($_ENV[$k] ?? ($_SERVER[$k] ?? ''));
        $host   = $e_get('DB_HOST') ?: 'localhost';
        $port   = $e_get('DB_PORT') ?: '3306';
        $dbname = $e_get('DB_NAME') ?: '';
        $user   = $e_get('DB_USER') ?: '';
        $pass   = $e_get('DB_PASS') ?: '';
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    }
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Exception $e) {
    http_response_code(500);
    exit('DB connection failed: ' . htmlspecialchars($e->getMessage()));
}

$steps = [];

// 1. Agregar columna precio_fraccionado (compatible MySQL 5.7+)
try {
    // Verificar si la columna ya existe
    $check = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'variantes' AND COLUMN_NAME = 'precio_fraccionado'");
    $check->execute();
    $exists = (int)$check->fetchColumn();

    if ($exists) {
        $steps[] = 'ℹ️ Columna precio_fraccionado ya existe. Nada que hacer.';
    } else {
        $pdo->exec("ALTER TABLE `variantes`
            ADD COLUMN `precio_fraccionado` DECIMAL(12,2) NOT NULL DEFAULT 0.00
            AFTER `precio`");
        $steps[] = '✅ ALTER TABLE variantes: precio_fraccionado agregado.';
    }
} catch (Exception $e) {
    $steps[] = '❌ ALTER TABLE variantes: ' . htmlspecialchars($e->getMessage());
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
