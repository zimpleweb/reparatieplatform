<?php
session_start();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$msg  = '';
$fout = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('Ongeldig beveiligingstoken.');
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $niveau = in_array($_POST['niveau'] ?? '', ['merk', 'serie', 'model'])
            ? $_POST['niveau'] : 'model';
        $titel        = trim($_POST['titel'] ?? '');
        $omschrijving = trim($_POST['omschrijving'] ?? '');
        $frequentie   = in_array($_POST['frequentie'] ?? '', ['hoog', 'middel', 'laag'])
            ? $_POST['frequentie'] : 'middel';
        $type_icon    = trim($_POST['type_icon'] ?? '🔧');

        $tv_model_id  = null;
        $merk_op      = null;
        $serie_op     = null;
        $geldig       = false;

        if ($niveau === 'model') {
            $tv_model_id = (int)($_POST['tv_model_id'] ?? 0);
            if ($tv_model_id && $titel && $omschrijving) {
                $geldig = true;
            }
        } elseif ($niveau === 'serie') {
            $merk_op  = trim($_POST['klacht_merk']  ?? '');
            $serie_op = trim($_POST['klacht_serie'] ?? '');
            if ($merk_op && $serie_op && $titel && $omschrijving) {
                $geldig = true;
            }
        } elseif ($niveau === 'merk') {
            $merk_op = trim($_POST['klacht_merk'] ?? '');
            if ($merk_op && $titel && $omschrijving) {
                $geldig = true;
            }
        }

        if ($geldig) {
            db()->prepare(
                'INSERT INTO klachten (tv_model_id, niveau, merk, serie, titel, omschrijving, frequentie, type_icon)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([$tv_model_id, $niveau, $merk_op, $serie_op, $titel, $omschrijving, $frequentie, $type_icon]);
            $msg = 'Klacht toegevoegd.';
        } else {
            $fout = 'Vul alle verplichte velden in voor het gekozen niveau.';
        }

    } elseif ($action === 'delete') {
        db()->prepare('DELETE FROM klachten WHERE id=?')->execute([(int)($_POST['id'] ?? 0)]);
        $msg = 'Klacht verwijderd.';
    }
}

// ── Lijst / filter ────────────────────────────────────────────────
$model_id = (int)($_GET['model'] ?? 0);
if ($model_id) {
    $mn = db()->prepare('SELECT merk, serie, modelnummer FROM tv_modellen WHERE id=?');
    $mn->execute([$model_id]);
    $filterModel = $mn->fetch() ?: null;

    $st = db()->prepare(
        'SELECT k.*, m.modelnummer as model_modelnummer, m.merk as model_merk
         FROM klachten k
         LEFT JOIN tv_modellen m ON m.id = k.tv_model_id
         WHERE k.tv_model_id = ?
            OR (k.niveau = "serie" AND k.merk = ? AND k.serie = ?)
            OR (k.niveau = "merk"  AND k.merk = ?)
         ORDER BY k.frequentie DESC, k.id DESC'
    );
    $st->execute([
        $model_id,
        $filterModel['merk']  ?? '',
        $filterModel['serie'] ?? '',
        $filterModel['merk']  ?? '',
    ]);
    $klachten = $st->fetchAll();
} else {
    $st = db()->query(
        'SELECT k.*, m.modelnummer as model_modelnummer, m.merk as model_merk
         FROM klachten k
         LEFT JOIN tv_modellen m ON m.id = k.tv_model_id
         ORDER BY k.frequentie DESC, k.id DESC'
    );
    $klachten = $st->fetchAll();
}

$modellen = db()->query('SELECT id, merk, modelnummer FROM tv_modellen WHERE actief=1 ORDER BY merk, modelnummer')->fetchAll();
$merken   = getMerken();

// Alle series per merk als JSON voor JavaScript
$seriesPerMerk = [];
foreach ($merken as $m) {
    $seriesPerMerk[$m] = getSeries($m);
}

$adminActivePage = 'klachten';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Klachten &ndash; Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Epilogue:wght@700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/base.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/components.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
  <meta name="robots" content="noindex,nofollow">
</head>
<body>

<?php require_once __DIR__ . '/includes/admin-header.php'; ?>

