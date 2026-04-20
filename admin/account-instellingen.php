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
if (!$adminId) {
    header('Location: ' . BASE_URL . '/admin/login.php');
    exit;
}

$admin = db()->prepare('SELECT * FROM admins WHERE id = ? LIMIT 1');
$admin->execute([$adminId]);
$admin = $admin->fetch();

if (!$admin) {
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
        $huidig    = $_POST['current_password'] ?? '';
        $nieuw     = $_POST['new_password'] ?? '';
        $bevestig  = $_POST['confirm_password'] ?? '';

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

// ── 2FA: Starten setup (genereer secret in sessie) ────────────────
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
            // Genereer alvast een nieuw secret voor eventuele herinschakeling
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
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Account instellingen &ndash; Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Epilogue:wght@800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/base.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/components.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
  <meta name="robots" content="noindex,nofollow">
  <style>
    /* ── Account-instellingen layout ── */
    .settings-layout {
      display: grid;
      grid-template-columns: 220px 1fr;
      gap: 1.5rem;
      align-items: start;
    }
    .settings-nav {
      position: sticky;
      top: 1.5rem;
    }
    .settings-nav-item {
      display: flex;
      align-items: center;
      gap: .6rem;
      padding: .7rem 1rem;
      border-radius: 10px;
      font-size: .875rem;
      font-weight: 500;
      color: var(--muted);
      text-decoration: none;
      cursor: pointer;
      border: none;
      background: none;
      width: 100%;
      text-align: left;
      transition: all .15s;
      margin-bottom: .2rem;
    }
    .settings-nav-item:hover  { background: var(--surface); color: var(--ink); }
    .settings-nav-item.active { background: var(--accent-light); color: var(--accent); font-weight: 700; }
    .settings-nav-item .icon  { font-size: 1rem; flex-shrink: 0; }

    /* ── 2FA specifiek ── */
    .fa-status-badge {
      display: inline-flex;
      align-items: center;
      gap: .4rem;
      font-size: .8rem;
      font-weight: 700;
      padding: .3rem .75rem;
      border-radius: 99px;
    }
    .fa-status-badge.on  { background: #dcfce7; color: #166534; }
    .fa-status-badge.off { background: #fef2f2; color: #991b1b; }

    .setup-step {
      display: flex;
      align-items: flex-start;
      gap: 1rem;
      margin-bottom: 1.5rem;
      padding-bottom: 1.5rem;
      border-bottom: 1px solid var(--border);
    }
    .setup-step:last-of-type { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
    .step-nr {
      width: 30px; height: 30px; border-radius: 50%;
      background: var(--ink); color: white;
      display: flex; align-items: center; justify-content: center;
      font-size: .78rem; font-weight: 700; flex-shrink: 0; margin-top: .1rem;
    }
    .step-body h3 { font-size: .95rem; font-weight: 700; margin-bottom: .35rem; }
    .step-body p  { font-size: .875rem; color: var(--muted); line-height: 1.65; margin: 0 0 .65rem; }

    .secret-box {
      display: flex; align-items: center; gap: .75rem;
      background: var(--surface); border: 1.5px solid var(--border);
      border-radius: 10px; padding: .7rem 1rem;
      font-family: monospace; font-size: .9rem; font-weight: 700;
      letter-spacing: .1em; color: var(--ink); flex-wrap: wrap;
    }
    .copy-btn {
      background: var(--ink); color: white; border: none; border-radius: 6px;
      padding: .28rem .65rem; font-size: .73rem; font-weight: 600; cursor: pointer;
      transition: background .2s; flex-shrink: 0; margin-left: auto;
    }
    .copy-btn:hover { background: var(--accent); }

    .qr-wrap {
      display: flex; flex-direction: column; align-items: center; gap: .65rem;
      padding: 1.1rem; background: var(--surface); border-radius: 12px; margin-top: .65rem;
    }
    .qr-wrap img { border-radius: 8px; border: 4px solid white; box-shadow: 0 4px 16px rgba(0,0,0,.1); }

    .totp-input {
      font-size: 1.5rem !important; letter-spacing: .3em; text-align: center;
      font-weight: 700; width: 100%; padding: .7rem 1rem;
      border: 1.5px solid var(--border); border-radius: 12px;
      font-family: 'Inter', sans-serif; color: var(--ink);
      outline: none; transition: border-color .2s, box-shadow .2s;
    }
    .totp-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(40,120,100,.1); }

    .tab-pane { display: none; }
    .tab-pane.active { display: block; }

    .danger-zone {
      border: 1.5px solid #fecaca;
      border-radius: 12px;
      padding: 1.25rem 1.5rem;
      background: #fff5f5;
      margin-top: 1.5rem;
    }
    .danger-zone h4 { color: #991b1b; font-size: .875rem; font-weight: 700; margin-bottom: .75rem; }
  </style>
</head>
<body>
<div class="admin-wrap">
<nav class="admin-nav">
  <span class="logo">Reparatie<span>Platform</span> Admin</span>
  <a href="<?= BASE_URL ?>/admin/logout.php">Uitloggen</a>
</nav>
<div class="admin-layout">
  <div class="admin-sidebar">
    <a href="<?= BASE_URL ?>/admin/dashboard.php"><span class="icon">&#128202;</span> Dashboard</a>
    <a href="<?= BASE_URL ?>/admin/aanvragen.php"><span class="icon">&#128236;</span> Inzendingen</a>
    <a href="<?= BASE_URL ?>/admin/meldingen.php"><span class="icon">&#128276;</span> Meldingen</a>
    <a href="<?= BASE_URL ?>/admin/modellen.php"><span class="icon">&#128250;</span> TV Modellen</a>
    <a href="<?= BASE_URL ?>/admin/klachten.php"><span class="icon">&#9888;</span> Klachten</a>
    <a href="<?= BASE_URL ?>/admin/advies-instellingen.php"><span class="icon">&#9881;</span> Advies instellingen</a>
    <a href="<?= BASE_URL ?>/admin/mailtemplates.php"><span class="icon">&#128140;</span> Mailtemplates</a>
    <a href="<?= BASE_URL ?>/admin/admins.php"><span class="icon">&#128100;</span> Admin accounts</a>
    <a href="<?= BASE_URL ?>/admin/account-instellingen.php" class="active"><span class="icon">&#128274;</span> Account instellingen</a>
    <a href="<?= BASE_URL ?>/" target="_blank"><span class="icon">&#127760;</span> Website bekijken</a>
  </div>
  <div class="admin-content">
    <h1>&#128274; Account instellingen</h1>
    <p style="color:var(--muted);font-size:.875rem;margin-bottom:1.5rem;">
      Ingelogd als <strong><?= h($admin['username']) ?></strong>
    </p>

    <?php if ($successMsg): ?>
      <div class="alert alert-success" style="margin-bottom:1.25rem;">&#10003; <?= h($successMsg) ?></div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
      <div class="alert alert-error" style="margin-bottom:1.25rem;">&#9888; <?= h($errorMsg) ?></div>
    <?php endif; ?>

    <div class="settings-layout">

      <!-- Zijnavigatie -->
      <div class="settings-nav admin-card" style="padding:.75rem;">
        <button class="settings-nav-item <?= $activeTab === 'account'    ? 'active' : '' ?>"
                onclick="switchTab('account', this)">
          <span class="icon">&#128100;</span> Account
        </button>
        <button class="settings-nav-item <?= $activeTab === 'wachtwoord' ? 'active' : '' ?>"
                onclick="switchTab('wachtwoord', this)">
          <span class="icon">&#128272;</span> Wachtwoord
        </button>
        <button class="settings-nav-item <?= $activeTab === '2fa'        ? 'active' : '' ?>"
                onclick="switchTab('2fa', this)">
          <span class="icon">&#128