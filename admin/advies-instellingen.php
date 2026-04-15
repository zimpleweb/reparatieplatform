<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/advies_regels.php';
requireAdmin();

$msg  = '';
$type = 'success';

// ── Opslaan ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo  = db();
        $stmt = $pdo->prepare(
            'UPDATE advies_regels SET regel_waarde = ? WHERE regel_key = ?'
        );

        $keys = [
            // Garantie
            'garantie_termijn_jaar', 'garantie_alleen_nl',
            'garantie_uitsluiten_klachten', 'garantie_merken',
            // Coulance
            'coulance_min_jaar', 'coulance_max_jaar',
            'coulance_uitsluiten_klachten', 'coulance_merken',
            'coulance_kans_matrix',
            'coulance_aftrek_buitenland', 'coulance_aftrek_failliet',
            // Reparatie
            'reparatie_min_jaar', 'reparatie_max_jaar',
            'reparatie_vereist_repareerbaar', 'reparatie_merken',
            // Recycling
            'recycling_min_jaar',
            // Taxatie
            'taxatie_bij_schade', 'taxatie_merken',
        ];

        foreach ($keys as $key) {
            if (!isset($_POST[$key])) continue;
            $raw = trim($_POST[$key]);

            $ts = $pdo->prepare('SELECT type FROM advies_regels WHERE regel_key = ?');
            $ts->execute([$key]);
            $t  = $ts->fetchColumn();

            if ($t === 'bool')  $raw = $raw ? '1' : '0';
            if ($t === 'int')   $raw = (string)(int)$raw;
            if ($t === 'float') $raw = (string)(float)$raw;
            if ($t === 'json') {
                $decoded = json_decode($raw, true);
                if ($decoded === null) {
                    $msg  = 'Ongeldige JSON bij veld: ' . $key;
                    $type = 'error';
                    break;
                }
                $raw = json_encode($decoded, JSON_UNESCAPED_UNICODE);
            }
            $stmt->execute([$raw, $key]);
        }

        // Bool-checkboxes: leeg = unchecked = 0
        foreach (['garantie_alleen_nl', 'reparatie_vereist_repareerbaar', 'taxatie_bij_schade'] as $bk) {
            if (!isset($_POST[$bk])) {
                $pdo->prepare('UPDATE advies_regels SET regel_waarde = ? WHERE regel_key = ?')
                    ->execute(['0', $bk]);
            }
        }

        if (!$msg) $msg = 'Instellingen succesvol opgeslagen.';
    } catch (Exception $e) {
        $msg  = 'Fout bij opslaan: ' . $e->getMessage();
        $type = 'error';
    }
}

$r = getAdviesRegels();

// Haal alle merken uit de TV-modellen DB (uniek, gesorteerd)
$alleMerken = [];
try {
    $alleMerken = db()->query('SELECT DISTINCT merk FROM tv_modellen WHERE merk IS NOT NULL AND merk != "" ORDER BY merk')
                      ->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) { $alleMerken = []; }

