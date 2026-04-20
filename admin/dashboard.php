<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$totalAanvragen = db()->query('SELECT COUNT(*) FROM aanvragen')->fetchColumn();
$totalModellen  = db()->query('SELECT COUNT(*) FROM tv_modellen WHERE actief=1')->fetchColumn();
$totalKlachten  = db()->query('SELECT COUNT(*) FROM klachten')->fetchColumn();

$TOEGESTANE_KOLOMMEN = ['aangemaakt_op', 'created_at', 'id'];
$datumKolom = 'id';
try {
    $cols = db()->query('SHOW COLUMNS FROM aanvragen')->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('aangemaakt_op', $cols, true))  $datumKolom = 'aangemaakt_op';
    elseif (in_array('created_at', $cols, true)) $datumKolom = 'created_at';
} catch (Exception $e) { $datumKolom = 'id'; }
if (!in_array($datumKolom, $TOEGESTANE_KOLOMMEN, true)) $datumKolom = 'id';

$recentAanvragen = db()->query(
    'SELECT * FROM aanvragen ORDER BY ' . $datumKolom . ' DESC LIMIT 5'
)->fetchAll();

$adminActivePage = 'dashboard';

$_adminHeaderPath = __DIR__ . '/includes/admin-header.php';
if (!file_exists($_adminHeaderPath)) {
    die('<p style="font:16px sans-serif;padding:2rem;color:red;">
        ⚠️ <strong>admin/includes/admin-header.php</strong> niet gevonden.<br>
        Maak de map <code>admin/includes/</code> aan en upload het header-component daarin.
    </p>');
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Dashboard &ndash; Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/base.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/components.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
  <meta name="robots" content="noindex,nofollow">
</head>
<body>
<div class="admin-wrap">

<?php require_once $_adminHeaderPath; ?>

<div class="admin-content">
  <h1>Dashboard</h1>

  <div class="stat-grid">
    <div class="stat-card">
      <div class="stat-val"><?= (int)$totalAanvragen ?></div>
      <div class="stat-label">Aanvragen totaal</div>
    </div>
    <div class="stat-card">
      <div class="stat-val"><?= (int)$totalModellen ?></div>
      <div class="stat-label">Actieve TV-modellen</div>
    </div>
    <div class="stat-card">
      <div class="stat-val"><?= (int)$totalKlachten ?></div>
      <div class="stat-label">Klachten</div>
    </div>
  </div>

  <div class="admin-card">
    <h2>Recente aanvragen</h2>
    <table class="admin-table">
      <thead>
        <tr>
          <th>E-mail</th>
          <th>Merk</th>
          <th>Model</th>
          <th>Route</th>
          <th>Datum</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($recentAanvragen as $a): ?>
      <tr>
        <td><?= h($a['email'] ?? '') ?></td>
        <td><?= h($a['merk'] ?? '') ?></td>
        <td><?= h($a['modelnummer'] ?? '') ?></td>
        <td><?= h($a['geadviseerde_route'] ?? '') ?></td>
        <td><?= h($a[$datumKolom] ?? '') ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($recentAanvragen)): ?>
      <tr><td colspan="5" style="text-align:center;color:#9ca3af;padding:2rem;">Nog geen aanvragen.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

</div><!-- /.admin-content -->
</div><!-- /.admin-wrap -->
</body>
</html>