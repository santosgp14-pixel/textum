<?php
$pageTitle   = 'Pedidos';
$currentPage = 'pedidos';
require VIEW_PATH . '/layout/header.php';
?>

<div class="card">
  <div class="card-header">
    <span class="card-title">Pedidos</span>
    <a href="index.php?page=pedido_nuevo" class="btn btn-primary btn-sm">＋ Nuevo Pedido</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Fecha</th>
          <th>Estado</th>
          <th>Items</th>
          <th>Total</th>
          <th>Cliente</th>
          <th>Vendedor</th>
          <th>Acción</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($pedidos as $p): ?>
        <tr>
          <td class="font-bold">#<?= $p['id'] ?></td>
          <td class="text-sm"><?= date('d/m/Y H:i', strtotime($p['created_at'])) ?></td>
          <td><span class="badge badge-<?= $p['estado'] ?>"><?= ucfirst($p['estado']) ?></span></td>
          <td><?= (int)$p['total_items'] ?></td>
          <td class="font-bold">$ <?= number_format($p['total'], 2, ',', '.') ?></td>
          <td class="text-sm">
            <?php if (!empty($p['cliente_nombre'])): ?>
              <a href="index.php?page=cliente_perfil&id=<?= $p['cliente_id'] ?>"><?= htmlspecialchars($p['cliente_nombre']) ?></a>
            <?php else: ?>
              <span class="text-muted">—</span>
            <?php endif; ?>
          </td>
          <td class="text-sm"><?= htmlspecialchars($p['vendedor']) ?></td>
          <td>
            <?php if ($p['estado'] === 'abierto'): ?>
              <a href="index.php?page=pedido_abierto&id=<?= $p['id'] ?>" class="btn btn-sm btn-warning">Abrir</a>
            <?php else: ?>
              <a href="index.php?page=pedido_detalle&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline">Ver</a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($pedidos)): ?>
        <tr>
          <td colspan="8" class="text-center text-muted" style="padding:32px">
            No hay pedidos aún. <a href="index.php?page=pedido_nuevo">Crear el primero</a>.
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require VIEW_PATH . '/layout/footer.php'; ?>
