<?php
$pageTitle   = 'Pedido #' . $pedido['id'];
$currentPage = 'pedidos';
require VIEW_PATH . '/layout/header.php';
?>

<div style="margin-bottom:12px" class="flex items-center gap-3 flex-wrap">
  <a href="index.php?page=pedidos" class="btn btn-outline btn-sm">← Pedidos</a>
  <span class="badge badge-abierto">Abierto</span>
  <span class="text-sm text-muted">Vendedor: <?= htmlspecialchars($pedido['vendedor_nombre']) ?></span>
  <span class="text-sm text-muted"><?= date('d/m/Y H:i', strtotime($pedido['created_at'])) ?></span>
</div>

<!-- Form wrapper (data attribute para JS) -->
<div id="pedido-abierto-form" data-pedido-id="<?= $pedido['id'] ?>">

  <div class="order-layout">

    <!-- IZQUIERDA: Escaner + tabla de items -->
    <div>

      <!-- Zona escaneo -->
      <div class="barcode-zone mb-4">
        <h3>📦 Escanear código de barras</h3>
        <p class="text-sm text-muted mb-4" style="margin-bottom:12px">
          Posicione el cursor aquí y escanee, o escriba el código manualmente.
        </p>
        <div class="flex gap-3 items-center flex-wrap" style="justify-content:center">
          <input type="text" id="barcode-input" class="form-control barcode-input"
                 placeholder="Código de barras..." autocomplete="off"
                 style="max-width:280px">
          <input type="number" id="qty-input" class="form-control"
                 placeholder="Cantidad" step="0.001" min="0.001"
                 style="max-width:120px">
          <button id="btn-add-item" class="btn btn-primary">+ Agregar</button>
        </div>
      </div>

      <!-- Tabla items -->
      <div class="card">
        <div class="card-header">
          <span class="card-title">Ítems del pedido</span>
        </div>
        <div class="table-wrap items-table-wrap">
          <table>
            <thead>
              <tr>
                <th>Producto</th>
                <th>Unidad</th>
                <th>Cantidad</th>
                <th>Precio unit.</th>
                <th>Subtotal</th>
                <th></th>
              </tr>
            </thead>
            <tbody id="items-body">
              <?php if (empty($items)): ?>
              <tr id="items-empty">
                <td colspan="6" class="text-center text-muted" style="padding:32px">
                  Sin productos. Escanee un código de barras para comenzar.
                </td>
              </tr>
              <?php else: ?>
                <?php foreach ($items as $item): ?>
                <tr>
                  <td>
                    <div class="font-bold"><?= htmlspecialchars($item['tela_nombre']) ?></div>
                    <div class="text-sm text-muted"><?= htmlspecialchars($item['descripcion']) ?></div>
                    <div class="text-xs text-muted"><?= $item['codigo_barras'] ?></div>
                  </td>
                  <td><?= $item['unidad'] ?></td>
                  <td>
                    <input type="number" class="qty-inline"
                           value="<?= $item['cantidad'] ?>"
                           step="0.001" min="0.001"
                           data-item-id="<?= $item['id'] ?>"
                           data-var-id="<?= $item['variante_id'] ?>"
                           data-minimo="0.001"
                           style="width:80px;padding:4px 8px;border:1px solid #d1d5db;border-radius:6px;text-align:center">
                  </td>
                  <td>$ <?= number_format($item['precio_unit'], 2, ',', '.') ?></td>
                  <td class="font-bold">$ <?= number_format($item['subtotal'], 2, ',', '.') ?></td>
                  <td>
                    <button class="btn btn-sm btn-danger" data-del-item="<?= $item['id'] ?>">✕</button>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>

    <!-- DERECHA: Total y confirmación -->
    <div>
      <div class="order-total-bar">
        <div class="total-label">TOTAL DEL PEDIDO</div>
        <div class="total-value" id="order-total">
          $ <?= number_format($pedido['total'], 2, ',', '.') ?>
        </div>
        <div style="margin-top:8px;font-size:.82rem;opacity:.6">
          <?= count($items) ?> líneas
        </div>
      </div>

      <div style="margin-top:16px">
        <button id="btn-confirmar"
                class="btn btn-success btn-lg w-full"
                <?= empty($items) ? 'disabled' : '' ?>>
          ✓ Confirmar Pedido
        </button>
        <div class="alert alert-warn" style="margin-top:12px;font-size:.82rem">
          Al confirmar, se descontará el stock y se registrará el ingreso. Esta acción no se puede deshacer directamente.
        </div>
        <a href="index.php?page=pedidos"
           class="btn btn-outline w-full mt-4"
           onclick="return confirm('¿Salir? El pedido quedará abierto.')">
          Guardar y salir
        </a>
      </div>
    </div>

  </div>
</div>

<?php require VIEW_PATH . '/layout/footer.php'; ?>
