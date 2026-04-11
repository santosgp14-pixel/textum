<?php
$pageTitle   = 'Importar telas por CSV';
$currentPage = 'stock';
require VIEW_PATH . '/layout/header.php';
?>

<div style="max-width:700px">

  <!-- Instrucciones y template -->
  <div class="card mb-4">
    <div class="card-header">
      <span class="card-title">📥 Importación masiva por CSV</span>
    </div>
    <div class="card-body">
      <p class="text-sm text-muted mb-4">
        Importá varias telas y variantes en un solo archivo. Cada fila representa una <strong>variante</strong>
        — si varios filas tienen el mismo nombre de tela se agrupan bajo la misma tela.
      </p>

      <h4 style="font-size:.9rem;font-weight:700;margin-bottom:8px">Formato del CSV</h4>
      <div class="table-wrap" style="margin-bottom:16px">
        <table style="font-size:.8rem">
          <thead>
            <tr>
              <th>Columna</th><th>Requerido</th><th>Descripción</th>
            </tr>
          </thead>
          <tbody>
            <tr><td><code>nombre_tela</code></td><td>✓</td><td>Nombre de la tela</td></tr>
            <tr><td><code>categoria</code></td><td></td><td>Nombre de la categoría (se crea si no existe)</td></tr>
            <tr><td><code>descripcion_variante</code></td><td></td><td>Descripción de la variante (ej: "Azul 140cm")</td></tr>
            <tr><td><code>precio</code></td><td>✓</td><td>Precio de venta (número)</td></tr>
            <tr><td><code>unidad</code></td><td></td><td>metro / kilo / rollo (default: metro)</td></tr>
            <tr><td><code>stock</code></td><td></td><td>Stock inicial de la variante (default: 0)</td></tr>
            <tr><td><code>costo</code></td><td></td><td>Costo de la variante (default: 0)</td></tr>
          </tbody>
        </table>
      </div>

      <a href="index.php?page=stock_csv_template" class="btn btn-sm btn-outline" download>
        ⬇ Descargar template CSV
      </a>
    </div>
  </div>

  <!-- Formulario de upload -->
  <div class="card mb-4">
    <div class="card-header">
      <span class="card-title">Elegir archivo</span>
    </div>
    <div class="card-body">
      <form method="POST" action="index.php?page=stock_importar_csv" enctype="multipart/form-data">
        <div class="form-group">
          <label class="form-label" for="csv_file">Archivo CSV</label>
          <input type="file" id="csv_file" name="csv_file" class="form-control"
                 accept=".csv,text/csv" required>
          <div class="form-hint">Máximo 2 MB. Codificación UTF-8.</div>
        </div>
        <div class="form-group">
          <label class="flex items-center gap-2" style="cursor:pointer">
            <input type="checkbox" name="update_existing" value="1">
            Actualizar precio/costo de variantes existentes
          </label>
        </div>
        <button type="submit" class="btn btn-primary">Importar</button>
        <a href="index.php?page=stock" class="btn btn-outline" style="margin-left:8px">Cancelar</a>
      </form>
    </div>
  </div>

  <!-- Resultado de importación -->
  <?php if (!empty($resultado)): ?>
  <div class="card">
    <div class="card-header">
      <span class="card-title">Resultado de la importación</span>
      <span class="badge badge-<?= $resultado['errores'] > 0 ? 'anulado' : 'confirmado' ?>">
        <?= $resultado['ok'] ?> OK / <?= $resultado['errores'] ?> errores / <?= $resultado['duplicados'] ?> duplicados
      </span>
    </div>
    <div class="table-wrap">
      <table style="font-size:.85rem">
        <thead>
          <tr><th>Fila</th><th>Tela</th><th>Variante</th><th>Estado</th></tr>
        </thead>
        <tbody>
          <?php foreach ($resultado['filas'] as $fila): ?>
          <tr>
            <td><?= (int)$fila['row'] ?></td>
            <td><?= htmlspecialchars($fila['tela']) ?></td>
            <td><?= htmlspecialchars($fila['variante']) ?></td>
            <td>
              <?php if ($fila['status'] === 'ok'): ?>
                <span class="badge badge-confirmado">Creado</span>
              <?php elseif ($fila['status'] === 'updated'): ?>
                <span class="badge badge-blue">Actualizado</span>
              <?php elseif ($fila['status'] === 'duplicado'): ?>
                <span class="badge badge-gray">Duplicado</span>
              <?php else: ?>
                <span class="badge badge-anulado">Error: <?= htmlspecialchars($fila['msg'] ?? '') ?></span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

</div>

<?php require VIEW_PATH . '/layout/footer.php'; ?>
