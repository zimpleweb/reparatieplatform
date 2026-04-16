<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/aanvragen.php');
}

$id       = (int)  ($_POST['id']        ?? 0);
$actie    = trim(  $_POST['actie']      ?? '');
$opmerking= trim(  $_POST['opmerking']  ?? '');

if (!$id || !$actie) redirect('/admin/aanvragen.php');

$geldig = ['doorzetten_reparatie','doorzetten_taxatie','coulance','recycling','behandeld','archiveren'];
if (!in_array($actie, $geldig)) redirect('/admin/aanvragen.php?id=' . $id);

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

// Update aanvraag: status altijd, advies_type alleen als er een nieuw type is
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

redirect('/admin/aanvragen.php?id=' . $id . '&saved=1');
