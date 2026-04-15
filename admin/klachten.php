<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$msg      = '';
$model_id = (int)($_GET['model_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='add') {
    db()->prepare('INSERT INTO klachten (tv_model_id,titel,omschrijving,frequentie,type_icon) VALUES (?,?,?,?,?)')
       ->execute([$_POST['tv_model_id'],$_POST['titel'],$_POST['omschrijving'],$_POST['frequentie'],$_POST['type_icon']]);
    $msg = 'Klacht toegevoegd.';
    $model_id = (int)$_POST['tv_model_id'];
}
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    db()->prepare('DELETE FROM klachten WHERE id=?')->execute([$_GET['delete']]);
    $msg = 'Klacht verwijderd.';
}

$modellen = db()->query('SELECT id,merk,modelnummer FROM tv_modellen WHERE actief=1 ORDER BY merk,modelnummer')->fetchAll();
$klachten = $model_id
    ? db()->prepare('SELECT k.*,t.merk,t.modelnummer FROM klachten k JOIN tv_modellen t ON t.id=k.tv_model_id WHERE k.tv_model_id=? ORDER BY FIELD(k.frequentie,"hoog","middel","laag")')
    : db()->prepare('SELECT k.*,t.merk,t.modelnummer FROM klachten k JOIN tv_modellen t ON t.id=k.tv_model_id ORDER BY t.merk,t.modelnummer,FIELD(k.frequentie,"hoog","middel","laag")');
if ($model_id) $klachten->execute([$model_id]); else $klachten->execute();
$klachten = $klachten->fetchAll();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8"><title>Klachten &ndash; Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Epilogue:wght@800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/base.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/components.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
  <meta name="robots" content="noindex,nofollow">
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
    <a href="<?= BASE_URL ?>/admin/klachten.php" class="active"><span class="icon">&#9888;</span> Klachten</a>
    <a href="<?= BASE_URL ?>/" target="_blank"><span class="icon">&#127760;</span> Website bekijken</a>
  </div>
  <div class="admin-content">
    <h1>Klachten beheren</h1>
    <?php if ($msg): ?><div class="alert alert-success"><?= h($msg) ?></div><?php endif; ?>

    <div class="admin-card">
      <h2>Klacht toevoegen</h2>
      <form method="POST" class="form-admin">
        <input type="hidden" name="action" value="add">
        <div class="form-row-2">
          <div class="field">
            <label>Model *</label>
            <select name="tv_model_id" required>
              <option value="">Selecteer model</option>
              <?php foreach ($modellen as $m): ?>
              <option value="<?= $m['id'] ?>" <?= $m['id']===$model_id?'selected':'' ?>>
                <?= h($m['merk'].' '.$m['modelnummer']) ?>
              </option>
              <?php endforeach; ?>
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
        <div class="form-row-2">
          <div class="field"><label>Titel *</label><input type="text" name="titel" placeholder="Bijv. Donkere vlekken — LED strip uitval" required></div>
          <div class="field"><label>Icoon (emoji)</label><input type="text" name="type_icon" value="🔧" maxlength="5"></div>
        </div>
        <div class="field"><label>Omschrijving *</label><textarea name="omschrijving" placeholder="Uitgebreide uitleg van de klacht en mogelijke oplossing..." required style="min-height:100px;"></textarea></div>
        <button type="submit" class="btn btn-primary-sm">&#43; Klacht toevoegen</button>
      </form>
    </div>

    <div class="admin-card">
      <h2>
        <?php if ($model_id):
          $mn = db()->prepare('SELECT merk,modelnummer FROM tv_modellen WHERE id=?');
          $mn->execute([$model_id]); $mn=$mn->fetch();
          echo 'Klachten voor '.h($mn['merk'].' '.$mn['modelnummer']).' &mdash; <a href="'.BASE_URL.'/admin/klachten.php" style="font-size:.9rem;font-weight:500;color:var(--accent)">Alle klachten tonen</a>';
        else: echo count($klachten).' klachten in database'; endif; ?>
      </h2>
      <table class="admin-table">
        <thead><tr><th>Model</th><th>Titel</th><th>Frequentie</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($klachten as $k): ?>
        <tr>
          <td style="white-space:nowrap"><?= h($k['merk'].' '.$k['modelnummer']) ?></td>
          <td><?= h($k['type_icon']) ?> <?= h($k['titel']) ?></td>
          <td>
            <span class="badge <?= $k['frequentie']==='hoog'?'badge-red':($k['frequentie']==='middel'?'badge-yellow':'badge-green') ?>">
              <?= h($k['frequentie']) ?>
            </span>
          </td>
          <td>
            <a href="?delete=<?= $k['id'] ?>&model_id=<?= $k['tv_model_id'] ?>"
               class="btn btn-sm btn-danger"
               onclick="return confirm('Klacht verwijderen?')">Verwijder</a>
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