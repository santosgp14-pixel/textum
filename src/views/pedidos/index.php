<?php
$pageTitle   = 'Pedidos';
$currentPage = 'pedidos';
require VIEW_PATH . '/layout/header.php';

$cntAbiertos    = count(array_filter($pedidos, fn($p) => $p['estado'] === 'abierto'));
$cntConfirmados = count(array_filter($pedidos, fn($p) => $p['estado'] === 'confirmado'));
$cntAnulados    = count(array_filter($pedidos, fn($p) => $p['estado'] === 'anulado'));
$cntActivos     = $cntAbiertos + $cntConfirmados;
$idsAbiertos    = array_values(array_map(fn($p) => $p['id'], array_filter($pedidos, fn($p) => $p['estado'] === 'abierto')));
$filtroEstado   = $_GET['estado'] ?? '';
?>

<!-- Page header -->
<div class="page-header">
  <div>
    <div class="page-header-title">Pedidos</div>
    <div class="page-header-sub"><?= $cntActivos ?> pedido<?= $cntActivos != 1 ? 's' : '' ?> activo<?= $cntActivos != 1 ? 's' : '' ?></div>
  </div>
  <a href="index.php?page=pedido_nuevo" class="btn btn-primary">＋ Nuevo Pedido</a>
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
          Activos <span class="tab-count"><?= $cntActivos ?></span>
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
      : array_values(array_filter($pedidos, fn($p) => $p['estado'] !== 'anulado'));
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
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<!-- Modal de confirmación de anulación -->
<div id="modal-anular-bulk" style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,.45);align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:12px;padding:28px;width:100%;max-width:440px;box-shadow:0 20px 60px rgba(0,0,0,.25);margin:16px">
    <h3 style="margin:0 0 6px;font-size:1.1rem;font-weight:700" id="modal-anular-bulk-title">Anular pedidos</h3>
    <p id="modal-anular-bulk-desc" style="margin:0 0 16px;color:#6b7280;font-size:.9rem"></p>
    <label style="display:block;font-size:.85rem;font-weight:600;margin-bottom:6px;color:#374151">Motivo de anulación <span style="color:#ef4444">*</span></label>
    <textarea id="modal-anular-bulk-motivo" rows="3" placeholder="Ej: Error de carga, duplicado…"
      style="width:100%;box-sizing:border-box;border:1px solid #d1d5db;border-radius:8px;padding:10px;font-size:.9rem;resize:vertical;font-family:inherit"></textarea>
    <p id="modal-anular-bulk-error" style="display:none;color:#ef4444;font-size:.82rem;margin:6px 0 0">Ingresá un motivo para continuar.</p>
    <div style="display:flex;gap:10px;margin-top:20px;justify-content:flex-end">
      <button id="modal-anular-bulk-cancel" class="btn btn-outline btn-sm">Cancelar</button>
      <button id="modal-anular-bulk-confirm" class="btn btn-danger btn-sm">Confirmar anulación</button>
    </div>
  </div>
</div>

