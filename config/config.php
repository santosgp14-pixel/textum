<?php
/**
 * TEXTUM - Configuración central
 * Copiar a config.php y ajustar valores locales
 */

// ── Entorno ────────────────────────────────────────────────────
define('APP_ENV',     getenv('APP_ENV')  ?: 'development'); // production | development
define('APP_NAME',    'Textum');
define('APP_VERSION', '1.0.0');
define('BASE_URL',    getenv('BASE_URL') ?: 'http://localhost/textum/public');

// ── Base de datos ──────────────────────────────────────────────
define('DB_HOST',    getenv('DB_HOST')    ?: 'localhost');
define('DB_PORT',    (int)(getenv('DB_PORT') ?: 3307));
define('DB_NAME',    getenv('DB_NAME')    ?: 'textum');
define('DB_USER',    getenv('DB_USER')    ?: 'root');
define('DB_PASS',    getenv('DB_PASS')    ?: '');
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
