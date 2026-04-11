<?php /* Product card partial — used by catalogo/index.php */ ?>
<div class="product-card">
  <?php if (!empty($t['imagen_url'])): ?>
    <img src="<?= htmlspecialchars($t['imagen_url']) ?>" alt="<?= htmlspecialchars($t['nombre']) ?>" class="product-img" loading="lazy">
  <?php else: ?>
    <div class="product-img-placeholder">🧵</div>
  <?php endif; ?>
  <div class="product-body">
    <?php if (!empty($t['tipo'])): ?>
      <div><span class="badge-tipo"><?= ucfirst(htmlspecialchars($t['tipo'])) ?></span></div>
    <?php endif; ?>
    <div class="product-name"><?= htmlspecialchars($t['nombre']) ?></div>
    <?php if (!empty($t['descripcion'])): ?>
      <div class="product-desc"><?= htmlspecialchars($t['descripcion']) ?></div>
    <?php endif; ?>
    <?php if (!empty($t['variantes_desc'])): ?>
      <div class="product-variants"><?= htmlspecialchars($t['variantes_desc']) ?></div>
    <?php endif; ?>
    <div class="product-footer">
      <div class="product-price">
        <?php if (!empty($t['precio_desde'])): ?>
          $ <?= number_format($t['precio_desde'], 2, ',', '.') ?>
        <?php else: ?>
          Consultar
        <?php endif; ?>
      </div>
      <?php if ((float)$t['stock_total'] > 0): ?>
        <span class="product-stock-ok">✓ Disponible</span>
      <?php else: ?>
        <span class="product-stock-no">Sin stock</span>
      <?php endif; ?>
    </div>
  </div>
</div>
