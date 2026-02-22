<?php
$pageTitle   = 'Variantes — ' . $tela['nombre'];
$currentPage = 'stock';
require VIEW_PATH . '/layout/header.php';
?>

<div class="card">
  <div class="card-header">
    <div>
      <span class="card-title">Variantes de <?= htmlspecialchars($tela['nombre']) ?></span>
      <div class="text-sm text-muted mt-1"><?= htmlspecialchars($tela['composicion'] ?? '') ?></div>
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
          <th>Código barras</th>
          <th>Unidad</th>
          <th>Mínimo venta</th>
          <th>Precio</th>
          <th>Stock</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($variantes as $v): ?>
        <tr>
          <td class="font-bold"><?= htmlspecialchars($v['descripcion']) ?></td>
          <td><code style="font-size:.82rem;background:#f3f4f6;padding:2px 6px;border-radius:4px"><?= htmlspecialchars($v['codigo_barras']) ?></code></td>
          <td><?= $v['unidad'] ?></td>
          <td><?= number_format($v['minimo_venta'], 3, ',', '.') ?> <?= $v['unidad'] ?></td>
          <td class="font-bold">$ <?= number_format($v['precio'], 2, ',', '.') ?></td>
          <td>
            <span class="<?= $v['stock'] < 5 ? 'text-danger font-bold' : 'text-success font-bold' ?>">
              <?= number_format($v['stock'], 3, ',', '.') ?>
            </span>
            <?= $v['unidad'] ?>
          </td>
          <td>
            <a href="index.php?page=variante_editar&id=<?= $v['id'] ?>" class="btn btn-sm btn-outline">Editar</a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($variantes)): ?>
        <tr>
          <td colspan="7" class="text-center text-muted" style="padding:32px">
            No hay variantes.
            <a href="index.php?page=variante_nueva&tela_id=<?= $tela['id'] ?>">Crear la primera</a>
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require VIEW_PATH . '/layout/footer.php'; ?>
