<?php
/**
 * admin/instellingen.php
 * Sitebrede instellingen — reCAPTCHA v3, SMTP (Brevo) en overige opties.
 */
session_start();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$adminActivePage = 'instellingen';

db()->exec("
    CREATE TABLE IF NOT EXISTS site_settings (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        setting_key   VARCHAR(100) NOT NULL UNIQUE,
        setting_value TEXT         NOT NULL DEFAULT '',
        updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$defaults = [
    'recaptcha_enabled'    => '0',
    'recaptcha_site_key'   => '',
    'recaptcha_secret_key' => '',
    'recaptcha_threshold'  => '0.5',
    'brevo_api_key'        => '',
    'brevo_sender_name'    => '',
    'brevo_sender_email'   => '',
    'brevo_enabled'        => '0',
];
$insertStmt = db()->prepare("INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES (?, ?)");
foreach ($defaults as $k => $v) {
    $insertStmt->execute([$k, $v]);
}

$success = false;
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_settings') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Beveiligingstoken ongeldig. Probeer opnieuw.';
    } else {
        $updateStmt = db()->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = ?");

        $updateStmt->execute([isset($_POST['recaptcha_enabled']) ? '1' : '0', 'recaptcha_enabled']);
        $updateStmt->execute([trim($_POST['recaptcha_site_key']   ?? ''), 'recaptcha_site_key']);
        $updateStmt->execute([trim($_POST['recaptcha_secret_key'] ?? ''), 'recaptcha_secret_key']);
        $threshold = (float)($_POST['recaptcha_threshold'] ?? 0.5);
        $threshold = max(0.0, min(1.0, $threshold));
        $updateStmt->execute([number_format($threshold, 2, '.', ''), 'recaptcha_threshold']);

        $updateStmt->execute([isset($_POST['brevo_enabled']) ? '1' : '0', 'brevo_enabled']);
        $updateStmt->execute([trim($_POST['brevo_api_key']      ?? ''), 'brevo_api_key']);
        $updateStmt->execute([trim($_POST['brevo_sender_name']  ?? ''), 'brevo_sender_name']);
        $updateStmt->execute([trim($_POST['brevo_sender_email'] ?? ''), 'brevo_sender_email']);

        $success = true;
    }
}

$rcEnabled   = getSetting('recaptcha_enabled')   === '1';
$rcSiteKey   = getSetting('recaptcha_site_key');
$rcSecretKey = getSetting('recaptcha_secret_key');
$rcThreshold = getSetting('recaptcha_threshold', '0.5');

$brevoEnabled     = getSetting('brevo_enabled')      === '1';
$brevoApiKey      = getSetting('brevo_api_key');
$brevoSenderName  = getSetting('brevo_sender_name');
$brevoSenderEmail = getSetting('brevo_sender_email');

require_once __DIR__ . '/includes/admin-header.php';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Instellingen &ndash; Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Epilogue:wght@700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/base.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/components.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
  <meta name="robots" content="noindex,nofollow">
</head>
<body>

