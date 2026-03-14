<?php
$esEdicion   = !empty($categoria);
$pageTitle   = $esEdicion ? 'Editar Categoría' : 'Nueva Categoría';
$currentPage = 'stock';

// Solo categorías raíz como posibles padres (no permitir sub-sub-categorías)
$catsPadre = array_filter($todasCategorias ?? [], fn($c) => empty($c['parent_id']));
// Excluir la categoría que se está editando para evitar auto-referencia
if ($esEdicion) {
    $catsPadre = array_filter($catsPadre, fn($c) => $c['id'] !== $categoria['id']);
}

require VIEW_PATH . '/layout/header.php';
?>

<div class="card" style="max-width:420px">
  <div class="card-header">
    <span class="card-title"><?= $pageTitle ?></span>
    <a href="index.php?page=categorias" class="btn btn-sm btn-outline">← Volver</a>
  </div>
  <div class="card-body">
    <form method="POST" action="index.php?page=categoria_guardar">
      <?php if ($esEdicion): ?>
        <input type="hidden" name="id" value="<?= $categoria['id'] ?>">
      <?php endif; ?>

      <div class="form-group">
        <label class="form-label" for="parent_id">Es sub-categoría de <span class="text-muted text-xs">(opcional)</span></label>
        <select id="parent_id" name="parent_id" class="form-control">
          <option value="">— Categoría raíz —</option>
          <?php foreach ($catsPadre as $p): ?>
          <option value="<?= $p['id'] ?>"
            <?= ($categoria['parent_id'] ?? 0) == $p['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($p['nombre']) ?>
          </option>
          <?php endforeach; ?>
        </select>
        <div class="text-xs text-muted mt-1">Dejá vacío para crear una categoría principal.</div>
      </div>

      <div class="form-group">
        <label class="form-label" for="nombre">Nombre *</label>
        <input type="text" id="nombre" name="nombre" class="form-control"
               value="<?= htmlspecialchars($categoria['nombre'] ?? '') ?>"
               placeholder="Ej: Bengalinas, Denim, Lisos, Estampados..." required autofocus>
      </div>

      <div class="form-group">
        <label class="form-label" for="orden">Orden visual</label>
        <input type="number" id="orden" name="orden" class="form-control"
               value="<?= (int)($categoria['orden'] ?? 0) ?>"
               min="0" max="99" style="max-width:100px">
        <div class="text-xs text-muted mt-1">Número menor aparece primero.</div>
      </div>

      <div class="flex gap-3 mt-4">
        <button type="submit" class="btn btn-primary">
          <?= $esEdicion ? 'Guardar cambios' : 'Crear categoría' ?>
        </button>
        <a href="index.php?page=categorias" class="btn btn-outline">Cancelar</a>
      </div>
    </form>
  </div>
</div>

<?php require VIEW_PATH . '/layout/footer.php'; ?>
