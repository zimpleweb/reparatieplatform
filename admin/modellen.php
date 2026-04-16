<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/advies_regels.php';
requireAdmin();

$msg = '';

// Laad adviesregels één keer — wordt gebruikt voor merk-standaarden
$advies = getAdviesRegels();
$repareerbareMerken = $advies['reparatie_merken'] ?? []; // lege array = alle merken
$taxatieMerken      = $advies['taxatie_merken']   ?? []; // lege array = alle merken

/**
 * Bepaal default waarde voor checkbox op basis van DB-regels.
 * Lege merklijst = alle merken toegestaan = default aan.
 */
function defaultVoorMerk(array $merkLijst, string $merk): bool {
    if (empty($merkLijst)) return true; // geen beperking = standaard aan
    return in_array(
        mb_strtolower(trim($merk)),
        array_map(fn($m) => mb_strtolower(trim($m)), $merkLijst),
        true
    );
}

// ── Toevoegen ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='add') {
    $merk   = trim($_POST['merk']);
    $slug   = slugify($merk.' '.$_POST['modelnummer']);
    $check  = db()->prepare('SELECT COUNT(*) FROM tv_modellen WHERE slug=?');
    $check->execute([$slug]);
    if ($check->fetchColumn() > 0) $slug .= '-'.time();

    // Repareerbaar: gebruik DB-standaard als checkbox niet aangevinkt
    $repVal = isset($_POST['repareerbaar']) ? 1
            : (defaultVoorMerk($repareerbareMerken, $merk) ? 1 : 0);
    $taxVal = isset($_POST['taxatie']) ? 1
            : (defaultVoorMerk($taxatieMerken, $merk) ? 1 : 0);

    db()->prepare(
        'INSERT INTO tv_modellen (merk,serie,modelnummer,slug,beschrijving,repareerbaar,taxatie) VALUES (?,?,?,?,?,?,?)'
    )->execute([
        $merk,
        trim($_POST['serie']),
        trim($_POST['modelnummer']),
        $slug,
        trim($_POST['beschrijving']),
        $repVal,
        $taxVal,
    ]);
    $msg = 'Model "'.h($_POST['modelnummer']).'" succesvol toegevoegd.';
}

// ── Bewerken ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='edit') {
    $id = (int)$_POST['id'];
    db()->prepare(
        'UPDATE tv_modellen SET merk=?,serie=?,modelnummer=?,beschrijving=?,repareerbaar=?,taxatie=? WHERE id=?'
    )->execute([
        trim($_POST['merk']),
        trim($_POST['serie']),
        trim($_POST['modelnummer']),
        trim($_POST['beschrijving']),
        isset($_POST['repareerbaar']) ? 1 : 0,
        isset($_POST['taxatie'])      ? 1 : 0,
        $id,
    ]);
    $msg = 'Model "'.h($_POST['modelnummer']).'" bijgewerkt.';
}

// ── Verwijderen ──────────────────────────────────────────
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    db()->prepare('UPDATE tv_modellen SET actief=0 WHERE id=?')->execute([$_GET['delete']]);
    $msg = 'Model verwijderd.';
}

