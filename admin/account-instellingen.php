<?php
session_start();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/totp.php';
requireAdmin();

$adminId = (int)($_SESSION['admin_id'] ?? 0);
if (!$adminId && !empty($_SESSION['admin_username'])) {
    $row = db()->prepare('SELECT id FROM admins WHERE username = ? LIMIT 1');
    $row->execute([$_SESSION['admin_username']]);
    $adminId = (int)($row->fetchColumn() ?: 0);
    if ($adminId) $_SESSION['admin_id'] = $adminId;
}
if (!$adminId) {
    session_destroy();
    header('Location: ' . BASE_URL . '/admin/login.php');
    exit;
}

$admin = db()->prepare('SELECT * FROM admins WHERE id = ? LIMIT 1');
$admin->execute([$adminId]);
$admin = $admin->fetch();

if (!$admin) {
    session_destroy();
    header('Location: ' . BASE_URL . '/admin/login.php');
    exit;
}

$successMsg = '';
$errorMsg   = '';
$activeTab  = $_GET['tab'] ?? 'account';

// ── E-mailadres bijwerken ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_email') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        $errorMsg = 'Ongeldig verzoek.';
    } else {
        $newEmail = trim($_POST['email'] ?? '');
        if ($newEmail && !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $errorMsg = 'Ongeldig e-mailadres.';
        } else {
            try {
                db()->prepare('UPDATE admins SET email = ? WHERE id = ?')
                     ->execute([$newEmail ?: null, $adminId]);
                $admin['email'] = $newEmail;
                $successMsg = 'E-mailadres bijgewerkt.';
            } catch (\PDOException $e) {
                $errorMsg = 'Kon e-mailadres niet opslaan.';
            }
        }
        $activeTab = 'account';
    }
}

// ── Wachtwoord wijzigen ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        $errorMsg = 'Ongeldig verzoek.';
    } else {
        $huidig   = $_POST['current_password'] ?? '';
        $nieuw    = $_POST['new_password'] ?? '';
        $bevestig = $_POST['confirm_password'] ?? '';

        if (!password_verify($huidig, $admin['password'])) {
            $errorMsg = 'Huidig wachtwoord is onjuist.';
        } elseif (strlen($nieuw) < 10) {
            $errorMsg = 'Nieuw wachtwoord moet minimaal 10 tekens lang zijn.';
        } elseif ($nieuw !== $bevestig) {
            $errorMsg = 'Nieuwe wachtwoorden komen niet overeen.';
        } else {
            $hash = password_hash($nieuw, PASSWORD_DEFAULT);
            db()->prepare('UPDATE admins SET password = ? WHERE id = ?')
                 ->execute([$hash, $adminId]);
            $successMsg = 'Wachtwoord succesvol gewijzigd.';
        }
        $activeTab = 'wachtwoord';
    }
}

// ── 2FA: genereer setup secret indien nog niet actief ────────────
if (empty($_SESSION['totp_setup_secret']) && !$admin['totp_enabled']) {
    $_SESSION['totp_setup_secret'] = totpGenerateSecret();
}
$tempSecret = $_SESSION['totp_setup_secret'] ?? '';
$otpUri     = $tempSecret ? totpUri($tempSecret, $admin['username']) : '';
$qrUrl      = $tempSecret
    ? 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . rawurlencode($otpUri)
    : '';

// ── 2FA activeren ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'verify_2fa') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        $errorMsg = 'Ongeldig verzoek.';
    } else {
        $code = preg_replace('/\s+/', '', $_POST['totp_code'] ?? '');
        if (totpVerify($tempSecret, $code)) {
            try {
                db()->prepare('UPDATE admins SET totp_secret = ?, totp_enabled = 1 WHERE id = ?')
                     ->execute([$tempSecret, $adminId]);
                $admin['totp_enabled'] = 1;
                $admin['totp_secret']  = $tempSecret;
                unset($_SESSION['totp_setup_secret']);
                $tempSecret = '';
                $successMsg = '2FA is succesvol ingeschakeld.';
            } catch (\PDOException $e) {
                $errorMsg = 'Database-kolom ontbreekt. Voer eerst de migratie uit (zie admins.php).';
            }
        } else {
            $errorMsg = 'Ongeldige code. Controleer uw app en probeer opnieuw.';
        }
        $activeTab = '2fa';
    }
}

