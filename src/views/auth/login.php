<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ingresar — Textum</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/app.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&family=Josefin+Sans:wght@700&display=swap" rel="stylesheet">
</head>
<body>
<div class="login-bg">
  <div class="login-card">
    <div class="login-logo">
      <h1 style="font-family:'Josefin Sans',sans-serif;letter-spacing:4px;text-transform:uppercase">Text<span>um</span></h1>
      <p>Sistema de Gestión Textil</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="index.php?page=login">
      <div class="form-group">
        <label class="form-label" for="email">Email</label>
        <input type="email" id="email" name="email" class="form-control"
               placeholder="usuario@empresa.com" required autofocus>
      </div>
      <div class="form-group">
        <label class="form-label" for="password">Contraseña</label>
        <input type="password" id="password" name="password" class="form-control"
               placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn btn-primary w-full btn-lg" style="margin-top:8px">
        Ingresar
      </button>
    </form>


  </div>
</div>
</body>
</html>
