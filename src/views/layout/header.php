<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'Textum') ?> — Textum</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/app.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Josefin+Sans:wght@300;600;700&display=swap" rel="stylesheet">
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
        <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg></span> Dashboard
      </a>
      <a href="index.php?page=pedido_nuevo" class="<?= ($currentPage??'')==='pedido_nuevo' ? 'active' : '' ?>">
        <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg></span> Nuevo Pedido
      </a>
      <a href="index.php?page=pedidos"    class="<?= ($currentPage??'')==='pedidos'    ? 'active' : '' ?>">
        <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg></span> Pedidos
      </a>
      <a href="index.php?page=clientes"   class="<?= ($currentPage??'')==='clientes'   ? 'active' : '' ?>">
        <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-1a4 4 0 00-5.477-3.72M17 20H7m10 0v-1c0-.653-.1-1.283-.287-1.873M7 20H2v-1a4 4 0 015.477-3.72M7 20v-1c0-.653.1-1.283.287-1.873m9.426 0A6 6 0 0012 6a6 6 0 00-5.713 9.127"/></svg></span> Clientes
      </a>
      <a href="index.php?page=stock"      class="<?= ($currentPage??'')==='stock'      ? 'active' : '' ?>">
        <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/></svg></span> Stock / Telas
      </a>
      <a href="index.php?page=balance"    class="<?= ($currentPage??'')==='balance'    ? 'active' : '' ?>">
        <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span> Balance
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
