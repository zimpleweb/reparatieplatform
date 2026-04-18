<?php
session_start();

// ── HTTP Security Headers ────────────────────────────────────
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: default-src 'self' https://fonts.googleapis.com https://fonts.gstatic.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src https://fonts.gstatic.com;");

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (isAdmin()) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

// ── Rate limiting (max 5 pogingen per IP, 15 min blokkering) ─
$ip          = md5($_SERVER['REMOTE_ADDR'] ?? 'unknown');
$atKey       = 'login_attempts_' . $ip;
$lockKey     = 'login_locked_until_' . $ip;
$maxPogingen = 5;
$lockSecs    = 900; // 15 minuten

$lockedUntil = $_SESSION[$lockKey] ?? 0;
$isLocked    = time() < $lockedUntil;
$pogingen    = $_SESSION[$atKey] ?? 0;
$remainSecs  = max(0, $lockedUntil - time());

$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── CSRF check ──────────────────────────────────────────
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        $errorMsg = 'Ongeldig verzoek. Ververs de pagina en probeer opnieuw.';

    // ── Lockout check ───────────────────────────────────────
    } elseif ($isLocked) {
        $min = ceil($remainSecs / 60);
        $errorMsg = "Te veel mislukte pogingen. Probeer over {$min} minuut" . ($min === 1 ? '' : 'en') . " opnieuw.";

    } else {
        // ── Timing-bescherming: kunstmatige vertraging ──────
        usleep(200000); // 200ms — maakt timing-aanvallen moeilijker

        $row = db()->prepare('SELECT * FROM admins WHERE username = ? LIMIT 1');
        $row->execute([trim($_POST['username'] ?? '')]);
        $admin = $row->fetch();

        if ($admin && password_verify($_POST['password'] ?? '', $admin['password'])) {
            // ── Succes: reset tellers, vernieuw sessie ───────
            unset($_SESSION[$atKey], $_SESSION[$lockKey]);
            session_regenerate_id(true);
            $_SESSION['admin'] = true;
            header('Location: ' . BASE_URL . '/admin/dashboard.php');
            exit;

        } else {
            // ── Mislukt: teller ophogen ──────────────────────
            $pogingen++;
            $_SESSION[$atKey] = $pogingen;

            if ($pogingen >= $maxPogingen) {
                $_SESSION[$lockKey] = time() + $lockSecs;
                unset($_SESSION[$atKey]);
                $isLocked   = true;
                $remainSecs = $lockSecs;
                $errorMsg   = "Te veel mislukte pogingen. Probeer over 15 minuten opnieuw.";
            } else {
                $over     = $maxPogingen - $pogingen;
                $errorMsg = "Gebruikersnaam of wachtwoord onjuist. Nog {$over} poging" . ($over === 1 ? '' : 'en') . " voor blokkering.";
            }
        }
    }
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
  <style>
    body {
      background: var(--surface, #f5f4f1);
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: 1.5rem;
    }

    .login-wrap {
      width: 100%;
      max-width: 420px;
    }

    .login-card {
      background: white;
      border: 1.5px solid var(--border, #e5e4e0);
      border-radius: 20px;
      padding: 2.75rem 2.5rem 2.25rem;
      box-shadow: 0 8px 32px rgba(0,0,0,.07), 0 2px 8px rgba(0,0,0,.04);
    }

    .login-icon {
      width: 52px; height: 52px;
      background: var(--accent-light, #e8f5f2);
      border: 1.5px solid #b2ddd4;
      border-radius: 14px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.4rem;
      margin-bottom: 1.25rem;
    }

    .login-brand {
      font-family: 'Epilogue', sans-serif;
      font-weight: 800;
      color: var(--accent, #287864);
      text-transform: uppercase;
      letter-spacing: .06em;
      font-size: .72rem;
      margin-bottom: .35rem;
    }

    .login-card h1 {
      font-family: 'Epilogue', sans-serif;
      font-size: 1.45rem;
      font-weight: 800;
      color: var(--ink, #1a1d26);
      margin-bottom: .35rem;
      letter-spacing: -.02em;
    }

    .login-lead {
      font-size: .875rem;
      color: var(--muted, #6b7280);
      margin-bottom: 1.75rem;
      line-height: 1.55;
    }

    .portal-alert {
      display: flex;
      align-items: flex-start;
      gap: .6rem;
      padding: .85rem 1rem;
      border-radius: 10px;
      font-size: .875rem;
      margin-bottom: 1.25rem;
      line-height: 1.5;
    }
    .alert-error {
      background: #fef2f2;
      border: 1px solid #fecaca;
      color: #991b1b;
    }
    .alert-warning {
      background: #fffbeb;
      border: 1px solid #fcd34d;
      color: #92400e;
    }

    .portal-field { margin-bottom: 1.1rem; }
    .portal-field label {
      display: block;
      font-size: .72rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .07em;
      color: var(--muted, #6b7280);
      margin-bottom: .4rem;
    }
    .portal-field input {
      width: 100%;
      padding: .75rem 1rem;
      border: 1.5px solid var(--border, #e5e4e0);
      border-radius: 12px;
      font-size: .9rem;
      font-family: 'Inter', sans-serif;
      color: var(--ink, #1a1d26);
      background: white;
      outline: none;
      transition: border-color .2s, box-shadow .2s;
    }
    .portal-field input:focus {
      border-color: var(--accent, #287864);
      box-shadow: 0 0 0 3px rgba(40,120,100,.1);
    }
    .portal-field input:disabled {
      background: #f9fafb;
      color: #9ca3af;
      cursor: not-allowed;
    }

    .login-btn {
      width: 100%;
      padding: .85rem 1rem;
      background: var(--ink, #1a1d26);
      color: white;
      border: none;
      border-radius: 12px;
      font-family: 'Inter', sans-serif;
      font-size: .95rem;
      font-weight: 700;
      cursor: pointer;
      transition: background .2s, transform .15s;
      margin-top: .5rem;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: .5rem;
    }
    .login-btn:hover:not(:disabled)  { background: var(--accent, #287864); transform: translateY(-1px); }
    .login-btn:active:not(:disabled) { transform: translateY(0); }
    .login-btn:disabled {
      background: #d1d5db;
      cursor: not-allowed;
      transform: none;
    }

    .attempts-bar {
      display: flex;
      gap: .3rem;
      margin-bottom: 1rem;
    }
    .attempt-dot {
      width: 8px; height: 8px;
      border-radius: 50%;
      background: #e5e7eb;
      transition: background .3s;
    }
    .attempt-dot.used { background: #f87171; }

    .login-footer {
      text-align: center;
      margin-top: 1.5rem;
      font-size: .78rem;
      color: var(--muted, #9ca3af);
    }
  </style>
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <div class="login-icon"><?= $isLocked ? '&#128274;' : '&#128272;' ?></div>
    <div class="login-brand">ReparatiePlatform</div>
    <h1><?= $isLocked ? 'Toegang geblokkeerd' : 'Admin inloggen' ?></h1>
    <p class="login-lead">
      <?php if ($isLocked):
        $min = ceil($remainSecs / 60); ?>
        Te veel mislukte pogingen. Probeer over
        <strong><?= $min ?> minuut<?= $min === 1 ? '' : 'en' ?></strong> opnieuw.
      <?php else: ?>
        Voer uw inloggegevens in om het beheerpanel te openen.
      <?php endif; ?>
    </p>

    <?php if ($errorMsg): ?>
    <div class="portal-alert <?= $isLocked ? 'alert-warning' : 'alert-error' ?>">
      <span><?= $isLocked ? '&#128274;' : '&#9888;' ?></span>
      <span><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <?php endif; ?>

    <?php
      $huidigPogingen = $_SESSION[$atKey] ?? 0;
      if ($huidigPogingen > 0 && !$isLocked):
    ?>
    <div class="attempts-bar" title="<?= $huidigPogingen ?> van <?= $maxPogingen ?> pogingen gebruikt">
      <?php for ($i = 0; $i < $maxPogingen; $i++): ?>
      <div class="attempt-dot <?= $i < $huidigPogingen ? 'used' : '' ?>"></div>
      <?php endfor; ?>
    </div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
      <input type="hidden" name="csrf" value="<?= csrf() ?>">

      <div class="portal-field">
        <label for="username">Gebruikersnaam</label>
        <input type="text" id="username" name="username"
               placeholder="Uw gebruikersnaam"
               value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
               required
               <?= $isLocked ? 'disabled' : 'autofocus' ?>
               autocomplete="off">
      </div>
      <div class="portal-field">
        <label for="password">Wachtwoord</label>
        <input type="password" id="password" name="password"
               placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
               required
               <?= $isLocked ? 'disabled' : '' ?>
               autocomplete="off">
      </div>
      <button type="submit" class="login-btn" <?= $isLocked ? 'disabled' : '' ?>>
        <?= $isLocked ? '&#128274; Geblokkeerd' : 'Inloggen &rarr;' ?>
      </button>
    </form>

    <p class="login-footer">Reparatieplatform.nl &mdash; Beheerpanel</p>
  </div>
</div>
</body>
</html>