<script>
(function() {
  var _anularIds = [];

  function openAnularModal(ids, titulo, desc) {
    _anularIds = ids;
    document.getElementById('modal-anular-bulk-title').textContent = titulo;
    document.getElementById('modal-anular-bulk-desc').textContent  = desc;
    document.getElementById('modal-anular-bulk-motivo').value      = '';
    document.getElementById('modal-anular-bulk-error').style.display = 'none';
    document.getElementById('modal-anular-bulk-confirm').disabled  = false;
    document.getElementById('modal-anular-bulk-confirm').textContent = 'Confirmar anulación';
    document.getElementById('modal-anular-bulk').style.display = 'flex';
    setTimeout(function() { document.getElementById('modal-anular-bulk-motivo').focus(); }, 80);
  }

  function closeAnularModal() {
    document.getElementById('modal-anular-bulk').style.display = 'none';
  }

  function updateBulkToolbar() {
    var checked = document.querySelectorAll('.chk-pedido:checked');
    var toolbar  = document.getElementById('bulk-toolbar');
    var countEl  = document.getElementById('bulk-count');
    if (!toolbar) return;
    if (checked.length > 0) {
      toolbar.style.display = 'flex';
      countEl.textContent = checked.length + ' seleccionado' + (checked.length > 1 ? 's' : '');
    } else {
      toolbar.style.display = 'none';
    }
  }

  document.addEventListener('DOMContentLoaded', function() {
    if (typeof initTableSearch === 'function') initTableSearch('search-pedidos', 'tabla-pedidos');
    if (typeof initRowLinks    === 'function') initRowLinks('tabla-pedidos');

    // ── Modal: cerrar ────────────────────────────────────────
    var modalEl = document.getElementById('modal-anular-bulk');
    document.getElementById('modal-anular-bulk-cancel').addEventListener('click', closeAnularModal);
    if (modalEl) {
      modalEl.addEventListener('click', function(e) { if (e.target === this) closeAnularModal(); });
    }

    // ── Modal: confirmar ─────────────────────────────────────
    document.getElementById('modal-anular-bulk-confirm').addEventListener('click', function() {
      var motivo = document.getElementById('modal-anular-bulk-motivo').value.trim();
      var errorEl = document.getElementById('modal-anular-bulk-error');
      if (!motivo) { errorEl.style.display = 'block'; return; }
      errorEl.style.display = 'none';
      this.disabled = true;
      this.textContent = 'Anulando…';

      var fd = new FormData();
      fd.append('motivo', motivo);
      var url;
      if (_anularIds.length > 1) {
        url = 'index.php?page=pedido_anular_seleccionados';
        _anularIds.forEach(function(id) { fd.append('ids[]', id); });
      } else {
        url = 'index.php?page=pedido_anular';
        fd.append('pedido_id', _anularIds[0]);
      }

      fetch(url, { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(data) {
          if (data.ok) {
            window.location.href = data.redirect || 'index.php?page=pedidos';
          } else {
            alert('Error: ' + (data.msg || 'No se pudo anular.'));
            document.getElementById('modal-anular-bulk-confirm').disabled = false;
            document.getElementById('modal-anular-bulk-confirm').textContent = 'Confirmar anulación';
          }
        })
        .catch(function() {
          alert('Error de conexión.');
          document.getElementById('modal-anular-bulk-confirm').disabled = false;
          document.getElementById('modal-anular-bulk-confirm').textContent = 'Confirmar anulación';
        });
    });

    // ── Checkbox "seleccionar todos" ─────────────────────────
    var chkAll = document.getElementById('chk-all');
    if (chkAll) {
      chkAll.addEventListener('change', function() {
        document.querySelectorAll('.chk-pedido').forEach(function(c) { c.checked = chkAll.checked; });
        updateBulkToolbar();
      });
    }

    // ── Checkboxes individuales ──────────────────────────────
    document.querySelectorAll('.chk-pedido').forEach(function(c) {
      c.addEventListener('change', function() {
        var all     = document.querySelectorAll('.chk-pedido');
        var checked = document.querySelectorAll('.chk-pedido:checked');
        if (chkAll) {
          chkAll.indeterminate = (checked.length > 0 && checked.length < all.length);
          chkAll.checked       = (checked.length === all.length && all.length > 0);
        }
        updateBulkToolbar();
      });
    });

    // ── Deseleccionar todo ────────────────────────────────────
    var btnDeselectAll = document.getElementById('btn-deselect-all');
    if (btnDeselectAll) {
      btnDeselectAll.addEventListener('click', function() {
        document.querySelectorAll('.chk-pedido').forEach(function(c) { c.checked = false; });
        if (chkAll) { chkAll.checked = false; chkAll.indeterminate = false; }
        updateBulkToolbar();
      });
    }

    // ── Anular seleccionados ──────────────────────────────────
    var btnAnularSel = document.getElementById('btn-anular-seleccionados');
    if (btnAnularSel) {
      btnAnularSel.addEventListener('click', function() {
        var checked = document.querySelectorAll('.chk-pedido:checked');
        if (!checked.length) return;
        var ids = Array.from(checked).map(function(c) { return c.dataset.id; });
        openAnularModal(
          ids,
          'Anular ' + ids.length + ' pedido' + (ids.length > 1 ? 's' : ''),
          ids.length > 1
            ? 'Se anularán los pedidos #' + ids.join(', #') + '. Los pedidos confirmados repondrán su stock.'
            : 'Se anulará el pedido #' + ids[0] + '.'
        );
      });
    }

  });
})();
</script>

<?php require VIEW_PATH . '/layout/footer.php'; ?>