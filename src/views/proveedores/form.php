<?php
$pageTitle   = $proveedor ? 'Editar proveedor' : 'Nuevo proveedor';
$currentPage = 'proveedores';
require VIEW_PATH . '/layout/header.php';
$isEdit = !empty($proveedor);
?>

<div style="max-width:560px">
  <div class="card">
    <div class="card-header">
      <span class="card-title"><?= $isEdit ? 'Editar proveedor' : 'Nuevo proveedor' ?></span>
    </div>
    <div class="card-body">
      <form method="POST" action="index.php?page=proveedor_guardar">
        <?php if ($isEdit): ?>
          <input type="hidden" name="id" value="<?= $proveedor['id'] ?>">
        <?php endif; ?>

        <div class="form-group">
          <label class="form-label" for="nombre">Nombre *</label>
          <input type="text" id="nombre" name="nombre" class="form-control" required
                 value="<?= htmlspecialchars($proveedor['nombre'] ?? '') ?>"
                 placeholder="Ej: Hilaturas Ejemplo S.A.">
        </div>

        <div class="form-group">
          <label class="form-label" for="cuit">CUIT</label>
          <input type="text" id="cuit" name="cuit" class="form-control"
                 value="<?= htmlspecialchars($proveedor['cuit'] ?? '') ?>"
                 placeholder="20-12345678-9">
        </div>

        <div class="form-group">
          <label class="form-label" for="telefono">Teléfono</label>
          <input type="text" id="telefono" name="telefono" class="form-control"
                 value="<?= htmlspecialchars($proveedor['telefono'] ?? '') ?>"
                 placeholder="Ej: 11-4567-8990">
        </div>

        <div class="form-group">
          <label class="form-label" for="email">Email</label>
          <input type="email" id="email" name="email" class="form-control"
                 value="<?= htmlspecialchars($proveedor['email'] ?? '') ?>"
                 placeholder="ventas@proveedor.com">
        </div>

        <div class="form-group">
          <label class="form-label" for="notas">Notas</label>
          <textarea id="notas" name="notas" class="form-control" rows="3"
                    placeholder="Condiciones de pago, contacto, etc."><?= htmlspecialchars($proveedor['notas'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
          <label class="flex items-center gap-2" style="cursor:pointer">
            <input type="checkbox" name="activo" value="1"
                   <?= (!$isEdit || $proveedor['activo']) ? 'checked' : '' ?>>
            Proveedor activo
          </label>
        </div>

        <div class="flex gap-3 mt-4">
          <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Actualizar' : 'Crear proveedor' ?></button>
          <a href="index.php?page=proveedores" class="btn btn-outline">Cancelar</a>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require VIEW_PATH . '/layout/footer.php'; ?>
