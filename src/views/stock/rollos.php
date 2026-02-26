<?php
$pageTitle   = 'Rollos — ' . htmlspecialchars($variante['descripcion']);
$currentPage = 'stock';
require VIEW_PATH . '/layout/header.php';
?>

<div style="margin-bottom:16px" class="flex items-center gap-3 flex-wrap">
  <a href="index.php?page=variantes&tela_id=<?= $variante['tela_id'] ?>" class="btn btn-outline btn-sm">← Variantes</a>
  <span class="text-sm text-muted">
    <?= htmlspecialchars($variante['tela_nombre']) ?> /
    <strong><?= htmlspecialchars($variante['descripcion']) ?></strong>
  </span>
</div>

<!-- Resumen de precios -->
<div class="stats-grid" style="margin-bottom:20px">
  <div class="stat-card">
    <div class="stat-label">Stock total</div>
    <div class="stat-value <?= $variante['stock'] < 5 ? 'text-danger' : '' ?>">
      <?= number_format($variante['stock'], 3, ',', '.') ?>
    </div>
    <div class="stat-sub"><?= $variante['unidad'] ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Costo</div>
    <div class="stat-value" style="font-size:1.1rem">$ <?= number_format($variante['costo'], 2, ',', '.') ?></div>
    <div class="stat-sub">precio de compra</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Precio fraccionado</div>
    <div class="stat-value" style="font-size:1.1rem">$ <?= number_format($variante['precio'], 2, ',', '.') ?></div>
    <div class="stat-sub">por <?= $variante['unidad'] ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Precio por rollo</div>
    <div class="stat-value" style="font-size:1.1rem">$ <?= number_format($variante['precio_rollo'], 2, ',', '.') ?></div>
    <div class="stat-sub">rollo completo</div>
  </div>
</div>

<!-- Tabla de rollos -->
<div class="card">
  <div class="card-header">
    <span class="card-title">Rollos de <?= htmlspecialchars($variante['descripcion']) ?></span>
    <a href="index.php?page=rollo_nuevo&variante_id=<?= $variante['id'] ?>" class="btn btn-primary btn-sm">＋ Nuevo rollo</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>N° Rollo</th>
          <th>Cantidad</th>
          <th>Estado</th>
          <th>Ingresado</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rollos as $r): ?>
        <tr>
          <td class="text-muted text-sm"><?= $r['id'] ?></td>
          <td class="font-bold"><?= htmlspecialchars($r['nro_rollo'] ?: '—') ?></td>
          <td class="font-bold">
            <?= number_format($r['metros'], 3, ',', '.') ?>
            <span class="text-sm text-muted"><?= $variante['unidad'] ?></span>
          </td>
          <td>
            <span class="badge badge-<?= $r['estado'] === 'disponible' ? 'confirmado' : 'anulado' ?>">
              <?= ucfirst($r['estado']) ?>
            </span>
          </td>
          <td class="text-sm"><?= date('d/m/Y', strtotime($r['created_at'])) ?></td>
          <td>
            <div class="flex gap-2">
              <a href="index.php?page=rollo_editar&id=<?= $r['id'] ?>" class="btn btn-sm btn-outline">Editar</a>
              <?php if (Auth::isAdmin()): ?>
              <form method="POST" action="index.php?page=rollo_eliminar"
                    onsubmit="return confirm('¿Eliminar rollo <?= htmlspecialchars($r['nro_rollo'] ?: '#'.$r['id']) ?>? Se descontarán <?= number_format($r['metros'],3,',','.') ?> <?= $variante['unidad'] ?> del stock.')">
                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                <input type="hidden" name="variante_id" value="<?= $variante['id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($rollos)): ?>
        <tr>
          <td colspan="6" class="text-center text-muted" style="padding:32px">
            No hay rollos cargados.
            <a href="index.php?page=rollo_nuevo&variante_id=<?= $variante['id'] ?>">Agregar el primero</a>
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
      <?php if (!empty($rollos)): ?>
      <tfoot>
        <tr style="background:var(--gray-50)">
          <td colspan="2" class="text-right font-bold" style="padding:10px 14px">TOTAL</td>
          <td class="font-bold" style="padding:10px 14px">
            <?= number_format(array_sum(array_column($rollos, 'metros')), 3, ',', '.') ?>
            <?= $variante['unidad'] ?>
          </td>
          <td colspan="3"></td>
        </tr>
      </tfoot>
      <?php endif; ?>
    </table>
  </div>
</div>

<?php require VIEW_PATH . '/layout/footer.php'; ?>
