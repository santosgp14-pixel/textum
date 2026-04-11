<?php
$pageTitle   = 'Proveedores';
$currentPage = 'proveedores';
require VIEW_PATH . '/layout/header.php';
?>

<?php if (empty($proveedores)): ?>
<div class="empty-state">
  <div class="empty-state-icon">🏭</div>
  <div class="empty-state-title">Sin proveedores cargados</div>
  <div class="empty-state-sub">Registrá tus proveedores para tenerlos a mano.</div>
  <a href="index.php?page=proveedor_nuevo" class="btn btn-primary mt-4">Agregar proveedor</a>
</div>
<?php else: ?>

<div class="card">
  <div class="card-header">
    <span class="card-title">Proveedores</span>
    <div class="flex gap-2 items-center">
      <div class="table-search-wrap">
        <svg class="search-icon" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input type="search" class="table-search-input" id="search-proveedores" placeholder="Buscar…">
        <button class="search-clear" title="Limpiar">✕</button>
      </div>
      <a href="index.php?page=proveedor_nuevo" class="btn btn-primary btn-sm">＋ Nuevo proveedor</a>
    </div>
  </div>
  <div class="table-wrap">
    <table id="tabla-proveedores">
      <thead>
        <tr>
          <th>Nombre</th>
          <th class="hide-mobile">CUIT</th>
          <th class="hide-mobile">Teléfono</th>
          <th class="hide-mobile">Email</th>
          <th>Estado</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($proveedores as $p): ?>
        <tr>
          <td>
            <div class="font-bold"><?= htmlspecialchars($p['nombre']) ?></div>
            <?php if (!empty($p['notas'])): ?>
              <div class="text-xs text-muted"><?= htmlspecialchars(mb_strimwidth($p['notas'], 0, 60, '…')) ?></div>
            <?php endif; ?>
          </td>
          <td class="hide-mobile text-sm"><?= htmlspecialchars($p['cuit'] ?? '—') ?></td>
          <td class="hide-mobile text-sm">
            <?php if (!empty($p['telefono'])): ?>
              <a href="tel:<?= htmlspecialchars($p['telefono']) ?>"><?= htmlspecialchars($p['telefono']) ?></a>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td class="hide-mobile text-sm">
            <?php if (!empty($p['email'])): ?>
              <a href="mailto:<?= htmlspecialchars($p['email']) ?>"><?= htmlspecialchars($p['email']) ?></a>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td>
            <?php if ($p['activo']): ?>
              <span class="badge badge-confirmado">Activo</span>
            <?php else: ?>
              <span class="badge badge-anulado">Inactivo</span>
            <?php endif; ?>
          </td>
          <td>
            <div class="flex gap-2">
              <a href="index.php?page=proveedor_editar&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline">Editar</a>
              <?php if (Auth::isAdmin()): ?>
              <form method="POST" action="index.php?page=proveedor_eliminar" data-confirm="¿Eliminar proveedor <?= htmlspecialchars(addslashes($p['nombre'])) ?>?">
                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php endif; ?>

<?php require VIEW_PATH . '/layout/footer.php'; ?>
<script>
initTableSearch('search-proveedores', 'tabla-proveedores');
// Async confirm for delete forms
document.querySelectorAll('form[data-confirm]').forEach(form => {
  form.addEventListener('submit', async e => {
    e.preventDefault();
    const ok = await Confirm({ title: form.dataset.confirm, icon: '\uD83D\uDDD1', okText: 'Eliminar' });
    if (ok) form.submit();
  });
});
</script>
