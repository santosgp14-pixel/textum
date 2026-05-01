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

<!-- ── Indicadores globales ─────────────────────────────────── -->
<div class="stats-grid" style="margin-bottom:24px">
  <div class="stat-card">
    <div class="stat-label">Productos activos</div>
    <div class="stat-value"><?= $totales['productos'] ?></div>
    <div class="stat-sub">telas en stock</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Total kilos</div>
    <div class="stat-value" style="font-size:1.3rem"><?= fNum($totales['stock_kilos']) ?></div>
    <div class="stat-sub">kg en variantes</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Total metros</div>
    <div class="stat-value" style="font-size:1.3rem"><?= fNum($totales['stock_metros']) ?></div>
    <div class="stat-sub">m en variantes</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">$ prom. x rollo</div>
    <div class="stat-value" style="font-size:1.15rem"><?= fPesos($totales['avg_precio_rollo']) ?></div>
    <div class="stat-sub">precio de venta</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">$ prom. x metro</div>
    <div class="stat-value" style="font-size:1.15rem"><?= fPesos($totales['avg_precio_metro']) ?></div>
    <div class="stat-sub">precio de venta</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Rinde promedio</div>
    <div class="stat-value" style="font-size:1.3rem"><?= fNum($totales['avg_rinde'], 2) ?></div>
    <div class="stat-sub">m/kg promedio</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Costo prom. rollo</div>
    <div class="stat-value" style="font-size:1.15rem"><?= fPesos($totales['avg_costo']) ?></div>
    <div class="stat-sub">costo de compra</div>
  </div>
  <?php if ($totales['costo_por_kilo'] > 0): ?>
  <div class="stat-card">
    <div class="stat-label">Costo prom. / kg</div>
    <div class="stat-value" style="font-size:1.15rem"><?= fPesos($totales['costo_por_kilo']) ?></div>
    <div class="stat-sub">total pagado ÷ kg en stock</div>
  </div>
  <?php endif; ?>
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
          <th class="hide-mobile" style="text-align:right">$ x&nbsp;rollo</th>
          <th style="text-align:right">$ x&nbsp;metro</th>
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
        <tr>
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

<?php require VIEW_PATH . '/layout/footer.php'; ?>