// Helper: JSON pretty voor textarea
function jsPretty($v): string {
    return json_encode($v, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
// Helper: geselecteerde merken als array
function selectedMerken($v): array {
    if (is_array($v)) return $v;
    $d = json_decode($v ?? '[]', true);
    return is_array($d) ? $d : [];
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8"><title>Advies Instellingen &ndash; Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Epilogue:wght@800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/base.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/components.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
  <meta name="robots" content="noindex,nofollow">
  <style>
    .rules-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
    @media(max-width:900px){ .rules-grid { grid-template-columns: 1fr; } }
    .rule-section { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 1.5rem; }
    .rule-section h3 { font-size: 1rem; font-weight: 700; margin-bottom: 1.1rem;
      padding-bottom: .6rem; border-bottom: 1px solid #f1f5f9;
      display: flex; align-items: center; gap: .5rem; }
    .rule-badge { font-size: .65rem; font-weight: 700; padding: .2rem .55rem;
      border-radius: 999px; text-transform: uppercase; letter-spacing: .06em; }
    .badge-garantie  { background: #dcfce7; color: #14532d; }
    .badge-coulance  { background: #fef9c3; color: #78350f; }
    .badge-reparatie { background: #dbeafe; color: #1e3a8a; }
    .badge-recycling { background: #f1f5f9; color: #334155; }
    .badge-taxatie   { background: #ede9fe; color: #3b0764; }
    .field { margin-bottom: 1rem; }
    .field label { display: block; font-size: .82rem; font-weight: 600;
      color: #374151; margin-bottom: .35rem; }
    .field .hint { font-size: .75rem; color: #9ca3af; margin-top: .3rem; }
    .field input[type=number], .field input[type=text], .field textarea {
      width: 100%; padding: .55rem .75rem; border: 1px solid #d1d5db;
      border-radius: 8px; font-size: .875rem; font-family: 'Inter', sans-serif;
      transition: border-color .15s; }
    .field textarea { font-family: 'Courier New', monospace; font-size: .8rem;
      min-height: 130px; resize: vertical; }
    .field input:focus, .field textarea:focus {
      outline: none; border-color: #287864; box-shadow: 0 0 0 3px rgba(40,120,100,.12); }
    .cb-field { display: flex; align-items: flex-start; gap: .6rem; }
    .cb-field input[type=checkbox] { margin-top: .2rem; accent-color: #287864;
      width: 16px; height: 16px; flex-shrink: 0; cursor: pointer; }
    .cb-field label { font-size: .875rem; color: #374151; cursor: pointer; }
    /* Merk-checkboxen grid */
    .merken-grid { display: flex; flex-wrap: wrap; gap: .4rem .75rem; margin-top: .4rem; }
    .merken-grid label { display: flex; align-items: center; gap: .35rem;
      font-size: .82rem; color: #374151; cursor: pointer;
      background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 6px;
      padding: .3rem .6rem; transition: border-color .15s, background .15s; }
    .merken-grid label:hover { background: #f0fdf4; border-color: #287864; }
    .merken-grid input[type=checkbox] { accent-color: #287864; width: 14px; height: 14px; cursor: pointer; }
    .merken-grid label:has(input:checked) { background: #dcfce7; border-color: #287864; color: #14532d; font-weight: 600; }
    .merken-empty { font-size: .8rem; color: #9ca3af; font-style: italic; }
    .merken-hint-all { font-size: .75rem; color: #059669; background: #ecfdf5;
      border: 1px solid #a7f3d0; border-radius: 6px; padding: .3rem .6rem;
      display: inline-block; margin-top: .4rem; }
    /* Matrix tabel */
    .matrix-table { width: 100%; border-collapse: collapse; font-size: .82rem; margin-top: .5rem; }
    .matrix-table th { background: #f8fafc; font-weight: 600; color: #475569;
      padding: .45rem .6rem; text-align: left; border: 1px solid #e5e7eb; }
    .matrix-table td { padding: .4rem .6rem; border: 1px solid #e5e7eb; }
    .matrix-table input { width: 100%; border: none; background: transparent;
      font-size: .82rem; font-family: inherit; padding: 0; }
    .matrix-table input:focus { outline: 2px solid #287864; border-radius: 3px; }
    /* Routeblok legenda */
    .route-legenda { display: flex; flex-wrap: wrap; gap: .4rem; margin-bottom: 1.25rem; }
    .route-chip { display: flex; align-items: center; gap: .3rem; padding: .3rem .7rem;
      border-radius: 999px; font-size: .75rem; font-weight: 700; }
    .chip-garantie  { background: #dcfce7; color: #14532d; }
    .chip-coulance  { background: #fef9c3; color: #78350f; }
    .chip-reparatie { background: #dbeafe; color: #1e3a8a; }
    .chip-recycling { background: #f1f5f9; color: #334155; }
    .chip-taxatie   { background: #ede9fe; color: #3b0764; }
    /* Opslaan balk */
    .save-bar { position: sticky; bottom: 0; background: #fff;
      border-top: 1px solid #e5e7eb; padding: 1rem 1.5rem;
      display: flex; align-items: center; gap: 1rem; margin-top: 2rem; z-index: 10; }
    .btn-save { background: #287864; color: #fff; border: none; border-radius: 8px;
      padding: .7rem 2rem; font-size: .9rem; font-weight: 700; cursor: pointer;
      transition: background .2s; }
    .btn-save:hover { background: #1e5c4c; }
    .alert { padding: .75rem 1rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: .875rem; }
    .alert-success { background: #f0fdf4; color: #14532d; border: 1px solid #bbf7d0; }
    .alert-error   { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
    .sql-note { background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px;
      padding: .9rem 1rem; font-size: .82rem; color: #78350f; margin-bottom: 1.5rem; }
    .sql-note code { background: #fef3c7; padding: .1rem .35rem; border-radius: 4px;
      font-family: monospace; font-size: .8rem; }
    .info-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px;
      padding: .85rem 1rem; font-size: .82rem; color: #14532d; }
    .info-box a { color: #15803d; font-weight: 600; }
    .section-divider { font-size: .7rem; font-weight: 700; letter-spacing: .08em;
      text-transform: uppercase; color: #9ca3af; margin: 1rem 0 .5rem; }
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
    <a href="<?= BASE_URL ?>/admin/aanvragen.php"><span class="icon">&#128236;</span> Aanvragen</a>
    <a href="<?= BASE_URL ?>/admin/modellen.php"><span class="icon">&#128250;</span> TV Modellen</a>
    <a href="<?= BASE_URL ?>/admin/klachten.php"><span class="icon">&#9888;</span> Klachten</a>
    <a href="<?= BASE_URL ?>/admin/advies-instellingen.php" class="active"><span class="icon">&#9881;</span> Advies instellingen</a>
    <a href="<?= BASE_URL ?>/" target="_blank"><span class="icon">&#127760;</span> Website bekijken</a>
  </div>
  <div class="admin-content">
    <h1>&#9881; Advies routing instellingen</h1>
    <p style="color:#6b7280;margin-bottom:.75rem;font-size:.9rem;">Hier bepaal je de regels op basis waarvan het adviesformulier klanten doorstuurt naar Garantie, Coulance, Reparatie, Taxatie of Recycling. Wijzigingen zijn <strong>direct actief</strong>.</p>

    <div class="route-legenda">
      <span class="route-chip chip-garantie">&#9989; Garantie</span>
      <span class="route-chip chip-coulance">&#129309; Coulance</span>
      <span class="route-chip chip-reparatie">&#128295; Reparatie</span>
      <span class="route-chip chip-taxatie">&#128203; Taxatie</span>
      <span class="route-chip chip-recycling">&#9851; Recycling</span>
    </div>

    <?php if (!$r): ?>
    <div class="sql-note">
      &#9888; De tabel <code>advies_regels</code> bestaat nog niet of is leeg. Voer eerst het SQL-script uit:
      <code>sql/advies_regels.sql</code>
    </div>
    <?php endif; ?>

    <?php if ($msg): ?>
    <div class="alert alert-<?= $type ?>"><?= h($msg) ?></div>
    <?php endif; ?>

    <form method="POST" id="instellingen-form">

      <div class="rules-grid">

        <!-- ── GARANTIE ── -->
        <div class="rule-section">
          <h3>&#9989; Garantie <span class="rule-badge badge-garantie">Garantie</span></h3>

          <div class="field">
            <label>Garantietermijn (jaren)</label>
            <input type="number" name="garantie_termijn_jaar" min="1" max="10"
                   value="<?= h((string)($r['garantie_termijn_jaar'] ?? 2)) ?>">
            <p class="hint">TV moet jonger zijn dan X jaar voor garantieroute.</p>
          </div>

          <div class="field">
            <div class="cb-field">
              <input type="checkbox" id="gnl" name="garantie_alleen_nl" value="1"
                     <?= !empty($r['garantie_alleen_nl']) ? 'checked' : '' ?>>
              <label for="gnl">Garantie alleen bij aankoop in Nederland</label>
            </div>
            <p class="hint">Bij buitenland-aankoop geen garantieroute, maar direct reparatie of coulance.</p>
          </div>

          <div class="field">
            <label>Klachten uitgesloten van garantie <small>(JSON array)</small></label>
            <textarea name="garantie_uitsluiten_klachten"
            ><?= h(jsPretty($r['garantie_uitsluiten_klachten'] ?? ['gebarsten_scherm'])) ?></textarea>
            <p class="hint">Klachtcodes (bijv. <code>gebarsten_scherm</code>) die nooit in aanmerking komen voor garantie.</p>
          </div>

          <div class="field">
            <?php
              $gMerken = selectedMerken($r['garantie_merken'] ?? []);
              $gAlles  = empty($gMerken);
            ?>
            <label>Merken in aanmerking voor garantieroute</label>
            <?php if ($gAlles): ?>
              <span class="merken-hint-all">&#10003; Alle merken zijn toegestaan (lege selectie = geen beperking)</span>
            <?php endif; ?>
            <input type="hidden" name="garantie_merken" id="garantie_merken_json" value="<?= h(json_encode($gMerken)) ?>">
            <div class="merken-grid" id="garantie_merken_grid">
              <?php if (empty($alleMerken)): ?>
                <span class="merken-empty">Geen merken gevonden in TV-modellen database.</span>
              <?php else: foreach ($alleMerken as $m): ?>
              <label>
                <input type="checkbox" class="merk-cb" data-group="garantie_merken" value="<?= h($m) ?>"
                       <?= in_array($m, $gMerken) ? 'checked' : '' ?>>
                <?= h($m) ?>
              </label>
              <?php endforeach; endif; ?>
            </div>
            <p class="hint">Laat alles leeg = alle merken toegestaan. Vink aan om te beperken.</p>
          </div>
        </div>

        <!-- ── COULANCE ── -->
        <div class="rule-section">
          <h3>&#129309; Coulance <span class="rule-badge badge-coulance">Coulance</span></h3>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
            <div class="field">
              <label>Min. leeftijd (jaren)</label>
              <input type="number" name="coulance_min_jaar" min="1" max="20"
                     value="<?= h((string)($r['coulance_min_jaar'] ?? 2)) ?>">
            </div>
            <div class="field">
              <label>Max. leeftijd (jaren)</label>
              <input type="number" name="coulance_max_jaar" min="1" max="20"
                     value="<?= h((string)($r['coulance_max_jaar'] ?? 5)) ?>">
            </div>
          </div>

          <div class="field">
            <label>Klachten uitgesloten van coulance <small>(JSON array)</small></label>
            <textarea name="coulance_uitsluiten_klachten"
            ><?= h(jsPretty($r['coulance_uitsluiten_klachten'] ?? ['gebarsten_scherm'])) ?></textarea>
          </div>

          <div class="field">
            <?php
              $cMerken = selectedMerken($r['coulance_merken'] ?? []);
              $cAlles  = empty($cMerken);
            ?>
            <label>Merken in aanmerking voor coulanceregeling</label>
            <?php if ($cAlles): ?>
              <span class="merken-hint-all">&#10003; Alle merken zijn toegestaan</span>
            <?php endif; ?>
            <input type="hidden" name="coulance_merken" id="coulance_merken_json" value="<?= h(json_encode($cMerken)) ?>">
            <div class="merken-grid" id="coulance_merken_grid">
              <?php if (empty($alleMerken)): ?>
                <span class="merken-empty">Geen merken gevonden in TV-modellen database.</span>
              <?php else: foreach ($alleMerken as $m): ?>
              <label>
                <input type="checkbox" class="merk-cb" data-group="coulance_merken" value="<?= h($m) ?>"
                       <?= in_array($m, $cMerken) ? 'checked' : '' ?>>
                <?= h($m) ?>
              </label>
              <?php endforeach; endif; ?>
            </div>
            <p class="hint">Laat leeg = alle merken komen in aanmerking voor coulance.</p>
          </div>

          <div class="field">
            <label>Kansmatrix coulance per prijsklasse</label>
            <?php
            $matrix = $r['coulance_kans_matrix'] ?? [];
            $prijsklassen = [
              ''         => 'Onbekend',
              '<500'     => '< &euro;500',
              '500-1000' => '&euro;500 &ndash; &euro;1.000',
              '1000-2000'=> '&euro;1.000 &ndash; &euro;2.000',
              '>2000'    => '> &euro;2.000',
            ];
            $matrixIdx = [];
            foreach ($matrix as $row) $matrixIdx[$row['prijsklasse']] = $row;
            ?>
            <table class="matrix-table">
              <thead><tr><th>Prijsklasse</th><th>Basiskans (%)</th><th>Aftrek/jaar (%)</th></tr></thead>
              <tbody>
              <?php foreach ($prijsklassen as $pk => $label): ?>
              <?php $mr = $matrixIdx[$pk] ?? ['basis_kans' => 50, 'per_jaar_aftrek' => 6]; ?>
              <tr>
                <td><?= $label ?></td>
                <td><input type="number" name="matrix_basis[<?= h($pk) ?>]" min="0" max="100"
                           value="<?= (int)($mr['basis_kans'] ?? 50) ?>"></td>
                <td><input type="number" name="matrix_aftrek[<?= h($pk) ?>]" min="0" max="50"
                           value="<?= (int)($mr['per_jaar_aftrek'] ?? 6) ?>"></td>
              </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
            <p class="hint" style="margin-top:.4rem;">Basiskans minus (aftrek &times; jaren boven min. leeftijd). Kans wordt geclipt op 5%&ndash;95%.</p>
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
            <div class="field">
              <label>Aftrek buitenland (%)</label>
              <input type="number" name="coulance_aftrek_buitenland" min="0" max="100"
                     value="<?= h((string)($r['coulance_aftrek_buitenland'] ?? 30)) ?>">
            </div>
            <div class="field">
              <label>Aftrek failliet verkoper (%)</label>
              <input type="number" name="coulance_aftrek_failliet" min="0" max="100"
                     value="<?= h((string)($r['coulance_aftrek_failliet'] ?? 40)) ?>">
            </div>
          </div>
        </div>

        <!-- ── REPARATIE ── -->
        <div class="rule-section">
          <h3>&#128295; Reparatie <span class="rule-badge badge-reparatie">Reparatie</span></h3>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
            <div class="field">
              <label>Min. leeftijd TV (jaren)</label>
              <input type="number" name="reparatie_min_jaar" min="0" max="20"
                     value="<?= h((string)($r['reparatie_min_jaar'] ?? 2)) ?>">
            </div>
            <div class="field">
              <label>Max. leeftijd TV (jaren)</label>
              <input type="number" name="reparatie_max_jaar" min="1" max="30"
                     value="<?= h((string)($r['reparatie_max_jaar'] ?? 10)) ?>">
            </div>
          </div>

          <div class="field">
            <div class="cb-field">
              <input type="checkbox" id="repdb" name="reparatie_vereist_repareerbaar" value="1"
                     <?= !empty($r['reparatie_vereist_repareerbaar']) ? 'checked' : '' ?>>
              <label for="repdb">Reparatie vereist <strong>repareerbaar = 1</strong> in TV-database</label>
            </div>
            <p class="hint">Als aan: alleen modellen met de vlag &ldquo;Repareerbaar&rdquo; komen in aanmerking. Overige gaan naar Recycling.</p>
          </div>

          <div class="field">
            <?php
              $rMerken = selectedMerken($r['reparatie_merken'] ?? []);
              $rAlles  = empty($rMerken);
            ?>
            <label>Merken toegestaan voor reparatieroute</label>
            <?php if ($rAlles): ?>
              <span class="merken-hint-all">&#10003; Alle repareerbare modellen toegestaan</span>
            <?php endif; ?>
            <input type="hidden" name="reparatie_merken" id="reparatie_merken_json" value="<?= h(json_encode($rMerken)) ?>">
            <div class="merken-grid" id="reparatie_merken_grid">
              <?php if (empty($alleMerken)): ?>
                <span class="merken-empty">Geen merken gevonden in TV-modellen database.</span>
              <?php else: foreach ($alleMerken as $m): ?>
              <label>
                <input type="checkbox" class="merk-cb" data-group="reparatie_merken" value="<?= h($m) ?>"
                       <?= in_array($m, $rMerken) ? 'checked' : '' ?>>
                <?= h($m) ?>
              </label>
              <?php endforeach; endif; ?>
            </div>
            <p class="hint">Laat leeg = alle repareerbare modellen. Gecombineerd met de DB-vlag hierboven.</p>
          </div>

          <div class="info-box">
            &#128270; Welke modellen repareerbaar zijn stel je in via
            <a href="<?= BASE_URL ?>/admin/modellen.php">TV Modellen &rarr;</a>
            door het vinkje &ldquo;Repareerbaar&rdquo; per model aan/uit te zetten.
          </div>
        </div>

        <!-- ── RECYCLING ── -->
        <div class="rule-section">
          <h3>&#9851; Recycling <span class="rule-badge badge-recycling">Recycling</span></h3>

          <div class="field">
            <label>Minimale leeftijd voor recyclingroute (jaren)</label>
            <input type="number" name="recycling_min_jaar" min="1" max="30"
                   value="<?= h((string)($r['recycling_min_jaar'] ?? 10)) ?>">
            <p class="hint">TV ouder dan X jaar &rarr; recycling als reparatie niet meer kosteneffi&euml;nt is. Niet-repareerbare modellen gaan altijd naar recycling.</p>
          </div>

          <div style="background:#f1f5f9;border-radius:8px;padding:.85rem 1rem;font-size:.82rem;color:#475569;">
            <strong>Automatisch naar recycling bij:</strong>
            <ul style="margin:.5rem 0 0 1rem;line-height:1.8;">
              <li>TV ouder dan <strong><?= (int)($r['recycling_min_jaar'] ?? 10) ?> jaar</strong></li>
              <li>Model heeft <code>repareerbaar = 0</code> in de database</li>
              <li>Merk valt buiten de toegestane reparatielijst (indien ingesteld)</li>
            </ul>
          </div>
        </div>

        <!-- ── TAXATIE ── -->
        <div class="rule-section" style="grid-column: 1 / -1;">
          <h3>&#128203; Taxatie <span class="rule-badge badge-taxatie">Taxatie</span></h3>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
            <div class="field">
              <div class="cb-field">
                <input type="checkbox" id="taxsch" name="taxatie_bij_schade" value="1"
                       <?= !empty($r['taxatie_bij_schade']) ? 'checked' : '' ?>>
                <label for="taxsch">Taxatieroute automatisch bij externe schade (stroom, brand, inbraak)</label>
              </div>
              <p class="hint" style="margin-top:.4rem;">Als ingeschakeld: klanten die &ldquo;Externe schade&rdquo; selecteren gaan altijd naar de taxatieroute.</p>
            </div>
            <div class="field">
              <?php
                $tMerken = selectedMerken($r['taxatie_merken'] ?? []);
                $tAlles  = empty($tMerken);
              ?>
              <label>Merken waarvoor taxatie mogelijk is</label>
              <?php if ($tAlles): ?>
                <span class="merken-hint-all">&#10003; Alle merken zijn toegestaan</span>
              <?php endif; ?>
              <input type="hidden" name="taxatie_merken" id="taxatie_merken_json" value="<?= h(json_encode($tMerken)) ?>">
              <div class="merken-grid" id="taxatie_merken_grid">
                <?php if (empty($alleMerken)): ?>
                  <span class="merken-empty">Geen merken gevonden in TV-modellen database.</span>
                <?php else: foreach ($alleMerken as $m): ?>
                <label>
                  <input type="checkbox" class="merk-cb" data-group="taxatie_merken" value="<?= h($m) ?>"
                         <?= in_array($m, $tMerken) ? 'checked' : '' ?>>
                  <?= h($m) ?>
                </label>
                <?php endforeach; endif; ?>
                </div>
              <p class="hint">Laat leeg = alle merken. Alleen aanvinken om te beperken.</p>
            </div>
          </div>
        </div>

      </div><!-- /.rules-grid -->

      <div class="save-bar">
        <button type="submit" class="btn-save">&#10003; Instellingen opslaan</button>
        <span style="font-size:.82rem;color:#9ca3af;">Wijzigingen zijn direct actief in het adviesformulier.</span>
      </div>

    </form>
  </div>
</div>
</div>

<script>
// ── Merk-checkboxen → hidden JSON veld ─────────────────────────
document.querySelectorAll('.merk-cb').forEach(cb => {
  cb.addEventListener('change', () => syncMerkenJson(cb.dataset.group));
});

function syncMerkenJson(group) {
  const checked = [...document.querySelectorAll(`.merk-cb[data-group="${group}"]:checked`)]
                    .map(c => c.value);
  const hid = document.getElementById(group + '_json');
  if (hid) hid.value = JSON.stringify(checked);
}

// ── Coulance kansmatrix → hidden JSON veld ─────────────────────
document.getElementById('instellingen-form').addEventListener('submit', function() {
  const basis  = Object.fromEntries(
    [...document.querySelectorAll('[name^="matrix_basis["]')].map(el => {
      const pk = el.name.replace('matrix_basis[','').replace(']','');
      return [pk, parseInt(el.value) || 0];
    })
  );
  const aftrek = Object.fromEntries(
    [...document.querySelectorAll('[name^="matrix_aftrek["]')].map(el => {
      const pk = el.name.replace('matrix_aftrek[','').replace(']','');
      return [pk, parseInt(el.value) || 0];
    })
  );
  const matrix = Object.keys(basis).map(pk => ({
    prijsklasse: pk,
    basis_kans: basis[pk],
    per_jaar_aftrek: aftrek[pk] ?? 0
  }));
  let hid = document.getElementById('_matrix_json');
  if (!hid) {
    hid = document.createElement('input');
    hid.type = 'hidden'; hid.id = '_matrix_json'; hid.name = 'coulance_kans_matrix';
    this.appendChild(hid);
  }
  hid.value = JSON.stringify(matrix);
});
</script>
</body>
</html>
