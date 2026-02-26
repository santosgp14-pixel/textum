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
          <th>Teléfono</th>
          <th>Email</th>
          <th>Pedidos</th>
          <th>Total comprado</th>
          <th>Última compra</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($clientes as $c): ?>
        <tr>
          <td class="font-bold"><?= htmlspecialchars($c['nombre']) ?></td>
          <td class="text-sm"><?= htmlspecialchars($c['telefono'] ?: '—') ?></td>
          <td class="text-sm"><?= htmlspecialchars($c['email'] ?: '—') ?></td>
          <td><?= (int)$c['total_pedidos'] ?></td>
          <td class="font-bold">$ <?= number_format((float)$c['total_comprado'], 2, ',', '.') ?></td>
          <td class="text-sm text-muted">
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
          <td colspan="7" class="text-center text-muted" style="padding:32px">
            No hay clientes registrados.
            <a href="index.php?page=cliente_nuevo">Crear el primero</a>.
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require VIEW_PATH . '/layout/footer.php'; ?>
