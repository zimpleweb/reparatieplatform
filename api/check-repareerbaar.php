<?php
/**
 * GET /api/check-repareerbaar.php?merk=Samsung&modelnummer=UE55CU8000
 *
 * Zoekstrategie (volgorde):
 *   1. Exacte match merk + modelnummer
 *   2. Merk + modelnummer LIKE (voor spaties, streepjes, kleine variaties)
 *   3. Alleen merk  → geef merk-level default terug
 *
 * Response: { gevonden: bool, repareerbaar: bool, merk: string, modelnummer: string, match: string }
 */
ob_start();
require_once __DIR__ . '/../includes/db.php';
error_reporting(0);
ini_set('display_errors', 0);
ob_clean();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, private');

$merk        = trim($_GET['merk']        ?? '');
$modelnummer = trim($_GET['modelnummer'] ?? '');

if ($merk === '' || $modelnummer === '') {
    echo json_encode(['gevonden' => false, 'repareerbaar' => false]);
    exit;
}

// Verwijder spaties/streepjes voor fuzzy vergelijking
$modelNormalized = preg_replace('/[\s\-]+/', '', strtolower($modelnummer));

try {
    $pdo = db();

    // ── Stap 1: exacte match (case-insensitive) ──
    $stmt = $pdo->prepare("
        SELECT id, merk, modelnummer, repareerbaar
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
            'merk'         => $row['merk'],
            'modelnummer'  => $row['modelnummer'],
            'match'        => 'exact',
        ]);
        exit;
    }

    // ── Stap 2: fuzzy match – haal alle modellen van dit merk op en vergelijk genormaliseerd ──
    $stmt2 = $pdo->prepare("
        SELECT id, merk, modelnummer, repareerbaar
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
                'merk'         => $r['merk'],
                'modelnummer'  => $r['modelnummer'],
                'match'        => 'fuzzy',
            ]);
            exit;
        }
    }

    // ── Stap 3: model niet gevonden – geef merk-level repareerbaar terug als fallback ──
    // Merken die standaard NIET repareerbaar zijn (conform admin/modellen.php $repareerbareMerken)
    $repareerbareMerken = ['Samsung', 'Philips', 'Sony', 'LG'];
    $merkRepareerbaar   = in_array($merk, $repareerbareMerken, true);

    echo json_encode([
        'gevonden'     => false,
        'repareerbaar' => $merkRepareerbaar,
        'merk'         => $merk,
        'modelnummer'  => $modelnummer,
        'match'        => 'merk_default',
    ]);

} catch (Exception $e) {
    echo json_encode(['gevonden' => false, 'repareerbaar' => false, 'match' => 'error']);
}
exit;
