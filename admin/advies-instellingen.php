<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/advies_regels.php';
requireAdmin();

$msg  = '';
$type = 'success';

// =====================================================================
// OPSLAAN
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = db();

        // ── Bool-velden: altijd '0' tenzij checkbox aangevinkt ─────────
        $bools = ['garantie_alleen_nl', 'reparatie_vereist_repareerbaar', 'taxatie_bij_schade'];
        foreach ($bools as $bk) {
            $pdo->prepare('UPDATE advies_regels SET regel_waarde = ? WHERE regel_key = ?')
                ->execute([isset($_POST[$bk]) ? '1' : '0', $bk]);
        }

        // ── Int-velden ─────────────────────────────────────────────────
        $ints = [
            'garantie_termijn_jaar',
            'coulance_min_jaar', 'coulance_max_jaar',
            'coulance_aftrek_buitenland', 'coulance_aftrek_failliet',
            'reparatie_min_jaar', 'reparatie_max_jaar',
            'recycling_min_jaar',
        ];
        foreach ($ints as $k) {
            if (!isset($_POST[$k])) continue;
            $pdo->prepare('UPDATE advies_regels SET regel_waarde = ? WHERE regel_key = ?')
                ->execute([(string)(int)$_POST[$k], $k]);
        }

        // ── JSON merk-lijsten (hidden velden via JS checkbox-sync) ─────
        $jsonMerken = [
            'garantie_merken', 'coulance_merken', 'reparatie_merken', 'taxatie_merken'
        ];
        foreach ($jsonMerken as $k) {
            if (!isset($_POST[$k])) continue;
            $decoded = json_decode($_POST[$k], true);
            if (!is_array($decoded)) $decoded = [];
            $pdo->prepare('UPDATE advies_regels SET regel_waarde = ? WHERE regel_key = ?')
                ->execute([json_encode(array_values($decoded), JSON_UNESCAPED_UNICODE), $k]);
        }

        // ── JSON klacht-uitsluitingen ──────────────────────────────────
        $jsonKlachten = ['garantie_uitsluiten_klachten', 'coulance_uitsluiten_klachten'];
        foreach ($jsonKlachten as $k) {
            if (!isset($_POST[$k])) continue;
            $decoded = json_decode($_POST[$k], true);
            if ($decoded === null) {
                // Probeer als komma-gescheiden tekst
                $decoded = array_filter(array_map('trim', explode(',', $_POST[$k])));
            }
            if (!is_array($decoded)) $decoded = [];
            $pdo->prepare('UPDATE advies_regels SET regel_waarde = ? WHERE regel_key = ?')
                ->execute([json_encode(array_values($decoded), JSON_UNESCAPED_UNICODE), $k]);
        }

        // ── Coulance kansmatrix: opgebouwd uit matrix_basis[] + matrix_aftrek[] ─
        // Dit wordt UITSLUITEND via de tabel-inputs aangeboden (geen vrije textarea)
        // zodat de data altijd geldig is.
        $prijsklassen = ['', '<500', '500-1000', '1000-2000', '>2000'];
        if (isset($_POST['matrix_basis'])) {
            $matrix = [];
            foreach ($prijsklassen as $pk) {
                $matrix[] = [
                    'prijsklasse'    => $pk,
                    'basis_kans'     => max(0, min(100, (int)($_POST['matrix_basis'][$pk]  ?? 50))),
                    'per_jaar_aftrek'=> max(0, min(50,  (int)($_POST['matrix_aftrek'][$pk] ?? 6))),
                ];
            }
            $pdo->prepare('UPDATE advies_regels SET regel_waarde = ? WHERE regel_key = ?')
                ->execute([json_encode($matrix, JSON_UNESCAPED_UNICODE), 'coulance_kans_matrix']);
        }

        if (!$msg) $msg = 'Instellingen succesvol opgeslagen. Wijzigingen zijn direct actief in het adviesformulier.';

    } catch (Exception $e) {
        $msg  = 'Fout bij opslaan: ' . $e->getMessage();
        $type = 'error';
    }
}

// Laad de (verse) regels NADAT opslaan
$r = getAdviesRegels();

// Merk-standaarden voor uitzonderingsberekening
$repareerbareMerken = is_array($r['reparatie_merken'] ?? null) ? $r['reparatie_merken'] : [];
$taxatieMerken      = is_array($r['taxatie_merken']   ?? null) ? $r['taxatie_merken']   : [];

