<?php
$esEdicion   = !empty($tela);
$pageTitle   = $esEdicion ? 'Editar Producto' : 'Nuevo Producto';
$currentPage = 'stock';

// Separar categorías raíz de sub-categorías
$catsRaiz = array_filter($categorias ?? [], fn($c) => empty($c['parent_id']));
$catsSub  = array_filter($categorias ?? [], fn($c) => !empty($c['parent_id']));
// Agrupar sub-categorías por parent_id para el select dinámico (pasado como JSON al JS)
$subcatsPorParent = [];
foreach ($catsSub as $sc) {
    $subcatsPorParent[(int)$sc['parent_id']][] = $sc;
}

require VIEW_PATH . '/layout/header.php';
?>

<style>
/* ── Producto form ───────────────────────────────── */
.section-title {
  font-size: .75rem;
  font-weight: 700;
  letter-spacing: .08em;
  text-transform: uppercase;
  color: var(--gray-400);
  margin: 28px 0 12px;
  padding-bottom: 6px;
  border-bottom: 1px solid var(--gray-100);
}
.variante-row {
  background: var(--gray-50, #f9fafb);
  border: 1px solid var(--gray-200, #e5e7eb);
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 12px;
  position: relative;
}
.variante-row .v-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 12px;
}
.variante-row .v-title {
  font-weight: 600;
  font-size: .875rem;
  flex: 1;
}
.rollo-row {
  display: grid;
  grid-template-columns: 1fr 1fr auto;
  gap: 10px;
  align-items: end;
  margin-bottom: 8px;
}
.btn-icon {
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 1rem;
  line-height: 1;
  color: var(--gray-400);
}
.btn-icon:hover { background: var(--gray-100); color: var(--danger, #ef4444); }
.img-preview { max-width: 120px; max-height: 120px; border-radius: 6px; object-fit: cover; border: 1px solid var(--gray-200); }
</style>

<div class="card" style="max-width:680px">
  <div class="card-header">
    <span class="card-title"><?= $pageTitle ?></span>
    <a href="index.php?page=stock" class="btn btn-sm btn-outline">← Volver</a>
  </div>
  <div class="card-body">
    <form method="POST" action="index.php?page=tela_guardar" enctype="multipart/form-data" id="form-producto">
      <?php if ($esEdicion): ?>
        <input type="hidden" name="id" value="<?= $tela['id'] ?>">
      <?php endif; ?>

      <!-- ── CLASIFICACIÓN ─────────────────────────────── -->
      <div class="section-title">Clasificación</div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div class="form-group">
          <label class="form-label" for="tipo">Tipo de tejido</label>
          <select id="tipo" name="tipo" class="form-control">
            <option value="">— Sin especificar —</option>
            <option value="punto"  <?= ($tela['tipo'] ?? '') === 'punto'  ? 'selected' : '' ?>>Punto (jersey, polar, licra…)</option>
            <option value="plano"  <?= ($tela['tipo'] ?? '') === 'plano'  ? 'selected' : '' ?>>Plano (denim, poplin, lino…)</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="categoria_id">Categoría</label>
          <select id="categoria_id" name="categoria_id" class="form-control">
            <option value="">— Sin categoría —</option>
            <?php foreach ($catsRaiz as $cat): ?>
            <option value="<?= $cat['id'] ?>"
              <?= ($tela['categoria_id'] ?? 0) == $cat['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($cat['nombre']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="subcategoria_id">Sub-categoría <span class="text-muted text-xs">(opcional)</span></label>
        <select id="subcategoria_id" name="subcategoria_id" class="form-control">
          <option value="">— Sin sub-categoría —</option>
          <?php foreach ($catsSub as $sc): ?>
          <option value="<?= $sc['id'] ?>" data-parent="<?= $sc['parent_id'] ?>"
            <?= ($tela['subcategoria_id'] ?? 0) == $sc['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($sc['nombre']) ?>
          </option>
          <?php endforeach; ?>
        </select>
        <div class="text-xs text-muted mt-1">Las sub-categorías se filtran según la categoría seleccionada.</div>
      </div>

      <!-- ── DATOS DEL PRODUCTO ─────────────────────────── -->
      <div class="section-title">Datos del producto</div>

      <div class="form-group">
        <label class="form-label" for="nombre">Nombre *</label>
        <input type="text" id="nombre" name="nombre" class="form-control"
               value="<?= htmlspecialchars($tela['nombre'] ?? '') ?>"
               placeholder="Ej: Bengalina Lisa, Denim Elastizado" required autofocus>
      </div>

      <div class="form-group">
        <label class="form-label" for="composicion">Composición</label>
        <input type="text" id="composicion" name="composicion" class="form-control"
               value="<?= htmlspecialchars($tela['composicion'] ?? '') ?>"
               placeholder="Ej: 65% polyester / 35% algodón">
      </div>

      <div class="form-group">
        <label class="form-label" for="descripcion">Descripción</label>
        <textarea id="descripcion" name="descripcion" class="form-control" rows="2"
                  placeholder="Descripción adicional..."><?= htmlspecialchars($tela['descripcion'] ?? '') ?></textarea>
      </div>

      <!-- ── PRECIO Y VENTA ─────────────────────────────── -->
      <div class="section-title">Precio y venta</div>

      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px">
        <div class="form-group">
          <label class="form-label" for="costo">Costo ($)</label>
          <input type="number" id="costo" name="costo" class="form-control"
                 value="<?= number_format((float)($tela['costo'] ?? 0), 2, '.', '') ?>"
                 step="0.01" min="0" placeholder="0.00">
          <div class="text-xs text-muted mt-1">Precio de compra</div>
        </div>
        <div class="form-group">
          <label class="form-label" for="precio">Precio ($) *</label>
          <input type="number" id="precio" name="precio" class="form-control"
                 value="<?= number_format((float)($tela['precio'] ?? 0), 2, '.', '') ?>"
                 step="0.01" min="0" placeholder="0.00" required>
          <div class="text-xs text-muted mt-1">Precio de venta</div>
        </div>
        <div class="form-group">
          <label class="form-label" for="unidad">Vender por *</label>
          <select id="unidad" name="unidad" class="form-control">
            <option value="metro" <?= ($tela['unidad'] ?? 'metro') === 'metro' ? 'selected' : '' ?>>Metro</option>
            <option value="kilo"  <?= ($tela['unidad'] ?? '') === 'kilo'  ? 'selected' : '' ?>>Kilo</option>
            <option value="rollo" <?= ($tela['unidad'] ?? '') === 'rollo' ? 'selected' : '' ?>>Rollo completo</option>
          </select>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div class="form-group">
          <label class="form-label" for="codigo_barras">Código de barras</label>
          <input type="text" id="codigo_barras" name="codigo_barras" class="form-control barcode-input"
                 value="<?= htmlspecialchars($tela['codigo_barras'] ?? '') ?>"
                 placeholder="Escanear o escribir código">
          <div class="text-xs text-muted mt-1">Código del producto base (opcional).</div>
        </div>
        <?php if (!$esEdicion): ?>
        <div class="form-group">
          <label class="form-label" for="stock_inicial">Stock inicial</label>
          <input type="number" id="stock_inicial" name="stock_inicial" class="form-control"
                 value="0" step="0.001" min="0" placeholder="0.000">
          <div class="text-xs text-muted mt-1">Solo si no usás variantes/rollos.</div>
        </div>
        <?php endif; ?>
      </div>

      <!-- ── IMAGEN ─────────────────────────────────────── -->
      <div class="section-title">Imagen del producto</div>

      <div class="form-group">
        <?php if ($esEdicion && !empty($tela['imagen_url'])): ?>
          <div style="margin-bottom:10px">
            <img src="<?= BASE_URL . '/' . htmlspecialchars($tela['imagen_url']) ?>"
                 alt="Imagen del producto" class="img-preview" id="img-preview">
          </div>
        <?php else: ?>
          <img src="" alt="" class="img-preview" id="img-preview" style="display:none">
        <?php endif; ?>
        <label class="form-label" for="imagen">
          <?= ($esEdicion && !empty($tela['imagen_url'])) ? 'Cambiar imagen' : 'Agregar imagen' ?>
        </label>
        <input type="file" id="imagen" name="imagen" class="form-control"
               accept="image/jpeg,image/png,image/webp,image/gif"
               style="padding:6px">
        <div class="text-xs text-muted mt-1">JPG, PNG o WEBP. Máximo 2 MB.</div>
      </div>

      <!-- ── VARIANTES POR COLOR ─────────────────────────── -->
      <div class="section-title">Variantes por color</div>

      <?php if ($esEdicion && !empty($variantesExistentes)): ?>
      <div style="margin-bottom:16px">
        <p class="text-sm text-muted">Variantes ya cargadas:</p>
        <table style="width:100%;font-size:.85rem;margin-bottom:8px">
          <thead>
            <tr style="text-align:left">
              <th style="padding:4px 8px">Color</th>
              <th style="padding:4px 8px">Barcode</th>
              <th style="padding:4px 8px">Stock</th>
              <th style="padding:4px 8px"></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($variantesExistentes as $v): ?>
            <tr>
              <td style="padding:4px 8px"><?= htmlspecialchars($v['descripcion']) ?></td>
              <td style="padding:4px 8px;color:var(--gray-400)"><?= htmlspecialchars($v['codigo_barras']) ?></td>
              <td style="padding:4px 8px"><?= number_format($v['stock'], 3, ',', '.') ?> <?= $v['unidad'] ?></td>
              <td style="padding:4px 8px">
                <a href="index.php?page=variante_editar&id=<?= $v['id'] ?>" class="btn btn-sm btn-outline">Editar</a>
                <a href="index.php?page=rollos&variante_id=<?= $v['id'] ?>"  class="btn btn-sm btn-outline">Rollos</a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <a href="index.php?page=variante_nueva&tela_id=<?= $tela['id'] ?>" class="btn btn-sm btn-outline">＋ Agregar color</a>
      </div>
      <?php endif; ?>

      <?php if (!$esEdicion): ?>
      <div id="variantes-container">
        <!-- Las filas de variantes se insertan aquí por JS -->
      </div>
      <button type="button" class="btn btn-outline btn-sm" id="btn-add-variante">＋ Agregar color / variante</button>
      <div class="text-xs text-muted mt-2 mb-2">
        Podés agregar colores ahora o hacerlo después desde la lista de productos.
      </div>
      <?php endif; ?>

      <!-- ── GUARDAR ───────────────────────────────────── -->
      <div class="flex gap-3 mt-4" style="border-top:1px solid var(--gray-100);padding-top:16px">
        <button type="submit" class="btn btn-primary">
          <?= $esEdicion ? 'Guardar cambios' : 'Crear producto' ?>
        </button>
        <a href="index.php?page=stock" class="btn btn-outline">Cancelar</a>
      </div>
    </form>
  </div>
</div>

<script>
(function () {
  // ── Sub-categorías dinámicas ──────────────────────────────
  const subcatsPorParent = <?= json_encode(array_map(fn($g) => array_values($g), $subcatsPorParent), JSON_UNESCAPED_UNICODE) ?>;
  const catSel    = document.getElementById('categoria_id');
  const subcatSel = document.getElementById('subcategoria_id');
  const savedSubcat = <?= (int)($tela['subcategoria_id'] ?? 0) ?>;

  function refreshSubcats() {
    const parentId = parseInt(catSel.value) || 0;
    const subs = subcatsPorParent[parentId] || [];
    subcatSel.innerHTML = '<option value="">— Sin sub-categoría —</option>';
    subs.forEach(s => {
      const opt = document.createElement('option');
      opt.value = s.id;
      opt.textContent = s.nombre;
      if (s.id === savedSubcat) opt.selected = true;
      subcatSel.appendChild(opt);
    });
    subcatSel.closest('.form-group').style.display = subs.length ? '' : 'none';
  }
  catSel.addEventListener('change', refreshSubcats);
  refreshSubcats();

  // ── Preview imagen ────────────────────────────────────────
  const imgInput   = document.getElementById('imagen');
  const imgPreview = document.getElementById('img-preview');
  imgInput?.addEventListener('change', () => {
    const file = imgInput.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
      imgPreview.src = e.target.result;
      imgPreview.style.display = 'block';
    };
    reader.readAsDataURL(file);
  });

  // ── Variantes inline (solo en nuevo producto) ─────────────
  const container   = document.getElementById('variantes-container');
  const btnAddVar   = document.getElementById('btn-add-variante');
  if (!container || !btnAddVar) return;

  let varIdx = 0;
  // Per-variant rollo counters to avoid index collisions after deletion
  const rolloCounters = {};

  function buildVarianteRow(idx) {
    rolloCounters[idx] = 0;
    const div = document.createElement('div');
    div.className = 'variante-row';
    div.dataset.varIdx = idx;
    div.innerHTML = `
      <div class="v-header">
        <span class="v-title">Color / variante #${idx + 1}</span>
        <button type="button" class="btn-icon btn-remove-variante" title="Quitar variante">✕</button>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
        <div class="form-group" style="margin:0">
          <label class="form-label">Descripción / color *</label>
          <input type="text" name="variantes[${idx}][descripcion]" class="form-control"
                 placeholder="Ej: Azul marino" required>
        </div>
        <div class="form-group" style="margin:0">
          <label class="form-label">Código de barras *</label>
          <input type="text" name="variantes[${idx}][codigo_barras]" class="form-control barcode-input"
                 placeholder="Escanear o escribir" required>
        </div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:12px">
        <div class="form-group" style="margin:0">
          <label class="form-label">Costo ($)</label>
          <input type="number" name="variantes[${idx}][costo]" class="form-control"
                 value="0" step="0.01" min="0">
        </div>
        <div class="form-group" style="margin:0">
          <label class="form-label">Precio ($)</label>
          <input type="number" name="variantes[${idx}][precio]" class="form-control"
                 value="0" step="0.01" min="0">
        </div>
        <div class="form-group" style="margin:0">
          <label class="form-label">Unidad</label>
          <select name="variantes[${idx}][unidad]" class="form-control">
            <option value="metro">Metro</option>
            <option value="kilo">Kilo</option>
            <option value="rollo">Rollo</option>
          </select>
        </div>
      </div>

      <div class="text-xs text-muted" style="margin-bottom:8px;font-weight:600">Rollos ingresados para este color:</div>
      <div class="rollos-container" data-var-idx="${idx}"></div>
      <button type="button" class="btn btn-outline btn-sm btn-add-rollo" data-var-idx="${idx}">＋ Agregar rollo</button>
    `;
    return div;
  }

  function buildRolloRow(varIdx, rolloIdx) {
    const div = document.createElement('div');
    div.className = 'rollo-row';
    div.innerHTML = `
      <div class="form-group" style="margin:0">
        <label class="form-label">N° de rollo</label>
        <input type="text" name="variantes[${varIdx}][rollos][${rolloIdx}][nro_rollo]"
               class="form-control" placeholder="Ej: R001">
      </div>
      <div class="form-group" style="margin:0">
        <label class="form-label">Metros / kilos *</label>
        <input type="number" name="variantes[${varIdx}][rollos][${rolloIdx}][metros]"
               class="form-control" step="0.001" min="0.001" placeholder="0.000" required>
      </div>
      <button type="button" class="btn-icon btn-remove-rollo" title="Quitar rollo">✕</button>
    `;
    return div;
  }

  btnAddVar.addEventListener('click', () => {
    const row = buildVarianteRow(varIdx++);
    container.appendChild(row);
  });

  container.addEventListener('click', e => {
    // Quitar variante
    if (e.target.closest('.btn-remove-variante')) {
      e.target.closest('.variante-row').remove();
      return;
    }
    // Quitar rollo
    if (e.target.closest('.btn-remove-rollo')) {
      e.target.closest('.rollo-row').remove();
      return;
    }
    // Agregar rollo
    const btnRollo = e.target.closest('.btn-add-rollo');
    if (btnRollo) {
      const vi = parseInt(btnRollo.dataset.varIdx);
      const rc = btnRollo.previousElementSibling; // rollos-container
      const ri = rolloCounters[vi]++;
      rc.appendChild(buildRolloRow(vi, ri));
    }
  });
})();
</script>

<?php require VIEW_PATH . '/layout/footer.php'; ?>
