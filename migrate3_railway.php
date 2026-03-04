<?php
// Temporal: migración v1.3 en Railway. BORRAR después.
$host = 'yamanote.proxy.rlwy.net';
$port = 36633;
$db   = 'railway';
$user = 'root';
$pass = 'CvtZUaTPMGBYbuZMAePTEpsabyxautPj';

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
        $user, $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $sql = file_get_contents(__DIR__ . '/sql/migration_v1_3.sql');
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
        "SHOW TABLES LIKE 'categorias'"                      => 'tabla categorias',
        "SHOW COLUMNS FROM telas LIKE 'categoria_id'"        => 'telas.categoria_id',
        "SHOW COLUMNS FROM rollos LIKE 'codigo_barras'"      => 'rollos.codigo_barras',
    ];
    foreach ($checks as $q => $label) {
        $r = $pdo->query($q)->fetchAll();
        echo (count($r) ? '  [OK] ' : '  [FAIL] ') . $label . "\n";
    }
} catch (Exception $e) {
    echo "ERROR de conexion: " . $e->getMessage() . "\n";
}
