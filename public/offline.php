<?php
require_once __DIR__ . '/../config/config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sin conexión — Textum</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/app.css">
  <style>
    .offline-wrap {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      gap: 1.25rem;
      padding: 2rem;
      text-align: center;
      background: var(--blue-900, #0d1f3c);
      color: var(--gray-100, #f3f4f6);
    }
    .offline-icon { font-size: 3.5rem; }
    .offline-title { font-size: 1.5rem; font-weight: 700; }
    .offline-msg   { font-size: 0.95rem; color: var(--gray-400, #9ca3af); max-width: 320px; }
    .btn-retry {
      margin-top: .5rem;
      padding: .6rem 1.5rem;
      border-radius: 8px;
      background: var(--blue-500, #2563eb);
      color: #fff;
      font-weight: 600;
      border: none;
      cursor: pointer;
      font-size: .95rem;
    }
  </style>
</head>
<body>
  <div class="offline-wrap">
    <div class="offline-icon">📡</div>
    <div class="offline-title">Sin conexión</div>
    <p class="offline-msg">No hay conexión a internet. Revisá tu red e intentá de nuevo.</p>
    <button class="btn-retry" onclick="location.reload()">Reintentar</button>
  </div>
</body>
</html>
