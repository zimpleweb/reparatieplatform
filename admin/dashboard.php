<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$totalAanvragen  = db()->query('SELECT COUNT(*) FROM aanvragen')->fetchColumn();
$totalModellen   = db()->query('SELECT COUNT(*) FROM tv_modellen WHERE actief=1')->fetchColumn();
$totalKlachten   = db()->query('SELECT COUNT(*) FROM klachten')->fetchColumn();
$recentAanvragen = db()->query('SELECT * FROM aanvragen ORDER BY aangemaakt_op DESC LIMIT 5')->fetchAll();
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
    <a href="<?= BASE_URL ?>/admin/advies-instellingen.php"><span class="icon">&#9881;</span> Advies instellingen</a>
    <a href="<?= BASE_URL ?>/" target="_blank"><span class="icon">&#127760;</span> Website bekijken</a>
  </div>
  <div class="admin-content">
    <h1>Dashboard</h1>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:2rem;">
      <div class="admin-card" style="text-align:center;">
        <div style="font-size:2rem;font-weight:800;color:#287864;"><?= $totalAanvragen ?></div>
        <div style="font-size:.85rem;color:#6b7280;margin-top:.25rem;">Aanvragen</div>
      </div>
      <div class="admin-card" style="text-align:center;">
        <div style="font-size:2rem;font-weight:800;color:#1d4ed8;"><?= $totalModellen ?></div>
        <div style="font-size:.85rem;color:#6b7280;margin-top:.25rem;">TV Modellen</div>
      </div>
      <div class="admin-card" style="text-align:center;">
        <div style="font-size:2rem;font-weight:800;color:#d97706;"><?= $totalKlachten ?></div>
        <div style="font-size:.85rem;color:#6b7280;margin-top:.25rem;">Klachten</div>
      </div>
    </div>
    <div class="admin-card">
      <h2>Recente aanvragen</h2>
      <table class="admin-table">
        <thead><tr><th>E-mail</th><th>Merk</th><th>Model</th><th>Route</th><th>Datum</th></tr></thead>
        <tbody>
        <?php foreach ($recentAanvragen as $a): ?>
        <tr>
          <td><?= h($a['email']) ?></td>
          <td><?= h($a['merk'] ?? '') ?></td>
          <td><?= h($a['modelnummer'] ?? '') ?></td>
          <td><?= h($a['geadviseerde_route'] ?? '') ?></td>
          <td><?= h($a['aangemaakt_op']) ?></td>
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
