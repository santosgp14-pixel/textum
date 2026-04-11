<?php
$pageTitle   = 'Configuración';
$currentPage = 'config';
require VIEW_PATH . '/layout/header.php';
$catalogUrl = BASE_URL . '/index.php?page=catalogo&empresa_id=' . htmlspecialchars($empresa['id']) . '&t=' . htmlspecialchars($catalogToken);
?>

<div style="max-width:640px">

  <div class="card mb-4">
    <div class="card-header">
      <span class="card-title">⚙ Configuración de la empresa</span>
    </div>
    <div class="card-body">
      <form method="POST" action="index.php?page=config_guardar">

        <div class="form-group">
          <label class="form-label" for="descripcion_catalogo">Descripción del catálogo público</label>
          <textarea id="descripcion_catalogo" name="descripcion_catalogo" class="form-control" rows="3"
            placeholder="Ej: Telas e insumos textiles al por mayor y menor…"><?= htmlspecialchars($empresa['descripcion_catalogo'] ?? '') ?></textarea>
          <div class="form-hint">Aparece en la cabecera del catálogo público.</div>
        </div>

        <div class="form-group">
          <label class="form-label" for="whatsapp">Número de WhatsApp (con código de país)</label>
          <input type="text" id="whatsapp" name="whatsapp" class="form-control"
            placeholder="Ej: 5491167890000"
            value="<?= htmlspecialchars($empresa['whatsapp'] ?? '') ?>">
          <div class="form-hint">Solo números y +. Se usa en los botones de contacto por WhatsApp.</div>
        </div>

        <div class="form-group">
          <label class="form-label" for="logo_url">URL del logo (https://…)</label>
          <input type="url" id="logo_url" name="logo_url" class="form-control"
            placeholder="https://ejemplo.com/logo.png"
            value="<?= htmlspecialchars($empresa['logo_url'] ?? '') ?>">
          <div class="form-hint">Imagen cuadrada recomendada (PNG o JPG, mín. 200×200px).</div>
        </div>
        <?php if (!empty($empresa['logo_url'])): ?>
        <div style="margin-bottom:16px">
          <img src="<?= htmlspecialchars($empresa['logo_url']) ?>" alt="Logo"
               style="max-width:100px;max-height:100px;border-radius:8px;border:1px solid var(--gray-200)">
        </div>
        <?php endif; ?>

        <div class="flex gap-3 mt-4">
          <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Link del catálogo público -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">🔗 Catálogo público</span>
    </div>
    <div class="card-body">
      <p class="text-sm text-muted mb-3">
        Compartí este enlace con tus clientes. No requiere contraseña.
      </p>
      <div class="flex gap-2 flex-wrap items-center" style="margin-bottom:12px">
        <input type="text" class="form-control" id="catalog-link" readonly
               value="<?= htmlspecialchars($catalogUrl) ?>"
               style="flex:1;min-width:0;font-size:.8rem;background:var(--gray-50)">
        <button type="button" class="btn btn-outline btn-sm" id="btn-copy-link">Copiar</button>
      </div>
      <div class="flex gap-2 flex-wrap">
        <a href="<?= htmlspecialchars($catalogUrl) ?>" target="_blank" class="btn btn-sm btn-outline">
          👁 Ver catálogo
        </a>
        <?php if (!empty($empresa['whatsapp'])): ?>
        <?php
          $waText = urlencode('¡Hola! Te comparto nuestro catálogo de telas: ' . $catalogUrl);
          $waLink = 'https://wa.me/' . preg_replace('/[^0-9]/', '', $empresa['whatsapp']) . '?text=' . $waText;
        ?>
        <a href="<?= htmlspecialchars($waLink) ?>" target="_blank" class="btn btn-sm btn-whatsapp">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
          Compartir por WhatsApp
        </a>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div>

<script>
document.getElementById('btn-copy-link').addEventListener('click', function() {
  const input = document.getElementById('catalog-link');
  input.select();
  input.setSelectionRange(0, 99999);
  navigator.clipboard.writeText(input.value).then(() => {
    this.textContent = '¡Copiado!';
    setTimeout(() => { this.textContent = 'Copiar'; }, 2000);
  });
});
</script>

<?php require VIEW_PATH . '/layout/footer.php'; ?>
