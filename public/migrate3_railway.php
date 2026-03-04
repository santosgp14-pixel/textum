<?php
// Temporal: migración v1.3 — acceso vía Railway app. BORRAR después.
// URL: /migrate3_railway.php?token=mig13textum
if (($_GET['token'] ?? '') !== 'mig13textum') { http_response_code(403); die('Forbidden'); }

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/core/Database.php';
$pdo = Database::getInstance()->getConnection();

header('Content-Type: text/plain');

$sql = file_get_contents(__DIR__ . '/../sql/migration_v1_3.sql');
$statements = array_filter(
    array_map(function($s) {
        $lines = explode("\n", trim($s));
        $code  = implode("\n", array_filter($lines, fn($l) => !preg_match('/^\s*--/', $l)));
        return trim($code);
    }, explode(';', $sql)),
    fn($s) => strlen($s) > 10
);

foreach ($statements as $stmt) {
    try {
        $pdo->exec($stmt);
        echo "OK: " . substr(trim($stmt), 0, 72) . "\n";
    } catch (PDOException $e) {
        echo "ERR: " . $e->getMessage() . "\n";
    }
}

echo "\nVerificando:\n";
$checks = [
    "SHOW TABLES LIKE 'categorias'"                 => 'tabla categorias',
    "SHOW COLUMNS FROM telas LIKE 'categoria_id'"   => 'telas.categoria_id',
    "SHOW COLUMNS FROM rollos LIKE 'codigo_barras'"  => 'rollos.codigo_barras',
];
foreach ($checks as $q => $label) {
    $r = $pdo->query($q)->fetchAll();
    echo (count($r) ? '  [OK] ' : '  [FAIL] ') . $label . "\n";
}
echo "\nBORRAR este archivo!\n";
