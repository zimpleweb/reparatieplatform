<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$statusLabels = [
    'inzending'    => ['tekst' => 'Ontvangen — wacht op beoordeling',  'css' => 's-inzending'],
    'doorgestuurd' => ['tekst' => 'Aanvulling nodig',                  'css' => 's-doorgestuurd'],
    'aanvraag'     => ['tekst' => 'Aanvraag volledig ontvangen',       'css' => 's-aanvraag'],
    'coulance'     => ['tekst' => 'Coulance traject',                  'css' => 's-coulance'],
    'recycling'    => ['tekst' => 'Recycling traject',                 'css' => 's-recycling'],
    'behandeld'    => ['tekst' => 'Behandeld',                         'css' => 's-behandeld'],
    'archief'      => ['tekst' => 'Gearchiveerd',                      'css' => 's-archief'],
];

$inzending  = null;
$loginFout  = false;
$melding    = '';
$meldingOk  = true;

// ── Uitloggen ──────────────────────────────────────────────────
if (isset($_GET['uitloggen'])) {
    unset($_SESSION['portal_case'], $_SESSION['portal_email']);
    redirect(BASE_URL . '/mijn-aanvraag.php');
}

// ── Inloggen via formulier ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_case'])) {
    $cnIn = strtoupper(trim($_POST['casenummer_check'] ?? ''));
    $emIn = strtolower(trim($_POST['email_check']      ?? ''));
    if ($cnIn && $emIn && filter_var($emIn, FILTER_VALIDATE_EMAIL)) {
        $chk = db()->prepare('SELECT id FROM aanvragen WHERE casenummer = ? AND LOWER(email) = ?');
        $chk->execute([$cnIn, $emIn]);
        if ($chk->fetch()) {
            $_SESSION['portal_case']  = $cnIn;
            $_SESSION['portal_email'] = $emIn;
            redirect(BASE_URL . '/mijn-aanvraag.php');
        } else {
            $loginFout = true;
        }
    } else {
        $loginFout = true;
    }
}

// ── Portaldashboard laden als ingelogd ─────────────────────────
if (!empty($_SESSION['portal_case']) && !empty($_SESSION['portal_email'])) {
    $chk = db()->prepare('SELECT * FROM aanvragen WHERE casenummer = ? AND LOWER(email) = ?');
    $chk->execute([$_SESSION['portal_case'], $_SESSION['portal_email']]);
    $inzending = $chk->fetch() ?: null;
    if (!$inzending) {
        unset($_SESSION['portal_case'], $_SESSION['portal_email']);
    } else {
        try {
            $lg = db()->prepare('SELECT * FROM aanvragen_log WHERE aanvraag_id = ? ORDER BY aangemaakt ASC');
            $lg->execute([$inzending['id']]);
            $inzending['log'] = $lg->fetchAll();
        } catch (\PDOException $e) { $inzending['log'] = []; }

        try {
            $bq = db()->prepare('SELECT * FROM aanvragen_berichten WHERE aanvraag_id = ? ORDER BY aangemaakt ASC');
            $bq->execute([$inzending['id']]);
            $inzending['berichten'] = $bq->fetchAll();
        } catch (\PDOException $e) { $inzending['berichten'] = []; }
    }
}

// ── Meldingen via URL ──────────────────────────────────────────
if (isset($_GET['nieuw'])) {
    $melding   = 'Uw aanvraag is ontvangen! Bewaar uw casenummer goed — gebruik het samen met uw e-mailadres om altijd terug te keren.';
    $meldingOk = true;
}
switch ((int)($_GET['verzonden'] ?? 0)) {
    case 2:
        $melding   = 'Uw aanvulling is ingediend. Ons team neemt zo spoedig mogelijk contact met u op.';
        $meldingOk = true;
        break;
    case 3:
        $melding   = 'Reparatieaanvraag gestart. Vul hieronder uw contactgegevens in om de aanvraag te voltooien.';
        $meldingOk = true;
        break;
}
if (isset($_GET['error'])) {
    $melding   = 'Er is iets misgegaan. Controleer uw gegevens en probeer het opnieuw.';
    $meldingOk = false;
}

// ── Helpers ────────────────────────────────────────────────────
function portalStapNr(string $status): int {
    return match ($status) {
        'inzending'                     => 1,
        'doorgestuurd'                  => 2,
        'aanvraag'                      => 3,
        'coulance', 'recycling',
        'behandeld', 'archief'          => 4,
        default                         => 1,
    };
}

$pageTitle       = 'Mijn aanvraag — Klantenomgeving | Reparatieplatform.nl';
$pageDescription = 'Bekijk de status van uw aanvraag en volg uw reparatie-, taxatie- of coulancetraject via uw persoonlijke klantenomgeving.';
$canonicalUrl    = '/mijn-aanvraag.php';

include __DIR__ . '/includes/header.php';
?>

<div class="page-header">
  <div class="page-header-inner">
    <div class="breadcrumb">
      <a href="<?= BASE_URL ?>/">Home</a><span class="sep">/</span>
      <span style="color:rgba(255,255,255,.4)">Mijn aanvraag</span>
    </div>
    <h1>Mijn aanvraag</h1>
    <p>Volg de status van uw aanvraag en dien aanvullende informatie in.</p>
  </div>
</div>

<div class="portal-page-bg">

