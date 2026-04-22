<?php
session_start();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$totalAanvragen = db()->query('SELECT COUNT(*) FROM aanvragen')->fetchColumn();
$totalModellen  = db()->query('SELECT COUNT(*) FROM tv_modellen WHERE actief=1')->fetchColumn();
$totalKlachten  = db()->query('SELECT COUNT(*) FROM klachten')->fetchColumn();

$TOEGESTANE_KOLOMMEN = ['aangemaakt_op', 'created_at', 'bijgewerkt_op', 'updated_at', 'id'];
$datumKolom = 'id';
try {
    $cols = db()->query('SHOW COLUMNS FROM aanvragen')->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('bijgewerkt_op', $cols, true))       $datumKolom = 'bijgewerkt_op';
    elseif (in_array('updated_at', $cols, true))      $datumKolom = 'updated_at';
    elseif (in_array('aangemaakt_op', $cols, true))   $datumKolom = 'aangemaakt_op';
    elseif (in_array('created_at', $cols, true))      $datumKolom = 'created_at';
} catch (Exception $e) { $datumKolom = 'id'; }
if (!in_array($datumKolom, $TOEGESTANE_KOLOMMEN, true)) $datumKolom = 'id';

$recentAanvragen = db()->query(
    'SELECT * FROM aanvragen ORDER BY ' . $datumKolom . ' DESC LIMIT 16'
)->fetchAll();

$adminActivePage = 'dashboard';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Dashboard &ndash; Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Epilogue:wght@700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/base.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/components.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
  <meta name="robots" content="noindex,nofollow">
  <style>
    /* Statussen — dashboard-specifiek, niet in admin.css */
    .recent-card-status   { display:inline-flex;align-items:center;gap:.3rem;font-size:.7rem;font-weight:600;padding:.18rem .6rem;border-radius:999px;white-space:nowrap; }
    .status-nieuw         { background:#eff6ff;color:#1d4ed8; }
    .status-behandeld     { background:#f0fdf4;color:#166534; }
    .status-afgewezen     { background:#fef2f2;color:#991b1b; }
    .status-default       { background:#f5f4f1;color:#6b7280; }
    .recent-card-route    { font-size:.7rem;font-weight:700;padding:.18rem .55rem;border-radius:999px;background:#f1f5f9;color:#475569;white-space:nowrap;max-width:120px;overflow:hidden;text-overflow:ellipsis; }
    .load-more-wrap       { text-align:center;margin-top:1.5rem; }
    .btn-load-more        { display:inline-flex;align-items:center;gap:.5rem;padding:.55rem 1.4rem;border-radius:8px;background:var(--adm-ink);color:#fff;font-size:.825rem;font-weight:600;font-family:var(--adm-font);border:none;cursor:pointer;transition:background .15s; }
    .btn-load-more:hover  { background:var(--adm-accent); }
    .btn-load-more:disabled { opacity:.5;cursor:default; }
    .hidden-cards         { display:none; }
  </style>
</head>
<body>

<?php require_once __DIR__ . '/includes/admin-header.php'; ?>

<div class="adm-page">
  <h1 class="adm-page-title">Dashboard</h1>

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

  <!-- Recente aanvragen grid -->
  <div class="admin-card" style="margin-top:2rem;">
    <div class="page-header-row" style="margin-bottom:0;">
      <h2 style="margin:0;">Recente aanvragen</h2>
      <a href="<?= BASE_URL ?>/admin/aanvragen.php" class="link-accent" style="font-size:.82rem;font-weight:600;">
        Alle aanvragen bekijken &rarr;
      </a>
    </div>

    <?php if (empty($recentAanvragen)): ?>
      <p style="color:var(--adm-faint);text-align:center;padding:3rem 1rem;">Nog geen aanvragen.</p>
    <?php else: ?>

      <div class="recent-grid" id="recentGrid">
        <?php
        foreach ($recentAanvragen as $i => $a):
          $merk   = htmlspecialchars($a['merk']               ?? '', ENT_QUOTES);
          $email  = htmlspecialchars($a['email']              ?? '', ENT_QUOTES);
          $model  = htmlspecialchars($a['modelnummer']        ?? $a['model'] ?? '', ENT_QUOTES);
          $route  = htmlspecialchars($a['geadviseerde_route'] ?? $a['route'] ?? '', ENT_QUOTES);
          $datum  = htmlspecialchars($a[$datumKolom]          ?? '', ENT_QUOTES);
          $status = strtolower(trim($a['status'] ?? 'nieuw'));

          $datumFormatted = $datum;
          if ($datum && strtotime($datum)) {
            $datumFormatted = date('d-m-Y H:i', strtotime($datum));
          }

          $statusClass = 'status-default';
          if (str_contains($status, 'nieuw') || str_contains($status, 'new'))         $statusClass = 'status-nieuw';
          elseif (str_contains($status, 'behandeld') || str_contains($status, 'ok'))  $statusClass = 'status-behandeld';
          elseif (str_contains($status, 'afgewezen') || str_contains($status, 'rej')) $statusClass = 'status-afgewezen';

          $hidden = $i >= 8 ? ' hidden-cards' : '';
        ?>
        <a href="<?= BASE_URL ?>/admin/aanvragen.php?id=<?= (int)($a['id'] ?? 0) ?>"
           class="recent-card<?= $hidden ?>"
           data-index="<?= $i ?>">
          <div class="recent-card-merk"><?= $merk ?: '—' ?></div>
          <div class="recent-card-model"><?= $model ?: '<span style="color:var(--adm-faint)">Onbekend model</span>' ?></div>
          <div class="recent-card-email"><?= $email ?: '—' ?></div>
          <div class="recent-card-meta">
            <span class="recent-card-datum"><?= $datumFormatted ?></span>
            <span class="recent-card-status <?= $statusClass ?>"><?= htmlspecialchars(ucfirst($status)) ?></span>
          </div>
          <?php if ($route): ?>
          <div style="margin-top:.2rem;">
            <span class="recent-card-route"><?= $route ?></span>
          </div>
          <?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>

      <?php if (count($recentAanvragen) > 8): ?>
      <div class="load-more-wrap">
        <button class="btn-load-more" id="btnLoadMore" onclick="loadMoreCards()">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6,9 12,15 18,9"/></svg>
          Meer laden
        </button>
      </div>
      <?php endif; ?>

    <?php endif; ?>
  </div>

</div><!-- /.adm-page -->

<script>
function loadMoreCards() {
  var hidden = document.querySelectorAll('.recent-card.hidden-cards');
  var shown = 0;
  hidden.forEach(function(card) {
    if (shown < 8) {
      card.classList.remove('hidden-cards');
      shown++;
    }
  });
  var stillHidden = document.querySelectorAll('.recent-card.hidden-cards');
  if (stillHidden.length === 0) {
    var btn = document.getElementById('btnLoadMore');
    if (btn) btn.style.display = 'none';
  }
}
</script>
</body>
</html>