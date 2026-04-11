<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Recibo #<?= $pedido['id'] ?> — <?= htmlspecialchars($empresa['nombre'] ?? 'Textum') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Josefin+Sans:wght@300;600;700&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --blue-900: #0f2447; --blue-700: #1a4080; --blue-100: #dbeafe;
      --gray-800: #1f2937; --gray-600: #4b5563; --gray-400: #9ca3af; --gray-200: #e5e7eb;
      --gray-100: #f3f4f6; --gray-50: #f9fafb;
    }
    body { font-family: 'Inter', sans-serif; background: var(--gray-100); color: var(--gray-800); min-height: 100vh; }

    .receipt-wrapper { max-width: 600px; margin: 24px auto; background: #fff; border-radius: 12px; box-shadow: 0 2px 16px rgba(0,0,0,.08); overflow: hidden; }

    .receipt-header { background: var(--blue-900); color: #fff; padding: 24px 28px; }
    .receipt-brand { font-family: 'Josefin Sans', sans-serif; font-size: 1.5rem; font-weight: 700; }
    .receipt-brand span { color: #93c5fd; }
    .receipt-empresa { font-size: .9rem; color: rgba(255,255,255,.75); margin-top: 2px; }
    .receipt-num { font-size: 1.1rem; font-weight: 700; margin-top: 10px; }
    .receipt-fecha { font-size: .82rem; color: rgba(255,255,255,.65); }

    .receipt-actions { padding: 16px 28px; border-bottom: 1px solid var(--gray-200); display: flex; gap: 10px; flex-wrap: wrap; }
    .btn-print { display: inline-flex; align-items: center; gap: 6px; background: var(--gray-100); color: var(--gray-800); border: 1px solid var(--gray-200); border-radius: 8px; padding: 8px 14px; font-weight: 600; font-size: .85rem; cursor: pointer; text-decoration: none; }
    .btn-print:hover { background: var(--gray-200); }
    .btn-wa { display: inline-flex; align-items: center; gap: 6px; background: #25d366; color: #fff; border: none; border-radius: 8px; padding: 8px 14px; font-weight: 600; font-size: .85rem; cursor: pointer; text-decoration: none; }
    .btn-wa:hover { background: #1ebe57; }

    .receipt-meta { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px; padding: 20px 28px; border-bottom: 1px solid var(--gray-200); }
    .meta-label { font-size: .75rem; color: var(--gray-400); font-weight: 600; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 2px; }
    .meta-value { font-weight: 700; font-size: .95rem; }

    .receipt-table { width: 100%; border-collapse: collapse; }
    .receipt-table th { text-align: left; padding: 10px 16px; font-size: .8rem; font-weight: 600; color: var(--gray-600); background: var(--gray-50); border-bottom: 2px solid var(--gray-200); }
    .receipt-table td { padding: 10px 16px; border-bottom: 1px solid var(--gray-100); font-size: .9rem; }
    .receipt-table tfoot tr { background: var(--gray-50); }
    .receipt-table tfoot td { padding: 13px 16px; font-weight: 800; border-top: 2px solid var(--gray-200); }
    .text-right { text-align: right; }
    .font-bold { font-weight: 700; }

    .receipt-footer { text-align: center; padding: 20px; font-size: .78rem; color: var(--gray-400); }

    @media print {
      body { background: #fff; }
      .receipt-wrapper { box-shadow: none; margin: 0; border-radius: 0; }
      .receipt-actions { display: none; }
    }
  </style>
</head>
<body>

<div class="receipt-wrapper">

  <div class="receipt-header">
    <div class="receipt-brand">Text<span>um</span></div>
    <div class="receipt-empresa"><?= htmlspecialchars($empresa['nombre'] ?? '') ?></div>
    <div class="receipt-num">Recibo #<?= $pedido['id'] ?></div>
    <div class="receipt-fecha"><?= date('d/m/Y H:i', strtotime($pedido['created_at'])) ?></div>
  </div>

  <div class="receipt-actions">
    <button onclick="window.print()" class="btn-print">🖨 Imprimir</button>
    <?php if (!empty($empresa['whatsapp'])): ?>
    <?php
      $reciboUrl  = BASE_URL . '/index.php?page=recibo_pub&pedido=' . $pedido['id'] . '&t=' . urlencode($receiptToken);
      $waText     = urlencode('Recibo #' . $pedido['id'] . ' de ' . ($empresa['nombre'] ?? 'Textum') . ': ' . $reciboUrl);
      $waLink     = 'https://wa.me/' . preg_replace('/[^0-9]/', '', $empresa['whatsapp']) . '?text=' . $waText;
    ?>
    <a href="<?= htmlspecialchars($waLink) ?>" target="_blank" class="btn-wa">
      <svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
      Compartir
    </a>
    <?php endif; ?>
  </div>

  <div class="receipt-meta">
    <div>
      <div class="meta-label">Cliente</div>
      <div class="meta-value"><?= htmlspecialchars($pedido['cliente_nombre'] ?? 'Sin asignar') ?></div>
    </div>
    <div>
      <div class="meta-label">Vendedor</div>
      <div class="meta-value"><?= htmlspecialchars($pedido['vendedor_nombre']) ?></div>
    </div>
    <div>
      <div class="meta-label">Estado</div>
      <div class="meta-value">Confirmado</div>
    </div>
    <?php if ($pedido['confirmado_at']): ?>
    <div>
      <div class="meta-label">Confirmado</div>
      <div class="meta-value"><?= date('d/m/Y H:i', strtotime($pedido['confirmado_at'])) ?></div>
    </div>
    <?php endif; ?>
  </div>

  <table class="receipt-table">
    <thead>
      <tr>
        <th>Artículo</th>
        <th class="text-right">Cant.</th>
        <th class="text-right">Precio unit.</th>
        <th class="text-right">Subtotal</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $item): ?>
      <tr>
        <td>
          <div class="font-bold"><?= htmlspecialchars($item['tela_nombre']) ?></div>
          <div style="font-size:.8rem;color:var(--gray-400)"><?= htmlspecialchars($item['descripcion']) ?></div>
        </td>
        <td class="text-right" style="white-space:nowrap">
          <?= number_format($item['cantidad'], 3, ',', '.') ?> <?= htmlspecialchars($item['unidad']) ?>
        </td>
        <td class="text-right" style="white-space:nowrap">
          $&thinsp;<?= number_format($item['precio_unit'], 2, ',', '.') ?>
        </td>
        <td class="text-right font-bold" style="white-space:nowrap">
          $&thinsp;<?= number_format($item['subtotal'], 2, ',', '.') ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="3" class="text-right">TOTAL</td>
        <td class="text-right" style="font-size:1.1rem">
          $&thinsp;<?= number_format($pedido['total'], 2, ',', '.') ?>
        </td>
      </tr>
    </tfoot>
  </table>

  <div class="receipt-footer">
    <?= htmlspecialchars($empresa['nombre'] ?? 'Textum') ?> &mdash; Gestionado con Textum
  </div>
</div>

</body>
</html>
