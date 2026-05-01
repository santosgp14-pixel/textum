/**
 * TEXTUM - app.js
 * Vanilla JS: sidebar, pedido abierto (barcode), confirmación, anulación
 */
'use strict';

// ═══════════════════════════════════════════════════════════════
// MÓDULO: TOAST NOTIFICATIONS
// ═══════════════════════════════════════════════════════════════
const Toast = (() => {
  let container;
  function getContainer() {
    if (!container) {
      container = document.createElement('div');
      container.id = 'toast-container';
      document.body.appendChild(container);
    }
    return container;
  }
  function show(msg, type = 'info', duration = 3500) {
    const icons = { success: '✓', error: '✕', warn: '⚠', info: 'ℹ' };
    const el = document.createElement('div');
    el.className = `toast toast-${type}`;
    el.innerHTML = `
      <span class="toast-icon">${icons[type] || 'ℹ'}</span>
      <span class="toast-msg">${msg}</span>
      <button class="toast-close" aria-label="Cerrar">✕</button>`;
    getContainer().appendChild(el);
    requestAnimationFrame(() => requestAnimationFrame(() => el.classList.add('show')));
    const dismiss = () => {
      el.classList.add('hide');
      el.classList.remove('show');
      setTimeout(() => el.remove(), 350);
    };
    el.querySelector('.toast-close').addEventListener('click', dismiss);
    if (duration > 0) setTimeout(dismiss, duration);
    return { dismiss };
  }
  return {
    success: (m, d) => show(m, 'success', d),
    error:   (m, d) => show(m, 'error',   d),
    warn:    (m, d) => show(m, 'warn',    d),
    info:    (m, d) => show(m, 'info',    d),
  };
})();

// ═══════════════════════════════════════════════════════════════
// MÓDULO: CONFIRM DIALOG (reemplaza confirm() nativo)
// ═══════════════════════════════════════════════════════════════
const Confirm = (() => {
  let dialogEl, resolve;
  function build() {
    dialogEl = document.createElement('div');
    dialogEl.id = 'confirm-dialog';
    dialogEl.innerHTML = `
      <div id="confirm-dialog-backdrop"></div>
      <div class="confirm-box">
        <div class="confirm-icon" id="confirm-icon"></div>
        <div class="confirm-title" id="confirm-title"></div>
        <div class="confirm-msg"  id="confirm-msg"></div>
        <div class="confirm-btns">
          <button id="confirm-cancel" class="btn btn-outline">Cancelar</button>
          <button id="confirm-ok"     class="btn btn-danger" >Confirmar</button>
        </div>
      </div>`;
    document.body.appendChild(dialogEl);
    dialogEl.querySelector('#confirm-cancel').addEventListener('click', () => close(false));
    dialogEl.querySelector('#confirm-ok').addEventListener('click',     () => close(true));
    dialogEl.querySelector('#confirm-dialog-backdrop').addEventListener('click', () => close(false));
    document.addEventListener('keydown', e => {
      if (dialogEl.classList.contains('show') && e.key === 'Escape') close(false);
    });
  }
  function close(val) {
    dialogEl.classList.remove('show');
    if (resolve) { resolve(val); resolve = null; }
  }
  return function(opts = {}) {
    if (!dialogEl) build();
    const {
      title   = '¿Estás seguro?',
      message = 'Esta acción no se puede deshacer.',
      icon    = '🗑',
      okText  = 'Confirmar',
      okClass = 'btn-danger',
    } = typeof opts === 'string' ? { title: opts } : opts;
    dialogEl.querySelector('#confirm-icon').textContent  = icon;
    dialogEl.querySelector('#confirm-title').textContent = title;
    dialogEl.querySelector('#confirm-msg').textContent   = message;
    const okBtn = dialogEl.querySelector('#confirm-ok');
    okBtn.textContent = okText;
    okBtn.className   = `btn ${okClass}`;
    dialogEl.classList.add('show');
    okBtn.focus();
    return new Promise(res => { resolve = res; });
  };
})();

// ═══════════════════════════════════════════════════════════════
// MÓDULO: PROGRESS BAR (navegación)
// ═══════════════════════════════════════════════════════════════
const Progress = (() => {
  let bar, timer, pct = 0;
  function getBar() {
    if (!bar) {
      bar = document.createElement('div');
      bar.id = 'nprogress';
      document.body.prepend(bar);
    }
    return bar;
  }
  function set(p) {
    pct = Math.min(p, 95);
    getBar().style.transform = `scaleX(${pct / 100})`;
  }
  return {
    start() {
      clearInterval(timer);
      getBar().classList.add('running');
      set(10);
      timer = setInterval(() => { set(pct + (95 - pct) * 0.12); }, 200);
    },
    done() {
      clearInterval(timer);
      const b = getBar();
      set(100); b.style.transform = 'scaleX(1)';
      setTimeout(() => { b.classList.remove('running'); set(0); }, 400);
    },
  };
})();

