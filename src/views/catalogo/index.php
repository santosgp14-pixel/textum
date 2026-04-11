<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Catálogo — <?= htmlspecialchars($empresa['nombre']) ?></title>
  <meta name="description" content="<?= htmlspecialchars(($empresa['descripcion_catalogo'] ?? '') ?: 'Catálogo de telas') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Josefin+Sans:wght@300;600;700&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --blue-900: #0f2447; --blue-700: #1a4080; --blue-500: #2563eb;
      --blue-100: #dbeafe; --gray-800: #1f2937; --gray-600: #4b5563;
      --gray-400: #9ca3af; --gray-200: #e5e7eb; --gray-100: #f3f4f6;
      --green-600: #16a34a; --red-500: #ef4444;
    }
    body { font-family: 'Inter', sans-serif; background: var(--gray-100); color: var(--gray-800); min-height: 100vh; }
    a { color: var(--blue-500); text-decoration: none; }

    /* Header */
    .cat-header { background: var(--blue-900); color: #fff; padding: 24px 20px; }
    .cat-header-inner { max-width: 960px; margin: 0 auto; display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
    .cat-logo { width: 64px; height: 64px; border-radius: 12px; object-fit: cover; background: rgba(255,255,255,.15); }
    .cat-logo-placeholder { width: 64px; height: 64px; border-radius: 12px; background: rgba(255,255,255,.15); display: flex; align-items: center; justify-content: center; font-size: 1.6rem; font-weight: 700; font-family: 'Josefin Sans', sans-serif; color: #fff; flex-shrink: 0; }
    .cat-empresa-nombre { font-size: 1.5rem; font-weight: 800; line-height: 1.2; }
    .cat-empresa-desc { font-size: .9rem; color: rgba(255,255,255,.75); margin-top: 4px; }
    .cat-header-actions { margin-left: auto; display: flex; gap: 8px; flex-wrap: wrap; }
    .btn-wa { display: inline-flex; align-items: center; gap: 6px; background: #25d366; color: #fff; border: none; border-radius: 8px; padding: 8px 16px; font-weight: 600; font-size: .85rem; cursor: pointer; text-decoration: none; }
    .btn-wa:hover { background: #1ebe57; }
    .btn-wa svg { fill: #fff; }

    /* Main */
    .cat-main { max-width: 960px; margin: 0 auto; padding: 24px 16px 48px; }

    /* Category section */
    .cat-section { margin-bottom: 36px; }
    .cat-section-title { font-size: 1.05rem; font-weight: 700; color: var(--blue-900); border-bottom: 2px solid var(--blue-100); padding-bottom: 8px; margin-bottom: 16px; }
    .cat-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 16px; }

    /* Product card */
    .product-card { background: #fff; border-radius: 12px; border: 1px solid var(--gray-200); overflow: hidden; transition: box-shadow .15s; }
    .product-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.1); }
    .product-img { width: 100%; height: 140px; object-fit: cover; background: var(--gray-100); }
    .product-img-placeholder { width: 100%; height: 140px; background: linear-gradient(135deg, var(--blue-100), var(--gray-100)); display: flex; align-items: center; justify-content: center; font-size: 2.5rem; }
    .product-body { padding: 12px 14px; }
    .product-name { font-weight: 700; font-size: .95rem; margin-bottom: 2px; }
    .product-desc { font-size: .8rem; color: var(--gray-600); margin-bottom: 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .product-variants { font-size: .78rem; color: var(--gray-400); margin-bottom: 8px; }
    .product-footer { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-top: auto; }
    .product-price { font-size: 1rem; font-weight: 800; color: var(--blue-700); }
    .product-stock-ok { font-size: .78rem; color: var(--green-600); font-weight: 600; }
    .product-stock-no { font-size: .78rem; color: var(--red-500); font-weight: 600; }
    .badge-tipo { display: inline-block; font-size: .7rem; font-weight: 600; padding: 2px 7px; border-radius: 20px; background: var(--blue-100); color: var(--blue-700); margin-bottom: 4px; }

    /* Footer */
    .cat-footer { text-align: center; padding: 24px 16px; font-size: .78rem; color: var(--gray-400); }

    @media (max-width: 480px) {
      .cat-header-actions { margin-left: 0; width: 100%; }
      .btn-wa { font-size: .8rem; }
    }
  </style>
</head>
<body>

<header class="cat-header">
  <div class="cat-header-inner">
    <?php if (!empty($empresa['logo_url'])): ?>
      <img src="<?= htmlspecialchars($empresa['logo_url']) ?>" alt="Logo" class="cat-logo">
    <?php else: ?>
      <div class="cat-logo-placeholder"><?= mb_strtoupper(mb_substr($empresa['nombre'], 0, 1)) ?></div>
    <?php endif; ?>
    <div>
      <div class="cat-empresa-nombre"><?= htmlspecialchars($empresa['nombre']) ?></div>
      <?php if (!empty($empresa['descripcion_catalogo'])): ?>
        <div class="cat-empresa-desc"><?= htmlspecialchars($empresa['descripcion_catalogo']) ?></div>
      <?php endif; ?>
    </div>
    <div class="cat-header-actions">
      <?php if (!empty($empresa['whatsapp'])): ?>
      <?php
        $pageUrl = BASE_URL . '/index.php?page=catalogo&empresa_id=' . urlencode($eid) . '&t=' . urlencode($token);
        $waText  = urlencode('¡Hola! Quisiera consultar sobre su catálogo de telas: ' . $pageUrl);
        $waLink  = 'https://wa.me/' . preg_replace('/[^0-9]/', '', $empresa['whatsapp']) . '?text=' . $waText;
      ?>
      <a href="<?= htmlspecialchars($waLink) ?>" target="_blank" class="btn-wa">
        <svg width="16" height="16" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
        Consultar por WhatsApp
      </a>
      <?php endif; ?>
    </div>
  </div>
</header>

<main class="cat-main">

  <?php if (empty($telas)): ?>
    <div style="text-align:center;padding:60px 16px;color:var(--gray-400)">
      <div style="font-size:3rem;margin-bottom:12px">📦</div>
      <div style="font-size:1.1rem;font-weight:600">Sin productos disponibles por el momento</div>
    </div>
  <?php else: ?>

    <?php foreach ($categorias as $cat): ?>
      <?php $telasCat = $telasPorCategoria[$cat['id']] ?? []; ?>
      <?php if (empty($telasCat)) continue; ?>
      <section class="cat-section">
        <div class="cat-section-title"><?= htmlspecialchars($cat['nombre']) ?></div>
        <div class="cat-grid">
          <?php foreach ($telasCat as $t): ?>
            <?php include __DIR__ . '/_product_card.php'; ?>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endforeach; ?>

    <?php if (!empty($sinCategoria)): ?>
    <section class="cat-section">
      <div class="cat-section-title">Otros</div>
      <div class="cat-grid">
        <?php foreach ($sinCategoria as $t): ?>
          <?php include __DIR__ . '/_product_card.php'; ?>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

  <?php endif; ?>
</main>

<footer class="cat-footer">
  <?= htmlspecialchars($empresa['nombre']) ?> &mdash; Gestionado con Textum
</footer>

</body>
</html>
