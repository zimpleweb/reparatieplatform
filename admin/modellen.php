<?php
session_start();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/advies_regels.php';
requireAdmin();

$msg = '';
$advies = getAdviesRegels();
$repareerbareMerken = $advies['reparatie_merken'] ?? [];
$taxatieMerken      = $advies['taxatie_merken']   ?? [];
function defaultVoorMerk(array $merkLijst, string $merk): bool { if (empty($merkLijst)) return true; return in_array(mb_strtolower(trim($merk)), array_map(fn($m) => mb_strtolower(trim($m)), $merkLijst), true); }
function isUitzondering(array $merkLijst, string $merk, int $modelWaarde): ?string { $merkDefault = empty($merkLijst) ? true : in_array(mb_strtolower(trim($merk)), array_map(fn($m) => mb_strtolower(trim($m)), $merkLijst), true); if (!$merkDefault && $modelWaarde === 1) return 'positief'; if ($merkDefault && $modelWaarde === 0) return 'negatief'; return null; }
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle') { if (!verifyCsrf($_POST['csrf'] ?? '')) { http_response_code(403); exit('Ongeldig beveiligingstoken.'); } $veld = in_array($_POST['veld'] ?? '', ['repareerbaar','taxatie']) ? $_POST['veld'] : null; if ($veld) { $row = db()->prepare('SELECT '.$veld.' FROM tv_modellen WHERE id=? AND actief=1'); $row->execute([(int)($_POST['id'] ?? 0)]); $huidig = (int)$row->fetchColumn(); db()->prepare('UPDATE tv_modellen SET '.$veld.'=? WHERE id=?')->execute([$huidig ? 0 : 1, (int)($_POST['id'] ?? 0)]); } header('Location: '.$_SERVER['PHP_SELF'].'?updated=1'); exit; }
$statsTotal = (int)db()->query('SELECT COUNT(*) FROM tv_modellen WHERE actief=1')->fetchColumn();
$statsRep   = (int)db()->query('SELECT COUNT(*) FROM tv_modellen WHERE actief=1 AND repareerbaar=1')->fetchColumn();
$statsTax   = (int)db()->query('SELECT COUNT(*) FROM tv_modellen WHERE actief=1 AND taxatie=1')->fetchColumn();
$PAGE_SIZE = 35;
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>TV Modellen &ndash; Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Epilogue:wght@800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/base.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/components.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
  <meta name="robots" content="noindex,nofollow">
</head>
<body>
<?php $adminActivePage = 'modellen'; require_once __DIR__ . '/includes/admin-header.php'; ?>
<div class="adm-page">
  <h1>TV Modellen</h1>
  <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1rem;">
    <span class="stat-chip sc-total">&#128250; <?= $statsTotal ?> totaal</span>
    <span class="stat-chip sc-rep">&#128295; <?= $statsRep ?> repareerbaar</span>
    <span class="stat-chip sc-tax">&#128203; <?= $statsTax ?> taxatie</span>
  </div>
</div>
</body>
</html>