<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$statusLabels = [
    'inzending'            => ['tekst' => 'Ontvangen — wacht op beoordeling',  'css' => 's-inzending'],
    // Nieuwe *_afwachting statussen (wacht op formulier klant)
    'reparatie_afwachting' => ['tekst' => 'Aanvulling nodig',                  'css' => 's-doorgestuurd'],
    'taxatie_afwachting'   => ['tekst' => 'Aanvulling nodig',                  'css' => 's-doorgestuurd'],
    'garantie_afwachting'  => ['tekst' => 'Aanvulling nodig',                  'css' => 's-doorgestuurd'],
    'coulance_afwachting'  => ['tekst' => 'Aanvulling nodig',                  'css' => 's-doorgestuurd'],
    'recycling_afwachting' => ['tekst' => 'Aanvulling nodig',                  'css' => 's-doorgestuurd'],
    // Nieuwe *_ingevuld statussen (formulier ontvangen)
    'reparatie_ingevuld'   => ['tekst' => 'Aanvraag volledig ontvangen',       'css' => 's-aanvraag'],
    'taxatie_ingevuld'     => ['tekst' => 'Aanvraag volledig ontvangen',       'css' => 's-aanvraag'],
    'garantie_ingevuld'    => ['tekst' => 'Aanvraag volledig ontvangen',       'css' => 's-aanvraag'],
    'coulance_ingevuld'    => ['tekst' => 'Aanvraag volledig ontvangen',       'css' => 's-aanvraag'],
    'recycling_ingevuld'   => ['tekst' => 'Aanvraag volledig ontvangen',       'css' => 's-aanvraag'],
    'afgewezen'            => ['tekst' => 'Afgewezen',                          'css' => 's-archief'],
    'gesloten'             => ['tekst' => 'Gesloten',                           'css' => 's-archief'],
    // Legacy statussen
    'doorgestuurd'         => ['tekst' => 'Aanvulling nodig',                  'css' => 's-doorgestuurd'],
    'aanvraag'             => ['tekst' => 'Aanvraag volledig ontvangen',       'css' => 's-aanvraag'],
    'coulance'             => ['tekst' => 'Coulance traject',                  'css' => 's-coulance'],
    'recycling'            => ['tekst' => 'Recycling traject',                 'css' => 's-recycling'],
    'behandeld'            => ['tekst' => 'Behandeld',                         'css' => 's-behandeld'],
    'archief'              => ['tekst' => 'Gearchiveerd',                      'css' => 's-archief'],
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
    $portalRlKey = 'portal_' . (filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP) !== false
                    ? $_SERVER['REMOTE_ADDR'] : 'unknown');
    $portalRl    = rateLimitBekijk($portalRlKey);

    if ($portalRl['geblokkeerd']) {
        $loginFout = true;
        $melding   = 'Te veel mislukte pogingen. Probeer over enkele minuten opnieuw.';
        $meldingOk = false;
    } else {
        $cnIn = strtoupper(trim($_POST['casenummer_check'] ?? ''));
        $emIn = strtolower(trim($_POST['email_check']      ?? ''));
        if ($cnIn && $emIn && filter_var($emIn, FILTER_VALIDATE_EMAIL)) {
            $chk = db()->prepare('SELECT id FROM aanvragen WHERE casenummer = ? AND LOWER(email) = ?');
            $chk->execute([$cnIn, $emIn]);
            if ($chk->fetch()) {
                rateLimitReset($portalRlKey);
                $_SESSION['portal_case']  = $cnIn;
                $_SESSION['portal_email'] = $emIn;
                redirect(BASE_URL . '/mijn-aanvraag.php');
            } else {
                rateLimitMislukt($portalRlKey, 10, 300);
                $loginFout = true;
            }
        } else {
            $loginFout = true;
        }
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
    $afwachting = ['doorgestuurd','reparatie_afwachting','taxatie_afwachting',
                   'garantie_afwachting','coulance_afwachting','recycling_afwachting'];
    $ingevuld   = ['aanvraag','reparatie_ingevuld','taxatie_ingevuld',
                   'garantie_ingevuld','coulance_ingevuld','recycling_ingevuld'];
    $afgerond   = ['coulance','recycling','behandeld','archief','afgewezen','gesloten'];
    if ($status === 'inzending')        return 1;
    if (in_array($status, $afwachting)) return 2;
    if (in_array($status, $ingevuld))   return 3;
    if (in_array($status, $afgerond))   return 4;
    return 1;
}

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
  <?php require __DIR__ . '/includes/portal-login.php'; ?>
<?php else:
  $status   = $inzending['status'];
  $stapNr   = portalStapNr($status);
  $sl       = $statusLabels[$status] ?? ['tekst' => $status, 'css' => ''];
  $advType     = $inzending['gekozen_advies'] ?? $inzending['aanvraag_type'] ?? $inzending['advies_type'] ?? '';
  $isTaxatie   = $advType === 'taxatie';
  $isReparatie = $advType === 'reparatie';
  require __DIR__ . '/includes/portal-dashboard.php';
endif; ?>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>