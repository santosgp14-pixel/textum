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
    <label class="form-label" style="margin:0">Fecha:</label>
    <input type="date" name="fecha" value="<?= htmlspecialchars($fecha) ?>" class="form-control" style="width:auto">
    <button type="submit" class="btn btn-primary btn-sm">Ver</button>
  </form>
  <a href="index.php?page=balance&fecha=<?= date('Y-m-d') ?>" class="btn btn-sm btn-outline">Hoy</a>
</div>

<!-- Tarjetas resumen -->
<div class="balance-grid">
  <div class="balance-card ingresos">
    <div class="bc-label">Ingresos ventas</div>
    <div class="bc-value">$ <?= number_format($ingresos, 2, ',', '.') ?></div>
  </div>
  <div class="balance-card anulados">
    <div class="bc-label">Anulaciones</div>
    <div class="bc-value">$ <?= number_format($anulaciones, 2, ',', '.') ?></div>
  </div>
  <div class="balance-card gastos">
    <div class="bc-label">Gastos</div>
    <div class="bc-value">$ <?= number_format($gastos, 2, ',', '.') ?></div>
  </div>
  <div class="balance-card <?= $netoClass ?>">
    <div class="bc-label">RESULTADO NETO</div>
    <div class="bc-value">$ <?= number_format($neto, 2, ',', '.') ?></div>
  </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:20px">

  <!-- Pedidos del día -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">Ventas del día</span>
      <span class="badge badge-confirmado"><?= count($pedidos_dia) ?> pedidos</span>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>#</th><th>Hora</th><th>Vendedor</th><th>Total</th></tr></thead>
        <tbody>
          <?php foreach ($pedidos_dia as $p): ?>
          <tr>
            <td><a href="index.php?page=pedido_detalle&id=<?= $p['id'] ?>" class="font-bold">#<?= $p['id'] ?></a></td>
            <td class="text-sm"><?= date('H:i', strtotime($p['confirmado_at'])) ?></td>
            <td class="text-sm"><?= htmlspecialchars($p['vendedor']) ?></td>
            <td class="font-bold">$ <?= number_format($p['total'], 2, ',', '.') ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($pedidos_dia)): ?>
          <tr><td colspan="4" class="text-center text-muted" style="padding:20px">Sin ventas este día.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Gastos del día -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">Gastos del día</span>
    </div>
    <div class="card-body" style="padding-bottom:8px">
      <!-- Formulario nuevo gasto -->
      <form method="POST" action="index.php?page=gasto_guardar" class="flex gap-2 flex-wrap mb-4" style="margin-bottom:16px">
        <input type="hidden" name="fecha" value="<?= htmlspecialchars($fecha) ?>">
        <input type="text" name="descripcion" class="form-control" placeholder="Descripción del gasto" required style="flex:1;min-width:150px">
        <input type="number" name="monto" class="form-control" placeholder="$ Monto" step="0.01" min="0.01" required style="width:120px">
        <button type="submit" class="btn btn-primary btn-sm">Registrar</button>
      </form>

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
        <p class="text-sm text-muted text-center" style="padding:12px 0">Sin gastos registrados.</p>
      <?php endif; ?>
    </div>
  </div>

</div>

<?php require VIEW_PATH . '/layout/footer.php'; ?>
