<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (isAdmin()) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

$error = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $row = db()->prepare('SELECT * FROM admins WHERE username = ? LIMIT 1');
    $row->execute([trim($_POST['username'] ?? '')]);
    $admin = $row->fetch();
    if ($admin && password_verify($_POST['password'] ?? '', $admin['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin'] = true;
        header('Location: ' . BASE_URL . '/admin/dashboard.php');
        exit;
    }
    $error = true;
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin inloggen &ndash; Reparatieplatform.nl</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Epilogue:wght@800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/base.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/components.css">
  <meta name="robots" content="noindex,nofollow">
</head>
<body style="background:#f5f4f1;display:flex;align-items:center;justify-content:center;min-height:100vh;">
<div style="background:white;border:1.5px solid #e5e4e0;border-radius:20px;padding:3rem 2.5rem;width:100%;max-width:400px;box-shadow:0 8px 32px rgba(0,0,0,.08);">
  <p style="font-family:'Epilogue',sans-serif;font-size:1.1rem;font-weight:800;margin-bottom:.25rem;">ReparatiePlatform</p>
  <h1 style="font-family:'Epilogue',sans-serif;font-size:1.5rem;font-weight:800;margin-bottom:.5rem;">Admin inloggen</h1>
  <p style="font-size:.875rem;color:#6b7280;margin-bottom:2rem;">Beheerpanel</p>
  <?php if ($error): ?>
  <div class="alert alert-error">Gebruikersnaam of wachtwoord onjuist.</div>
  <?php endif; ?>
  <form method="POST">
    <div style="margin-bottom:1rem;">
      <label style="display:block;font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#6b7280;margin-bottom:.4rem;">Gebruikersnaam</label>
      <input type="text" name="username" required autofocus
             style="width:100%;padding:.75rem 1rem;border:1.5px solid #e5e4e0;border-radius:12px;font-size:.9rem;font-family:'Inter',sans-serif;outline:none;transition:border-color .2s;"
             onfocus="this.style.borderColor='#287864'" onblur="this.style.borderColor='#e5e4e0'">
    </div>
    <div style="margin-bottom:1.5rem;">
      <label style="display:block;font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#6b7280;margin-bottom:.4rem;">Wachtwoord</label>
      <input type="password" name="password" required
             style="width:100%;padding:.75rem 1rem;border:1.5px solid #e5e4e0;border-radius:12px;font-size:.9rem;font-family:'Inter',sans-serif;outline:none;transition:border-color .2s;"
             onfocus="this.style.borderColor='#287864'" onblur="this.style.borderColor='#e5e4e0'">
    </div>
    <button type="submit" class="submit-btn">Inloggen &rarr;</button>
  </form>
</div>
</body>
</html>