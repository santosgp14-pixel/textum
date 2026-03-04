<?php
$esEdicion   = !empty($tela);
$pageTitle   = $esEdicion ? 'Editar Producto' : 'Nuevo Producto';
$currentPage = 'stock';
require VIEW_PATH . '/layout/header.php';
?>

<div class="card" style="max-width:560px">
  <div class="card-header">
    <span class="card-title"><?= $pageTitle ?></span>
    <a href="index.php?page=stock" class="btn btn-sm btn-outline">← Volver</a>
  </div>
  <div class="card-body">
    <form method="POST" action="index.php?page=tela_guardar">
      <?php if ($esEdicion): ?>
        <input type="hidden" name="id" value="<?= $tela['id'] ?>">
      <?php endif; ?>

      <?php if (!empty($categorias)): ?>
      <div class="form-group">
        <label class="form-label" for="categoria_id">Categoría</label>
        <select id="categoria_id" name="categoria_id" class="form-control">
          <option value="">— Sin categoría —</option>
          <?php foreach ($categorias as $cat): ?>
          <option value="<?= $cat['id'] ?>"
            <?= ($tela['categoria_id'] ?? 0) == $cat['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($cat['nombre']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>

      <div class="form-group">
        <label class="form-label" for="nombre">Nombre del producto *</label>
        <input type="text" id="nombre" name="nombre" class="form-control"
               value="<?= htmlspecialchars($tela['nombre'] ?? '') ?>"
               placeholder="Ej: Bengalina Lisa, Denim, Polar Soft" required autofocus>
      </div>

      <div class="form-group">
        <label class="form-label" for="composicion">Composición</label>
        <input type="text" id="composicion" name="composicion" class="form-control"
               value="<?= htmlspecialchars($tela['composicion'] ?? '') ?>"
               placeholder="Ej: 65% polyester / 35% algodón">
      </div>

      <div class="form-group">
        <label class="form-label" for="descripcion">Descripción</label>
        <textarea id="descripcion" name="descripcion" class="form-control" rows="3"
                  placeholder="Descripción adicional..."><?= htmlspecialchars($tela['descripcion'] ?? '') ?></textarea>
      </div>

      <div class="flex gap-3 mt-4">
        <button type="submit" class="btn btn-primary">
          <?= $esEdicion ? 'Guardar cambios' : 'Crear producto' ?>
        </button>
        <a href="index.php?page=stock" class="btn btn-outline">Cancelar</a>
      </div>
    </form>
  </div>
</div>

<?php require VIEW_PATH . '/layout/footer.php'; ?>