<div class="adm-page" style="max-width:900px;">

  <h1 class="adm-page-title">Instellingen</h1>
  <p class="adm-page-subtitle">Sitebrede configuratie — reCAPTCHA, SMTP en overige opties.</p>

  <?php if ($success): ?>
    <div class="alert alert-success">&#10003; Instellingen opgeslagen.</div>
  <?php elseif ($error): ?>
    <div class="alert alert-error">&#9888; <?= h($error) ?></div>
  <?php endif; ?>

  <form method="POST" action="">
    <input type="hidden" name="action"     value="save_settings" />
    <input type="hidden" name="csrf_token" value="<?= csrf() ?>" />

    <!-- SECTIE 1 — Google reCAPTCHA v3 -->
    <div class="settings-card">
      <div class="settings-card-header">
        <div class="settings-card-icon">&#128274;</div>
        <div>
          <div class="settings-card-title">Google reCAPTCHA v3</div>
          <p class="settings-card-desc">
            Beschermt formulieren automatisch op de achtergrond.
            Vereist een gratis Google-account op
            <a href="https://www.google.com/recaptcha/admin" target="_blank" rel="noopener" class="link-accent">google.com/recaptcha</a>.
          </p>
        </div>
      </div>

      <div class="toggle-row">
        <div>
          <div class="toggle-row-label">reCAPTCHA activeren</div>
          <div class="toggle-row-desc">Laadt het reCAPTCHA-script en valideert alle formulieren met <code>data-recaptcha</code>.</div>
        </div>
        <label class="toggle-switch">
          <input type="checkbox" name="recaptcha_enabled" value="1" <?= $rcEnabled ? 'checked' : '' ?> />
          <span class="toggle-slider"></span>
        </label>
      </div>

      <div class="info-box">
        <strong>Hoe werkt het?</strong><br>
        Elk formulier met het attribuut <code>data-recaptcha="actienaam"</code> wordt beschermd.
        Bij verzenden wordt een onzichtbaar token meegestuurd dat de server valideert via
        <code>verifyRecaptcha($token)</code> in <code>includes/functions.php</code>.<br><br>
        <strong>Sleutels aanmaken:</strong> ga naar
        <a href="https://www.google.com/recaptcha/admin/create" target="_blank" rel="noopener">google.com/recaptcha/admin/create</a>,
        kies <strong>Score-based (v3)</strong> en voeg jouw domeinnaam toe.
      </div>

      <div class="s-field">
        <label for="recaptcha_site_key">Site Key (publiek)</label>
        <input type="text" id="recaptcha_site_key" name="recaptcha_site_key"
               value="<?= h($rcSiteKey) ?>" placeholder="6LcXXXXXXXXXXXXXXXXXXXXXXXXX" autocomplete="off" />
        <div class="hint">Zichtbaar in de frontend — wordt meegegeven met het formulier.</div>
      </div>

      <div class="s-field">
        <label for="recaptcha_secret_key">Secret Key (privé)</label>
        <input type="text" id="recaptcha_secret_key" name="recaptcha_secret_key"
               value="<?= h($rcSecretKey) ?>" placeholder="6LcXXXXXXXXXXXXXXXXXXXXXXXXX" autocomplete="off" />
        <div class="hint">Nooit delen — alleen server-side gebruikt voor tokenvalidatie.</div>
      </div>

      <div class="s-field">
        <label for="recaptcha_threshold">Minimale score (drempelwaarde)</label>
        <div class="threshold-row">
          <input type="range" id="rcSlider" min="0" max="1" step="0.05"
                 value="<?= h($rcThreshold) ?>"
                 oninput="document.getElementById('rcVal').textContent=this.value;
                          document.getElementById('recaptcha_threshold').value=this.value;" />
          <span class="threshold-val" id="rcVal"><?= h($rcThreshold) ?></span>
        </div>
        <input type="hidden" id="recaptcha_threshold" name="recaptcha_threshold" value="<?= h($rcThreshold) ?>" />
        <div class="hint">Scores van <strong>0.0</strong> (bot) tot <strong>1.0</strong> (mens). Aanbevolen: <strong>0.5</strong>.</div>
      </div>
    </div>

    <!-- SECTIE 2 — SMTP / Brevo -->
    <div class="settings-card">
      <div class="settings-card-header">
        <div class="settings-card-icon">&#128231;</div>
        <div>
          <div class="settings-card-title">SMTP — Brevo (Sendinblue)</div>
          <p class="settings-card-desc">
            Koppel de site aan Brevo voor transactionele e-mails.
            Vereist een account op
            <a href="https://app.brevo.com" target="_blank" rel="noopener" class="link-accent">app.brevo.com</a>.
          </p>
        </div>
      </div>

      <div class="toggle-row">
        <div>
          <div class="toggle-row-label">Brevo SMTP activeren</div>
          <div class="toggle-row-desc">Alle systeem-e-mails worden via de Brevo API verstuurd.</div>
        </div>
        <label class="toggle-switch">
          <input type="checkbox" name="brevo_enabled" value="1" <?= $brevoEnabled ? 'checked' : '' ?> />
          <span class="toggle-slider"></span>
        </label>
      </div>

      <div class="info-box">
        <strong>API Key ophalen:</strong><br>
        Log in op <a href="https://app.brevo.com/settings/keys/api" target="_blank" rel="noopener">app.brevo.com → Instellingen → API Keys</a>
        en maak een nieuwe API v3-sleutel aan (begint met <code>xkeysib-</code>).
      </div>

      <div class="s-field">
        <label for="brevo_api_key">Brevo API Key (v3)</label>
        <input type="text" id="brevo_api_key" name="brevo_api_key"
               value="<?= h($brevoApiKey) ?>" placeholder="xkeysib-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" autocomplete="off" />
        <div class="hint">
          Vind je sleutel op
          <a href="https://app.brevo.com/settings/keys/api" target="_blank" rel="noopener">app.brevo.com → API Keys</a>.
        </div>
      </div>

      <div class="s-row-2">
        <div class="s-field">
          <label for="brevo_sender_name">Verzendernaam</label>
          <input type="text" id="brevo_sender_name" name="brevo_sender_name"
                 value="<?= h($brevoSenderName) ?>" placeholder="ReparatiePlatform" />
          <div class="hint">De naam die de ontvanger ziet als afzender.</div>
        </div>
        <div class="s-field">
          <label for="brevo_sender_email">Verzender e-mailadres</label>
          <input type="email" id="brevo_sender_email" name="brevo_sender_email"
                 value="<?= h($brevoSenderEmail) ?>" placeholder="noreply@jouwdomein.nl" />
          <div class="hint">Moet geverifieerd zijn in je Brevo-account.</div>
        </div>
      </div>
    </div>

    <button type="submit" class="btn-save">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17,21 17,13 7,13 7,21"/><polyline points="7,3 7,8 15,8"/></svg>
      Instellingen opslaan
    </button>
  </form>
</div>

<script>
(function(){
  var slider = document.getElementById('rcSlider');
  var val    = document.getElementById('rcVal');
  if (slider && val) {
    slider.addEventListener('input', function() { val.textContent = this.value; });
  }
})();
</script>
</body>
</html>