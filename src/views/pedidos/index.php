<?php
$pageTitle   = 'Pedidos';
$currentPage = 'pedidos';
require VIEW_PATH . '/layout/header.php';

$cntAbiertos   = count(array_filter($pedidos, fn($p) => $p['estado'] === 'abierto'));
$cntConfirmados = count(array_filter($pedidos, fn($p) => $p['estado'] === 'confirmado'));
$cntAnulados   = count(array_filter($pedidos, fn($p) => $p['estado'] === 'anulado'));
$filtroEstado  = $_GET['estado'] ?? '';
?>

<!-- Page header -->
<div class="page-header">
  <div>
    <div class="page-header-title">Pedidos</div>
    <div class="page-header-sub"><?= count($pedidos) ?> pedido<?= count($pedidos) != 1 ? 's' : '' ?> en total</div>
  </div>
  <a href="index.php?page=pedido_nuevo" class="btn btn-primary">
    ＋ Nuevo Pedido
    <span class="kbd" style="background:rgba(255,255,255,.18);border-color:rgba(255,255,255,.25);color:#fff">N</span>
  </a>
  <?php if ($cntAbiertos > 1 && Auth::isAdmin()): ?>
  <button id="btn-anular-todos" class="btn btn-danger btn-sm"
          data-count="<?= $cntAbiertos ?>">
    Anular todos los abiertos (<?= $cntAbiertos ?>)
  </button>
  <?php endif; ?>
</div>

<!-- Barra de acciones masivas -->
<div id="bulk-toolbar" style="display:none;padding:10px 20px;background:#fef3c7;border:1px solid #fde68a;border-bottom:none;border-radius:8px 8px 0 0;align-items:center;gap:12px">
  <span id="bulk-count" style="font-size:.9rem;font-weight:600;color:#92400e">0 seleccionados</span>
  <button id="btn-anular-seleccionados" class="btn btn-danger btn-sm">Anular seleccionados</button>
  <button id="btn-deselect-all" class="btn btn-sm btn-outline" style="margin-left:auto">Deseleccionar todo</button>
</div>

<div class="card">
  <!-- Filtros -->
  <div style="padding:14px 20px;border-bottom:1px solid var(--gray-100)">
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;justify-content:space-between">
      <div class="filter-tabs" style="margin-bottom:0">
        <a href="index.php?page=pedidos"
           class="filter-tab <?= $filtroEstado === '' ? 'active' : '' ?>">
          Todos <span class="tab-count"><?= count($pedidos) ?></span>
        </a>
        <?php if ($cntAbiertos): ?>
        <a href="index.php?page=pedidos&estado=abierto"
           class="filter-tab <?= $filtroEstado === 'abierto' ? 'active' : '' ?>"
           style="<?= $filtroEstado !== 'abierto' ? 'border-color:#fde68a;color:#92400e;background:#fffbeb' : '' ?>">
          Abiertos <span class="tab-count"><?= $cntAbiertos ?></span>
        </a>
        <?php endif; ?>
        <?php if ($cntConfirmados): ?>
        <a href="index.php?page=pedidos&estado=confirmado"
           class="filter-tab <?= $filtroEstado === 'confirmado' ? 'active' : '' ?>">
          Confirmados <span class="tab-count"><?= $cntConfirmados ?></span>
        </a>
        <?php endif; ?>
        <?php if ($cntAnulados): ?>
        <a href="index.php?page=pedidos&estado=anulado"
           class="filter-tab <?= $filtroEstado === 'anulado' ? 'active' : '' ?>">
          Anulados <span class="tab-count"><?= $cntAnulados ?></span>
        </a>
        <?php endif; ?>
      </div>
      <div class="table-search-wrap">
        <svg class="search-icon" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input type="search" class="table-search-input" id="search-pedidos" placeholder="Buscar… (/)">
        <button class="search-clear" title="Limpiar">✕</button>
      </div>
    </div>
  </div>

  <!-- Tabla -->
  <div class="table-wrap">
    <?php
    $pedidosFiltrados = $filtroEstado
      ? array_values(array_filter($pedidos, fn($p) => $p['estado'] === $filtroEstado))
      : $pedidos;
    ?>
    <?php if (empty($pedidosFiltrados)): ?>
    <div class="empty-state">
      <div class="empty-state-icon">🧾</div>
      <div class="empty-state-title">
        <?= $filtroEstado ? 'No hay pedidos ' . $filtroEstado . 's' : 'Aún no hay pedidos' ?>
      </div>
      <div class="empty-state-text">Cuando crees un pedido, aparecerá aquí.</div>
      <a href="index.php?page=pedido_nuevo" class="btn btn-primary btn-sm">＋ Crear pedido</a>
    </div>
    <?php else: ?>
    <table id="tabla-pedidos">
      <thead>
        <tr>
          <th style="width:36px"><input type="checkbox" id="chk-all" title="Seleccionar todos"></th>
          <th>#</th>
          <th>Fecha</th>
          <th>Estado</th>
          <th>Total</th>
          <th>Cliente</th>
          <th class="hide-mobile">Vendedor</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($pedidosFiltrados as $p): ?>
        <tr data-href="index.php?page=pedido_detalle&id=<?= $p['id'] ?>" data-id="<?= $p['id'] ?>" data-estado="<?= $p['estado'] ?>">
          <td onclick="event.stopPropagation()" style="vertical-align:middle">
            <?php if (in_array($p['estado'], ['abierto','confirmado'])): ?>
            <input type="checkbox" class="chk-pedido" data-id="<?= $p['id'] ?>">
            <?php endif; ?>
          </td>
          <td>
            <span class="font-bold text-muted">#<?= $p['id'] ?></span>
          </td>
          <td class="text-sm text-muted"><?= date('d/m/Y', strtotime($p['created_at'])) ?>
            <div class="text-xs text-muted"><?= date('H:i', strtotime($p['created_at'])) ?></div>
          </td>
          <td><span class="badge badge-<?= $p['estado'] ?>"><?= ucfirst($p['estado']) ?></span></td>
          <td class="font-bold">$ <?= number_format($p['total'], 2, ',', '.') ?>
            <div class="text-xs text-muted"><?= (int)$p['total_items'] ?> ítem<?= $p['total_items'] != 1 ? 's' : '' ?></div>
          </td>
          <td class="text-sm">
            <?php if (!empty($p['cliente_nombre'])): ?>
              <span class="font-bold"><?= htmlspecialchars($p['cliente_nombre']) ?></span>
            <?php else: ?>
              <span class="text-muted">—</span>
            <?php endif; ?>
          </td>
          <td class="text-sm text-muted hide-mobile"><?= htmlspecialchars($p['vendedor']) ?></td>
          <td onclick="event.stopPropagation()" style="white-space:nowrap">
            <?php if ($p['estado'] === 'abierto'): ?>
              <a href="index.php?page=pedido_abierto&id=<?= $p['id'] ?>" class="btn btn-sm btn-primary">Continuar</a>
              <button class="btn btn-sm btn-danger btn-anular-row" data-id="<?= $p['id'] ?>">Anular</button>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<?php require VIEW_PATH . '/layout/footer.php'; ?>
