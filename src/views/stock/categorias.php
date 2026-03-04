<?php
$pageTitle   = 'Categorías';
$currentPage = 'stock';
require VIEW_PATH . '/layout/header.php';
?>

<div class="card">
  <div class="card-header">
    <span class="card-title">Categorías de productos</span>
    <a href="index.php?page=categoria_nueva" class="btn btn-primary btn-sm">＋ Nueva categoría</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Orden</th>
          <th>Nombre</th>
          <th>Productos</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($categorias as $c): ?>
        <tr>
          <td class="text-muted text-sm" style="width:60px"><?= (int)$c['orden'] ?></td>
          <td class="font-bold"><?= htmlspecialchars($c['nombre']) ?></td>
          <td>
            <a href="index.php?page=stock&cat=<?= $c['id'] ?>" class="badge badge-blue">
              <?= (int)$c['total_productos'] ?> productos
            </a>
          </td>
          <td>
            <a href="index.php?page=categoria_editar&id=<?= $c['id'] ?>" class="btn btn-sm btn-outline">Editar</a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($categorias)): ?>
        <tr>
          <td colspan="4" class="text-center text-muted" style="padding:32px">
            No hay categorías.
            <a href="index.php?page=categoria_nueva">Crear la primera</a>.
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="card-body" style="border-top:1px solid var(--gray-100);padding-top:12px">
    <a href="index.php?page=stock" class="btn btn-sm btn-outline">← Volver a Productos</a>
  </div>
</div>

<?php require VIEW_PATH . '/layout/footer.php'; ?>
