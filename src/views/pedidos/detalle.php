<?php
$pageTitle   = 'Pedido #' . $pedido['id'];
$currentPage = 'pedidos';
require VIEW_PATH . '/layout/header.php';
?>

<!-- Page header -->
<div class="page-header">
  <div class="flex items-center gap-3 flex-wrap">
    <a href="index.php?page=pedidos" class="btn btn-outline btn-sm">← Volver</a>
    <div>
      <div class="page-header-title" style="font-size:1.15rem">Pedido #<?= $pedido['id'] ?></div>
      <div class="page-header-sub"><?= date('d/m/Y H:i', strtotime($pedido['created_at'])) ?> — <?= htmlspecialchars($pedido['vendedor_nombre']) ?></div>
    </div>
    <span class="badge badge-<?= $pedido['estado'] ?>" style="font-size:.8rem;padding:4px 12px"><?= ucfirst($pedido['estado']) ?></span>
  </div>
  <div class="flex gap-2 flex-wrap">
    <?php if ($pedido['estado'] === 'confirmado'): ?>
    <?php $reciboUrl = BASE_URL . '/index.php?page=recibo_pub&pedido=' . $pedido['id'] . '&t=' . urlencode($receiptToken); ?>
    <button type="button" class="btn btn-sm btn-outline" id="btn-copy-recibo" data-url="<?= htmlspecialchars($reciboUrl) ?>">
      Copiar enlace
    </button>
    <a href="<?= htmlspecialchars($reciboUrl) ?>" target="_blank" class="btn btn-sm btn-outline">Ver recibo</a>
    <?php endif; ?>
    <?php if ($pedido['estado'] === 'confirmado' && Auth::isAdmin()): ?>
    <button id="btn-anular" class="btn btn-sm btn-danger" data-pedido-id="<?= $pedido['id'] ?>">Anular</button>
    <?php endif; ?>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr;gap:20px;max-width:820px">

  <!-- Meta del pedido -->
  <div class="card">
    <div class="card-body">
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:20px">
        <div>
          <div class="text-xs text-muted" style="font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">Vendedor</div>
          <div class="font-bold"><?= htmlspecialchars($pedido['vendedor_nombre']) ?></div>
        </div>
        <div>
          <div class="text-xs text-muted" style="font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">Cliente</div>
          <div class="font-bold">
            <?php if (!empty($pedido['cliente_nombre'])): ?>
              <a href="index.php?page=cliente_perfil&id=<?= $pedido['cliente_id'] ?>"><?= htmlspecialchars($pedido['cliente_nombre']) ?></a>
            <?php else: ?>
              <span class="text-muted" style="font-weight:400">Sin asignar</span>
            <?php endif; ?>
          </div>
        </div>
        <?php if ($pedido['confirmado_at']): ?>
        <div>
          <div class="text-xs text-muted" style="font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">Confirmado</div>
          <div class="font-bold"><?= date('d/m/Y H:i', strtotime($pedido['confirmado_at'])) ?></div>
        </div>
        <?php endif; ?>
        <?php if ($pedido['estado'] === 'anulado'): ?>
        <div>
          <div class="text-xs text-muted" style="font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">Anulado</div>
          <div class="text-danger font-bold"><?= date('d/m/Y H:i', strtotime($pedido['anulado_at'])) ?></div>
        </div>
        <?php endif; ?>
        <div>
          <div class="text-xs text-muted" style="font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">Total</div>
          <div class="font-bold" style="font-size:1.3rem;letter-spacing:-.02em">$ <?= number_format($pedido['total'], 2, ',', '.') ?></div>
        </div>
      </div>

      <?php if ($pedido['motivo_anulacion']): ?>
      <div class="alert alert-error mt-4" style="margin-top:16px">
        <strong>Motivo de anulación:</strong> <?= htmlspecialchars($pedido['motivo_anulacion']) ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Items -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">Artículos</span>
      <span class="badge badge-gray"><?= count($items) ?> ítem<?= count($items) != 1 ? 's' : '' ?></span>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Producto</th><th class="hide-mobile">Cantidad</th><th class="hide-mobile">Precio unit.</th><th>Subtotal</th></tr>
        </thead>
        <tbody>
          <?php foreach ($items as $item): ?>
          <tr>
            <td>
              <div class="font-bold"><?= htmlspecialchars($item['tela_nombre']) ?></div>
              <div class="text-sm text-muted"><?= htmlspecialchars($item['descripcion']) ?></div>
              <div class="text-xs text-muted hide-mobile"><?= $item['codigo_barras'] ?></div>
            </td>
            <td class="hide-mobile">
              <span class="font-bold"><?= number_format($item['cantidad'], 3, ',', '.') ?></span>
              <span class="text-sm text-muted"> <?= $item['unidad'] ?></span>
            </td>
            <td class="hide-mobile text-sm text-muted">$ <?= number_format($item['precio_unit'], 2, ',', '.') ?></td>
            <td class="font-bold">$ <?= number_format($item['subtotal'], 2, ',', '.') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr style="border-top:2px solid var(--gray-100);background:var(--gray-50)">
            <td colspan="3" class="text-right" style="padding:12px 14px;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--gray-400)">Total del pedido</td>
            <td style="padding:12px 14px;font-weight:800;font-size:1.2rem;letter-spacing:-.02em">
              $ <?= number_format($pedido['total'], 2, ',', '.') ?>
            </td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>

  <!-- Resumen para imprimir/compartir -->
  <?php if ($pedido['estado'] === 'confirmado'): ?>
  <div class="card" id="resumen-card">
    <div class="card-header">
      <span class="card-title">Recibo del pedido</span>
      <button onclick="window.print()" class="btn btn-sm btn-outline no-print">Imprimir</button>
    </div>
    <div class="card-body" style="padding:20px 24px">
      <div class="resumen-header">
        <div class="resumen-logo">Text<span>um</span></div>
        <div>
          <div class="resumen-num">Pedido #<?= $pedido['id'] ?></div>
          <div class="resumen-fecha"><?= date('d/m/Y H:i', strtotime($pedido['created_at'])) ?></div>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px;margin:16px 0">
        <div><div class="text-xs text-muted">Cliente</div><div class="font-bold"><?= htmlspecialchars($pedido['cliente_nombre'] ?? 'Sin asignar') ?></div></div>
        <div><div class="text-xs text-muted">Vendedor</div><div class="font-bold"><?= htmlspecialchars($pedido['vendedor_nombre']) ?></div></div>
        <div><div class="text-xs text-muted">Estado</div><div class="font-bold"><?= ucfirst($pedido['estado']) ?></div></div>
        <?php if ($pedido['confirmado_at']): ?>
        <div><div class="text-xs text-muted">Confirmado</div><div class="font-bold"><?= date('d/m/Y H:i', strtotime($pedido['confirmado_at'])) ?></div></div>
        <?php endif; ?>
      </div>

      <table style="width:100%;border-collapse:collapse;font-size:.88rem">
        <thead>
          <tr style="border-bottom:2px solid var(--gray-200)">
            <th style="text-align:left;padding:8px 4px;color:var(--gray-500)">Artículo</th>
            <th style="text-align:right;padding:8px 4px;color:var(--gray-500)">Cant.</th>
            <th style="text-align:right;padding:8px 4px;color:var(--gray-500)">Precio</th>
            <th style="text-align:right;padding:8px 4px;color:var(--gray-500)">Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $item): ?>
          <tr style="border-bottom:1px solid var(--gray-100)">
            <td style="padding:8px 4px">
              <div style="font-weight:600"><?= htmlspecialchars($item['tela_nombre']) ?></div>
              <div style="font-size:.78rem;color:var(--gray-400)"><?= htmlspecialchars($item['descripcion']) ?></div>
            </td>
            <td style="text-align:right;padding:8px 4px;white-space:nowrap"><?= number_format($item['cantidad'], 3, ',', '.') ?> <?= $item['unidad'] ?></td>
            <td style="text-align:right;padding:8px 4px;white-space:nowrap;color:var(--gray-500)">$ <?= number_format($item['precio_unit'], 2, ',', '.') ?></td>
            <td style="text-align:right;padding:8px 4px;font-weight:700;white-space:nowrap">$ <?= number_format($item['subtotal'], 2, ',', '.') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr style="border-top:2px solid var(--gray-200)">
            <td colspan="3" style="text-align:right;padding:12px 4px;font-weight:700;font-size:.8rem;text-transform:uppercase;letter-spacing:.5px;color:var(--gray-400)">Total</td>
            <td style="text-align:right;padding:12px 4px;font-weight:800;font-size:1.1rem">$ <?= number_format($pedido['total'], 2, ',', '.') ?></td>
          </tr>
        </tfoot>
      </table>

      <div style="margin-top:20px;font-size:.75rem;color:var(--gray-400);text-align:center">
        <?= htmlspecialchars(Auth::empresaNombre()) ?> — Textum
      </div>
    </div>
  </div>
  <?php endif; ?>