// ── 2FA uitschakelen ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'disable_2fa') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        $errorMsg = 'Ongeldig verzoek.';
    } else {
        $code = preg_replace('/\s+/', '', $_POST['totp_code_disable'] ?? '');
        if (totpVerify($admin['totp_secret'], $code)) {
            db()->prepare('UPDATE admins SET totp_secret = NULL, totp_enabled = 0 WHERE id = ?')
                 ->execute([$adminId]);
            $admin['totp_enabled'] = 0;
            $admin['totp_secret']  = null;
            $_SESSION['totp_setup_secret'] = totpGenerateSecret();
            $tempSecret = $_SESSION['totp_setup_secret'];
            $otpUri     = totpUri($tempSecret, $admin['username']);
            $qrUrl      = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . rawurlencode($otpUri);
            $successMsg = '2FA is uitgeschakeld.';
        } else {
            $errorMsg = 'Ongeldige verificatiecode. 2FA niet uitgeschakeld.';
        }
        $activeTab = '2fa';
    }
}

$adminActivePage = 'account-instellingen';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Account instellingen &ndash; Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Epilogue:wght@700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/base.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/components.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
  <meta name="robots" content="noindex,nofollow">
  <style>
    /* ── Enkel paginaspecifiek: tab-pane zichtbaarheid & totp-input ── */
    .tab-pane            { display: none; }
    .tab-pane.active     { display: block; }

    .totp-input {
      font-size: 1.5rem !important;
      letter-spacing: .3em;
      text-align: center;
      font-weight: 700;
      width: 100%;
      padding: .7rem 1rem;
      border: 1.5px solid var(--adm-border);
      border-radius: 10px;
      font-family: var(--adm-font);
      color: var(--adm-ink);
      outline: none;
      transition: border-color .2s, box-shadow .2s;
      background: var(--adm-surface-2);
    }
    .totp-input:focus {
      border-color: var(--adm-accent);
      box-shadow: 0 0 0 3px var(--adm-accent-ring);
      background: var(--adm-surface);
    }

    .setup-step {
      display: flex;
      align-items: flex-start;
      gap: 1rem;
      margin-bottom: 1.5rem;
      padding-bottom: 1.5rem;
      border-bottom: 1px solid var(--adm-border);
    }
    .setup-step:last-of-type {
      border-bottom: none;
      margin-bottom: 0;
      padding-bottom: 0;
    }

    .danger-zone {
      border: 1.5px solid #fecaca;
      border-radius: var(--adm-radius-lg);
      padding: 1.1rem 1.4rem;
      background: #fff5f5;
      margin-top: 1.5rem;
    }
    .danger-zone h4 {
      color: #991b1b;
      font-size: .855rem;
      font-weight: 700;
      margin-bottom: .7rem;
    }
  </style>
</head>
<body>

<?php require_once __DIR__ . '/includes/admin-header.php'; ?>

