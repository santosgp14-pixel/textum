<?php
$pageTitle   = 'Clientes';
$currentPage = 'clientes';
require VIEW_PATH . '/layout/header.php';
?>

<div class="card">
  <div class="card-header">
    <span class="card-title">Clientes</span>
    <div class="flex gap-2 items-center">
      <div class="table-search-wrap" style="width:200px">
        <svg class="search-icon" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input type="search" class="table-search-input" id="search-clientes" placeholder="Buscar…">
        <button class="search-clear" title="Limpiar">✕</button>
      </div>
      <a href="index.php?page=cliente_nuevo" class="btn btn-primary btn-sm">＋ Nuevo cliente</a>
    </div>
  </div>
  <div class="table-wrap">
    <table id="tabla-clientes">
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
        <tr data-href="index.php?page=cliente_perfil&id=<?= $c['id'] ?>">
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
<script>
initTableSearch('search-clientes', 'tabla-clientes');
initRowLinks('tabla-clientes');
</script>
