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

// Hulpfunctie voor vergrendeld invoerveld met slotje
function lockedField(string $value, string $type = 'text'): string {
    $val = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>';
    return '<div class="portal-field-locked-wrap">'
         . '<input type="' . $type . '" value="' . $val . '" readonly class="portal-field-prefilled" />'
         . '<span class="portal-field-lock-icon" aria-hidden="true">' . $svg . '</span>'
         . '</div>';
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

<div style="background:var(--surface);padding-top:2.5rem;padding-bottom:.5rem;">

<?php if (!$inzending): ?>
  <!-- ── LOGIN FORMULIER ──────────────────────────────────────── -->
  <div class="portal-login-wrap">
    <div class="portal-login-card">
      <div class="portal-login-icon">🔐</div>
      <h2>Aanvraag inzien</h2>
      <p class="lead">Voer uw casenummer en e-mailadres in om uw persoonlijke klantenomgeving te openen.</p>

      <?php if ($loginFout): ?>
        <div class="portal-alert alert-error">
          <span>⚠</span>
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
        <button type="submit" class="portal-login-btn">Mijn aanvraag bekijken &rarr;</button>
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
  $advType     = $inzending['advies_type'] ?? '';
  $isTaxatie   = $advType === 'taxatie';
  $isReparatie = $advType === 'reparatie';
?>

  <!-- ── PORTAL DASHBOARD ─────────────────────────────────────── -->
  <div class="portal-wrap">

    <!-- Top bar -->
    <div class="portal-top-bar">
      <div>
        <div class="portal-case-title">
          Aanvraag <?= h($inzending['casenummer']) ?>
          <span class="portal-status-badge <?= $sl['css'] ?>"><?= h($sl['tekst']) ?></span>
        </div>
        <div class="portal-case-sub">
          <?= h($inzending['merk'] . ' ' . $inzending['modelnummer']) ?>
          &nbsp;&bull;&nbsp; <?= h($inzending['klacht_type'] ?? '') ?>
        </div>
      </div>
      <a href="?uitloggen=1" class="portal-logout">← Uitloggen</a>
    </div>

    <!-- Melding -->
    <?php if ($melding): ?>
      <div class="portal-alert <?= $meldingOk ? 'alert-success' : 'alert-error' ?>">
        <span><?= $meldingOk ? '✓' : '⚠' ?></span>
        <span><?= h($melding) ?></span>
      </div>
    <?php endif; ?>

    <!-- Status stappen -->
    <div class="portal-status-steps">
      <div class="portal-status-steps-title">Voortgang</div>
      <div class="status-steps-track">

        <div class="status-step <?= $stapNr >= 1 ? ($stapNr === 1 ? 'active' : 'done') : '' ?>">
          <div class="status-step-dot"><?= $stapNr > 1 ? '✓' : '1' ?></div>
          <div class="status-step-label">Ontvangen</div>
        </div>

        <div class="status-step-connector <?= $stapNr >= 2 ? 'done' : '' ?>"></div>

        <div class="status-step <?= $stapNr >= 2 ? ($stapNr === 2 ? 'active' : 'done') : '' ?>">
          <div class="status-step-dot"><?= $stapNr > 2 ? '✓' : '2' ?></div>
          <div class="status-step-label">In behandeling</div>
        </div>

        <div class="status-step-connector <?= $stapNr >= 3 ? 'done' : '' ?>"></div>

        <div class="status-step <?= $stapNr >= 3 ? ($stapNr === 3 ? 'active' : 'done') : '' ?>">
          <div class="status-step-dot"><?= $stapNr > 3 ? '✓' : '3' ?></div>
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

        <!-- ── Actie: aanvulling nodig (doorgestuurd) ───────────── -->
        <?php if ($status === 'doorgestuurd'): ?>

          <?php if ($isReparatie): ?>
          <div class="portal-action-card">
            <div class="portal-action-header">
              <div class="portal-action-icon">🔧</div>
              <div>
                <h3>Reparatieaanvraag</h3>
                <p>Vul uw gegevens aan om de reparatieaanvraag te voltooien.</p>
              </div>
            </div>
            <form class="portal-form" method="POST" action="<?= BASE_URL ?>/api/aanvulling.php" enctype="multipart/form-data">
              <input type="hidden" name="csrf_token"  value="<?= csrf() ?>" />
              <input type="hidden" name="aanvraag_id" value="<?= (int)$inzending['id'] ?>" />
              <input type="hidden" name="casenummer"  value="<?= h($inzending['casenummer']) ?>" />
              <input type="hidden" name="type"        value="reparatie" />

              <div class="portal-form-section">Contactgegevens</div>
              <div class="portal-fields-row">
                <div class="portal-field">
                  <label>Naam *</label>
                  <input type="text" name="naam" required value="<?= h($inzending['naam'] ?? '') ?>" />
                </div>
                <div class="portal-field">
                  <label>E-mail</label>
                  <?= lockedField($inzending['email'], 'email') ?>
                </div>
              </div>
              <div class="portal-fields-row">
                <div class="portal-field">
                  <label>Plaats *</label>
                  <input type="text" name="plaats" required value="<?= h($inzending['plaats'] ?? '') ?>" />
                </div>
                <div class="portal-field">
                  <label>Telefoon *</label>
                  <input type="tel" name="telefoon" required value="<?= h($inzending['telefoon'] ?? '') ?>" />
                </div>
              </div>

              <div class="portal-form-section">Televisie</div>
              <div class="portal-fields-row">
                <div class="portal-field">
                  <label>Merk televisie</label>
                  <?= lockedField($inzending['merk']) ?>
                </div>
                <div class="portal-field">
                  <label>Model televisie</label>
                  <?= lockedField($inzending['modelnummer']) ?>
                </div>
              </div>

              <div class="portal-form-section">Klacht</div>
              <div class="portal-field">
                <label>Klachtomschrijving *</label>
                <textarea name="omschrijving" required rows="4"><?= h($inzending['omschrijving'] ?? '') ?></textarea>
              </div>

              <div class="portal-form-section">Foto's <em class="portal-form-optional">(niet verplicht)</em></div>
              <div class="portal-fields-row">
                <div class="portal-field">
                  <label>Foto van de klacht</label>
                  <span class="portal-field-hint">Foto van het defect (optioneel).</span>
                  <input type="file" name="foto_defect" accept="image/*" />
                </div>
                <div class="portal-field">
                  <label>Foto van het modelnummer</label>
                  <span class="portal-field-hint">Foto van het modelnummersticker (optioneel).</span>
                  <input type="file" name="foto_label" accept="image/*" />
                </div>
              </div>
              <p class="portal-upload-hint">Maximaal 10 MB per foto. Toegestane formaten: JPG, PNG, WebP.</p>

              <button type="submit" class="portal-submit-btn">Reparatieaanvraag indienen &rarr;</button>
            </form>
          </div>

          <?php elseif ($isTaxatie): ?>
          <div class="portal-action-card">
            <div class="portal-action-header">
              <div class="portal-action-icon">📋</div>
              <div>
                <h3>Taxatieaanvraag</h3>
                <p>Vul uw gegevens aan om de taxatieaanvraag te voltooien.</p>
              </div>
            </div>
            <form class="portal-form" method="POST" action="<?= BASE_URL ?>/api/aanvulling.php" enctype="multipart/form-data">
              <input type="hidden" name="csrf_token"  value="<?= csrf() ?>" />
              <input type="hidden" name="aanvraag_id" value="<?= (int)$inzending['id'] ?>" />
              <input type="hidden" name="casenummer"  value="<?= h($inzending['casenummer']) ?>" />
              <input type="hidden" name="type"        value="taxatie" />

              <div class="portal-form-section">Contactgegevens</div>
              <div class="portal-field">
                <label>Voor- en achternaam *</label>
                <input type="text" name="naam" required value="<?= h($inzending['naam'] ?? '') ?>" />
              </div>
              <div class="portal-field">
                <label>Adres *</label>
                <input type="text" name="adres" required placeholder="Straat + huisnummer" value="<?= h($inzending['adres'] ?? '') ?>" />
              </div>
              <div class="portal-fields-row">
                <div class="portal-field">
                  <label>Postcode *</label>
                  <input type="text" name="postcode" required value="<?= h($inzending['postcode'] ?? '') ?>" />
                </div>
                <div class="portal-field">
                  <label>Plaats *</label>
                  <input type="text" name="plaats" required value="<?= h($inzending['plaats'] ?? '') ?>" />
                </div>
              </div>
              <div class="portal-fields-row">
                <div class="portal-field">
                  <label>E-mail</label>
                  <?= lockedField($inzending['email'], 'email') ?>
                </div>
                <div class="portal-field">
                  <label>Telefoonnummer *</label>
                  <input type="tel" name="telefoon" required value="<?= h($inzending['telefoon'] ?? '') ?>" />
                </div>
              </div>

              <div class="portal-form-section">Televisie</div>
              <div class="portal-fields-row">
                <div class="portal-field">
                  <label>Merk TV</label>
                  <?= lockedField($inzending['merk']) ?>
                </div>
                <div class="portal-field">
                  <label>Modelnummer</label>
                  <?= lockedField($inzending['modelnummer']) ?>
                </div>
              </div>
              <div class="portal-field">
                <label>Serienummer *</label>
                <input type="text" name="serienummer" required value="<?= h($inzending['serienummer'] ?? '') ?>" />
              </div>

              <div class="portal-form-section">Schade</div>
              <div class="portal-field">
                <label>Reden schade *</label>
                <div class="portal-radio-group">
                  <?php foreach (['Iets tegen scherm gekomen','De TV is gevallen','Water/vochtschade','Anders'] as $opt): ?>
                    <label class="portal-radio-label">
                      <input type="radio" name="reden_schade" value="<?= h($opt) ?>" required
                             <?= ($inzending['reden_schade'] ?? '') === $opt ? 'checked' : '' ?> />
                      <?= h($opt) ?>
                    </label>
                  <?php endforeach; ?>
                </div>
              </div>
              <div class="portal-field">
                <label>Beschrijving <em class="portal-form-optional">(optioneel)</em></label>
                <textarea name="beschrijving" rows="3"><?= h($inzending['omschrijving'] ?? '') ?></textarea>
              </div>

              <div class="portal-form-section">Aankoop &amp; Verzekering</div>
              <div class="portal-fields-row">
                <div class="portal-field">
                  <label>Aankoopbedrag *</label>
                  <input type="text" name="aankoopbedrag" required placeholder="Bijv. 799,00" value="<?= h($inzending['aankoopbedrag'] ?? '') ?>" />
                </div>
                <div class="portal-field">
                  <label>Aankoopdatum *</label>
                  <input type="date" name="aankoopdatum" required value="<?= h($inzending['aankoopdatum'] ?? date('Y-m-d')) ?>" />
                </div>
              </div>
              <div class="portal-field portal-field-check">
                <label class="portal-checkbox-label">
                  <input type="checkbox" name="heeft_bon" value="1" <?= !empty($inzending['heeft_bon']) ? 'checked' : '' ?> />
                  Ik heb een bon / aankoopbewijs
                </label>
              </div>
              <div class="portal-fields-row">
                <div class="portal-field">
                  <label>Naam verzekeringsmaatschappij *</label>
                  <input type="text" name="naam_verzekeraar" required value="<?= h($inzending['naam_verzekeraar'] ?? '') ?>" />
                </div>
                <div class="portal-field">
                  <label>Polisnummer *</label>
                  <input type="text" name="polisnummer" required value="<?= h($inzending['polisnummer'] ?? '') ?>" />
                </div>
              </div>

              <div class="portal-form-section">Foto's</div>
              <div class="portal-fields-row">
                <div class="portal-field">
                  <label>Foto van het gehele toestel *</label>
                  <input type="file" name="foto_toestel" accept="image/*" required />
                </div>
                <div class="portal-field">
                  <label>Foto van de schade (eventueel met toestel aan) *</label>
                  <input type="file" name="foto_defect" accept="image/*" required />
                </div>
              </div>
              <div class="portal-fields-row">
                <div class="portal-field">
                  <label>Foto achterkant (modelnummersticker afleesbaar) *</label>
                  <input type="file" name="foto_label" accept="image/*" required />
                </div>
                <div class="portal-field">
                  <label>Extra foto (bv. aankoopfactuur) <em class="portal-form-optional">(optioneel)</em></label>
                  <input type="file" name="foto_extra" accept="image/*" />
                </div>
              </div>
              <p class="portal-upload-hint">Maximaal 10 MB per foto. Toegestane formaten: JPG, PNG, WebP.</p>

              <button type="submit" class="portal-submit-btn">Taxatieaanvraag indienen &rarr;</button>
            </form>
          </div>

          <?php else: ?>
          <div class="portal-action-card">
            <div class="portal-action-header">
              <div class="portal-action-icon">📋</div>
              <div>
                <h3>Aanvullende gegevens nodig</h3>
                <p>Vul uw contactgegevens in om uw aanvraag te voltooien.</p>
              </div>
            </div>
            <form class="portal-form" method="POST" action="<?= BASE_URL ?>/api/aanvulling.php" enctype="multipart/form-data">
              <input type="hidden" name="csrf_token"  value="<?= csrf() ?>" />
              <input type="hidden" name="aanvraag_id" value="<?= (int)$inzending['id'] ?>" />
              <input type="hidden" name="casenummer"  value="<?= h($inzending['casenummer']) ?>" />
              <div class="portal-field">
                <label>Naam *</label>
                <input type="text" name="naam" required value="<?= h($inzending['naam'] ?? '') ?>" />
              </div>
              <div class="portal-field">
                <label>Telefoonnummer *</label>
                <input type="tel" name="telefoon" required value="<?= h($inzending['telefoon'] ?? '') ?>" />
              </div>
              <div class="portal-field">
                <label>Adres (straat + huisnummer, postcode, stad) *</label>
                <input type="text" name="adres" required value="<?= h($inzending['adres'] ?? '') ?>" />
              </div>
              <button type="submit" class="portal-submit-btn">Aanvraag indienen &rarr;</button>
            </form>
          </div>
          <?php endif; ?>

        <!-- ── Actie: coulance traject ───────────────────────────── -->
        <?php elseif ($status === 'coulance'): ?>
          <div class="portal-action-card card-warning">
            <div class="portal-action-header">
              <div class="portal-action-icon icon-warning">🤝</div>
              <div>
                <h3>Coulancetraject</h3>
                <p>Neem contact op met de verkoper of fabrikant voor een coulanceverzoek.</p>
              </div>
            </div>
            <p style="font-size:.9rem;color:#374151;margin-bottom:1rem;line-height:1.65;">
              Leg uw situatie rustig uit en verwijs naar de wettelijke regels rondom consumentenkoop.
              Vermeld dat de televisie <?= h((int)(date('Y') - (int)($inzending['aanschafjaar'] ?? date('Y')))) ?> jaar oud is
              en een technisch defect heeft dat niet door uzelf is veroorzaakt.
            </p>
            <p style="font-size:.85rem;color:var(--muted);margin-bottom:1.5rem;">
              Lukt het coulanceverzoek niet? Dan kunt u via onderstaande knop een reparatieaanvraag starten.
            </p>
            <form method="POST" action="<?= BASE_URL ?>/api/aanvulling.php">
              <input type="hidden" name="csrf_token"  value="<?= csrf() ?>" />
              <input type="hidden" name="aanvraag_id" value="<?= (int)$inzending['id'] ?>" />
              <input type="hidden" name="casenummer"  value="<?= h($inzending['casenummer']) ?>" />
              <input type="hidden" name="actie"       value="coulance_naar_reparatie" />
              <button type="submit" class="portal-submit-btn btn-warning">
                Coulance lukt niet — reparatieaanvraag starten
              </button>
            </form>
          </div>

        <!-- ── Actie: recycling traject ─────────────────────────── -->
        <?php elseif ($status === 'recycling'): ?>
          <div class="portal-action-card card-purple">
            <div class="portal-action-header">
              <div class="portal-action-icon icon-purple">♻</div>
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
              <div class="portal-field">
                <label>Naam *</label>
                <input type="text" name="naam" required value="<?= h($inzending['naam'] ?? '') ?>" />
              </div>
              <div class="portal-field">
                <label>Telefoonnummer *</label>
                <input type="tel" name="telefoon" required value="<?= h($inzending['telefoon'] ?? '') ?>" />
              </div>
              <div class="portal-field">
                <label>Ophaaladres (straat + huisnummer, postcode, stad) *</label>
                <input type="text" name="adres" required value="<?= h($inzending['adres'] ?? '') ?>" />
              </div>
              <button type="submit" class="portal-submit-btn btn-purple">
                Recyclingverzoek indienen &rarr;
              </button>
            </form>
          </div>

        <!-- ── Status: ontvangen, wacht op beoordeling ──────────── -->
        <?php elseif ($status === 'inzending'): ?>
          <div class="portal-info-card info-blue">
            <h3>🔍 Uw aanvraag is ontvangen</h3>
            <p>Ons team beoordeelt uw aanvraag zo spoedig mogelijk. U ontvangt bericht zodra er een update is. Gemiddelde verwerkingstijd: 1 werkdag.</p>
          </div>

        <!-- ── Status: aanvraag volledig ingediend ──────────────── -->
        <?php elseif ($status === 'aanvraag'): ?>
          <div class="portal-info-card">
            <h3>✓ Aanvraag volledig ontvangen</h3>
            <p>Uw aanvraag is volledig ontvangen en ligt bij ons team ter beoordeling. Wij nemen zo spoedig mogelijk contact met u op.</p>
          </div>

        <!-- ── Status: behandeld / archief ──────────────────────── -->
        <?php elseif (in_array($status, ['behandeld', 'archief'])): ?>
          <div class="portal-info-card info-done">
            <h3>✅ Aanvraag afgehandeld</h3>
            <p>Uw aanvraag is <?= $status === 'archief' ? 'gearchiveerd' : 'behandeld' ?>. Heeft u nog vragen? Neem contact op met vermelding van uw casenummer <strong><?= h($inzending['casenummer']) ?></strong>.</p>
          </div>
        <?php endif; ?>

        <!-- ── Berichten ─────────────────────────────────────────── -->
        <div class="portal-card portal-berichten" id="berichten-sectie">
          <div class="portal-card-title">Berichten</div>

          <?php if (empty($inzending['log'])): ?>
            <p class="berichten-leeg">Nog geen berichten. Stel gerust een vraag hieronder.</p>
          <?php else: ?>
            <div class="berichten-lijst" id="berichten-lijst">
              <?php
              $chatDatum = null;
              foreach ($inzending['log'] as $le):
                $le_datum       = date('Y-m-d', strtotime($le['aangemaakt']));
                $door           = $le['gedaan_door'];
                $isKlant        = $door === 'klant';
                $isAdmin        = $door === 'admin';
                $heeftBericht   = !empty(trim($le['opmerking'] ?? ''));
                $isBericht      = str_starts_with(strtolower($le['actie']), 'bericht');
                $isKlantBericht = $isKlant && ($heeftBericht || $isBericht);
                $isAdminBericht = $isAdmin && ($heeftBericht || $isBericht);

                if ($le_datum !== $chatDatum):
                  $chatDatum  = $le_datum;
                  $vandaag    = date('Y-m-d');
                  $gisteren   = date('Y-m-d', strtotime('-1 day'));
                  $datumLabel = $le_datum === $vandaag ? 'Vandaag'
                              : ($le_datum === $gisteren ? 'Gisteren'
                              : date('d F Y', strtotime($le['aangemaakt'])));
              ?>
                <div class="bericht-datum-separator"><?= $datumLabel ?></div>
              <?php endif; ?>

                <?php if (!$isKlantBericht && !$isAdminBericht): ?>
                  <div class="bericht-systeem">
                    <span class="bericht-systeem-label">
                      <span class="bericht-systeem-tekst"><?= h($le['actie']) ?></span>
                      <span class="bericht-systeem-tijd"><?= date('H:i', strtotime($le['aangemaakt'])) ?></span>
                    </span>
                  </div>
                <?php else: ?>
                  <div class="bericht-rij <?= $isKlant ? 'bericht-rij-klant' : 'bericht-rij-admin' ?>">
                    <?php if ($isAdmin): ?><div class="bericht-avatar bericht-avatar-admin">R</div><?php endif; ?>
                    <div class="bericht-bubble">
                      <div class="bericht-inhoud"><?= $heeftBericht ? nl2br(h($le['opmerking'])) : '<em style="opacity:.55;font-size:.82em;">Berichtinhoud niet beschikbaar</em>' ?></div>
                      <div class="bericht-meta">
                        <span class="bericht-meta-naam"><?= $isKlant ? 'U' : 'Reparatieplatform' ?></span>
                        <span><?= date('H:i', strtotime($le['aangemaakt'])) ?></span>
                      </div>
                    </div>
                    <?php if ($isKlant): ?><div class="bericht-avatar bericht-avatar-klant">U</div><?php endif; ?>
                  </div>
                <?php endif; ?>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <?php if (!in_array($status, ['behandeld', 'archief'])): ?>
          <form class="bericht-invoer-form" method="POST" action="<?= BASE_URL ?>/api/aanvulling.php">
            <input type="hidden" name="csrf_token"  value="<?= csrf() ?>" />
            <input type="hidden" name="aanvraag_id" value="<?= (int)$inzending['id'] ?>" />
            <input type="hidden" name="casenummer"  value="<?= h($inzending['casenummer']) ?>" />
            <input type="hidden" name="actie"       value="bericht" />
            <div class="bericht-invoer-wrap">
              <textarea name="bericht_tekst" id="bericht-invoer" class="bericht-invoer"
                        placeholder="Typ een bericht of vraag..." rows="1"></textarea>
              <button type="submit" class="bericht-verstuur-btn" title="Versturen">
                <svg viewBox="0 0 24 24"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg>
              </button>
            </div>
          </form>
          <?php endif; ?>
        </div>

      </div><!-- /.portal-main -->

      <!-- ── Zijbalk: aanvraagdetails ────────────────────────────── -->
      <div class="portal-sidebar">
        <div class="portal-card">
          <div class="portal-card-title">Aanvraagdetails</div>
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
          <div class="portal-card-title">Omschrijving defect</div>
          <p style="font-size:.875rem;color:#374151;line-height:1.65;"><?= h($inzending['omschrijving']) ?></p>
        </div>
        <?php endif; ?>

        <?php if (!empty($inzending['log'])): ?>
        <div class="portal-card">
          <div class="portal-card-title">Tijdlijn</div>
          <ul class="portal-timeline-list">
            <?php foreach (array_reverse($inzending['log']) as $le): ?>
              <li class="portal-timeline-item">
                <div class="portal-timeline-dot"></div>
                <div>
                  <div class="portal-timeline-time"><?= date('d-m H:i', strtotime($le['aangemaakt'])) ?></div>
                  <div class="portal-timeline-actie"><?= h($le['actie']) ?></div>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
        <?php endif; ?>

        <div class="portal-card" style="background:#f8fafc;">
          <div class="portal-card-title">Hulp nodig?</div>
          <p style="font-size:.85rem;color:var(--muted);margin-bottom:.85rem;line-height:1.5;">Neem contact op met vermelding van uw casenummer.</p>
          <a href="<?= BASE_URL ?>/contact.php" style="font-size:.9rem;font-weight:600;color:#287864;text-decoration:none;">✉ Contact opnemen &rarr;</a>
        </div>
      </div>

    </div><!-- /.portal-grid -->
  </div><!-- /.portal-wrap -->

  <script>
  // Scroll berichten automatisch naar beneden bij laden
  (function() {
    var lijst = document.getElementById('berichten-lijst');
    if (lijst) lijst.scrollTop = lijst.scrollHeight;

    // Auto-resize textarea bericht-invoer
    var invoer = document.getElementById('bericht-invoer');
    if (invoer) {
      invoer.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
      });
    }
  })();
  </script>

<?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>