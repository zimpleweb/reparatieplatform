<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$stats = [
    'modellen'  => db()->query('SELECT COUNT(*) FROM tv_modellen WHERE actief=1')->fetchColumn(),
    'aanvragen' => db()->query('SELECT COUNT(*) FROM aanvragen')->fetchColumn(),
    'nieuw'     => db()->query('SELECT COUNT(*) FROM aanvragen WHERE status="nieuw"')->fetchColumn(),
    'behandeld' => db()->query('SELECT COUNT(*) FROM aanvragen WHERE status="behandeld"')->fetchColumn(),
];
$recent = db()->query('SELECT * FROM aanvragen ORDER BY created_at DESC LIMIT 10')->fetchAll();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8"><title>Dashboard &ndash; Admin</title>
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
    <a href="<?= BASE_URL ?>/admin/dashboard.php" class="active"><span class="icon">&#128202;</span> Dashboard</a>
    <a href="<?= BASE_URL ?>/admin/aanvragen.php"><span class="icon">&#128236;</span> Aanvragen</a>
    <a href="<?= BASE_URL ?>/admin/modellen.php"><span class="icon">&#128250;</span> TV Modellen</a>
    <a href="<?= BASE_URL ?>/admin/klachten.php"><span class="icon">&#9888;</span> Klachten</a>
    <a href="<?= BASE_URL ?>/" target="_blank"><span class="icon">&#127760;</span> Website bekijken</a>
  </div>
  <div class="admin-content">
    <h1>Dashboard</h1>
    <div class="stat-grid">
      <div class="stat-card">
        <div class="stat-val"><?= $stats['modellen'] ?></div>
        <div class="stat-label">TV Modellen</div>
      </div>
      <div class="stat-card">
        <div class="stat-val"><?= $stats['aanvragen'] ?></div>
        <div class="stat-label">Totaal aanvragen</div>
      </div>
      <div class="stat-card">
        <div class="stat-val" style="color:#b91c1c"><?= $stats['nieuw'] ?></div>
        <div class="stat-label">Nieuw / onbehandeld</div>
      </div>
      <div class="stat-card">
        <div class="stat-val" style="color:#166534"><?= $stats['behandeld'] ?></div>
        <div class="stat-label">Behandeld</div>
      </div>
    </div>

    <div class="admin-card">
      <h2>Recente aanvragen</h2>
      <?php if (empty($recent)): ?>
        <p style="color:var(--muted);font-size:.875rem;">Nog geen aanvragen ontvangen.</p>
      <?php else: ?>
      <table class="admin-table">
        <thead>
          <tr>
            <th>Datum</th>
            <th>TV</th>
            <th>E-mail</th>
            <th>Klacht</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($recent as $r): ?>
        <tr>
          <td style="white-space:nowrap"><?= date('d-m-Y H:i', strtotime($r['created_at'])) ?></td>
          <td><strong><?= h($r['merk'] . ' ' . $r['modelnummer']) ?></strong></td>
          <td><?= h($r['email']) ?></td>
          <td><?= h($r['klacht_type']) ?></td>
          <td>
            <span class="badge badge-<?= $r['status']==='nieuw' ? 'red' : ($r['status']==='behandeld' ? 'green' : 'gray') ?>">
              <?= h($r['status']) ?>
            </span>
          </td>
          <td>
            <a href="<?= BASE_URL ?>/admin/aanvragen.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-secondary">Bekijk</a>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
</div>
</div>
</body>
</html>