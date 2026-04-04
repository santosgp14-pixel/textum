<?php
$pageTitle   = 'Pedidos';
$currentPage = 'pedidos';
require VIEW_PATH . '/layout/header.php';

// Conteos por estado para los tabs
$cntAbiertos   = count(array_filter($pedidos, fn($p) => $p['estado'] === 'abierto'));
$cntConfirmados = count(array_filter($pedidos, fn($p) => $p['estado'] === 'confirmado'));
$cntAnulados   = count(array_filter($pedidos, fn($p) => $p['estado'] === 'anulado'));
$filtroEstado  = $_GET['estado'] ?? '';
?>

<div class="card">
  <div class="card-header">
    <span class="card-title">Pedidos</span>
    <a href="index.php?page=pedido_nuevo" class="btn btn-primary btn-sm">＋ Nuevo Pedido</a>
  </div>

  <!-- Filtros por estado -->
  <div style="padding:12px 20px;border-bottom:1px solid var(--gray-200)">
    <div class="filter-tabs">
      <a href="index.php?page=pedidos"
         class="filter-tab <?= $filtroEstado === '' ? 'active' : '' ?>">
        Todos <span class="tab-count">(<?= count($pedidos) ?>)</span>
      </a>
      <?php if ($cntAbiertos): ?>
      <a href="index.php?page=pedidos&estado=abierto"
         class="filter-tab <?= $filtroEstado === 'abierto' ? 'active' : '' ?>"
         style="<?= $filtroEstado !== 'abierto' ? 'border-color:#fde68a;color:#92400e;background:#fffbeb' : '' ?>">
        ⏳ Abiertos <span class="tab-count">(<?= $cntAbiertos ?>)</span>
      </a>
      <?php endif; ?>
      <?php if ($cntConfirmados): ?>
      <a href="index.php?page=pedidos&estado=confirmado"
         class="filter-tab <?= $filtroEstado === 'confirmado' ? 'active' : '' ?>">
        ✓ Confirmados <span class="tab-count">(<?= $cntConfirmados ?>)</span>
      </a>
      <?php endif; ?>
      <?php if ($cntAnulados): ?>
      <a href="index.php?page=pedidos&estado=anulado"
         class="filter-tab <?= $filtroEstado === 'anulado' ? 'active' : '' ?>">
        Anulados <span class="tab-count">(<?= $cntAnulados ?>)</span>
      </a>
      <?php endif; ?>
    </div>
  </div>

  <div class="table-wrap">
    <?php
    $pedidosFiltrados = $filtroEstado
      ? array_values(array_filter($pedidos, fn($p) => $p['estado'] === $filtroEstado))
      : $pedidos;
    ?>
    <?php if (empty($pedidosFiltrados)): ?>
    <div class="empty-state">
      <div class="empty-state-icon">🧾</div>
      <div class="empty-state-title">
        <?= $filtroEstado ? 'No hay pedidos ' . $filtroEstado . 's' : 'Aún no hay pedidos' ?>
      </div>
      <div class="empty-state-text">Cuando crees un pedido, aparecerá aquí.</div>
      <a href="index.php?page=pedido_nuevo" class="btn btn-primary btn-sm">＋ Crear pedido</a>
    </div>
    <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Fecha</th>
          <th>Estado</th>
          <th class="hide-mobile">Items</th>
          <th>Total</th>
          <th>Cliente</th>
          <th class="hide-mobile">Vendedor</th>
          <th>Acción</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($pedidosFiltrados as $p): ?>
        <tr>
          <td class="font-bold text-muted">#<?= $p['id'] ?></td>
          <td class="text-sm"><?= date('d/m/Y H:i', strtotime($p['created_at'])) ?></td>
          <td><span class="badge badge-<?= $p['estado'] ?>"><?= ucfirst($p['estado']) ?></span></td>
          <td class="hide-mobile text-muted"><?= (int)$p['total_items'] ?> ítems</td>
          <td class="font-bold">$ <?= number_format($p['total'], 2, ',', '.') ?></td>
          <td class="text-sm">
            <?php if (!empty($p['cliente_nombre'])): ?>
              <a href="index.php?page=cliente_perfil&id=<?= $p['cliente_id'] ?>"><?= htmlspecialchars($p['cliente_nombre']) ?></a>
            <?php else: ?>
              <span class="text-muted">—</span>
            <?php endif; ?>
          </td>
          <td class="text-sm text-muted hide-mobile"><?= htmlspecialchars($p['vendedor']) ?></td>
          <td>
            <?php if ($p['estado'] === 'abierto'): ?>
              <a href="index.php?page=pedido_abierto&id=<?= $p['id'] ?>" class="btn btn-sm btn-warning">Abrir</a>
            <?php else: ?>
              <a href="index.php?page=pedido_detalle&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline">Ver</a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<?php require VIEW_PATH . '/layout/footer.php'; ?>