<div class="adm-page">
  <div class="page-header-row">
    <h1 class="adm-page-title">&#128274; Account instellingen</h1>
  </div>
  <p class="adm-page-subtitle">
    Ingelogd als <strong><?= h($admin['username']) ?></strong>
  </p>

  <?php if ($successMsg): ?>
    <div class="alert alert-success">✓ <?= h($successMsg) ?></div>
  <?php endif; ?>
  <?php if ($errorMsg): ?>
    <div class="alert alert-error">⚠ <?= h($errorMsg) ?></div>
  <?php endif; ?>

  <div class="settings-layout">

    <!-- ── Zijnavigatie ── -->
    <div class="settings-nav admin-card" style="padding:.75rem;">
      <button class="settings-nav-item <?= $activeTab === 'account'    ? 'active' : '' ?>"
              onclick="switchTab('account', this)">
        <span>👤</span> Account
      </button>
      <button class="settings-nav-item <?= $activeTab === 'wachtwoord' ? 'active' : '' ?>"
              onclick="switchTab('wachtwoord', this)">
        <span>🔑</span> Wachtwoord
      </button>
      <button class="settings-nav-item <?= $activeTab === '2fa'        ? 'active' : '' ?>"
              onclick="switchTab('2fa', this)">
        <span>🔐</span> Tweefactorauthenticatie
      </button>
    </div>

    <!-- ── Tabinhoud ── -->
    <div class="settings-content">

      <!-- TAB: Account -->
      <div id="tab-account" class="tab-pane <?= $activeTab === 'account' ? 'active' : '' ?>">
        <div class="admin-card">
          <h2>Accountgegevens</h2>
          <form method="POST" class="form-admin">
            <input type="hidden" name="action" value="update_email">
            <input type="hidden" name="csrf"   value="<?= csrf() ?>">
            <div class="field">
              <label for="username">Gebruikersnaam</label>
              <input type="text" id="username" value="<?= h($admin['username']) ?>" disabled
                     style="background:var(--adm-bg);color:var(--adm-faint);cursor:not-allowed;">
            </div>
            <div class="field">
              <label for="email">E-mailadres</label>
              <input type="email" id="email" name="email"
                     value="<?= h($admin['email'] ?? '') ?>"
                     placeholder="admin@jouwdomein.nl">
            </div>
            <button type="submit" class="btn btn-primary">Opslaan</button>
          </form>
        </div>
      </div>

      <!-- TAB: Wachtwoord -->
      <div id="tab-wachtwoord" class="tab-pane <?= $activeTab === 'wachtwoord' ? 'active' : '' ?>">
        <div class="admin-card">
          <h2>Wachtwoord wijzigen</h2>
          <form method="POST" class="form-admin" autocomplete="off">
            <input type="hidden" name="action" value="change_password">
            <input type="hidden" name="csrf"   value="<?= csrf() ?>">
            <div class="field">
              <label for="current_password">Huidig wachtwoord</label>
              <input type="password" id="current_password" name="current_password"
                     autocomplete="current-password" required>
            </div>
            <div class="field">
              <label for="new_password">
                Nieuw wachtwoord
                <span style="color:var(--adm-faint);font-weight:400;text-transform:none;letter-spacing:0;">(minimaal 10 tekens)</span>
              </label>
              <input type="password" id="new_password" name="new_password"
                     autocomplete="new-password" minlength="10" required>
            </div>
            <div class="field">
              <label for="confirm_password">Nieuw wachtwoord bevestigen</label>
              <input type="password" id="confirm_password" name="confirm_password"
                     autocomplete="new-password" minlength="10" required>
            </div>
            <button type="submit" class="btn btn-primary">Wachtwoord wijzigen</button>
          </form>
        </div>
      </div>

      <!-- TAB: 2FA -->
      <div id="tab-2fa" class="tab-pane <?= $activeTab === '2fa' ? 'active' : '' ?>">
        <div class="admin-card">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;flex-wrap:wrap;gap:.75rem;">
            <h2 style="margin:0;">Tweefactorauthenticatie</h2>
            <?php if ($admin['totp_enabled']): ?>
              <span class="fa-status-badge on">✓ Ingeschakeld</span>
            <?php else: ?>
              <span class="fa-status-badge off">✕ Uitgeschakeld</span>
            <?php endif; ?>
          </div>

          <?php if (!$admin['totp_enabled']): ?>

            <!-- Stap 1 -->
            <div class="setup-step">
              <div class="step-num">1</div>
              <div class="step-body">
                <h3>Installeer een authenticator-app</h3>
                <p>Download <strong>Google Authenticator</strong>, <strong>Aegis</strong> of <strong>Authy</strong> op uw telefoon.</p>
              </div>
            </div>

            <!-- Stap 2 -->
            <div class="setup-step">
              <div class="step-num">2</div>
              <div class="step-body">
                <h3>Scan de QR-code of voer de sleutel in</h3>
                <p>Scan onderstaande QR-code met uw authenticator-app, of voer de handmatige sleutel in.</p>
                <?php if ($qrUrl): ?>
                  <div class="qr-wrap">
                    <img src="<?= h($qrUrl) ?>" width="200" height="200" alt="2FA QR-code" loading="lazy">
                  </div>
                  <div style="margin-top:1rem;">
                    <p style="font-size:.8rem;color:var(--adm-muted);margin-bottom:.4rem;">Handmatige sleutel:</p>
                    <div class="secret-code">
                      <span id="totp-secret"><?= h(chunk_split($tempSecret, 4, ' ')) ?></span>
                      <button type="button" class="copy-btn"
                              onclick="navigator.clipboard.writeText('<?= h($tempSecret) ?>').then(()=>{this.textContent='Gekopieerd!';setTimeout(()=>{this.textContent='Kopieer'},2000)})">
                        Kopieer
                      </button>
                    </div>
                  </div>
                <?php endif; ?>
              </div>
            </div>

            <!-- Stap 3 -->
            <div class="setup-step">
              <div class="step-num">3</div>
              <div class="step-body">
                <h3>Verificeer en activeer</h3>
                <p>Voer de 6-cijferige code in die uw app toont om 2FA te activeren.</p>
                <form method="POST" class="form-admin" style="max-width:320px;">
                  <input type="hidden" name="action" value="verify_2fa">
                  <input type="hidden" name="csrf"   value="<?= csrf() ?>">
                  <div class="field">
                    <input type="text" name="totp_code" class="totp-input"
                           placeholder="000 000" maxlength="7"
                           autocomplete="one-time-code" inputmode="numeric" required>
                  </div>
                  <button type="submit" class="btn btn-primary" style="width:100%;">
                    2FA activeren
                  </button>
                </form>
              </div>
            </div>

          <?php else: ?>

            <p style="font-size:.875rem;color:var(--adm-muted);margin-bottom:1.5rem;">
              Uw account is beveiligd met tweefactorauthenticatie. Om 2FA uit te schakelen heeft u uw huidige verificatiecode nodig.
            </p>
            <div class="danger-zone">
              <h4>⚠ 2FA uitschakelen</h4>
              <form method="POST" class="form-admin" style="max-width:320px;">
                <input type="hidden" name="action" value="disable_2fa">
                <input type="hidden" name="csrf"   value="<?= csrf() ?>">
                <div class="field">
                  <label for="totp_code_disable">Verificatiecode</label>
                  <input type="text" id="totp_code_disable" name="totp_code_disable"
                         class="totp-input" placeholder="000 000" maxlength="7"
                         autocomplete="one-time-code" inputmode="numeric" required>
                </div>
                <button type="submit" class="btn btn-danger" style="margin-top:.5rem;">
                  2FA uitschakelen
                </button>
              </form>
            </div>

          <?php endif; ?>
        </div>
      </div>

    </div><!-- /.settings-content -->
  </div><!-- /.settings-layout -->
</div><!-- /.adm-page -->

<script>
function switchTab(name, btn) {
  document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.settings-nav-item').forEach(b => b.classList.remove('active'));
  const pane = document.getElementById('tab-' + name);
  if (pane) pane.classList.add('active');
  if (btn)  btn.classList.add('active');
}

document.querySelectorAll('.totp-input').forEach(function(el) {
  el.addEventListener('input', function() {
    let v = this.value.replace(/\D/g, '').substring(0, 6);
    this.value = v.length > 3 ? v.slice(0,3) + ' ' + v.slice(3) : v;
  });
});
</script>
</body>
</html>