<?php
/**
 * GET /api/check-repareerbaar.php?merk=Samsung&modelnummer=UE55CU8000
 * Geeft terug of het model in de database staat én repareerbaar is.
 * Response: { "gevonden": bool, "repareerbaar": bool, "merk": string, "modelnummer": string }
 */
ob_start();
require_once __DIR__ . '/../includes/db.php';
error_reporting(0);
ini_set('display_errors', 0);
ob_clean();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');

$merk        = trim($_GET['merk']        ?? '');
$modelnummer = trim($_GET['modelnummer'] ?? '');

if ($merk === '' || $modelnummer === '') {
    echo json_encode(['gevonden' => false, 'repareerbaar' => false]);
    exit;
}

try {
    // Zoek eerst op exacte match modelnummer + merk (case-insensitive)
    $stmt = db()->prepare("
        SELECT id, merk, modelnummer, status, actief
        FROM   tv_modellen
        WHERE  actief = 1
          AND  LOWER(merk)        = LOWER(:merk)
          AND  LOWER(modelnummer) = LOWER(:modelnummer)
        LIMIT 1
    ");
    $stmt->execute([':merk' => $merk, ':modelnummer' => $modelnummer]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        // Repareerbaar als status = 'repareerbaar' (of soortgelijke waarde)
        $status       = strtolower(trim($row['status'] ?? ''));
        $repareerbaar = in_array($status, ['repareerbaar', 'repairable', 'ja', 'yes', '1'], true);
        echo json_encode([
            'gevonden'     => true,
            'repareerbaar' => $repareerbaar,
            'status'       => $status,
            'merk'         => $row['merk'],
            'modelnummer'  => $row['modelnummer'],
        ]);
    } else {
        // Niet gevonden in database
        echo json_encode(['gevonden' => false, 'repareerbaar' => false]);
    }
} catch (Exception $e) {
    echo json_encode(['gevonden' => false, 'repareerbaar' => false]);
}
exit;
