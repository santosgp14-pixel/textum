<?php
$pageTitle   = 'Cliente: ' . htmlspecialchars($cliente['nombre']);
$currentPage = 'clientes';
require VIEW_PATH . '/layout/header.php';
?>

<div style="margin-bottom:16px" class="flex items-center gap-3 flex-wrap">
  <a href="index.php?page=clientes" class="btn btn-outline btn-sm">← Clientes</a>
  <a href="index.php?page=cliente_editar&id=<?= $cliente['id'] ?>" class="btn btn-sm btn-outline">✏ Editar</a>
</div>

<!-- Header del cliente -->
<div class="card" style="margin-bottom:20px">
  <div class="card-body">
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:16px">
      <div>
        <div class="text-xs text-muted">Cliente</div>
        <div class="font-bold" style="font-size:1.15rem"><?= htmlspecialchars($cliente['nombre']) ?></div>
        <?php if ($cliente['telefono']): ?>
          <div class="text-sm text-muted" style="margin-top:2px"><?= htmlspecialchars($cliente['telefono']) ?></div>
        <?php endif; ?>
        <?php if ($cliente['email']): ?>
          <div class="text-sm text-muted"><?= htmlspecialchars($cliente['email']) ?></div>
        <?php endif; ?>
      </div>
      <div>
        <div class="text-xs text-muted">Pedidos confirmados</div>
        <div class="font-bold" style="font-size:1.6rem"><?= (int)$stats['total_pedidos'] ?></div>
      </div>
      <div>
        <div class="text-xs text-muted">Total comprado</div>
        <div class="font-bold" style="font-size:1.2rem">
          $ <?= number_format((float)$stats['total_comprado'], 2, ',', '.') ?>
        </div>
      </div>
      <div>
        <div class="text-xs text-muted">Última compra</div>
        <div class="font-bold">
          <?= $stats['ultima_compra'] ? date('d/m/Y', strtotime($stats['ultima_compra'])) : '—' ?>
        </div>
      </div>
    </div>
    <?php if ($cliente['notas']): ?>
      <div class="alert alert-warn" style="margin-top:16px;font-size:.85rem">
        <strong>Notas:</strong> <?= htmlspecialchars($cliente['notas']) ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:20px">

  <!-- Productos más comprados -->
  <?php if (!empty($productos_top)): ?>
  <div class="card">
    <div class="card-header">
      <span class="card-title">📊 Productos más comprados</span>
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
            <td>
              <?= number_format($p['total_cantidad'], 3, ',', '.') ?>
              <span class="text-xs text-muted"><?= $p['unidad'] ?></span>
            </td>
            <td class="text-muted"><?= (int)$p['veces'] ?></td>
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
      <span class="card-title">🧾 Historial de pedidos</span>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>#</th><th>Fecha</th><th>Estado</th><th>Vendedor</th><th>Total</th></tr>
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
            <td class="text-sm text-muted"><?= htmlspecialchars($p['vendedor_nombre']) ?></td>
            <td class="font-bold">$ <?= number_format($p['total'], 2, ',', '.') ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($pedidos)): ?>
          <tr>
            <td colspan="5" class="text-center text-muted" style="padding:24px">
              Sin pedidos aún.
            </td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php require VIEW_PATH . '/layout/footer.php'; ?>
