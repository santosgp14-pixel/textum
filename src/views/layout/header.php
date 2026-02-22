<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'Textum') ?> — Textum</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/app.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body>

<div class="overlay" id="overlay"></div>
<div class="app-wrapper">

  <!-- SIDEBAR -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
      <h1>Text<span>um</span></h1>
      <div class="sidebar-empresa"><?= htmlspecialchars(Auth::empresaNombre()) ?></div>
    </div>
    <nav class="sidebar-nav">
      <a href="index.php?page=dashboard"  class="<?= ($currentPage??'')==='dashboard'  ? 'active' : '' ?>">
        <span class="nav-icon">◉</span> Dashboard
      </a>
      <a href="index.php?page=pedido_nuevo" class="<?= ($currentPage??'')==='pedido_nuevo' ? 'active' : '' ?>">
        <span class="nav-icon">＋</span> Nuevo Pedido
      </a>
      <a href="index.php?page=pedidos"    class="<?= ($currentPage??'')==='pedidos'    ? 'active' : '' ?>">
        <span class="nav-icon">☰</span> Pedidos
      </a>
      <a href="index.php?page=stock"      class="<?= ($currentPage??'')==='stock'      ? 'active' : '' ?>">
        <span class="nav-icon">▦</span> Stock / Telas
      </a>
      <a href="index.php?page=balance"    class="<?= ($currentPage??'')==='balance'    ? 'active' : '' ?>">
        <span class="nav-icon">$</span> Balance
      </a>
    </nav>
    <div class="sidebar-footer">
      <div><?= htmlspecialchars(Auth::nombre()) ?></div>
      <a href="index.php?page=logout">Cerrar sesión</a>
    </div>
  </aside>

  <!-- MAIN -->
  <div class="main-content">
    <header class="topbar">
      <div class="flex items-center gap-3">
        <button class="btn-menu" id="btn-menu" aria-label="Menú">
          <span></span><span></span><span></span>
        </button>
        <span class="topbar-title"><?= htmlspecialchars($pageTitle ?? 'Textum') ?></span>
      </div>
      <div class="topbar-right">
        <span class="topbar-user"><?= htmlspecialchars(Auth::nombre()) ?></span>
        <?php if (Auth::isAdmin()): ?>
          <span class="badge badge-blue">Admin</span>
        <?php endif; ?>
      </div>
    </header>

    <main class="page-content">

      <?php // Flash messages
      if (!empty($_SESSION['flash_ok'])): ?>
        <div class="alert alert-ok" data-autodismiss>✓ <?= htmlspecialchars($_SESSION['flash_ok']) ?></div>
        <?php unset($_SESSION['flash_ok']); endif; ?>

      <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-error" data-autodismiss>✕ <?= htmlspecialchars($_SESSION['flash_error']) ?></div>
        <?php unset($_SESSION['flash_error']); endif; ?>
