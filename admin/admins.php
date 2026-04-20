<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$successMsg = '';
$errorMsg   = '';

// ── Verwijderen ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        $errorMsg = 'Ongeldig verzoek.';
    } else {
        $deleteId = (int)($_POST['id'] ?? 0);
        $huidig = db()->prepare('SELECT id FROM admins WHERE username = ? LIMIT 1');
        $huidig->execute([$_SESSION['admin_username'] ?? '']);
        $huidigId = (int)($huidig->fetchColumn() ?: 0);
        if ($deleteId && $deleteId !== $huidigId) {
            db()->prepare('DELETE FROM admins WHERE id = ?')->execute([$deleteId]);
            $successMsg = 'Admin-account verwijderd.';
        } else {
            $errorMsg = 'Je kunt je eigen actieve account niet verwijderen.';
        }
    }
}

// ── Nieuw account aanmaken ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        $errorMsg = 'Ongeldig verzoek.';
    } else {
        $username  = trim($_POST['username'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $password  = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';

        if (!$username || !$password) {
            $errorMsg = 'Gebruikersnaam en wachtwoord zijn verplicht.';
        } elseif ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMsg = 'Ongeldig e-mailadres.';
        } elseif (strlen($password) < 10) {
            $errorMsg = 'Wachtwoord moet minimaal 10 tekens lang zijn.';
        } elseif ($password !== $password2) {
            $errorMsg = 'Wachtwoorden komen niet overeen.';
        } else {
            $check = db()->prepare('SELECT COUNT(*) FROM admins WHERE username = ?');
            $check->execute([$username]);
            if ($check->fetchColumn() > 0) {
                $errorMsg = 'Deze gebruikersnaam is al in gebruik.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                db()->prepare('INSERT INTO admins (username, email, password) VALUES (?, ?, ?)')
                     ->execute([$username, $email ?: null, $hash]);
                $successMsg = 'Nieuw admin-account aangemaakt voor "' . h($username) . '".';
            }
        }
    }
}

// ── E-mail bijwerken ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_email') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        $errorMsg = 'Ongeldig verzoek.';
    } else {
        $editId   = (int)($_POST['id'] ?? 0);
        $newEmail = trim($_POST['new_email'] ?? '');
        if ($newEmail && !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $errorMsg = 'Ongeldig e-mailadres.';
        } elseif ($editId) {
            db()->prepare('UPDATE admins SET email = ? WHERE id = ?')
                 ->execute([$newEmail ?: null, $editId]);
            $successMsg = 'E-mailadres bijgewerkt.';
        }
    }
}

// ── Wachtwoord wijzigen ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        $errorMsg = 'Ongeldig verzoek.';
    } else {
        $editId  = (int)($_POST['id'] ?? 0);
        $newPw   = $_POST['new_password'] ?? '';
        $newPw2  = $_POST['new_password2'] ?? '';
        if (strlen($newPw) < 10) {
            $errorMsg = 'Wachtwoord moet minimaal 10 tekens lang zijn.';
        } elseif ($newPw !== $newPw2) {
            $errorMsg = 'Wachtwoorden komen niet overeen.';
        } elseif ($editId) {
            $hash = password_hash($newPw, PASSWORD_DEFAULT);
            db()->prepare('UPDATE admins SET password = ? WHERE id = ?')->execute([$hash, $editId]);
            $successMsg = 'Wachtwoord bijgewerkt.';
        }
    }
}

// ── 2FA uitschakelen ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'disable_2fa') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        $errorMsg = 'Ongeldig verzoek.';
    } else {
        $editId = (int)($_POST['id'] ?? 0);
        if ($editId) {
            db()->prepare('UPDATE admins SET totp_secret = NULL, totp_enabled = 0 WHERE id = ?')
                 ->execute([$editId]);
            $successMsg = '2FA uitgeschakeld.';
        }
    }
}

