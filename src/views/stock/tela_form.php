<?php
$esEdicion   = !empty($tela);
$pageTitle   = $esEdicion ? 'Editar Tela' : 'Nueva Tela';
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

      <div class="form-group">
        <label class="form-label" for="nombre">Nombre de la tela *</label>
        <input type="text" id="nombre" name="nombre" class="form-control"
               value="<?= htmlspecialchars($tela['nombre'] ?? '') ?>"
               placeholder="Ej: Gabardina Premium" required autofocus>
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
          <?= $esEdicion ? 'Guardar cambios' : 'Crear tela' ?>
        </button>
        <a href="index.php?page=stock" class="btn btn-outline">Cancelar</a>
      </div>
    </form>
  </div>
</div>

<?php require VIEW_PATH . '/layout/footer.php'; ?>