// Model-uitzonderingen: modellen die afwijken van hun merk-standaard
$uitzRep = $uitzTax = [];
try {
    foreach (
        db()->query('SELECT merk, modelnummer, repareerbaar, taxatie FROM tv_modellen WHERE actief=1 ORDER BY merk, modelnummer')
            ->fetchAll(PDO::FETCH_ASSOC)
        as $m
    ) {
        $dRep = empty($repareerbareMerken)
            || in_array(mb_strtolower(trim($m['merk'])), array_map('mb_strtolower', $repareerbareMerken), true);
        $dTax = empty($taxatieMerken)
            || in_array(mb_strtolower(trim($m['merk'])), array_map('mb_strtolower', $taxatieMerken), true);

        if (!$dRep && (int)$m['repareerbaar'] === 1) $uitzRep[] = $m + ['_type' => 'positief'];
        elseif ($dRep  && (int)$m['repareerbaar'] === 0) $uitzRep[] = $m + ['_type' => 'negatief'];

        if (!$dTax && (int)$m['taxatie'] === 1) $uitzTax[] = $m + ['_type' => 'positief'];
        elseif ($dTax  && (int)$m['taxatie'] === 0) $uitzTax[] = $m + ['_type' => 'negatief'];
    }
} catch (Exception $e) {}

// Haal alle unieke merken uit de TV-database
$alleMerken = [];
try {
    $alleMerken = db()->query(
        'SELECT DISTINCT merk FROM tv_modellen WHERE merk IS NOT NULL AND merk != "" AND actief=1 ORDER BY merk'
    )->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) { $alleMerken = []; }

// Haal statistieken op
$statsRep = $statsTax = $statsTotal = 0;
try {
    $statsTotal = (int)db()->query('SELECT COUNT(*) FROM tv_modellen WHERE actief=1')->fetchColumn();
    $statsRep   = (int)db()->query('SELECT COUNT(*) FROM tv_modellen WHERE actief=1 AND repareerbaar=1')->fetchColumn();
    $statsTax   = (int)db()->query('SELECT COUNT(*) FROM tv_modellen WHERE actief=1 AND taxatie=1')->fetchColumn();
} catch (Exception $e) {}

