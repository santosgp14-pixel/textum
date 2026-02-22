<?php
$pageTitle   = 'Pedido #' . $pedido['id'];
$currentPage = 'pedidos';
require VIEW_PATH . '/layout/header.php';
?>

<div style="margin-bottom:16px" class="flex items-center gap-3 flex-wrap">
  <a href="index.php?page=pedidos" class="btn btn-outline btn-sm">← Pedidos</a>
  <span class="badge badge-<?= $pedido['estado'] ?>"><?= ucfirst($pedido['estado']) ?></span>
</div>

<div style="display:grid;grid-template-columns:1fr;gap:20px;max-width:800px">

  <!-- Info del pedido -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">Pedido #<?= $pedido['id'] ?></span>
      <?php if ($pedido['estado'] === 'confirmado' && Auth::isAdmin()): ?>
        <button id="btn-anular" class="btn btn-danger btn-sm" data-pedido-id="<?= $pedido['id'] ?>">
          Anular pedido
        </button>
      <?php endif; ?>
    </div>
    <div class="card-body">
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px">
        <div>
          <div class="text-xs text-muted">Fecha</div>
          <div class="font-bold"><?= date('d/m/Y H:i', strtotime($pedido['created_at'])) ?></div>
        </div>
        <div>
          <div class="text-xs text-muted">Vendedor</div>
          <div class="font-bold"><?= htmlspecialchars($pedido['vendedor_nombre']) ?></div>
        </div>
        <?php if ($pedido['confirmado_at']): ?>
        <div>
          <div class="text-xs text-muted">Confirmado</div>
          <div class="font-bold"><?= date('d/m/Y H:i', strtotime($pedido['confirmado_at'])) ?></div>
        </div>
        <?php endif; ?>
        <?php if ($pedido['estado'] === 'anulado'): ?>
        <div>
          <div class="text-xs text-muted">Anulado</div>
          <div class="font-bold text-danger"><?= date('d/m/Y H:i', strtotime($pedido['anulado_at'])) ?></div>
        </div>
        <?php endif; ?>
        <div>
          <div class="text-xs text-muted">Total</div>
          <div class="font-bold" style="font-size:1.2rem">$ <?= number_format($pedido['total'], 2, ',', '.') ?></div>
        </div>
      </div>

      <?php if ($pedido['motivo_anulacion']): ?>
        <div class="alert alert-error mt-4">
          <strong>Motivo de anulación:</strong> <?= htmlspecialchars($pedido['motivo_anulacion']) ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Items -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">Ítems</span>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Producto</th><th>Unidad</th><th>Cantidad</th><th>Precio unit.</th><th>Subtotal</th></tr>
        </thead>
        <tbody>
          <?php foreach ($items as $item): ?>
          <tr>
            <td>
              <div class="font-bold"><?= htmlspecialchars($item['tela_nombre']) ?></div>
              <div class="text-sm text-muted"><?= htmlspecialchars($item['descripcion']) ?></div>
            </td>
            <td><?= $item['unidad'] ?></td>
            <td><?= number_format($item['cantidad'], 3, ',', '.') ?></td>
            <td>$ <?= number_format($item['precio_unit'], 2, ',', '.') ?></td>
            <td class="font-bold">$ <?= number_format($item['subtotal'], 2, ',', '.') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr style="background:var(--gray-50)">
            <td colspan="4" class="text-right font-bold" style="padding:12px 14px">TOTAL</td>
            <td class="font-bold" style="font-size:1.1rem;padding:12px 14px">
              $ <?= number_format($pedido['total'], 2, ',', '.') ?>
            </td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>

</div>

<!-- Modal anulación -->
<?php if ($pedido['estado'] === 'confirmado' && Auth::isAdmin()): ?>
<div class="modal" id="modal-anular">
  <div class="modal-backdrop"></div>
  <div class="modal-box">
    <h3 class="modal-title">⚠ Anular Pedido #<?= $pedido['id'] ?></h3>
    <p class="text-sm text-muted mb-4">
      Esta acción revertirá el stock y el ingreso registrado. No se puede deshacer.<br>
      El registro permanecerá en el historial con estado "anulado".
    </p>
    <div class="form-group">
      <label class="form-label" for="motivo-anulacion">Motivo de anulación *</label>
      <textarea id="motivo-anulacion" class="form-control" rows="3"
                placeholder="Ej: Error en la carga, devolución del cliente..."></textarea>
    </div>
    <div class="flex gap-3 mt-4">
      <button id="btn-confirmar-anular" class="btn btn-danger">Confirmar Anulación</button>
      <button id="modal-close" class="btn btn-outline">Cancelar</button>
    </div>
  </div>
</div>
<?php endif; ?>

<?php require VIEW_PATH . '/layout/footer.php'; ?>
