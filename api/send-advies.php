<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf($_POST['csrf_token'] ?? '')) {
    redirect('/?error=csrf');
}

$merk              = trim($_POST['merk']              ?? '');
$modelnummer       = trim($_POST['modelnummer']       ?? '');
$aanschafjaar      = trim($_POST['aanschafjaar']      ?? '');
$aanschafwaarde    = trim($_POST['aanschafwaarde']    ?? '');
$aankoop_locatie   = trim($_POST['aankoop_locatie']   ?? 'nl');
$verkoper_failliet = isset($_POST['verkoper_failliet']) ? 1 : 0;
$situatie          = trim($_POST['situatie']          ?? '');
$klacht_type       = trim($_POST['klacht_type']       ?? '');
$omschrijving      = trim($_POST['omschrijving']      ?? '');
$email             = filter_var(trim($_POST['email']  ?? ''), FILTER_VALIDATE_EMAIL);
$geadviseerde_route= trim($_POST['geadviseerde_route']?? '');
$coulance_kans     = (int) ($_POST['coulance_kans']   ?? 0);

if (!$email || !$merk || !$modelnummer) {
    redirect('/advies.php?error=2');
}

// ── Sla op in DB ────────────────────────────────────────────────
$token = bin2hex(random_bytes(32));

try {
    $stmt = db()->prepare('
        INSERT INTO aanvragen
          (merk, modelnummer, aanschafjaar, aanschafwaarde, aankoop_locatie,
           verkoper_failliet, situatie, klacht_type, omschrijving, email,
           geadviseerde_route, coulance_kans, ip, token)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        $merk, $modelnummer, $aanschafjaar, $aanschafwaarde, $aankoop_locatie,
        $verkoper_failliet, $situatie, $klacht_type, $omschrijving, $email,
        $geadviseerde_route, $coulance_kans, $_SERVER['REMOTE_ADDR'], $token
    ]);
} catch (\PDOException $e) {
    // Kolommen bestaan nog niet: fallback naar oude structuur
    $stmt = db()->prepare('
        INSERT INTO aanvragen (merk, modelnummer, aanschafjaar, klacht_type, omschrijving, email, ip, token)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([$merk, $modelnummer, $aanschafjaar, $klacht_type, $omschrijving, $email, $_SERVER['REMOTE_ADDR'], $token]);
}

// ── Route-label voor e-mails ─────────────────────────────────────
$routeLabels = [
    'garantie'    => 'Garantie aanspreken bij verkoper / fabrikant',
    'coulance'    => 'Coulanceregeling (kans: ' . $coulance_kans . '%)',
    'reparatie'   => 'Reparatie aan huis',
    'taxatie'     => 'Taxatierapport voor verzekeraar',
    'second-life' => 'Second life: doorverkoop of recycling',
    ''            => 'Nog te bepalen door specialist',
];
$routeLabel = $routeLabels[$geadviseerde_route] ?? 'Nog te bepalen door specialist';

// Bijzondere situaties aanvullen
$bijzonder = [];
if ($aankoop_locatie === 'buitenland') $bijzonder[] = 'Buiten Nederland aangekocht (afwijkende garantieregels)';
if ($verkoper_failliet)               $bijzonder[] = 'Verkoper is failliet gegaan (uitzonderingspositie garantie)';
$bijzonderTxt = $bijzonder ? implode("\n  - ", ['Bijzondere omstandigheden:', ...$bijzonder]) : '';

// ── E-mail aan klant ─────────────────────────────────────────────
$klantMail = "Bedankt voor uw aanvraag!\n\n"
    . "Wij hebben uw gegevens ontvangen en sturen u zo snel mogelijk (binnen 1 werkdag) een persoonlijk advies.\n\n"
    . "=== UW AANVRAAG ===\n"
    . "Televisie   : $merk $modelnummer\n"
    . "Aanschafjaar: $aanschafjaar\n"
    . "Situatie    : " . ($situatie === 'schade' ? 'Externe schade' : 'Technisch defect') . "\n"
    . "Klacht      : $klacht_type\n\n"
    . "=== VOORLOPIG ADVIES ===\n"
    . "Op basis van uw antwoorden lijkt de meest passende route:\n"
    . "  $routeLabel\n\n"
    . ($bijzonderTxt ? $bijzonderTxt . "\n\n" : '')
    . "Let op: dit is een indicatie. Een specialist beoordeelt uw aanvraag en stelt het definitieve advies op.\n\n"
    . "Met vriendelijke groet,\nReparatieplatform.nl\nonderdeel van TV Reparatie Service Nederland";

mail(
    $email,
    'Uw adviesaanvraag is ontvangen \u2013 Reparatieplatform.nl',
    $klantMail,
    "From: noreply@reparatieplatform.nl\r\nContent-Type: text/plain; charset=UTF-8"
);

// ── E-mail aan admin ─────────────────────────────────────────────
$adminMail = "Nieuwe adviesaanvraag ontvangen\n\n"
    . "=== KLANTGEGEVENS ===\n"
    . "E-mail: $email\n"
    . "TV    : $merk $modelnummer\n\n"
    . "=== SITUATIE ===\n"
    . "Type situatie   : " . ($situatie === 'schade' ? 'SCHADE (externe oorzaak)' : 'STORING (technisch defect)') . "\n"
    . "Aanschafjaar    : $aanschafjaar\n"
    . "Aanschafwaarde  : $aanschafwaarde\n"
    . "Aankoop locatie : $aankoop_locatie\n"
    . "Verkoper failliet: " . ($verkoper_failliet ? 'JA' : 'Nee') . "\n"
    . "Klachttype      : $klacht_type\n\n"
    . "=== ROUTING ENGINE OUTPUT ===\n"
    . "Geadviseerde route: $geadviseerde_route\n"
    . "Routelabel        : $routeLabel\n"
    . ($coulance_kans ? "Coulance kans     : {$coulance_kans}%\n" : '')
    . ($bijzonderTxt ? "\n$bijzonderTxt\n" : '')
    . "\n=== OMSCHRIJVING KLANT ===\n$omschrijving\n\n"
    . "Behandelen via:\nhttps://reparatieplatform.nl/admin/aanvragen.php";

mail(
    'info@tvreparatieservicenederland.nl',
    '[' . strtoupper($geadviseerde_route ?: 'nieuw') . "] $merk $modelnummer \u2013 $email",
    $adminMail,
    "From: noreply@reparatieplatform.nl\r\nContent-Type: text/plain; charset=UTF-8"
);

redirect('/advies.php?verzonden=1');