// Start progress on every link click (except anchors, new tabs)
document.addEventListener('click', e => {
  const a = e.target.closest('a[href]');
  if (!a || a.target === '_blank' || a.href.startsWith('mailto:') ||
      a.href === location.href || a.href.includes('#') ||
      e.ctrlKey || e.metaKey || e.shiftKey) return;
  Progress.start();
});
window.addEventListener('pageshow', () => Progress.done());

// ═══════════════════════════════════════════════════════════════
// MÓDULO: TABLE SEARCH
// ═══════════════════════════════════════════════════════════════
function initTableSearch(inputId, tableId) {
  const input = document.getElementById(inputId);
  const table = document.getElementById(tableId);
  if (!input || !table) return;
  const clearBtn = input.parentElement.querySelector('.search-clear');
  const tbody = table.querySelector('tbody');
  if (!tbody) return;

  function filter() {
    const q = input.value.trim().toLowerCase();
    if (clearBtn) clearBtn.classList.toggle('visible', q.length > 0);
    let visible = 0;
    tbody.querySelectorAll('tr').forEach(tr => {
      const match = !q || tr.textContent.toLowerCase().includes(q);
      tr.style.display = match ? '' : 'none';
      if (match) visible++;
    });
    // Show/hide empty state
    let emptyEl = table.parentElement.querySelector('.table-search-empty');
    if (!visible && q) {
      if (!emptyEl) {
        emptyEl = document.createElement('div');
        emptyEl.className = 'empty-state table-search-empty';
        emptyEl.style.padding = '32px';
        emptyEl.innerHTML = `<div class="empty-state-icon">🔍</div><div class="empty-state-title">Sin resultados</div><div class="empty-state-text">Ningún registro coincide con "<strong>${q}</strong>"</div>`;
        table.parentElement.appendChild(emptyEl);
      }
      emptyEl.querySelector('strong').textContent = q;
      emptyEl.style.display = '';
    } else if (emptyEl) {
      emptyEl.style.display = 'none';
    }
  }
  input.addEventListener('input', filter);
  if (clearBtn) clearBtn.addEventListener('click', () => { input.value = ''; filter(); input.focus(); });
}

// ═══════════════════════════════════════════════════════════════
// MÓDULO: CLICKABLE TABLE ROWS
// ═══════════════════════════════════════════════════════════════
function initRowLinks(tableId) {
  const table = document.getElementById(tableId);
  if (!table) return;
  table.querySelectorAll('tbody tr[data-href]').forEach(tr => {
    tr.classList.add('row-link');
    tr.addEventListener('click', e => {
      if (e.target.closest('a, button, input, select')) return;
      Progress.start();
      window.location.href = tr.dataset.href;
    });
  });
}

// ═══════════════════════════════════════════════════════════════


// ── PWA: Service Worker ─────────────────────────────────────────
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('./sw.js', { scope: './' })
      .catch(err => console.warn('SW registro fallido:', err));
  });
}

// ── Sidebar mobile ──────────────────────────────────────────────
const sidebar  = document.getElementById('sidebar');
const overlay  = document.getElementById('overlay');
const btnMenu  = document.getElementById('btn-menu');

if (btnMenu && sidebar) {
  btnMenu.addEventListener('click', () => {
    sidebar.classList.toggle('open');
    overlay.classList.toggle('show');
  });
  overlay?.addEventListener('click', () => {
    sidebar.classList.remove('open');
    overlay.classList.remove('show');
  });
}

// ── Flash auto-dismiss — handled in footer inline script ───────

// ── Formateo de pesos argentinos ────────────────────────────────
function formatPesos(n) {
  return new Intl.NumberFormat('es-AR', {
    style: 'currency', currency: 'ARS', minimumFractionDigits: 2
  }).format(n);
}
function formatQty(n, unidad) {
  return parseFloat(n).toFixed(3).replace('.', ',') + ' ' + (unidad || '');
}

