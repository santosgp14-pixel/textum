<?php
$pageTitle  = 'Dashboard';
$currentPage = 'dashboard';
require VIEW_PATH . '/layout/header.php';
?>

<!-- Page header -->
<div class="page-header">
  <div>
    <div class="page-header-title"><?php
      $h = (int)date('H');
      echo $h < 12 ? 'Buenos días' : ($h < 19 ? 'Buenas tardes' : 'Buenas noches');
    ?>, <?= htmlspecialchars(Auth::nombre()) ?></div>
    <div class="page-header-sub"><?= date('l, d \d\e F \d\e Y') ?> — <?= htmlspecialchars(Auth::empresaNombre()) ?></div>
  </div>
  <a href="index.php?page=pedido_nuevo" class="btn btn-primary">
    ＋ Nuevo Pedido
    <span class="kbd" style="background:rgba(255,255,255,.18);border-color:rgba(255,255,255,.25);color:#fff">N</span>
  </a>
</div>

<!-- KPIs -->
<div class="stats-grid" style="margin-bottom:24px">
  <div class="stat-card stat-card--green">
    <div class="stat-label">Ventas hoy</div>
    <div class="stat-value"><?= (int)$ventas_hoy_qty ?></div>
    <div class="stat-sub">pedido<?= $ventas_hoy_qty != 1 ? 's' : '' ?> confirmado<?= $ventas_hoy_qty != 1 ? 's' : '' ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Ingresos hoy</div>
    <div class="stat-value stat-value--sm">$ <?= number_format((float)$ventas_hoy_monto, 0, ',', '.') ?></div>
    <div class="stat-sub">pesos argentinos</div>
  </div>
  <?php if ($pedidos_abiertos > 0): ?>
  <div class="stat-card stat-card--yellow">
    <div class="stat-label">Para atender</div>
    <div class="stat-value"><?= $pedidos_abiertos ?></div>
    <div class="stat-sub">pedido<?= $pedidos_abiertos != 1 ? 's' : '' ?> abierto<?= $pedidos_abiertos != 1 ? 's' : '' ?></div>
  </div>
  <?php endif; ?>
  <?php if (!empty($stock_bajo)): ?>
  <div class="stat-card stat-card--red">
    <div class="stat-label">Stock bajo</div>
    <div class="stat-value"><?= count($stock_bajo) ?></div>
    <div class="stat-sub">variante<?= count($stock_bajo) != 1 ? 's' : '' ?> crítica<?= count($stock_bajo) != 1 ? 's' : '' ?></div>
  </div>
  <?php endif; ?>
</div>

<!-- Accesos rápidos -->
<div style="margin-bottom:24px">
  <div class="section-label">Accesos rápidos</div>
  <div class="quick-grid">
    <a href="index.php?page=pedido_nuevo" class="quick-btn quick-btn--primary">
      <span class="quick-btn-icon">＋</span>Nuevo Pedido
    </a>
    <a href="index.php?page=pedidos" class="quick-btn">
      <span class="quick-btn-icon">🧾</span>Pedidos
    </a>
    <a href="index.php?page=clientes" class="quick-btn">
      <span class="quick-btn-icon">👥</span>Clientes
    </a>
    <a href="index.php?page=stock" class="quick-btn">
      <span class="quick-btn-icon">📦</span>Stock
    </a>
    <a href="index.php?page=balance" class="quick-btn">
      <span class="quick-btn-icon">💰</span>Balance
    </a>
    <a href="index.php?page=reportes" class="quick-btn">
      <span class="quick-btn-icon">📊</span>Reportes
    </a>
  </div>
</div>

<?php if (!empty($stock_bajo)): ?>
<div class="alert alert-warn" style="margin-bottom:24px">
  <div style="flex:1">
    <div style="font-weight:700;margin-bottom:8px">
      ⚠ Stock bajo — <?= count($stock_bajo) ?> variante<?= count($stock_bajo) > 1 ? 's' : '' ?> crítica<?= count($stock_bajo) > 1 ? 's' : '' ?>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Producto</th><th>Stock</th><th>Unidad</th></tr></thead>
        <tbody>
          <?php foreach ($stock_bajo as $v): ?>
          <tr>
            <td>
              <div class="font-bold"><?= htmlspecialchars($v['tela']) ?></div>
              <div class="text-sm" style="opacity:.75"><?= htmlspecialchars($v['descripcion']) ?></div>
            </td>
            <td class="font-bold" style="color:var(--red-600)"><?= number_format($v['stock'], 3, ',', '.') ?></td>
            <td class="text-sm"><?= $v['unidad'] ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px;margin-bottom:32px">

  <?php if (!empty($top_clientes)): ?>
  <div class="card">
    <div class="card-header">
      <span class="card-title">Mejores clientes</span>
      <a href="index.php?page=clientes" class="btn btn-sm btn-outline">Ver todos</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Cliente</th><th>Pedidos</th><th>Total</th></tr></thead>
        <tbody>
          <?php foreach ($top_clientes as $c): ?>
          <tr>
            <td class="font-bold">
              <a href="index.php?page=cliente_perfil&id=<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></a>
            </td>
            <td><span class="badge badge-blue"><?= (int)$c['pedidos'] ?></span></td>
            <td class="font-bold">$ <?= number_format((float)$c['total_comprado'], 0, ',', '.') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-header">
      <span class="card-title">Últimos pedidos</span>
      <a href="index.php?page=pedidos" class="btn btn-sm btn-outline">Ver todos</a>
    </div>
    <?php if (empty($ultimos_pedidos)): ?>
    <div class="empty-state">
      <div class="empty-state-icon">📋</div>
      <div class="empty-state-title">Aún no hay pedidos</div>
      <a href="index.php?page=pedido_nuevo" class="btn btn-primary btn-sm" style="margin-top:8px">＋ Crear el primero</a>
    </div>
    <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>#</th><th>Estado</th><th>Total</th><th class="hide-mobile">Vendedor</th></tr>
        </thead>
        <tbody>
          <?php foreach ($ultimos_pedidos as $p): ?>
          <tr>
            <td>
              <a href="index.php?page=<?= $p['estado']==='abierto' ? 'pedido_abierto' : 'pedido_detalle' ?>&id=<?= $p['id'] ?>"
                 class="font-bold text-muted">#<?= $p['id'] ?></a>
              <div class="text-xs text-muted"><?= date('d/m H:i', strtotime($p['created_at'])) ?></div>
            </td>
            <td><span class="badge badge-<?= $p['estado'] ?>"><?= ucfirst($p['estado']) ?></span></td>
            <td class="font-bold">$ <?= number_format($p['total'], 0, ',', '.') ?></td>
            <td class="text-sm text-muted hide-mobile"><?= htmlspecialchars($p['vendedor']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

</div>

<!-- Shortcuts -->
<div style="padding:12px 16px;background:var(--white);border:1px solid rgba(0,0,0,.07);border-radius:var(--radius-lg);font-size:.78rem;color:var(--gray-400);display:flex;gap:16px;flex-wrap:wrap;align-items:center">
  <span style="font-weight:600">Atajos:</span>
  <span><kbd class="kbd">N</kbd> Nuevo pedido</span>
  <span><kbd class="kbd">P</kbd> Pedidos</span>
  <span><kbd class="kbd">C</kbd> Clientes</span>
  <span><kbd class="kbd">S</kbd> Stock</span>
  <span><kbd class="kbd">B</kbd> Balance</span>
  <span><kbd class="kbd">R</kbd> Reportes</span>
  <span><kbd class="kbd">/</kbd> Buscar</span>
</div>

<?php require VIEW_PATH . '/layout/footer.php'; ?>