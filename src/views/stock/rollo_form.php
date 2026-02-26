<?php
$esEdicion   = !empty($rollo);
$pageTitle   = $esEdicion ? 'Editar Rollo' : 'Nuevo Rollo';
$currentPage = 'stock';
require VIEW_PATH . '/layout/header.php';
?>

<div class="card" style="max-width:480px">
  <div class="card-header">
    <div>
      <span class="card-title"><?= $pageTitle ?></span>
      <div class="text-sm text-muted mt-1">
        <?= htmlspecialchars($variante['tela_nombre']) ?> —
        <?= htmlspecialchars($variante['descripcion']) ?>
      </div>
    </div>
    <a href="index.php?page=rollos&variante_id=<?= $variante['id'] ?>" class="btn btn-sm btn-outline">← Volver</a>
  </div>
  <div class="card-body">
    <form method="POST" action="index.php?page=rollo_guardar">
      <input type="hidden" name="variante_id" value="<?= $variante['id'] ?>">
      <?php if ($esEdicion): ?>
        <input type="hidden" name="id" value="<?= $rollo['id'] ?>">
      <?php endif; ?>

      <div class="form-group">
        <label class="form-label" for="nro_rollo">N° de Rollo</label>
        <input type="text" id="nro_rollo" name="nro_rollo" class="form-control"
               value="<?= htmlspecialchars($rollo['nro_rollo'] ?? '') ?>"
               placeholder="Ej: R001, A-23, Lote-7..." autofocus>
        <div class="text-xs text-muted" style="margin-top:4px">Identificador interno del rollo. Opcional.</div>
      </div>

      <div class="form-group">
        <label class="form-label" for="metros">
          Cantidad en <?= $variante['unidad'] ?>s *
        </label>
        <input type="number" id="metros" name="metros" class="form-control"
               value="<?= $rollo['metros'] ?? '' ?>"
               step="0.001" min="0.001" required
               placeholder="Ej: 45.500">
        <?php if (!$esEdicion): ?>
          <div class="text-xs text-muted" style="margin-top:4px">
            Se sumará al stock de esta variante.
            Stock actual: <strong><?= number_format($variante['stock'], 3, ',', '.') ?> <?= $variante['unidad'] ?></strong>
          </div>
        <?php else: ?>
          <div class="text-xs text-muted" style="margin-top:4px">
            Editar ajustará el stock por la diferencia respecto al valor anterior
            (<strong><?= number_format($rollo['metros'], 3, ',', '.') ?> <?= $variante['unidad'] ?></strong>).
          </div>
        <?php endif; ?>
      </div>

      <div class="flex gap-3 mt-4">
        <button type="submit" class="btn btn-primary">
          <?= $esEdicion ? 'Guardar cambios' : 'Agregar rollo' ?>
        </button>
        <a href="index.php?page=rollos&variante_id=<?= $variante['id'] ?>" class="btn btn-outline">Cancelar</a>
      </div>
    </form>
  </div>
</div>

<?php require VIEW_PATH . '/layout/footer.php'; ?>
