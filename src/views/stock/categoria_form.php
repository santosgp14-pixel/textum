<?php
$esEdicion   = !empty($categoria);
$pageTitle   = $esEdicion ? 'Editar Categoría' : 'Nueva Categoría';
$currentPage = 'stock';
require VIEW_PATH . '/layout/header.php';
?>

<div class="card" style="max-width:400px">
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
        <label class="form-label" for="nombre">Nombre de la categoría *</label>
        <input type="text" id="nombre" name="nombre" class="form-control"
               value="<?= htmlspecialchars($categoria['nombre'] ?? '') ?>"
               placeholder="Ej: Bengalinas, Denim, Lisos, Estampados..." required autofocus>
      </div>

      <div class="form-group">
        <label class="form-label" for="orden">Orden visual</label>
        <input type="number" id="orden" name="orden" class="form-control"
               value="<?= (int)($categoria['orden'] ?? 0) ?>"
               min="0" max="99" style="max-width:100px">
        <div class="text-xs text-muted" style="margin-top:4px">Número menor aparece primero.</div>
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
