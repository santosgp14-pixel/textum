<?php
$pageTitle   = 'Balance';
$currentPage = 'balance';
require VIEW_PATH . '/layout/header.php';
$netoClass = $neto >= 0 ? 'neto-pos' : 'neto-neg';
?>

<!-- Selector de fecha -->
<div class="flex items-center gap-3 mb-6 flex-wrap">
  <form method="GET" action="index.php" class="flex items-center gap-3">
    <input type="hidden" name="page" value="balance">
    <label class="form-label" style="margin:0;white-space:nowrap">📅 Fecha:</label>
    <input type="date" name="fecha" value="<?= htmlspecialchars($fecha) ?>" class="form-control" style="width:auto">
    <button type="submit" class="btn btn-primary btn-sm">Ver</button>
  </form>
  <a href="index.php?page=balance&fecha=<?= date('Y-m-d') ?>" class="btn btn-sm btn-outline">Hoy</a>
</div>

<!-- Tarjetas resumen -->
<div class="balance-grid">
  <div class="balance-card ingresos">
    <div class="bc-icon">💰</div>
    <div class="bc-label">Ingresos ventas</div>
    <div class="bc-value">$ <?= number_format($ingresos, 2, ',', '.') ?></div>
  </div>
  <div class="balance-card anulados">
    <div class="bc-icon">↩</div>
    <div class="bc-label">Anulaciones</div>
    <div class="bc-value">$ <?= number_format($anulaciones, 2, ',', '.') ?></div>
  </div>
  <div class="balance-card gastos">
    <div class="bc-icon">📤</div>
    <div class="bc-label">Gastos</div>
    <div class="bc-value">$ <?= number_format($gastos, 2, ',', '.') ?></div>
  </div>
  <div class="balance-card <?= $netoClass ?>">
    <div class="bc-icon"><?= $neto >= 0 ? '📈' : '📉' ?></div>
    <div class="bc-label">Resultado neto</div>
    <div class="bc-value">$ <?= number_format($neto, 2, ',', '.') ?></div>
  </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:20px">

  <!-- Pedidos del día -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">Ventas del día</span>
      <span class="badge badge-confirmado"><?= count($pedidos_dia) ?> pedido<?= count($pedidos_dia) !== 1 ? 's' : '' ?></span>
    </div>
    <?php if (empty($pedidos_dia)): ?>
    <div class="empty-state">
      <div class="empty-state-icon">🧾</div>
      <div class="empty-state-title">Sin ventas este día</div>
    </div>
    <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>#</th><th>Hora</th><th>Vendedor</th><th>Total</th></tr></thead>
        <tbody>
          <?php foreach ($pedidos_dia as $p): ?>
          <tr>
            <td><a href="index.php?page=pedido_detalle&id=<?= $p['id'] ?>" class="font-bold">#<?= $p['id'] ?></a></td>
            <td class="text-sm text-muted"><?= date('H:i', strtotime($p['confirmado_at'])) ?></td>
            <td class="text-sm"><?= htmlspecialchars($p['vendedor']) ?></td>
            <td class="font-bold">$ <?= number_format($p['total'], 2, ',', '.') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- Gastos del día -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">Gastos del día</span>
      <?php if (!empty($gastos_lista)): ?>
      <span class="badge badge-gray"><?= count($gastos_lista) ?> gasto<?= count($gastos_lista) !== 1 ? 's' : '' ?></span>
      <?php endif; ?>
    </div>
    <div class="card-body" style="padding-bottom:8px">
      <!-- Formulario nuevo gasto (con soporte recurrente) -->
      <form method="POST" action="index.php?page=gasto_guardar" id="form-gasto" style="margin-bottom:16px">
        <input type="hidden" name="fecha" value="<?= htmlspecialchars($fecha) ?>">
        <div class="flex gap-2 flex-wrap" style="margin-bottom:8px">
          <input type="text" name="descripcion" class="form-control" placeholder="Ej: Fletes, insumos…" required style="flex:1;min-width:140px">
          <input type="number" name="monto" class="form-control" placeholder="$ Monto" step="0.01" min="0.01" required style="width:110px">
          <button type="submit" class="btn btn-primary btn-sm">＋ Registrar</button>
        </div>
        <div style="margin-bottom:8px">
          <label class="flex items-center gap-2" style="cursor:pointer;font-size:.85rem">
            <input type="checkbox" name="es_recurrente" value="1" id="chk-recurrente">
            Gasto recurrente (se repetirá cada mes)
          </label>
        </div>
        <div id="recurrente-fields" style="display:none;padding:10px;background:var(--gray-50);border-radius:8px;margin-bottom:8px">
          <div class="flex gap-3 flex-wrap items-center" style="font-size:.85rem">
            <label class="flex items-center gap-1">
              Frecuencia:
              <select name="frecuencia" class="form-control" style="width:auto;padding:4px 8px">
                <option value="mensual">Mensual</option>
                <option value="semanal">Semanal</option>
                <option value="diario">Diario</option>
              </select>
            </label>
            <label class="flex items-center gap-1" id="wrap-dia">
              Día del mes:
              <input type="number" name="dia_cobro" class="form-control" value="<?= (int)date('j') ?>"
                     min="1" max="31" style="width:70px;padding:4px 8px">
            </label>
          </div>
        </div>
      </form>

      <?php if (!empty($gastos_recurrentes_pendientes)): ?>
      <!-- Alerta: gastos recurrentes pendientes de aplicar hoy -->
      <div class="alert alert-ok" style="margin-bottom:12px;font-size:.85rem">
        <strong>⏰ Gastos recurrentes pendientes hoy:</strong>
        <form method="POST" action="index.php?page=gasto_aplicar_recurrentes" style="margin-top:6px">
          <?php foreach ($gastos_recurrentes_pendientes as $gr): ?>
            <label class="flex items-center gap-2" style="cursor:pointer;margin-bottom:4px">
              <input type="checkbox" name="ids[]" value="<?= $gr['id'] ?>" checked>
              <?= htmlspecialchars($gr['descripcion']) ?> — $ <?= number_format($gr['monto'], 2, ',', '.') ?>
            </label>
          <?php endforeach; ?>
          <button type="submit" class="btn btn-sm btn-primary" style="margin-top:6px">Aplicar seleccionados</button>
        </form>
      </div>
      <?php endif; ?>

      <?php if (!empty($gastos_lista)): ?>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Descripción</th><th>Monto</th></tr></thead>
          <tbody>
            <?php foreach ($gastos_lista as $g): ?>
            <tr>
              <td><?= htmlspecialchars($g['descripcion']) ?></td>
              <td class="font-bold text-danger">$ <?= number_format($g['monto'], 2, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
        <p class="text-sm text-muted text-center" style="padding:16px 0">Sin gastos registrados.</p>
      <?php endif; ?>
    </div>
  </div>

</div>

<?php require VIEW_PATH . '/layout/footer.php'; ?>
<script>
(function() {
  var chk = document.getElementById('chk-recurrente');
  var fields = document.getElementById('recurrente-fields');
  var selFrecuencia = document.querySelector('[name="frecuencia"]');
  var wrapDia = document.getElementById('wrap-dia');
  if (!chk) return;
  chk.addEventListener('change', function() {
    fields.style.display = this.checked ? 'block' : 'none';
  });
  if (selFrecuencia) {
    selFrecuencia.addEventListener('change', function() {
      wrapDia.style.display = this.value === 'mensual' ? 'flex' : 'none';
    });
  }
})();
</script>
