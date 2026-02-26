/**
 * TEXTUM - app.js
 * Vanilla JS: sidebar, pedido abierto (barcode), confirmación, anulación
 */
'use strict';

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
  const qtyInput     = document.getElementById('qty-input');
  const addItemBtn   = document.getElementById('btn-add-item');
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
        showVariantePreview(varianteActual);
        qtyInput.value = varianteActual.minimo_venta;
        qtyInput.focus();
        qtyInput.select();
        clearBarcodeError();
      })
      .catch(() => showBarcodeError('Error de conexión'));
  }

  // ── Agregar item ────────────────────────────────────────────
  addItemBtn.addEventListener('click', agregarItem);
  qtyInput.addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); agregarItem(); }
  });

  function agregarItem() {
    if (!varianteActual) { alert('Escanee un código de barras primero.'); return; }

    const cantidad = parseFloat(qtyInput.value);
    if (!cantidad || cantidad <= 0) { alert('Ingrese una cantidad válida.'); return; }

    addItemBtn.disabled = true;
    addItemBtn.innerHTML = '<span class="spinner"></span>';

    const fd = new FormData();
    fd.append('pedido_id',   pedidoId);
    fd.append('variante_id', varianteActual.id);
    fd.append('cantidad',    cantidad);

    fetch('index.php?page=pedido_item_add', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        if (!data.ok) { alert(data.msg); return; }
        renderItems(data.items, data.total);
        // Reset
        varianteActual = null;
        barcodeInput.value = '';
        qtyInput.value = '';
        document.getElementById('variante-preview')?.remove();
        barcodeInput.focus();
      })
      .catch(() => alert('Error al agregar item.'))
      .finally(() => {
        addItemBtn.disabled = false;
        addItemBtn.textContent = '+ Agregar';
      });
  }

  // ── Eliminar item ───────────────────────────────────────────
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

  // ── Editar cantidad inline ──────────────────────────────────
  itemsBody.addEventListener('change', e => {
    const input = e.target.closest('.qty-inline');
    if (!input) return;
    const itemId    = input.dataset.itemId;
    const varId     = input.dataset.varId;
    const cantidad  = parseFloat(input.value);
    const minimo    = parseFloat(input.dataset.minimo);

    if (cantidad < minimo) {
      alert(`Mínimo: ${minimo}`);
      input.value = minimo;
      return;
    }

    const fd = new FormData();
    fd.append('pedido_id',   pedidoId);
    fd.append('variante_id', varId);
    fd.append('cantidad',    cantidad);

    fetch('index.php?page=pedido_item_add', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        if (!data.ok) { alert(data.msg); return; }
        renderItems(data.items, data.total);
      });
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
          <div class="text-xs text-muted">${item.codigo_barras}</div>
        </td>
        <td>${item.unidad}</td>
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
        <td>${formatPesos(item.precio_unit)}</td>
        <td class="font-bold">${formatPesos(item.subtotal)}</td>
        <td>
          <button class="btn btn-sm btn-danger" data-del-item="${item.id}">✕</button>
        </td>
      </tr>`).join('');

    totalEl.textContent = formatPesos(total);
    if (btnConfirmar) btnConfirmar.disabled = false;
  }

  // ── UI helpers ──────────────────────────────────────────────
  function showVariantePreview(v) {
    let el = document.getElementById('variante-preview');
    if (!el) {
      el = document.createElement('div');
      el.id = 'variante-preview';
      el.className = 'alert alert-ok mt-4';
      barcodeInput.closest('.barcode-zone').appendChild(el);
    }
    el.innerHTML = `
      <div>
        <strong>${v.tela_nombre}</strong> — ${v.descripcion}<br>
        <span class="text-sm">Precio: ${formatPesos(v.precio)} / ${v.unidad}
        &nbsp;|&nbsp; Stock: ${formatQty(v.stock, v.unidad)}</span>
      </div>`;
  }

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

  // Auto-focus barcode input
  barcodeInput.focus();

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

  // ── Widget de cliente ─────────────────────────────────────────
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
