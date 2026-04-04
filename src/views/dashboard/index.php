<?php
$pageTitle  = 'Dashboard';
$currentPage = 'dashboard';
require VIEW_PATH . '/layout/header.php';
?>

<!-- KPIs del día -->
<div class="stats-grid">
  <div class="stat-card stat-card--green">
    <div class="stat-label">Ventas hoy</div>
    <div class="stat-value"><?= (int)$ventas_hoy_qty ?></div>
    <div class="stat-sub">pedidos confirmados</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Ingresos hoy</div>
    <div class="stat-value stat-value--sm">
      $ <?= number_format((float)$ventas_hoy_monto, 2, ',', '.') ?>
    </div>
    <div class="stat-sub">pesos argentinos</div>
  </div>
  <div class="stat-card <?= $pedidos_abiertos > 0 ? 'stat-card--yellow' : '' ?>">
    <div class="stat-label">Pedidos abiertos</div>
    <div class="stat-value"><?= $pedidos_abiertos ?></div>
    <div class="stat-sub"><?= $pedidos_abiertos === 1 ? 'requiere atención' : 'en curso' ?></div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr;gap:24px">

  <!-- Accesos rápidos -->
  <div>
    <div style="font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--gray-400);margin-bottom:10px">Accesos rápidos</div>
    <div class="quick-grid">
      <a href="index.php?page=pedido_nuevo" class="quick-btn quick-btn--primary">
        <span class="quick-btn-icon">＋</span>
        Nuevo Pedido
      </a>
      <a href="index.php?page=pedidos" class="quick-btn">
        <span class="quick-btn-icon">🧾</span>
        Pedidos
      </a>
      <a href="index.php?page=clientes" class="quick-btn">
        <span class="quick-btn-icon">👥</span>
        Clientes
      </a>
      <a href="index.php?page=stock" class="quick-btn">
        <span class="quick-btn-icon">📦</span>
        Stock / Telas
      </a>
      <a href="index.php?page=balance" class="quick-btn">
        <span class="quick-btn-icon">💰</span>
        Balance del día
      </a>
    </div>
  </div>

  <?php if (!empty($stock_bajo)): ?>
  <!-- Alerta stock bajo -->
  <div class="alert alert-warn" style="margin:0">
    <div style="flex:1">
      <div style="font-weight:700;margin-bottom:8px">⚠ Stock bajo — <?= count($stock_bajo) ?> variante<?= count($stock_bajo) > 1 ? 's' : '' ?> por debajo de 5 unidades</div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Producto</th><th>Stock</th><th>Unidad</th></tr>
          </thead>
          <tbody>
            <?php foreach ($stock_bajo as $v): ?>
            <tr>
              <td>
                <div class="font-bold"><?= htmlspecialchars($v['tela']) ?></div>
                <div class="text-sm" style="color:#92400e;opacity:.8"><?= htmlspecialchars($v['descripcion']) ?></div>
              </td>
              <td class="font-bold" style="color:var(--red-500)"><?= number_format($v['stock'], 3, ',', '.') ?></td>
              <td><?= $v['unidad'] ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px">

    <!-- Top clientes -->
    <?php if (!empty($top_clientes)): ?>
    <div class="card">
      <div class="card-header">
        <span class="card-title">👥 Mejores clientes</span>
        <a href="index.php?page=clientes" class="btn btn-sm btn-outline">Ver todos</a>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Cliente</th><th>Pedidos</th><th>Total</th></tr>
          </thead>
          <tbody>
            <?php foreach ($top_clientes as $c): ?>
            <tr>
              <td class="font-bold">
                <a href="index.php?page=cliente_perfil&id=<?= $c['id'] ?>">
                  <?= htmlspecialchars($c['nombre']) ?>
                </a>
              </td>
              <td><span class="badge badge-blue"><?= (int)$c['pedidos'] ?></span></td>
              <td class="font-bold">$ <?= number_format((float)$c['total_comprado'], 2, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <!-- Últimos pedidos -->
    <div class="card">
      <div class="card-header">
        <span class="card-title">🧾 Últimos pedidos</span>
        <a href="index.php?page=pedidos" class="btn btn-sm btn-outline">Ver todos</a>
      </div>
      <?php if (empty($ultimos_pedidos)): ?>
      <div class="empty-state">
        <div class="empty-state-icon">📋</div>
        <div class="empty-state-title">Aún no hay pedidos</div>
        <a href="index.php?page=pedido_nuevo" class="btn btn-primary btn-sm">Crear el primero</a>
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
                   class="font-bold">#<?= $p['id'] ?></a>
              </td>
              <td><span class="badge badge-<?= $p['estado'] ?>"><?= ucfirst($p['estado']) ?></span></td>
              <td class="font-bold">$ <?= number_format($p['total'], 2, ',', '.') ?></td>
              <td class="text-sm text-muted hide-mobile"><?= htmlspecialchars($p['vendedor']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

  </div>
</div>

<?php require VIEW_PATH . '/layout/footer.php'; ?>
