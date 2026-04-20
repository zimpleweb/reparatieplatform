<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$totalAanvragen  = db()->query('SELECT COUNT(*) FROM aanvragen')->fetchColumn();
$totalModellen   = db()->query('SELECT COUNT(*) FROM tv_modellen WHERE actief=1')->fetchColumn();
$totalKlachten   = db()->query('SELECT COUNT(*) FROM klachten')->fetchColumn();

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

$adminUsername = $_SESSION['admin_username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Dashboard &ndash; Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Epilogue:wght@800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/base.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/components.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
  <meta name="robots" content="noindex,nofollow">
</head>
<body>
<div class="admin-wrap">

<nav class="admin-nav">
  <span class="logo">Reparatie<span>Platform</span></span>
  <div class="admin-nav-actions">
    <a href="<?= BASE_URL ?>/admin/account-instellingen.php" title="Account instellingen">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
      <?= htmlspecialchars($adminUsername) ?>
    </a>
    <div class="admin-nav-divider"></div>
    <a href="<?= BASE_URL ?>/admin/logout.php" class="nav-logout">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16,17 21,12 16,7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Uitloggen
    </a>
  </div>
</nav>

<div class="admin-layout">
  <div class="admin-sidebar">
    <span class="admin-sidebar-label">Beheer</span>
    <div class="admin-sidebar-section">
      <a href="<?= BASE_URL ?>/admin/dashboard.php" class="active"><span class="icon">&#128202;</span> Dashboard</a>
      <a href="<?= BASE_URL ?>/admin/aanvragen.php"><span class="icon">&#128236;</span> Inzendingen</a>
      <a href="<?= BASE_URL ?>/admin/meldingen.php"><span class="icon">&#128276;</span> Meldingen</a>
      <a href="<?= BASE_URL ?>/admin/modellen.php"><span class="icon">&#128250;</span> TV Modellen</a>
      <a href="<?= BASE_URL ?>/admin/klachten.php"><span class="icon">&#9888;&#65039;</span> Klachten</a>
    </div>
    <div class="sidebar-divider"></div>
    <span class="admin-sidebar-label">Instellingen</span>
    <div class="admin-sidebar-section">
      <a href="<?= BASE_URL ?>/admin/advies-instellingen.php"><span class="icon">&#9881;&#65039;</span> Adviesregels</a>
      <a href="<?= BASE_URL ?>/admin/mailtemplates.php"><span class="icon">&#128140;</span> Mailtemplates</a>
      <a href="<?= BASE_URL ?>/admin/admins.php"><span class="icon">&#128100;</span> Admin accounts</a>
      <a href="<?= BASE_URL ?>/admin/account-instellingen.php"><span class="icon">&#128274;</span> Mijn account</a>
    </div>
    <div class="sidebar-divider"></div>
    <div class="admin-sidebar-section">
      <a href="<?= BASE_URL ?>/" target="_blank"><span class="icon">&#127760;</span> Website bekijken</a>
    </div>
  </div>

  <div class="admin-content">
    <h1>Dashboard</h1>

    <div class="stat-grid">
      <div class="stat-card">
        <div class="stat-icon">&#128236;</div>
        <div class="stat-val"><?= $totalAanvragen ?></div>
        <div class="stat-label">Aanvragen totaal</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">&#128250;</div>
        <div class="stat-val"><?= $totalModellen ?></div>
        <div class="stat-label">Actieve TV-modellen</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">&#9888;&#65039;</div>
        <div class="stat-val"><?= $totalKlachten ?></div>
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
  </div>
</div>
</div>
</body>
</html>