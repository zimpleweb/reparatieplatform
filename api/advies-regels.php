<?php
/**
 * GET /api/advies-regels.php
 * Geeft alle adviesregels als JSON terug aan de front-end.
 * Gevoelige keys worden NIET meegestuurd (alleen routing-regels).
 */
ob_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/advies_regels.php';
error_reporting(0);
ini_set('display_errors', 0);
ob_clean();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=60');

$r = getAdviesRegels();

// Alleen routing-relevante regels naar front-end sturen
$allowed = [
    'garantie_termijn_jaar',
    'garantie_alleen_nl',
    'garantie_uitsluiten_klachten',
    'coulance_min_jaar',
    'coulance_max_jaar',
    'coulance_uitsluiten_klachten',
    'coulance_kans_matrix',
    'coulance_aftrek_buitenland',
    'coulance_aftrek_failliet',
    'reparatie_min_jaar',
    'reparatie_max_jaar',
    'reparatie_vereist_repareerbaar',
    'recycling_min_jaar',
    'taxatie_bij_schade',
    'taxatie_merken',
];

$out = [];
foreach ($allowed as $key) {
    if (isset($r[$key])) $out[$key] = $r[$key];
}

echo json_encode($out, JSON_UNESCAPED_UNICODE);
exit;
