<?php
// TEMPORAL - eliminar después del diagnóstico
$vars = [];
foreach ($_ENV as $k => $v)    { $vars["_ENV.$k"] = $v; }
foreach ($_SERVER as $k => $v) { if (is_string($v)) $vars["_SERVER.$k"] = $v; }

// También via getenv
foreach (['MYSQL_URL','DB_HOST','DB_PORT','DB_NAME','DB_USER','DB_PASS',
          'MYSQLHOST','MYSQLPORT','MYSQLDATABASE','MYSQLUSER','MYSQLPASSWORD',
          'DATABASE_URL','PORT'] as $key) {
    $vars["getenv.$key"] = getenv($key) ?: '(vacío)';
}

header('Content-Type: text/plain');
ksort($vars);
foreach ($vars as $k => $v) {
    // Ocultar contraseñas parcialmente
    if (stripos($k, 'pass') !== false || stripos($k, 'url') !== false) {
        $v = substr($v, 0, 8) . '***';
    }
    echo "$k = $v\n";
}