<script>
initTableSearch('search-pedidos', 'tabla-pedidos');
initRowLinks('tabla-pedidos');

// ── Selección múltiple ──────────────────────────────────────────
function updateBulkToolbar() {
  var checked = document.querySelectorAll('.chk-pedido:checked');
  var toolbar = document.getElementById('bulk-toolbar');
  var countEl = document.getElementById('bulk-count');
  if (checked.length > 0) {
    toolbar.style.display = 'flex';
    countEl.textContent = checked.length + ' seleccionado' + (checked.length > 1 ? 's' : '');
  } else {
    toolbar.style.display = 'none';
  }
}

document.getElementById('chk-all').addEventListener('change', function() {
  document.querySelectorAll('.chk-pedido').forEach(function(c) { c.checked = this.checked; }, this);
  updateBulkToolbar();
});

document.querySelectorAll('.chk-pedido').forEach(function(c) {
  c.addEventListener('change', function() {
    var all = document.querySelectorAll('.chk-pedido');
    var checked = document.querySelectorAll('.chk-pedido:checked');
    document.getElementById('chk-all').indeterminate = (checked.length > 0 && checked.length < all.length);
    document.getElementById('chk-all').checked = (checked.length === all.length);
    updateBulkToolbar();
  });
});

document.getElementById('btn-deselect-all').addEventListener('click', function() {
  document.querySelectorAll('.chk-pedido').forEach(function(c) { c.checked = false; });
  document.getElementById('chk-all').checked = false;
  document.getElementById('chk-all').indeterminate = false;
  updateBulkToolbar();
});

document.getElementById('btn-anular-seleccionados').addEventListener('click', function() {
  var checked = document.querySelectorAll('.chk-pedido:checked');
  if (!checked.length) return;
  var ids = Array.from(checked).map(function(c) { return c.dataset.id; });
  var motivo = prompt('Motivo de anulación para los ' + ids.length + ' pedidos seleccionados:');
  if (!motivo || !motivo.trim()) return;
  document.getElementById('btn-anular-seleccionados').disabled = true;
  document.getElementById('btn-anular-seleccionados').textContent = 'Anulando...';
  var fd = new FormData();
  ids.forEach(function(id) { fd.append('ids[]', id); });
  fd.append('motivo', motivo.trim());
  fetch('index.php?page=pedido_anular_seleccionados', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (data.ok) window.location.href = data.redirect;
      else alert('Error: ' + data.msg);
    });
});

// Anular pedido individual desde la lista
document.querySelectorAll('.btn-anular-row').forEach(btn => {
  btn.addEventListener('click', function() {
    const id = this.dataset.id;
    const motivo = prompt('Motivo de anulación del pedido #' + id + ':');
    if (!motivo || !motivo.trim()) return;
    const fd = new FormData();
    fd.append('pedido_id', id);
    fd.append('motivo', motivo.trim());
    fetch('index.php?page=pedido_anular', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        if (data.ok) location.reload();
        else alert('Error: ' + data.msg);
      });
  });
});

// Anular todos los abiertos
const btnAnularTodos = document.getElementById('btn-anular-todos');
if (btnAnularTodos) {
  btnAnularTodos.addEventListener('click', function() {
    const n = this.dataset.count;
    if (!confirm('¿Anular los ' + n + ' pedidos abiertos? Esta acción no se puede deshacer.')) return;
    btnAnularTodos.disabled = true;
    btnAnularTodos.textContent = 'Anulando...';
    fetch('index.php?page=pedido_anular_todos', { method: 'POST' })
      .then(r => r.json())
      .then(data => {
        if (data.ok) window.location.href = data.redirect;
        else alert('Error al anular.');
      });
  });
}
</script>