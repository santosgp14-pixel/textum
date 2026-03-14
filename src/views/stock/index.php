<?php
$pageTitle   = 'Productos';
$currentPage = 'stock';
require VIEW_PATH . '/layout/header.php';
$catFiltro = (int)($_GET['cat'] ?? 0);
?>

<?php if (!empty($categorias)): ?>
<div class="flex gap-2 flex-wrap mb-4 items-center">
  <a href="index.php?page=stock"
     class="btn btn-sm <?= !$catFiltro ? 'btn-primary' : 'btn-outline' ?>">Todas</a>
  <?php foreach ($categorias as $cat): ?>
  <a href="index.php?page=stock&cat=<?= $cat['id'] ?>"
     class="btn btn-sm <?= $catFiltro == $cat['id'] ? 'btn-primary' : 'btn-outline' ?>">
    <?= htmlspecialchars($cat['nombre']) ?>
  </a>
  <?php endforeach; ?>
  <a href="index.php?page=categorias" class="btn btn-sm btn-outline" style="margin-left:auto">⚙ Categorías</a>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-header">
    <span class="card-title">Productos<?= $catFiltro ? ' — '.htmlspecialchars(array_column($categorias,'nombre','id')[$catFiltro] ?? '') : '' ?></span>
    <div class="flex gap-2">
      <?php if (empty($categorias)): ?>
      <a href="index.php?page=categorias" class="btn btn-sm btn-outline">⊕ Agregar categorías</a>
      <?php endif; ?>
      <a href="index.php?page=tela_nueva" class="btn btn-primary btn-sm">＋ Nuevo Producto</a>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Producto</th>
          <th>Categoría</th>
          <th>Categoría</th>
          <th>Colores / variantes</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($telas as $t): ?>
        <tr>
          <td>
            <div class="font-bold"><?= htmlspecialchars($t['nombre']) ?></div>
            <?php if ($t['descripcion']): ?>
              <div class="text-sm text-muted hide-mobile"><?= htmlspecialchars($t['descripcion']) ?></div>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($t['categoria_nombre']): ?>
              <a href="index.php?page=stock&cat=<?= $t['categoria_id'] ?>" class="badge badge-blue">
                <?= htmlspecialchars($t['categoria_nombre']) ?>
              </a>
            <?php else: ?>
              <span class="text-muted text-sm">—</span>
            <?php endif; ?>
          </td>
          <td class="text-sm">
            <?php if (!empty($t['tipo'])): ?>
              <span class="badge <?= $t['tipo'] === 'punto' ? 'badge-blue' : 'badge-gray' ?>"><?= ucfirst($t['tipo']) ?></span>
            <?php endif; ?>
            <?php if (!empty($t['subcategoria'])): ?>
              <span class="text-xs text-muted" style="display:block;margin-top:2px"><?= htmlspecialchars($t['subcategoria']) ?></span>
            <?php endif; ?>
          </td>
          <td>
            <span class="badge badge-blue"><?= (int)$t['variantes_activas'] ?> colores</span>
          </td>
          <td>
            <div class="flex gap-2">
              <a href="index.php?page=variantes&tela_id=<?= $t['id'] ?>" class="btn btn-sm btn-primary">Variantes</a>
              <a href="index.php?page=tela_editar&id=<?= $t['id'] ?>"    class="btn btn-sm btn-outline">Editar</a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($telas)): ?>
        <tr>
          <td colspan="5" class="text-center text-muted" style="padding:32px">
            No hay productos cargados.
            <a href="index.php?page=tela_nueva">Crear el primero</a>
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require VIEW_PATH . '/layout/footer.php'; ?>
