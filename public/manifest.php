<?php
/**
 * TEXTUM - Web App Manifest (PWA)
 * Served as application/manifest+json with dynamic BASE_URL
 */
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: public, max-age=86400');

$base = BASE_URL;

$manifest = [
    'name'             => 'Textum',
    'short_name'       => 'Textum',
    'description'      => 'Sistema de gestión textil',
    'start_url'        => $base . '/index.php',
    'scope'            => $base . '/',
    'display'          => 'standalone',
    'orientation'      => 'portrait',
    'background_color' => '#0d1f3c',
    'theme_color'      => '#1a4080',
    'lang'             => 'es',
    'icons'            => [
        ['src' => $base . '/assets/icon-72.png',  'sizes' => '72x72',   'type' => 'image/png'],
        ['src' => $base . '/assets/icon-96.png',  'sizes' => '96x96',   'type' => 'image/png'],
        ['src' => $base . '/assets/icon-128.png', 'sizes' => '128x128', 'type' => 'image/png'],
        ['src' => $base . '/assets/icon-144.png', 'sizes' => '144x144', 'type' => 'image/png'],
        ['src' => $base . '/assets/icon-152.png', 'sizes' => '152x152', 'type' => 'image/png'],
        ['src' => $base . '/assets/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
        ['src' => $base . '/assets/icon-384.png', 'sizes' => '384x384', 'type' => 'image/png'],
        ['src' => $base . '/assets/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'],
    ],
    'screenshots'      => [],
    'categories'       => ['business', 'productivity'],
];

echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
