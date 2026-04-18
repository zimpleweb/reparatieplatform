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
        // Verwijder niet het account waarmee je bent ingelogd
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
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';

        if (!$username || !$password) {
            $errorMsg = 'Gebruikersnaam en wachtwoord zijn verplicht.';
        } elseif (strlen($password) < 10) {
            $errorMsg = 'Wachtwoord moet minimaal 10 tekens lang zijn.';
        } elseif ($password !== $password2) {
            $errorMsg = 'Wachtwoorden komen niet overeen.';
        } else {
            // Check duplicate
            $check = db()->prepare('SELECT COUNT(*) FROM admins WHERE username = ?');
            $check->execute([$username]);
            if ($check->fetchColumn() > 0) {
                $errorMsg = 'Deze gebruikersnaam is al in gebruik.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                db()->prepare('INSERT INTO admins (username, password) VALUES (?, ?)')
                     ->execute([$username, $hash]);
                $successMsg = 'Nieuw admin-account aangemaakt voor "' . h($username) . '".';
            }
        }
    }
}

// ── Wachtwoord wijzigen ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        $errorMsg = 'Ongeldig verzoek.';
    } else {
        $editId    = (int)($_POST['id'] ?? 0);
        $newPw     = $_POST['new_password'] ?? '';
        $newPw2    = $_POST['new_password2'] ?? '';
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

$admins = db()->query('SELECT id, username, created_at FROM admins ORDER BY id ASC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin accounts &ndash; Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Epilogue:wght@800&display=swap" rel="stylesheet">
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
    <a href="<?= BASE_URL ?>/admin/admins.php" class="active"><span class="icon">&#128100;</span> Admin accounts</a>
    <a href="<?= BASE_URL ?>/" target="_blank"><span class="icon">&#127760;</span> Website bekijken</a>
  </div>
  <div class="admin-content">
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
            <th>Aangemaakt op</th>
            <th>Acties</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($admins as $a): 
          $isJij = ($a['username'] === ($_SESSION['admin_username'] ?? ''));
        ?>
        <tr>
          <td style="color:var(--muted);font-size:.8rem;"><?= (int)$a['id'] ?></td>
          <td>
            <strong><?= h($a['username']) ?></strong>
            <?php if ($isJij): ?><span class="badge-you">jij</span><?php endif; ?>
          </td>
          <td style="font-size:.8rem;color:var(--muted);"><?= h($a['created_at'] ?? '—') ?></td>
          <td>
            <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
              <button type="button"
                onclick="openPwModal(<?= (int)$a['id'] ?>, '<?= h(addslashes($a['username'])) ?>')"
                class="btn-admin-sm btn-admin-outline">
                &#128273; Wachtwoord
              </button>
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

<style>
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
</style>

<script>
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