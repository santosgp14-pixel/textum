<?php
$pageTitle  = 'Dashboard';
$currentPage = 'dashboard';
require VIEW_PATH . '/layout/header.php';
?>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-label">Ventas hoy</div>
    <div class="stat-value"><?= (int)$ventas_hoy_qty ?></div>
    <div class="stat-sub">pedidos confirmados</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Ingresos hoy</div>
    <div class="stat-value" style="font-size:1.2rem">
      <?= number_format((float)$ventas_hoy_monto, 2, ',', '.') ?>
    </div>
    <div class="stat-sub">$ pesos</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Pedidos abiertos</div>
    <div class="stat-value <?= $pedidos_abiertos > 0 ? '' : 'text-muted' ?>">
      <?= $pedidos_abiertos ?>
    </div>
    <div class="stat-sub">en curso</div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr;gap:20px">

  <!-- Acceso rápido -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">Accesos rápidos</span>
    </div>
    <div class="card-body flex gap-3 flex-wrap">
      <a href="index.php?page=pedido_nuevo" class="btn btn-primary btn-lg">＋ Nuevo Pedido</a>
      <a href="index.php?page=pedidos"      class="btn btn-outline">Ver Pedidos</a>
      <a href="index.php?page=clientes"     class="btn btn-outline">👥 Clientes</a>
      <a href="index.php?page=stock"        class="btn btn-outline">Stock / Telas</a>
      <a href="index.php?page=balance"      class="btn btn-outline">Balance del día</a>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px">

    <!-- Stock bajo -->
    <?php if (!empty($stock_bajo)): ?>
    <div class="card">
      <div class="card-header">
        <span class="card-title">⚠ Stock bajo (menos de 5 unidades)</span>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Variante</th><th>Stock</th><th>Unidad</th></tr>
          </thead>
          <tbody>
            <?php foreach ($stock_bajo as $v): ?>
            <tr>
              <td>
                <div class="font-bold"><?= htmlspecialchars($v['tela']) ?></div>
                <div class="text-sm text-muted"><?= htmlspecialchars($v['descripcion']) ?></div>
              </td>
              <td class="text-danger font-bold"><?= number_format($v['stock'], 3, ',', '.') ?></td>
              <td><?= $v['unidad'] ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <!-- Top clientes -->
    <?php if (!empty($top_clientes)): ?>
    <div class="card">
      <div class="card-header">
        <span class="card-title">👥 Top clientes</span>
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
              <td>
                <a href="index.php?page=cliente_perfil&id=<?= $c['id'] ?>" class="font-bold">
                  <?= htmlspecialchars($c['nombre']) ?>
                </a>
              </td>
              <td class="text-muted"><?= (int)$c['pedidos'] ?></td>
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
        <span class="card-title">Últimos pedidos</span>
        <a href="index.php?page=pedidos" class="btn btn-sm btn-outline">Ver todos</a>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>#</th><th>Estado</th><th>Total</th><th>Vendedor</th></tr>
          </thead>
          <tbody>
            <?php foreach ($ultimos_pedidos as $p): ?>
            <tr>
              <td>
                <a href="index.php?page=<?= $p['estado']==='abierto' ? 'pedido_abierto' : 'pedido_detalle' ?>&id=<?= $p['id'] ?>"
                   class="font-bold">#<?= $p['id'] ?></a>
              </td>
              <td><span class="badge badge-<?= $p['estado'] ?>"><?= ucfirst($p['estado']) ?></span></td>
              <td>$ <?= number_format($p['total'], 2, ',', '.') ?></td>
              <td class="text-sm"><?= htmlspecialchars($p['vendedor']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($ultimos_pedidos)): ?>
            <tr><td colspan="4" class="text-center text-muted">Sin pedidos aún.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<?php require VIEW_PATH . '/layout/footer.php'; ?>
