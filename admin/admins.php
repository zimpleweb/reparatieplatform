<?php
session_start();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$successMsg = '';
$errorMsg   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) { $errorMsg = 'Ongeldig verzoek.'; } else {
        $deleteId = (int)($_POST['id'] ?? 0);
        $huidig = db()->prepare('SELECT id FROM admins WHERE username = ? LIMIT 1');
        $huidig->execute([$_SESSION['admin_username'] ?? '']);
        $huidigId = (int)($huidig->fetchColumn() ?: 0);
        if ($deleteId && $deleteId !== $huidigId) {
            db()->prepare('DELETE FROM admins WHERE id = ?')->execute([$deleteId]);
            $successMsg = 'Admin-account verwijderd.';
        } else { $errorMsg = 'Je kunt je eigen actieve account niet verwijderen.'; }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) { $errorMsg = 'Ongeldig verzoek.'; } else {
        $username  = trim($_POST['username'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $password  = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';
        if (!$username || !$password) { $errorMsg = 'Gebruikersnaam en wachtwoord zijn verplicht.'; }
        elseif ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errorMsg = 'Ongeldig e-mailadres.'; }
        elseif (strlen($password) < 10) { $errorMsg = 'Wachtwoord moet minimaal 10 tekens lang zijn.'; }
        elseif ($password !== $password2) { $errorMsg = 'Wachtwoorden komen niet overeen.'; }
        else {
            $check = db()->prepare('SELECT COUNT(*) FROM admins WHERE username = ?');
            $check->execute([$username]);
            if ($check->fetchColumn() > 0) { $errorMsg = 'Deze gebruikersnaam is al in gebruik.'; }
            else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                db()->prepare('INSERT INTO admins (username, email, password) VALUES (?, ?, ?)')->execute([$username, $email ?: null, $hash]);
                $successMsg = 'Nieuw admin-account aangemaakt voor "' . h($username) . '".';
            }
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_email') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) { $errorMsg = 'Ongeldig verzoek.'; } else {
        $editId   = (int)($_POST['id'] ?? 0);
        $newEmail = trim($_POST['new_email'] ?? '');
        if ($newEmail && !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) { $errorMsg = 'Ongeldig e-mailadres.'; }
        elseif ($editId) { db()->prepare('UPDATE admins SET email = ? WHERE id = ?')->execute([$newEmail ?: null, $editId]); $successMsg = 'E-mailadres bijgewerkt.'; }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) { $errorMsg = 'Ongeldig verzoek.'; } else {
        $editId = (int)($_POST['id'] ?? 0);
        $newPw  = $_POST['new_password'] ?? '';
        $newPw2 = $_POST['new_password2'] ?? '';
        if (strlen($newPw) < 10) { $errorMsg = 'Wachtwoord moet minimaal 10 tekens lang zijn.'; }
        elseif ($newPw !== $newPw2) { $errorMsg = 'Wachtwoorden komen niet overeen.'; }
        elseif ($editId) { $hash = password_hash($newPw, PASSWORD_DEFAULT); db()->prepare('UPDATE admins SET password = ? WHERE id = ?')->execute([$hash, $editId]); $successMsg = 'Wachtwoord bijgewerkt.'; }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'disable_2fa') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) { $errorMsg = 'Ongeldig verzoek.'; } else {
        $editId = (int)($_POST['id'] ?? 0);
        if ($editId) { db()->prepare('UPDATE admins SET totp_secret = NULL, totp_enabled = 0 WHERE id = ?')->execute([$editId]); $successMsg = '2FA uitgeschakeld.'; }
    }
}

$admins = db()->query('SELECT id, username, email, totp_enabled, created_at FROM admins ORDER BY id ASC')->fetchAll();
$adminActivePage = 'admins';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Beheerders &ndash; Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Epilogue:wght@700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/base.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/components.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
  <meta name="robots" content="noindex,nofollow">
</head>
<body>

<?php require_once __DIR__ . '/includes/admin-header.php'; ?>

