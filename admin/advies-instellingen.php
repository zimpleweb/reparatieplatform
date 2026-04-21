<?php
/**
 * admin/advies-instellingen.php
 * Beheer van adviesrouting-regels.
 */
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (empty($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/login.php');
    exit;
}

// ── Helpers ───────────────────────────────────────────────────────────────
function isUitzondering(array $merkenLijst, string $merk, int $vlag): ?bool {
    if (empty($merkenLijst)) return null; // geen uitzonderingen gedefinieerd
    $inLijst = in_array($merk, $merkenLijst, true);
    // Als vlag afwijkt van "alles mag" (1) of "niets mag" (0) op basis van lijst
    if ($inLijst && $vlag === 0) return false;
    if (!$inLijst && $vlag === 1) return true;
    return null;
}

// ── DB: tabel aanmaken indien nodig ───────────────────────────────────────
try {
    db()->exec("
        CREATE TABLE IF NOT EXISTS advies_regels (
            id           INT AUTO_INCREMENT PRIMARY KEY,
            regel_key    VARCHAR(100) NOT NULL UNIQUE,
            regel_value  TEXT         NOT NULL DEFAULT '',
            updated_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
} catch (\Exception $e) {}

// ── Standaardregel helpers ────────────────────────────────────────────────
function getAdviesRegel(string $key, string $default = ''): string {
    static $cache = [];
    if (!isset($cache[$key])) {
        try {
            $s = db()->prepare("SELECT regel_value FROM advies_regels WHERE regel_key=?");
            $s->execute([$key]);
            $cache[$key] = $s->fetchColumn() ?: $default;
        } catch (\Exception $e) { $cache[$key] = $default; }
    }
    return $cache[$key];
}
function setAdviesRegel(string $key, string $value): void {
    db()->prepare("
        INSERT INTO advies_regels (regel_key, regel_value) VALUES (?,?)
        ON DUPLICATE KEY UPDATE regel_value=VALUES(regel_value)
    ")->execute([$key, $value]);
}

// ── TV-modellen statistieken ──────────────────────────────────────────────
$statsTotal = 0; $statsRep = 0; $statsTax = 0;
try {
    $statsTotal = (int) db()->query("SELECT COUNT(*) FROM tv_modellen")->fetchColumn();
    $statsRep   = (int) db()->query("SELECT COUNT(*) FROM tv_modellen WHERE repareerbaar=1")->fetchColumn();
    $statsTax   = (int) db()->query("SELECT COUNT(*) FROM tv_modellen WHERE taxatie=1")->fetchColumn();
} catch (\Exception $e) {}

// ── Alle modellen voor uitzonderingentelling ──────────────────────────────
$allModellenVoorTelling = [];
try {
    $allModellenVoorTelling = db()->query("SELECT merk, repareerbaar, taxatie FROM tv_modellen")->fetchAll(\PDO::FETCH_ASSOC);
} catch (\Exception $e) {}

// ── Opslaan ───────────────────────────────────────────────────────────────
$msg  = '';
$type = 'success';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $msg = 'Beveiligingstoken ongeldig.'; $type = 'error';
    } else {
        $act = $_POST['action'];

        if ($act === 'save_leeftijd') {
            setAdviesRegel('garantie_max_leeftijd', (string)(int)($_POST['garantie_max_leeftijd'] ?? 2));
            setAdviesRegel('coulance_max_leeftijd', (string)(int)($_POST['coulance_max_leeftijd'] ?? 5));
            $msg = 'Leeftijdsgrenzen opgeslagen.';
        }
        if ($act === 'save_prijs') {
            setAdviesRegel('reparatie_max_prijs_klasse', trim($_POST['reparatie_max_prijs_klasse'] ?? '500-1000'));
            $msg = 'Prijsgrens opgeslagen.';
        }
        if ($act === 'save_merken') {
            foreach (['garantie_merken','coulance_merken','reparatie_merken','taxatie_merken'] as $mk) {
                $raw = $_POST[$mk . '_json'] ?? '[]';
                $arr = json_decode($raw, true);
                if (!is_array($arr)) $arr = [];
                $arr = array_values(array_filter(array_map('trim', $arr)));
                setAdviesRegel($mk, json_encode($arr));
            }
            $msg = 'Merkfilters opgeslagen.';
        }
        if ($act === 'save_recycling') {
            setAdviesRegel('recycling_enabled',       isset($_POST['recycling_enabled']) ? '1' : '0');
            setAdviesRegel('recycling_max_leeftijd',  (string)(int)($_POST['recycling_max_leeftijd'] ?? 10));
            $msg = 'Recycling-instelling opgeslagen.';
        }
    }
}

// ── Huidige waarden ophalen ───────────────────────────────────────────────
$r = null;
try {
    $r = db()->query("SELECT * FROM advies_regels LIMIT 1")->fetch();
} catch (\Exception $e) {}

$garantieMaxLeeftijd  = (int) getAdviesRegel('garantie_max_leeftijd', '2');
$coulanceMaxLeeftijd  = (int) getAdviesRegel('coulance_max_leeftijd', '5');
$reparatieMaxPrijs    = getAdviesRegel('reparatie_max_prijs_klasse', '500-1000');
$recyclingEnabled     = getAdviesRegel('recycling_enabled',    '0') === '1';
$recyclingMaxLeeftijd = (int) getAdviesRegel('recycling_max_leeftijd', '10');

$garantieMerken  = json_decode(getAdviesRegel('garantie_merken',  '[]'), true) ?: [];
$coulanceMerken  = json_decode(getAdviesRegel('coulance_merken',  '[]'), true) ?: [];
$repareerbareMerken = json_decode(getAdviesRegel('reparatie_merken', '[]'), true) ?: [];
$taxatieMerken   = json_decode(getAdviesRegel('taxatie_merken',   '[]'), true) ?: [];

// Beschikbare merken uit tv_modellen
$beschikbareMerken = [];
try {
    $beschikbareMerken = db()->query("SELECT DISTINCT merk FROM tv_modellen ORDER BY merk")->fetchAll(\PDO::FETCH_COLUMN);
} catch (\Exception $e) {}

$prijsKlassen = [
    '0-200'      => '< €200',
    '200-500'    => '€200 – €500',
    '500-1000'   => '€500 – €1.000',
    '1000-2000'  => '€1.000 – €2.000',
    '>2000'      => '> €2.000',
];

$totalUitzRep = 0; $totalUitzTax = 0;
foreach ($allModellenVoorTelling as $m) {
    if (isUitzondering($repareerbareMerken, $m['merk'], (int)$m['repareerbaar']) !== null) $totalUitzRep++;
    if (isUitzondering($taxatieMerken,      $m['merk'], (int)$m['taxatie'])      !== null) $totalUitzTax++;
}

$adminActivePage = 'advies-instellingen';
require_once __DIR__ . '/includes/admin-header.php';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8"><title>Advies Instellingen &ndash; Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Epilogue:wght@800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/base.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/components.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
  <meta name="robots" content="noindex,nofollow">
  <style>
    /* Layout */
    .rules-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
    @media(max-width:900px){ .rules-grid { grid-template-columns: 1fr; } }
    .rule-section {
      background: #fff; border: 1px solid #e5e7eb; border-radius: 12px;
      padding: 1.5rem; display:flex; flex-direction:column; gap:1rem;
    }
    .rule-section h2 { font-size:1rem; font-weight:700; color:#111827; margin:0 0 .1rem; }
    .rule-section p  { font-size:.8rem; color:#6b7280; margin:0 0 .5rem; }
    label { font-size:.8rem; font-weight:600; color:#374151; display:block; margin-bottom:.3rem; }
    input[type=number], select {
      width:100%; padding:.5rem .75rem; border:1px solid #d1d5db; border-radius:8px;
      font-size:.875rem; color:#111827; background:#fff;
    }
    input[type=number]:focus, select:focus { outline:none; border-color:#4f98a3; box-shadow:0 0 0 3px rgba(79,152,163,.15); }
    .btn-sm {
      display:inline-flex; align-items:center; gap:.4rem;
      background:#111827; color:#fff; border:none; border-radius:8px;
      padding:.5rem 1rem; font-size:.8rem; font-weight:600; cursor:pointer;
      transition:background .15s;
    }
    .btn-sm:hover { background:#1f2937; }
    .alert { padding:.75rem 1rem; border-radius:8px; font-size:.85rem; font-weight:600; margin-bottom:1.25rem; }
    .alert-success { background:#f0fdf4; border:1px solid #bbf7d0; color:#15803d; }
    .alert-error   { background:#fef2f2; border:1px solid #fecaca; color:#dc2626; }

    /* Stats */
    .stats-row { display:flex; flex-wrap:wrap; gap:.5rem; margin-bottom:1.25rem; }
    .stat-chip { font-size:.75rem; font-weight:600; padding:.3rem .75rem; border-radius:999px; }
    .sc-total  { background:#f3f4f6; color:#374151; }
    .sc-rep    { background:#eff6ff; color:#1d4ed8; }
    .sc-tax    { background:#fef9c3; color:#854d0e; }

    /* Route chips */
    .route-legenda { display:flex; flex-wrap:wrap; gap:.4rem; margin-bottom:1.25rem; }
    .route-chip    { font-size:.72rem; font-weight:700; padding:.25rem .65rem; border-radius:999px; }
    .chip-garantie  { background:#dcfce7; color:#15803d; }
    .chip-coulance  { background:#fce7f3; color:#9d174d; }
    .chip-reparatie { background:#dbeafe; color:#1e40af; }
    .chip-taxatie   { background:#fef9c3; color:#92400e; }
    .chip-recycling { background:#f3f4f6; color:#374151; }

    /* SQL note */
    .sql-note { background:#fff7ed; border:1px solid #fed7aa; border-radius:8px; padding:.85rem 1rem; font-size:.8rem; color:#92400e; margin-bottom:1.25rem; }
    .sql-note code { background:#fff; border:1px solid #fed7aa; border-radius:4px; padding:.1rem .35rem; font-size:.8rem; }

    /* Merken grid */
    .merken-grid { display:flex; flex-wrap:wrap; gap:.4rem; }
    .merk-label  { display:flex; align-items:center; gap:.3rem; font-size:.78rem; font-weight:500; color:#374151;
                   background:#f9fafb; border:1px solid #e5e7eb; border-radius:6px; padding:.25rem .6rem; cursor:pointer;
                   transition:background .1s, border-color .1s; }
    .merk-label:hover { background:#f0f9ff; border-color:#bae6fd; }
    .merk-label input:checked ~ span { color:#1d4ed8; }
    .merk-label:has(input:checked) { background:#eff6ff; border-color:#bfdbfe; }
    .merken-hint-all { font-size:.75rem; color:#6b7280; margin-bottom:.4rem; display:inline-block; }
  </style>
</head>
<body>
<div class="adm-page">

    <h1>&#9881; Advies routing instellingen</h1>
    <p style="color:#6b7280;margin-bottom:.75rem;font-size:.875rem;">
      Stel hier de voorwaarden in voor doorverwijzing naar
      <strong>Garantie</strong>, <strong>Coulance</strong>, <strong>Reparatie</strong>,
      <strong>Taxatie</strong> en <strong>Recycling</strong>.
      Wijzigingen zijn <strong>direct actief</strong> in het adviesformulier.
    </p>

    <div class="route-legenda">
      <span class="route-chip chip-garantie">&#9989; Garantie</span>
      <span class="route-chip chip-coulance">&#129309; Coulance</span>
      <span class="route-chip chip-reparatie">&#128295; Reparatie</span>
      <span class="route-chip chip-taxatie">&#128203; Taxatie</span>
      <span class="route-chip chip-recycling">&#9851; Recycling</span>
    </div>

    <!-- TV-modellen statistieken -->
    <div class="stats-row">
      <span class="stat-chip sc-total">&#128250; <?= $statsTotal ?> modellen totaal</span>
      <span class="stat-chip sc-rep">&#128295; <?= $statsRep ?> repareerbaar</span>
      <span class="stat-chip sc-tax">&#128203; <?= $statsTax ?> taxatie mogelijk</span>
    </div>

    <?php if (!$r): ?>
    <div class="sql-note">
      &#9888; De tabel <code>advies_regels</code> bestaat nog niet of is leeg.
      Voer eerst het SQL-script uit: <code>sql/advies_regels.sql</code>
    </div>
    <?php endif; ?>

    <?php if ($msg): ?>
    <div class="alert alert-<?= $type ?>"><?= h($msg) ?></div>
    <?php endif; ?>

    <div class="rules-grid">

      <!-- Leeftijdsgrenzen -->
      <div class="rule-section">
        <h2>&#128197; Leeftijdsgrenzen</h2>
        <p>Bepaal tot welke leeftijd (jaren) een TV in aanmerking komt voor Garantie of Coulance.</p>
        <form method="POST">
          <input type="hidden" name="action"     value="save_leeftijd">
          <input type="hidden" name="csrf_token" value="<?= csrf() ?>">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin-bottom:1rem;">
            <div>
              <label for="garantie_max_leeftijd">Garantie — max leeftijd (jaar)</label>
              <input type="number" id="garantie_max_leeftijd" name="garantie_max_leeftijd"
                     min="0" max="20" value="<?= $garantieMaxLeeftijd ?>">
            </div>
            <div>
              <label for="coulance_max_leeftijd">Coulance — max leeftijd (jaar)</label>
              <input type="number" id="coulance_max_leeftijd" name="coulance_max_leeftijd"
                     min="0" max="20" value="<?= $coulanceMaxLeeftijd ?>">
            </div>
          </div>
          <button type="submit" class="btn-sm">&#128190; Opslaan</button>
        </form>
      </div>

      <!-- Prijsgrens reparatie -->
      <div class="rule-section">
        <h2>&#128176; Prijsgrens reparatie</h2>
        <p>Boven welke aankoopprijsklasse is reparatie zinvol?</p>
        <form method="POST">
          <input type="hidden" name="action"     value="save_prijs">
          <input type="hidden" name="csrf_token" value="<?= csrf() ?>">
          <div style="margin-bottom:1rem;">
            <label for="reparatie_max_prijs_klasse">Minimale prijsklasse voor reparatie</label>
            <select id="reparatie_max_prijs_klasse" name="reparatie_max_prijs_klasse">
              <?php foreach ($prijsKlassen as $val => $lbl): ?>
                <option value="<?= h($val) ?>" <?= $reparatieMaxPrijs === $val ? 'selected' : '' ?>><?= h($lbl) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="btn-sm">&#128190; Opslaan</button>
        </form>
      </div>

      <!-- Merkfilters -->
      <div class="rule-section" style="grid-column:1/-1;">
        <h2>&#127981; Merkfilters</h2>
        <p>
          Laat leeg = <strong>alle merken</strong>. Vink merken aan om uitsluitend die te activeren voor de betreffende route.
          <?php if ($totalUitzRep + $totalUitzTax > 0): ?>
            <strong style="color:#b45309;">&#9888; <?= $totalUitzRep ?> reparatie- en <?= $totalUitzTax ?> taxatie-uitzondering(en) actief via Modellen.</strong>
          <?php endif; ?>
        </p>
        <form method="POST">
          <input type="hidden" name="action"     value="save_merken">
          <input type="hidden" name="csrf_token" value="<?= csrf() ?>">
          <?php
          $merkGroepen = [
            ['key'=>'garantie_merken',  'label'=>'&#9989; Garantie-merken',  'selected'=>$garantieMerken],
            ['key'=>'coulance_merken',  'label'=>'&#129309; Coulance-merken', 'selected'=>$coulanceMerken],
            ['key'=>'reparatie_merken', 'label'=>'&#128295; Reparatie-merken','selected'=>$repareerbareMerken],
            ['key'=>'taxatie_merken',   'label'=>'&#128203; Taxatie-merken',  'selected'=>$taxatieMerken],
          ];
          foreach ($merkGroepen as $grp): ?>
          <div style="margin-bottom:1.25rem;">
            <label style="margin-bottom:.4rem;"><?= $grp['label'] ?></label>
            <input type="hidden" id="<?= $grp['key'] ?>_json" name="<?= $grp['key'] ?>_json"
                   value="<?= h(json_encode($grp['selected'])) ?>">
            <?php if (empty($grp['selected'])): ?>
              <span class="merken-hint-all" id="<?= $grp['key'] ?>_hint">Alle merken (geen filter)</span>
            <?php else: ?>
              <span class="merken-hint-all" id="<?= $grp['key'] ?>_hint" style="display:none">Alle merken (geen filter)</span>
            <?php endif; ?>
            <div class="merken-grid" id="<?= $grp['key'] ?>_grid">
              <?php foreach ($beschikbareMerken as $merk): ?>
              <label class="merk-label">
                <input type="checkbox" class="merk-cb" data-group="<?= $grp['key'] ?>"
                       value="<?= h($merk) ?>" <?= in_array($merk, $grp['selected'], true) ? 'checked' : '' ?> style="display:none">
                <span><?= h($merk) ?></span>
              </label>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endforeach; ?>
          <button type="submit" class="btn-sm">&#128190; Merkfilters opslaan</button>
        </form>
      </div>

      <!-- Recycling -->
      <div class="rule-section" style="grid-column:1/-1;">
        <h2>&#9851; Recycling</h2>
        <p>Stuur bezoekers door naar recycling als de TV te oud is voor reparatie of taxatie.</p>
        <form method="POST">
          <input type="hidden" name="action"     value="save_recycling">
          <input type="hidden" name="csrf_token" value="<?= csrf() ?>">
          <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1rem;flex-wrap:wrap;">
            <label style="display:flex;align-items:center;gap:.5rem;margin:0;font-size:.875rem;cursor:pointer;">
              <input type="checkbox" name="recycling_enabled" value="1" <?= $recyclingEnabled ? 'checked' : '' ?>>
              Recycling-route activeren
            </label>
            <div style="display:flex;align-items:center;gap:.5rem;">
              <label for="recycling_max_leeftijd" style="margin:0;white-space:nowrap;">Max leeftijd (jaar):</label>
              <input type="number" id="recycling_max_leeftijd" name="recycling_max_leeftijd"
                     min="1" max="30" value="<?= $recyclingMaxLeeftijd ?>" style="width:80px;">
            </div>
          </div>
          <button type="submit" class="btn-sm">&#128190; Opslaan</button>
        </form>
      </div>

    </div><!-- /.rules-grid -->

</div><!-- /.adm-page -->

<script>
// ── Merk-checkboxen → hidden JSON-veld sync ────────────────────────
document.querySelectorAll('.merk-cb').forEach(cb => {
  cb.addEventListener('change', () => syncMerkenJson(cb.dataset.group));
});
function syncMerkenJson(group) {
  const checked = [...document.querySelectorAll(
    `.merk-cb[data-group="${group}"]:checked`
  )].map(c => c.value);
  const hid = document.getElementById(group + '_json');
  if (hid) hid.value = JSON.stringify(checked);
  const wrap = document.getElementById(group + '_grid');
  if (!wrap) return;
  const prev = wrap.previousElementSibling;
  if (prev && prev.classList.contains('merken-hint-all')) {
    prev.style.display = checked.length === 0 ? 'inline-block' : 'none';
  }
}
['garantie_merken','coulance_merken','reparatie_merken','taxatie_merken'].forEach(syncMerkenJson);
</script>
</body>
</html>