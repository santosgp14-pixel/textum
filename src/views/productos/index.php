<?php
$pageTitle   = 'Productos';
$currentPage = 'productos';
require VIEW_PATH . '/layout/header.php';

function fPesos(float $n): string {
    return '$' . number_format($n, 0, ',', '.');
}
function fNum(float $n, int $dec = 1): string {
    return number_format($n, $dec, ',', '.');
}
?>

<!-- ── Panel de indicadores del producto seleccionado ─────────── -->
<div id="prod-kpi-panel" style="display:none;margin-bottom:24px">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
    <span id="kpi-nombre" style="font-weight:700;font-size:1.1rem;color:var(--primary)"></span>
    <button onclick="clearKpi()" style="background:none;border:none;cursor:pointer;font-size:1.2rem;color:#9ca3af" title="Cerrar">✕</button>
  </div>
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-label">Kilos en stock</div>
      <div id="kpi-kilos" class="stat-value" style="font-size:1.3rem">—</div>
      <div class="stat-sub">kg en variantes</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Metros equivalentes</div>
      <div id="kpi-metros" class="stat-value" style="font-size:1.3rem">—</div>
      <div class="stat-sub">kilos × rinde + metros</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">$ venta prom.</div>
      <div id="kpi-precio-rollo" class="stat-value" style="font-size:1.15rem">—</div>
      <div class="stat-sub">precio de venta directo</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">$ prom. x metro</div>
      <div id="kpi-precio-metro" class="stat-value" style="font-size:1.15rem">—</div>
      <div class="stat-sub">precio ÷ rinde +50%</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Rinde</div>
      <div id="kpi-rinde" class="stat-value" style="font-size:1.3rem">—</div>
      <div class="stat-sub">m/kg</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Costo prom.</div>
      <div id="kpi-costo" class="stat-value" style="font-size:1.15rem">—</div>
      <div class="stat-sub">costo de compra</div>
    </div>
    <div class="stat-card" id="kpi-variantes-card" style="cursor:pointer">
      <div class="stat-label">Variantes</div>
      <div id="kpi-variantes" class="stat-value" style="font-size:1.3rem">—</div>
      <div class="stat-sub">colores / presentaciones</div>
    </div>
  </div>
</div>

<div id="prod-kpi-hint" style="margin-bottom:16px;color:#9ca3af;font-size:0.85rem;text-align:center">
  Seleccioná un producto para ver sus indicadores
</div>

