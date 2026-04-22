<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf($_POST['csrf_token'] ?? '')) {
    redirect(BASE_URL . '/?error=csrf');
}

$merk              = trim($_POST['merk']              ?? '');
$modelnummer       = trim($_POST['modelnummer']       ?? '');
$aanschafjaar      = trim($_POST['aanschafjaar']      ?? '');
$aanschafwaarde    = trim($_POST['aanschafwaarde']    ?? '');
$aankoop_locatie   = trim($_POST['aankoop_locatie']   ?? 'nl');
$situatie          = trim($_POST['situatie']          ?? '');
$klacht_type       = trim($_POST['klacht_type']       ?? '');
$omschrijving      = trim($_POST['omschrijving']      ?? '');
$email             = filter_var(trim($_POST['email']  ?? ''), FILTER_VALIDATE_EMAIL);
$geadviseerde_route= trim($_POST['geadviseerde_route']?? '');
$coulance_kans     = (int) ($_POST['coulance_kans']   ?? 0);
$model_repareerbaar= trim($_POST['model_repareerbaar']?? '');

if (!$email || !$merk || !$modelnummer) {
    redirect(BASE_URL . '/advies.php?error=2');
}

// ── Genereer casenummer (YYYY-MM-NNNN, NNNN start bij 1000) ────
$jaar   = date('Y');
$maand  = date('m');
$prefix = $jaar . '-' . $maand . '-';
$stmt   = db()->prepare(
    "SELECT MAX(CAST(SUBSTRING_INDEX(casenummer, '-', -1) AS UNSIGNED))
       FROM aanvragen WHERE casenummer LIKE ?"
);
$stmt->execute([$prefix . '%']);
$maxNr      = (int) $stmt->fetchColumn();
$volgnummer = max(1000, $maxNr + 1);
$casenummer = $prefix . $volgnummer;

// ── Sla op in DB ────────────────────────────────────────────────
$token = bin2hex(random_bytes(32));

try {
    $ins = db()->prepare('
        INSERT INTO aanvragen
          (casenummer, merk, modelnummer, aanschafjaar, aanschafwaarde, aankoop_locatie,
           situatie, klacht_type, omschrijving, email,
           geadviseerde_route, coulance_kans, model_repareerbaar, ip, token, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'inzending\')
    ');
    $ins->execute([
        $casenummer, $merk, $modelnummer, $aanschafjaar, $aanschafwaarde, $aankoop_locatie,
        $situatie, $klacht_type, $omschrijving, $email,
        $geadviseerde_route, $coulance_kans, $model_repareerbaar,
        $_SERVER['REMOTE_ADDR'], $token,
    ]);
} catch (\PDOException $e) {
    // Fallback voor oudere tabelstructuur zonder nieuwe kolommen
    $ins = db()->prepare('
        INSERT INTO aanvragen (merk, modelnummer, aanschafjaar, klacht_type, omschrijving, email, ip, token)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $ins->execute([$merk, $modelnummer, $aanschafjaar, $klacht_type, $omschrijving, $email,
                   $_SERVER['REMOTE_ADDR'], $token]);
    $casenummer = null;
}

$aanvraagId = db()->lastInsertId();

// ── Eerste log-entry ─────────────────────────────────────────────
if ($aanvraagId) {
    try {
        db()->prepare(
            'INSERT INTO aanvragen_log (aanvraag_id, actie, opmerking, gedaan_door) VALUES (?, ?, ?, ?)'
        )->execute([
            $aanvraagId,
            'Inzending ontvangen via stappenplan',
            "Route: $geadviseerde_route" . ($coulance_kans ? " (coulance: {$coulance_kans}%)" : ''),
            'systeem',
        ]);
    } catch (\PDOException $e) { /* log-tabel nog niet aangemaakt */ }
}

// ── E-mailnotificaties ────────────────────────────────────────────
$mailVars = [
    'casenummer'         => $casenummer ?? 'onbekend',
    'merk'               => $merk,
    'modelnummer'        => $modelnummer,
    'aanschafjaar'       => $aanschafjaar,
    'aanschafwaarde'     => $aanschafwaarde,
    'situatie'           => $situatie,
    'klacht_type'        => $klacht_type,
    'omschrijving'       => $omschrijving ?: '—',
    'email'              => $email,
    'geadviseerde_route' => $geadviseerde_route ?: 'Onbekend',
    'coulance_kans'      => $coulance_kans ? $coulance_kans . '%' : '',
    'advies_toelichting' => '',
];

// Bevestiging naar inzender
if ($email && $casenummer) {
    @sendMail($email, 'inzender_bevestiging', $mailVars);
}

// Notificatie naar alle admins met een e-mailadres
try {
    $adminEmails = db()->query("SELECT email FROM admins WHERE email IS NOT NULL AND email != ''")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($adminEmails as $adminEmail) {
        @sendMail($adminEmail, 'admin_nieuwe_inzending', $mailVars);
    }
} catch (\PDOException $e) { /* admins-tabel heeft nog geen email-kolom */ }

// ── Redirect naar klantenomgeving ────────────────────────────────
if ($casenummer) {
    $_SESSION['portal_case']  = $casenummer;
    $_SESSION['portal_email'] = strtolower($email);
    redirect(BASE_URL . '/mijn-aanvraag.php?nieuw=1');
} else {
    redirect(BASE_URL . '/advies.php?verzonden=1');
}
