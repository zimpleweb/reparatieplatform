<?php
session_start();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('Ongeldig beveiligingstoken.');
    }
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $tv_model_id  = (int)($_POST['tv_model_id'] ?? 0);
        $titel        = trim($_POST['titel'] ?? '');
        $omschrijving = trim($_POST['omschrijving'] ?? '');
        $frequentie   = $_POST['frequentie'] ?? 'middel';
        $type_icon    = trim($_POST['type_icon'] ?? '🔧');
        if ($tv_model_id && $titel && $omschrijving) {
            db()->prepare('INSERT INTO klachten (tv_model_id,titel,omschrijving,frequentie,type_icon) VALUES (?,?,?,?,?)')
               ->execute([$tv_model_id,$titel,$omschrijving,$frequentie,$type_icon]);
            $msg = 'Klacht toegevoegd.';
        }
    } elseif ($action === 'delete') {
        db()->prepare('DELETE FROM klachten WHERE id=?')->execute([(int)($_POST['id'] ?? 0)]);
        $msg = 'Klacht verwijderd.';
    }
}

$model_id = (int)($_GET['model'] ?? 0);
if ($model_id) {
    $st = db()->prepare('SELECT k.*,m.merk,m.modelnummer FROM klachten k JOIN tv_modellen m ON m.id=k.tv_model_id WHERE k.tv_model_id=? ORDER BY k.frequentie DESC,k.id DESC');
    $st->execute([$model_id]);
    $klachten = $st->fetchAll();
} else {
    $st = db()->query('SELECT k.*,m.merk,m.modelnummer FROM klachten k JOIN tv_modellen m ON m.id=k.tv_model_id ORDER BY k.frequentie DESC,k.id DESC');
    $klachten = $st->fetchAll();
}
$modellen = db()->query('SELECT id,merk,modelnummer FROM tv_modellen WHERE actief=1 ORDER BY merk,modelnummer')->fetchAll();
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
  <?php if ($msg): ?><div class="alert alert-success"><?= h($msg) ?></div><?php endif; ?>

  <div class="admin-card">
    <h2>Klacht toevoegen</h2>
    <form method="POST" class="form-admin">
      <input type="hidden" name="csrf"   value="<?= csrf() ?>">
      <input type="hidden" name="action" value="add">
      <div class="form-row-2">
        <div class="field">
          <label>Model *</label>
          <select name="tv_model_id" required>
            <option value="">Selecteer model</option>
            <?php foreach ($modellen as $m): ?>
            <option value="<?= $m['id'] ?>" <?= $m['id']===$model_id ? 'selected' : '' ?>>
              <?= h($m['merk'] . ' ' . $m['modelnummer']) ?>
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
      <?php if ($model_id):
        $mn = db()->prepare('SELECT merk,modelnummer FROM tv_modellen WHERE id=?');
        $mn->execute([$model_id]);
        $mn = $mn->fetch();
        echo 'Klachten voor ' . h($mn['merk'] . ' ' . $mn['modelnummer']) . ' &mdash; <a href="' . BASE_URL . '/admin/klachten.php" class="link-muted">Alle klachten tonen</a>';
      else:
        echo count($klachten) . ' klachten in database';
      endif; ?>
    </h2>
    <table class="admin-table">
      <thead>
        <tr>
          <th>Model</th>
          <th>Titel</th>
          <th>Frequentie</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($klachten as $k): ?>
      <tr>
        <td style="white-space:nowrap"><?= h($k['merk'] . ' ' . $k['modelnummer']) ?></td>
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
</body>
</html>