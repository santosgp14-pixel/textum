<?php
$pageTitle   = 'Cliente: ' . htmlspecialchars($cliente['nombre']);
$currentPage = 'clientes';
require VIEW_PATH . '/layout/header.php';

// Inicial del cliente para el avatar
$inicial = mb_strtoupper(mb_substr(trim($cliente['nombre']), 0, 1));
?>

<div style="margin-bottom:16px" class="flex items-center gap-3 flex-wrap">
  <a href="index.php?page=clientes" class="btn btn-outline btn-sm">← Clientes</a>
  <a href="index.php?page=cliente_editar&id=<?= $cliente['id'] ?>" class="btn btn-sm btn-outline">✏ Editar</a>
</div>

<!-- Header del cliente -->
<div class="card" style="margin-bottom:20px">
  <div class="card-body">
    <div class="profile-header" style="margin-bottom:20px">
      <div class="profile-avatar"><?= $inicial ?></div>
      <div class="profile-info">
        <div class="profile-name"><?= htmlspecialchars($cliente['nombre']) ?></div>
        <div class="profile-meta">
          <?php if ($cliente['telefono']): ?>
            📞 <?= htmlspecialchars($cliente['telefono']) ?>
          <?php endif; ?>
          <?php if ($cliente['email']): ?>
            <?= $cliente['telefono'] ? ' · ' : '' ?>✉ <?= htmlspecialchars($cliente['email']) ?>
          <?php endif; ?>
          <?php if (!$cliente['telefono'] && !$cliente['email']): ?>
            Sin datos de contacto
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- KPIs del cliente -->
    <div class="stats-grid" style="margin-bottom:0">
      <div class="stat-card stat-card--green">
        <div class="stat-label">Pedidos</div>
        <div class="stat-value"><?= (int)$stats['total_pedidos'] ?></div>
        <div class="stat-sub">confirmados</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Total comprado</div>
        <div class="stat-value stat-value--sm">$ <?= number_format((float)$stats['total_comprado'], 2, ',', '.') ?></div>
        <div class="stat-sub">en pesos</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Última compra</div>
        <div class="stat-value stat-value--sm">
          <?= $stats['ultima_compra'] ? date('d/m/Y', strtotime($stats['ultima_compra'])) : '—' ?>
        </div>
        <div class="stat-sub"><?= $stats['ultima_compra'] ? date('H:i', strtotime($stats['ultima_compra'])) : 'sin pedidos' ?></div>
      </div>
    </div>

    <?php if ($cliente['notas']): ?>
      <div class="alert alert-warn" style="margin-top:16px;font-size:.85rem">
        <span>📝</span><span><strong>Nota:</strong> <?= htmlspecialchars($cliente['notas']) ?></span>
      </div>
    <?php endif; ?>
  </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:20px">

  <!-- Productos más comprados -->
  <?php if (!empty($productos_top)): ?>
  <div class="card">
    <div class="card-header">
      <span class="card-title">Productos frecuentes</span>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Producto</th>
            <th>Cantidad</th>
            <th>Veces</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($productos_top as $p): ?>
          <tr>
            <td>
              <div class="font-bold"><?= htmlspecialchars($p['tela']) ?></div>
              <div class="text-xs text-muted"><?= htmlspecialchars($p['descripcion']) ?></div>
            </td>
            <td class="font-bold">
              <?= number_format($p['total_cantidad'], 3, ',', '.') ?>
              <span class="text-xs text-muted"><?= $p['unidad'] ?></span>
            </td>
            <td><span class="badge badge-blue"><?= (int)$p['veces'] ?>×</span></td>
            <td class="font-bold">$ <?= number_format($p['total_monto'], 2, ',', '.') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <!-- Historial de pedidos -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">Historial de pedidos</span>
    </div>
    <?php if (empty($pedidos)): ?>
    <div class="empty-state">
      <div class="empty-state-icon">🧾</div>
      <div class="empty-state-title">Sin pedidos aún</div>
      <a href="index.php?page=pedido_nuevo" class="btn btn-primary btn-sm">Crear pedido</a>
    </div>
    <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>#</th><th>Fecha</th><th>Estado</th><th class="hide-mobile">Vendedor</th><th>Total</th></tr>
        </thead>
        <tbody>
          <?php foreach ($pedidos as $p): ?>
          <tr>
            <td>
              <a href="index.php?page=<?= $p['estado']==='abierto' ? 'pedido_abierto' : 'pedido_detalle' ?>&id=<?= $p['id'] ?>"
                 class="font-bold">#<?= $p['id'] ?></a>
            </td>
            <td class="text-sm"><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
            <td><span class="badge badge-<?= $p['estado'] ?>"><?= ucfirst($p['estado']) ?></span></td>
            <td class="text-sm text-muted hide-mobile"><?= htmlspecialchars($p['vendedor_nombre']) ?></td>
            <td class="font-bold">$ <?= number_format($p['total'], 2, ',', '.') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

</div>

<?php require VIEW_PATH . '/layout/footer.php'; ?>
