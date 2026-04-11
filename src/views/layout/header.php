<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'Textum') ?> — Textum</title>

  <!-- PWA: Manifest -->
  <link rel="manifest" href="<?= BASE_URL ?>/manifest.php">

  <!-- PWA: Tema y color de barra de estado -->
  <meta name="theme-color" content="#1a4080">
  <meta name="msapplication-navbutton-color" content="#1a4080">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

  <!-- PWA: iOS (Safari) -->
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-title" content="Textum">
  <meta name="mobile-web-app-capable" content="yes">

  <!-- PWA: Iconos iOS (apple-touch-icon) -->
  <link rel="apple-touch-icon" sizes="180x180" href="<?= BASE_URL ?>/assets/icon-180.png">
  <link rel="apple-touch-icon" sizes="152x152" href="<?= BASE_URL ?>/assets/icon-152.png">
  <link rel="apple-touch-icon" sizes="144x144" href="<?= BASE_URL ?>/assets/icon-144.png">
  <link rel="apple-touch-icon" sizes="128x128" href="<?= BASE_URL ?>/assets/icon-128.png">

  <!-- PWA: Favicon -->
  <link rel="icon" type="image/png" sizes="192x192" href="<?= BASE_URL ?>/assets/icon-192.png">
  <link rel="icon" type="image/png" sizes="96x96"   href="<?= BASE_URL ?>/assets/icon-96.png">

  <!-- Estilos -->
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
      <div class="nav-section-label">Ventas</div>
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
      <div class="nav-section-label">Inventario</div>
      <a href="index.php?page=stock"      class="<?= ($currentPage??'')==='stock'      ? 'active' : '' ?>">
        <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/></svg></span> Stock / Telas
      </a>
      <a href="index.php?page=productos"  class="<?= ($currentPage??'')==='productos'  ? 'active' : '' ?>">
        <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg></span> Productos
      </a>
      <div class="nav-section-label">Finanzas</div>
      <a href="index.php?page=balance"    class="<?= ($currentPage??'')==='balance'    ? 'active' : '' ?>">
        <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span> Balance
      </a>
      <a href="index.php?page=proveedores" class="<?= ($currentPage??'')==='proveedores' ? 'active' : '' ?>">
        <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg></span> Proveedores
      </a>
      <a href="index.php?page=reportes"  class="<?= ($currentPage??'')==='reportes'  ? 'active' : '' ?>">
        <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg></span> Reportes
      </a>
      <?php if (Auth::isAdmin()): ?>
      <div class="nav-section-label">Sistema</div>
      <a href="index.php?page=config"    class="<?= ($currentPage??'')==='config'    ? 'active' : '' ?>">
        <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3"/></svg></span> Configuración
      </a>
      <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
      <div class="sidebar-footer-avatar"><?= htmlspecialchars(mb_strtoupper(mb_substr(Auth::nombre(), 0, 1))) ?></div>
      <div class="sidebar-footer-info">
        <div class="sidebar-footer-name"><?= htmlspecialchars(Auth::nombre()) ?></div>
        <a href="index.php?page=logout" class="sidebar-footer-logout">Cerrar sesión</a>
      </div>
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
