<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$msg = '';

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['id'])) {
    db()->prepare('UPDATE aanvragen SET status=?, advies_type=? WHERE id=?')
       ->execute([$_POST['status'], $_POST['advies_type'] ?: null, $_POST['id']]);
    $msg = 'Aanvraag bijgewerkt.';
}

$detail = null;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $detail = db()->prepare('SELECT * FROM aanvragen WHERE id=?');
    $detail->execute([$_GET['id']]);
    $detail = $detail->fetch();
}

$aanvragen = db()->query('SELECT * FROM aanvragen ORDER BY created_at DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8"><title>Aanvragen &ndash; Admin</title>
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
    <a href="<?= BASE_URL ?>/admin/aanvragen.php" class="active"><span class="icon">&#128236;</span> Aanvragen</a>
    <a href="<?= BASE_URL ?>/admin/modellen.php"><span class="icon">&#128250;</span> TV Modellen</a>
    <a href="<?= BASE_URL ?>/admin/klachten.php"><span class="icon">&#9888;</span> Klachten</a>
    <a href="<?= BASE_URL ?>/" target="_blank"><span class="icon">&#127760;</span> Website bekijken</a>
  </div>
  <div class="admin-content">
    <h1>Aanvragen</h1>
    <?php if ($msg): ?><div class="alert alert-success"><?= h($msg) ?></div><?php endif; ?>

    <?php if ($detail): ?>
    <div class="admin-card" style="border:2px solid var(--accent);">
      <h2>Aanvraag #<?= $detail['id'] ?> &mdash; <?= h($detail['merk'].' '.$detail['modelnummer']) ?></h2>
      <table class="specs-table" style="margin-bottom:1.5rem;">
        <tr><td>Datum</td><td><?= date('d-m-Y H:i', strtotime($detail['created_at'])) ?></td></tr>
        <tr><td>E-mail</td><td><a href="mailto:<?= h($detail['email']) ?>"><?= h($detail['email']) ?></a></td></tr>
        <tr><td>TV</td><td><?= h($detail['merk'].' '.$detail['modelnummer']) ?></td></tr>
        <tr><td>Aanschafjaar</td><td><?= h($detail['aanschafjaar']) ?></td></tr>
        <tr><td>Type klacht</td><td><?= h($detail['klacht_type']) ?></td></tr>
        <tr><td>Omschrijving</td><td><?= h($detail['omschrijving']) ?></td></tr>
        <tr><td>IP</td><td><?= h($detail['ip']) ?></td></tr>
      </table>
      <form method="POST" style="display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap;">
        <input type="hidden" name="id" value="<?= $detail['id'] ?>">
        <div class="field" style="margin:0;">
          <label>Status</label>
          <select name="status">
            <option value="nieuw"     <?= $detail['status']==='nieuw'     ?'selected':'' ?>>Nieuw</option>
            <option value="behandeld" <?= $detail['status']==='behandeld' ?'selected':'' ?>>Behandeld</option>
            <option value="archief"   <?= $detail['status']==='archief'   ?'selected':'' ?>>Archief</option>
          </select>
        </div>
        <div class="field" style="margin:0;">
          <label>Adviestype</label>
          <select name="advies_type">
            <option value="">Nog niet bepaald</option>
            <option value="garantie"  <?= $detail['advies_type']==='garantie'  ?'selected':'' ?>>Garantie</option>
            <option value="coulance"  <?= $detail['advies_type']==='coulance'  ?'selected':'' ?>>Coulance</option>
            <option value="reparatie" <?= $detail['advies_type']==='reparatie' ?'selected':'' ?>>Reparatie</option>
            <option value="taxatie"   <?= $detail['advies_type']==='taxatie'   ?'selected':'' ?>>Taxatie</option>
            <option value="geen"      <?= $detail['advies_type']==='geen'      ?'selected':'' ?>>Geen advies mogelijk</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary-sm">Opslaan</button>
        <a href="<?= BASE_URL ?>/admin/aanvragen.php" class="btn btn-secondary">Sluiten</a>
      </form>
    </div>
    <?php endif; ?>

    <div class="admin-card">
      <h2>Alle aanvragen (<?= count($aanvragen) ?>)</h2>
      <?php if (empty($aanvragen)): ?>
        <p style="color:var(--muted);font-size:.875rem;">Nog geen aanvragen ontvangen.</p>
      <?php else: ?>
      <table class="admin-table">
        <thead><tr><th>Datum</th><th>TV</th><th>E-mail</th><th>Klacht</th><th>Status</th><th>Adviestype</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($aanvragen as $r): ?>
        <tr>
          <td style="white-space:nowrap;font-size:.8rem;"><?= date('d-m-Y H:i', strtotime($r['created_at'])) ?></td>
          <td><strong><?= h($r['merk'].' '.$r['modelnummer']) ?></strong></td>
          <td style="font-size:.82rem;"><?= h($r['email']) ?></td>
          <td style="font-size:.82rem;"><?= h($r['klacht_type']) ?></td>
          <td>
            <span class="badge badge-<?= $r['status']==='nieuw'?'red':($r['status']==='behandeld'?'green':'gray') ?>">
              <?= h($r['status']) ?>
            </span>
          </td>
          <td><?= $r['advies_type'] ? '<span class="badge badge-blue">'.h($r['advies_type']).'</span>' : '<span style="color:var(--muted);font-size:.8rem;">—</span>' ?></td>
          <td><a href="?id=<?= $r['id'] ?>" class="btn btn-sm btn-secondary">Behandel</a></td>
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