// ═══════════════════════════════════════════════════════════════
// MÓDULO: PEDIDO ABIERTO
// ═══════════════════════════════════════════════════════════════
const pedidoForm = document.getElementById('pedido-abierto-form');
if (pedidoForm) {
  const pedidoId   = pedidoForm.dataset.pedidoId;
  const barcodeInput = document.getElementById('barcode-input');
  const itemsBody    = document.getElementById('items-body');
  const totalEl      = document.getElementById('order-total');
  const emptyMsg     = document.getElementById('items-empty');
  const btnConfirmar = document.getElementById('btn-confirmar');

  let varianteActual = null; // variante seleccionada por barcode

  // ── Búsqueda unificada: nombre o código de barras ───────────
  const nombreResults  = document.getElementById('nombre-search-results');
  let unifiedTimeout;
  let nombreSearchData = [];
  let lastInputLen = 0, lastInputAt = 0;

  // Resetear contadores al enfocar para detectar bien el primer escaneo
  barcodeInput.addEventListener('focus', () => {
    lastInputLen = barcodeInput.value.length;
    lastInputAt  = Date.now();
  });

  function cerrarDropdown() {
    if (nombreResults) nombreResults.style.display = 'none';
    nombreSearchData = [];
  }

  function buscarBarcode(code) {
    cerrarDropdown();
    fetch(`index.php?page=barcode_buscar&barcode=${encodeURIComponent(code)}`)
      .then(r => r.json())
      .then(data => {
        if (!data.ok) { showBarcodeError(data.msg); return; }
        varianteActual = data.variante;
        clearBarcodeError();
        openAddItemModal(varianteActual, data.rollo || null);
      })
      .catch(() => showBarcodeError('Error de conexión'));
  }

  function buscarPorNombre(q) {
    fetch(`index.php?page=variantes_buscar&q=${encodeURIComponent(q)}`)
      .then(r => r.json())
      .then(data => {
        if (!data.ok || !data.variantes.length) {
          nombreSearchData = [];
          nombreResults.innerHTML = '<div class="cliente-item text-muted" style="cursor:default;font-size:.875rem;padding:12px 14px">Sin resultados</div>';
        } else {
          nombreSearchData = data.variantes;
          nombreResults.innerHTML = nombreSearchData.map((v, i) => `
            <div class="cliente-item" data-idx="${i}">
              <div style="font-weight:600;font-size:.9rem">${v.tela_nombre}</div>
              <div class="text-sm text-muted">${v.descripcion} &mdash; ${formatPesos(v.precio)}&thinsp;/&thinsp;${v.unidad}</div>
              <div style="font-size:.75rem;color:var(--green-600)">Stock: ${formatQty(v.stock, v.unidad)}</div>
            </div>`).join('');
          nombreResults.querySelectorAll('.cliente-item[data-idx]').forEach(el => {
            el.addEventListener('click', () => {
              const v = nombreSearchData[parseInt(el.dataset.idx, 10)];
              if (!v) return;
              barcodeInput.value = '';
              cerrarDropdown();
              clearBarcodeError();
              openAddItemModal(v);
            });
          });
        }
        nombreResults.style.display = '';
      })
      .catch(() => cerrarDropdown());
  }

  barcodeInput.addEventListener('input', () => {
    clearTimeout(unifiedTimeout);
    const val   = barcodeInput.value.trim();
    const now   = Date.now();
    const dLen  = val.length - lastInputLen;
    const dTime = now - lastInputAt;
    lastInputLen = val.length;
    lastInputAt  = now;

    if (!val) { cerrarDropdown(); clearBarcodeError(); return; }

    // Scanner: 4+ caracteres en < 80 ms → tratar como código de barras
    if (dLen > 3 && dTime < 80) {
      cerrarDropdown();
      unifiedTimeout = setTimeout(() => buscarBarcode(barcodeInput.value.trim()), 150);
    } else {
      // Tipeo manual → búsqueda por nombre
      clearBarcodeError();
      if (val.length < 2) { cerrarDropdown(); return; }
      unifiedTimeout = setTimeout(() => buscarPorNombre(val), 350);
    }
  });

  barcodeInput.addEventListener('keydown', e => {
    if (e.key === 'Enter') {
      e.preventDefault();
      clearTimeout(unifiedTimeout);
      const val = barcodeInput.value.trim();
      if (!val) return;
      // Dropdown visible con resultados → elegir el primero
      const firstItem = nombreResults?.querySelector('.cliente-item[data-idx]');
      if (nombreResults?.style.display !== 'none' && firstItem) {
        firstItem.click();
        return;
      }
      // Sin dropdown → intentar como código de barras
      buscarBarcode(val);
    }
    if (e.key === 'Escape') {
      barcodeInput.value = '';
      cerrarDropdown();
      clearBarcodeError();
    }
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      nombreResults?.querySelector('.cliente-item[data-idx]')?.focus();
    }
  });

  document.addEventListener('click', e => {
    if (!barcodeInput.contains(e.target) && !nombreResults?.contains(e.target)) {
      cerrarDropdown();
    }
  });

  // ── Eliminar item ───────────────────────────────────────────────
  itemsBody.addEventListener('click', async e => {
    const btn = e.target.closest('[data-del-item]');
    if (!btn) return;
    const itemId = btn.dataset.delItem;
    const ok = await Confirm({ title: 'Eliminar ítem', message: 'Se quitará del pedido.', icon: '🗑', okText: 'Eliminar' });
    if (!ok) return;

    const fd = new FormData();
    fd.append('pedido_id', pedidoId);
    fd.append('item_id',   itemId);

    fetch('index.php?page=pedido_item_del', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        if (!data.ok) { Toast.error(data.msg); return; }
        renderItems(data.items, data.total);
        Toast.success('Ítem eliminado.');
      });
  });

  // ── Editar cantidad / precio inline ────────────────────────
  itemsBody.addEventListener('change', e => {
    // Precio inline
    const pInput = e.target.closest('.precio-inline');
    if (pInput) {
      const varId    = pInput.dataset.varId;
      const cantidad = parseFloat(pInput.dataset.cantidad);
      const precio   = parseFloat(pInput.value);
      if (!precio || precio <= 0) return;
      const fd = new FormData();
      fd.append('pedido_id',   pedidoId);
      fd.append('variante_id', varId);
      fd.append('cantidad',    cantidad);
      fd.append('precio_unit', precio);
      fetch('index.php?page=pedido_item_add', { method: 'POST', body: fd })
        .then(r => r.json()).then(data => { if (data.ok) renderItems(data.items, data.total); });
      return;
    }
    // Cantidad inline
    const input = e.target.closest('.qty-inline');
    if (!input) return;
    const varId    = input.dataset.varId;
    const cantidad = parseFloat(input.value);
    const minimo   = parseFloat(input.dataset.minimo);
    if (cantidad < minimo) { alert(`Mínimo: ${minimo}`); input.value = minimo; return; }
    const fd = new FormData();
    fd.append('pedido_id',   pedidoId);
    fd.append('variante_id', varId);
    fd.append('cantidad',    cantidad);
    fetch('index.php?page=pedido_item_add', { method: 'POST', body: fd })
      .then(r => r.json()).then(data => { if (data.ok) renderItems(data.items, data.total); });
  });

  // ── Confirmar pedido ────────────────────────────────────────
  btnConfirmar?.addEventListener('click', async () => {
    const ok = await Confirm({
      title: 'Confirmar pedido',
      message: 'Se descontará el stock. Esta acción no se puede deshacer.',
      icon: '✅',
      okText: 'Confirmar',
      okClass: 'btn-success',
    });
    if (!ok) return;

    btnConfirmar.disabled = true;
    btnConfirmar.innerHTML = '<span class="spinner"></span> Confirmando...';

    const fd = new FormData();
    fd.append('pedido_id', pedidoId);

    fetch('index.php?page=pedido_confirmar', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        if (!data.ok) {
          Toast.error('Error: ' + data.msg);
          btnConfirmar.disabled = false;
          btnConfirmar.textContent = '✓ Confirmar Pedido';
          return;
        }
        window.location.href = data.redirect;
      })
      .catch(() => {
        Toast.error('Error de conexión.');
        btnConfirmar.disabled = false;
        btnConfirmar.textContent = '✓ Confirmar Pedido';
      });
  });

  // ── Render items ────────────────────────────────────────────
  function renderItems(items, total) {
    if (!items.length) {
      itemsBody.innerHTML = `
        <tr id="items-empty">
          <td colspan="6" class="text-center text-muted" style="padding:32px">
            Sin productos. Escanee un código de barras o busque por nombre.
          </td>
        </tr>`;
      totalEl.textContent = formatPesos(0);
      if (btnConfirmar) btnConfirmar.disabled = true;
      return;
    }

    itemsBody.innerHTML = items.map(item => `
      <tr>
        <td>
          <div class="font-bold">${item.tela_nombre}</div>
          <div class="text-sm text-muted">${item.descripcion}</div>
          <div class="text-xs text-muted">${formatPesos(item.precio_unit)}&thinsp;/&thinsp;${item.unidad}</div>
        </td>
        <td class="hide-mobile">${item.unidad}</td>
        <td>
          <input type="number"
            class="qty-inline"
            value="${item.cantidad}"
            step="0.001"
            min="${item.cantidad}"
            data-item-id="${item.id}"
            data-var-id="${item.variante_id}"
            data-minimo="0.001"
            style="width:80px;padding:4px 8px;border:1px solid #d1d5db;border-radius:6px;text-align:center">
        </td>
        <td class="hide-mobile">
          <input type="number" class="precio-inline" value="${parseFloat(item.precio_unit).toFixed(2)}"
                 step="0.01" min="0" data-var-id="${item.variante_id}" data-cantidad="${item.cantidad}"
                 style="width:82px;padding:4px 6px;border:1px solid #d1d5db;border-radius:6px;text-align:right;font-size:.85rem">
        </td>
        <td class="font-bold">${formatPesos(item.subtotal)}</td>
        <td>
          <button class="btn btn-sm btn-danger" data-del-item="${item.id}">✕</button>
        </td>
      </tr>`).join('');

    totalEl.textContent = formatPesos(total);
    if (btnConfirmar) btnConfirmar.disabled = false;
  }

  // ── UI helpers ──────────────────────────────────────────────
  function showBarcodeError(msg) {
    let el = document.getElementById('barcode-error');
    if (!el) {
      el = document.createElement('div');
      el.id = 'barcode-error';
      el.className = 'alert alert-error mt-4';
      barcodeInput.closest('.barcode-zone').appendChild(el);
    }
    el.textContent = msg;
    document.getElementById('variante-preview')?.remove();
    varianteActual = null;
  }

  function clearBarcodeError() {
    document.getElementById('barcode-error')?.remove();
  }

  // Auto-focus barcode input (solo en escritorio; en móvil el teclado no debe aparecer automáticamente)
  if (!('ontouchstart' in window)) barcodeInput.focus();

  // ── Escáner de cámara ───────────────────────────────────────
  const btnCamara = document.getElementById('btn-camara');
  if (btnCamara && typeof ZXing !== 'undefined') {
    const modalCamara    = document.getElementById('modal-camara');
    const btnCamaraClose = document.getElementById('btn-camara-close');
    const camaraVideo    = document.getElementById('camara-video');
    const camaraStatus   = document.getElementById('camara-status');
    const deviceWrap     = document.getElementById('camara-device-wrap');
    const deviceSelect   = document.getElementById('camara-device-select');

    const codeReader = new ZXing.BrowserMultiFormatReader();
    let scanActive   = false;

    btnCamara.addEventListener('click', async () => {
      modalCamara.classList.add('show');
      camaraStatus.textContent = 'Buscando cámaras...';
      deviceWrap.style.display = 'none';
      try {
        const devices = await codeReader.listVideoInputDevices();
        if (!devices.length) {
          camaraStatus.textContent = 'No se encontraron cámaras disponibles.';
          return;
        }
        // Preferir cámara trasera en móviles
        const back = devices.find(d => /back|rear|environment/i.test(d.label));
        let selectedId = (back || devices[devices.length - 1]).deviceId;

        if (devices.length > 1) {
          deviceSelect.innerHTML = devices.map((d, i) =>
            `<option value="${d.deviceId}" ${d.deviceId === selectedId ? 'selected' : ''}>${d.label || 'Cámara ' + (i + 1)}</option>`
          ).join('');
          deviceWrap.style.display = '';
          deviceSelect.onchange = () => {
            selectedId = deviceSelect.value;
            iniciarScan(selectedId);
          };
        }
        iniciarScan(selectedId);
      } catch (err) {
        camaraStatus.textContent = 'Error al acceder a la cámara: ' + err.message;
      }
    });

    function iniciarScan(deviceId) {
      codeReader.reset();
      camaraStatus.textContent = 'Apunte al código de barras...';
      scanActive = true;
      codeReader.decodeFromVideoDevice(deviceId, camaraVideo, (result, err) => {
        if (!scanActive) return;
        if (result) {
          const code = result.getText();
          cerrarCamara();
          barcodeInput.value = code;
          buscarBarcode(code);
        }
        // NotFoundException es normal (sin código visible), se ignora
      });
    }

    function cerrarCamara() {
      scanActive = false;
      codeReader.reset();
      modalCamara.classList.remove('show');
    }

    btnCamaraClose.addEventListener('click', cerrarCamara);
    modalCamara.querySelector('.modal-backdrop').addEventListener('click', cerrarCamara);
  }

  // ── Modal: configurar venta de ítem ─────────────────────────
  const modalAI     = document.getElementById('modal-add-item');
  const btnMaiClose = document.getElementById('btn-mai-close');
  const btnMaiAdd   = document.getElementById('btn-mai-agregar');
  const mAIqty      = document.getElementById('mai-qty');
  const mAIprecio   = document.getElementById('mai-precio');
  let mAIvar = null, _mAIrollo = null;

  function openAddItemModal(v, rollo = null) {
    mAIvar = v; _mAIrollo = rollo;
    document.getElementById('mai-nombre').textContent = `${v.tela_nombre} — ${v.descripcion}`;
    document.getElementById('mai-stock').textContent  = `📦 Stock: ${formatQty(v.stock, v.unidad)}  ·  ${formatPesos(v.precio)} / ${v.unidad}`;
    document.getElementById('mai-unidad').textContent = v.unidad;
    setMaiTab(rollo ? 'rollo' : 'fraccionado', rollo);
    modalAI?.classList.add('show');
    setTimeout(() => mAIqty?.focus(), 120);
  }

  function closeMaiModal() {
    modalAI?.classList.remove('show');
    mAIvar = null; varianteActual = null;
    barcodeInput.value = '';
    cerrarDropdown();
    clearBarcodeError();
  }

  function setMaiTab(tab, rollo) {
    if (rollo !== undefined) _mAIrollo = rollo ?? null;
    document.querySelectorAll('[data-mai-tab]').forEach(b => {
      b.classList.toggle('btn-primary', b.dataset.maiTab === tab);
      b.classList.toggle('btn-outline',  b.dataset.maiTab !== tab);
    });
    if (!mAIvar) return;
    if (tab === 'fraccionado') {
      mAIqty.value    = parseFloat(mAIvar.minimo_venta).toFixed(3);
      mAIqty.min      = mAIvar.minimo_venta;
      const pFrac = parseFloat(mAIvar.precio_fraccionado || 0);
      mAIprecio.value = (pFrac > 0 ? pFrac : parseFloat(mAIvar.precio)).toFixed(2);
      document.getElementById('mai-qty-hint').textContent =
        `Mínimo: ${formatQty(mAIvar.minimo_venta, mAIvar.unidad)}`;
    } else if (tab === 'rollo') {
      const r = _mAIrollo;
      mAIqty.value = r ? r.metros : '';
      mAIqty.min   = '0.001';
      const pr = parseFloat(mAIvar.precio_rollo || 0);
      const m  = parseFloat(mAIqty.value) || 0;
      mAIprecio.value = pr > 0 && m ? (pr / m).toFixed(2) : parseFloat(mAIvar.precio).toFixed(2);
      document.getElementById('mai-qty-hint').textContent = r
        ? `Rollo ${r.nro_rollo || '#' + r.id}: ${formatQty(r.metros, mAIvar.unidad)}`
        : 'Ingresá los metros/kilos del rollo';
    } else {
      mAIqty.value    = '';
      mAIqty.min      = '0.001';
      mAIprecio.value = parseFloat(mAIvar.precio).toFixed(2);
      document.getElementById('mai-qty-hint').textContent = '';
    }
    recalcMaiSub();
  }

  function recalcMaiSub() {
    const q = parseFloat(mAIqty?.value) || 0;
    const p = parseFloat(mAIprecio?.value) || 0;
    const el = document.getElementById('mai-subtotal');
    if (el) el.textContent = formatPesos(q * p);
  }

  [mAIqty, mAIprecio].forEach(el => el?.addEventListener('input', recalcMaiSub));
  document.querySelectorAll('[data-mai-tab]').forEach(b =>
    b.addEventListener('click', () => setMaiTab(b.dataset.maiTab)));
  btnMaiClose?.addEventListener('click', closeMaiModal);
  modalAI?.querySelector('.modal-backdrop')?.addEventListener('click', closeMaiModal);

  btnMaiAdd?.addEventListener('click', () => {
    if (!mAIvar) return;
    const cantidad = parseFloat(mAIqty.value);
    const precio   = parseFloat(mAIprecio.value);
    if (!cantidad || cantidad <= 0) { alert('Ingresá una cantidad válida.'); return; }
    btnMaiAdd.disabled = true;
    btnMaiAdd.innerHTML = '<span class="spinner"></span>';
    const fd = new FormData();
    fd.append('pedido_id',   pedidoId);
    fd.append('variante_id', mAIvar.id);
    fd.append('cantidad',    cantidad);
    fd.append('precio_unit', precio);
    if (_mAIrollo?.id) fd.append('rollo_id', _mAIrollo.id);
    fetch('index.php?page=pedido_item_add', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        if (!data.ok) { Toast.error(data.msg); return; }
        renderItems(data.items, data.total);
        closeMaiModal();
        if (!('ontouchstart' in window)) barcodeInput.focus();
      })
      .catch(() => Toast.error('Error al agregar ítem.'))
      .finally(() => {
        btnMaiAdd.disabled = false;
        btnMaiAdd.textContent = '＋ Agregar al pedido';
      });
  });

  // ── Catálogo de productos ─────────────────────────────────────
  const catalogoPanel = document.getElementById('catalogo-panel');
  const barcodeZone   = document.querySelector('.barcode-zone');
  let catLoaded = false, catData = [], catVista = 'scanner';

  function setVista(v) {
    catVista = v;
    document.querySelectorAll('.btn-vista').forEach(b => {
      b.classList.toggle('btn-primary', b.dataset.vista === v);
      b.classList.toggle('btn-outline',  b.dataset.vista !== v);
    });
    if (barcodeZone) barcodeZone.style.display = v === 'scanner' ? '' : 'none';
    if (catalogoPanel) catalogoPanel.style.display = v !== 'scanner' ? '' : 'none';
    if (v !== 'scanner' && !catLoaded) loadCatalogo();
    if (v !== 'scanner') renderCatalogo(v);
    else if (!('ontouchstart' in window)) barcodeInput.focus();
  }

  document.querySelectorAll('.btn-vista').forEach(b =>
    b.addEventListener('click', () => setVista(b.dataset.vista)));

  function loadCatalogo() {
    catLoaded = true;
    const grid = document.getElementById('cat-grid');
    if (grid) grid.innerHTML = '<div class="text-sm text-muted" style="padding:20px">Cargando...</div>';
    fetch('index.php?page=pedido_catalogo')
      .then(r => r.json())
      .then(data => {
        if (!data.ok) return;
        catData = data.variantes;
        const cnt = document.getElementById('cat-count');
        if (cnt) cnt.textContent = `${catData.length} artículo${catData.length !== 1 ? 's' : ''}`;
        renderCatalogo(catVista);
      })
      .catch(() => {
        const grid = document.getElementById('cat-grid');
        if (grid) grid.innerHTML = '<div class="text-sm text-muted" style="padding:20px">Error al cargar.</div>';
      });
  }

  function renderCatalogo(mode) {
    const search = (document.getElementById('cat-search')?.value || '').toLowerCase();
    const tipo   = document.getElementById('cat-tipo')?.value  || '';
    const filtered = catData.filter(v =>
      (!tipo   || v.tipo === tipo) &&
      (!search || `${v.tela_nombre} ${v.descripcion}`.toLowerCase().includes(search))
    );
    const grid = document.getElementById('cat-grid');
    if (!grid) return;
    if (!filtered.length) {
      grid.innerHTML = '<div class="text-sm text-muted" style="padding:20px">Sin resultados.</div>';
      return;
    }
    const base = window._base || '';
    if (mode === 'imagenes') {
      grid.className = 'cat-imagen-grid';
      grid.innerHTML = filtered.map(v => `
        <div class="cat-card" data-var-id="${v.id}">
          <div class="cat-img">
            ${v.imagen_url
              ? `<img src="${base}/${v.imagen_url}" alt="${v.tela_nombre}" loading="lazy">`
              : `<span class="cat-img-placeholder">🧵</span>`
            }
          </div>
          <div class="cat-info">
            <div class="cat-nombre">${v.tela_nombre}</div>
            <div class="cat-desc">${v.descripcion}</div>
            <div class="cat-precio">${formatPesos(v.precio)}&thinsp;/&thinsp;${v.unidad}</div>
            <div class="cat-stock">${formatQty(v.stock, v.unidad)}</div>
          </div>
        </div>`).join('');
    } else {
      grid.className = '';
      grid.innerHTML = `<table style="width:100%;font-size:.85rem;border-collapse:collapse">
        <thead><tr style="border-bottom:2px solid #e5e7eb;text-align:left">
          <th style="padding:8px">Producto</th>
          <th style="padding:8px">Stock</th>
          <th style="padding:8px">Precio</th>
          <th style="padding:8px"></th>
        </tr></thead><tbody>` +
        filtered.map(v => `<tr style="border-bottom:1px solid #f3f4f6">
          <td style="padding:8px">
            <div style="font-weight:600">${v.tela_nombre}</div>
            <div style="color:#6b7280;font-size:.8rem">${v.descripcion}</div>
          </td>
          <td style="padding:8px;white-space:nowrap">${formatQty(v.stock, v.unidad)}</td>
          <td style="padding:8px;white-space:nowrap">${formatPesos(v.precio)}</td>
          <td style="padding:8px">
            <button class="btn btn-sm btn-outline cat-card" data-var-id="${v.id}">＋</button>
          </td>
        </tr>`).join('') + '</tbody></table>';
    }
    grid.querySelectorAll('.cat-card').forEach(el => {
      el.addEventListener('click', () => {
        const vid = parseInt(el.dataset.varId || el.closest('[data-var-id]')?.dataset.varId);
        const v   = catData.find(x => x.id === vid);
        if (v) openAddItemModal(v);
      });
    });
  }

  document.getElementById('cat-search')?.addEventListener('input',  () => renderCatalogo(catVista));
  document.getElementById('cat-tipo')?.addEventListener('change',   () => renderCatalogo(catVista));

  // ── Widget de cliente ───────────────────────────────────────────────
  const clienteAsignado      = document.getElementById('cliente-asignado');
  const clienteSinAsignar    = document.getElementById('cliente-sin-asignar');
  const clienteSearchPanel   = document.getElementById('cliente-search-panel');
  const clienteNombreDisplay = document.getElementById('cliente-nombre-display');
  const clienteSearchInput   = document.getElementById('cliente-search-input');
  const clienteSearchResults = document.getElementById('cliente-search-results');

  if (clienteAsignado && clienteSearchInput) {
    const abrirBusquedaCliente = () => {
      clienteSearchPanel.style.display = '';
      clienteSearchInput.value = '';
      clienteSearchResults.innerHTML = '';
      clienteSearchInput.focus();
    };
    const cerrarBusquedaCliente = () => {
      clienteSearchPanel.style.display = 'none';
    };
    const setCliente = clienteId => {
      const fd = new FormData();
      fd.append('pedido_id',  pedidoId);
      fd.append('cliente_id', clienteId);
      fetch('index.php?page=pedido_cliente_set', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
          if (!data.ok) { Toast.error(data.msg); return; }
          cerrarBusquedaCliente();
          if (data.cliente) {
            clienteNombreDisplay.textContent = data.cliente.nombre;
            clienteAsignado.style.display = '';
            clienteSinAsignar.style.display = 'none';
          } else {
            clienteAsignado.style.display = 'none';
            clienteSinAsignar.style.display = '';
          }
        })
          .catch(() => Toast.error('Error al asignar cliente.'));
    };

    document.getElementById('btn-asignar-cliente')?.addEventListener('click', abrirBusquedaCliente);
    document.getElementById('btn-cambiar-cliente')?.addEventListener('click', abrirBusquedaCliente);
    document.getElementById('btn-cerrar-cliente-search')?.addEventListener('click', cerrarBusquedaCliente);
    document.getElementById('btn-quitar-cliente')?.addEventListener('click', () => setCliente(0));

    let buscarClienteTimeout;
    clienteSearchInput.addEventListener('input', () => {
      clearTimeout(buscarClienteTimeout);
      const q = clienteSearchInput.value.trim();
      if (q.length < 2) { clienteSearchResults.innerHTML = ''; return; }
      buscarClienteTimeout = setTimeout(() => {
        fetch(`index.php?page=clientes_buscar&q=${encodeURIComponent(q)}`)
          .then(r => r.json())
          .then(data => {
            if (!data.length) {
              clienteSearchResults.innerHTML = '<div class="cliente-item" style="cursor:default;color:var(--gray-500)">Sin resultados. <a href="index.php?page=cliente_nuevo" target="_blank">Crear nuevo ↗</a></div>';
              return;
            }
            clienteSearchResults.innerHTML = data.map(c =>
              `<div class="cliente-item" data-id="${c.id}">
                <div class="font-bold">${c.nombre}</div>
                ${c.telefono ? `<div class="text-xs text-muted">${c.telefono}</div>` : ''}
              </div>`
            ).join('');
            clienteSearchResults.querySelectorAll('.cliente-item[data-id]').forEach(el => {
              el.addEventListener('click', () => setCliente(parseInt(el.dataset.id)));
            });
          });
      }, 250);
    });
  }
}

