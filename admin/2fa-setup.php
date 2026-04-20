<?php
session_start();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/totp.php';
requireAdmin();

$adminId = (int) ($_SESSION['admin_id'] ?? 0);
if (!$adminId) {
    header('Location: ' . BASE_URL . '/admin/admins.php');
    exit;
}

$admin = db()->prepare('SELECT * FROM admins WHERE id = ? LIMIT 1');
$admin->execute([$adminId]);
$admin = $admin->fetch();

if (!$admin) {
    header('Location: ' . BASE_URL . '/admin/admins.php');
    exit;
}

$successMsg = '';
$errorMsg   = '';
$fase       = 'scan'; // scan | verify

// Genereer tijdelijk secret in sessie als het er nog niet is
if (empty($_SESSION['totp_setup_secret'])) {
    $_SESSION['totp_setup_secret'] = totpGenerateSecret();
}
$tempSecret = $_SESSION['totp_setup_secret'];
$otpUri     = totpUri($tempSecret, $admin['username']);
$qrUrl      = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . rawurlencode($otpUri);

// ── Verificatie en activering ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'verify_2fa') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        $errorMsg = 'Ongeldig verzoek.';
    } else {
        $code = preg_replace('/\s+/', '', $_POST['totp_code'] ?? '');
        if (totpVerify($tempSecret, $code)) {
            try {
                db()->prepare('UPDATE admins SET totp_secret = ?, totp_enabled = 1 WHERE id = ?')
                     ->execute([$tempSecret, $adminId]);
                unset($_SESSION['totp_setup_secret']);
                $successMsg = '2FA is succesvol ingeschakeld op uw account.';
                $fase = 'done';
            } catch (\PDOException $e) {
                $errorMsg = 'Database-kolom ontbreekt. Voer eerst de migratie uit (zie admins.php).';
            }
        } else {
            $errorMsg = 'Ongeldige code. Controleer uw app en probeer opnieuw.';
            $fase = 'scan';
        }
    }
}

$adminActivePage = 'admins';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>2FA instellen &ndash; Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/base.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/components.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
  <meta name="robots" content="noindex,nofollow">
  <style>
    .setup-card {
      background: white; border: 1.5px solid var(--border); border-radius: 20px;
      padding: 2.5rem; max-width: 540px; margin: 0 auto;
      box-shadow: 0 8px 32px rgba(0,0,0,.07);
    }
    .setup-step {
      display: flex; align-items: flex-start; gap: 1rem;
      margin-bottom: 1.75rem; padding-bottom: 1.75rem;
      border-bottom: 1px solid var(--border);
    }
    .setup-step:last-of-type { border-bottom: none; margin-bottom: 0; }
    .step-nr {
      width: 32px; height: 32px; border-radius: 50%;
      background: var(--ink); color: white;
      display: flex; align-items: center; justify-content: center;
      font-size: .8rem; font-weight: 700; flex-shrink: 0; margin-top: .1rem;
    }
    .step-body h3 { font-size: 1rem; font-weight: 700; margin-bottom: .4rem; }
    .step-body p  { font-size: .875rem; color: var(--muted); line-height: 1.65; margin: 0 0 .75rem; }
    .secret-box {
      display: flex; align-items: center; gap: .75rem;
      background: var(--surface); border: 1.5px solid var(--border);
      border-radius: 10px; padding: .75rem 1rem;
      font-family: monospace; font-size: .95rem; font-weight: 700;
      letter-spacing: .1em; color: var(--ink); flex-wrap: wrap;
    }
    .copy-btn {
      background: var(--ink); color: white; border: none; border-radius: 6px;
      padding: .3rem .7rem; font-size: .75rem; font-weight: 600; cursor: pointer;
      transition: background .2s; flex-shrink: 0; margin-left: auto;
    }
    .copy-btn:hover { background: var(--accent); }
    .qr-wrap {
      display: flex; flex-direction: column; align-items: center; gap: .75rem;
      padding: 1.25rem; background: var(--surface); border-radius: 12px; margin-top: .75rem;
    }
    .qr-wrap img { border-radius: 8px; border: 4px solid white; box-shadow: 0 4px 16px rgba(0,0,0,.1); }
    .totp-input {
      font-size: 1.6rem !important; letter-spacing: .3em; text-align: center;
      font-weight: 700; width: 100%; padding: .75rem 1rem;
      border: 1.5px solid var(--border); border-radius: 12px;
      font-family: 'Inter', sans-serif; color: var(--ink);
      outline: none; transition: border-color .2s, box-shadow .2s;
    }
    .totp-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(40,120,100,.1); }
    .success-block { text-align: center; padding: 2rem; }
    .success-block .success-icon { font-size: 3rem; margin-bottom: 1rem; }
    .success-block h2 { font-size: 1.3rem; font-weight: 800; margin-bottom: .5rem; }
    .success-block p  { font-size: .9rem; color: var(--muted); margin-bottom: 1.5rem; }
  </style>
