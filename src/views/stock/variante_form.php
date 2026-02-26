<?php
$esEdicion   = !empty($variante);
$pageTitle   = $esEdicion ? 'Editar Variante' : 'Nueva Variante';
$currentPage = 'stock';
require VIEW_PATH . '/layout/header.php';
?>

<div class="card" style="max-width:600px">
  <div class="card-header">
    <div>
      <span class="card-title"><?= $pageTitle ?></span>
      <div class="text-sm text-muted mt-1">Tela: <?= htmlspecialchars($tela['nombre']) ?></div>
    </div>
    <a href="index.php?page=variantes&tela_id=<?= $tela['id'] ?>" class="btn btn-sm btn-outline">← Volver</a>
  </div>
  <div class="card-body">
    <form method="POST" action="index.php?page=variante_guardar">
      <input type="hidden" name="tela_id" value="<?= $tela['id'] ?>">
      <?php if ($esEdicion): ?>
        <input type="hidden" name="id" value="<?= $variante['id'] ?>">
      <?php endif; ?>

      <div class="form-group">
        <label class="form-label" for="descripcion">Descripción *</label>
        <input type="text" id="descripcion" name="descripcion" class="form-control"
               value="<?= htmlspecialchars($variante['descripcion'] ?? '') ?>"
               placeholder="Ej: Azul marino, ancho 1.50m" required autofocus>
      </div>

      <div class="form-group">
        <label class="form-label" for="codigo_barras">Código de barras *</label>
        <input type="text" id="codigo_barras" name="codigo_barras" class="form-control barcode-input"
               value="<?= htmlspecialchars($variante['codigo_barras'] ?? '') ?>"
               placeholder="Escanear o escribir código" required>
        <div class="text-xs text-muted" style="margin-top:4px">Debe ser único para esta empresa.</div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div class="form-group">
          <label class="form-label" for="unidad">Unidad de venta *</label>
          <select id="unidad" name="unidad" class="form-control">
            <option value="metro" <?= ($variante['unidad'] ?? 'metro') === 'metro' ? 'selected' : '' ?>>Metro</option>
            <option value="kilo"  <?= ($variante['unidad'] ?? '') === 'kilo'  ? 'selected' : '' ?>>Kilo</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="minimo_venta">Mínimo de venta *</label>
          <input type="number" id="minimo_venta" name="minimo_venta" class="form-control"
                 value="<?= $variante['minimo_venta'] ?? '0.100' ?>"
                 step="0.001" min="0.001" required>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px">
        <div class="form-group">
          <label class="form-label" for="costo">Costo ($)</label>
          <input type="number" id="costo" name="costo" class="form-control"
                 value="<?= $variante['costo'] ?? '0.00' ?>"
                 step="0.01" min="0">
          <div class="text-xs text-muted" style="margin-top:4px">Precio de compra</div>
        </div>
        <div class="form-group">
          <label class="form-label" for="precio_rollo">Precio rollo ($)</label>
          <input type="number" id="precio_rollo" name="precio_rollo" class="form-control"
                 value="<?= $variante['precio_rollo'] ?? '0.00' ?>"
                 step="0.01" min="0">
          <div class="text-xs text-muted" style="margin-top:4px">Venta rollo completo</div>
        </div>
        <div class="form-group">
          <label class="form-label" for="precio">Precio fraccionado ($) *</label>
          <input type="number" id="precio" name="precio" class="form-control"
                 value="<?= $variante['precio'] ?? '0.00' ?>"
                 step="0.01" min="0" required>
          <div class="text-xs text-muted" style="margin-top:4px">Por <?= $variante['unidad'] ?? 'metro' ?> / kilo</div>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="stock">Stock inicial</label>
        <input type="number" id="stock" name="stock" class="form-control"
               value="<?= $variante['stock'] ?? '0.000' ?>"
               step="0.001" min="0">
        <div class="text-xs text-muted" style="margin-top:4px">
          Solo para carga inicial. Gestioná el stock desde el módulo de <strong>Rollos</strong>.
        </div>
      </div>

      <div class="flex gap-3 mt-4">
        <button type="submit" class="btn btn-primary">
          <?= $esEdicion ? 'Guardar cambios' : 'Crear variante' ?>
        </button>
        <a href="index.php?page=variantes&tela_id=<?= $tela['id'] ?>" class="btn btn-outline">Cancelar</a>
      </div>
    </form>
  </div>
</div>

<?php require VIEW_PATH . '/layout/footer.php'; ?>
