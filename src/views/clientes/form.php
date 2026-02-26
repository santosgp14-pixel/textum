<?php
$esEdicion   = !empty($cliente);
$pageTitle   = $esEdicion ? 'Editar Cliente' : 'Nuevo Cliente';
$currentPage = 'clientes';
require VIEW_PATH . '/layout/header.php';
?>

<div class="card" style="max-width:540px">
  <div class="card-header">
    <span class="card-title"><?= $pageTitle ?></span>
    <a href="index.php?page=clientes" class="btn btn-sm btn-outline">← Volver</a>
  </div>
  <div class="card-body">
    <form method="POST" action="index.php?page=cliente_guardar">
      <?php if ($esEdicion): ?>
        <input type="hidden" name="id" value="<?= $cliente['id'] ?>">
      <?php endif; ?>

      <div class="form-group">
        <label class="form-label" for="nombre">Nombre *</label>
        <input type="text" id="nombre" name="nombre" class="form-control"
               value="<?= htmlspecialchars($cliente['nombre'] ?? '') ?>"
               required autofocus placeholder="Nombre completo o razón social">
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div class="form-group">
          <label class="form-label" for="telefono">Teléfono</label>
          <input type="tel" id="telefono" name="telefono" class="form-control"
                 value="<?= htmlspecialchars($cliente['telefono'] ?? '') ?>"
                 placeholder="Ej: 11-1234-5678">
        </div>
        <div class="form-group">
          <label class="form-label" for="email">Email</label>
          <input type="email" id="email" name="email" class="form-control"
                 value="<?= htmlspecialchars($cliente['email'] ?? '') ?>"
                 placeholder="cliente@ejemplo.com">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="notas">Notas internas</label>
        <textarea id="notas" name="notas" class="form-control" rows="3"
                  placeholder="Preferencias, condiciones especiales, referencias..."><?= htmlspecialchars($cliente['notas'] ?? '') ?></textarea>
      </div>

      <div class="flex gap-3 mt-4">
        <button type="submit" class="btn btn-primary">
          <?= $esEdicion ? 'Guardar cambios' : 'Crear cliente' ?>
        </button>
        <a href="index.php?page=clientes" class="btn btn-outline">Cancelar</a>
      </div>
    </form>
  </div>
</div>

<?php require VIEW_PATH . '/layout/footer.php'; ?>