// ── Snel toggle repareerbaar/taxatie via AJAX-achtige GET ─────────
if (isset($_GET['toggle']) && is_numeric($_GET['toggle']) && isset($_GET['veld'])) {
    $veld = in_array($_GET['veld'], ['repareerbaar','taxatie']) ? $_GET['veld'] : null;
    if ($veld) {
        $row = db()->prepare('SELECT '.$veld.' FROM tv_modellen WHERE id=? AND actief=1');
        $row->execute([(int)$_GET['toggle']]);
        $huidig = (int)$row->fetchColumn();
        db()->prepare('UPDATE tv_modellen SET '.$veld.'=? WHERE id=?')
            ->execute([$huidig ? 0 : 1, (int)$_GET['toggle']]);
        $msg = 'Status bijgewerkt.';
    }
    // Redirect terug zonder GET-params in history
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

// ── Alle modellen + filter ──────────────────────────────
$filterMerk = trim($_GET['filter_merk'] ?? '');
if ($filterMerk !== '') {
    $stmt = db()->prepare('SELECT * FROM tv_modellen WHERE actief=1 AND merk=? ORDER BY serie,modelnummer');
    $stmt->execute([$filterMerk]);
    $modellen = $stmt->fetchAll();
} else {
    $modellen = db()->query('SELECT * FROM tv_modellen WHERE actief=1 ORDER BY merk,serie,modelnummer')->fetchAll();
}

// Statistieken
$statsTotal = (int)db()->query('SELECT COUNT(*) FROM tv_modellen WHERE actief=1')->fetchColumn();
$statsRep   = (int)db()->query('SELECT COUNT(*) FROM tv_modellen WHERE actief=1 AND repareerbaar=1')->fetchColumn();
$statsTax   = (int)db()->query('SELECT COUNT(*) FROM tv_modellen WHERE actief=1 AND taxatie=1')->fetchColumn();

// Alle unieke merken voor filter-dropdown
$alleMerken = db()->query(
    'SELECT DISTINCT merk FROM tv_modellen WHERE actief=1 AND merk IS NOT NULL ORDER BY merk'
)->fetchAll(PDO::FETCH_COLUMN);
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
    /* Checkboxes */
    .cb-row { display:flex; gap:2rem; margin-top:.75rem; margin-bottom:.5rem; }
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
    /* Toggle pills in tabel */
    .toggle-pill {
      display:inline-flex; align-items:center; gap:.3rem;
      padding:.2rem .6rem; border-radius:999px; font-size:.72rem; font-weight:700;
      text-decoration:none; transition:all .15s; border:none; cursor:pointer;
    }
    .toggle-on  { background:#dcfce7; color:#14532d; }
    .toggle-on:hover  { background:#bbf7d0; }
    .toggle-off { background:#f1f5f9; color:#94a3b8; }
    .toggle-off:hover { background:#e2e8f0; color:#475569; }
    /* Stats row */
    .stats-row { display:flex; gap:.5rem; flex-wrap:wrap; margin-bottom:1rem; }
    .stat-chip {
      font-size:.75rem; font-weight:600; padding:.25rem .65rem;
      border-radius:999px; display:flex; align-items:center; gap:.3rem;
    }
    .sc-total { background:#f1f5f9; color:#475569; }
    .sc-rep   { background:#dbeafe; color:#1e3a8a; }
    .sc-tax   { background:#ede9fe; color:#3b0764; }
    /* Advies sync info box */
    .sync-box {
      background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px;
      padding:.75rem 1rem; font-size:.82rem; color:#14532d; margin-bottom:1rem;
      display:flex; align-items:center; gap:.6rem;
    }
    .sync-box a { color:#15803d; font-weight:600; }
    /* Filter bar */
    .filter-bar {
      display:flex; align-items:center; gap:.75rem; margin-bottom:1rem; flex-wrap:wrap;
    }
    .filter-bar select, .filter-bar input {
      padding:.4rem .75rem; border:1px solid #d1d5db; border-radius:8px;
      font-size:.875rem; background:#fff;
    }
    .filter-bar select:focus, .filter-bar input:focus {
      outline:none; border-color:#287864;
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
    <a href="<?= BASE_URL ?>/admin/aanvragen.php"><span class="icon">&#128236;</span> Aanvragen</a>
    <a href="<?= BASE_URL ?>/admin/modellen.php" class="active"><span class="icon">&#128250;</span> TV Modellen</a>
    <a href="<?= BASE_URL ?>/admin/klachten.php"><span class="icon">&#9888;</span> Klachten</a>
    <a href="<?= BASE_URL ?>/admin/advies-instellingen.php"><span class="icon">&#9881;</span> Advies instellingen</a>
    <a href="<?= BASE_URL ?>/" target="_blank"><span class="icon">&#127760;</span> Website bekijken</a>
  </div>
  <div class="admin-content">
    <h1>TV Modellen</h1>

    <!-- Sync info -->
    <div class="sync-box">
      &#128279;
      De vlaggen <strong>Repareerbaar</strong> en <strong>Taxatie</strong> per model
      zijn direct gekoppeld aan
      <a href="<?= BASE_URL ?>/admin/advies-instellingen.php">Advies instellingen</a>.
      Merk-standaarden worden geladen uit de DB-regels
      (reparatie: <?= empty($repareerbareMerken) ? '<em>alle merken</em>' : h(implode(', ', $repareerbareMerken)) ?> |
       taxatie: <?= empty($taxatieMerken) ? '<em>alle merken</em>' : h(implode(', ', $taxatieMerken)) ?>).
    </div>

    <!-- Statistieken -->
    <div class="stats-row">
      <span class="stat-chip sc-total">&#128250; <?= $statsTotal ?> modellen totaal</span>
      <span class="stat-chip sc-rep">&#128295; <?= $statsRep ?> repareerbaar</span>
      <span class="stat-chip sc-tax">&#128203; <?= $statsTax ?> taxatie mogelijk</span>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-success" style="margin-bottom:1rem;"><?= $msg ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['updated'])): ?>
    <div class="alert alert-success" style="margin-bottom:1rem;">Status bijgewerkt.</div>
    <?php endif; ?>

    <?php if ($editModel):
      // Gebruik DB-standaard voor het merk van dit model
      $defaultRep = defaultVoorMerk($repareerbareMerken, $editModel['merk']);
      $defaultTax = defaultVoorMerk($taxatieMerken,      $editModel['merk']);
      $checkRep   = ($editModel['repareerbaar'] !== null) ? (bool)(int)$editModel['repareerbaar'] : $defaultRep;
      $checkTax   = ($editModel['taxatie']      !== null) ? (bool)(int)$editModel['taxatie']      : $defaultTax;
    ?>
    <!-- BEWERK FORMULIER -->
    <div class="admin-card" style="border:2px solid #287864;">
      <h2>&#9998; Bewerken: <?= h($editModel['merk'].' '.$editModel['modelnummer']) ?></h2>
      <form method="POST" class="form-admin">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id"     value="<?= $editModel['id'] ?>">
        <div class="form-row-3">
          <div class="field">
            <label>Merk *</label>
            <select name="merk" required id="edit_merk">
              <?php foreach ($alleMerken ?: ['Samsung','Philips','Sony','LG','Panasonic','Hisense','TCL','Anders'] as $m): ?>
              <option value="<?= h($m) ?>" <?= $editModel['merk']===$m ? 'selected' : '' ?>><?= h($m) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label>Serie *</label>
            <input type="text" name="serie" value="<?= h($editModel['serie']) ?>" required>
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
        <div class="cb-row">
          <div class="cb-item">
            <input type="checkbox" id="cb_rep" name="repareerbaar" value="1" <?= $checkRep ? 'checked' : '' ?>>
            <label for="cb_rep">
              Repareerbaar
              <span class="cb-hint" id="hint_rep">
                DB-standaard voor <?= h($editModel['merk']) ?>:
                <?= $defaultRep ? '&#10003; aan' : '&#10007; uit' ?>
                &mdash; via <a href="<?= BASE_URL ?>/admin/advies-instellingen.php" style="color:#287864;">Advies instellingen</a>
              </span>
            </label>
          </div>
          <div class="cb-item">
            <input type="checkbox" id="cb_tax" name="taxatie" value="1" <?= $checkTax ? 'checked' : '' ?>>
            <label for="cb_tax">
              Taxatie mogelijk
              <span class="cb-hint" id="hint_tax">
                DB-standaard voor <?= h($editModel['merk']) ?>:
                <?= $defaultTax ? '&#10003; aan' : '&#10007; uit' ?>
                &mdash; via <a href="<?= BASE_URL ?>/admin/advies-instellingen.php" style="color:#287864;">Advies instellingen</a>
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
    <!-- TOEVOEGEN FORMULIER -->
    <div class="admin-card">
      <h2>Nieuw model toevoegen</h2>
      <form method="POST" class="form-admin" id="add_form">
        <input type="hidden" name="action" value="add">
        <div class="form-row-3">
          <div class="field">
            <label>Merk *</label>
            <select name="merk" required id="add_merk">
              <?php foreach ($alleMerken ?: ['Samsung','Philips','Sony','LG','Panasonic','Hisense','TCL','Anders'] as $m): ?>
              <option value="<?= h($m) ?>"><?= h($m) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field"><label>Serie *</label><input type="text" name="serie" placeholder="Crystal UHD" required></div>
          <div class="field"><label>Modelnummer *</label><input type="text" name="modelnummer" placeholder="UE55CU8000" required></div>
        </div>
        <div class="field"><label>Beschrijving</label><textarea name="beschrijving" placeholder="Korte omschrijving..."></textarea></div>
        <div class="cb-row">
          <div class="cb-item">
            <input type="checkbox" id="add_rep" name="repareerbaar" value="1"
              <?= defaultVoorMerk($repareerbareMerken, $alleMerken[0] ?? 'Samsung') ? 'checked' : '' ?>>
            <label for="add_rep">
              Repareerbaar
              <span class="cb-hint" id="add_hint_rep">Standaard op basis van merk &amp; advies instellingen</span>
            </label>
          </div>
          <div class="cb-item">
            <input type="checkbox" id="add_tax" name="taxatie" value="1"
              <?= defaultVoorMerk($taxatieMerken, $alleMerken[0] ?? 'Samsung') ? 'checked' : '' ?>>
            <label for="add_tax">
              Taxatie mogelijk
              <span class="cb-hint" id="add_hint_tax">Standaard op basis van merk &amp; advies instellingen</span>
            </label>
          </div>
        </div>
        <button type="submit" class="btn btn-primary-sm" style="margin-top:.75rem;">&#43; Model toevoegen</button>
      </form>
    </div>
    <?php endif; ?>

    <!-- OVERZICHT -->
    <div class="admin-card">
      <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem;margin-bottom:1rem;">
        <h2 style="margin:0;"><?= count($modellen) ?> modellen<?= $filterMerk ? ' &mdash; '.h($filterMerk) : '' ?></h2>
        <form method="GET" class="filter-bar">
          <select name="filter_merk" onchange="this.form.submit()">
            <option value="">Alle merken</option>
            <?php foreach ($alleMerken as $m): ?>
            <option value="<?= h($m) ?>" <?= $filterMerk===$m ? 'selected' : '' ?>><?= h($m) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if ($filterMerk): ?>
          <a href="<?= BASE_URL ?>/admin/modellen.php" class="btn btn-sm btn-secondary">&#10005; Filter wissen</a>
          <?php endif; ?>
        </form>
      </div>
      <table class="admin-table">
        <thead>
          <tr>
            <th>Merk</th>
            <th>Modelnummer</th>
            <th>&#128295; Repareerbaar</th>
            <th>&#128203; Taxatie</th>
            <th>Acties</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($modellen as $m): ?>
        <tr <?= (isset($_GET['edit']) && (int)$_GET['edit'] === (int)$m['id']) ? 'style="background:#f0faf7;"' : '' ?>>
          <td><?= h($m['merk']) ?></td>
          <td>
            <strong><?= h($m['modelnummer']) ?></strong>
            <?php if ($m['serie']): ?><br><span style="font-size:.78rem;color:#9ca3af;"><?= h($m['serie']) ?></span><?php endif; ?>
          </td>
          <td>
            <a href="?toggle=<?= $m['id'] ?>&veld=repareerbaar"
               class="toggle-pill <?= $m['repareerbaar'] ? 'toggle-on' : 'toggle-off' ?>"
               title="Klik om te wisselen">
              <?= $m['repareerbaar'] ? '&#10003; Ja' : '&#10007; Nee' ?>
            </a>
          </td>
          <td>
            <a href="?toggle=<?= $m['id'] ?>&veld=taxatie"
               class="toggle-pill <?= $m['taxatie'] ? 'toggle-on' : 'toggle-off' ?>"
               title="Klik om te wisselen">
              <?= $m['taxatie'] ? '&#10003; Ja' : '&#10007; Nee' ?>
            </a>
          </td>
          <td style="display:flex;gap:.4rem;flex-wrap:wrap;">
            <a href="?edit=<?= $m['id'] ?>" class="btn btn-sm btn-secondary">&#9998; Bewerken</a>
            <a href="<?= BASE_URL ?>/tv/<?= h($m['slug']) ?>" target="_blank" class="btn btn-sm btn-secondary">Bekijk</a>
            <a href="?delete=<?= $m['id'] ?>" class="btn btn-sm btn-danger"
               onclick="return confirm('Model <?= h($m['modelnummer']) ?> verwijderen?')">&#128465;</a>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>
</div>

<script>
// Pas checkbox-standaarden aan wanneer merk verandert (add formulier)
(function() {
  // Merk-defaults komen uit PHP via data-attribuut
  const repMerken = <?= json_encode(array_map('mb_strtolower', $repareerbareMerken)) ?>;
  const taxMerken = <?= json_encode(array_map('mb_strtolower', $taxatieMerken)) ?>;

  function defaultVoorMerk(lijst, merk) {
    if (lijst.length === 0) return true; // lege lijst = alle merken = aan
    return lijst.includes(merk.toLowerCase());
  }

  const addMerk = document.getElementById('add_merk');
  if (addMerk) {
    addMerk.addEventListener('change', function() {
      const m = this.value;
      const rep = document.getElementById('add_rep');
      const tax = document.getElementById('add_tax');
      const hRep = document.getElementById('add_hint_rep');
      const hTax = document.getElementById('add_hint_tax');

      const dRep = defaultVoorMerk(repMerken, m);
      const dTax = defaultVoorMerk(taxMerken, m);

      rep.checked = dRep;
      tax.checked = dTax;

      if (hRep) hRep.textContent = 'DB-standaard voor ' + m + ': ' + (dRep ? '\u2713 aan' : '\u2717 uit');
      if (hTax) hTax.textContent = 'DB-standaard voor ' + m + ': ' + (dTax ? '\u2713 aan' : '\u2717 uit');
    });
  }
})();
</script>
</body>
</html>
