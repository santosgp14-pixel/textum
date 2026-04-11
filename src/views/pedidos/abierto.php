<?php
$pageTitle   = 'Pedido #' . $pedido['id'];
$currentPage = 'pedidos';
require VIEW_PATH . '/layout/header.php';
?>

<!-- Page header -->
<div class="page-header" style="margin-bottom:20px">
  <div class="flex items-center gap-3 flex-wrap">
    <a href="index.php?page=pedidos" class="btn btn-outline btn-sm">← Pedidos</a>
    <div>
      <div class="page-header-title" style="font-size:1.1rem">Pedido #<?= $pedido['id'] ?></div>
      <div class="page-header-sub"><?= date('d/m H:i', strtotime($pedido['created_at'])) ?> &mdash; <?= htmlspecialchars($pedido['vendedor_nombre']) ?></div>
    </div>
    <span class="badge badge-abierto">Abierto</span>
  </div>
</div>

<!-- Form wrapper -->
<div id="pedido-abierto-form" data-pedido-id="<?= $pedido['id'] ?>">

  <!-- Cliente del pedido -->
  <div class="cliente-bar mb-4">
    <span class="text-sm" style="opacity:.65;white-space:nowrap">👤 Cliente:</span>
    <div id="cliente-asignado" <?= empty($pedido['cliente_nombre']) ? 'style="display:none"' : '' ?>
         class="flex items-center gap-2 flex-wrap">
      <strong id="cliente-nombre-display"><?= htmlspecialchars($pedido['cliente_nombre'] ?? '') ?></strong>
      <?php if (!empty($pedido['cliente_id'])): ?>
        <a href="index.php?page=cliente_perfil&id=<?= $pedido['cliente_id'] ?>"
           class="text-sm text-muted" target="_blank">ver perfil ↗</a>
      <?php endif; ?>
      <button type="button" id="btn-cambiar-cliente" class="btn btn-sm btn-outline">Cambiar</button>
      <button type="button" id="btn-quitar-cliente" class="btn btn-sm btn-danger">✕ Quitar</button>
    </div>
    <div id="cliente-sin-asignar" <?= !empty($pedido['cliente_nombre']) ? 'style="display:none"' : '' ?>
         class="flex items-center gap-2">
      <span class="text-sm text-muted">Sin cliente asignado</span>
      <button type="button" id="btn-asignar-cliente" class="btn btn-sm btn-outline">＋ Asignar cliente</button>
    </div>
    <div id="cliente-search-panel" style="display:none;width:100%;margin-top:8px">
      <div class="flex gap-2 items-center flex-wrap">
        <input type="text" id="cliente-search-input" class="form-control"
               placeholder="Buscar por nombre, teléfono o email..." autocomplete="off"
               style="max-width:320px">
        <a href="index.php?page=cliente_nuevo" class="btn btn-sm btn-outline" target="_blank">＋ Nuevo cliente</a>
        <button type="button" id="btn-cerrar-cliente-search" class="btn btn-sm btn-outline">✕</button>
      </div>
      <div id="cliente-search-results" class="cliente-dropdown"></div>
    </div>
  </div>

  <div class="order-layout">

    <!-- IZQUIERDA: Escaner + tabla de items -->
    <div>

      <!-- Toggle de vista -->
      <div class="flex gap-2 mb-3 flex-wrap" style="align-items:center">

        <button type="button" class="btn btn-sm btn-primary btn-vista" data-vista="scanner">⌨ Escáner</button>
        <span class="btn btn-sm btn-outline" style="opacity:.5;cursor:not-allowed" title="Próximamente">🖼 Imágenes</span>
        <span class="btn btn-sm btn-outline" style="opacity:.5;cursor:not-allowed" title="Próximamente">☰ Lista</span>
        <span style="background:#fef9c3;color:#a16207;font-size:.7rem;padding:2px 8px;border-radius:99px;font-weight:600">Próximamente</span>
      </div>

      <!-- Zona escaneo -->
      <div class="barcode-zone mb-4">
        <h3 style="font-size:.875rem;font-weight:700;color:var(--blue-800);margin-bottom:4px">Agregar artículo</h3>
        <p class="text-sm text-muted" style="margin-bottom:12px">Escriba el nombre o escanee / ingrese el código de barras.</p>
        <div style="max-width:400px;margin:0 auto;position:relative">
          <input type="text" id="barcode-input" class="form-control barcode-input"
                 placeholder="Nombre o código de barras…" autocomplete="off"
                 style="padding-right:48px">
          <button type="button" id="btn-camara" title="Escanear con cámara"
                  style="position:absolute;right:6px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:1.3rem;line-height:1;padding:4px">
            📷
          </button>
          <div id="nombre-search-results" class="cliente-dropdown"
               style="display:none;position:absolute;z-index:200;width:100%;top:calc(100% + 4px);left:0"></div>
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
                <th class="hide-mobile">Unidad</th>
                <th>Cantidad</th>
                <th class="hide-mobile">Precio unit.</th>
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
                  <td class="hide-mobile"><?= $item['unidad'] ?></td>
                  <td>
                    <input type="number" class="qty-inline"
                           value="<?= $item['cantidad'] ?>"
                           step="0.001" min="0.001"
                           data-item-id="<?= $item['id'] ?>"
                           data-var-id="<?= $item['variante_id'] ?>"
                           data-minimo="0.001"
                           style="width:80px;padding:4px 8px;border:1px solid #d1d5db;border-radius:6px;text-align:center">
                  </td>
                  <td class="hide-mobile">$ <?= number_format($item['precio_unit'], 2, ',', '.') ?></td>
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
        <div class="total-label" style="font-size:.68rem;font-weight:700;letter-spacing:.8px;text-transform:uppercase;opacity:.6">Total del pedido</div>
        <div class="total-value" id="order-total" style="letter-spacing:-.03em">
          $ <?= number_format($pedido['total'], 2, ',', '.') ?>
        </div>
        <div style="margin-top:6px;font-size:.78rem;opacity:.5" id="items-count-label">
          <?= count($items) ?> línea<?= count($items) != 1 ? 's' : '' ?>
        </div>
      </div>

      <div style="margin-top:16px;display:flex;flex-direction:column;gap:10px">
        <button id="btn-confirmar"
                class="btn btn-success btn-lg w-full"
                <?= empty($items) ? 'disabled' : '' ?>>
          Confirmar pedido
        </button>
        <p class="text-xs text-muted text-center" style="line-height:1.5;padding:0 4px">
          El stock se descontará y el ingreso quedará registrado.
        </p>
        <a href="index.php?page=pedidos"
           class="btn btn-outline w-full"
           onclick="return confirm('¿Salir? El pedido quedará abierto.')">
          Guardar y salir
        </a>
      </div>
    </div>

  </div>

  <!-- Modal: Configurar venta de ítem -->
  <div id="modal-add-item" class="modal">
    <div class="modal-backdrop"></div>
    <div class="modal-box" style="max-width:420px">
      <div class="flex justify-between items-start" style="margin-bottom:14px">
        <div style="flex:1;min-width:0">
          <div style="font-size:1rem;font-weight:700;margin-bottom:4px" id="mai-nombre">—</div>
          <div class="text-sm text-muted" id="mai-stock"></div>
        </div>
        <button type="button" id="btn-mai-close" class="btn-icon" style="font-size:1.2rem;margin-left:12px">✕</button>
      </div>
      <!-- Tipo de venta -->
      <div class="flex gap-1" style="margin-bottom:16px;flex-wrap:wrap">
        <button type="button" class="btn btn-sm btn-primary"  data-mai-tab="fraccionado">Fraccionado</button>
        <button type="button" class="btn btn-sm btn-outline"  data-mai-tab="rollo">Por rollo</button>
        <button type="button" class="btn btn-sm btn-outline"  data-mai-tab="otro">Otro</button>
      </div>
      <!-- Cantidad -->
      <div class="form-group">
        <label class="form-label">Cantidad (<span id="mai-unidad">—</span>)</label>
        <input type="number" id="mai-qty" class="form-control" step="0.001" min="0.001" placeholder="0.000">
        <div class="text-xs text-muted mt-1" id="mai-qty-hint"></div>
      </div>
      <!-- Precio -->
      <div class="form-group">
        <label class="form-label">Precio unitario ($)
          <span class="text-xs text-muted" style="font-weight:400"> — editable para esta venta</span>
        </label>
        <input type="number" id="mai-precio" class="form-control" step="0.01" min="0" placeholder="0.00">
      </div>
      <!-- Subtotal -->
      <div style="background:var(--blue-50,#eff6ff);border-radius:8px;padding:10px 16px;margin-bottom:16px;font-size:.9rem">
        Subtotal estimado: <strong id="mai-subtotal">$ 0,00</strong>
      </div>
      <button type="button" id="btn-mai-agregar" class="btn btn-primary w-full">＋ Agregar al pedido</button>
    </div>
  </div>

  <!-- Modal: Escáner de cámara -->
  <div id="modal-camara" class="modal">
    <div class="modal-backdrop"></div>
    <div class="modal-box modal-camara-box">
      <div class="flex justify-between items-center" style="margin-bottom:14px">
        <span class="font-bold" style="font-size:1rem">📷 Escanear código de barras</span>
        <button type="button" id="btn-camara-close" class="btn btn-sm btn-outline">✕ Cerrar</button>
      </div>
      <div class="camara-video-wrap">
        <video id="camara-video" autoplay muted playsinline></video>
        <div class="camara-scan-line"></div>
      </div>
      <p id="camara-status" class="text-sm text-muted text-center" style="margin-top:10px">Iniciando cámara...</p>
      <div id="camara-device-wrap" style="margin-top:10px;display:none">
        <label class="form-label" style="font-size:.8rem">Seleccionar cámara:</label>
        <select id="camara-device-select" class="form-control" style="margin-top:4px"></select>
      </div>
    </div>
  </div>
</div>

<script>window._base = '<?= BASE_URL ?>';</script>
<script src="https://unpkg.com/@zxing/library@0.18.6/umd/index.min.js"></script>
<?php require VIEW_PATH . '/layout/footer.php'; ?>