<div class="adm-page">
  <h1>Klachten beheren</h1>

  <?php if ($msg):  ?><div class="alert alert-success"><?= h($msg) ?></div><?php endif; ?>
  <?php if ($fout): ?><div class="alert alert-error"><?= h($fout) ?></div><?php endif; ?>

  <div class="admin-card">
    <h2>Klacht toevoegen</h2>
    <form method="POST" class="form-admin" id="klacht-form">
      <input type="hidden" name="csrf"   value="<?= csrf() ?>">
      <input type="hidden" name="action" value="add">

      <div class="form-row-2">
        <div class="field">
          <label>Niveau *</label>
          <select name="niveau" id="klacht-niveau" required onchange="updateNiveauVelden()">
            <option value="model">Per Model</option>
            <option value="serie">Per Serie (alle modellen in de serie)</option>
            <option value="merk">Per Merk (alle series en modellen)</option>
          </select>
        </div>
        <div class="field">
          <label>Frequentie *</label>
          <select name="frequentie" required>
            <option value="hoog">Hoog &mdash; veel gemeld</option>
            <option value="middel" selected>Middel &mdash; regelmatig</option>
            <option value="laag">Laag &mdash; minder vaak</option>
          </select>
        </div>
      </div>

      <!-- Velden: Model-niveau -->
      <div id="velden-model" class="form-row-2">
        <div class="field">
          <label>Model *</label>
          <select name="tv_model_id">
            <option value="">Selecteer model</option>
            <?php foreach ($modellen as $m): ?>
            <option value="<?= $m['id'] ?>" <?= $m['id'] === $model_id ? 'selected' : '' ?>>
              <?= h($m['merk'] . ' ' . $m['modelnummer']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div><!-- lege cel voor uitlijning --></div>
      </div>

      <!-- Velden: Merk-niveau (en Serie-niveau) -->
      <div id="velden-merk-serie" style="display:none;" class="form-row-2">
        <div class="field">
          <label>Merk *</label>
          <select name="klacht_merk" id="klacht-merk-sel" onchange="updateSerieOpties()">
            <option value="">Selecteer merk</option>
            <?php foreach ($merken as $m): ?>
            <option value="<?= h($m) ?>"><?= h($m) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field" id="veld-serie" style="display:none;">
          <label>Serie *</label>
          <select name="klacht_serie" id="klacht-serie-sel">
            <option value="">Selecteer eerst een merk</option>
          </select>
        </div>
      </div>

      <div class="form-row-2">
        <div class="field">
          <label>Titel *</label>
          <input type="text" name="titel" placeholder="Bijv. Donkere vlekken — LED strip uitval" required>
        </div>
        <div class="field">
          <label>Icoon (emoji)</label>
          <input type="text" name="type_icon" value="🔧" maxlength="5">
        </div>
      </div>
      <div class="field">
        <label>Omschrijving *</label>
        <textarea name="omschrijving" class="textarea-tall" placeholder="Uitgebreide uitleg van de klacht en mogelijke oplossing..." required></textarea>
      </div>
      <button type="submit" class="btn btn-primary-sm">+ Klacht toevoegen</button>
    </form>
  </div>

  <div class="admin-card">
    <h2>
      <?php if ($model_id && isset($filterModel)):
        echo 'Klachten voor '
           . h($filterModel['merk'] . ' ' . $filterModel['modelnummer'])
           . ' (incl. serie &amp; merk) &mdash; <a href="' . BASE_URL . '/admin/klachten.php" class="link-muted">Alle klachten tonen</a>';
      else:
        echo count($klachten) . ' klachten in database';
      endif; ?>
    </h2>
    <table class="admin-table">
      <thead>
        <tr>
          <th>Niveau</th>
          <th>Geldt voor</th>
          <th>Titel</th>
          <th>Frequentie</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($klachten as $k):
        $niveau = $k['niveau'] ?? 'model';
        if ($niveau === 'merk') {
            $geldtVoor = '🏷 ' . h($k['merk'] ?? $k['model_merk'] ?? '—') . ' (alle series)';
        } elseif ($niveau === 'serie') {
            $geldtVoor = '📂 ' . h($k['merk'] ?? $k['model_merk'] ?? '—') . ' / ' . h($k['serie'] ?? '—') . ' (alle modellen)';
        } else {
            $geldtVoor = h(($k['model_merk'] ?? $k['merk'] ?? '—') . ' ' . ($k['model_modelnummer'] ?? ''));
        }
        $niveauLabel = ['merk' => 'Merk', 'serie' => 'Serie', 'model' => 'Model'][$niveau] ?? 'Model';
        $niveauKleur = ['merk' => 'badge-purple', 'serie' => 'badge-blue', 'model' => 'badge-green'][$niveau] ?? 'badge-green';
      ?>
      <tr>
        <td><span class="badge <?= $niveauKleur ?>"><?= $niveauLabel ?></span></td>
        <td style="white-space:nowrap;font-size:.83rem;"><?= $geldtVoor ?></td>
        <td><?= h($k['type_icon']) ?> <?= h($k['titel']) ?></td>
        <td>
          <span class="badge <?= $k['frequentie']==='hoog' ? 'badge-red' : ($k['frequentie']==='middel' ? 'badge-yellow' : 'badge-green') ?>">
            <?= h($k['frequentie']) ?>
          </span>
        </td>
        <td>
          <form method="POST" style="margin:0;" onsubmit="return confirm('Klacht verwijderen?')">
            <input type="hidden" name="csrf"   value="<?= csrf() ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id"     value="<?= (int)$k['id'] ?>">
            <button type="submit" class="btn btn-sm btn-danger">Verwijder</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div><!-- /.adm-page -->

<script>
const SERIES_PER_MERK = <?= json_encode($seriesPerMerk, JSON_UNESCAPED_UNICODE) ?>;

function updateNiveauVelden() {
  var niveau = document.getElementById('klacht-niveau').value;
  document.getElementById('velden-model').style.display      = (niveau === 'model') ? '' : 'none';
  document.getElementById('velden-merk-serie').style.display = (niveau !== 'model') ? '' : 'none';
  document.getElementById('veld-serie').style.display        = (niveau === 'serie') ? '' : 'none';

  // (De)activeer required-attributen
  document.querySelector('[name="tv_model_id"]').required    = (niveau === 'model');
  document.querySelector('[name="klacht_merk"]').required    = (niveau !== 'model');
  var serieEl = document.querySelector('[name="klacht_serie"]');
  if (serieEl) serieEl.required = (niveau === 'serie');
}

function updateSerieOpties() {
  var merk   = document.getElementById('klacht-merk-sel').value;
  var serieEl = document.getElementById('klacht-serie-sel');
  if (!serieEl) return;
  var series  = (merk && SERIES_PER_MERK[merk]) ? SERIES_PER_MERK[merk] : [];
  serieEl.innerHTML = '<option value="">Selecteer serie</option>';
  series.forEach(function(s) {
    if (!s) return;
    var opt = document.createElement('option');
    opt.value = s; opt.textContent = s;
    serieEl.appendChild(opt);
  });
}

// Initialiseren
updateNiveauVelden();
</script>
</body>
</html>
