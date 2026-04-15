<?php
/**
 * GET /api/check-repareerbaar.php?merk=Samsung&modelnummer=UE55CU8000
 *
 * Zoekstrategie (volgorde):
 *   1. Exacte match merk + modelnummer
 *   2. Fuzzy match (spaties/streepjes genegeerd)
 *   3. Merk-level fallback via advies_regels (reparatie_merken / coulance_merken)
 *
 * Response:
 *   {
 *     gevonden:     bool,
 *     repareerbaar: bool,
 *     taxatie:      bool,
 *     merk:         string,
 *     modelnummer:  string,
 *     match:        'exact'|'fuzzy'|'merk_default'|'niet_gevonden'
 *   }
 */
ob_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/advies_regels.php';
error_reporting(0);
ini_set('display_errors', 0);
ob_clean();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, private');

$merk        = trim($_GET['merk']        ?? '');
$modelnummer = trim($_GET['modelnummer'] ?? '');

if ($merk === '' || $modelnummer === '') {
    echo json_encode(['gevonden' => false, 'repareerbaar' => false, 'taxatie' => false]);
    exit;
}

// Verwijder spaties/streepjes voor fuzzy vergelijking
$modelNormalized = preg_replace('/[\s\-]+/', '', strtolower($modelnummer));

try {
    $pdo = db();

    // ── Stap 1: exacte match ──────────────────────────────────────
    $stmt = $pdo->prepare("
        SELECT id, merk, modelnummer, repareerbaar, taxatie
        FROM   tv_modellen
        WHERE  actief = 1
          AND  LOWER(merk)        = LOWER(:merk)
          AND  LOWER(modelnummer) = LOWER(:model)
        LIMIT 1
    ");
    $stmt->execute([':merk' => $merk, ':model' => $modelnummer]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        echo json_encode([
            'gevonden'     => true,
            'repareerbaar' => (bool)(int)$row['repareerbaar'],
            'taxatie'      => (bool)(int)($row['taxatie'] ?? 0),
            'merk'         => $row['merk'],
            'modelnummer'  => $row['modelnummer'],
            'match'        => 'exact',
        ]);
        exit;
    }

    // ── Stap 2: fuzzy match ───────────────────────────────────────
    $stmt2 = $pdo->prepare("
        SELECT id, merk, modelnummer, repareerbaar, taxatie
        FROM   tv_modellen
        WHERE  actief = 1
          AND  LOWER(merk) = LOWER(:merk)
    ");
    $stmt2->execute([':merk' => $merk]);
    $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $r) {
        $dbNorm = preg_replace('/[\s\-]+/', '', strtolower($r['modelnummer']));
        if ($dbNorm === $modelNormalized) {
            echo json_encode([
                'gevonden'     => true,
                'repareerbaar' => (bool)(int)$r['repareerbaar'],
                'taxatie'      => (bool)(int)($r['taxatie'] ?? 0),
                'merk'         => $r['merk'],
                'modelnummer'  => $r['modelnummer'],
                'match'        => 'fuzzy',
            ]);
            exit;
        }
    }

    // ── Stap 3: model niet gevonden — merk-level fallback via DB-regels ──
    // Gebruik reparatie_merken en taxatie_merken uit advies_regels.
    // Lege lijst = alle merken toegestaan.
    $regels        = getAdviesRegels();
    $repMerken     = $regels['reparatie_merken'] ?? [];
    $taxMerken     = $regels['taxatie_merken']   ?? [];

    $merkRep = merkToegestaanVoorRoute($repMerken, $merk);
    $merkTax = merkToegestaanVoorRoute($taxMerken, $merk);

    echo json_encode([
        'gevonden'     => false,
        'repareerbaar' => $merkRep,
        'taxatie'      => $merkTax,
        'merk'         => $merk,
        'modelnummer'  => $modelnummer,
        'match'        => 'merk_default',
    ]);

} catch (Exception $e) {
    echo json_encode(['gevonden' => false, 'repareerbaar' => false, 'taxatie' => false, 'match' => 'error']);
}
exit;
