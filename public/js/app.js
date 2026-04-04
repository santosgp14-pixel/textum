/**
 * TEXTUM - app.js
 * Vanilla JS: sidebar, pedido abierto (barcode), confirmación, anulación
 */
'use strict';

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

// ── Flash auto-dismiss ──────────────────────────────────────────
document.querySelectorAll('.alert[data-autodismiss]').forEach(el => {
  setTimeout(() => el.remove(), 3500);
});

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

  // ── Búsqueda por código de barras ──────────────────────────
  let scanTimeout;
  barcodeInput.addEventListener('input', () => {
    clearTimeout(scanTimeout);
    // Esperar 300ms después del último caracter (simula scanner)
    scanTimeout = setTimeout(() => {
      const code = barcodeInput.value.trim();
      if (code.length < 3) return;
      buscarBarcode(code);
    }, 300);
  });

  barcodeInput.addEventListener('keydown', e => {
    if (e.key === 'Enter') {
      e.preventDefault();
      const code = barcodeInput.value.trim();
      if (code) buscarBarcode(code);
    }
  });

  function buscarBarcode(code) {
    fetch(`index.php?page=barcode_buscar&barcode=${encodeURIComponent(code)}`)
      .then(r => r.json())
      .then(data => {
        if (!data.ok) {
          showBarcodeError(data.msg);
          return;
        }
        varianteActual = data.variante;
        const rollo = data.rollo || null;
        clearBarcodeError();
        openAddItemModal(varianteActual, rollo);
      })
      .catch(() => showBarcodeError('Error de conexión'));
  }

  // ── Eliminar item ───────────────────────────────────────────────
  itemsBody.addEventListener('click', e => {
    const btn = e.target.closest('[data-del-item]');
    if (!btn) return;
    const itemId = btn.dataset.delItem;
    if (!confirm('¿Eliminar este ítem?')) return;

    const fd = new FormData();
    fd.append('pedido_id', pedidoId);
    fd.append('item_id',   itemId);

    fetch('index.php?page=pedido_item_del', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        if (!data.ok) { alert(data.msg); return; }
        renderItems(data.items, data.total);
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
  btnConfirmar?.addEventListener('click', () => {
    if (!confirm('¿Confirmar pedido? Se descontará el stock.')) return;

    btnConfirmar.disabled = true;
    btnConfirmar.innerHTML = '<span class="spinner"></span> Confirmando...';

    const fd = new FormData();
    fd.append('pedido_id', pedidoId);

    fetch('index.php?page=pedido_confirmar', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        if (!data.ok) {
          alert('Error: ' + data.msg);
          btnConfirmar.disabled = false;
          btnConfirmar.textContent = '✓ Confirmar Pedido';
          return;
        }
        window.location.href = data.redirect;
      })
      .catch(() => {
        alert('Error de conexión.');
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
            Sin productos. Escanee un código de barras para comenzar.
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
      mAIprecio.value = parseFloat(mAIvar.precio).toFixed(2);
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
        if (!data.ok) { alert(data.msg); return; }
        renderItems(data.items, data.total);
        closeMaiModal();
        if (!('ontouchstart' in window)) barcodeInput.focus();
      })
      .catch(() => alert('Error al agregar ítem.'))
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
          if (!data.ok) { alert(data.msg); return; }
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
        .catch(() => alert('Error al asignar cliente.'));
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
    if (!motivo) { alert('Ingrese el motivo de anulación.'); return; }

    btnConfAn.disabled = true;
    btnConfAn.innerHTML = '<span class="spinner"></span> Anulando...';

    const fd = new FormData();
    fd.append('pedido_id', pedidoId);
    fd.append('motivo',    motivo);

    fetch('index.php?page=pedido_anular', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        if (!data.ok) {
          alert('Error: ' + data.msg);
          btnConfAn.disabled = false;
          btnConfAn.textContent = 'Confirmar Anulación';
          return;
        }
        window.location.href = data.redirect;
      });
  });
}
