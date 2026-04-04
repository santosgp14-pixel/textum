<?php
$pageTitle   = 'Variantes — ' . $tela['nombre'];
$currentPage = 'stock';
require VIEW_PATH . '/layout/header.php';
?>

<div class="card">
  <div class="card-header">
    <div>
      <span class="card-title">Variantes de <?= htmlspecialchars($tela['nombre']) ?></span>
      <div class="text-sm text-muted mt-1"><?= $tela['rinde'] ? 'Rinde: ' . number_format($tela['rinde'], 3, ',', '.') . ' m/kg' : '' ?></div>
    </div>
    <div class="flex gap-2 flex-wrap">
      <a href="index.php?page=variante_nueva&tela_id=<?= $tela['id'] ?>" class="btn btn-primary btn-sm">＋ Nueva variante</a>
      <a href="index.php?page=stock" class="btn btn-sm btn-outline">← Telas</a>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Descripción</th>
          <th class="hide-mobile">Código barras</th>
          <th class="hide-mobile">Unidad</th>
          <th class="hide-mobile">Mínimo venta</th>
          <th class="hide-mobile">Costo</th>
          <th class="hide-mobile">$ rollo</th>
          <th>$ frac.</th>
          <th>Stock</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($variantes as $v): ?>
        <tr>
          <td class="font-bold"><?= htmlspecialchars($v['descripcion']) ?></td>
          <td class="hide-mobile"><code style="font-size:.82rem;background:#f3f4f6;padding:2px 6px;border-radius:4px"><?= htmlspecialchars($v['codigo_barras']) ?></code></td>
          <td class="hide-mobile"><?= $v['unidad'] ?></td>
          <td class="hide-mobile"><?= number_format($v['minimo_venta'], 3, ',', '.') ?> <?= $v['unidad'] ?></td>
          <td class="hide-mobile text-muted">$ <?= number_format($v['costo'], 2, ',', '.') ?></td>
          <td class="hide-mobile">$ <?= number_format($v['precio_rollo'], 2, ',', '.') ?></td>
          <td class="font-bold">$ <?= number_format($v['precio'], 2, ',', '.') ?></td>
          <td>
            <span class="<?= $v['stock'] < 5 ? 'text-danger font-bold' : 'text-success font-bold' ?>">
              <?= number_format($v['stock'], 3, ',', '.') ?>
            </span>
            <?= $v['unidad'] ?>
          </td>
          <td>
            <div class="flex gap-2">
              <a href="index.php?page=rollos&variante_id=<?= $v['id'] ?>" class="btn btn-sm btn-primary">Rollos</a>
              <a href="index.php?page=variante_editar&id=<?= $v['id'] ?>" class="btn btn-sm btn-outline">Editar</a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($variantes)): ?>
        <tr>
          <td colspan="10">
            <div class="empty-state">
              <div class="empty-state-icon">🎨</div>
              <div class="empty-state-title">No hay variantes</div>
              <div class="empty-state-text">Agregá colores o variantes a este producto.</div>
              <a href="index.php?page=variante_nueva&tela_id=<?= $tela['id'] ?>" class="btn btn-primary btn-sm">＋ Nueva variante</a>
            </div>
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require VIEW_PATH . '/layout/footer.php'; ?>
