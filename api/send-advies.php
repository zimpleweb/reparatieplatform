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
$situatie          = trim($_POST['situatie']          ?? '');
$klacht_type       = trim($_POST['klacht_type']       ?? '');
$omschrijving      = trim($_POST['omschrijving']      ?? '');
$email             = filter_var(trim($_POST['email']  ?? ''), FILTER_VALIDATE_EMAIL);
$geadviseerde_route= trim($_POST['geadviseerde_route']?? '');
$coulance_kans     = (int) ($_POST['coulance_kans']   ?? 0);
$model_repareerbaar= trim($_POST['model_repareerbaar']?? '');

if (!$email || !$merk || !$modelnummer) {
    redirect('/advies.php?error=2');
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

// ── Redirect ─────────────────────────────────────────────────────
$param = $casenummer ? '&case=' . urlencode($casenummer) : '';
redirect('/advies.php?verzonden=1' . $param);
