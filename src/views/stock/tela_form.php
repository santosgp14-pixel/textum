<?php
$esEdicion   = !empty($tela);
$pageTitle   = $esEdicion ? 'Editar Producto' : 'Nuevo Producto';
$currentPage = 'stock';


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
  grid-template-columns: 1fr 1fr 1fr 1fr auto;
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
/* ── Calculadoras de precio ────────────────────────── */
.calc-box { background:var(--gray-50,#f9fafb); border:1px solid var(--gray-200,#e5e7eb); border-radius:8px; margin-bottom:16px; overflow:hidden; }
.calc-tabs { display:flex; border-bottom:1px solid var(--gray-200,#e5e7eb); background:#fff; }
.calc-tab { flex:1; padding:8px 10px; font-size:.78rem; font-weight:600; background:none; border:none; border-bottom:2px solid transparent; cursor:pointer; color:var(--gray-500,#6b7280); transition:color .15s,border-color .15s; }
.calc-tab.active { color:var(--primary,#2563eb); border-bottom-color:var(--primary,#2563eb); }
.calc-panel { padding:14px 16px; }
.calc-formula { font-size:.72rem; color:var(--gray-400); font-style:italic; margin:0 0 10px; }
.calc-resultado { font-size:.85rem; color:var(--gray-700,#374151); min-height:20px; margin-bottom:0; }
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
          <label class="form-label" for="tipo">Categoría *</label>
          <select id="tipo" name="tipo" class="form-control" required>
            <option value="">— Seleccionar —</option>
            <option value="punto" <?= ($tela['tipo'] ?? '') === 'punto' ? 'selected' : '' ?>>Punto</option>
            <option value="plano" <?= ($tela['tipo'] ?? '') === 'plano' ? 'selected' : '' ?>>Plano</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="subcategoria">Estación <span class="text-muted text-xs">(opcional)</span></label>
          <select id="subcategoria" name="subcategoria" class="form-control">
            <option value="">— Sin especificar —</option>
            <option value="atemporal"   <?= ($tela['subcategoria'] ?? '') === 'atemporal'   ? 'selected' : '' ?>>Atemporal</option>
            <option value="invierno"    <?= ($tela['subcategoria'] ?? '') === 'invierno'    ? 'selected' : '' ?>>Invierno</option>
            <option value="verano"      <?= ($tela['subcategoria'] ?? '') === 'verano'      ? 'selected' : '' ?>>Verano</option>
          </select>
        </div>
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
        <label class="form-label" for="rinde">Rinde <span class="text-muted text-xs">(metros por kilo)</span></label>
        <input type="number" id="rinde" name="rinde" class="form-control"
               value="<?= $tela['rinde'] ?? '' ?>"
               step="0.001" min="0" placeholder="Ej: 1.250">
        <div class="text-xs text-muted mt-1">Cuántos metros rinde 1 kilo de tela.</div>
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
          <label class="form-label" for="precio">Precio ($) *</label>
          <input type="number" id="precio" name="precio" class="form-control"
                 value="<?= rtrim(rtrim(number_format((float)($tela['precio'] ?? 0), 2, '.', ''), '0'), '.') ?>"
                 step="0.01" min="0" placeholder="Ej: 7000" required>
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
        <div class="form-group">
          <label class="form-label" for="minimo_venta">Mínimo fraccionado</label>
          <input type="number" id="minimo_venta" name="minimo_venta" class="form-control"
                 value="<?= number_format((float)($tela['minimo_venta'] ?? 1), 3, '.', '') ?>"
                 step="0.001" min="0.001" placeholder="1.000">
          <div class="text-xs text-muted mt-1" id="minimo-hint">Ej: 2 kg ó 5 metros</div>
        </div>
      </div>

      <!-- ── CALCULADORAS DE PRECIO ───────────────────── -->
      <div class="section-title">Calculadoras de precio</div>

      <div class="calc-box">
        <nav class="calc-tabs">
          <button type="button" class="calc-tab active" data-tab="metro">Por metro</button>
          <button type="button" class="calc-tab" data-tab="rollo">Por rollo</button>
          <button type="button" class="calc-tab" data-tab="fraccionado">Fraccionado</button>
        </nav>

        <!-- Tab 1: precio por metro — kilos = 1 —————————————— -->
        <div class="calc-panel" id="calc-panel-metro">
          <p class="calc-formula">1 kg × Precio/kg ÷ Rinde&nbsp;=&nbsp;precio / metro</p>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:10px">
            <div class="form-group" style="margin:0">
              <label class="form-label" style="font-size:.75rem">Precio por kilo ($)</label>
              <input type="number" id="c1-precio-kg" class="form-control" step="0.01" min="0" placeholder="5000">
            </div>
            <div class="form-group" style="margin:0">
              <label class="form-label" style="font-size:.75rem">Rinde (m / kg)</label>
              <input type="number" id="c1-rinde" class="form-control" step="0.001" min="0" placeholder="1.250">
            </div>
          </div>
          <div id="c1-resultado" class="calc-resultado"></div>
        </div>

        <!-- Tab 2: precio por rollo ———————————————————— -->
        <div class="calc-panel" id="calc-panel-rollo" style="display:none">
          <p class="calc-formula">Metros del rollo × Precio/metro = precio del rollo</p>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:10px">
            <div class="form-group" style="margin:0">
              <label class="form-label" style="font-size:.75rem">Metros del rollo</label>
              <input type="number" id="c2-metros" class="form-control" step="0.001" min="0" placeholder="25.000">
            </div>
            <div class="form-group" style="margin:0">
              <label class="form-label" style="font-size:.75rem">Precio por metro ($)</label>
              <input type="number" id="c2-precio-metro" class="form-control" step="0.01" min="0" placeholder="3000">
            </div>
          </div>
          <div id="c2-resultado" class="calc-resultado"></div>
        </div>

        <!-- Tab 3: precio fraccionado ————————————————— -->
        <div class="calc-panel" id="calc-panel-fraccionado" style="display:none">
          <p class="calc-formula">Precio del rollo ÷ Metros = precio / metro fraccionado</p>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:10px">
            <div class="form-group" style="margin:0">
              <label class="form-label" style="font-size:.75rem">Precio del rollo ($)</label>
              <input type="number" id="c3-precio-rollo" class="form-control" step="0.01" min="0" placeholder="75000">
            </div>
            <div class="form-group" style="margin:0">
              <label class="form-label" style="font-size:.75rem">Metros del rollo</label>
              <input type="number" id="c3-metros" class="form-control" step="0.001" min="0" placeholder="25.000">
            </div>
          </div>
          <div id="c3-resultado" class="calc-resultado"></div>
        </div>
      </div>

      <?php if (!$esEdicion): ?>
      <div class="form-group">
        <label class="form-label" for="stock_inicial">Stock inicial</label>
        <input type="number" id="stock_inicial" name="stock_inicial" class="form-control"
               value="0" step="0.001" min="0" placeholder="0.000" readonly>
        <div class="text-xs text-muted mt-1">Calculado automáticamente de los rollos ingresados.</div>
      </div>
      <?php endif; ?>

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

<!-- ── Modal: escáner de código de barras ────────────── -->
<div id="scanner-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:9999;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:12px;padding:20px;width:min(340px,92vw)">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
      <strong style="font-size:.95rem">Escanear código</strong>
      <button type="button" id="btn-close-scanner" class="btn-icon" style="font-size:1.3rem">✕</button>
    </div>
    <div id="scanner-preview" style="width:100%;border-radius:8px;overflow:hidden;min-height:180px"></div>
    <p id="scanner-status" class="text-xs text-muted text-center" style="margin:8px 0 0">
      Apuntá la cámara al código de barras
    </p>
  </div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(function () {
  const tipoSel   = document.getElementById('tipo');
  const unidadSel = document.getElementById('unidad');
  // Helper: formatear precio sin ceros decimales innecesarios
  const fmtPrecio = n => {
    const r = parseFloat(n.toFixed(2));
    return String(r);
  };

  const fmt = n => new Intl.NumberFormat('es-AR', { style:'currency', currency:'ARS', minimumFractionDigits:2 }).format(n);
  const precioInput = document.getElementById('precio');
  const rindeInput  = document.getElementById('rinde');
  let _fromPrecio = false; // evita loop al sincronizar precio ↔ calculadora

  // ── Tabs de calculadoras ──────────────────────────────────
  document.querySelectorAll('.calc-tab').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.calc-tab').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.calc-panel').forEach(p => p.style.display = 'none');
      btn.classList.add('active');
      document.getElementById('calc-panel-' + btn.dataset.tab).style.display = '';
    });
  });

  // ── Calc 1: precio por metro (1 kg) ──────────────────────
  const c1PrecioKg = document.getElementById('c1-precio-kg');
  const c1Rinde    = document.getElementById('c1-rinde');
  const c1Res      = document.getElementById('c1-resultado');

  // Sincronizar c1-rinde ↔ campo rinde del formulario
  rindeInput?.addEventListener('input', () => { c1Rinde.value = rindeInput.value; calc1(); });
  c1Rinde.addEventListener('input', () => { if (rindeInput) rindeInput.value = c1Rinde.value; calc1(); });
  c1PrecioKg.addEventListener('input', calc1);

  function calc1() {
    const precioKg = parseFloat(c1PrecioKg.value) || 0;
    const rinde    = parseFloat(c1Rinde.value)    || 0;
    if (!precioKg || !rinde) { c1Res.textContent = ''; return; }
    const precioMetro = precioKg / rinde;
    if (rindeInput) rindeInput.value = rinde.toFixed(3);
    if (precioInput && !_fromPrecio) precioInput.value = fmtPrecio(precioMetro);
    c1Res.innerHTML =
      `${fmt(precioKg)} / kg ÷ <strong>${rinde.toFixed(3)} m/kg</strong> = ` +
      `<strong style="color:var(--primary,#2563eb)">${fmt(precioMetro)} / metro</strong>`;
  }

  // ── Calc 2: precio por rollo ───────────────────────────
  const c2Metros    = document.getElementById('c2-metros');
  const c2PrecioMtr = document.getElementById('c2-precio-metro');
  const c2Res       = document.getElementById('c2-resultado');
  [c2Metros, c2PrecioMtr].forEach(el => el.addEventListener('input', calc2));

  function calc2() {
    const metros     = parseFloat(c2Metros.value)    || 0;
    const precioMtro = parseFloat(c2PrecioMtr.value) || 0;
    if (!metros || !precioMtro) { c2Res.textContent = ''; return; }
    const precioRollo = metros * precioMtro;
    if (precioInput && !_fromPrecio) precioInput.value = fmtPrecio(precioRollo);
    c2Res.innerHTML =
      `<strong>${metros.toFixed(3)} m</strong> × ${fmt(precioMtro)}/m = ` +
      `<strong style="color:var(--primary,#2563eb)">${fmt(precioRollo)} / rollo</strong>`;
  }

  // ── Calc 3: precio fraccionado ────────────────────────
  const c3PrecioRollo = document.getElementById('c3-precio-rollo');
  const c3Metros      = document.getElementById('c3-metros');
  const c3Res         = document.getElementById('c3-resultado');
  [c3PrecioRollo, c3Metros].forEach(el => el.addEventListener('input', calc3));

  function calc3() {
    const precioRollo = parseFloat(c3PrecioRollo.value) || 0;
    const metros      = parseFloat(c3Metros.value)      || 0;
    if (!precioRollo || !metros) { c3Res.textContent = ''; return; }
    const precioMetro = precioRollo / metros;
    if (precioInput) precioInput.value = fmtPrecio(precioMetro);
    c3Res.innerHTML =
      `${fmt(precioRollo)} ÷ <strong>${metros.toFixed(3)} m</strong> = ` +
      `<strong style="color:var(--primary,#2563eb)">${fmt(precioMetro)} / metro</strong>`;
  }
  // ── Tipo → unidad auto + labels ──────────────────────────
  function updateRolloLabels() {
    const lbl = tipoSel?.value === 'punto' ? 'Kilos *' : 'Metros *';
    document.querySelectorAll('.rollo-cantidad-label').forEach(el => el.textContent = lbl);
  }
  // Sincronizar precioInput → calculadora activa (sin sobreescribir precioInput)
  function syncPrecioToCalc() {
    const t = tipoSel?.value;
    const p = precioInput?.value;
    if (!p) return;
    _fromPrecio = true;
    if (t === 'punto') {
      c1PrecioKg.value = p;
      if (rindeInput?.value) c1Rinde.value = rindeInput.value;
      calc1();
    } else if (t === 'plano') {
      c2PrecioMtr.value = p;
      calc2();
    }
    _fromPrecio = false;
  }

  precioInput?.addEventListener('input', syncPrecioToCalc);

  function onTipoChange() {
    const t = tipoSel?.value;
    if (unidadSel) {
      if (t === 'punto') unidadSel.value = 'kilo';
      if (t === 'plano') unidadSel.value = 'metro';
    }
    const hint = document.getElementById('minimo-hint');
    if (hint) hint.textContent = t === 'punto' ? 'Ej: 2 kg mínimo' : t === 'plano' ? 'Ej: 5 metros mínimo' : 'Ej: 1.000';
    updateRolloLabels();
    // Sincronizar al cargar en modo edición
    if (rindeInput?.value && c1Rinde) c1Rinde.value = rindeInput.value;
    syncPrecioToCalc();
  }
  tipoSel?.addEventListener('change', onTipoChange);
  onTipoChange(); // aplicar al cargar en modo edición
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
      <div style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:12px;margin-bottom:12px">
        <div class="form-group" style="margin:0">
          <label class="form-label">Descripción / color *</label>
          <input type="text" name="variantes[${idx}][descripcion]" class="form-control"
                 placeholder="Ej: Azul marino" required>
        </div>
        <div class="form-group" style="margin:0">
          <label class="form-label">Precio de venta ($)</label>
          <input type="number" name="variantes[${idx}][precio]" class="v-precio form-control"
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
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
        <div class="form-group" style="margin:0">
          <label class="form-label">Precio fraccionado ($) <span style="color:var(--gray-400);font-weight:400;font-size:.78rem">+15%</span></label>
          <input type="number" name="variantes[${idx}][precio_fraccionado]" class="v-precio-frac form-control"
                 value="0" step="0.01" min="0">
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
        <label class="form-label">Código de barras</label>
        <div style="display:flex;gap:6px;align-items:stretch">
          <input type="text" name="variantes[${varIdx}][rollos][${rolloIdx}][codigo_barras]"
                 class="form-control barcode-input" placeholder="Escanear o escribir" style="flex:1">
          <button type="button" class="btn btn-outline btn-sm btn-scan-barcode"
                  title="Escanear con cámara" style="padding:0 10px;font-size:1.1rem">&#x1F4F7;</button>
        </div>
      </div>
      <div class="form-group" style="margin:0">
        <label class="form-label">Costo ($)</label>
        <input type="number" name="variantes[${varIdx}][rollos][${rolloIdx}][costo]"
               class="form-control" step="0.01" min="0" placeholder="0.00">
      </div>
      <div class="form-group" style="margin:0">
        <label class="form-label rollo-cantidad-label">${tipoSel?.value === 'punto' ? 'Kilos *' : 'Metros *'}</label>
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
    // Auto-calc precio_fraccionado (+15%) when precio changes
    const precioInput = row.querySelector('.v-precio');
    const fracInput   = row.querySelector('.v-precio-frac');
    if (precioInput && fracInput) {
      precioInput.addEventListener('input', function() {
        const base = parseFloat(this.value) || 0;
        if (base > 0) fracInput.value = (base * 1.15).toFixed(2);
      });
    }
  });

  function recalcStock() {
    const stockInput = document.getElementById('stock_inicial');
    if (!stockInput) return;
    let total = 0;
    container.querySelectorAll('input[name*="[metros]"]').forEach(inp => {
      total += parseFloat(inp.value) || 0;
    });
    stockInput.value = total.toFixed(3);
  }

  container.addEventListener('input', e => {
    if (e.target.name && e.target.name.includes('[metros]')) recalcStock();
  });

  container.addEventListener('click', e => {
    // Escanear barcode con cámara
    const btnScan = e.target.closest('.btn-scan-barcode');
    if (btnScan) {
      startScanner(btnScan.previousElementSibling);
      return;
    }
    // Quitar variante
    if (e.target.closest('.btn-remove-variante')) {
      e.target.closest('.variante-row').remove();
      recalcStock();
      return;
    }
    // Quitar rollo
    if (e.target.closest('.btn-remove-rollo')) {
      e.target.closest('.rollo-row').remove();
      recalcStock();
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

  // ── Escáner de cámara ────────────────────────────────────
  let activeScanner   = null;
  let scanTargetInput = null;
  const scanModal    = document.getElementById('scanner-modal');
  const btnCloseScan = document.getElementById('btn-close-scanner');
  const scanStatus   = document.getElementById('scanner-status');

  function stopScanner() {
    if (activeScanner) {
      activeScanner.stop()
        .then(() => { activeScanner.clear(); activeScanner = null; })
        .catch(() => {});
    }
    if (scanModal) scanModal.style.display = 'none';
    scanTargetInput = null;
  }

  function startScanner(inputEl) {
    if (typeof Html5Qrcode === 'undefined') {
      alert('La librería de escaneo no está disponible. Verificá tu conexión a internet.');
      return;
    }
    scanTargetInput = inputEl;
    scanModal.style.display = 'flex';
    scanStatus.textContent  = 'Apuntá la cámara al código de barras…';
    // Limpiar preview anterior si quedaron elementos
    document.getElementById('scanner-preview').innerHTML = '';
    activeScanner = new Html5Qrcode('scanner-preview');
    activeScanner.start(
      { facingMode: 'environment' },
      { fps: 10, qrbox: { width: 260, height: 100 } },
      (decoded) => { scanTargetInput.value = decoded; stopScanner(); },
      () => {}
    ).catch(err => {
      scanStatus.textContent = 'No se pudo acceder a la cámara: ' + (err.message || err);
    });
  }

  btnCloseScan?.addEventListener('click', stopScanner);
  scanModal?.addEventListener('click', e => { if (e.target === scanModal) stopScanner(); });
})();
</script>

<?php require VIEW_PATH . '/layout/footer.php'; ?>
