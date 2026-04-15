<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$msg = '';

// Toevoegen
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='add') {
    $slug = slugify($_POST['merk'].' '.$_POST['modelnummer']);
    $check = db()->prepare('SELECT COUNT(*) FROM tv_modellen WHERE slug=?');
    $check->execute([$slug]);
    if ($check->fetchColumn() > 0) $slug .= '-'.time();
    db()->prepare('INSERT INTO tv_modellen (merk,serie,modelnummer,slug,beschrijving,repareerbaar,taxatie) VALUES (?,?,?,?,?,?,?)')
       ->execute([
           $_POST['merk'],
           $_POST['serie'],
           $_POST['modelnummer'],
           $slug,
           trim($_POST['beschrijving']),
           isset($_POST['repareerbaar']) ? 1 : 0,
           isset($_POST['taxatie'])      ? 1 : 0,
       ]);
    $msg = 'Model "'.$_POST['modelnummer'].'" succesvol toegevoegd.';
}

// Bewerken
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='edit') {
    $id = (int)$_POST['id'];
    db()->prepare(
        'UPDATE tv_modellen SET merk=?, serie=?, modelnummer=?, beschrijving=?, repareerbaar=?, taxatie=? WHERE id=?'
    )->execute([
        $_POST['merk'],
        $_POST['serie'],
        $_POST['modelnummer'],
        trim($_POST['beschrijving']),
        isset($_POST['repareerbaar']) ? 1 : 0,
        isset($_POST['taxatie'])      ? 1 : 0,
        $id,
    ]);
    $msg = 'Model "'.$_POST['modelnummer'].'" bijgewerkt.';
}

// Verwijderen
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    db()->prepare('UPDATE tv_modellen SET actief=0 WHERE id=?')->execute([$_GET['delete']]);
    $msg = 'Model verwijderd.';
}

// Bewerk-model ophalen
$editModel = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $s = db()->prepare('SELECT * FROM tv_modellen WHERE id=? AND actief=1');
    $s->execute([$_GET['edit']]);
    $editModel = $s->fetch();
}

$repareerbareMerken = ['Samsung','Philips','Sony','LG'];
$taxatieMerken      = ['Samsung','Philips','Sony','LG','Panasonic','Hisense','TCL','Anders'];