<!-- ── Detalle por producto ──────────────────────────────────── -->
<div class="card">
  <div class="card-header" style="justify-content:space-between">
    <span class="card-title">🧵 Indicadores por producto</span>
    <a href="index.php?page=stock" class="btn btn-sm btn-outline">Ver stock / telas</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Producto</th>
          <th class="hide-mobile">Tipo</th>
          <th class="hide-mobile" style="text-align:right">Kilos</th>
          <th style="text-align:right">Metros</th>
          <th class="hide-mobile" style="text-align:right">$ venta</th>
          <th style="text-align:right" title="precio ÷ rinde +15%">$ x&nbsp;metro</th>
          <th class="hide-mobile" style="text-align:right">Rinde</th>
          <th class="hide-mobile" style="text-align:right">Costo&nbsp;prom.</th>
          <th style="text-align:center">Var.</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($productos)): ?>
        <tr>
          <td colspan="9" class="text-center text-muted" style="padding:32px">
            No hay productos activos. <a href="index.php?page=tela_nueva">Crear uno</a>.
          </td>
        </tr>
        <?php else: ?>
        <?php foreach ($productos as $p): ?>
        <tr class="prod-row" style="cursor:pointer"
            data-id="<?= $p['id'] ?>"
            data-nombre="<?= htmlspecialchars($p['nombre'], ENT_QUOTES) ?>"
            data-tipo="<?= htmlspecialchars($p['tipo'] ?? '', ENT_QUOTES) ?>"
            data-kilos="<?= (float)$p['stock_kilos'] ?>"
            data-metros="<?= (float)$p['stock_metros'] ?>"
            data-precio-rollo="<?= (float)$p['avg_precio_rollo'] ?>"
            data-precio-metro="<?= (float)$p['avg_precio_metro'] ?>"
            data-rinde="<?= (float)($p['rinde'] ?? 0) ?>"
            data-costo="<?= (float)$p['avg_costo'] ?>"
            data-variantes="<?= (int)$p['total_variantes'] ?>">
          <td>
            <div class="font-bold"><?= htmlspecialchars($p['nombre']) ?></div>
            <?php if ($p['imagen_url']): ?>
            <div style="margin-top:4px">
              <img src="<?= BASE_URL . '/' . htmlspecialchars($p['imagen_url']) ?>"
                   alt="" style="height:36px;width:36px;object-fit:cover;border-radius:4px;border:1px solid #e5e7eb">
            </div>
            <?php endif; ?>
          </td>
          <td class="hide-mobile">
            <span class="badge <?= $p['tipo'] === 'punto' ? 'badge-blue' : 'badge-abierto' ?>">
              <?= htmlspecialchars($p['tipo'] ?? '—') ?>
            </span>
          </td>
          <td class="hide-mobile" style="text-align:right;font-variant-numeric:tabular-nums">
            <?= $p['stock_kilos'] > 0 ? fNum($p['stock_kilos']) : '<span class="text-muted">—</span>' ?>
          </td>
          <td style="text-align:right;font-variant-numeric:tabular-nums">
            <?= $p['stock_metros'] > 0 ? fNum($p['stock_metros']) : '<span class="text-muted">—</span>' ?>
          </td>
          <td class="hide-mobile" style="text-align:right;font-variant-numeric:tabular-nums">
            <?= $p['avg_precio_rollo'] > 0 ? fPesos($p['avg_precio_rollo']) : '<span class="text-muted">—</span>' ?>
          </td>
          <td style="text-align:right;font-variant-numeric:tabular-nums">
            <?= $p['avg_precio_metro'] > 0 ? fPesos($p['avg_precio_metro']) : '<span class="text-muted">—</span>' ?>
          </td>
          <td class="hide-mobile" style="text-align:right;font-variant-numeric:tabular-nums">
            <?= $p['rinde'] > 0 ? fNum((float)$p['rinde'], 2) : '<span class="text-muted">—</span>' ?>
          </td>
          <td class="hide-mobile" style="text-align:right;font-variant-numeric:tabular-nums">
            <?= $p['avg_costo'] > 0 ? fPesos($p['avg_costo']) : '<span class="text-muted">—</span>' ?>
          </td>
          <td style="text-align:center">
            <a href="index.php?page=variantes&tela_id=<?= $p['id'] ?>"
               class="btn btn-sm btn-outline"><?= (int)$p['total_variantes'] ?></a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function fPesos(n) { return '$' + Math.round(n).toLocaleString('es-AR'); }
function fNum(n, d) { return n.toLocaleString('es-AR', {minimumFractionDigits:d??1,maximumFractionDigits:d??1}); }

function clearKpi() {
  document.getElementById('prod-kpi-panel').style.display = 'none';
  document.getElementById('prod-kpi-hint').style.display  = 'block';
  document.querySelectorAll('.prod-row').forEach(r => r.classList.remove('row-selected'));
}

document.querySelectorAll('.prod-row').forEach(function(row) {
  row.addEventListener('click', function(e) {
    // Evitar abrir KPI si se hizo click en el botón de variantes
    if (e.target.closest('a')) return;

    document.querySelectorAll('.prod-row').forEach(r => r.classList.remove('row-selected'));
    row.classList.add('row-selected');

    var d = row.dataset;
    document.getElementById('kpi-nombre').textContent       = d.nombre;
    document.getElementById('kpi-kilos').textContent        = parseFloat(d.kilos) > 0 ? fNum(parseFloat(d.kilos)) : '—';
    document.getElementById('kpi-metros').textContent       = parseFloat(d.metros) > 0 ? fNum(parseFloat(d.metros)) : '—';
    document.getElementById('kpi-precio-rollo').textContent = parseFloat(d.precioRollo) > 0 ? fPesos(parseFloat(d.precioRollo)) : '—';
    document.getElementById('kpi-precio-metro').textContent = parseFloat(d.precioMetro) > 0 ? fPesos(parseFloat(d.precioMetro)) : '—';
    document.getElementById('kpi-rinde').textContent        = parseFloat(d.rinde) > 0 ? fNum(parseFloat(d.rinde), 2) : '—';
    document.getElementById('kpi-costo').textContent        = parseFloat(d.costo) > 0 ? fPesos(parseFloat(d.costo)) : '—';
    document.getElementById('kpi-variantes').textContent    = d.variantes;
    document.getElementById('kpi-variantes-card').onclick   = function() {
      window.location = 'index.php?page=variantes&tela_id=' + d.id;
    };

    document.getElementById('prod-kpi-hint').style.display  = 'none';
    document.getElementById('prod-kpi-panel').style.display = 'block';
    document.getElementById('prod-kpi-panel').scrollIntoView({behavior:'smooth', block:'nearest'});
  });
});
</script>

<?php require VIEW_PATH . '/layout/footer.php'; ?>