</head>
<body>

<?php require_once __DIR__ . '/includes/admin-header.php'; ?>

<div class="adm-page">
  <h1>&#128272; Tweestapsverificatie instellen</h1>

  <div class="setup-card">

  <?php if ($fase === 'done'): ?>
    <div class="success-block">
      <div class="success-icon">&#128274;</div>
      <h2>2FA ingeschakeld!</h2>
      <p>Tweestapsverificatie is succesvol geconfigureerd voor uw account. Voortaan heeft u bij elke login uw authenticator-app nodig.</p>
      <a href="<?= BASE_URL ?>/admin/admins.php" class="btn-primary">Terug naar accounts</a>
    </div>

  <?php else: ?>

    <?php if ($errorMsg): ?>
      <div class="alert alert-error" style="margin-bottom:1.5rem;">&#9888; <?= h($errorMsg) ?></div>
    <?php endif; ?>

    <!-- Stap 1: App installeren -->
    <div class="setup-step">
      <div class="step-nr">1</div>
      <div class="step-body">
        <h3>Installeer een authenticator-app</h3>
        <p>Download <strong>Google Authenticator</strong>, <strong>Authy</strong> of een andere TOTP-compatibele app op uw telefoon als u dat nog niet heeft gedaan.</p>
      </div>
    </div>

    <!-- Stap 2: QR-code scannen -->
    <div class="setup-step">
      <div class="step-nr">2</div>
      <div class="step-body">
        <h3>Scan de QR-code</h3>
        <p>Open uw authenticator-app, kies <em>"Account toevoegen"</em> en scan onderstaande QR-code. Werkt het scannen niet? Voer de sleutel handmatig in.</p>
        <div class="qr-wrap">
          <img src="<?= h($qrUrl) ?>" alt="TOTP QR-code" width="200" height="200">
          <p style="font-size:.75rem;color:var(--muted);margin:0;">Scan met uw authenticator-app</p>
        </div>
        <p style="margin-top:.75rem;font-size:.82rem;color:var(--muted);">Of voer deze sleutel handmatig in:</p>
        <div class="secret-box">
          <?= h(wordwrap($tempSecret, 4, ' ', true)) ?>
          <button type="button" class="copy-btn" onclick="copySecret()">Kopiëren</button>
        </div>
      </div>
    </div>

    <!-- Stap 3: Code verifiëren -->
    <div class="setup-step">
      <div class="step-nr">3</div>
      <div class="step-body">
        <h3>Verificeer de instelling</h3>
        <p>Voer de 6-cijferige code uit uw app in om de instelling te bevestigen.</p>
        <form method="POST">
          <input type="hidden" name="csrf"   value="<?= csrf() ?>">
          <input type="hidden" name="action" value="verify_2fa">
          <input type="text" name="totp_code" class="totp-input"
                 placeholder="000 000" maxlength="7"
                 autocomplete="one-time-code" inputmode="numeric"
                 oninput="this.value=this.value.replace(/[^0-9 ]/g,'').trimStart()" autofocus>
          <button type="submit" class="btn-primary" style="margin-top:1rem;width:100%;justify-content:center;">
            &#128274; Activeren &rarr;
          </button>
        </form>
      </div>
    </div>

  <?php endif; ?>
  </div><!-- /.setup-card -->
</div><!-- /.adm-page -->

<script>
function copySecret() {
  const raw = <?= json_encode($tempSecret) ?>;
  navigator.clipboard.writeText(raw).then(() => {
    const btn = document.querySelector('.copy-btn');
    btn.textContent = 'Gekopieerd ✓';
    setTimeout(() => btn.textContent = 'Kopiëren', 2000);
  });
}
</script>
</body>
</html>