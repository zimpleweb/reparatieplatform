<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/advies_regels.php';
requireAdmin();

$msg = '';

// Laad adviesregels één keer — wordt gebruikt voor merk-standaarden
$advies = getAdviesRegels();
$repareerbareMerken = $advies['reparatie_merken'] ?? [];
$taxatieMerken      = $advies['taxatie_merken']   ?? [];

function defaultVoorMerk(array $merkLijst, string $merk): bool {
    if (empty($merkLijst)) return true;
    return in_array(
        mb_strtolower(trim($merk)),
        array_map(fn($m) => mb_strtolower(trim($m)), $merkLijst),
        true
    );
}

/**
 * Is dit model een "uitzondering"?
 * - Merk is standaard NIET repareerbaar, maar dit model WEL: positieve uitzondering
 * - Merk is standaard WEL repareerbaar, maar dit model NIET: negatieve uitzondering
 * Zelfde logica voor taxatie.
 */
function isUitzondering(array $merkLijst, string $merk, int $modelWaarde): ?string {
    $merkDefault = empty($merkLijst)
        ? true
        : in_array(mb_strtolower(trim($merk)), array_map(fn($m) => mb_strtolower(trim($m)), $merkLijst), true);
    if (!$merkDefault && $modelWaarde === 1) return 'positief';
    if ($merkDefault  && $modelWaarde === 0) return 'negatief';
    return null;
}

// ── Toevoegen ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='add') {
    $merk   = trim($_POST['merk'] ?? '');
    $modelNr = trim($_POST['modelnummer'] ?? '');
    $slug   = slugify($merk.' '.$modelNr);
    $check  = db()->prepare('SELECT COUNT(*) FROM tv_modellen WHERE slug=?');
    $check->execute([$slug]);
    if ($check->fetchColumn() > 0) $slug .= '-'.time();

    $repVal = isset($_POST['repareerbaar']) ? 1 : (defaultVoorMerk($repareerbareMerken, $merk) ? 1 : 0);
    $taxVal = isset($_POST['taxatie'])      ? 1 : (defaultVoorMerk($taxatieMerken,      $merk) ? 1 : 0);

    db()->prepare(
        'INSERT INTO tv_modellen (merk,serie,modelnummer,slug,beschrijving,repareerbaar,taxatie) VALUES (?,?,?,?,?,?,?)'
    )->execute([
        $merk, trim($_POST['serie'] ?? ''), $modelNr, $slug,
        trim($_POST['beschrijving'] ?? ''), $repVal, $taxVal,
    ]);
    $msg = 'Model &ldquo;'.h($modelNr).'&rdquo; succesvol toegevoegd.';
}

// ── Bewerken ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='edit') {
    $id = (int)$_POST['id'];
    db()->prepare(
        'UPDATE tv_modellen SET merk=?,serie=?,modelnummer=?,beschrijving=?,repareerbaar=?,taxatie=? WHERE id=?'
    )->execute([
        trim($_POST['merk'] ?? ''),
        trim($_POST['serie'] ?? ''),
        trim($_POST['modelnummer'] ?? ''),
        trim($_POST['beschrijving'] ?? ''),
        isset($_POST['repareerbaar']) ? 1 : 0,
        isset($_POST['taxatie'])      ? 1 : 0,
        $id,
    ]);
    $msg = 'Model &ldquo;'.h($_POST['modelnummer'] ?? '').'&rdquo; bijgewerkt.';
}

// ── Verwijderen ──────────────────────────────────────────
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    db()->prepare('UPDATE tv_modellen SET actief=0 WHERE id=?')->execute([$_GET['delete']]);
    $msg = 'Model verwijderd.';
}

// ── Snel toggle repareerbaar/taxatie ──────────────────────
if (isset($_GET['toggle']) && is_numeric($_GET['toggle']) && isset($_GET['veld'])) {
    $veld = in_array($_GET['veld'], ['repareerbaar','taxatie']) ? $_GET['veld'] : null;
    if ($veld) {
        $row = db()->prepare('SELECT '.$veld.' FROM tv_modellen WHERE id=? AND actief=1');
        $row->execute([(int)$_GET['toggle']]);
        $huidig = (int)$row->fetchColumn();
        db()->prepare('UPDATE tv_modellen SET '.$veld.'=? WHERE id=?')
            ->execute([$huidig ? 0 : 1, (int)$_GET['toggle']]);
    }
    header('Location: '.$_SERVER['PHP_SELF'].'?updated=1');
    exit;
}

