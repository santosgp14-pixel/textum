<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ingresar — Textum</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/app.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&family=Josefin+Sans:wght@700&display=swap" rel="stylesheet">
  <style>
    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      20%       { transform: translateX(-6px); }
      40%       { transform: translateX(6px); }
      60%       { transform: translateX(-4px); }
      80%       { transform: translateX(4px); }
    }
    .login-shake { animation: shake .4s ease; }
    .input-error  { border-color: var(--red-500) !important; }
  </style>
</head>
<body>
<div class="login-bg">
  <div class="login-card">
    <div class="login-logo">
      <h1 style="font-family:'Josefin Sans',sans-serif;letter-spacing:4px;text-transform:uppercase">Text<span>um</span></h1>
      <p>Sistema de Gestión Textil</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-error login-shake"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="index.php?page=login">
      <div class="form-group">
        <label class="form-label" for="email">Email</label>
        <input type="email" id="email" name="email"
               class="form-control<?= $error ? ' input-error' : '' ?>"
               placeholder="usuario@empresa.com" required autofocus
               value="<?= htmlspecialchars($lastEmail ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label" for="password">Contraseña</label>
        <input type="password" id="password" name="password"
               class="form-control<?= $error ? ' input-error' : '' ?>"
               placeholder="••••••••" required>
      </div>
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
        <input type="checkbox" id="remember_me" name="remember_me" value="1"
               style="width:16px;height:16px;cursor:pointer;accent-color:var(--blue-600)">
        <label for="remember_me" style="font-size:.875rem;color:var(--gray-600);cursor:pointer;user-select:none">
          Mantener sesión iniciada
        </label>
      </div>
      <button type="submit" class="btn btn-primary w-full btn-lg" style="margin-top:8px">
        Ingresar
      </button>
    </form>


  </div>
</div>
</body>
</html>