function jsPretty($v): string {
    return json_encode($v, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
function selectedMerken($v): array {
    if (is_array($v)) return $v;
    $d = json_decode($v ?? '[]', true);
    return is_array($d) ? $d : [];
}

// Coulance matrix als geindexeerde array
$matrix    = $r['coulance_kans_matrix'] ?? [];
$matrixIdx = [];
foreach ($matrix as $row) $matrixIdx[$row['prijsklasse']] = $row;
$prijsklassenLabels = [
    ''          => 'Onbekend',
    '<500'      => '< €500',
    '500-1000'  => '€500 – €1.000',
    '1000-2000' => '€1.000 – €2.000',
    '>2000'     => '> €2.000',
];
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
    .rule-section h3 {
      font-size: .95rem; font-weight: 700; margin: 0;
      padding-bottom: .75rem; border-bottom: 1px solid #f1f5f9;
      display: flex; align-items: center; gap: .5rem;
    }
    .full-width { grid-column: 1 / -1; }
    /* Badges */
    .rule-badge {
      font-size: .6rem; font-weight: 700; padding: .2rem .55rem;
      border-radius: 999px; text-transform: uppercase; letter-spacing: .06em;
    }
    .badge-garantie  { background:#dcfce7; color:#14532d; }
    .badge-coulance  { background:#fef9c3; color:#78350f; }
    .badge-reparatie { background:#dbeafe; color:#1e3a8a; }
    .badge-recycling { background:#f1f5f9; color:#334155; }
    .badge-taxatie   { background:#ede9fe; color:#3b0764; }
    /* Veld */
    .field { display:flex; flex-direction:column; gap:.3rem; }
    .field label { font-size:.82rem; font-weight:600; color:#374151; }
    .field .hint { font-size:.73rem; color:#9ca3af; margin:0; }
    .field input[type=number], .field input[type=text], .field textarea {
      width:100%; padding:.5rem .75rem; border:1px solid #d1d5db;
      border-radius:8px; font-size:.875rem; transition:border-color .15s;
    }
    .field textarea {
      font-family:'Courier New',monospace; font-size:.78rem;
      min-height:90px; resize:vertical;
    }
    .field input:focus, .field textarea:focus {
      outline:none; border-color:#287864; box-shadow:0 0 0 3px rgba(40,120,100,.12);
    }
    .two-col { display:grid; grid-template-columns:1fr 1fr; gap:.75rem; }
    /* Checkbox veld */
    .cb-field { display:flex; align-items:flex-start; gap:.6rem; }
    .cb-field input[type=checkbox] {
      width:16px; height:16px; margin-top:.2rem; flex-shrink:0;
      accent-color:#287864; cursor:pointer;
    }
    .cb-field label { font-size:.875rem; color:#374151; cursor:pointer; }
    /* Merk-grid */
    .merken-grid {
      display:flex; flex-wrap:wrap; gap:.35rem .6rem; margin-top:.25rem;
    }
    .merken-grid label {
      display:flex; align-items:center; gap:.3rem;
      font-size:.8rem; color:#374151; cursor:pointer;
      background:#f8fafc; border:1px solid #e5e7eb; border-radius:6px;
      padding:.28rem .55rem; transition:all .12s;
    }
    .merken-grid label:hover { background:#f0fdf4; border-color:#287864; }
    .merken-grid label:has(input:checked) {
      background:#dcfce7; border-color:#287864; color:#14532d; font-weight:600;
    }
    .merken-grid input[type=checkbox] { width:13px; height:13px; accent-color:#287864; }
    .merken-hint-all {
      font-size:.73rem; color:#059669; background:#ecfdf5;
      border:1px solid #a7f3d0; border-radius:5px; padding:.25rem .55rem;
    }
    /* Kansmatrix */
    .matrix-table { width:100%; border-collapse:collapse; font-size:.82rem; }
    .matrix-table th {
      background:#f8fafc; font-weight:600; color:#475569;
      padding:.4rem .6rem; text-align:left; border:1px solid #e5e7eb;
    }
    .matrix-table td { padding:.35rem .5rem; border:1px solid #e5e7eb; }
    .matrix-table td:first-child { color:#374151; font-weight:500; }
    .matrix-table input {
      width:100%; border:1px solid #d1d5db; border-radius:5px;
      font-size:.82rem; padding:.25rem .4rem; text-align:center;
    }
    .matrix-table input:focus { outline:none; border-color:#287864; }
    /* Stats chips */
    .stats-row { display:flex; gap:.5rem; flex-wrap:wrap; margin-bottom:.5rem; }
    .stat-chip {
      font-size:.75rem; font-weight:600; padding:.25rem .65rem;
      border-radius:999px; display:flex; align-items:center; gap:.3rem;
    }
    .sc-total   { background:#f1f5f9; color:#475569; }
    .sc-rep     { background:#dbeafe; color:#1e3a8a; }
    .sc-tax     { background:#ede9fe; color:#3b0764; }
    /* Route legenda */
    .route-legenda { display:flex; flex-wrap:wrap; gap:.4rem; margin-bottom:1.25rem; }
    .route-chip {
      display:flex; align-items:center; gap:.3rem;
      padding:.3rem .75rem; border-radius:999px; font-size:.75rem; font-weight:700;
    }
    .chip-garantie  { background:#dcfce7; color:#14532d; }
    .chip-coulance  { background:#fef9c3; color:#78350f; }
    .chip-reparatie { background:#dbeafe; color:#1e3a8a; }
    .chip-recycling { background:#f1f5f9; color:#334155; }
    .chip-taxatie   { background:#ede9fe; color:#3b0764; }
    /* Recycling uitleg box */
    .recycling-rules {
      background:#f8fafc; border-radius:8px; padding:.85rem 1rem;
      font-size:.82rem; color:#475569;
    }
    .recycling-rules ul { margin:.4rem 0 0 1.2rem; line-height:1.9; }
    /* Info/link box */
    .info-box {
      background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px;
      padding:.8rem 1rem; font-size:.82rem; color:#14532d;
    }
    .info-box a { color:#15803d; font-weight:600; text-decoration:none; }
    /* Opslaan sticky balk */
    .save-bar {
      position:sticky; bottom:0; background:#fff;
      border-top:1px solid #e5e7eb; padding:1rem 1.5rem;
      display:flex; align-items:center; gap:1rem;
      margin-top:2rem; z-index:10;
    }
    .btn-save {
      background:#287864; color:#fff; border:none; border-radius:8px;
      padding:.7rem 2rem; font-size:.9rem; font-weight:700;
      cursor:pointer; transition:background .2s;
    }
    .btn-save:hover { background:#1e5c4c; }
    /* Alerts */
    .alert { padding:.75rem 1rem; border-radius:8px; font-size:.875rem; }
    .alert-success { background:#f0fdf4; color:#14532d; border:1px solid #bbf7d0; }
    .alert-error   { background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; }
    /* SQL note */
    .sql-note {
      background:#fffbeb; border:1px solid #fde68a; border-radius:8px;
      padding:.9rem 1rem; font-size:.82rem; color:#78350f; margin-bottom:1.25rem;
    }
    .sql-note code { background:#fef3c7; padding:.1rem .35rem; border-radius:4px; font-size:.8rem; }
    /* Sectie-divider label */
    .section-label {
      font-size:.7rem; font-weight:700; letter-spacing:.08em;
      text-transform:uppercase; color:#9ca3af; margin:.25rem 0 0;
    }
    /* Uitzonderingen lijst */
    .uitzondering-lijst {
      display:flex; flex-direction:column; gap:.3rem;
      max-height:260px; overflow-y:auto; margin-top:.25rem;
    }
    .uitzondering-item {
      display:flex; flex-direction:column; gap:.1rem;
      padding:.4rem .7rem; border-radius:6px; font-size:.78rem;
      border:1px solid transparent;
    }
    .uitzondering-item strong { font-weight:700; font-size:.8rem; }
    .uitzondering-item span   { font-size:.72rem; opacity:.85; }
    .uitz-positief { background:#fef9c3; color:#713f12; border-color:#fde68a; }
    .uitz-negatief { background:#fee2e2; color:#7f1d1d; border-color:#fca5a5; }
    .uitzondering-leeg { font-size:.8rem; color:#9ca3af; font-style:italic; }
    .uitz-col-title {
      font-size:.72rem; font-weight:700; letter-spacing:.07em; text-transform:uppercase;
      color:#64748b; margin-bottom:.4rem; display:flex; align-items:center; gap:.4rem;
    }
    .uitz-count {
      background:#f1f5f9; color:#475569; border-radius:999px;
      padding:.1rem .45rem; font-size:.68rem; font-weight:700;
    }
  </style>
</head>
<body>
<div class="admin-wrap">
<nav class="admin-nav">
  <span class="logo">Reparatie<span>Platform</span> Admin</span>
  <a href="<?= BASE_URL ?>/admin/logout.php">Uitloggen</a>
</nav>
<div class="admin-layout">
  <div class="admin-sidebar">
    <a href="<?= BASE_URL ?>/admin/dashboard.php"><span class="icon">&#128202;</span> Dashboard</a>
    <a href="<?= BASE_URL ?>/admin/aanvragen.php"><span class="icon">&#128236;</span> Inzendingen</a>
    <a href="<?= BASE_URL ?>/admin/meldingen.php"><span class="icon">&#128276;</span> Meldingen</a>
    <a href="<?= BASE_URL ?>/admin/modellen.php"><span class="icon">&#128250;</span> TV Modellen</a>
    <a href="<?= BASE_URL ?>/admin/klachten.php"><span class="icon">&#9888;</span> Klachten</a>
    <a href="<?= BASE_URL ?>/admin/advies-instellingen.php" class="active"><span class="icon">&#9881;</span> Advies instellingen</a>
    <a href="<?= BASE_URL ?>/" target="_blank"><span class="icon">&#127760;</span> Website bekijken</a>
  </div>
  <div class="admin-content">

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
    <div class="alert alert-<?= $type ?>" style="margin-bottom:1.25rem;"><?= h($msg) ?></div>
    <?php endif; ?>

    <form method="POST" id="instellingen-form">
    <div class="rules-grid">

      <!-- ====================================================== -->
      <!-- GARANTIE                                               -->
      <!-- ====================================================== -->
      <div class="rule-section">
        <h3>&#9989; Garantie <span class="rule-badge badge-garantie">Garantie</span></h3>

        <div class="field">
          <label>Garantietermijn (jaren)</label>
          <input type="number" name="garantie_termijn_jaar" min="1" max="10"
                 value="<?= (int)($r['garantie_termijn_jaar'] ?? 2) ?>">
          <p class="hint">TV jonger dan X jaar komt in aanmerking voor garantieroute.</p>
        </div>

        <div class="field">
          <div class="cb-field">
            <input type="checkbox" id="gnl" name="garantie_alleen_nl" value="1"
                   <?= !empty($r['garantie_alleen_nl']) ? 'checked' : '' ?>>
            <label for="gnl">Garantie alleen bij aankoop in Nederland</label>
          </div>
          <p class="hint">Bij buitenland-aankoop geen garantieroute, maar reparatie of coulance.</p>
        </div>

        <div class="field">
          <label>Klachten uitgesloten van garantie</label>
          <textarea name="garantie_uitsluiten_klachten" rows="3"
          ><?= h(jsPretty($r['garantie_uitsluiten_klachten'] ?? ['gebarsten_scherm'])) ?></textarea>
          <p class="hint">JSON-array met klachtcodes. Bijv: <code>["gebarsten_scherm","stroomstoot"]</code></p>
        </div>

        <?php
          $gMerken = selectedMerken($r['garantie_merken'] ?? []);
          $gAlles  = empty($gMerken);
        ?>
        <div class="field">
          <label>Merken in aanmerking voor garantie</label>
          <?php if ($gAlles): ?>
            <span class="merken-hint-all">&#10003; Alle merken toegestaan (geen selectie = geen beperking)</span>
          <?php endif; ?>
          <input type="hidden" name="garantie_merken" id="garantie_merken_json"
                 value="<?= h(json_encode($gMerken)) ?>">
          <div class="merken-grid" id="garantie_merken_grid">
            <?php if (empty($alleMerken)): ?>
              <span style="font-size:.8rem;color:#9ca3af;font-style:italic;">Geen merken gevonden in database.</span>
            <?php else: foreach ($alleMerken as $m): ?>
            <label>
              <input type="checkbox" class="merk-cb" data-group="garantie_merken"
                     value="<?= h($m) ?>" <?= in_array($m, $gMerken) ? 'checked' : '' ?>>
              <?= h($m) ?>
            </label>
            <?php endforeach; endif; ?>
          </div>
          <p class="hint">Laat leeg = alle merken. Aanvinken = alleen die merken.</p>
        </div>
      </div>

      <!-- ====================================================== -->
      <!-- COULANCE                                               -->
      <!-- ====================================================== -->
      <div class="rule-section">
        <h3>&#129309; Coulance <span class="rule-badge badge-coulance">Coulance</span></h3>

        <div class="two-col">
          <div class="field">
            <label>Min. leeftijd TV (jaren)</label>
            <input type="number" name="coulance_min_jaar" min="1" max="20"
                   value="<?= (int)($r['coulance_min_jaar'] ?? 2) ?>">
            <p class="hint">Vanaf X jaar oud</p>
          </div>
          <div class="field">
            <label>Max. leeftijd TV (jaren)</label>
            <input type="number" name="coulance_max_jaar" min="1" max="20"
                   value="<?= (int)($r['coulance_max_jaar'] ?? 5) ?>">
            <p class="hint">Tot en met X jaar oud</p>
          </div>
        </div>

        <div class="field">
          <label>Klachten uitgesloten van coulance</label>
          <textarea name="coulance_uitsluiten_klachten" rows="3"
          ><?= h(jsPretty($r['coulance_uitsluiten_klachten'] ?? ['gebarsten_scherm'])) ?></textarea>
          <p class="hint">JSON-array met klachtcodes die nooit in aanmerking komen voor coulance.</p>
        </div>

        <?php
          $cMerken = selectedMerken($r['coulance_merken'] ?? []);
          $cAlles  = empty($cMerken);
        ?>
        <div class="field">
          <label>Merken in aanmerking voor coulance</label>
          <?php if ($cAlles): ?>
            <span class="merken-hint-all">&#10003; Alle merken toegestaan</span>
          <?php endif; ?>
          <input type="hidden" name="coulance_merken" id="coulance_merken_json"
                 value="<?= h(json_encode($cMerken)) ?>">
          <div class="merken-grid" id="coulance_merken_grid">
            <?php if (empty($alleMerken)): ?>
              <span style="font-size:.8rem;color:#9ca3af;font-style:italic;">Geen merken gevonden in database.</span>
            <?php else: foreach ($alleMerken as $m): ?>
            <label>
              <input type="checkbox" class="merk-cb" data-group="coulance_merken"
                     value="<?= h($m) ?>" <?= in_array($m, $cMerken) ? 'checked' : '' ?>>
              <?= h($m) ?>
            </label>
            <?php endforeach; endif; ?>
          </div>
          <p class="hint">Laat leeg = alle merken. Aanvinken = alleen die merken.</p>
        </div>

        <!-- Kansmatrix -->
        <div class="field">
          <label>Kansmatrix coulance per prijsklasse</label>
          <p class="hint" style="margin-bottom:.4rem;">
            Basiskans minus (aftrek &times; jaren boven de minimum leeftijd).
            Kans wordt altijd geclipt op 5%&ndash;95%.
          </p>
          <table class="matrix-table">
            <thead>
              <tr>
                <th>Prijsklasse</th>
                <th>Basiskans&nbsp;(%)</th>
                <th>Aftrek per jaar&nbsp;(%)</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($prijsklassenLabels as $pk => $label):
              $mr = $matrixIdx[$pk] ?? ['basis_kans' => 50, 'per_jaar_aftrek' => 6];
            ?>
            <tr>
              <td><?= $label ?></td>
              <td>
                <input type="number" name="matrix_basis[<?= h($pk) ?>]" min="0" max="100"
                       value="<?= (int)($mr['basis_kans'] ?? 50) ?>">
              </td>
              <td>
                <input type="number" name="matrix_aftrek[<?= h($pk) ?>]" min="0" max="50"
                       value="<?= (int)($mr['per_jaar_aftrek'] ?? 6) ?>">
              </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="two-col">
          <div class="field">
            <label>Kansaftrek bij buitenland-aankoop&nbsp;(%)</label>
            <input type="number" name="coulance_aftrek_buitenland" min="0" max="100"
                   value="<?= (int)($r['coulance_aftrek_buitenland'] ?? 30) ?>">
          </div>
          <div class="field">
            <label>Kansaftrek bij failliet verkoper&nbsp;(%)</label>
            <input type="number" name="coulance_aftrek_failliet" min="0" max="100"
                   value="<?= (int)($r['coulance_aftrek_failliet'] ?? 40) ?>">
          </div>
        </div>
      </div>

      <!-- ====================================================== -->
      <!-- REPARATIE                                              -->
      <!-- ====================================================== -->
      <div class="rule-section">
        <h3>&#128295; Reparatie <span class="rule-badge badge-reparatie">Reparatie</span></h3>

        <div class="two-col">
          <div class="field">
            <label>Min. leeftijd TV (jaren)</label>
            <input type="number" name="reparatie_min_jaar" min="0" max="20"
                   value="<?= (int)($r['reparatie_min_jaar'] ?? 2) ?>">
          </div>
          <div class="field">
            <label>Max. leeftijd TV (jaren)</label>
            <input type="number" name="reparatie_max_jaar" min="1" max="30"
                   value="<?= (int)($r['reparatie_max_jaar'] ?? 10) ?>">
          </div>
        </div>

        <div class="field">
          <div class="cb-field">
            <input type="checkbox" id="repdb" name="reparatie_vereist_repareerbaar" value="1"
                   <?= !empty($r['reparatie_vereist_repareerbaar']) ? 'checked' : '' ?>>
            <label for="repdb">
              Reparatieroute vereist <strong>repareerbaar&nbsp;=&nbsp;1</strong> in de TV-database
            </label>
          </div>
          <p class="hint">
            Als ingeschakeld: alleen modellen met het vinkje &ldquo;Repareerbaar&rdquo;
            in <a href="<?= BASE_URL ?>/admin/modellen.php" style="color:#287864;font-weight:600;">TV&nbsp;Modellen</a>
            komen in aanmerking. Overige gaan automatisch naar Recycling.
          </p>
        </div>

        <?php
          $rMerken = selectedMerken($r['reparatie_merken'] ?? []);
          $rAlles  = empty($rMerken);
        ?>
        <div class="field">
          <label>Merken toegestaan voor reparatieroute</label>
          <?php if ($rAlles): ?>
            <span class="merken-hint-all">&#10003; Alle repareerbare modellen toegestaan</span>
          <?php endif; ?>
          <input type="hidden" name="reparatie_merken" id="reparatie_merken_json"
                 value="<?= h(json_encode($rMerken)) ?>">
          <div class="merken-grid" id="reparatie_merken_grid">
            <?php if (empty($alleMerken)): ?>
              <span style="font-size:.8rem;color:#9ca3af;font-style:italic;">Geen merken gevonden in database.</span>
            <?php else: foreach ($alleMerken as $m): ?>
            <label>
              <input type="checkbox" class="merk-cb" data-group="reparatie_merken"
                     value="<?= h($m) ?>" <?= in_array($m, $rMerken) ? 'checked' : '' ?>>
              <?= h($m) ?>
            </label>
            <?php endforeach; endif; ?>
          </div>
          <p class="hint">
            Gecombineerd met de DB-vlag hierboven. Laat leeg = alle repareerbare modellen.
          </p>
        </div>

        <div class="info-box">
          &#128270; Stel per model in via
          <a href="<?= BASE_URL ?>/admin/modellen.php">TV Modellen &rarr;</a>
          door het vinkje &ldquo;Repareerbaar&rdquo; aan/uit te zetten.
          Momenteel zijn <strong><?= $statsRep ?> van de <?= $statsTotal ?> modellen</strong> repareerbaar.
        </div>
      </div>

      <!-- ====================================================== -->
      <!-- RECYCLING                                              -->
      <!-- ====================================================== -->
      <div class="rule-section">
        <h3>&#9851; Recycling <span class="rule-badge badge-recycling">Recycling</span></h3>

        <div class="field">
          <label>Minimale leeftijd voor recyclingroute (jaren)</label>
          <input type="number" name="recycling_min_jaar" min="1" max="30"
                 value="<?= (int)($r['recycling_min_jaar'] ?? 10) ?>">
          <p class="hint">
            TV ouder dan X jaar &rarr; recycling als geen andere route van toepassing is.
          </p>
        </div>

        <div class="recycling-rules">
          <strong>Automatisch naar Recycling bij:</strong>
          <ul>
            <li>TV ouder dan <strong><?= (int)($r['recycling_min_jaar'] ?? 10) ?> jaar</strong> (geen reparatie meer kosteneffici&euml;nt)</li>
            <li>Model heeft <code>repareerbaar&nbsp;=&nbsp;0</code> in de database <em>(en reparatie DB-check staat aan)</em></li>
            <li>Merk valt buiten de toegestane reparatielijst <em>(indien ingesteld)</em></li>
          </ul>
        </div>
      </div>

      <!-- ====================================================== -->
      <!-- TAXATIE (full-width)                                   -->
      <!-- ====================================================== -->
      <div class="rule-section full-width">
        <h3>&#128203; Taxatie <span class="rule-badge badge-taxatie">Taxatie</span></h3>
        <div class="two-col">
          <div class="field">
            <div class="cb-field">
              <input type="checkbox" id="taxsch" name="taxatie_bij_schade" value="1"
                     <?= !empty($r['taxatie_bij_schade']) ? 'checked' : '' ?>>
              <label for="taxsch">
                Taxatieroute automatisch bij <strong>externe schade</strong>
                (stroom, brand, inbraak, valschade)
              </label>
            </div>
            <p class="hint">
              Als ingeschakeld: klanten die &ldquo;Externe schade&rdquo; selecteren
              in het adviesformulier worden altijd doorgestuurd naar de taxatieroute.
            </p>
          </div>

          <?php
            $tMerken = selectedMerken($r['taxatie_merken'] ?? []);
            $tAlles  = empty($tMerken);
          ?>
          <div class="field">
            <label>Merken waarvoor taxatie mogelijk is</label>
            <?php if ($tAlles): ?>
              <span class="merken-hint-all">&#10003; Alle merken toegestaan</span>
            <?php endif; ?>
            <input type="hidden" name="taxatie_merken" id="taxatie_merken_json"
                   value="<?= h(json_encode($tMerken)) ?>">
            <div class="merken-grid" id="taxatie_merken_grid">
              <?php if (empty($alleMerken)): ?>
                <span style="font-size:.8rem;color:#9ca3af;font-style:italic;">Geen merken gevonden in database.</span>
              <?php else: foreach ($alleMerken as $m): ?>
              <label>
                <input type="checkbox" class="merk-cb" data-group="taxatie_merken"
                       value="<?= h($m) ?>" <?= in_array($m, $tMerken) ? 'checked' : '' ?>>
                <?= h($m) ?>
              </label>
              <?php endforeach; endif; ?>
              </div>
            <p class="hint">
              Laat leeg = alle merken. Momenteel <strong><?= $statsTax ?> modellen</strong>
              hebben taxatie ingesteld in de database.
            </p>
          </div>
        </div>
      </div>

      <!-- ====================================================== -->
      <!-- UITZONDERINGEN (synced met TV Modellen)              -->
      <!-- ====================================================== -->
      <div class="rule-section full-width">
        <h3>&#9888; Model-uitzonderingen
          <span class="rule-badge" style="background:#fef9c3;color:#78350f;">Sync</span>
        </h3>
        <p style="font-size:.82rem;color:#6b7280;margin:-.25rem 0 .75rem;">
          Modellen die afwijken van de merk-standaard voor reparatie of taxatie.
          Aanpassen kan via <a href="<?= BASE_URL ?>/admin/modellen.php" style="color:#287864;font-weight:600;">TV Modellen &rarr;</a>
          &mdash; wijzigingen zijn hier direct zichtbaar.
        </p>
        <div class="two-col">

          <!-- Reparatie uitzonderingen -->
          <div>
            <p class="uitz-col-title">
              &#128295; Reparatie-uitzonderingen
              <span class="uitz-count"><?= count($uitzRep) ?></span>
            </p>
            <?php if (empty($uitzRep)): ?>
              <p class="uitzondering-leeg">Geen uitzonderingen &mdash; alle modellen volgen de merk-standaard.</p>
            <?php else: ?>
              <div class="uitzondering-lijst">
                <?php foreach ($uitzRep as $u): ?>
                <div class="uitzondering-item uitz-<?= $u['_type'] ?>">
                  <strong><?= h($u['merk']) ?> &nbsp;<?= h($u['modelnummer']) ?></strong>
                  <span>
                    <?php if ($u['_type'] === 'positief'): ?>
                      Merk: standaard <em>niet</em> repareerbaar &rarr; dit model: <strong>wél</strong> repareerbaar
                    <?php else: ?>
                      Merk: standaard repareerbaar &rarr; dit model: <strong>niet</strong> repareerbaar
                    <?php endif; ?>
                  </span>
                </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

          <!-- Taxatie uitzonderingen -->
          <div>
            <p class="uitz-col-title">
              &#128203; Taxatie-uitzonderingen
              <span class="uitz-count"><?= count($uitzTax) ?></span>
            </p>
            <?php if (empty($uitzTax)): ?>
              <p class="uitzondering-leeg">Geen uitzonderingen &mdash; alle modellen volgen de merk-standaard.</p>
            <?php else: ?>
              <div class="uitzondering-lijst">
                <?php foreach ($uitzTax as $u): ?>
                <div class="uitzondering-item uitz-<?= $u['_type'] ?>">
                  <strong><?= h($u['merk']) ?> &nbsp;<?= h($u['modelnummer']) ?></strong>
                  <span>
                    <?php if ($u['_type'] === 'positief'): ?>
                      Merk: standaard <em>geen</em> taxatie &rarr; dit model: <strong>wél</strong> taxatie
                    <?php else: ?>
                      Merk: standaard taxatie &rarr; dit model: <strong>geen</strong> taxatie
                    <?php endif; ?>
                  </span>
                </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

        </div>
      </div>

    </div><!-- /.rules-grid -->

    <div class="save-bar">
      <button type="submit" class="btn-save">&#10003; Instellingen opslaan</button>
      <span style="font-size:.82rem;color:#9ca3af;">
        Wijzigingen zijn direct actief in het adviesformulier.
      </span>
    </div>
    </form>

  </div>
</div>
</div>

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
  // Toon/verberg 'alle merken' hint
  const wrap = document.getElementById(group + '_grid');
  if (!wrap) return;
  const prev = wrap.previousElementSibling;
  if (prev && prev.classList.contains('merken-hint-all')) {
    prev.style.display = checked.length === 0 ? 'inline-block' : 'none';
  }
}
// Init: sync alle hidden velden op paginalaad
['garantie_merken','coulance_merken','reparatie_merken','taxatie_merken'].forEach(syncMerkenJson);
</script>
</body>
</html>