<?php if (!$inzending): ?>
  <!-- ── LOGIN FORMULIER ──────────────────────────────────────── -->
  <div class="portal-login-wrap">
    <div class="portal-login-card">
      <div class="portal-login-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
          <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
          <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
        </svg>
      </div>
      <h2>Aanvraag inzien</h2>
      <p class="lead">Voer uw casenummer en e-mailadres in om uw persoonlijke klantenomgeving te openen.</p>

      <?php if ($loginFout): ?>
        <div class="portal-alert alert-error">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <span>Geen aanvraag gevonden met dit casenummer en e-mailadres. Controleer de gegevens en probeer opnieuw.</span>
        </div>
      <?php endif; ?>

      <form method="POST">
        <input type="hidden" name="check_case" value="1" />
        <div class="portal-field">
          <label for="casenummer_check">Casenummer</label>
          <input type="text" id="casenummer_check" name="casenummer_check"
                 placeholder="Bijv. 2026-04-1000"
                 value="<?= h($_POST['casenummer_check'] ?? '') ?>"
                 autocomplete="off" required />
        </div>
        <div class="portal-field">
          <label for="email_check">E-mailadres</label>
          <input type="email" id="email_check" name="email_check"
                 placeholder="uw@email.nl"
                 value="<?= h($_POST['email_check'] ?? '') ?>"
                 required />
        </div>
        <button type="submit" class="portal-btn portal-btn--primary portal-btn--full">
          Mijn aanvraag bekijken
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M12 5l7 7-7 7"/></svg>
        </button>
      </form>

      <p class="portal-login-hint">
        Nog geen aanvraag gedaan?
        <a href="<?= BASE_URL ?>/advies.php">Vraag gratis advies aan &rarr;</a>
      </p>
    </div>
  </div>

<?php else:
  $status   = $inzending['status'];
  $stapNr   = portalStapNr($status);
  $sl       = $statusLabels[$status] ?? ['tekst' => $status, 'css' => ''];
  $advType  = $inzending['advies_type'] ?? '';