// ═══════════════════════════════════════════════════════════════
// MÓDULO: ANULAR PEDIDO (modal)
// ═══════════════════════════════════════════════════════════════
const btnAnular = document.getElementById('btn-anular');
if (btnAnular) {
  const modal     = document.getElementById('modal-anular');
  const btnClose  = document.getElementById('modal-close');
  const btnConfAn = document.getElementById('btn-confirmar-anular');
  const motivoEl  = document.getElementById('motivo-anulacion');
  const pedidoId  = btnAnular.dataset.pedidoId;

  btnAnular.addEventListener('click', () => modal.classList.add('show'));
  btnClose?.addEventListener('click', () => modal.classList.remove('show'));
  modal?.querySelector('.modal-backdrop')?.addEventListener('click', () => modal.classList.remove('show'));

  btnConfAn?.addEventListener('click', () => {
    const motivo = motivoEl.value.trim();
    if (!motivo) { Toast.warn('Ingresá el motivo de anulación.'); return; }
    btnConfAn.innerHTML = '<span class="spinner"></span> Anulando...';

    const fd = new FormData();
    fd.append('pedido_id', pedidoId);
    fd.append('motivo',    motivo);

    fetch('index.php?page=pedido_anular', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        if (!data.ok) {
          Toast.error('Error: ' + data.msg);
          btnConfAn.disabled = false;
          btnConfAn.textContent = 'Confirmar Anulación';
          return;
        }
        window.location.href = data.redirect;
      });
  });
}