<div class="adm-page">
  <h1 class="adm-page-title">Admin accounts</h1>

  <?php if ($successMsg): ?>
    <div class="alert alert-success">&#10003; <?= h($successMsg) ?></div>
  <?php endif; ?>
  <?php if ($errorMsg): ?>
    <div class="alert alert-error">&#9888; <?= h($errorMsg) ?></div>
  <?php endif; ?>

  <!-- Overzicht bestaande accounts -->
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
        $isJij  = ($a['username'] === ($_SESSION['admin_username'] ?? ''));
        $has2fa = !empty($a['totp_enabled']);
      ?>
      <tr>
        <td class="adm-id-cel"><?= (int)$a['id'] ?></td>
        <td>
          <strong><?= h($a['username']) ?></strong>
          <?php if ($isJij): ?><span class="badge-you">jij</span><?php endif; ?>
        </td>
        <td class="adm-email-cel">
          <?= !empty($a['email']) ? h($a['email']) : '<span class="adm-leeg-em">—</span>' ?>
        </td>
        <td>
          <?= $has2fa
            ? '<span class="badge badge-green">Aan</span>'
            : '<span class="badge badge-yellow">Uit</span>' ?>
        </td>
        <td class="adm-datum-cel"><?= h($a['created_at'] ?? '—') ?></td>
        <td>
          <div class="adm-acties-row">
            <button type="button"
              onclick="openEmailModal(<?= (int)$a['id'] ?>, '<?= h(addslashes($a['username'])) ?>', '<?= h(addslashes($a['email'] ?? '')) ?>')"
              class="btn btn-secondary btn-sm">E-mail</button>
            <button type="button"
              onclick="openPwModal(<?= (int)$a['id'] ?>, '<?= h(addslashes($a['username'])) ?>')"
              class="btn btn-secondary btn-sm">Wachtwoord</button>
            <?php if ($isJij && !$has2fa): ?>
              <a href="<?= BASE_URL ?>/admin/2fa-setup.php" class="btn btn-sm adm-btn-warning">2FA instellen</a>
            <?php elseif ($has2fa && $isJij): ?>
              <form method="POST" style="margin:0;" onsubmit="return confirm('2FA uitschakelen?');">
                <input type="hidden" name="csrf"   value="<?= csrf() ?>">
                <input type="hidden" name="action" value="disable_2fa">
                <input type="hidden" name="id"     value="<?= (int)$a['id'] ?>">
                <button type="submit" class="btn btn-sm adm-btn-warning">2FA uitzetten</button>
              </form>
            <?php endif; ?>
            <?php if (!$isJij): ?>
              <form method="POST" style="margin:0;"
                    onsubmit="return confirm('Admin &quot;<?= h(addslashes($a['username'])) ?>&quot; verwijderen?');">
                <input type="hidden" name="csrf"   value="<?= csrf() ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id"     value="<?= (int)$a['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm">Verwijderen</button>
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
    <form method="POST" class="form-admin adm-create-form">
      <input type="hidden" name="csrf"   value="<?= csrf() ?>">
      <input type="hidden" name="action" value="create">
      <div class="field">
        <label>Gebruikersnaam *</label>
        <input type="text" name="username" placeholder="bijv. admin2" autocomplete="off" required />
      </div>
      <div class="field">
        <label>E-mailadres <span class="adm-opt-label">(optioneel)</span></label>
        <input type="email" name="email" placeholder="admin@voorbeeld.nl" autocomplete="off" />
      </div>
      <div class="field">
        <label>Wachtwoord * <span class="adm-opt-label">(minimaal 10 tekens)</span></label>
        <div class="pw-toggle-wrap">
          <input type="password" name="password" id="newPw"
                 placeholder="••••••••••••" autocomplete="new-password" required
                 oninput="checkStrength(this,'newPwStrength')" />
          <button type="button" class="pw-eye" onclick="togglePw('newPw',this)">&#128065;</button>
        </div>
        <div class="pw-strength" id="newPwStrength"></div>
      </div>
      <div class="field">
        <label>Herhaal wachtwoord *</label>
        <div class="pw-toggle-wrap">
          <input type="password" name="password2" id="newPw2"
                 placeholder="••••••••••••" autocomplete="new-password" required />
          <button type="button" class="pw-eye" onclick="togglePw('newPw2',this)">&#128065;</button>
        </div>
      </div>
      <button type="submit" class="btn btn-primary">Account aanmaken</button>
    </form>
  </div>
</div><!-- /.adm-page -->

