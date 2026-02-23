<?php
/**
 * TEXTUM - Configuración central
 * Copiar a config.php y ajustar valores locales
 */

// ── Entorno ────────────────────────────────────────────────────
define('APP_ENV',     getenv('APP_ENV')  ?: 'development'); // production | development
define('APP_NAME',    'Textum');
define('APP_VERSION', '1.0.0');

// BASE_URL: prioridad → env var → detección automática → fallback local
if (getenv('BASE_URL')) {
    define('BASE_URL', rtrim(getenv('BASE_URL'), '/'));
} else {
    $isHttps  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
             || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
             || ($_SERVER['HTTP_X_FORWARDED_SSL']   ?? '') === 'on';
    $scheme   = $isHttps ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script   = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $basePath = rtrim(dirname($script), '/');
    define('BASE_URL', $scheme . '://' . $host . ($basePath === '/' ? '' : $basePath));
}

// ── Base de datos ──────────────────────────────────────────────
function env(string $key, string $default = ''): string {
    return getenv($key) ?: ($_ENV[$key] ?? ($_SERVER[$key] ?? $default));
}

define('DB_HOST',    env('DB_HOST',    'localhost'));
define('DB_PORT',    (int)(env('DB_PORT', '3307')));
define('DB_NAME',    env('DB_NAME',    'textum'));
define('DB_USER',    env('DB_USER',    'root'));
define('DB_PASS',    env('DB_PASS',    ''));
define('DB_CHARSET', 'utf8mb4');

// ── Sesión ─────────────────────────────────────────────────────
define('SESSION_NAME',     'textum_sess');
define('SESSION_LIFETIME', 28800); // 8 horas

// ── Seguridad ──────────────────────────────────────────────────
define('BCRYPT_COST', 10);

// ── Rutas internas ─────────────────────────────────────────────
define('ROOT_PATH',  dirname(__DIR__));
define('SRC_PATH',   ROOT_PATH . '/src');
define('VIEW_PATH',  SRC_PATH  . '/views');

// ── Zona horaria ───────────────────────────────────────────────
date_default_timezone_set(getenv('TZ') ?: 'America/Argentina/Buenos_Aires');