// ── Bewerk-model ophalen ────────────────────────────────
$editModel = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $s = db()->prepare('SELECT * FROM tv_modellen WHERE id=? AND actief=1');
    $s->execute([$_GET['edit']]);
    $editModel = $s->fetch();
}

// ── Filter & zoek ───────────────────────────────────────
$filterMerk  = trim($_GET['filter_merk']  ?? '');
$filterZoek  = trim($_GET['zoek']         ?? '');
$filterFlag  = trim($_GET['filter_flag']  ?? '');

$where  = ['actief=1'];
$params = [];
if ($filterMerk !== '') { $where[] = 'merk=?';  $params[] = $filterMerk; }
if ($filterZoek !== '') {
    $where[] = '(modelnummer LIKE ? OR serie LIKE ? OR merk LIKE ?)';
    $p = '%'.$filterZoek.'%';
    $params[] = $p; $params[] = $p; $params[] = $p;
}
$sql      = 'SELECT * FROM tv_modellen WHERE '.implode(' AND ', $where).' ORDER BY merk,serie,modelnummer';
$stmt     = db()->prepare($sql);
$stmt->execute($params);
$modellenAll = $stmt->fetchAll();

foreach ($modellenAll as &$m) {
    $m['_uitzondering_rep'] = isUitzondering($repareerbareMerken, $m['merk'], (int)$m['repareerbaar']);
    $m['_uitzondering_tax'] = isUitzondering($taxatieMerken,      $m['merk'], (int)$m['taxatie']);
}
unset($m);

if ($filterFlag === 'uitzondering_rep') {
    $modellenAll = array_values(array_filter($modellenAll, fn($m) => $m['_uitzondering_rep'] !== null));
} elseif ($filterFlag === 'uitzondering_tax') {
    $modellenAll = array_values(array_filter($modellenAll, fn($m) => $m['_uitzondering_tax'] !== null));
} elseif ($filterFlag === 'niet_rep') {
    $modellenAll = array_values(array_filter($modellenAll, fn($m) => !(int)$m['repareerbaar']));
}

// Statistieken
$statsTotal = (int)db()->query('SELECT COUNT(*) FROM tv_modellen WHERE actief=1')->fetchColumn();
$statsRep   = (int)db()->query('SELECT COUNT(*) FROM tv_modellen WHERE actief=1 AND repareerbaar=1')->fetchColumn();
$statsTax   = (int)db()->query('SELECT COUNT(*) FROM tv_modellen WHERE actief=1 AND taxatie=1')->fetchColumn();

$statsUitzRep = count(array_filter($modellenAll ?: [], fn($m) => $m['_uitzondering_rep'] !== null));
$statsUitzTax = count(array_filter($modellenAll ?: [], fn($m) => $m['_uitzondering_tax'] !== null));

$alleMerken = db()->query(
    'SELECT DISTINCT merk FROM tv_modellen WHERE actief=1 AND merk IS NOT NULL ORDER BY merk'
)->fetchAll(PDO::FETCH_COLUMN);

$allModellenVoorTelling = db()->query('SELECT merk, repareerbaar, taxatie FROM tv_modellen WHERE actief=1')->fetchAll();
$totalUitzRep = 0; $totalUitzTax = 0;
foreach ($allModellenVoorTelling as $m) {
    if (isUitzondering($repareerbareMerken, $m['merk'], (int)$m['repareerbaar']) !== null) $totalUitzRep++;
    if (isUitzondering($taxatieMerken,      $m['merk'], (int)$m['taxatie'])      !== null) $totalUitzTax++;
}

$PAGE_SIZE = 35;

