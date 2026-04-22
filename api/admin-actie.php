<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/admin/aanvragen.php');
}

// ── CSRF check ────────────────────────────────────────────────────
if (!verifyCsrf($_POST['csrf'] ?? '')) {
    http_response_code(403);
    die('Ongeldig verzoek. Ververs de pagina en probeer opnieuw.');
}

$id        = (int)  ($_POST['id']       ?? 0);
$actie     = trim(  $_POST['actie']     ?? '');
$opmerking = trim(  $_POST['opmerking'] ?? '');

if (!$id || !$actie) redirect(BASE_URL . '/admin/aanvragen.php');

$geldig = ['doorzetten_reparatie','doorzetten_taxatie','coulance','recycling','behandeld','archiveren','bericht_admin'];
if (!in_array($actie, $geldig, true)) redirect(BASE_URL . '/admin/aanvragen.php?id=' . $id);

// ── Laad aanvraag voor mail-variabelen ────────────────────────────
$avRow = null;
try {
    $avQ = db()->prepare('SELECT casenummer, email, merk, modelnummer, status FROM aanvragen WHERE id=?');
    $avQ->execute([$id]);
    $avRow = $avQ->fetch() ?: null;
} catch (\PDOException $e) {}

// ── Bericht sturen (alleen loggen, geen statuswijziging) ──────────
if ($actie === 'bericht_admin') {
    $tekst = trim($opmerking);
    if ($tekst) {
        try {
            db()->prepare(
                'INSERT INTO aanvragen_log (aanvraag_id, actie, opmerking, gedaan_door) VALUES (?,?,?,?)'
            )->execute([$id, 'Bericht verstuurd door admin', $tekst, 'admin']);
        } catch (\PDOException $e) {}
        if ($avRow && !empty($avRow['email'])) {
            @sendMail($avRow['email'], 'inzender_nieuw_chatbericht', [
                'casenummer'  => $avRow['casenummer'] ?? '',
                'merk'        => $avRow['merk'] ?? '',
                'modelnummer' => $avRow['modelnummer'] ?? '',
                'chatbericht' => $tekst,
            ]);
        }
    }
    redirect(BASE_URL . '/admin/aanvragen.php?id=' . $id . '&saved=1');
}

$statusMap = [
    'doorzetten_reparatie' => 'doorgestuurd',
    'doorzetten_taxatie'   => 'doorgestuurd',
    'coulance'             => 'coulance',
    'recycling'            => 'recycling',
    'behandeld'            => 'behandeld',
    'archiveren'           => 'archief',
];
$typeMap = [
    'doorzetten_reparatie' => 'reparatie',
    'doorzetten_taxatie'   => 'taxatie',
    'coulance'             => 'coulance',
    'recycling'            => 'recycling',
];
$actieLabelMap = [
    'doorzetten_reparatie' => 'Doorgestuurd voor reparatieaanvraag',
    'doorzetten_taxatie'   => 'Doorgestuurd voor taxatieaanvraag',
    'coulance'             => 'Coulance traject gestart',
    'recycling'            => 'Recycling traject gestart',
    'behandeld'            => 'Gemarkeerd als behandeld',
    'archiveren'           => 'Gearchiveerd',
];

$nieuweStatus = $statusMap[$actie];
$nieuwType    = $typeMap[$actie] ?? null;

// ── Whitelist datumkolom — SQL injection fix ──────────────────────
if ($nieuwType) {
    db()->prepare('UPDATE aanvragen SET status=?, advies_type=? WHERE id=?')
       ->execute([$nieuweStatus, $nieuwType, $id]);
} else {
    db()->prepare('UPDATE aanvragen SET status=? WHERE id=?')
       ->execute([$nieuweStatus, $id]);
}

// Log-entry
try {
    db()->prepare(
        'INSERT INTO aanvragen_log (aanvraag_id, actie, opmerking, gedaan_door) VALUES (?,?,?,?)'
    )->execute([$id, $actieLabelMap[$actie], $opmerking ?: null, 'admin']);
} catch (\PDOException $e) { /* log-tabel nog niet beschikbaar */ }

// Mail: statuswijziging naar inzender
if ($avRow && !empty($avRow['email'])) {
    $statusTekstMap = [
        'inzending'    => 'Ontvangen',
        'doorgestuurd' => 'Aanvulling nodig',
        'aanvraag'     => 'Aanvraag ontvangen',
        'coulance'     => 'Coulance',
        'recycling'    => 'Recycling',
        'behandeld'    => 'Behandeld',
        'archief'      => 'Archief',
    ];
    @sendMail($avRow['email'], 'inzender_status_gewijzigd', [
        'casenummer'  => $avRow['casenummer'] ?? '',
        'merk'        => $avRow['merk'] ?? '',
        'modelnummer' => $avRow['modelnummer'] ?? '',
        'status_oud'  => $statusTekstMap[$avRow['status']] ?? $avRow['status'],
        'status_nieuw'=> $statusTekstMap[$nieuweStatus] ?? $nieuweStatus,
    ]);
}

redirect(BASE_URL . '/admin/aanvragen.php?id=' . $id . '&saved=1');