<!-- Modal: e-mail wijzigen -->
<div class="modal-overlay" id="emailModal">
  <div class="modal-box">
    <button type="button" class="modal-close" onclick="closeEmailModal()">&#10005;</button>
    <p class="modal-title" id="emailModalTitle">E-mailadres wijzigen</p>
    <form method="POST" class="form-admin">
      <input type="hidden" name="csrf"   value="<?= csrf() ?>">
      <input type="hidden" name="action" value="update_email">
      <input type="hidden" name="id"     id="emailModalId">
      <div class="field">
        <label>E-mailadres</label>
        <input type="email" name="new_email" id="modalEmail" placeholder="admin@voorbeeld.nl" />
      </div>
      <div class="adm-modal-actions">
        <button type="submit" class="btn btn-primary">Opslaan</button>
        <button type="button" class="btn btn-secondary" onclick="closeEmailModal()">Annuleren</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: wachtwoord wijzigen -->
<div class="modal-overlay" id="pwModal">
  <div class="modal-box">
    <button type="button" class="modal-close" onclick="closePwModal()">&#10005;</button>
    <p class="modal-title" id="pwModalTitle">Wachtwoord wijzigen</p>
    <form method="POST" class="form-admin">
      <input type="hidden" name="csrf"   value="<?= csrf() ?>">
      <input type="hidden" name="action" value="change_password">
      <input type="hidden" name="id"     id="pwModalId">
      <div class="field">
        <label>Nieuw wachtwoord *</label>
        <div class="pw-toggle-wrap">
          <input type="password" name="new_password" id="modalPw"
                 placeholder="••••••••••••" autocomplete="new-password" required
                 oninput="checkStrength(this,'modalPwStrength')" />
          <button type="button" class="pw-eye" onclick="togglePw('modalPw',this)">&#128065;</button>
        </div>
        <div class="pw-strength" id="modalPwStrength"></div>
      </div>
      <div class="field">
        <label>Herhaal wachtwoord *</label>
        <div class="pw-toggle-wrap">
          <input type="password" name="new_password2" id="modalPw2"
                 placeholder="••••••••••••" autocomplete="new-password" required />
          <button type="button" class="pw-eye" onclick="togglePw('modalPw2',this)">&#128065;</button>
        </div>
      </div>
      <div class="adm-modal-actions">
        <button type="submit" class="btn btn-primary">Opslaan</button>
        <button type="button" class="btn btn-secondary" onclick="closePwModal()">Annuleren</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEmailModal(id, username, currentEmail) {
  document.getElementById('emailModalId').value = id;
  document.getElementById('emailModalTitle').textContent = 'E-mail wijzigen – ' + username;
  document.getElementById('modalEmail').value = currentEmail;
  document.getElementById('emailModal').classList.add('is-open');
}
function closeEmailModal() { document.getElementById('emailModal').classList.remove('is-open'); }
document.getElementById('emailModal').addEventListener('click', function(e) { if (e.target === this) closeEmailModal(); });

function openPwModal(id, username) {
  document.getElementById('pwModalId').value = id;
  document.getElementById('pwModalTitle').textContent = 'Wachtwoord wijzigen – ' + username;
  document.getElementById('modalPw').value = '';
  document.getElementById('modalPw2').value = '';
  document.getElementById('modalPwStrength').textContent = '';
  document.getElementById('pwModal').classList.add('is-open');
}
function closePwModal() { document.getElementById('pwModal').classList.remove('is-open'); }
document.getElementById('pwModal').addEventListener('click', function(e) { if (e.target === this) closePwModal(); });

function togglePw(id, btn) {
  const el = document.getElementById(id);
  if (el.type === 'password') { el.type = 'text'; btn.textContent = '🙈'; }
  else { el.type = 'password'; btn.textContent = '👁'; }
}

function checkStrength(input, targetId) {
  const pw = input.value;
  const el = document.getElementById(targetId);
  if (!pw) { el.textContent = ''; el.className = 'pw-strength'; return; }
  let score = 0;
  if (pw.length >= 10) score++;
  if (pw.length >= 14) score++;
  if (/[A-Z]/.test(pw)) score++;
  if (/[0-9]/.test(pw)) score++;
  if (/[^A-Za-z0-9]/.test(pw)) score++;
  if (score <= 1)      { el.textContent = 'Sterkte: zwak';    el.className = 'pw-strength weak'; }
  else if (score <= 3) { el.textContent = 'Sterkte: redelijk'; el.className = 'pw-strength medium'; }
  else                 { el.textContent = 'Sterkte: sterk ✓'; el.className = 'pw-strength strong'; }
}
</script>
</body>
</html>