$admins = db()->query('SELECT id, username, email, totp_enabled, created_at FROM admins ORDER BY id ASC')->fetchAll();
$adminUsername = $_SESSION['admin_username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin accounts &ndash; Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Epilogue:wght@800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/base.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/components.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
  <meta name="robots" content="noindex,nofollow">
  <style>
    .pw-toggle-wrap { position: relative; }
    .pw-toggle-wrap input { padding-right: 2.5rem; }
    .pw-eye {
      position: absolute; right: .65rem; top: 50%; transform: translateY(-50%);
      background: none; border: none; cursor: pointer; color: var(--muted);
      font-size: 1rem; padding: 0; line-height: 1;
    }
    .pw-strength { font-size: .75rem; margin-top: .3rem; height: 1.1em; }
    .pw-strength.weak   { color: #dc2626; }
    .pw-strength.medium { color: #d97706; }
    .pw-strength.strong { color: #16a34a; }
    .badge-you { background: #d6f0eb; color: #287864; font-size: .7rem; font-weight: 700;
      padding: .15rem .5rem; border-radius: 99px; margin-left: .5rem; vertical-align: middle; }
    .badge-2fa-on  { background: #dcfce7; color: #15803d; font-size: .7rem; font-weight: 700;
      padding: .15rem .5rem; border-radius: 99px; margin-left: .4rem; vertical-align: middle; }
    .badge-2fa-off { background: #fef9c3; color: #854d0e; font-size: .7rem; font-weight: 700;
      padding: .15rem .5rem; border-radius: 99px; margin-left: .4rem; vertical-align: middle; }
    .modal-overlay {
      display: none; position: fixed; inset: 0; background: rgba(0,0,0,.45);
      z-index: 9999; align-items: center; justify-content: center;
    }
    .modal-overlay.open { display: flex; }
    .modal-box {
      background: white; border-radius: 16px; padding: 2rem; width: 100%; max-width: 420px;
      box-shadow: 0 20px 60px rgba(0,0,0,.2);
    }
    .modal-box h3 { font-size: 1.1rem; font-weight: 700; margin-bottom: 1.25rem; }
    .modal-close { float: right; background: none; border: none; font-size: 1.2rem; cursor: pointer; color: var(--muted); }
    .btn-admin-sm {
      padding: .35rem .75rem; border-radius: 8px; font-size: .8rem;
      font-weight: 600; cursor: pointer; transition: all .15s; border: none;
    }
    .btn-admin-outline {
      border: 1.5px solid var(--border); background: white; color: var(--ink);
    }
    .btn-admin-outline:hover { border-color: var(--accent); color: var(--accent); }
    .btn-admin-danger {
      background: #fee2e2; color: #b91c1c; border: 1.5px solid #fecaca;
    }
    .btn-admin-danger:hover { background: #fecaca; }
    .btn-admin-warning {
      background: #fef9c3; color: #854d0e; border: 1.5px solid #fef08a;
    }
    .btn-admin-warning:hover { background: #fef08a; }
  </style>
</head>
<body>
<div class="admin-wrap">

<nav class="admin-nav">
  <span class="logo">Reparatie<span>Platform</span></span>

  <div class="admin-nav-menu">
    <a href="<?= BASE_URL ?>/admin/dashboard.php"><span class="icon">&#128202;</span> Dashboard</a>
    <a href="<?= BASE_URL ?>/admin/aanvragen.php"><span class="icon">&#128236;</span> Inzendingen</a>
    <a href="<?= BASE_URL ?>/admin/meldingen.php"><span class="icon">&#128276;</span> Meldingen</a>
    <a href="<?= BASE_URL ?>/admin/modellen.php"><span class="icon">&#128250;</span> TV Modellen</a>
    <a href="<?= BASE_URL ?>/admin/klachten.php"><span class="icon">&#9888;&#65039;</span> Klachten</a>
    <a href="<?= BASE_URL ?>/admin/advies-instellingen.php"><span class="icon">&#9881;&#65039;</span> Adviesregels</a>
    <a href="<?= BASE_URL ?>/admin/mailtemplates.php"><span class="icon">&#128140;</span> Mailtemplates</a>
    <a href="<?= BASE_URL ?>/admin/admins.php" class="active"><span class="icon">&#128100;</span> Admin accounts</a>
  </div>

  <div class="admin-nav-actions">
    <a href="<?= BASE_URL ?>/admin/account-instellingen.php" title="Account instellingen">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
      <?= htmlspecialchars($adminUsername) ?>
    </a>
    <div class="admin-nav-divider"></div>
    <a href="<?= BASE_URL ?>/" target="_blank" title="Website bekijken">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
      Website
    </a>
    <div class="admin-nav-divider"></div>
    <a href="<?= BASE_URL ?>/admin/logout.php" class="nav-logout">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16,17 21,12 16,7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Uitloggen
    </a>
  </div>
</nav>

<div class="admin-content" style="padding: 2rem;">
  <h1>&#128100; Admin accounts</h1>

  <?php if ($successMsg): ?>
    <div class="alert alert-success" style="margin-bottom:1.5rem;">&#10003; <?= h($successMsg) ?></div>
  <?php endif; ?>
  <?php if ($errorMsg): ?>
    <div class="alert alert-error" style="margin-bottom:1.5rem;">&#9888; <?= h($errorMsg) ?></div>
  <?php endif; ?>

  <!-- Bestaande accounts -->
  <div class="admin-card" style="margin-bottom:2rem;">
    <h2>Bestaande admin-accounts</h2>
    <table class="admin-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Gebruikersnaam</th>
          <th>E-mailadres</th>
          <th>2FA</th>
          <th>Aangemaakt op</th>
          <th>Acties</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($admins as $a):
        $isJij = ($a['username'] === ($_SESSION['admin_username'] ?? ''));
        $has2fa = !empty($a['totp_enabled']);
      ?>
      <tr>
        <td style="color:var(--muted);font-size:.8rem;"><?= (int)$a['id'] ?></td>
        <td>
          <strong><?= h($a['username']) ?></strong>
          <?php if ($isJij): ?><span class="badge-you">jij</span><?php endif; ?>
        </td>
        <td style="font-size:.85rem;">
          <?php if (!empty($a['email'])): ?>
            <?= h($a['email']) ?>
          <?php else: ?>
            <span style="color:var(--muted);font-style:italic;">—</span>
          <?php endif; ?>
        </td>
        <td>
          <?php if ($has2fa): ?>
            <span class="badge-2fa-on">&#128274; Aan</span>
          <?php else: ?>
            <span class="badge-2fa-off">Uit</span>
          <?php endif; ?>
        </td>
        <td style="font-size:.8rem;color:var(--muted);"><?= h($a['created_at'] ?? '—') ?></td>
        <td>
          <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
            <button type="button"
              onclick="openEmailModal(<?= (int)$a['id'] ?>, '<?= h(addslashes($a['username'])) ?>', '<?= h(addslashes($a['email'] ?? '')) ?>')"
              class="btn-admin-sm btn-admin-outline">
              &#9993; E-mail
            </button>
            <button type="button"
              onclick="openPwModal(<?= (int)$a['id'] ?>, '<?= h(addslashes($a['username'])) ?>')"
              class="btn-admin-sm btn-admin-outline">
              &#128273; Wachtwoord
            </button>
            <?php if ($isJij && !$has2fa): ?>
            <a href="<?= BASE_URL ?>/admin/2fa-setup.php" class="btn-admin-sm btn-admin-warning" style="text-decoration:none;">
              &#128272; 2FA instellen
            </a>
            <?php elseif ($has2fa && $isJij): ?>
            <form method="POST" style="margin:0;" onsubmit="return confirm('2FA uitschakelen voor dit account?');">
              <input type="hidden" name="csrf"   value="<?= csrf() ?>">
              <input type="hidden" name="action" value="disable_2fa">
              <input type="hidden" name="id"     value="<?= (int)$a['id'] ?>">
              <button type="submit" class="btn-admin-sm btn-admin-warning">&#128274; 2FA uitzetten</button>
            </form>
            <?php endif; ?>
            <?php if (!$isJij): ?>
            <form method="POST" style="margin:0;"
                  onsubmit="return confirm('Admin \"<?= h(addslashes($a['username'])) ?>\" verwijderen?');">
              <input type="hidden" name="csrf"   value="<?= csrf() ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id"     value="<?= (int)$a['id'] ?>">
              <button type="submit" class="btn-admin-sm btn-admin-danger">&#128465; Verwijderen</button>
            </form>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Nieuw account aanmaken -->
  <div class="admin-card">
    <h2>Nieuw admin-account aanmaken</h2>
    <form method="POST" class="form-admin" style="max-width:460px;" id="createForm">
      <input type="hidden" name="csrf"   value="<?= csrf() ?>">
      <input type="hidden" name="action" value="create">
      <div class="field">
        <label>Gebruikersnaam *</label>
        <input type="text" name="username" placeholder="bijv. admin2" autocomplete="off" required />
      </div>
      <div class="field">
        <label>E-mailadres <small style="font-weight:400;color:var(--muted);">(optioneel, voor meldingen)</small></label>
        <input type="email" name="email" placeholder="admin@voorbeeld.nl" autocomplete="off" />
      </div>
      <div class="field">
        <label>Wachtwoord * <small style="font-weight:400;color:var(--muted);">(minimaal 10 tekens)</small></label>
        <div class="pw-toggle-wrap">
          <input type="password" name="password" id="newPw" placeholder="••••••••••••" autocomplete="new-password" required oninput="checkStrength(this,'newPwStrength')" />
          <button type="button" class="pw-eye" onclick="togglePw('newPw',this)">&#128065;</button>
        </div>
        <div class="pw-strength" id="newPwStrength"></div>
      </div>
      <div class="field">
        <label>Herhaal wachtwoord *</label>
        <div class="pw-toggle-wrap">
          <input type="password" name="password2" id="newPw2" placeholder="••••••••••••" autocomplete="new-password" required />
          <button type="button" class="pw-eye" onclick="togglePw('newPw2',this)">&#128065;</button>
        </div>
      </div>
      <button type="submit" class="btn-primary" style="margin-top:.5rem;">
        &#43; Account aanmaken
      </button>
    </form>
  </div>
</div><!-- /admin-content -->
</div><!-- /admin-wrap -->

<!-- Modal: e-mail wijzigen -->
<div class="modal-overlay" id="emailModal">
  <div class="modal-box">
    <button type="button" class="modal-close" onclick="closeEmailModal()">&#10005;</button>
    <h3 id="emailModalTitle">E-mailadres wijzigen</h3>
    <form method="POST" class="form-admin" id="emailForm">
      <input type="hidden" name="csrf"   value="<?= csrf() ?>">
      <input type="hidden" name="action" value="update_email">
      <input type="hidden" name="id"     id="emailModalId">
      <div class="field">
        <label>E-mailadres <small style="font-weight:400;color:var(--muted);">(leeg = geen)</small></label>
        <input type="email" name="new_email" id="modalEmail" placeholder="admin@voorbeeld.nl" autocomplete="off" />
      </div>
      <div style="display:flex;gap:.75rem;margin-top:1rem;">
        <button type="submit" class="btn-primary">Opslaan</button>
        <button type="button" class="btn-ghost" onclick="closeEmailModal()">Annuleren</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: wachtwoord wijzigen -->
<div class="modal-overlay" id="pwModal">
  <div class="modal-box">
    <button type="button" class="modal-close" onclick="closePwModal()">&#10005;</button>
    <h3 id="pwModalTitle">Wachtwoord wijzigen</h3>
    <form method="POST" class="form-admin" id="pwForm">
      <input type="hidden" name="csrf"   value="<?= csrf() ?>">
      <input type="hidden" name="action" value="change_password">
      <input type="hidden" name="id"     id="pwModalId">
      <div class="field">
        <label>Nieuw wachtwoord *</label>
        <div class="pw-toggle-wrap">
          <input type="password" name="new_password" id="modalPw" placeholder="••••••••••••" autocomplete="new-password" required oninput="checkStrength(this,'modalPwStrength')" />
          <button type="button" class="pw-eye" onclick="togglePw('modalPw',this)">&#128065;</button>
        </div>
        <div class="pw-strength" id="modalPwStrength"></div>
      </div>
      <div class="field">
        <label>Herhaal wachtwoord *</label>
        <div class="pw-toggle-wrap">
          <input type="password" name="new_password2" id="modalPw2" placeholder="••••••••••••" autocomplete="new-password" required />
          <button type="button" class="pw-eye" onclick="togglePw('modalPw2',this)">&#128065;</button>
        </div>
      </div>
      <div style="display:flex;gap:.75rem;margin-top:1rem;">
        <button type="submit" class="btn-primary">Opslaan</button>
        <button type="button" class="btn-ghost" onclick="closePwModal()">Annuleren</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEmailModal(id, username, currentEmail) {
  document.getElementById('emailModalId').value = id;
  document.getElementById('emailModalTitle').textContent = 'E-mail wijzigen – ' + username;
  document.getElementById('modalEmail').value = currentEmail;
  document.getElementById('emailModal').classList.add('open');
}
function closeEmailModal() {
  document.getElementById('emailModal').classList.remove('open');
}
document.getElementById('emailModal').addEventListener('click', function(e) {
  if (e.target === this) closeEmailModal();
});

function openPwModal(id, username) {
  document.getElementById('pwModalId').value = id;
  document.getElementById('pwModalTitle').textContent = 'Wachtwoord wijzigen – ' + username;
  document.getElementById('modalPw').value = '';
  document.getElementById('modalPw2').value = '';
  document.getElementById('modalPwStrength').textContent = '';
  document.getElementById('pwModal').classList.add('open');
}
function closePwModal() {
  document.getElementById('pwModal').classList.remove('open');
}
document.getElementById('pwModal').addEventListener('click', function(e) {
  if (e.target === this) closePwModal();
});

function togglePw(id, btn) {
  const el = document.getElementById(id);
  if (el.type === 'password') { el.type = 'text'; btn.textContent = '🙈'; }
  else { el.type = 'password'; btn.textContent = '👁'; }
}

function checkStrength(input, targetId) {
  const pw  = input.value;
  const el  = document.getElementById(targetId);
  if (!pw) { el.textContent = ''; el.className = 'pw-strength'; return; }
  let score = 0;
  if (pw.length >= 10) score++;
  if (pw.length >= 14) score++;
  if (/[A-Z]/.test(pw)) score++;
  if (/[0-9]/.test(pw)) score++;
  if (/[^A-Za-z0-9]/.test(pw)) score++;
  if (score <= 1) { el.textContent = 'Sterkte: zwak'; el.className = 'pw-strength weak'; }
  else if (score <= 3) { el.textContent = 'Sterkte: redelijk'; el.className = 'pw-strength medium'; }
  else { el.textContent = 'Sterkte: sterk ✓'; el.className = 'pw-strength strong'; }
}
</script>
</body>
</html>