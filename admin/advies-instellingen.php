<?php
/**
 * admin/advies-instellingen.php
 * Beheer van alle adviesrouting-regels voor advies.php
 */
session_start();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

// ── DB: tabel aanmaken/migreren indien nodig ─────────────────────────────
try {
    db()->exec("
        CREATE TABLE IF NOT EXISTS advies_regels (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            regel_key   VARCHAR(100) NOT NULL UNIQUE,
            regel_waarde TEXT        NOT NULL DEFAULT '',
            type        VARCHAR(20)  NOT NULL DEFAULT 'string',
            updated_at  TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    // Kolom 'type' toevoegen als die nog niet bestaat (migratie voor oude installaties)
    $cols = db()->query("SHOW COLUMNS FROM advies_regels LIKE 'type'")->fetchAll();
    if (empty($cols)) {
        db()->exec("ALTER TABLE advies_regels ADD COLUMN type VARCHAR(20) NOT NULL DEFAULT 'string' AFTER regel_waarde");
    }
    // regel_waarde kolom (migratie van regel_value → regel_waarde)
    $colsV = db()->query("SHOW COLUMNS FROM advies_regels LIKE 'regel_value'")->fetchAll();
    if (!empty($colsV)) {
        $colsW = db()->query("SHOW COLUMNS FROM advies_regels LIKE 'regel_waarde'")->fetchAll();
        if (empty($colsW)) {
            db()->exec("ALTER TABLE advies_regels CHANGE regel_value regel_waarde TEXT NOT NULL DEFAULT ''");
        }
    }
} catch (\Exception $e) {}

// ── Helpers ───────────────────────────────────────────────────────────────
function getAR(string $key, string $default = ''): string {
    static $cache = [];
    if (!array_key_exists($key, $cache)) {
        try {
            // Probeer beide kolomnamen (migratie veilig)
            try {
                $s = db()->prepare("SELECT regel_waarde FROM advies_regels WHERE regel_key=?");
            } catch (\Exception $e) {
                $s = db()->prepare("SELECT regel_value FROM advies_regels WHERE regel_key=?");
            }
            $s->execute([$key]);
            $v = $s->fetchColumn();
            $cache[$key] = ($v !== false && $v !== '') ? $v : $default;
        } catch (\Exception $e) { $cache[$key] = $default; }
    }
    return $cache[$key];
}

function setAR(string $key, string $value, string $type = 'string'): void {
    try {
        // Probeer regel_waarde kolom
        db()->prepare("
            INSERT INTO advies_regels (regel_key, regel_waarde, type) VALUES (?,?,?)
            ON DUPLICATE KEY UPDATE regel_waarde=VALUES(regel_waarde), type=VALUES(type)
        ")->execute([$key, $value, $type]);
    } catch (\Exception $e) {
        // Fallback naar oude kolomnaam
        db()->prepare("
            INSERT INTO advies_regels (regel_key, regel_value) VALUES (?,?)
            ON DUPLICATE KEY UPDATE regel_value=VALUES(regel_value)
        ")->execute([$key, $value]);
    }
}

// ── Alle klacht-opties (gesynchroniseerd met advies.php) ──────────────────
$alleKlachten = [
    'gebarsten_scherm'  => 'Kapot of gebarsten scherm',
    'strepen'           => 'Strepen of lijnen in beeld',
    'geen_beeld'        => 'Geen beeld, wel geluid',
    'backlight'         => 'Donkere vlekken of backlight-uitval',
    'flikkering'        => 'Bevroren beeld of flikkering',
    'kleur'             => 'Kleurproblemen of sterk verkleurde pixels',
    'niet_aan'          => 'TV gaat niet aan',
    'stroomstoot'       => 'Schade na stroomstoot of blikseminslag',
    'oververhitting'    => 'Oververhitting of stopt na korte tijd',
    'software'          => 'Software of Smart TV werkt niet',
    'afstandsbediening' => 'Afstandsbediening reageert niet',
    'geluid'            => 'Geen of slecht geluid, beeld werkt wel',
    'anders'            => 'Anders of niet in de lijst',
];

// ── Prijsklasse-opties ────────────────────────────────────────────────────
$allePrijsklassen = [
    '<500'      => 'Minder dan €500',
    '500-1000'  => '€500 – €1.000',
    '1000-2000' => '€1.000 – €2.000',
    '>2000'     => 'Meer dan €2.000',
    ''          => 'Onbekend / niet ingevuld',
];

// ── TV-modellen statistieken ──────────────────────────────────────────────
$statsTotal = $statsRep = $statsTax = 0;
try {
    $statsTotal = (int) db()->query("SELECT COUNT(*) FROM tv_modellen")->fetchColumn();
    $statsRep   = (int) db()->query("SELECT COUNT(*) FROM tv_modellen WHERE repareerbaar=1")->fetchColumn();
    $statsTax   = (int) db()->query("SELECT COUNT(*) FROM tv_modellen WHERE taxatie=1")->fetchColumn();
} catch (\Exception $e) {}

$beschikbareMerken = [];
try {
    $beschikbareMerken = db()->query("SELECT DISTINCT merk FROM tv_modellen ORDER BY merk")->fetchAll(\PDO::FETCH_COLUMN);
} catch (\Exception $e) {}

// ── Opslaan ───────────────────────────────────────────────────────────────
$msg  = '';
$type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $msg = 'Beveiligingstoken ongeldig.'; $type = 'error';
    } else {
        $act = $_POST['action'];

        // ── Stappenplan configuratie opslaan ──────────────────────────────
        if ($act === 'save_stappen') {
            $stappen = [];
            $cnt = (int)($_POST['stap_count'] ?? 4);
            for ($i = 1; $i <= min($cnt, 10); $i++) {
                $stappen[] = [
                    'nummer' => $i,
                    'label'  => trim($_POST["stap_{$i}_label"]  ?? ''),
                    'titel'  => trim($_POST["stap_{$i}_titel"]  ?? ''),
                    'lead'   => trim($_POST["stap_{$i}_lead"]   ?? ''),
                ];
            }
            setAR('stappen_config', json_encode($stappen, JSON_UNESCAPED_UNICODE), 'json');
            $msg = 'Stappenplan opgeslagen.';
        }

        // ── Leeftijdsgrenzen opslaan ──────────────────────────────────────
        if ($act === 'save_leeftijd') {
            setAR('garantie_termijn_jaar',     (string)(int)($_POST['garantie_termijn_jaar']     ?? 2), 'int');
            setAR('coulance_min_jaar',         (string)(int)($_POST['coulance_min_jaar']         ?? 2), 'int');
            setAR('coulance_max_jaar',         (string)(int)($_POST['coulance_max_jaar']         ?? 5), 'int');
            setAR('reparatie_min_jaar',        (string)(int)($_POST['reparatie_min_jaar']        ?? 2), 'int');
            setAR('reparatie_max_jaar',        (string)(int)($_POST['reparatie_max_jaar']        ?? 10), 'int');
            setAR('recycling_min_jaar',        (string)(int)($_POST['recycling_min_jaar']        ?? 10), 'int');
            setAR('garantie_alleen_nl',        isset($_POST['garantie_alleen_nl'])         ? '1' : '0', 'bool');
            setAR('reparatie_vereist_repareerbaar', isset($_POST['reparatie_vereist_repareerbaar']) ? '1' : '0', 'bool');
            setAR('taxatie_bij_schade',        isset($_POST['taxatie_bij_schade'])         ? '1' : '0', 'bool');
            setAR('coulance_aftrek_buitenland',(string)(int)($_POST['coulance_aftrek_buitenland'] ?? 30), 'int');
            $msg = 'Leeftijdsgrenzen & algemene instellingen opgeslagen.';
        }

        // ── Coulance-kans rangen opslaan ──────────────────────────────────
        if ($act === 'save_coulance_matrix') {
            $matrix   = [];
            $cnt      = (int)($_POST['matrix_count'] ?? 0);
            $gebruikte = [];
            for ($i = 0; $i < $cnt; $i++) {
                $pk  = trim($_POST["matrix_{$i}_prijsklasse"] ?? '');
                $bk  = (int)($_POST["matrix_{$i}_basis_kans"]      ?? 50);
                $pja = (int)($_POST["matrix_{$i}_per_jaar_aftrek"]  ?? 6);
                if (in_array($pk, $gebruikte, true)) continue; // duplicaat overslaan
                $gebruikte[] = $pk;
                $matrix[] = [
                    'prijsklasse'    => $pk,
                    'basis_kans'     => max(5, min(95, $bk)),
                    'per_jaar_aftrek'=> max(0, min(50, $pja)),
                ];
            }
            setAR('coulance_kans_matrix', json_encode($matrix, JSON_UNESCAPED_UNICODE), 'json');
            $msg = 'Coulance-kans rangen opgeslagen.';
        }

        // ── Defect/schade routing opslaan ──────────────────────────────────
        if ($act === 'save_defecten') {
            $reparatieUitsluit = $_POST['reparatie_uitsluiten'] ?? [];
            $taxatieUitsluit   = $_POST['taxatie_uitsluiten']   ?? [];
            $taxatieInclude    = $_POST['taxatie_include']       ?? [];
            $garantieUitsluit  = $_POST['garantie_uitsluiten']  ?? [];
            $coulanceUitsluit  = $_POST['coulance_uitsluiten']  ?? [];

            // Alleen geldige klacht-keys toestaan
            $geldig = array_keys($alleKlachten);
            $reparatieUitsluit = array_values(array_intersect($reparatieUitsluit, $geldig));
            $taxatieUitsluit   = array_values(array_intersect($taxatieUitsluit,   $geldig));
            $taxatieInclude    = array_values(array_intersect($taxatieInclude,    $geldig));
            $garantieUitsluit  = array_values(array_intersect($garantieUitsluit,  $geldig));
            $coulanceUitsluit  = array_values(array_intersect($coulanceUitsluit,  $geldig));

            setAR('reparatie_uitsluiten_klachten', json_encode($reparatieUitsluit, JSON_UNESCAPED_UNICODE), 'json');
            setAR('taxatie_uitsluiten_klachten',   json_encode($taxatieUitsluit,   JSON_UNESCAPED_UNICODE), 'json');
            setAR('taxatie_include_klachten',      json_encode($taxatieInclude,    JSON_UNESCAPED_UNICODE), 'json');
            setAR('garantie_uitsluiten_klachten',  json_encode($garantieUitsluit,  JSON_UNESCAPED_UNICODE), 'json');
            setAR('coulance_uitsluiten_klachten',  json_encode($coulanceUitsluit,  JSON_UNESCAPED_UNICODE), 'json');
            $msg = 'Defect/schade routing opgeslagen.';
        }

        // ── Merkfilters opslaan ───────────────────────────────────────────
        if ($act === 'save_merken') {
            foreach (['garantie_merken','coulance_merken','reparatie_merken','taxatie_merken'] as $mk) {
                $raw = $_POST[$mk . '_json'] ?? '[]';
                $arr = json_decode($raw, true);
                if (!is_array($arr)) $arr = [];
                $arr = array_values(array_filter(array_map('trim', $arr)));
                setAR($mk, json_encode($arr, JSON_UNESCAPED_UNICODE), 'json');
            }
            $msg = 'Merkfilters opgeslagen.';
        }
    }
}

// ── Huidige waarden ophalen ───────────────────────────────────────────────
$garantieTermijn      = (int) getAR('garantie_termijn_jaar',           '2');
$coulanceMinJaar      = (int) getAR('coulance_min_jaar',               '2');
$coulanceMaxJaar      = (int) getAR('coulance_max_jaar',               '5');
$reparatieMinJaar     = (int) getAR('reparatie_min_jaar',              '2');
$reparatieMaxJaar     = (int) getAR('reparatie_max_jaar',              '10');
$recyclingMinJaar     = (int) getAR('recycling_min_jaar',              '10');
$garantieAlleenNl     = getAR('garantie_alleen_nl',                    '1') === '1';
$repVereistRepbr      = getAR('reparatie_vereist_repareerbaar',        '1') === '1';
$taxatieBijSchade     = getAR('taxatie_bij_schade',                    '1') === '1';
$coulanceAftrekBl     = (int) getAR('coulance_aftrek_buitenland',      '30');

$stappenConfig        = json_decode(getAR('stappen_config', '[]'), true) ?: [];
$coulanceMatrix       = json_decode(getAR('coulance_kans_matrix', '[]'), true) ?: [];
$reparatieUitsluit    = json_decode(getAR('reparatie_uitsluiten_klachten', '["gebarsten_scherm"]'), true) ?: [];
$taxatieUitsluit      = json_decode(getAR('taxatie_uitsluiten_klachten',   '[]'), true) ?: [];
$taxatieInclude       = json_decode(getAR('taxatie_include_klachten',      '["gebarsten_scherm","stroomstoot"]'), true) ?: [];
$garantieUitsluit     = json_decode(getAR('garantie_uitsluiten_klachten',  '["gebarsten_scherm"]'), true) ?: [];
$coulanceUitsluit     = json_decode(getAR('coulance_uitsluiten_klachten',  '["gebarsten_scherm"]'), true) ?: [];
$garantieMerken       = json_decode(getAR('garantie_merken',  '[]'), true) ?: [];
$coulanceMerken       = json_decode(getAR('coulance_merken',  '[]'), true) ?: [];
$reparatieMerken      = json_decode(getAR('reparatie_merken', '[]'), true) ?: [];
$taxatieMerken        = json_decode(getAR('taxatie_merken',   '[]'), true) ?: [];

// Standaard stappenplan als DB leeg is
if (empty($stappenConfig)) {
    $stappenConfig = [
        ['nummer'=>1,'label'=>'Situatie',    'titel'=>'Wat is er aan de hand?',      'lead'=>'Dit bepaalt direct welke route het meest geschikt is.'],
        ['nummer'=>2,'label'=>'TV gegevens', 'titel'=>'Over je televisie',            'lead'=>'Merk, model en aankoopinformatie bepalen de route.'],
        ['nummer'=>3,'label'=>'Defect',      'titel'=>'Beschrijf het defect',         'lead'=>'Hoe specifieker, hoe beter het advies.'],
        ['nummer'=>4,'label'=>'Contact',     'titel'=>'Je contactgegevens',           'lead'=>'Hier sturen wij je persoonlijk advies naartoe.'],
    ];
}

// Standaard coulance-matrix als DB leeg is
if (empty($coulanceMatrix)) {
    $coulanceMatrix = [
        ['prijsklasse'=>'<500',      'basis_kans'=>35, 'per_jaar_aftrek'=>6],
        ['prijsklasse'=>'500-1000',  'basis_kans'=>55, 'per_jaar_aftrek'=>7],
        ['prijsklasse'=>'1000-2000', 'basis_kans'=>70, 'per_jaar_aftrek'=>8],
        ['prijsklasse'=>'>2000',     'basis_kans'=>85, 'per_jaar_aftrek'=>9],
        ['prijsklasse'=>'',          'basis_kans'=>50, 'per_jaar_aftrek'=>6],
    ];
}

$adminActivePage = 'advies-instellingen';
require_once __DIR__ . '/includes/admin-header.php';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <title>Advies Instellingen &ndash; Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Epilogue:wght@800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/base.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/components.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
  <meta name="robots" content="noindex,nofollow">
  <style>
    /* ── Tabs ── */
    .ai-tabs        { display:flex; gap:.25rem; border-bottom:2px solid #e5e7eb; margin-bottom:1.75rem; flex-wrap:wrap; }
    .ai-tab         { padding:.6rem 1.1rem; font-size:.82rem; font-weight:600; color:#6b7280;
                      border:none; background:none; cursor:pointer; border-bottom:2px solid transparent;
                      margin-bottom:-2px; border-radius:6px 6px 0 0; transition:color .15s,border-color .15s; }
    .ai-tab:hover   { color:#111827; background:#f9fafb; }
    .ai-tab.actief  { color:#111827; border-bottom-color:#111827; }
    .ai-tabpanel    { display:none; }
    .ai-tabpanel.actief { display:block; }

    /* ── Layout helpers ── */
    .rule-section {
      background:#fff; border:1px solid #e5e7eb; border-radius:12px;
      padding:1.5rem; display:flex; flex-direction:column; gap:1rem; margin-bottom:1.25rem;
    }
    .rule-section h2 { font-size:1rem; font-weight:700; color:#111827; margin:0 0 .1rem; display:flex; align-items:center; gap:.4rem; }
    .rule-section > p { font-size:.8rem; color:#6b7280; margin:0; }
    .two-col { display:grid; grid-template-columns:1fr 1fr; gap:.75rem; }
    .three-col { display:grid; grid-template-columns:1fr 1fr 1fr; gap:.75rem; }
    @media(max-width:700px){ .two-col,.three-col { grid-template-columns:1fr; } }

    label.field-label { font-size:.8rem; font-weight:600; color:#374151; display:block; margin-bottom:.3rem; }
    input[type=number], input[type=text], select {
      width:100%; padding:.5rem .75rem; border:1px solid #d1d5db; border-radius:8px;
      font-size:.875rem; color:#111827; background:#fff;
    }
    textarea.field-ta {
      width:100%; padding:.5rem .75rem; border:1px solid #d1d5db; border-radius:8px;
      font-size:.875rem; color:#111827; background:#fff; resize:vertical; min-height:60px;
    }
    input:focus, select:focus, textarea:focus {
      outline:none; border-color:#4f98a3; box-shadow:0 0 0 3px rgba(79,152,163,.15);
    }
    .toggle-row { display:flex; align-items:center; gap:.55rem; font-size:.875rem; color:#374151; cursor:pointer; }
    .toggle-row input[type=checkbox] { accent-color:#4f98a3; width:16px; height:16px; }

    /* ── Buttons ── */
    .btn-sm {
      display:inline-flex; align-items:center; gap:.4rem;
      background:#111827; color:#fff; border:none; border-radius:8px;
      padding:.5rem 1rem; font-size:.8rem; font-weight:600; cursor:pointer; transition:background .15s;
    }
    .btn-sm:hover { background:#1f2937; }
    .btn-outline {
      display:inline-flex; align-items:center; gap:.4rem;
      background:#fff; color:#374151; border:1px solid #d1d5db; border-radius:8px;
      padding:.45rem .9rem; font-size:.78rem; font-weight:600; cursor:pointer; transition:background .15s;
    }
    .btn-outline:hover { background:#f9fafb; }
    .btn-danger-sm {
      display:inline-flex; align-items:center; gap:.3rem;
      background:#fef2f2; color:#dc2626; border:1px solid #fecaca; border-radius:6px;
      padding:.3rem .65rem; font-size:.75rem; font-weight:600; cursor:pointer; transition:background .15s;
    }
    .btn-danger-sm:hover { background:#fee2e2; }

    /* ── Alert ── */
    .alert { padding:.75rem 1rem; border-radius:8px; font-size:.85rem; font-weight:600; margin-bottom:1.25rem; }
    .alert-success { background:#f0fdf4; border:1px solid #bbf7d0; color:#15803d; }
    .alert-error   { background:#fef2f2; border:1px solid #fecaca; color:#dc2626; }

    /* ── Stats ── */
    .stats-row { display:flex; flex-wrap:wrap; gap:.5rem; margin-bottom:1.25rem; }
    .stat-chip { font-size:.75rem; font-weight:600; padding:.3rem .75rem; border-radius:999px; }
    .sc-total  { background:#f3f4f6; color:#374151; }
    .sc-rep    { background:#eff6ff; color:#1d4ed8; }
    .sc-tax    { background:#fef9c3; color:#854d0e; }

    /* ── Route chips ── */
    .route-legenda { display:flex; flex-wrap:wrap; gap:.4rem; margin-bottom:1.25rem; }
    .route-chip    { font-size:.72rem; font-weight:700; padding:.25rem .65rem; border-radius:999px; }
    .chip-garantie  { background:#dcfce7; color:#15803d; }
    .chip-coulance  { background:#fce7f3; color:#9d174d; }
    .chip-reparatie { background:#dbeafe; color:#1e40af; }
    .chip-taxatie   { background:#fef9c3; color:#92400e; }
    .chip-recycling { background:#f3f4f6; color:#374151; }

    /* ── Merken grid ── */
    .merken-grid { display:flex; flex-wrap:wrap; gap:.4rem; margin-top:.4rem; }
    .merk-label  { display:flex; align-items:center; gap:.3rem; font-size:.78rem; font-weight:500;
                   color:#374151; background:#f9fafb; border:1px solid #e5e7eb; border-radius:6px;
                   padding:.25rem .6rem; cursor:pointer; transition:background .1s,border-color .1s; }
    .merk-label:hover { background:#f0f9ff; border-color:#bae6fd; }
    .merk-label:has(input:checked) { background:#eff6ff; border-color:#bfdbfe; }
    .merken-hint-all { font-size:.75rem; color:#6b7280; margin-bottom:.4rem; display:inline-block; }

    /* ── Stappenplan editor ── */
    .stap-editor-rij {
      border:1px solid #e5e7eb; border-radius:10px; padding:1rem 1.1rem;
      background:#f9fafb; display:flex; flex-direction:column; gap:.6rem; margin-bottom:.75rem;
    }
    .stap-editor-header { display:flex; align-items:center; justify-content:space-between; }
    .stap-nr-badge { font-size:.72rem; font-weight:800; background:#111827; color:#fff;
                     border-radius:999px; width:22px; height:22px; display:flex;
                     align-items:center; justify-content:center; flex-shrink:0; }

    /* ── Coulance matrix ── */
    .matrix-rij {
      border:1px solid #e5e7eb; border-radius:10px; padding:1rem 1.1rem;
      background:#f9fafb; margin-bottom:.75rem;
    }
    .matrix-rij-header { display:flex; align-items:center; justify-content:space-between; gap:.5rem; margin-bottom:.75rem; }
    .matrix-kans-preview {
      font-size:.72rem; font-weight:700; background:#eff6ff; color:#1e40af;
      border-radius:6px; padding:.2rem .5rem; white-space:nowrap;
    }

    /* ── Defect/schade checkboxen ── */
    .klacht-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(200px,1fr)); gap:.35rem; margin-top:.5rem; }
    .klacht-label { display:flex; align-items:center; gap:.45rem; font-size:.8rem; color:#374151;
                    background:#f9fafb; border:1px solid #e5e7eb; border-radius:7px;
                    padding:.35rem .65rem; cursor:pointer; transition:background .1s,border-color .1s; }
    .klacht-label:hover { background:#f0f9ff; border-color:#bae6fd; }
    .klacht-label:has(input:checked) { background:#fef2f2; border-color:#fecaca; color:#dc2626; font-weight:600; }
    .klacht-label.taxatie-incl:has(input:checked) { background:#fef9c3; border-color:#fde68a; color:#92400e; }

    .section-divider { border:none; border-top:1px solid #e5e7eb; margin:1.25rem 0; }
  </style>
</head>
<body>
<div class="adm-page">

  <h1>&#9881; Advies routing instellingen</h1>
  <p style="color:#6b7280;margin-bottom:.75rem;font-size:.875rem;">
    Configureer het gehele adviesformulier op <strong>advies.php</strong>.
    Wijzigingen zijn <strong>direct actief</strong>.
  </p>

  <div class="route-legenda">
    <span class="route-chip chip-garantie">&#9989; Garantie</span>
    <span class="route-chip chip-coulance">&#129309; Coulance</span>
    <span class="route-chip chip-reparatie">&#128295; Reparatie</span>
    <span class="route-chip chip-taxatie">&#128203; Taxatie</span>
    <span class="route-chip chip-recycling">&#9851; Recycling</span>
  </div>

  <div class="stats-row">
    <span class="stat-chip sc-total">&#128250; <?= $statsTotal ?> modellen totaal</span>
    <span class="stat-chip sc-rep">&#128295; <?= $statsRep ?> repareerbaar</span>
    <span class="stat-chip sc-tax">&#128203; <?= $statsTax ?> taxatie mogelijk</span>
  </div>

  <?php if ($msg): ?>
  <div class="alert alert-<?= $type ?>"><?= h($msg) ?></div>
  <?php endif; ?>

  <!-- TABS -->
  <div class="ai-tabs" role="tablist">
    <button class="ai-tab actief" role="tab" onclick="toonTab('stappen')">&#128203; Stappenplan</button>
    <button class="ai-tab" role="tab" onclick="toonTab('leeftijd')">&#128197; Leeftijd &amp; Algemeen</button>
    <button class="ai-tab" role="tab" onclick="toonTab('coulance')">&#129309; Coulance rangen</button>
    <button class="ai-tab" role="tab" onclick="toonTab('defecten')">&#9889; Defecten routing</button>
    <button class="ai-tab" role="tab" onclick="toonTab('merken')">&#127981; Merkfilters</button>
  </div>

  <!-- ════════════════════════════════════════════════════════════════════════
       TAB 1 – STAPPENPLAN
       ════════════════════════════════════════════════════════════════════════ -->
  <div class="ai-tabpanel actief" id="tab-stappen">
    <div class="rule-section">
      <h2>&#128203; Stappenplan configureren</h2>
      <p>Stel de labels, titels en lead-teksten in van elk formulierstap. De volgorde en labels worden ook gebruikt in de voortgangsbalk op advies.php.</p>

      <form method="POST" id="form-stappen">
        <input type="hidden" name="action" value="save_stappen">
        <input type="hidden" name="csrf_token" value="<?= csrf() ?>">
        <input type="hidden" name="stap_count" id="stap_count" value="<?= count($stappenConfig) ?>">

        <div id="stappen-container">
          <?php foreach ($stappenConfig as $si => $stap): ?>
          <div class="stap-editor-rij" id="stap-rij-<?= $si ?>">
            <div class="stap-editor-header">
              <div style="display:flex;align-items:center;gap:.6rem;">
                <span class="stap-nr-badge"><?= $stap['nummer'] ?></span>
                <strong style="font-size:.875rem;color:#111827;">Stap <?= $stap['nummer'] ?></strong>
              </div>
              <?php if (count($stappenConfig) > 2): ?>
              <button type="button" class="btn-danger-sm" onclick="verwijderStap(<?= $si ?>)">&#10005; Verwijder</button>
              <?php endif; ?>
            </div>
            <div class="two-col">
              <div>
                <label class="field-label">Label (voortgangsbalk)</label>
                <input type="text" name="stap_<?= $stap['nummer'] ?>_label"
                       value="<?= h($stap['label']) ?>" placeholder="Bijv. Situatie" maxlength="25">
              </div>
              <div>
                <label class="field-label">Staptitel (in het formulier)</label>
                <input type="text" name="stap_<?= $stap['nummer'] ?>_titel"
                       value="<?= h($stap['titel']) ?>" placeholder="Bijv. Wat is er aan de hand?" maxlength="80">
              </div>
            </div>
            <div>
              <label class="field-label">Lead-tekst (subtitel onder de staptitel)</label>
              <textarea class="field-ta" name="stap_<?= $stap['nummer'] ?>_lead"
                        placeholder="Bijv. Dit bepaalt direct welke route het meest geschikt is."><?= h($stap['lead']) ?></textarea>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <div style="display:flex;align-items:center;gap:.75rem;margin-top:.5rem;">
          <button type="button" class="btn-outline" onclick="voegStapToe()">&#43; Stap toevoegen</button>
          <button type="submit" class="btn-sm">&#128190; Stappenplan opslaan</button>
        </div>
      </form>
    </div>
  </div>

  <!-- ════════════════════════════════════════════════════════════════════════
       TAB 2 – LEEFTIJD & ALGEMEEN
       ════════════════════════════════════════════════════════════════════════ -->
  <div class="ai-tabpanel" id="tab-leeftijd">
    <div class="rule-section">
      <h2>&#128197; Leeftijdsgrenzen &amp; algemene routelogica</h2>
      <p>Stel de termijnen en vlaggen in die de routeberekening in advies.php aanstuurt.</p>

      <form method="POST">
        <input type="hidden" name="action" value="save_leeftijd">
        <input type="hidden" name="csrf_token" value="<?= csrf() ?>">

        <h3 style="font-size:.85rem;font-weight:700;color:#374151;margin-bottom:.6rem;">&#9989; Garantie</h3>
        <div class="two-col" style="margin-bottom:1rem;">
          <div>
            <label class="field-label">Garantietermijn (jaar)</label>
            <input type="number" name="garantie_termijn_jaar" min="0" max="20" value="<?= $garantieTermijn ?>">
            <p style="font-size:.73rem;color:#9ca3af;margin:.25rem 0 0;">Wettelijk 2 jaar. TV-merken hanteren soms 3–5 jaar.</p>
          </div>
          <div style="display:flex;flex-direction:column;justify-content:center;">
            <label class="toggle-row">
              <input type="checkbox" name="garantie_alleen_nl" <?= $garantieAlleenNl ? 'checked' : '' ?>>
              <span>Garantie alleen geldig bij aankoop in Nederland</span>
            </label>
          </div>
        </div>

        <hr class="section-divider">
        <h3 style="font-size:.85rem;font-weight:700;color:#374151;margin-bottom:.6rem;">&#129309; Coulance</h3>
        <div class="three-col" style="margin-bottom:.5rem;">
          <div>
            <label class="field-label">Coulance — min leeftijd (jaar)</label>
            <input type="number" name="coulance_min_jaar" min="0" max="20" value="<?= $coulanceMinJaar ?>">
          </div>
          <div>
            <label class="field-label">Coulance — max leeftijd (jaar)</label>
            <input type="number" name="coulance_max_jaar" min="0" max="20" value="<?= $coulanceMaxJaar ?>">
          </div>
          <div>
            <label class="field-label">Aftrek bij aankoop buitenland (%)</label>
            <input type="number" name="coulance_aftrek_buitenland" min="0" max="100" value="<?= $coulanceAftrekBl ?>">
          </div>
        </div>

        <hr class="section-divider">
        <h3 style="font-size:.85rem;font-weight:700;color:#374151;margin-bottom:.6rem;">&#128295; Reparatie</h3>
        <div class="three-col" style="margin-bottom:.5rem;">
          <div>
            <label class="field-label">Reparatie — min leeftijd (jaar)</label>
            <input type="number" name="reparatie_min_jaar" min="0" max="20" value="<?= $reparatieMinJaar ?>">
          </div>
          <div>
            <label class="field-label">Reparatie — max leeftijd (jaar)</label>
            <input type="number" name="reparatie_max_jaar" min="0" max="30" value="<?= $reparatieMaxJaar ?>">
          </div>
          <div style="display:flex;flex-direction:column;justify-content:center;">
            <label class="toggle-row">
              <input type="checkbox" name="reparatie_vereist_repareerbaar" <?= $repVereistRepbr ? 'checked' : '' ?>>
              <span>Vereist dat model als "repareerbaar" staat in de database</span>
            </label>
          </div>
        </div>

        <hr class="section-divider">
        <h3 style="font-size:.85rem;font-weight:700;color:#374151;margin-bottom:.6rem;">&#128203; Taxatie</h3>
        <div style="margin-bottom:.5rem;">
          <label class="toggle-row">
            <input type="checkbox" name="taxatie_bij_schade" <?= $taxatieBijSchade ? 'checked' : '' ?>>
            <span>Taxatieroute activeren bij "schade door externe oorzaak"</span>
          </label>
        </div>

        <hr class="section-divider">
        <h3 style="font-size:.85rem;font-weight:700;color:#374151;margin-bottom:.6rem;">&#9851; Recycling</h3>
        <div class="two-col" style="margin-bottom:1rem;">
          <div>
            <label class="field-label">Recycling — min leeftijd (jaar)</label>
            <input type="number" name="recycling_min_jaar" min="1" max="30" value="<?= $recyclingMinJaar ?>">
            <p style="font-size:.73rem;color:#9ca3af;margin:.25rem 0 0;">TV ouder dan dit jaar → altijd recyclingadvies.</p>
          </div>
        </div>

        <button type="submit" class="btn-sm">&#128190; Opslaan</button>
      </form>
    </div>
  </div>

  <!-- ════════════════════════════════════════════════════════════════════════
       TAB 3 – COULANCE RANGEN
       ════════════════════════════════════════════════════════════════════════ -->
  <div class="ai-tabpanel" id="tab-coulance">
    <div class="rule-section">
      <h2>&#129309; Coulance-kans rangen</h2>
      <p>
        Stel per prijsklasse de basiskansprocenten en de jaarlijkse aftrek in.
        Hoe duurder de TV, hoe groter de kans op coulance. Elke prijsklasse mag maximaal één keer voorkomen.
        De rij met <strong>lege prijsklasse</strong> is de fallback voor onbekende/niet-ingevulde prijs.
      </p>

      <form method="POST" id="form-matrix">
        <input type="hidden" name="action" value="save_coulance_matrix">
        <input type="hidden" name="csrf_token" value="<?= csrf() ?>">
        <input type="hidden" name="matrix_count" id="matrix_count" value="<?= count($coulanceMatrix) ?>">

        <div id="matrix-container">
          <?php foreach ($coulanceMatrix as $mi => $rij): ?>
          <div class="matrix-rij" id="matrix-rij-<?= $mi ?>">
            <div class="matrix-rij-header">
              <strong style="font-size:.85rem;color:#111827;">
                <?= h($allePrijsklassen[$rij['prijsklasse']] ?? 'Onbekend') ?>
              </strong>
              <div style="display:flex;align-items:center;gap:.5rem;">
                <span class="matrix-kans-preview" id="preview-<?= $mi ?>">
                  <?= $rij['basis_kans'] ?>% basis
                </span>
                <button type="button" class="btn-danger-sm" onclick="verwijderMatrixRij(<?= $mi ?>)">&#10005;</button>
              </div>
            </div>
            <input type="hidden" name="matrix_<?= $mi ?>_prijsklasse" value="<?= h($rij['prijsklasse']) ?>">
            <div class="two-col">
              <div>
                <label class="field-label">Basiskans (%)</label>
                <input type="number" name="matrix_<?= $mi ?>_basis_kans" min="5" max="95"
                       value="<?= (int)$rij['basis_kans'] ?>"
                       oninput="updateMatrixPreview(<?= $mi ?>)">
                <p style="font-size:.72rem;color:#9ca3af;margin:.2rem 0 0;">Coulancekans als TV precies de minimumleeftijd heeft bereikt.</p>
              </div>
              <div>
                <label class="field-label">Aftrek per jaar boven minimum (%)</label>
                <input type="number" name="matrix_<?= $mi ?>_per_jaar_aftrek" min="0" max="50"
                       value="<?= (int)$rij['per_jaar_aftrek'] ?>"
                       oninput="updateMatrixPreview(<?= $mi ?>)">
                <p style="font-size:.72rem;color:#9ca3af;margin:.2rem 0 0;">Wordt elk jaar afgetrokken van de basiskans na de minimum leeftijd.</p>
              </div>
            </div>
            <div id="preview-detail-<?= $mi ?>" style="font-size:.75rem;color:#6b7280;margin-top:.4rem;"></div>
          </div>
          <?php endforeach; ?>
        </div>

        <div style="margin-bottom:1rem;">
          <label class="field-label" style="margin-bottom:.4rem;">Prijsklasse toevoegen</label>
          <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
            <?php foreach ($allePrijsklassen as $pk => $pklbl): ?>
            <button type="button" class="btn-outline voeg-pk-toe"
                    data-pk="<?= h($pk) ?>" data-pklbl="<?= h($pklbl) ?>"
                    onclick="voegMatrixRijToe('<?= h($pk) ?>', '<?= h($pklbl) ?>')">
              &#43; <?= h($pklbl) ?>
            </button>
            <?php endforeach; ?>
          </div>
        </div>

        <button type="submit" class="btn-sm">&#128190; Rangen opslaan</button>
      </form>
    </div>

    <!-- Uitlegkader -->
    <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:1rem 1.25rem;font-size:.8rem;color:#92400e;margin-top:1rem;">
      <strong>&#9888; Rekenvoorbeeld:</strong><br>
      Een TV van €1.200, 4 jaar oud. Coulance-minimum = 2 jaar, basiskans = 70%, aftrek = 8%/jaar.<br>
      Leeftijd boven minimum = 4 − 2 = <strong>2 jaar</strong>. Kans = 70 − (8 × 2) = <strong>54%</strong>.<br>
      Bij aankoop in buitenland: 54 − <?= $coulanceAftrekBl ?>% aftrek = <?= max(5, 54 - $coulanceAftrekBl) ?>%.
    </div>
  </div>

  <!-- ════════════════════════════════════════════════════════════════════════
       TAB 4 – DEFECTEN ROUTING
       ════════════════════════════════════════════════════════════════════════ -->
  <div class="ai-tabpanel" id="tab-defecten">
    <div class="rule-section">
      <h2>&#9889; Defect &amp; schade routing</h2>
      <p>
        Bepaal per defect/klachttype welke routes worden <strong>uitgesloten</strong> of <strong>verplicht doorgestuurd</strong>.
        Hou rekening met de merkfilters en de database-instellingen (repareerbaar / taxatie per model).
      </p>

      <form method="POST">
        <input type="hidden" name="action" value="save_defecten">
        <input type="hidden" name="csrf_token" value="<?= csrf() ?>">

        <!-- Reparatie uitsluiten -->
        <h3 style="font-size:.87rem;font-weight:700;color:#1e40af;margin-bottom:.4rem;">
          &#128295; Niet in aanmerking voor reparatie (uitgesloten)
        </h3>
        <p style="font-size:.78rem;color:#6b7280;margin-bottom:.6rem;">
          Defecten die aangevinkt zijn, worden <strong>nooit</strong> naar de reparatieroute gestuurd
          (zelfs als het model repareerbaar is in de database).
        </p>
        <div class="klacht-grid">
          <?php foreach ($alleKlachten as $kv => $kl): ?>
          <label class="klacht-label">
            <input type="checkbox" name="reparatie_uitsluiten[]" value="<?= h($kv) ?>"
                   <?= in_array($kv, $reparatieUitsluit, true) ? 'checked' : '' ?>>
            <span><?= h($kl) ?></span>
          </label>
          <?php endforeach; ?>
        </div>

        <hr class="section-divider">

        <!-- Taxatie verplicht bij -->
        <h3 style="font-size:.87rem;font-weight:700;color:#92400e;margin-bottom:.4rem;">
          &#128203; Verplicht naar taxatie (schadetaxatie)
        </h3>
        <p style="font-size:.78rem;color:#6b7280;margin-bottom:.6rem;">
          Defecten die aangevinkt zijn, worden <strong>altijd</strong> naar de taxatieroute gestuurd
          — mits het merk/model in aanmerking komt voor taxatie.
          Bijv. barst in scherm = verzekeringskwestie → schadetaxatie.
        </p>
        <div class="klacht-grid">
          <?php foreach ($alleKlachten as $kv => $kl): ?>
          <label class="klacht-label taxatie-incl">
            <input type="checkbox" name="taxatie_include[]" value="<?= h($kv) ?>"
                   <?= in_array($kv, $taxatieInclude, true) ? 'checked' : '' ?>>
            <span><?= h($kl) ?></span>
          </label>
          <?php endforeach; ?>
        </div>

        <hr class="section-divider">

        <!-- Garantie uitsluiten -->
        <h3 style="font-size:.87rem;font-weight:700;color:#15803d;margin-bottom:.4rem;">
          &#9989; Niet in aanmerking voor garantie (uitgesloten)
        </h3>
        <p style="font-size:.78rem;color:#6b7280;margin-bottom:.6rem;">
          Defecten die aangevinkt zijn, worden <strong>nooit</strong> naar de garantieroute gestuurd.
          Barst in scherm valt bijv. altijd buiten de wettelijke garantie.
        </p>
        <div class="klacht-grid">
          <?php foreach ($alleKlachten as $kv => $kl): ?>
          <label class="klacht-label">
            <input type="checkbox" name="garantie_uitsluiten[]" value="<?= h($kv) ?>"
                   <?= in_array($kv, $garantieUitsluit, true) ? 'checked' : '' ?>>
            <span><?= h($kl) ?></span>
          </label>
          <?php endforeach; ?>
        </div>

        <hr class="section-divider">

        <!-- Coulance uitsluiten -->
        <h3 style="font-size:.87rem;font-weight:700;color:#9d174d;margin-bottom:.4rem;">
          &#129309; Niet in aanmerking voor coulance (uitgesloten)
        </h3>
        <p style="font-size:.78rem;color:#6b7280;margin-bottom:.6rem;">
          Defecten die aangevinkt zijn, worden <strong>nooit</strong> naar de coulanceroute gestuurd.
        </p>
        <div class="klacht-grid">
          <?php foreach ($alleKlachten as $kv => $kl): ?>
          <label class="klacht-label">
            <input type="checkbox" name="coulance_uitsluiten[]" value="<?= h($kv) ?>"
                   <?= in_array($kv, $coulanceUitsluit, true) ? 'checked' : '' ?>>
            <span><?= h($kl) ?></span>
          </label>
          <?php endforeach; ?>
        </div>

        <hr class="section-divider">

        <!-- Taxatie uitsluiten -->
        <h3 style="font-size:.87rem;font-weight:700;color:#92400e;margin-bottom:.4rem;">
          &#128203; Niet in aanmerking voor taxatie (uitgesloten)
        </h3>
        <p style="font-size:.78rem;color:#6b7280;margin-bottom:.6rem;">
          Defecten die <em>nooit</em> naar de taxatieroute mogen, ook al is het merk/model taxeerbaar.
        </p>
        <div class="klacht-grid">
          <?php foreach ($alleKlachten as $kv => $kl): ?>
          <label class="klacht-label">
            <input type="checkbox" name="taxatie_uitsluiten[]" value="<?= h($kv) ?>"
                   <?= in_array($kv, $taxatieUitsluit, true) ? 'checked' : '' ?>>
            <span><?= h($kl) ?></span>
          </label>
          <?php endforeach; ?>
        </div>

        <hr class="section-divider">
        <button type="submit" class="btn-sm">&#128190; Defecten routing opslaan</button>
      </form>
    </div>
  </div>

  <!-- ════════════════════════════════════════════════════════════════════════
       TAB 5 – MERKFILTERS
       ════════════════════════════════════════════════════════════════════════ -->
  <div class="ai-tabpanel" id="tab-merken">
    <div class="rule-section">
      <h2>&#127981; Merkfilters</h2>
      <p>
        Laat leeg = <strong>alle merken</strong>. Vink merken aan om uitsluitend
        die te activeren voor de betreffende route. De database-instellingen per model
        (repareerbaar / taxatie aangevinkt) hebben altijd prioriteit boven deze merkfilter.
      </p>
      <form method="POST">
        <input type="hidden" name="action" value="save_merken">
        <input type="hidden" name="csrf_token" value="<?= csrf() ?>">
        <?php
        $merkGroepen = [
            ['key'=>'garantie_merken',  'label'=>'&#9989; Garantie-merken',   'chip'=>'chip-garantie',  'selected'=>$garantieMerken],
            ['key'=>'coulance_merken',  'label'=>'&#129309; Coulance-merken',  'chip'=>'chip-coulance',  'selected'=>$coulanceMerken],
            ['key'=>'reparatie_merken', 'label'=>'&#128295; Reparatie-merken', 'chip'=>'chip-reparatie', 'selected'=>$reparatieMerken],
            ['key'=>'taxatie_merken',   'label'=>'&#128203; Taxatie-merken',   'chip'=>'chip-taxatie',   'selected'=>$taxatieMerken],
        ];
        foreach ($merkGroepen as $grp): ?>
        <div style="margin-bottom:1.5rem;">
          <label class="field-label" style="font-size:.85rem;">
            <span class="route-chip <?= $grp['chip'] ?>" style="margin-right:.3rem;"><?= $grp['label'] ?></span>
          </label>
          <input type="hidden" id="<?= $grp['key'] ?>_json" name="<?= $grp['key'] ?>_json"
                 value="<?= h(json_encode($grp['selected'], JSON_UNESCAPED_UNICODE)) ?>">
          <span class="merken-hint-all" id="<?= $grp['key'] ?>_hint"
                style="<?= empty($grp['selected']) ? '' : 'display:none' ?>">Alle merken (geen filter)</span>
          <div class="merken-grid" id="<?= $grp['key'] ?>_grid">
            <?php foreach ($beschikbareMerken as $merk): ?>
            <label class="merk-label">
              <input type="checkbox" class="merk-cb" data-group="<?= $grp['key'] ?>"
                     value="<?= h($merk) ?>"
                     <?= in_array($merk, $grp['selected'], true) ? 'checked' : '' ?> style="display:none">
              <span><?= h($merk) ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endforeach; ?>
        <button type="submit" class="btn-sm">&#128190; Merkfilters opslaan</button>
      </form>
    </div>
  </div>

</div><!-- /.adm-page -->

<script>
// ── Tabs ────────────────────────────────────────────────────────────────
function toonTab(id) {
  document.querySelectorAll('.ai-tab').forEach((t,i) => {
    t.classList.toggle('actief', t.getAttribute('onclick').includes("'" + id + "'"));
  });
  document.querySelectorAll('.ai-tabpanel').forEach(p => {
    p.classList.toggle('actief', p.id === 'tab-' + id);
  });
}

// ── Stappenplan: stap toevoegen/verwijderen ──────────────────────────────
let stapCount = <?= count($stappenConfig) ?>;

function voegStapToe() {
  stapCount++;
  document.getElementById('stap_count').value = stapCount;
  const container = document.getElementById('stappen-container');
  const idx       = stapCount - 1;
  const div       = document.createElement('div');
  div.className   = 'stap-editor-rij';
  div.id          = 'stap-rij-' + idx;
  div.innerHTML   = `
    <div class="stap-editor-header">
      <div style="display:flex;align-items:center;gap:.6rem;">
        <span class="stap-nr-badge">${stapCount}</span>
        <strong style="font-size:.875rem;color:#111827;">Stap ${stapCount}</strong>
      </div>
      <button type="button" class="btn-danger-sm" onclick="verwijderStap(${idx})">&#10005; Verwijder</button>
    </div>
    <div class="two-col">
      <div>
        <label class="field-label">Label (voortgangsbalk)</label>
        <input type="text" name="stap_${stapCount}_label" placeholder="Bijv. Extra stap" maxlength="25">
      </div>
      <div>
        <label class="field-label">Staptitel (in het formulier)</label>
        <input type="text" name="stap_${stapCount}_titel" placeholder="Bijv. Aanvullende informatie" maxlength="80">
      </div>
    </div>
    <div>
      <label class="field-label">Lead-tekst</label>
      <textarea class="field-ta" name="stap_${stapCount}_lead" placeholder="Bijv. Vul aanvullende gegevens in."></textarea>
    </div>`;
  container.appendChild(div);
}

function verwijderStap(idx) {
  const el = document.getElementById('stap-rij-' + idx);
  if (el) el.remove();
  stapCount = Math.max(0, stapCount - 1);
  document.getElementById('stap_count').value = stapCount;
}

// ── Coulance matrix: rij toevoegen/verwijderen ───────────────────────────
let matrixCount = <?= count($coulanceMatrix) ?>;

// Bijhouden welke prijsklassen al in gebruik zijn
function getGebruiktePrijsklassen() {
  const inputs = document.querySelectorAll('[name^="matrix_"][name$="_prijsklasse"]');
  return [...inputs].map(i => i.value);
}

function voegMatrixRijToe(pk, pklbl) {
  // Controleer of al aanwezig
  if (getGebruiktePrijsklassen().includes(pk)) {
    alert('De prijsklasse "' + pklbl + '" is al toegevoegd.');
    return;
  }
  const idx       = matrixCount;
  matrixCount++;
  document.getElementById('matrix_count').value = matrixCount;
  const container = document.getElementById('matrix-container');
  const div       = document.createElement('div');
  div.className   = 'matrix-rij';
  div.id          = 'matrix-rij-' + idx;
  div.innerHTML   = `
    <div class="matrix-rij-header">
      <strong style="font-size:.85rem;color:#111827;">${pklbl}</strong>
      <div style="display:flex;align-items:center;gap:.5rem;">
        <span class="matrix-kans-preview" id="preview-${idx}">50% basis</span>
        <button type="button" class="btn-danger-sm" onclick="verwijderMatrixRij(${idx})">&#10005;</button>
      </div>
    </div>
    <input type="hidden" name="matrix_${idx}_prijsklasse" value="${pk}">
    <div class="two-col">
      <div>
        <label class="field-label">Basiskans (%)</label>
        <input type="number" name="matrix_${idx}_basis_kans" min="5" max="95" value="50"
               oninput="updateMatrixPreview(${idx})">
        <p style="font-size:.72rem;color:#9ca3af;margin:.2rem 0 0;">Kans op coulance bij minimumleeftijd.</p>
      </div>
      <div>
        <label class="field-label">Aftrek per jaar boven minimum (%)</label>
        <input type="number" name="matrix_${idx}_per_jaar_aftrek" min="0" max="50" value="6"
               oninput="updateMatrixPreview(${idx})">
        <p style="font-size:.72rem;color:#9ca3af;margin:.2rem 0 0;">Jaarlijkse aftrek na de minimum leeftijd.</p>
      </div>
    </div>
    <div id="preview-detail-${idx}" style="font-size:.75rem;color:#6b7280;margin-top:.4rem;"></div>`;
  container.appendChild(div);
  updateMatrixPreview(idx);
}

function verwijderMatrixRij(idx) {
  const el = document.getElementById('matrix-rij-' + idx);
  if (el) el.remove();
}

function updateMatrixPreview(idx) {
  const bk  = parseInt(document.querySelector(`[name="matrix_${idx}_basis_kans"]`)?.value)  || 50;
  const pja = parseInt(document.querySelector(`[name="matrix_${idx}_per_jaar_aftrek"]`)?.value) || 0;
  const prev = document.getElementById('preview-' + idx);
  if (prev) prev.textContent = bk + '% basis';
  const det = document.getElementById('preview-detail-' + idx);
  if (det) {
    const cMin = <?= $coulanceMinJaar ?>;
    const voorbeeldLeeftijden = [cMin, cMin+1, cMin+2, cMin+3];
    const voorbeeldKansen = voorbeeldLeeftijden.map(l => {
      const k = Math.max(5, Math.min(95, bk - pja * (l - cMin)));
      return `${l}j→${k}%`;
    });
    det.textContent = 'Voorbeeld: ' + voorbeeldKansen.join(', ');
  }
}

// Matrix previews initialiseren
<?php foreach ($coulanceMatrix as $mi => $rij): ?>
updateMatrixPreview(<?= $mi ?>);
<?php endforeach; ?>

// ── Merken checkboxen → hidden JSON ─────────────────────────────────────
document.querySelectorAll('.merk-cb').forEach(cb => {
  cb.addEventListener('change', () => syncMerkenJson(cb.dataset.group));
});
function syncMerkenJson(group) {
  const checked = [...document.querySelectorAll(`.merk-cb[data-group="${group}"]:checked`)].map(c => c.value);
  const hid = document.getElementById(group + '_json');
  if (hid) hid.value = JSON.stringify(checked);
  const hint = document.getElementById(group + '_hint');
  if (hint) hint.style.display = checked.length === 0 ? 'inline-block' : 'none';
}
['garantie_merken','coulance_merken','reparatie_merken','taxatie_merken'].forEach(syncMerkenJson);
</script>
</body>
</html>