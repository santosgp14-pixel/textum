<?php
$pageTitle   = 'Stock / Telas';
$currentPage = 'stock';
require VIEW_PATH . '/layout/header.php';
?>

<div class="card">
  <div class="card-header">
    <span class="card-title">Telas registradas</span>
    <a href="index.php?page=tela_nueva" class="btn btn-primary btn-sm">＋ Nueva Tela</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Nombre</th>
          <th>Composición</th>
          <th>Variantes</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($telas as $t): ?>
        <tr>
          <td class="text-muted text-sm"><?= $t['id'] ?></td>
          <td>
            <div class="font-bold"><?= htmlspecialchars($t['nombre']) ?></div>
            <?php if ($t['descripcion']): ?>
              <div class="text-sm text-muted"><?= htmlspecialchars($t['descripcion']) ?></div>
            <?php endif; ?>
          </td>
          <td class="text-sm"><?= htmlspecialchars($t['composicion'] ?? '—') ?></td>
          <td>
            <span class="badge badge-blue"><?= (int)$t['variantes_activas'] ?> variantes</span>
          </td>
          <td>
            <a href="index.php?page=variantes&tela_id=<?= $t['id'] ?>" class="btn btn-sm btn-outline">Ver variantes</a>
            <a href="index.php?page=tela_editar&id=<?= $t['id'] ?>"    class="btn btn-sm btn-outline">Editar</a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($telas)): ?>
        <tr>
          <td colspan="5" class="text-center text-muted" style="padding:32px">
            No hay telas cargadas.
            <a href="index.php?page=tela_nueva">Crear la primera</a>
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require VIEW_PATH . '/layout/footer.php'; ?>
