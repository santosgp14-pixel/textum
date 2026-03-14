<?php
$pageTitle   = 'Categorías';
$currentPage = 'stock';
require VIEW_PATH . '/layout/header.php';

// Separar raíces de sub-categorías para mostrar la jerarquía
$raices = array_filter($categorias, fn($c) => empty($c['parent_id']));
$subMap = [];
foreach ($categorias as $c) {
    if (!empty($c['parent_id'])) $subMap[(int)$c['parent_id']][] = $c;
}
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
          <th>Nombre</th>
          <th>Productos</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($raices as $c): ?>
        <tr>
          <td>
            <span class="font-bold"><?= htmlspecialchars($c['nombre']) ?></span>
            <span class="text-xs text-muted" style="margin-left:6px">orden <?= (int)$c['orden'] ?></span>
          </td>
          <td>
            <a href="index.php?page=stock&cat=<?= $c['id'] ?>" class="badge badge-blue">
              <?= (int)$c['total_productos'] ?> productos
            </a>
          </td>
          <td>
            <a href="index.php?page=categoria_editar&id=<?= $c['id'] ?>" class="btn btn-sm btn-outline">Editar</a>
          </td>
        </tr>
        <?php foreach ($subMap[(int)$c['id']] ?? [] as $sc): ?>
        <tr style="background:var(--gray-50,#f9fafb)">
          <td style="padding-left:28px">
            <span class="text-muted text-xs">↳</span>
            <span style="margin-left:4px"><?= htmlspecialchars($sc['nombre']) ?></span>
            <span class="text-xs text-muted" style="margin-left:6px">orden <?= (int)$sc['orden'] ?></span>
          </td>
          <td>
            <a href="index.php?page=stock&cat=<?= $sc['id'] ?>" class="badge badge-blue">
              <?= (int)$sc['total_productos'] ?> productos
            </a>
          </td>
          <td>
            <a href="index.php?page=categoria_editar&id=<?= $sc['id'] ?>" class="btn btn-sm btn-outline">Editar</a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endforeach; ?>
        <?php if (empty($categorias)): ?>
        <tr>
          <td colspan="3" class="text-center text-muted" style="padding:32px">
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
