<?php
$pageTitle   = 'Productos';
$currentPage = 'stock';
require VIEW_PATH . '/layout/header.php';
$catFiltro     = (int)($_GET['cat'] ?? 0);
$mostrarColCat = !empty($categorias) && !empty(array_filter($telas, fn($t) => !empty($t['categoria_nombre'])));
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
  <div class="card-header" style="flex-wrap:wrap;gap:8px">
    <span class="card-title">Productos<?= $catFiltro ? ' — '.htmlspecialchars(array_column($categorias,'nombre','id')[$catFiltro] ?? '') : '' ?></span>
    <div class="flex gap-2 items-center flex-wrap">
      <div class="table-search-wrap">
        <svg class="search-icon" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input type="search" class="table-search-input" id="search-stock" placeholder="Buscar…">
        <button class="search-clear" title="Limpiar">✕</button>
      </div>
      <?php if (empty($categorias)): ?>
      <a href="index.php?page=categorias" class="btn btn-sm btn-outline">⊕ Categorías</a>
      <?php endif; ?>
      <a href="index.php?page=tela_nueva" class="btn btn-primary btn-sm">＋ Nuevo</a>
    </div>
  </div>
  <div class="table-wrap">
    <table id="tabla-stock">
      <thead>
        <tr>
          <th>Producto</th>
          <?php if ($mostrarColCat): ?><th class="hide-mobile">Categoría</th><?php endif; ?>
          <th class="hide-mobile">Tipo</th>
          <th class="hide-mobile">Rinde</th>
          <th class="hide-mobile">Rinde</th>
          <th class="hide-mobile">Ancho</th>
          <th class="hide-mobile">Colores</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($telas as $t): ?>
        <tr data-href="index.php?page=variantes&tela_id=<?= $t['id'] ?>">
          <td>
            <div class="font-bold"><?= htmlspecialchars($t['nombre']) ?></div>
            <?php if ($t['descripcion']): ?>
              <div class="text-sm text-muted hide-mobile"><?= htmlspecialchars($t['descripcion']) ?></div>
            <?php endif; ?>
          </td>
          <?php if ($mostrarColCat): ?>
          <td class="hide-mobile">
            <?php if ($t['categoria_nombre']): ?>
              <a href="index.php?page=stock&cat=<?= $t['categoria_id'] ?>" class="badge badge-blue">
                <?= htmlspecialchars($t['categoria_nombre']) ?>
              </a>
            <?php else: ?>
              <span class="text-muted text-sm">—</span>
            <?php endif; ?>
          </td>
          <?php endif; ?>
          <td class="hide-mobile text-sm">
            <?php if (!empty($t['tipo'])): ?>
              <span class="badge <?= $t['tipo'] === 'punto' ? 'badge-blue' : 'badge-gray' ?>"><?= ucfirst($t['tipo']) ?></span>
            <?php endif; ?>
            <?php if (!empty($t['subcategoria'])): ?>
              <span class="badge badge-gray" style="margin-left:4px"><?= ucfirst($t['subcategoria']) ?></span>
            <?php endif; ?>
          </td>
          <td class="hide-mobile text-sm">
            <?= !empty($t['rinde']) ? number_format((float)$t['rinde'], 3, ',', '') . ' m/kg' : '<span class="text-muted">—</span>' ?>
          </td>
          <td class="hide-mobile text-sm">
            <?= !empty($t['ancho']) ? number_format((float)$t['ancho'], 2, ',', '') . ' m' : '<span class="text-muted">—</span>' ?>
          </td>
          <td class="hide-mobile">
            <span class="badge badge-blue"><?= (int)$t['variantes_activas'] ?> color<?= $t['variantes_activas'] != 1 ? 'es' : '' ?></span>
          </td>
          <td>
            <div class="flex gap-2">
              <a href="index.php?page=variantes&tela_id=<?= $t['id'] ?>" class="btn btn-sm btn-primary">Ver variantes</a>
              <a href="index.php?page=tela_editar&id=<?= $t['id'] ?>"    class="btn btn-sm btn-outline">Editar</a>
              <form method="POST" action="index.php?page=tela_eliminar"
                    onsubmit="return confirm('¿Eliminar <?= addslashes(htmlspecialchars($t['nombre'])) ?>? Se desactivarán también sus variantes.')"
                    style="display:inline">
                <input type="hidden" name="id" value="<?= $t['id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($telas)): ?>
        <tr>
          <td colspan="5">
            <div class="empty-state">
              <div class="empty-state-icon">📦</div>
              <div class="empty-state-title">No hay productos cargados</div>
              <div class="empty-state-text">Creá el primer producto para empezar a gestionar el stock.</div>
              <a href="index.php?page=tela_nueva" class="btn btn-primary btn-sm">＋ Crear producto</a>
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
initTableSearch('search-stock', 'tabla-stock');
initRowLinks('tabla-stock');
</script>