</div>

<?php if ($pedido['estado'] === 'confirmado' && Auth::isAdmin()): ?>
<div class="modal" id="modal-anular">
  <div class="modal-backdrop"></div>
  <div class="modal-box">
    <h3 class="modal-title">Anular Pedido #<?= $pedido['id'] ?></h3>
    <p class="text-sm text-muted" style="margin-bottom:20px">
      Esta acción revertirá el stock y el ingreso registrado. El pedido quedará con estado "anulado" en el historial.
    </p>
    <div class="form-group">
      <label class="form-label" for="motivo-anulacion">Motivo de anulación <span style="color:var(--red-500)">*</span></label>
      <textarea id="motivo-anulacion" class="form-control" rows="3" placeholder="Ej: Error en la carga, devolución del cliente..."></textarea>
    </div>
    <div class="flex gap-3 mt-4">
      <button id="btn-confirmar-anular" class="btn btn-danger">Confirmar anulación</button>
      <button id="modal-close" class="btn btn-outline">Cancelar</button>
    </div>
  </div>
</div>
<?php endif; ?>

<?php require VIEW_PATH . '/layout/footer.php'; ?>
<script>
(function() {
  var btn = document.getElementById('btn-copy-recibo');
  if (!btn) return;
  btn.addEventListener('click', function() {
    var url = this.dataset.url;
    navigator.clipboard.writeText(url).then(function() {
      btn.textContent = '¡Copiado!';
      setTimeout(function() { btn.textContent = 'Copiar enlace'; }, 2000);
    });
  });
})();
</script>