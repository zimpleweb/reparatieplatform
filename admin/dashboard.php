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

// Paginering voor recente aanvragen grid
$perPage = 8; // 4 kolommen × 2 rijen initieel, meer laden via AJAX
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

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
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/base.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/components.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
  <meta name="robots" content="noindex,nofollow">
  <style>
    /* ── Recente aanvragen grid ── */
    .recent-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 1rem;
      margin-top: 1.25rem;
    }
    .recent-card {
      background: #fff;
      border: 1px solid rgba(0,0,0,.08);
      border-radius: 12px;
      padding: 1rem 1.1rem;
      display: flex;
      flex-direction: column;
      gap: .35rem;
      transition: box-shadow .15s, transform .15s;
      text-decoration: none;
      color: inherit;
    }
    .recent-card:hover {
      box-shadow: 0 4px 18px rgba(0,0,0,.10);
      transform: translateY(-2px);
    }
    .recent-card-merk {
      font-size: .72rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .06em;
      color: #4f98a3;
    }
    .recent-card-model {
      font-size: .875rem;
      font-weight: 700;
      color: #0f172a;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .recent-card-email {
      font-size: .775rem;
      color: #6b7280;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .recent-card-meta {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-top: .3rem;
      gap: .4rem;
      flex-wrap: wrap;
    }
    .recent-card-datum {
      font-size: .72rem;
      color: #9ca3af;
    }
    .recent-card-route {
      font-size: .7rem;
      font-weight: 600;
      padding: .18rem .55rem;
      border-radius: 999px;
      background: #f1f5f9;
      color: #475569;
      white-space: nowrap;
      max-width: 120px;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .recent-card-status {
      display: inline-flex;
      align-items: center;
      gap: .3rem;
      font-size: .7rem;
      font-weight: 600;
      padding: .18rem .6rem;
      border-radius: 999px;
      white-space: nowrap;
    }
    .status-nieuw     { background: #eff6ff; color: #1d4ed8; }
    .status-behandeld { background: #f0fdf4; color: #166534; }
    .status-afgewezen { background: #fef2f2; color: #991b1b; }
    .status-default   { background: #f5f4f1; color: #6b7280; }

    .load-more-wrap {
      text-align: center;
      margin-top: 1.5rem;
    }
    .btn-load-more {
      display: inline-flex;
      align-items: center;
      gap: .5rem;
      padding: .55rem 1.4rem;
      border-radius: 8px;
      background: #0f172a;
      color: #fff;
      font-size: .825rem;
      font-weight: 600;
      font-family: 'Inter', sans-serif;
      border: none;
      cursor: pointer;
      transition: background .15s;
    }
    .btn-load-more:hover { background: #4f98a3; }
    .btn-load-more:disabled { opacity: .5; cursor: default; }

    .hidden-cards { display: none; }

    @media (max-width: 1100px) {
      .recent-grid { grid-template-columns: repeat(3, 1fr); }
    }
    @media (max-width: 768px) {
      .recent-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 480px) {
      .recent-grid { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

<?php require_once __DIR__ . '/includes/admin-header.php'; ?>

<div class="adm-page">
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

  <!-- Recente aanvragen grid -->
  <div class="admin-card" style="margin-top:2rem;">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem;">
      <h2 style="margin:0;">Recente aanvragen</h2>
      <a href="<?= BASE_URL ?>/admin/aanvragen.php" style="font-size:.82rem;color:#4f98a3;font-weight:600;text-decoration:none;">
        Alle aanvragen bekijken &rarr;
      </a>
    </div>

    <?php if (empty($recentAanvragen)): ?>
      <p style="color:#9ca3af;text-align:center;padding:3rem 1rem;">Nog geen aanvragen.</p>
    <?php else: ?>

      <div class="recent-grid" id="recentGrid">
        <?php
        // Toon eerste 8 direct zichtbaar, rest verborgen
        foreach ($recentAanvragen as $i => $a):
          $merk   = htmlspecialchars($a['merk']               ?? '', ENT_QUOTES);
          $email  = htmlspecialchars($a['email']              ?? '', ENT_QUOTES);
          $model  = htmlspecialchars($a['modelnummer']        ?? $a['model'] ?? '', ENT_QUOTES);
          $route  = htmlspecialchars($a['geadviseerde_route'] ?? $a['route'] ?? '', ENT_QUOTES);
          $datum  = htmlspecialchars($a[$datumKolom]          ?? '', ENT_QUOTES);
          $status = strtolower(trim($a['status'] ?? 'nieuw'));

          // Datum formatteren
          $datumFormatted = $datum;
          if ($datum && strtotime($datum)) {
            $datumFormatted = date('d-m-Y H:i', strtotime($datum));
          }

          // Status kleur
          $statusClass = 'status-default';
          if (str_contains($status, 'nieuw') || str_contains($status, 'new'))        $statusClass = 'status-nieuw';
          elseif (str_contains($status, 'behandeld') || str_contains($status, 'ok')) $statusClass = 'status-behandeld';
          elseif (str_contains($status, 'afgewezen') || str_contains($status, 'rej'))$statusClass = 'status-afgewezen';

          $hidden = $i >= 8 ? ' hidden-cards' : '';
        ?>
        <a href="<?= BASE_URL ?>/admin/aanvragen.php?id=<?= (int)($a['id'] ?? 0) ?>"
           class="recent-card<?= $hidden ?>"
           data-index="<?= $i ?>">
          <div class="recent-card-merk"><?= $merk ?: '—' ?></div>
          <div class="recent-card-model"><?= $model ?: '<span style="color:#9ca3af">Onbekend model</span>' ?></div>
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
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6,9 12,15 18,9"/></svg>
          Meer laden
        </button>
      </div>
      <?php endif; ?>

    <?php endif; ?>
  </div>

</div><!-- /.adm-page -->

<script>
function loadMoreCards() {
  const hidden = document.querySelectorAll('.recent-card.hidden-cards');
  let shown = 0;
  hidden.forEach(function(card) {
    if (shown < 8) {
      card.classList.remove('hidden-cards');
      shown++;
    }
  });
  // Verberg knop als er niets meer verborgen is
  const stillHidden = document.querySelectorAll('.recent-card.hidden-cards');
  if (stillHidden.length === 0) {
    const btn = document.getElementById('btnLoadMore');
    if (btn) btn.style.display = 'none';
  }
}
</script>
</body>
</html>