?>

  <!-- ── PORTAL DASHBOARD ─────────────────────────────────────── -->
  <div class="portal-wrap">

    <!-- Top bar -->
    <div class="portal-top-bar">
      <div class="portal-top-bar-info">
        <div class="portal-case-title">
          Aanvraag <?= h($inzending['casenummer']) ?>
          <span class="portal-status-badge <?= $sl['css'] ?>"><?= h($sl['tekst']) ?></span>
        </div>
        <div class="portal-case-sub">
          <?= h($inzending['merk'] . ' ' . $inzending['modelnummer']) ?>
          <?php if (!empty($inzending['klacht_type'])): ?>
            &nbsp;&bull;&nbsp; <?= h($inzending['klacht_type']) ?>
          <?php endif; ?>
        </div>
      </div>
      <a href="?uitloggen=1" class="portal-btn portal-btn--ghost portal-btn--sm">
        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        Uitloggen
      </a>
    </div>

    <!-- Melding -->
    <?php if ($melding): ?>
      <div class="portal-alert <?= $meldingOk ? 'alert-success' : 'alert-error' ?>">
        <?php if ($meldingOk): ?>
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true" style="flex-shrink:0;margin-top:1px"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <?php else: ?>
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?php endif; ?>
        <span><?= h($melding) ?></span>
      </div>
    <?php endif; ?>

    <!-- Status stappen -->
    <div class="portal-status-steps">
      <div class="portal-status-steps-title">Voortgang van uw aanvraag</div>
      <div class="status-steps-track">

        <div class="status-step <?= $stapNr >= 1 ? ($stapNr === 1 ? 'active' : 'done') : '' ?>">
          <div class="status-step-dot">
            <?php if ($stapNr > 1): ?>
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            <?php else: ?>1<?php endif; ?>
          </div>
          <div class="status-step-label">Ontvangen</div>
        </div>

        <div class="status-step-connector <?= $stapNr >= 2 ? 'done' : '' ?>"></div>

        <div class="status-step <?= $stapNr >= 2 ? ($stapNr === 2 ? 'active' : 'done') : '' ?>">
          <div class="status-step-dot">
            <?php if ($stapNr > 2): ?>
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            <?php else: ?>2<?php endif; ?>
          </div>
          <div class="status-step-label">In behandeling</div>
        </div>

        <div class="status-step-connector <?= $stapNr >= 3 ? 'done' : '' ?>"></div>

        <div class="status-step <?= $stapNr >= 3 ? ($stapNr === 3 ? 'active' : 'done') : '' ?>">
          <div class="status-step-dot">
            <?php if ($stapNr > 3): ?>
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            <?php else: ?>3<?php endif; ?>
          </div>
          <div class="status-step-label">Ingediend</div>
        </div>

        <div class="status-step-connector <?= $stapNr >= 4 ? 'done' : '' ?>"></div>

        <div class="status-step <?= $stapNr >= 4 ? 'active' : '' ?>">
          <div class="status-step-dot">4</div>
          <div class="status-step-label">Afgerond</div>
        </div>

      </div>
    </div>

    <div class="portal-grid">
      <div class="portal-main">

        <!-- ── Actie: doorgestuurd → Reparatieaanvraag ──────────── -->
        <?php if ($status === 'doorgestuurd' && $advType === 'reparatie'): ?>
          <div class="portal-action-card">
            <div class="portal-action-header">
              <div class="portal-action-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
              </div>
              <div>
                <h3>Reparatieaanvraag voltooien</h3>
                <p>Controleer de vooraf ingevulde gegevens en vul de ontbrekende informatie aan.</p>
              </div>
            </div>

            <form class="portal-form" method="POST" action="<?= BASE_URL ?>/api/aanvulling.php" enctype="multipart/form-data">
              <input type="hidden" name="csrf_token"  value="<?= csrf() ?>" />
              <input type="hidden" name="aanvraag_id" value="<?= (int)$inzending['id'] ?>" />
              <input type="hidden" name="casenummer"  value="<?= h($inzending['casenummer']) ?>" />

              <div class="portal-form-section">
                <div class="portal-form-section-title">Contactgegevens</div>

                <div class="portal-field">
                  <label for="rp_naam">Naam</label>
                  <div class="portal-input-wrap<?= !empty($inzending['naam']) ? ' is-prefilled' : '' ?>">
                    <input type="text" id="rp_naam" name="naam" required
                           value="<?= h($inzending['naam'] ?? '') ?>"
                           <?= !empty($inzending['naam']) ? 'readonly' : '' ?> />
                    <?php if (!empty($inzending['naam'])): ?>
                      <span class="portal-input-lock" title="Automatisch ingevuld">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                      </span>
                    <?php endif; ?>
                  </div>
                </div>

                <div class="portal-field">
                  <label for="rp_plaats">Plaats</label>
                  <div class="portal-input-wrap<?= !empty($inzending['plaats']) ? ' is-prefilled' : '' ?>">
                    <input type="text" id="rp_plaats" name="plaats" required
                           value="<?= h($inzending['plaats'] ?? '') ?>"
                           <?= !empty($inzending['plaats']) ? 'readonly' : '' ?> />
                    <?php if (!empty($inzending['plaats'])): ?>
                      <span class="portal-input-lock" title="Automatisch ingevuld">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                      </span>
                    <?php endif; ?>
                  </div>
                </div>

                <div class="portal-field">
                  <label for="rp_email">E-mail</label>
                  <div class="portal-input-wrap<?= !empty($inzending['email']) ? ' is-prefilled' : '' ?>">
                    <input type="email" id="rp_email" name="email" required
                           value="<?= h($inzending['email'] ?? '') ?>"
                           <?= !empty($inzending['email']) ? 'readonly' : '' ?> />
                    <?php if (!empty($inzending['email'])): ?>
                      <span class="portal-input-lock" title="Automatisch ingevuld">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                      </span>
                    <?php endif; ?>
                  </div>
                </div>

                <div class="portal-field">
                  <label for="rp_telefoon">Telefoon</label>
                  <div class="portal-input-wrap<?= !empty($inzending['telefoon']) ? ' is-prefilled' : '' ?>">
                    <input type="tel" id="rp_telefoon" name="telefoon" required
                           value="<?= h($inzending['telefoon'] ?? '') ?>"
                           <?= !empty($inzending['telefoon']) ? 'readonly' : '' ?> />
                    <?php if (!empty($inzending['telefoon'])): ?>
                      <span class="portal-input-lock" title="Automatisch ingevuld">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                      </span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <div class="portal-form-section">
                <div class="portal-form-section-title">Televisiegegevens</div>

                <div class="portal-field">
                  <label for="rp_merk">Merk televisie</label>
                  <?php
                  $merken = ['Samsung','LG','Sony','Philips','Panasonic','Hisense','TCL','Grundig','Loewe','Toshiba','Anders'];
                  $merkPrefilled = !empty($inzending['merk']);
                  ?>
                  <?php if ($merkPrefilled): ?>
                    <div class="portal-input-wrap is-prefilled">
                      <input type="text" id="rp_merk" readonly value="<?= h($inzending['merk']) ?>" />
                      <input type="hidden" name="merk" value="<?= h($inzending['merk']) ?>" />
                      <span class="portal-input-lock" title="Automatisch ingevuld">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                      </span>
                    </div>
                  <?php else: ?>
                    <select name="merk" required>
                      <option value="">Selecteer merk</option>
                      <?php foreach ($merken as $m): ?>
                        <option value="<?= h($m) ?>"><?= h($m) ?></option>
                      <?php endforeach; ?>
                    </select>
                  <?php endif; ?>
                </div>

                <div class="portal-field">
                  <label for="rp_model">Modelnummer</label>
                  <div class="portal-input-wrap<?= !empty($inzending['modelnummer']) ? ' is-prefilled' : '' ?>">
                    <input type="text" id="rp_model" name="modelnummer" required
                           value="<?= h($inzending['modelnummer'] ?? '') ?>"
                           <?= !empty($inzending['modelnummer']) ? 'readonly' : '' ?> />
                    <?php if (!empty($inzending['modelnummer'])): ?>
                      <span class="portal-input-lock" title="Automatisch ingevuld">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                      </span>
                    <?php endif; ?>
                  </div>
                  <p class="portal-field-hint">Gelieve het volledige modelnummer in te voeren. Zonder compleet modelnummer kunnen wij u geen reparatieadvies toesturen.</p>
                </div>

                <div class="portal-field">
                  <label for="rp_klacht">Klachtomschrijving</label>
                  <?php if (!empty($inzending['omschrijving'])): ?>
                    <div class="portal-input-wrap is-prefilled is-prefilled--textarea">
                      <textarea id="rp_klacht" name="klacht_omschrijving" rows="4" readonly><?= h($inzending['omschrijving']) ?></textarea>
                      <span class="portal-input-lock portal-input-lock--textarea" title="Automatisch ingevuld">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                      </span>
                    </div>
                  <?php else: ?>
                    <textarea id="rp_klacht" name="klacht_omschrijving" rows="4" required placeholder="Beschrijf het defect zo nauwkeurig mogelijk..."></textarea>
                  <?php endif; ?>
                </div>
              </div>

              <div class="portal-form-section">
                <div class="portal-form-section-title">Foto's toevoegen <span class="portal-form-section-optional">(optioneel)</span></div>
                <div class="portal-field">
                  <label>Foto van de klacht</label>
                  <div class="portal-file-wrap">
                    <input type="file" name="foto_klacht" accept="image/*" />
                  </div>
                  <p class="portal-field-hint">Een foto van het defect helpt ons uw aanvraag sneller te beoordelen.</p>
                </div>
                <div class="portal-field">
                  <label>Foto van het modelnummer</label>
                  <div class="portal-file-wrap">
                    <input type="file" name="foto_modelnummer" accept="image/*" />
                  </div>
                  <p class="portal-field-hint">Foto van de sticker op de achterkant van de televisie.</p>
                </div>
              </div>

              <div class="portal-form-actions">
                <button type="submit" class="portal-btn portal-btn--primary portal-btn--full">
                  Reparatieaanvraag indienen
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M12 5l7 7-7 7"/></svg>
                </button>
              </div>
            </form>
          </div>

        <!-- ── Actie: doorgestuurd → Taxatieaanvraag ────────────── -->
        <?php elseif ($status === 'doorgestuurd' && $advType === 'taxatie'): ?>
          <div class="portal-action-card">
            <div class="portal-action-header">
              <div class="portal-action-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2"/></svg>
              </div>
              <div>
                <h3>Taxatieaanvraag voltooien</h3>
                <p>Controleer de vooraf ingevulde gegevens en vul de ontbrekende informatie aan.</p>
              </div>
            </div>

            <form class="portal-form" method="POST" action="<?= BASE_URL ?>/api/aanvulling.php" enctype="multipart/form-data">
              <input type="hidden" name="csrf_token"  value="<?= csrf() ?>" />
              <input type="hidden" name="aanvraag_id" value="<?= (int)$inzending['id'] ?>" />
              <input type="hidden" name="casenummer"  value="<?= h($inzending['casenummer']) ?>" />

              <div class="portal-form-section">
                <div class="portal-form-section-title">Contactgegevens</div>

                <div class="portal-field">
                  <label>Voor- en achternaam</label>
                  <div class="portal-input-wrap<?= !empty($inzending['naam']) ? ' is-prefilled' : '' ?>">
                    <input type="text" name="naam" required value="<?= h($inzending['naam'] ?? '') ?>" <?= !empty($inzending['naam']) ? 'readonly' : '' ?> />
                    <?php if (!empty($inzending['naam'])): ?><span class="portal-input-lock" title="Automatisch ingevuld"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span><?php endif; ?>
                  </div>
                </div>

                <div class="portal-field">
                  <label>Adres</label>
                  <div class="portal-input-wrap<?= !empty($inzending['adres']) ? ' is-prefilled' : '' ?>">
                    <input type="text" name="adres" required placeholder="Straat en huisnummer" value="<?= h($inzending['adres'] ?? '') ?>" <?= !empty($inzending['adres']) ? 'readonly' : '' ?> />
                    <?php if (!empty($inzending['adres'])): ?><span class="portal-input-lock" title="Automatisch ingevuld"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span><?php endif; ?>
                  </div>
                </div>

                <div class="portal-field-row">
                  <div class="portal-field">
                    <label>Postcode</label>
                    <div class="portal-input-wrap<?= !empty($inzending['postcode']) ? ' is-prefilled' : '' ?>">
                      <input type="text" name="postcode" required placeholder="1234 AB" value="<?= h($inzending['postcode'] ?? '') ?>" <?= !empty($inzending['postcode']) ? 'readonly' : '' ?> />
                      <?php if (!empty($inzending['postcode'])): ?><span class="portal-input-lock" title="Automatisch ingevuld"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span><?php endif; ?>
                    </div>
                  </div>
                  <div class="portal-field">
                    <label>Plaats</label>
                    <div class="portal-input-wrap<?= !empty($inzending['plaats']) ? ' is-prefilled' : '' ?>">
                      <input type="text" name="plaats" required value="<?= h($inzending['plaats'] ?? '') ?>" <?= !empty($inzending['plaats']) ? 'readonly' : '' ?> />
                      <?php if (!empty($inzending['plaats'])): ?><span class="portal-input-lock" title="Automatisch ingevuld"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span><?php endif; ?>
                    </div>
                  </div>
                </div>

                <div class="portal-field">
                  <label>E-mail</label>
                  <div class="portal-input-wrap<?= !empty($inzending['email']) ? ' is-prefilled' : '' ?>">
                    <input type="email" name="email" required value="<?= h($inzending['email'] ?? '') ?>" <?= !empty($inzending['email']) ? 'readonly' : '' ?> />
                    <?php if (!empty($inzending['email'])): ?><span class="portal-input-lock" title="Automatisch ingevuld"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span><?php endif; ?>
                  </div>
                </div>

                <div class="portal-field">
                  <label>Telefoonnummer</label>
                  <div class="portal-input-wrap<?= !empty($inzending['telefoon']) ? ' is-prefilled' : '' ?>">
                    <input type="tel" name="telefoon" required value="<?= h($inzending['telefoon'] ?? '') ?>" <?= !empty($inzending['telefoon']) ? 'readonly' : '' ?> />
                    <?php if (!empty($inzending['telefoon'])): ?><span class="portal-input-lock" title="Automatisch ingevuld"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span><?php endif; ?>
                  </div>
                </div>
              </div>

              <div class="portal-form-section">
                <div class="portal-form-section-title">Televisiegegevens</div>

                <div class="portal-field">
                  <label>Merk TV</label>
                  <?php $merkPrefilled = !empty($inzending['merk']); ?>
                  <?php if ($merkPrefilled): ?>
                    <div class="portal-input-wrap is-prefilled">
                      <input type="text" readonly value="<?= h($inzending['merk']) ?>" />
                      <input type="hidden" name="merk" value="<?= h($inzending['merk']) ?>" />
                      <span class="portal-input-lock" title="Automatisch ingevuld"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
                    </div>
                  <?php else: ?>
                    <select name="merk" required>
                      <option value="">Selecteer merk</option>
                      <?php foreach ($merken as $m): ?><option value="<?= h($m) ?>"><?= h($m) ?></option><?php endforeach; ?>
                    </select>
                  <?php endif; ?>
                </div>

                <div class="portal-field-row">
                  <div class="portal-field">
                    <label>Modelnummer</label>
                    <div class="portal-input-wrap<?= !empty($inzending['modelnummer']) ? ' is-prefilled' : '' ?>">
                      <input type="text" name="modelnummer" required value="<?= h($inzending['modelnummer'] ?? '') ?>" <?= !empty($inzending['modelnummer']) ? 'readonly' : '' ?> />
                      <?php if (!empty($inzending['modelnummer'])): ?><span class="portal-input-lock" title="Automatisch ingevuld"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span><?php endif; ?>
                    </div>
                  </div>
                  <div class="portal-field">
                    <label>Serienummer</label>
                    <div class="portal-input-wrap<?= !empty($inzending['serienummer']) ? ' is-prefilled' : '' ?>">
                      <input type="text" name="serienummer" required value="<?= h($inzending['serienummer'] ?? '') ?>" <?= !empty($inzending['serienummer']) ? 'readonly' : '' ?> />
                      <?php if (!empty($inzending['serienummer'])): ?><span class="portal-input-lock" title="Automatisch ingevuld"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span><?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>

              <div class="portal-form-section">
                <div class="portal-form-section-title">Schadegegevens</div>

                <div class="portal-field">
                  <label>Reden schade</label>
                  <div class="portal-radio-group">
                    <label class="portal-radio-item"><input type="radio" name="reden_schade" value="iets_tegen_scherm" required /><span>Iets tegen scherm gekomen</span></label>
                    <label class="portal-radio-item"><input type="radio" name="reden_schade" value="tv_gevallen" /><span>De TV is gevallen</span></label>
                    <label class="portal-radio-item"><input type="radio" name="reden_schade" value="water_vocht" /><span>Water/vochtschade</span></label>
                    <label class="portal-radio-item"><input type="radio" name="reden_schade" value="anders" /><span>Anders, namelijk (vul hieronder in)</span></label>
                  </div>
                </div>

                <div class="portal-field">
                  <label>Beschrijving <span class="portal-form-section-optional">(optioneel)</span></label>
                  <?php if (!empty($inzending['omschrijving'])): ?>
                    <div class="portal-input-wrap is-prefilled is-prefilled--textarea">
                      <textarea name="schade_beschrijving" rows="3" readonly><?= h($inzending['omschrijving']) ?></textarea>
                      <span class="portal-input-lock portal-input-lock--textarea" title="Automatisch ingevuld"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
                    </div>
                  <?php else: ?>
                    <textarea name="schade_beschrijving" rows="3" placeholder="Toelichting bij de schade..."></textarea>
                  <?php endif; ?>
                </div>

                <div class="portal-field-row">
                  <div class="portal-field">
                    <label>Aankoopbedrag</label>
                    <div class="portal-input-wrap<?= !empty($inzending['aanschafwaarde']) ? ' is-prefilled' : '' ?>">
                      <input type="text" name="aankoopbedrag" required placeholder="Bijv. €899" value="<?= h($inzending['aanschafwaarde'] ?? '') ?>" <?= !empty($inzending['aanschafwaarde']) ? 'readonly' : '' ?> />
                      <?php if (!empty($inzending['aanschafwaarde'])): ?><span class="portal-input-lock" title="Automatisch ingevuld"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span><?php endif; ?>
                    </div>
                  </div>
                  <div class="portal-field">
                    <label>Aankoopdatum</label>
                    <div class="portal-input-wrap<?= !empty($inzending['aankoopdatum']) ? ' is-prefilled' : '' ?>">
                      <input type="date" name="aankoopdatum" required value="<?= h($inzending['aankoopdatum'] ?? date('Y-m-d')) ?>" <?= !empty($inzending['aankoopdatum']) ? 'readonly' : '' ?> />
                      <?php if (!empty($inzending['aankoopdatum'])): ?><span class="portal-input-lock" title="Automatisch ingevuld"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span><?php endif; ?>
                    </div>
                  </div>
                </div>

                <div class="portal-field">
                  <label>Heeft u een bon/aankoopbewijs?</label>
                  <div class="portal-radio-group portal-radio-group--inline">
                    <label class="portal-radio-item"><input type="radio" name="heeft_bon" value="ja" /><span>Ja</span></label>
                    <label class="portal-radio-item"><input type="radio" name="heeft_bon" value="nee" /><span>Nee</span></label>
                  </div>
                </div>
              </div>

              <div class="portal-form-section">
                <div class="portal-form-section-title">Verzekeringsgegevens</div>
                <div class="portal-field-row">
                  <div class="portal-field">
                    <label>Naam verzekeringsmaatschappij</label>
                    <div class="portal-input-wrap<?= !empty($inzending['verzekeraar']) ? ' is-prefilled' : '' ?>">
                      <input type="text" name="verzekeraar" required value="<?= h($inzending['verzekeraar'] ?? '') ?>" <?= !empty($inzending['verzekeraar']) ? 'readonly' : '' ?> />
                      <?php if (!empty($inzending['verzekeraar'])): ?><span class="portal-input-lock" title="Automatisch ingevuld"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span><?php endif; ?>
                    </div>
                  </div>
                  <div class="portal-field">
                    <label>Polisnummer</label>
                    <div class="portal-input-wrap<?= !empty($inzending['polisnummer']) ? ' is-prefilled' : '' ?>">
                      <input type="text" name="polisnummer" required value="<?= h($inzending['polisnummer'] ?? '') ?>" <?= !empty($inzending['polisnummer']) ? 'readonly' : '' ?> />
                      <?php if (!empty($inzending['polisnummer'])): ?><span class="portal-input-lock" title="Automatisch ingevuld"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span><?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>

              <div class="portal-form-section">
                <div class="portal-form-section-title">Foto's uploaden</div>
                <div class="portal-field">
                  <label>Foto van het gehele toestel <span class="portal-required">*</span></label>
                  <div class="portal-file-wrap"><input type="file" name="foto_toestel" accept="image/*" required /></div>
                </div>
                <div class="portal-field">
                  <label>Foto van de schade (eventueel met toestel aan) <span class="portal-required">*</span></label>
                  <div class="portal-file-wrap"><input type="file" name="foto_schade" accept="image/*" required /></div>
                </div>
                <div class="portal-field">
                  <label>Foto achterkant (modelnummersticker afleesbaar) <span class="portal-required">*</span></label>
                  <div class="portal-file-wrap"><input type="file" name="foto_achterkant" accept="image/*" required /></div>
                </div>
                <div class="portal-field">
                  <label>Extra foto bijv. aankoopfactuur <span class="portal-form-section-optional">(optioneel)</span></label>
                  <div class="portal-file-wrap"><input type="file" name="foto_extra" accept="image/*" /></div>
                </div>
                <p class="portal-field-hint">Maximaal 10 MB per foto. Toegestane formaten: JPG, PNG, WebP.</p>
              </div>

              <div class="portal-form-actions">
                <button type="submit" class="portal-btn portal-btn--primary portal-btn--full">
                  Taxatieaanvraag indienen
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M12 5l7 7-7 7"/></svg>
                </button>
              </div>
            </form>
          </div>

        <!-- ── Actie: doorgestuurd → overig (fallback) ──────────── -->
        <?php elseif ($status === 'doorgestuurd'): ?>
          <div class="portal-action-card">
            <div class="portal-action-header">
              <div class="portal-action-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2"/></svg>
              </div>
              <div>
                <h3>Aanvullende gegevens nodig</h3>
                <p>Vul uw contactgegevens en foto's in om uw aanvraag te voltooien.</p>
              </div>
            </div>
            <form class="portal-form" method="POST" action="<?= BASE_URL ?>/api/aanvulling.php" enctype="multipart/form-data">
              <input type="hidden" name="csrf_token"  value="<?= csrf() ?>" />
              <input type="hidden" name="aanvraag_id" value="<?= (int)$inzending['id'] ?>" />
              <input type="hidden" name="casenummer"  value="<?= h($inzending['casenummer']) ?>" />

              <div class="portal-form-section">
                <div class="portal-form-section-title">Contactgegevens</div>
                <div class="portal-field">
                  <label>Naam</label>
                  <div class="portal-input-wrap<?= !empty($inzending['naam']) ? ' is-prefilled' : '' ?>">
                    <input type="text" name="naam" required value="<?= h($inzending['naam'] ?? '') ?>" <?= !empty($inzending['naam']) ? 'readonly' : '' ?> />
                    <?php if (!empty($inzending['naam'])): ?><span class="portal-input-lock" title="Automatisch ingevuld"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span><?php endif; ?>
                  </div>
                </div>
                <div class="portal-field">
                  <label>Telefoonnummer</label>
                  <div class="portal-input-wrap<?= !empty($inzending['telefoon']) ? ' is-prefilled' : '' ?>">
                    <input type="tel" name="telefoon" required value="<?= h($inzending['telefoon'] ?? '') ?>" <?= !empty($inzending['telefoon']) ? 'readonly' : '' ?> />
                    <?php if (!empty($inzending['telefoon'])): ?><span class="portal-input-lock" title="Automatisch ingevuld"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span><?php endif; ?>
                  </div>
                </div>
                <div class="portal-field">
                  <label>Adres (straat + huisnummer, postcode, stad)</label>
                  <div class="portal-input-wrap<?= !empty($inzending['adres']) ? ' is-prefilled' : '' ?>">
                    <input type="text" name="adres" required value="<?= h($inzending['adres'] ?? '') ?>" <?= !empty($inzending['adres']) ? 'readonly' : '' ?> />
                    <?php if (!empty($inzending['adres'])): ?><span class="portal-input-lock" title="Automatisch ingevuld"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span><?php endif; ?>
                  </div>
                </div>
              </div>

              <div class="portal-form-section">
                <div class="portal-form-section-title">Foto's uploaden</div>
                <div class="portal-field">
                  <label>Foto van het defect</label>
                  <div class="portal-file-wrap"><input type="file" name="foto_defect" accept="image/*" /></div>
                </div>
                <div class="portal-field">
                  <label>Foto van het label / serienummer (achterkant TV)</label>
                  <div class="portal-file-wrap"><input type="file" name="foto_label" accept="image/*" /></div>
                </div>
                <p class="portal-field-hint">Maximaal 10 MB per foto. Toegestane formaten: JPG, PNG, WebP.</p>
              </div>

              <div class="portal-form-actions">
                <button type="submit" class="portal-btn portal-btn--primary portal-btn--full">
                  Aanvulling indienen
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M12 5l7 7-7 7"/></svg>
                </button>
              </div>
            </form>
          </div>

        <!-- ── Actie: coulance traject ───────────────────────────── -->
        <?php elseif ($status === 'coulance'): ?>
          <div class="portal-action-card portal-action-card--warning">
            <div class="portal-action-header">
              <div class="portal-action-icon portal-action-icon--warning">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
              </div>
              <div>
                <h3>Coulancetraject</h3>
                <p>Neem contact op met de verkoper of fabrikant voor een coulanceverzoek.</p>
              </div>
            </div>
            <p class="portal-action-body">
              Leg uw situatie rustig uit en verwijs naar de wettelijke regels rondom consumentenkoop.
              Vermeld dat de televisie <?= h((int)(date('Y') - (int)($inzending['aanschafjaar'] ?? date('Y')))) ?> jaar oud is
              en een technisch defect heeft dat niet door uzelf is veroorzaakt.
            </p>
            <p class="portal-action-hint">
              Lukt het coulanceverzoek niet? Dan kunt u via onderstaande knop een reparatieaanvraag starten.
            </p>
            <form method="POST" action="<?= BASE_URL ?>/api/aanvulling.php">
              <input type="hidden" name="csrf_token"  value="<?= csrf() ?>" />
              <input type="hidden" name="aanvraag_id" value="<?= (int)$inzending['id'] ?>" />
              <input type="hidden" name="casenummer"  value="<?= h($inzending['casenummer']) ?>" />
              <input type="hidden" name="actie"       value="coulance_naar_reparatie" />
              <button type="submit" class="portal-btn portal-btn--warning">
                Coulance lukt niet — reparatieaanvraag starten
              </button>
            </form>
          </div>

        <!-- ── Actie: recycling traject ─────────────────────────── -->
        <?php elseif ($status === 'recycling'): ?>
          <div class="portal-action-card portal-action-card--purple">
            <div class="portal-action-header">
              <div class="portal-action-icon portal-action-icon--purple">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
              </div>
              <div>
                <h3>Recyclingverzoek indienen</h3>
                <p>Uw televisie komt in aanmerking voor verantwoorde recycling.</p>
              </div>
            </div>
            <form class="portal-form" method="POST" action="<?= BASE_URL ?>/api/aanvulling.php" enctype="multipart/form-data">
              <input type="hidden" name="csrf_token"  value="<?= csrf() ?>" />
              <input type="hidden" name="aanvraag_id" value="<?= (int)$inzending['id'] ?>" />
              <input type="hidden" name="casenummer"  value="<?= h($inzending['casenummer']) ?>" />
              <input type="hidden" name="actie"       value="recycling_aanvraag" />

              <div class="portal-form-section">
                <div class="portal-field">
                  <label>Naam</label>
                  <div class="portal-input-wrap<?= !empty($inzending['naam']) ? ' is-prefilled' : '' ?>">
                    <input type="text" name="naam" required value="<?= h($inzending['naam'] ?? '') ?>" <?= !empty($inzending['naam']) ? 'readonly' : '' ?> />
                    <?php if (!empty($inzending['naam'])): ?><span class="portal-input-lock" title="Automatisch ingevuld"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span><?php endif; ?>
                  </div>
                </div>
                <div class="portal-field">
                  <label>Telefoonnummer</label>
                  <div class="portal-input-wrap<?= !empty($inzending['telefoon']) ? ' is-prefilled' : '' ?>">
                    <input type="tel" name="telefoon" required value="<?= h($inzending['telefoon'] ?? '') ?>" <?= !empty($inzending['telefoon']) ? 'readonly' : '' ?> />
                    <?php if (!empty($inzending['telefoon'])): ?><span class="portal-input-lock" title="Automatisch ingevuld"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span><?php endif; ?>
                  </div>
                </div>
                <div class="portal-field">
                  <label>Ophaaladres (straat + huisnummer, postcode, stad)</label>
                  <div class="portal-input-wrap<?= !empty($inzending['adres']) ? ' is-prefilled' : '' ?>">
                    <input type="text" name="adres" required value="<?= h($inzending['adres'] ?? '') ?>" <?= !empty($inzending['adres']) ? 'readonly' : '' ?> />
                    <?php if (!empty($inzending['adres'])): ?><span class="portal-input-lock" title="Automatisch ingevuld"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span><?php endif; ?>
                  </div>
                </div>
              </div>

              <div class="portal-form-actions">
                <button type="submit" class="portal-btn portal-btn--purple portal-btn--full">
                  Recyclingverzoek indienen
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M12 5l7 7-7 7"/></svg>
                </button>
              </div>
            </form>
          </div>

        <!-- ── Status: ontvangen, wacht op beoordeling ──────────── -->
        <?php elseif ($status === 'inzending'): ?>
          <div class="portal-info-card portal-info-card--blue">
            <div class="portal-info-card-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35"/></svg>
            </div>
            <div>
              <h3>Uw aanvraag is ontvangen</h3>
              <p>Ons team beoordeelt uw aanvraag zo spoedig mogelijk. U ontvangt bericht zodra er een update is. Gemiddelde verwerkingstijd: <strong>1 werkdag</strong>.</p>
            </div>
          </div>

        <!-- ── Status: aanvraag volledig ingediend ──────────────── -->
        <?php elseif ($status === 'aanvraag'): ?>
          <div class="portal-info-card portal-info-card--green">
            <div class="portal-info-card-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
              <h3>Aanvraag volledig ontvangen</h3>
              <p>Uw aanvraag is volledig ontvangen en ligt bij ons team ter beoordeling. Wij nemen zo spoedig mogelijk contact met u op.</p>
            </div>
          </div>

        <!-- ── Status: behandeld / archief ──────────────────────── -->
        <?php elseif (in_array($status, ['behandeld', 'archief'])): ?>
          <div class="portal-info-card portal-info-card--green">
            <div class="portal-info-card-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div>
              <h3>Aanvraag afgehandeld</h3>
              <p>Uw aanvraag is <?= $status === 'archief' ? 'gearchiveerd' : 'behandeld' ?>. Heeft u nog vragen? Neem contact op met vermelding van uw casenummer <strong><?= h($inzending['casenummer']) ?></strong>.</p>
            </div>
          </div>
        <?php endif; ?>

        <!-- ── Berichten ─────────────────────────────────────────── -->
        <div class="portal-card portal-berichten-card">
          <div class="portal-berichten-header">
            <div class="portal-card-title">
              <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
              Berichten
            </div>
          </div>

          <?php if (empty($inzending['berichten'])): ?>
            <div class="portal-berichten-leeg">
              <div class="portal-berichten-leeg-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
              </div>
              <p>Nog geen berichten</p>
              <span>Zodra ons team een update stuurt, verschijnt die hier.</span>
            </div>
          <?php else: ?>
            <div class="portal-berichten-lijst">
              <?php foreach ($inzending['berichten'] as $bericht): ?>
                <?php $isKlant = ($bericht['afzender'] ?? 'team') === 'klant'; ?>
                <div class="portal-bericht <?= $isKlant ? 'bericht-klant' : 'bericht-team' ?>">
                  <div class="portal-bericht-meta">
                    <span class="portal-bericht-afzender"><?= $isKlant ? 'U' : 'Reparatieplatform' ?></span>
                    <span class="portal-bericht-tijd"><?= date('d-m-Y H:i', strtotime($bericht['aangemaakt'])) ?></span>
                  </div>
                  <div class="portal-bericht-tekst"><?= nl2br(h($bericht['bericht'])) ?></div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <!-- Invoerveld nieuw bericht -->
          <form class="portal-bericht-form" method="POST" action="<?= BASE_URL ?>/api/aanvulling.php">
            <input type="hidden" name="csrf_token"  value="<?= csrf() ?>" />
            <input type="hidden" name="aanvraag_id" value="<?= (int)$inzending['id'] ?>" />
            <input type="hidden" name="casenummer"  value="<?= h($inzending['casenummer']) ?>" />
            <input type="hidden" name="actie"       value="stuur_bericht" />
            <div class="portal-bericht-invoer-wrap">
              <textarea name="bericht" rows="2" class="portal-bericht-invoer"
                        placeholder="Stel een vraag of stuur een aanvulling..." required></textarea>
              <button type="submit" class="portal-bericht-verzend-btn" aria-label="Bericht versturen">
                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M22 2L11 13M22 2L15 22l-4-9-9-4 20-7z"/>
                </svg>
                <span>Verstuur</span>
              </button>
            </div>
          </form>
        </div>

      </div><!-- /.portal-main -->

      <!-- ── Zijbalk ──────────────────────────────────────────────── -->
      <div class="portal-sidebar">

        <!-- Tijdlijn -->
        <?php if (!empty($inzending['log'])): ?>
        <div class="portal-card">
          <div class="portal-card-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2"/></svg>
            Tijdlijn
          </div>
          <ul class="portal-timeline-list">
            <?php foreach (array_reverse($inzending['log']) as $le): ?>
              <li class="portal-timeline-item">
                <div class="portal-timeline-dot"></div>
                <div>
                  <div class="portal-timeline-time"><?= date('d-m H:i', strtotime($le['aangemaakt'])) ?></div>
                  <div class="portal-timeline-actie"><?= h($le['actie']) ?></div>
                  <?php if ($le['opmerking']): ?>
                    <div class="portal-timeline-opmerking"><?= h($le['opmerking']) ?></div>
                  <?php endif; ?>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
        <?php endif; ?>

        <!-- Aanvraagdetails -->
        <div class="portal-card">
          <div class="portal-card-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2"/></svg>
            Aanvraagdetails
          </div>
          <div class="portal-detail-row">
            <span>Casenummer</span>
            <strong><?= h($inzending['casenummer']) ?></strong>
          </div>
          <div class="portal-detail-row">
            <span>Merk</span>
            <strong><?= h($inzending['merk']) ?></strong>
          </div>
          <div class="portal-detail-row">
            <span>Modelnummer</span>
            <strong><?= h($inzending['modelnummer']) ?></strong>
          </div>
          <?php if (!empty($inzending['aanschafjaar'])): ?>
          <div class="portal-detail-row">
            <span>Aanschafjaar</span>
            <strong><?= h($inzending['aanschafjaar']) ?></strong>
          </div>
          <?php endif; ?>
          <?php if (!empty($inzending['klacht_type'])): ?>
          <div class="portal-detail-row">
            <span>Klachttype</span>
            <strong><?= h($inzending['klacht_type']) ?></strong>
          </div>
          <?php endif; ?>
          <?php if (!empty($inzending['geadviseerde_route'])): ?>
          <div class="portal-detail-row">
            <span>Adviesroute</span>
            <strong><?= h($inzending['geadviseerde_route']) ?></strong>
          </div>
          <?php endif; ?>
          <?php if (!empty($inzending['naam'])): ?>
          <div class="portal-detail-row">
            <span>Naam</span>
            <strong><?= h($inzending['naam']) ?></strong>
          </div>
          <?php endif; ?>
        </div>

        <?php if (!empty($inzending['omschrijving'])): ?>
        <div class="portal-card">
          <div class="portal-card-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
            Omschrijving defect
          </div>
          <p class="portal-omschrijving-tekst"><?= h($inzending['omschrijving']) ?></p>
        </div>
        <?php endif; ?>

        <div class="portal-card portal-hulp-card">
          <div class="portal-card-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            Hulp nodig?
          </div>
          <p>Neem contact op met vermelding van uw casenummer.</p>
          <a href="<?= BASE_URL ?>/contact.php" class="portal-hulp-link">
            Contact opnemen
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M12 5l7 7-7 7"/></svg>
          </a>
        </div>

      </div><!-- /.portal-sidebar -->

    </div><!-- /.portal-grid -->
  </div><!-- /.portal-wrap -->

<?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>