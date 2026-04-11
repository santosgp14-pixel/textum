<?php
$pageTitle   = 'Reportes';
$currentPage = 'reportes';
// Pass chart data as JSON for Chart.js
$chartVentasDias   = json_encode($diasTotales,  JSON_NUMERIC_CHECK);
$chartDiasLabels   = json_encode($diasLabels);
$chartTopNombres   = json_encode(array_column($topTelas, 'tela'));
$chartTopTotales   = json_encode(array_map(fn($r) => (float)$r['total'], $topTelas), JSON_NUMERIC_CHECK);
$chartMesesLabels  = json_encode($mesesLabels);
$chartMesesIngresos = json_encode($mesesIngresos, JSON_NUMERIC_CHECK);
$chartMesesGastos  = json_encode($mesesGastos,   JSON_NUMERIC_CHECK);
require VIEW_PATH . '/layout/header.php';
?>

<!-- KPIs del mes -->
<div class="stats-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px;margin-bottom:28px">
  <div class="stat-card">
    <div class="stat-value">$ <?= number_format($kpis['total_ventas'], 0, ',', '.') ?></div>
    <div class="stat-label">Ventas este mes</div>
  </div>
  <div class="stat-card">
    <div class="stat-value"><?= (int)$kpis['total_pedidos'] ?></div>
    <div class="stat-label">Pedidos confirmados</div>
  </div>
  <div class="stat-card">
    <div class="stat-value">$ <?= number_format($kpis['ticket_promedio'], 0, ',', '.') ?></div>
    <div class="stat-label">Ticket promedio</div>
  </div>
  <div class="stat-card">
    <div class="stat-value" style="font-size:1rem"><?= htmlspecialchars($topCliente['nombre'] ?? '—') ?></div>
    <div class="stat-label">Mejor cliente del mes</div>
  </div>
</div>

<!-- Gráficos -->
<div style="display:grid;grid-template-columns:1fr;gap:24px">

  <!-- Ventas diarias 30 días -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">📈 Ventas diarias — últimos 30 días</span>
    </div>
    <div class="card-body" style="padding:16px">
      <canvas id="chart-dias" height="80"></canvas>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:24px">

    <!-- Top telas del mes -->
    <div class="card">
      <div class="card-header">
        <span class="card-title">🏆 Top telas este mes</span>
      </div>
      <div class="card-body" style="padding:16px">
        <?php if (empty($topTelas)): ?>
          <p class="text-sm text-muted text-center" style="padding:24px 0">Sin datos este mes.</p>
        <?php else: ?>
          <canvas id="chart-top" height="140"></canvas>
        <?php endif; ?>
      </div>
    </div>

    <!-- Ingresos vs Gastos 6 meses -->
    <div class="card">
      <div class="card-header">
        <span class="card-title">📊 Ingresos vs Gastos — 6 meses</span>
      </div>
      <div class="card-body" style="padding:16px">
        <canvas id="chart-meses" height="140"></canvas>
      </div>
    </div>

  </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function() {
  const blueMain = '#2563eb';
  const blueLight = 'rgba(37,99,235,.15)';
  const green = '#16a34a';
  const red = '#ef4444';
  const orange = '#f97316';

  const gridColor = 'rgba(0,0,0,.06)';
  const fmtPesos = v => '$\u2009' + new Intl.NumberFormat('es-AR', {maximumFractionDigits: 0}).format(v);

  // Chart 1: ventas diarias
  new Chart(document.getElementById('chart-dias'), {
    type: 'line',
    data: {
      labels: <?= $chartDiasLabels ?>,
      datasets: [{
        label: 'Ventas',
        data: <?= $chartVentasDias ?>,
        borderColor: blueMain,
        backgroundColor: blueLight,
        borderWidth: 2,
        pointRadius: 2,
        fill: true,
        tension: 0.3
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: ctx => fmtPesos(ctx.parsed.y) } }
      },
      scales: {
        x: { grid: { color: gridColor } },
        y: { grid: { color: gridColor }, ticks: { callback: v => fmtPesos(v) } }
      }
    }
  });

  <?php if (!empty($topTelas)): ?>
  // Chart 2: top telas
  new Chart(document.getElementById('chart-top'), {
    type: 'bar',
    data: {
      labels: <?= $chartTopNombres ?>,
      datasets: [{
        label: 'Ingresos',
        data: <?= $chartTopTotales ?>,
        backgroundColor: blueMain,
        borderRadius: 4
      }]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: ctx => fmtPesos(ctx.parsed.x) } }
      },
      scales: {
        x: { grid: { color: gridColor }, ticks: { callback: v => fmtPesos(v) } },
        y: { grid: { color: gridColor } }
      }
    }
  });
  <?php endif; ?>

  // Chart 3: ingresos vs gastos por mes
  new Chart(document.getElementById('chart-meses'), {
    type: 'bar',
    data: {
      labels: <?= $chartMesesLabels ?>,
      datasets: [
        {
          label: 'Ingresos',
          data: <?= $chartMesesIngresos ?>,
          backgroundColor: green,
          borderRadius: 4
        },
        {
          label: 'Gastos',
          data: <?= $chartMesesGastos ?>,
          backgroundColor: red,
          borderRadius: 4
        }
      ]
    },
    options: {
      responsive: true,
      plugins: {
        tooltip: { callbacks: { label: ctx => ctx.dataset.label + ': ' + fmtPesos(ctx.parsed.y) } }
      },
      scales: {
        x: { grid: { color: gridColor } },
        y: { grid: { color: gridColor }, ticks: { callback: v => fmtPesos(v) } }
      }
    }
  });
})();
</script>

<?php require VIEW_PATH . '/layout/footer.php'; ?>
