<?php
/**
 * admin/instellingen.php
 * Sitebrede instellingen — reCAPTCHA v3, SMTP (Brevo) en overige opties.
 */
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Toegangscontrole
if (empty($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/login.php');
    exit;
}

$adminActivePage = 'instellingen';

// ── Zorg dat de tabel bestaat ──────────────────────────────────────────────
db()->exec("
    CREATE TABLE IF NOT EXISTS site_settings (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        setting_key   VARCHAR(100) NOT NULL UNIQUE,
        setting_value TEXT         NOT NULL DEFAULT '',
        updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// ── Standaardwaarden inserten als ze nog niet bestaan ─────────────────────
$defaults = [
    'recaptcha_enabled'    => '0',
    'recaptcha_site_key'   => '',
    'recaptcha_secret_key' => '',
    'recaptcha_threshold'  => '0.5',
    // Brevo SMTP
    'brevo_api_key'        => '',
    'brevo_sender_name'    => '',
    'brevo_sender_email'   => '',
    'brevo_enabled'        => '0',
];
$insertStmt = db()->prepare("
    INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES (?, ?)
");
foreach ($defaults as $k => $v) {
    $insertStmt->execute([$k, $v]);
}

// ── Opslaan ───────────────────────────────────────────────────────────────
$success = false;
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_settings') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Beveiligingstoken ongeldig. Probeer opnieuw.';
    } else {
        $updateStmt = db()->prepare("
            UPDATE site_settings SET setting_value = ? WHERE setting_key = ?
        ");

        // reCAPTCHA
        $updateStmt->execute([
            isset($_POST['recaptcha_enabled']) ? '1' : '0',
            'recaptcha_enabled'
        ]);
        $updateStmt->execute([
            trim($_POST['recaptcha_site_key']   ?? ''),
            'recaptcha_site_key'
        ]);
        $updateStmt->execute([
            trim($_POST['recaptcha_secret_key'] ?? ''),
            'recaptcha_secret_key'
        ]);
        $threshold = (float)($_POST['recaptcha_threshold'] ?? 0.5);
        $threshold = max(0.0, min(1.0, $threshold));
        $updateStmt->execute([
            number_format($threshold, 2, '.', ''),
            'recaptcha_threshold'
        ]);

        // Brevo SMTP
        $updateStmt->execute([
            isset($_POST['brevo_enabled']) ? '1' : '0',
            'brevo_enabled'
        ]);
        $updateStmt->execute([
            trim($_POST['brevo_api_key']      ?? ''),
            'brevo_api_key'
        ]);
        $updateStmt->execute([
            trim($_POST['brevo_sender_name']  ?? ''),
            'brevo_sender_name'
        ]);
        $updateStmt->execute([
            trim($_POST['brevo_sender_email'] ?? ''),
            'brevo_sender_email'
        ]);

        $success = true;
    }
}

// ── Huidige waarden ophalen via getSetting() uit functions.php ────────────
$rcEnabled   = getSetting('recaptcha_enabled')    === '1';
$rcSiteKey   = getSetting('recaptcha_site_key');
$rcSecretKey = getSetting('recaptcha_secret_key');
$rcThreshold = getSetting('recaptcha_threshold',  '0.5');

$brevoEnabled     = getSetting('brevo_enabled')      === '1';
$brevoApiKey      = getSetting('brevo_api_key');
$brevoSenderName  = getSetting('brevo_sender_name');
$brevoSenderEmail = getSetting('brevo_sender_email');

require_once __DIR__ . '/includes/admin-header.php';
?>

<style>
body { background: #0b0f19; font-family: 'Inter', system-ui, sans-serif; color: #e2e8f0; margin: 0; }

.adm-page { padding: 2rem 2.25rem; max-width: 900px; }

.page-title {
  font-size: 1.4rem;
  font-weight: 800;
  color: #fff;
  margin: 0 0 .35rem;
  letter-spacing: -.025em;
}
.page-subtitle {
  font-size: .875rem;
  color: rgba(255,255,255,.45);
  margin: 0 0 2.5rem;
}

.settings-card {
  background: #161b2e;
  border: 1px solid rgba(255,255,255,.07);
  border-radius: 12px;
  padding: 2rem;
  margin-bottom: 1.5rem;
}
.settings-card-header {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 1.75rem;
  padding-bottom: 1.25rem;
  border-bottom: 1px solid rgba(255,255,255,.06);
}
.settings-card-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: rgba(79,152,163,.18);
  border: 1px solid rgba(79,152,163,.25);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  color: #4ecb9e;
  font-size: 1.1rem;
}
.settings-card-title {
  font-size: 1rem;
  font-weight: 700;
  color: #fff;
  margin: 0 0 .2rem;
  letter-spacing: -.02em;
}
.settings-card-desc {
  font-size: .8rem;
  color: rgba(255,255,255,.4);
  margin: 0;
  line-height: 1.6;
}

.s-field { margin-bottom: 1.25rem; }
.s-field label {
  display: block;
  font-size: .8rem;
  font-weight: 600;
  color: rgba(255,255,255,.7);
  margin-bottom: .45rem;
  letter-spacing: .01em;
}
.s-field input[type="text"],
.s-field input[type="number"],
.s-field input[type="email"] {
  width: 100%;
  background: #0d1117;
  border: 1px solid rgba(255,255,255,.1);
  border-radius: 8px;
  padding: .6rem .85rem;
  font-size: .875rem;
  color: #e2e8f0;
  font-family: 'Inter', monospace;
  transition: border-color .15s;
  box-sizing: border-box;
}
.s-field input[type="text"]:focus,
.s-field input[type="number"]:focus,
.s-field input[type="email"]:focus {
  outline: none;
  border-color: #4f98a3;
  box-shadow: 0 0 0 3px rgba(79,152,163,.15);
}
.s-field .hint {
  font-size: .75rem;
  color: rgba(255,255,255,.3);
  margin-top: .35rem;
  line-height: 1.5;
}
.s-field .hint a { color: #4ecb9e; text-decoration: none; }
.s-field .hint a:hover { text-decoration: underline; }

.s-row-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}
@media (max-width: 600px) { .s-row-2 { grid-template-columns: 1fr; } }

.toggle-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: .75rem 1rem;
  background: rgba(255,255,255,.03);
  border: 1px solid rgba(255,255,255,.06);
  border-radius: 8px;
  margin-bottom: 1.5rem;
}
.toggle-row-label { font-size: .875rem; font-weight: 600; color: rgba(255,255,255,.8); }
.toggle-row-desc  { font-size: .75rem; color: rgba(255,255,255,.35); margin-top: .15rem; }
.toggle-switch { position: relative; display: inline-block; width: 44px; height: 24px; flex-shrink: 0; }
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.toggle-slider {
  position: absolute;
  inset: 0;
  background: rgba(255,255,255,.12);
  border-radius: 999px;
  cursor: pointer;
  transition: background .2s;
}
.toggle-slider::before {
  content: '';
  position: absolute;
  left: 3px; top: 3px;
  width: 18px; height: 18px;
  background: #fff;
  border-radius: 50%;
  transition: transform .2s;
}
.toggle-switch input:checked + .toggle-slider { background: #4f98a3; }
.toggle-switch input:checked + .toggle-slider::before { transform: translateX(20px); }

.threshold-row {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-top: .5rem;
}
.threshold-row input[type="range"] {
  flex: 1;
  accent-color: #4f98a3;
  cursor: pointer;
}
.threshold-val {
  font-size: .875rem;
  font-weight: 700;
  color: #4ecb9e;
  min-width: 36px;
  text-align: right;
}

.alert {
  padding: .85rem 1.1rem;
  border-radius: 8px;
  font-size: .875rem;
  font-weight: 600;
  margin-bottom: 1.5rem;
  display: flex;
  align-items: center;
  gap: .6rem;
}
.alert-success {
  background: rgba(78,203,158,.12);
  border: 1px solid rgba(78,203,158,.3);
  color: #4ecb9e;
}
.alert-error {
  background: rgba(231,76,60,.12);
  border: 1px solid rgba(231,76,60,.3);
  color: #fc8181;
}

.info-box {
  background: rgba(79,152,163,.08);
  border: 1px solid rgba(79,152,163,.2);
  border-radius: 8px;
  padding: 1rem 1.25rem;
  font-size: .8rem;
  color: rgba(255,255,255,.55);
  line-height: 1.7;
  margin-bottom: 1.5rem;
}
.info-box strong { color: #4ecb9e; }
.info-box code {
  background: rgba(255,255,255,.06);
  border-radius: 4px;
  padding: .1rem .4rem;
  font-family: monospace;
  font-size: .8rem;
  color: #a5d8ff;
}

.btn-save {
  display: inline-flex;
  align-items: center;
  gap: .5rem;
  background: #4f98a3;
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: .65rem 1.4rem;
  font-size: .875rem;
  font-weight: 700;
  cursor: pointer;
  transition: background .15s, transform .1s;
  letter-spacing: -.01em;
}
.btn-save:hover { background: #3a7d88; }
.btn-save:active { transform: scale(.98); }

body { padding-bottom: 3rem; }
</style>

<div class="adm-page">

  <h1 class="page-title">Instellingen</h1>
  <p class="page-subtitle">Sitebrede configuratie — reCAPTCHA, SMTP en overige opties.</p>

  <?php if ($success): ?>
    <div class="alert alert-success">&#10003; Instellingen opgeslagen.</div>
  <?php elseif ($error): ?>
    <div class="alert alert-error">&#9888; <?= h($error) ?></div>
  <?php endif; ?>

  <form method="POST" action="">
    <input type="hidden" name="action"     value="save_settings" />
    <input type="hidden" name="csrf_token" value="<?= csrf() ?>" />

    <!-- ══════════════════════════════════════════
         SECTIE 1 — Google reCAPTCHA v3
    ══════════════════════════════════════════ -->
    <div class="settings-card">
      <div class="settings-card-header">
        <div class="settings-card-icon">&#128274;</div>
        <div>
          <div class="settings-card-title">Google reCAPTCHA v3</div>
          <p class="settings-card-desc">
            Beschermt formulieren automatisch op de achtergrond — zonder extra handelingen voor bezoekers.
            Vereist een gratis Google-account op
            <a href="https://www.google.com/recaptcha/admin" target="_blank" rel="noopener" style="color:#4ecb9e;">google.com/recaptcha</a>.
          </p>
        </div>
      </div>

      <!-- Aan/Uit -->
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
        Wanneer reCAPTCHA is ingeschakeld, laadt <code>includes/header.php</code> automatisch het Google-script.
        Elk formulier met het attribuut <code>data-recaptcha="actienaam"</code> wordt beschermd:
        bij verzenden wordt een onzichtbaar token meegestuurd dat jouw server valideert via
        <code>verifyRecaptcha($token)</code> in <code>includes/functions.php</code>.<br><br>
        <strong>Sleutels aanmaken:</strong> ga naar
        <a href="https://www.google.com/recaptcha/admin/create" target="_blank" rel="noopener" style="color:#4ecb9e;">google.com/recaptcha/admin/create</a>,
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
        <div class="hint">Nooit delen — alleen server-side gebruikt voor tokenvalidatie via de Google API.</div>
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
        <div class="hint">
          Scores lopen van <strong>0.0</strong> (waarschijnlijk bot) tot <strong>1.0</strong> (waarschijnlijk mens).
          Aanbevolen: <strong>0.5</strong>. Verlaag bij te veel valse positieven.
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════
         SECTIE 2 — SMTP / Brevo
    ══════════════════════════════════════════ -->
    <div class="settings-card">
      <div class="settings-card-header">
        <div class="settings-card-icon">&#128231;</div>
        <div>
          <div class="settings-card-title">SMTP — Brevo (Sendinblue)</div>
          <p class="settings-card-desc">
            Koppel de site aan Brevo voor het verzenden van transactionele e-mails (bevestigingen, meldingen, templates).
            Vereist een gratis of betaald Brevo-account op
            <a href="https://app.brevo.com" target="_blank" rel="noopener" style="color:#4ecb9e;">app.brevo.com</a>.
          </p>
        </div>
      </div>

      <!-- Aan/Uit -->
      <div class="toggle-row">
        <div>
          <div class="toggle-row-label">Brevo SMTP activeren</div>
          <div class="toggle-row-desc">Wanneer actief worden alle systeem-e-mails via de Brevo API verstuurd.</div>
        </div>
        <label class="toggle-switch">
          <input type="checkbox" name="brevo_enabled" value="1" <?= $brevoEnabled ? 'checked' : '' ?> />
          <span class="toggle-slider"></span>
        </label>
      </div>

      <div class="info-box">
        <strong>API Key ophalen:</strong><br>
        Log in op <a href="https://app.brevo.com/settings/keys/api" target="_blank" rel="noopener" style="color:#4ecb9e;">app.brevo.com → Instellingen → API Keys</a>
        en maak een nieuwe API v3-sleutel aan. Kopieer de volledige sleutel (begint met <code>xkeysib-</code>) en plak die hieronder.<br><br>
        <strong>Gebruik in code:</strong> roep <code>getSetting('brevo_api_key')</code> aan om de sleutel op te halen
        en gebruik de officiële <a href="https://github.com/sendinblue/APIv3-php-library" target="_blank" rel="noopener" style="color:#4ecb9e;">Brevo PHP-library</a>
        (<code>APIv3-php-library</code>) om transactionele mails of campaigns te versturen via
        <code>Sendinblue\Client\Configuration::getDefaultConfiguration()->setApiKey("api-key", $apiKey)</code>.
      </div>

      <div class="s-field">
        <label for="brevo_api_key">Brevo API Key (v3)</label>
        <input type="text" id="brevo_api_key" name="brevo_api_key"
               value="<?= h($brevoApiKey) ?>" placeholder="xkeysib-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" autocomplete="off" />
        <div class="hint">
          Vind je sleutel op
          <a href="https://app.brevo.com/settings/keys/api" target="_blank" rel="noopener">app.brevo.com → Instellingen → API Keys</a>.
          Bewaar deze sleutel veilig — geef hem nooit publiek vrij.
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
          <div class="hint">Moet geverifieerd zijn in je Brevo-account (Senders & IPs).</div>
        </div>
      </div>
    </div>

    <!-- Opslaan -->
    <button type="submit" class="btn-save">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17,21 17,13 7,13 7,21"/><polyline points="7,3 7,8 15,8"/></svg>
      Instellingen opslaan
    </button>

  </form>
</div>

<script>
// Sync slider op paginalaad
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