$modellen = db()->query('SELECT * FROM tv_modellen WHERE actief=1 ORDER BY merk,serie,modelnummer')->fetchAll();
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
    .cb-row { display:flex; gap:2rem; margin-top:.75rem; margin-bottom:.5rem; }
    .cb-item { display:flex; align-items:flex-start; gap:.5rem; }
    .cb-item input[type="checkbox"] {
      appearance: checkbox !important;
      -webkit-appearance: checkbox !important;
      width: 16px !important;
      height: 16px !important;
      min-width: 16px !important;
      padding: 0 !important;
      margin: 3px 0 0 0 !important;
      border: 1px solid #ccc !important;
      border-radius: 3px !important;
      background: white !important;
      box-shadow: none !important;
      cursor: pointer;
      accent-color: #287864;
      flex-shrink: 0;
    }
    .cb-item label {
      display: flex !important;
      flex-direction: column;
      font-size: .875rem !important;
      font-weight: 600 !important;
      color: #1a1d26 !important;
      cursor: pointer;
      margin: 0 !important;
    }
    .cb-hint { font-size: .72rem; font-weight: 400; color: #9ca3af; margin-top: .15rem; }
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
    <a href="<?= BASE_URL ?>/" target="_blank"><span class="icon">&#127760;</span> Website bekijken</a>
  </div>
  <div class="admin-content">
    <h1>TV Modellen</h1>
    <?php if ($msg): ?><div class="alert alert-success"><?= h($msg) ?></div><?php endif; ?>

    <?php if ($editModel):
      $defaultRep = in_array($editModel['merk'], $repareerbareMerken);
      $defaultTax = in_array($editModel['merk'], $taxatieMerken);
      $checkRep   = ($editModel['repareerbaar'] !== null) ? (bool)$editModel['repareerbaar'] : $defaultRep;
      $checkTax   = ($editModel['taxatie']      !== null) ? (bool)$editModel['taxatie']      : $defaultTax;
    ?>
    <!-- ── BEWERK FORMULIER ── -->
    <div class="admin-card" style="border:2px solid #287864;">
      <h2>&#9998; Bewerken: <?= h($editModel['merk'].' '.$editModel['modelnummer']) ?></h2>
      <form method="POST" class="form-admin">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id"     value="<?= $editModel['id'] ?>">
        <div class="form-row-3">
          <div class="field">
            <label>Merk *</label>
            <select name="merk" required>
              <?php foreach (['Samsung','Philips','Sony','LG','Panasonic','Hisense','TCL','Anders'] as $m): ?>
              <option value="<?= $m ?>" <?= $editModel['merk']===$m ? 'selected' : '' ?>><?= $m ?></option>
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
          <textarea name="beschrijving" rows="4"><?= h($editModel['beschrijving']) ?></textarea>
        </div>

        <div class="cb-row">
          <div class="cb-item">
            <input type="checkbox" id="cb_rep" name="repareerbaar" value="1" <?= $checkRep ? 'checked' : '' ?>>
            <label for="cb_rep">
              Repareerbaar
              <span class="cb-hint">Standaard <?= $defaultRep ? '✓ aan' : '✗ uit' ?> voor <?= h($editModel['merk']) ?></span>
            </label>
          </div>
          <div class="cb-item">
            <input type="checkbox" id="cb_tax" name="taxatie" value="1" <?= $checkTax ? 'checked' : '' ?>>
            <label for="cb_tax">
              Taxatie mogelijk
              <span class="cb-hint">Standaard <?= $defaultTax ? '✓ aan' : '✗ uit' ?> voor <?= h($editModel['merk']) ?></span>
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
      <h2>Nieuw model toevoegen</h2>
      <form method="POST" class="form-admin">
        <input type="hidden" name="action" value="add">
        <div class="form-row-3">
          <div class="field">
            <label>Merk *</label>
            <select name="merk" required>
              <?php foreach (['Samsung','Philips','Sony','LG','Panasonic','Hisense','TCL','Anders'] as $m): ?>
              <option value="<?= $m ?>"><?= $m ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field"><label>Serie *</label><input type="text" name="serie" placeholder="Crystal UHD" required></div>
          <div class="field"><label>Modelnummer *</label><input type="text" name="modelnummer" placeholder="UE55CU8000" required></div>
        </div>
        <div class="field"><label>Beschrijving</label><textarea name="beschrijving" placeholder="Korte omschrijving van het model..."></textarea></div>

        <div class="cb-row">
          <div class="cb-item">
            <input type="checkbox" id="add_rep" name="repareerbaar" value="1">
            <label for="add_rep">Repareerbaar</label>
          </div>
          <div class="cb-item">
            <input type="checkbox" id="add_tax" name="taxatie" value="1" checked>
            <label for="add_tax">Taxatie mogelijk</label>
          </div>
        </div>

        <button type="submit" class="btn btn-primary-sm" style="margin-top:.75rem;">&#43; Model toevoegen</button>
      </form>
    </div>
    <?php endif; ?>

    <!-- ── OVERZICHT ── -->
    <div class="admin-card">
      <h2><?= count($modellen) ?> modellen in de database</h2>
      <table class="admin-table">
        <thead>
          <tr>
            <th>Merk</th>
            <th>Serie</th>
            <th>Modelnummer</th>
            <th>Acties</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($modellen as $m): ?>
        <tr <?= (isset($_GET['edit']) && (int)$_GET['edit'] === $m['id']) ? 'style="background:#f0faf7;"' : '' ?>>
          <td><?= h($m['merk']) ?></td>
          <td><?= h($m['serie']) ?></td>
          <td><strong><?= h($m['modelnummer']) ?></strong></td>
          <td style="display:flex;gap:.5rem;flex-wrap:wrap;">
            <a href="?edit=<?= $m['id'] ?>" class="btn btn-sm btn-secondary">&#9998; Bewerken</a>
            <a href="<?= BASE_URL ?>/tv/<?= h($m['slug']) ?>" target="_blank" class="btn btn-sm btn-secondary">Bekijk</a>
            <a href="<?= BASE_URL ?>/admin/klachten.php?model_id=<?= $m['id'] ?>" class="btn btn-sm btn-secondary">Klachten</a>
            <a href="?delete=<?= $m['id'] ?>" class="btn btn-sm btn-danger"
               onclick="return confirm('Model <?= h($m['modelnummer']) ?> verwijderen?')">Verwijder</a>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>
</div>
</body>
</html>