$adminActivePage = 'modellen';
require_once __DIR__ . '/includes/admin-header.php';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8"><title>TV Modellen &ndash; Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Epilogue:wght@800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/base.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/components.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
  <meta name="robots" content="noindex,nofollow">
  <style>
    /* ── Formulier checkboxes ── */
    .cb-row { display:flex; gap:2rem; margin-top:.75rem; margin-bottom:.5rem; flex-wrap:wrap; }
    .cb-item { display:flex; align-items:flex-start; gap:.5rem; }
    .cb-item input[type="checkbox"] {
      appearance:checkbox !important; -webkit-appearance:checkbox !important;
      width:16px !important; height:16px !important; min-width:16px !important;
      padding:0 !important; margin:3px 0 0 0 !important;
      border:1px solid #ccc !important; border-radius:3px !important;
      background:white !important; box-shadow:none !important;
      cursor:pointer; accent-color:#287864; flex-shrink:0;
    }
    .cb-item label {
      display:flex !important; flex-direction:column;
      font-size:.875rem !important; font-weight:600 !important;
      color:#1a1d26 !important; cursor:pointer; margin:0 !important;
    }
    .cb-hint { font-size:.72rem; font-weight:400; color:#9ca3af; margin-top:.15rem; }

    /* ── Toggle pills ── */
    .toggle-pill {
      display:inline-flex; align-items:center; gap:.3rem;
      padding:.2rem .6rem; border-radius:999px; font-size:.72rem; font-weight:700;
      text-decoration:none; transition:all .15s; border:none; cursor:pointer; white-space:nowrap;
    }
    .toggle-on        { background:#dcfce7; color:#14532d; }
    .toggle-on:hover  { background:#bbf7d0; }
    .toggle-off       { background:#f1f5f9; color:#94a3b8; }
    .toggle-off:hover { background:#e2e8f0; color:#475569; }

    /* ── Uitzondering badges ── */
    .uitzondering-badge {
      display:inline-flex; align-items:center; gap:.2rem;
      font-size:.65rem; font-weight:700; padding:.15rem .4rem;
      border-radius:4px; margin-left:.3rem; vertical-align:middle;
      white-space:nowrap;
    }
    .uitz-positief { background:#fef9c3; color:#713f12; border:1px solid #fde68a; }
    .uitz-negatief { background:#fee2e2; color:#7f1d1d; border:1px solid #fca5a5; }

    /* ── Stats row ── */
    .stats-row { display:flex; gap:.5rem; flex-wrap:wrap; margin-bottom:1rem; }
    .stat-chip {
      font-size:.75rem; font-weight:600; padding:.25rem .65rem;
      border-radius:999px; display:flex; align-items:center; gap:.3rem;
      cursor:default;
    }
    .sc-total  { background:#f1f5f9; color:#475569; }
    .sc-rep    { background:#dbeafe; color:#1e3a8a; }
    .sc-tax    { background:#ede9fe; color:#3b0764; }
    .sc-uitz   { background:#fef9c3; color:#713f12; cursor:pointer; text-decoration:none; }
    .sc-uitz:hover { background:#fde68a; }

    /* ── Sync info box ── */
    .sync-box {
      background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px;
      padding:.75rem 1rem; font-size:.82rem; color:#14532d; margin-bottom:1rem;
      display:flex; align-items:flex-start; gap:.6rem; flex-wrap:wrap;
    }
    .sync-box a { color:#15803d; font-weight:600; }

    /* ── Filter & zoekbalk ── */
    .filter-bar {
      display:flex; align-items:center; gap:.6rem; margin-bottom:1rem; flex-wrap:wrap;
    }
    .filter-bar select, .filter-bar input[type=text] {
      padding:.4rem .75rem; border:1px solid #d1d5db; border-radius:8px;
      font-size:.875rem; background:#fff; height:36px;
    }
    .filter-bar select:focus, .filter-bar input:focus {
      outline:none; border-color:#287864;
    }
    .filter-bar input[type=text] { min-width:180px; }
    .filter-actief { border-color:#287864 !important; background:#f0fdf4 !important; }

    /* ── Tabel ── */
    .tabel-teller {
      font-size:.78rem; color:#9ca3af; padding:.5rem 0 .5rem;
    }
    .lazy-sentinel {
      height:48px; display:flex; align-items:center; justify-content:center;
      color:#9ca3af; font-size:.82rem;
    }
    .lazy-spinner { display:none; }
    .lazy-spinner.actief { display:flex; }
    .lazy-einde { display:none; }
    .lazy-einde.zichtbaar { display:flex; }

    /* ── Merk-groep header in tabel ── */
    .merk-groep-row td {
      background:#f8fafc; font-size:.72rem; font-weight:700;
      color:#64748b; letter-spacing:.06em; text-transform:uppercase;
      padding:.35rem .75rem; border-bottom:1px solid #e5e7eb;
    }

    /* ── Add/edit formulier extra styling ── */
    .form-section-title {
      font-size:.7rem; font-weight:700; letter-spacing:.08em;
      text-transform:uppercase; color:#9ca3af; margin:.5rem 0 .3rem;
    }
  </style>
</head>
<body>
<div class="adm-page">

    <h1>TV Modellen</h1>

    <!-- Sync info -->
    <div class="sync-box">
      <span>&#128279;</span>
      <span>
        De vlaggen <strong>Repareerbaar</strong> en <strong>Taxatie</strong> per model zijn gekoppeld aan
        <a href="<?= BASE_URL ?>/admin/advies-instellingen.php">Advies instellingen</a>.
        Merk-standaard reparatie: <strong><?= empty($repareerbareMerken) ? 'alle merken' : h(implode(', ', $repareerbareMerken)) ?></strong> |
        taxatie: <strong><?= empty($taxatieMerken) ? 'alle merken' : h(implode(', ', $taxatieMerken)) ?></strong>.
        <?php if ($totalUitzRep + $totalUitzTax > 0): ?>
          <br>&#9888;&#65039; <strong><?= $totalUitzRep ?></strong> reparatie-uitzondering<?= $totalUitzRep !== 1 ? 'en' : '' ?> &nbsp;&bull;&nbsp;
          <strong><?= $totalUitzTax ?></strong> taxatie-uitzondering<?= $totalUitzTax !== 1 ? 'en' : '' ?>.
          <a href="?filter_flag=uitzondering_rep">Bekijk reparatie-uitzonderingen &rarr;</a>
        <?php endif; ?>
      </span>
    </div>

    <!-- Statistieken -->
    <div class="stats-row">
      <span class="stat-chip sc-total">&#128250; <?= $statsTotal ?> totaal</span>
      <span class="stat-chip sc-rep">&#128295; <?= $statsRep ?> repareerbaar</span>
      <span class="stat-chip sc-tax">&#128203; <?= $statsTax ?> taxatie</span>
      <?php if ($totalUitzRep > 0): ?>
      <a href="?filter_flag=uitzondering_rep" class="stat-chip sc-uitz" title="Modellen die afwijken van merk-standaard reparatie">
        &#9888; <?= $totalUitzRep ?> rep-uitzondering<?= $totalUitzRep !== 1 ? 'en' : '' ?>
      </a>
      <?php endif; ?>
      <?php if ($totalUitzTax > 0): ?>
      <a href="?filter_flag=uitzondering_tax" class="stat-chip sc-uitz" title="Modellen die afwijken van merk-standaard taxatie">
        &#9888; <?= $totalUitzTax ?> tax-uitzondering<?= $totalUitzTax !== 1 ? 'en' : '' ?>
      </a>
      <?php endif; ?>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-success" style="margin-bottom:1rem;"><?= $msg ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['updated'])): ?>
    <div class="alert alert-success" style="margin-bottom:1rem;">Status bijgewerkt.</div>
    <?php endif; ?>

    <?php if ($editModel):
      $defaultRep = defaultVoorMerk($repareerbareMerken, $editModel['merk']);
      $defaultTax = defaultVoorMerk($taxatieMerken,      $editModel['merk']);
      $checkRep   = ($editModel['repareerbaar'] !== null) ? (bool)(int)$editModel['repareerbaar'] : $defaultRep;
      $checkTax   = ($editModel['taxatie']      !== null) ? (bool)(int)$editModel['taxatie']      : $defaultTax;
      $uitzRep    = isUitzondering($repareerbareMerken, $editModel['merk'], (int)$editModel['repareerbaar']);
      $uitzTax    = isUitzondering($taxatieMerken,      $editModel['merk'], (int)$editModel['taxatie']);
    ?>
    <!-- ── BEWERK FORMULIER ── -->
    <div class="admin-card" style="border:2px solid #287864;">
      <h2>&#9998; Bewerken: <?= h($editModel['merk'].' '.$editModel['modelnummer']) ?>
        <?php if ($uitzRep): ?>
          <span class="uitzondering-badge uitz-<?= $uitzRep ?>">&#9888; rep-uitzondering</span>
        <?php endif; ?>
        <?php if ($uitzTax): ?>
          <span class="uitzondering-badge uitz-<?= $uitzTax ?>">&#9888; tax-uitzondering</span>
        <?php endif; ?>
      </h2>
      <form method="POST" class="form-admin">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id"     value="<?= $editModel['id'] ?>">
        <div class="form-row-3">
          <div class="field">
            <label>Merk *</label>
            <input type="text" name="merk" required id="edit_merk"
                   list="merken-list" autocomplete="off"
                   value="<?= h($editModel['merk']) ?>"
                   placeholder="Samsung, Philips, LG&hellip;">
          </div>
          <div class="field">
            <label>Serie</label>
            <input type="text" name="serie" value="<?= h($editModel['serie']) ?>">
          </div>
          <div class="field">
            <label>Modelnummer *</label>
            <input type="text" name="modelnummer" value="<?= h($editModel['modelnummer']) ?>" required>
          </div>
        </div>
        <div class="field">
          <label>Beschrijving</label>
          <textarea name="beschrijving" rows="3"><?= h($editModel['beschrijving']) ?></textarea>
        </div>
        <p class="form-section-title">Advies-vlaggen (afwijking van merk-standaard = uitzondering)</p>
        <div class="cb-row">
          <div class="cb-item">
            <input type="checkbox" id="cb_rep" name="repareerbaar" value="1" <?= $checkRep ? 'checked' : '' ?>>
            <label for="cb_rep">
              Repareerbaar
              <span class="cb-hint">
                Merk-standaard: <?= $defaultRep ? '&#10003; aan' : '&#10007; uit' ?>
                <?php if ($uitzRep === 'positief'): ?>&nbsp;<span style="color:#713f12;font-weight:700;">&#9888; positieve uitzondering (model aan, merk uit)</span><?php endif; ?>
                <?php if ($uitzRep === 'negatief'): ?>&nbsp;<span style="color:#7f1d1d;font-weight:700;">&#9888; negatieve uitzondering (model uit, merk aan)</span><?php endif; ?>
                &mdash; <a href="<?= BASE_URL ?>/admin/advies-instellingen.php" style="color:#287864;">Advies instellingen</a>
              </span>
            </label>
          </div>
          <div class="cb-item">
            <input type="checkbox" id="cb_tax" name="taxatie" value="1" <?= $checkTax ? 'checked' : '' ?>>
            <label for="cb_tax">
              Taxatie mogelijk
              <span class="cb-hint">
                Merk-standaard: <?= $defaultTax ? '&#10003; aan' : '&#10007; uit' ?>
                <?php if ($uitzTax === 'positief'): ?>&nbsp;<span style="color:#713f12;font-weight:700;">&#9888; positieve uitzondering</span><?php endif; ?>
                <?php if ($uitzTax === 'negatief'): ?>&nbsp;<span style="color:#7f1d1d;font-weight:700;">&#9888; negatieve uitzondering</span><?php endif; ?>
                &mdash; <a href="<?= BASE_URL ?>/admin/advies-instellingen.php" style="color:#287864;">Advies instellingen</a>
              </span>
            </label>
          </div>
        </div>
        <div style="display:flex;gap:.75rem;margin-top:1rem;">
          <button type="submit" class="btn btn-primary-sm">&#10003; Wijzigingen opslaan</button>
          <a href="<?= BASE_URL ?>/admin/modellen.php" class="btn btn-sm btn-secondary">Annuleren</a>
        </div>
      </form>
    </div>

    <?php else: ?>
    <!-- ── TOEVOEGEN FORMULIER ── -->
    <div class="admin-card">
      <h2>&#43; Nieuw model toevoegen</h2>
      <form method="POST" class="form-admin" id="add_form">
        <input type="hidden" name="action" value="add">
        <datalist id="merken-list">
          <?php foreach ($alleMerken ?: ['Samsung','Philips','Sony','LG','Panasonic','Hisense','TCL','Anders'] as $m): ?>
          <option value="<?= h($m) ?>">
          <?php endforeach; ?>
        </datalist>
        <div class="form-row-3">
          <div class="field">
            <label>Merk *</label>
            <input type="text" name="merk" required id="add_merk"
                   list="merken-list" autocomplete="off"
                   placeholder="Samsung, Philips, LG&hellip;">
          </div>
          <div class="field">
            <label>Serie</label>
            <input type="text" name="serie" placeholder="Crystal UHD">
          </div>
          <div class="field">
            <label>Modelnummer *</label>
            <input type="text" name="modelnummer" placeholder="UE55CU8000" required>
          </div>
        </div>
        <div class="field">
          <label>Beschrijving</label>
          <textarea name="beschrijving" rows="2" placeholder="Korte omschrijving (optioneel)&hellip;"></textarea>
        </div>
        <p class="form-section-title">Advies-vlaggen</p>
        <div class="cb-row">
          <div class="cb-item">
            <input type="checkbox" id="add_rep" name="repareerbaar" value="1"
              <?= empty($repareerbareMerken) ? 'checked' : '' ?>>
            <label for="add_rep">
              Repareerbaar
              <span class="cb-hint" id="add_hint_rep">Selecteer een merk om de standaard te zien</span>
            </label>
          </div>
          <div class="cb-item">
            <input type="checkbox" id="add_tax" name="taxatie" value="1"
              <?= empty($taxatieMerken) ? 'checked' : '' ?>>
            <label for="add_tax">
              Taxatie mogelijk
              <span class="cb-hint" id="add_hint_tax">Selecteer een merk om de standaard te zien</span>
            </label>
          </div>
        </div>
        <button type="submit" class="btn btn-primary-sm" style="margin-top:.75rem;">&#43; Model toevoegen</button>
      </form>
    </div>
    <?php endif; ?>

    <!-- ── OVERZICHT MET LAZY LOADING ── -->
    <div class="admin-card">
      <!-- Header + zoek/filter -->
      <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem;margin-bottom:.75rem;">
        <h2 style="margin:0;">
          <?= count($modellenAll) ?> model<?= count($modellenAll) !== 1 ? 'len' : '' ?>
          <?php if ($filterMerk): ?>&mdash; <?= h($filterMerk) ?><?php endif; ?>
          <?php if ($filterZoek): ?>&mdash; &ldquo;<?= h($filterZoek) ?>&rdquo;<?php endif; ?>
          <?php if ($filterFlag === 'uitzondering_rep'): ?>&mdash; <span style="color:#713f12;">rep-uitzonderingen</span><?php endif; ?>
          <?php if ($filterFlag === 'uitzondering_tax'): ?>&mdash; <span style="color:#713f12;">tax-uitzonderingen</span><?php endif; ?>
          <?php if ($filterFlag === 'niet_rep'): ?>&mdash; <span style="color:#7f1d1d;">niet repareerbaar</span><?php endif; ?>
        </h2>
      </div>
      <form method="GET" class="filter-bar" id="filter-form">
        <input type="text" name="zoek" placeholder="&#128269; Zoek modelnummer, serie&hellip;"
               value="<?= h($filterZoek) ?>"
               <?= $filterZoek ? 'class="filter-actief"' : '' ?>
               autocomplete="off" style="flex:1;min-width:160px;max-width:260px;">
        <select name="filter_merk" <?= $filterMerk ? 'class="filter-actief"' : '' ?>>
          <option value="">Alle merken</option>
          <?php foreach ($alleMerken as $m): ?>
          <option value="<?= h($m) ?>" <?= $filterMerk===$m ? 'selected' : '' ?>><?= h($m) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="filter_flag" <?= $filterFlag ? 'class="filter-actief"' : '' ?>>
          <option value="">Alle modellen</option>
          <option value="uitzondering_rep" <?= $filterFlag==='uitzondering_rep' ? 'selected' : '' ?>>&#9888; Rep-uitzonderingen (<?= $totalUitzRep ?>)</option>
          <option value="uitzondering_tax" <?= $filterFlag==='uitzondering_tax' ? 'selected' : '' ?>>&#9888; Tax-uitzonderingen (<?= $totalUitzTax ?>)</option>
          <option value="niet_rep"         <?= $filterFlag==='niet_rep'         ? 'selected' : '' ?>>&#10007; Niet repareerbaar</option>
        </select>
        <button type="submit" class="btn btn-sm btn-secondary">Filter</button>
        <?php if ($filterMerk || $filterZoek || $filterFlag): ?>
        <a href="<?= BASE_URL ?>/admin/modellen.php" class="btn btn-sm btn-secondary">&#10005; Wis filters</a>
        <?php endif; ?>
      </form>

      <div class="tabel-teller" id="tabel-teller">
        <?= count($modellenAll) > 0
          ? 'Toont <span id="getoond-aantal">0</span> van <strong>'.count($modellenAll).'</strong> modellen'
          : 'Geen modellen gevonden.'
        ?>
      </div>

      <table class="admin-table">
        <thead>
          <tr>
            <th style="width:120px">Merk</th>
            <th>Modelnummer</th>
            <th style="width:90px">&#128295; Repareerbaar</th>
            <th style="width:150px">&#128203; Taxatie</th>
            <th style="width:190px">Acties</th>
          </tr>
        </thead>
        <tbody id="modellen-tbody">
          <!-- Gevuld door JS lazy loader -->
        </tbody>
      </table>

      <!-- Sentinel voor Intersection Observer -->
      <div id="lazy-sentinel" class="lazy-sentinel">
        <span class="lazy-spinner" id="lazy-spinner">&#9679; laden&hellip;</span>
        <span class="lazy-einde"  id="lazy-einde">&#10003; Alle <?= count($modellenAll) ?> modellen geladen</span>
      </div>
    </div>

</div><!-- /.adm-page -->

<!-- ── Alle modellen als JSON voor de lazy loader ── -->
<script id="modellen-data" type="application/json">
<?= json_encode(array_values($modellenAll), JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>
</script>

<script>
// ── Merk-defaults ──────────────────────────────────────
const repMerken = <?= json_encode(array_map('mb_strtolower', $repareerbareMerken)) ?>;
const taxMerken = <?= json_encode(array_map('mb_strtolower', $taxatieMerken)) ?>;
const BASE_URL  = '<?= BASE_URL ?>';
const PAGE_SIZE = <?= $PAGE_SIZE ?>;

function merkDefault(lijst, merk) {
  return lijst.length === 0 || lijst.includes((merk||'').toLowerCase());
}

// ── Lazy loader ────────────────────────────────────────
const MODELLEN = JSON.parse(document.getElementById('modellen-data').textContent);
let geladen = 0;
const tbody  = document.getElementById('modellen-tbody');
const spinner = document.getElementById('lazy-spinner');
const eindeEl = document.getElementById('lazy-einde');
const teller  = document.getElementById('getoond-aantal');

function merkGroepLabel(merk) {
  const tr = document.createElement('tr');
  tr.className = 'merk-groep-row';
  tr.innerHTML = '<td colspan="5">' + escHtml(merk) + '</td>';
  return tr;
}

function escHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function uitzBadge(soort, type) {
  if (!type) return '';
  const label = type === 'positief' ? '&#9888; positief' : '&#9888; negatief';
  return `<span class="uitzondering-badge uitz-${escHtml(type)}" title="${escHtml(soort)}-uitzondering: ${escHtml(type)}">${label}</span>`;
}

function laadBatch() {
  if (geladen >= MODELLEN.length) return;
  spinner.classList.add('actief');

  const batch = MODELLEN.slice(geladen, geladen + PAGE_SIZE);
  let huidigMerk = geladen > 0 ? MODELLEN[geladen - 1].merk : null;

  batch.forEach(m => {
    if (m.merk !== huidigMerk) {
      tbody.appendChild(merkGroepLabel(m.merk));
      huidigMerk = m.merk;
    }

    const repUitz = m._uitzondering_rep || null;
    const taxUitz = m._uitzondering_tax || null;
    const repOn   = parseInt(m.repareerbaar) === 1;
    const taxOn   = parseInt(m.taxatie)      === 1;

    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td style="color:#9ca3af;font-size:.78rem;">${escHtml(m.merk)}</td>
      <td>
        <strong>${escHtml(m.modelnummer)}</strong>
        ${m.serie ? `<br><span style="font-size:.78rem;color:#9ca3af;">${escHtml(m.serie)}</span>` : ''}
      </td>
      <td>
        <a href="?toggle=${m.id}&veld=repareerbaar"
           class="toggle-pill ${repOn ? 'toggle-on' : 'toggle-off'}"
           title="Klik om te wisselen">
          ${repOn ? '&#10003; Ja' : '&#10007; Nee'}
        </a>
        ${uitzBadge('reparatie', repUitz)}
      </td>
      <td>
        <a href="?toggle=${m.id}&veld=taxatie"
           class="toggle-pill ${taxOn ? 'toggle-on' : 'toggle-off'}"
           title="Klik om te wisselen">
          ${taxOn ? '&#10003; Ja' : '&#10007; Nee'}
        </a>
        ${uitzBadge('taxatie', taxUitz)}
      </td>
      <td style="display:flex;gap:.4rem;flex-wrap:wrap;">
        <a href="?edit=${m.id}" class="btn btn-sm btn-secondary">&#9998;</a>
        <a href="${BASE_URL}/tv/${escHtml(m.slug)}" target="_blank" class="btn btn-sm btn-secondary">&#128269;</a>
        <a href="?delete=${m.id}" class="btn btn-sm btn-danger"
           onclick="return confirm('Model ${escHtml(m.modelnummer)} verwijderen?')">&#128465;</a>
      </td>
    `;
    tbody.appendChild(tr);
  });

  geladen += batch.length;
  if (teller) teller.textContent = geladen;
  spinner.classList.remove('actief');

  if (geladen >= MODELLEN.length) {
    eindeEl.classList.add('zichtbaar');
    observer.disconnect();
  }
}

const sentinel = document.getElementById('lazy-sentinel');
const observer = new IntersectionObserver(entries => {
  if (entries[0].isIntersecting) laadBatch();
}, { rootMargin: '200px' });

laadBatch();
if (geladen < MODELLEN.length) observer.observe(sentinel);

// ── Add-formulier: checkbox-standaard bij merk-wissel ──
(function() {
  const addMerk = document.getElementById('add_merk');
  if (!addMerk) return;
  let debounce;
  function updateDefaults() {
    const m    = addMerk.value.trim();
    if (!m) return;
    const dRep = merkDefault(repMerken, m);
    const dTax = merkDefault(taxMerken, m);
    const rep  = document.getElementById('add_rep');
    const tax  = document.getElementById('add_tax');
    const hRep = document.getElementById('add_hint_rep');
    const hTax = document.getElementById('add_hint_tax');
    if (rep) rep.checked = dRep;
    if (tax) tax.checked = dTax;
    if (hRep) hRep.textContent = 'DB-standaard voor ' + m + ': ' + (dRep ? '\u2713 aan' : '\u2717 uit');
    if (hTax) hTax.textContent = 'DB-standaard voor ' + m + ': ' + (dTax ? '\u2713 aan' : '\u2717 uit');
  }
  addMerk.addEventListener('change', updateDefaults);
  addMerk.addEventListener('input', function() {
    clearTimeout(debounce);
    debounce = setTimeout(updateDefaults, 350);
  });
})();

// ── Zoek: live filter via debounce ──
(function() {
  const zoekInput = document.querySelector('input[name=zoek]');
  if (!zoekInput) return;
  let t;
  zoekInput.addEventListener('input', function() {
    clearTimeout(t);
    t = setTimeout(() => document.getElementById('filter-form').submit(), 400);
  });
})();
</script>
</body>
</html>