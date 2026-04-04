<?php
$pageTitle   = 'Clientes';
$currentPage = 'clientes';
require VIEW_PATH . '/layout/header.php';
?>

<div class="card">
  <div class="card-header">
    <span class="card-title">Clientes</span>
    <a href="index.php?page=cliente_nuevo" class="btn btn-primary btn-sm">＋ Nuevo cliente</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Nombre</th>
          <th class="hide-mobile">Teléfono</th>
          <th class="hide-mobile">Email</th>
          <th class="hide-mobile">Pedidos</th>
          <th>Total</th>
          <th class="hide-mobile">Última compra</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($clientes as $c): ?>
        <tr>
          <td class="font-bold"><?= htmlspecialchars($c['nombre']) ?></td>
          <td class="text-sm hide-mobile"><?= htmlspecialchars($c['telefono'] ?: '—') ?></td>
          <td class="text-sm hide-mobile"><?= htmlspecialchars($c['email'] ?: '—') ?></td>
          <td class="hide-mobile"><?= (int)$c['total_pedidos'] ?></td>
          <td class="font-bold">$ <?= number_format((float)$c['total_comprado'], 2, ',', '.') ?></td>
          <td class="text-sm text-muted hide-mobile">
            <?= $c['ultima_compra'] ? date('d/m/Y', strtotime($c['ultima_compra'])) : '—' ?>
          </td>
          <td>
            <div class="flex gap-2">
              <a href="index.php?page=cliente_perfil&id=<?= $c['id'] ?>" class="btn btn-sm btn-primary">Ver perfil</a>
              <a href="index.php?page=cliente_editar&id=<?= $c['id'] ?>" class="btn btn-sm btn-outline">Editar</a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($clientes)): ?>
        <tr>
          <td colspan="7">
            <div class="empty-state">
              <div class="empty-state-icon">👥</div>
              <div class="empty-state-title">No hay clientes registrados</div>
              <div class="empty-state-text">Creá el primer cliente para asociarlo a tus pedidos.</div>
              <a href="index.php?page=cliente_nuevo" class="btn btn-primary btn-sm">＋ Nuevo cliente</a>
            </div>
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require VIEW_PATH . '/layout/footer.php'; ?>
