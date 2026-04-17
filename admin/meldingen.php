<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$filterType = trim($_GET['type'] ?? '');
$filterDoor = trim($_GET['door'] ?? '');
$filterZoek = trim($_GET['zoek'] ?? '');

// ── Query opbouwen ────────────────────────────────────────────────
$where  = ['1=1'];
$params = [];

if ($filterType === 'bericht')      { $where[] = "l.actie LIKE 'Bericht%'"; }
elseif ($filterType === 'inzending'){ $where[] = "l.actie LIKE 'Inzending%'"; }
elseif ($filterType === 'doorgestuurd') { $where[] = "l.actie LIKE 'Doorgestuurd%'"; }
elseif ($filterType === 'ingediend'){ $where[] = "l.actie LIKE '%ingediend%'"; }
elseif ($filterType === 'status')   {
    $where[] = "l.actie NOT LIKE 'Bericht%'
            AND l.actie NOT LIKE 'Inzending%'
            AND l.actie NOT LIKE 'Doorgestuurd%'
            AND l.actie NOT LIKE '%ingediend%'";
}

if ($filterDoor) { $where[] = 'l.gedaan_door = ?'; $params[] = $filterDoor; }

if ($filterZoek) {
    $where[] = '(a.casenummer LIKE ? OR a.email LIKE ? OR l.actie LIKE ? OR l.opmerking LIKE ?)';
    $like = '%' . $filterZoek . '%';
    $params = array_merge($params, [$like, $like, $like, $like]);
}

$sql = 'SELECT l.*, a.casenummer, a.id AS aanvraag_id_link, a.merk, a.modelnummer, a.email
        FROM aanvragen_log l
        LEFT JOIN aanvragen a ON l.aanvraag_id = a.id
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY l.aangemaakt DESC
        LIMIT 300';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$meldingen = $stmt->fetchAll();

// ── Aantallen per type voor tabs ──────────────────────────────────
$typeCondities = [
    'bericht'      => "actie LIKE 'Bericht%'",
    'inzending'    => "actie LIKE 'Inzending%'",
    'doorgestuurd' => "actie LIKE 'Doorgestuurd%'",
    'ingediend'    => "actie LIKE '%ingediend%'",
];
$aantallen = ['alle' => 0];
try {
    $aantallen['alle'] = (int) db()->query('SELECT COUNT(*) FROM aanvragen_log')->fetchColumn();
    foreach ($typeCondities as $k => $cond) {
        $aantallen[$k] = (int) db()->query("SELECT COUNT(*) FROM aanvragen_log WHERE $cond")->fetchColumn();
    }
    $aantallen['status'] = $aantallen['alle']
        - ($aantallen['bericht'] + $aantallen['inzending'] + $aantallen['doorgestuurd'] + $aantallen['ingediend']);
} catch (\Exception $e) {}

// ── Type-indeling ─────────────────────────────────────────────────
function melding_type(string $actie): string {
    $a = strtolower($actie);
    if (str_starts_with($a, 'bericht'))       return 'bericht';
    if (str_starts_with($a, 'inzending'))     return 'inzending';
    if (str_starts_with($a, 'doorgestuurd'))  return 'doorgestuurd';
    if (str_contains($a, 'ingediend'))        return 'ingediend';
    return 'status';
}
function melding_type_label(string $type): string {
    return match($type) {
        'bericht'      => 'Bericht',
        'inzending'    => 'Inzending',
        'doorgestuurd' => 'Doorgestuurd',
        'ingediend'    => 'Ingediend',
        default        => 'Status',
    };
}
function melding_type_css(string $type): string {
    return match($type) {
        'bericht'      => 'mt-bericht',
        'inzending'    => 'mt-inzending',
        'doorgestuurd' => 'mt-doorgestuurd',
        'ingediend'    => 'mt-ingediend',
        default        => 'mt-status',
    };
}
function door_css(string $door): string {
    return match($door) {
        'klant'  => 'door-klant',
        'admin'  => 'door-admin',
        default  => 'door-system',
    };
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8"><title>Meldingen &ndash; Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Epilogue:wght@800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/base.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/components.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin-meldingen.css?v=<?= filemtime(__DIR__.'/../assets/css/admin-meldingen.css') ?>">
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
    <a href="<?= BASE_URL ?>/admin/aanvragen.php"><span class="icon">&#128236;</span> Inzendingen</a>
    <a href="<?= BASE_URL ?>/admin/meldingen.php" class="active"><span class="icon">&#128276;</span> Meldingen</a>
    <a href="<?= BASE_URL ?>/admin/modellen.php"><span class="icon">&#128250;</span> TV Modellen</a>
    <a href="<?= BASE_URL ?>/admin/klachten.php"><span class="icon">&#9888;</span> Klachten</a>
    <a href="<?= BASE_URL ?>/admin/advies-instellingen.php"><span class="icon">&#9881;</span> Advies instellingen</a>
    <a href="<?= BASE_URL ?>/" target="_blank"><span class="icon">&#127760;</span> Website bekijken</a>
  </div>

  <div class="admin-content">
    <h1>Meldingen</h1>

    <!-- ── Type-tabs ─────────────────────────────────────────── -->
    <div class="m-tabs">
      <?php
      $tabs = [
          ''             => ['label' => 'Alle',               'count' => $aantallen['alle']],
          'bericht'      => ['label' => 'Berichten',          'count' => $aantallen['bericht']      ?? 0],
          'inzending'    => ['label' => 'Inzendingen',        'count' => $aantallen['inzending']    ?? 0],
          'doorgestuurd' => ['label' => 'Doorsturingen',      'count' => $aantallen['doorgestuurd'] ?? 0],
          'ingediend'    => ['label' => 'Aanvragen ingediend','count' => $aantallen['ingediend']    ?? 0],
          'status'       => ['label' => 'Statuswijzigingen',  'count' => $aantallen['status']       ?? 0],
      ];
      foreach ($tabs as $key => $tab):
          $active = ($filterType === $key) ? 'active' : '';
          $url = '?' . http_build_query(array_filter(['type' => $key, 'door' => $filterDoor, 'zoek' => $filterZoek]));
      ?>
        <a href="<?= $url ?>" class="m-tab <?= $active ?>">
          <?= h($tab['label']) ?>
          <span class="m-tab-count"><?= $tab['count'] ?></span>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- ── Filterbar ─────────────────────────────────────────── -->
    <form method="GET" class="m-filterbar">
      <input type="hidden" name="type" value="<?= h($filterType) ?>" />
      <select name="door" onchange="this.form.submit()">
        <option value="">Iedereen</option>
        <option value="klant"  <?= $filterDoor === 'klant'  ? 'selected' : '' ?>>Klant</option>
        <option value="admin"  <?= $filterDoor === 'admin'  ? 'selected' : '' ?>>Admin</option>
        <option value="system" <?= $filterDoor === 'system' ? 'selected' : '' ?>>Systeem</option>
      </select>
      <input type="text" name="zoek" value="<?= h($filterZoek) ?>" placeholder="Zoek op casenummer, e-mail, tekst…" />
      <button type="submit">Zoeken</button>
      <?php if ($filterDoor || $filterZoek): ?>
        <a href="?type=<?= h($filterType) ?>" class="m-reset">✕ Wissen</a>
      <?php endif; ?>
    </form>

    <!-- ── Resultaten ────────────────────────────────────────── -->
    <div class="admin-card" style="padding:0;overflow:hidden;">
      <?php if (empty($meldingen)): ?>
        <p style="padding:2rem;text-align:center;color:#94a3b8;font-size:.9rem;">Geen meldingen gevonden.</p>
      <?php else: ?>
        <table class="m-tabel">
          <thead>
            <tr>
              <th>Datum</th>
              <th>Type</th>
              <th>Casenummer</th>
              <th>Apparaat</th>
              <th>Actie / Bericht</th>
              <th>Door</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($meldingen as $m):
              $type = melding_type($m['actie']);
          ?>
            <tr>
              <td class="m-datum" title="<?= h($m['aangemaakt']) ?>">
                <?= date('d-m-y', strtotime($m['aangemaakt'])) ?><br>
                <span><?= date('H:i', strtotime($m['aangemaakt'])) ?></span>
              </td>
              <td>
                <span class="m-badge <?= melding_type_css($type) ?>"><?= melding_type_label($type) ?></span>
              </td>
              <td>
                <?php if ($m['aanvraag_id_link']): ?>
                  <a href="<?= BASE_URL ?>/admin/aanvragen.php?id=<?= (int)$m['aanvraag_id_link'] ?>" class="m-case">
                    <?= h($m['casenummer'] ?? '—') ?>
                  </a>
                <?php else: ?>
                  <span style="color:#94a3b8;">—</span>
                <?php endif; ?>
              </td>
              <td class="m-apparaat">
                <?= h(trim(($m['merk'] ?? '') . ' ' . ($m['modelnummer'] ?? ''))) ?: '—' ?>
                <?php if (!empty($m['email'])): ?>
                  <br><span><?= h($m['email']) ?></span>
                <?php endif; ?>
              </td>
              <td class="m-tekst">
                <div class="m-actie"><?= h($m['actie']) ?></div>
                <?php if (!empty($m['opmerking'])): ?>
                  <div class="m-opmerking"><?= nl2br(h($m['opmerking'])) ?></div>
                <?php endif; ?>
              </td>
              <td>
                <span class="m-door <?= door_css($m['gedaan_door']) ?>"><?= h($m['gedaan_door']) ?></span>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <div style="padding:.75rem 1.25rem;font-size:.775rem;color:#94a3b8;border-top:1px solid #f1f5f9;">
          <?= count($meldingen) ?> melding<?= count($meldingen) !== 1 ? 'en' : '' ?> getoond (max 300)
        </div>
      <?php endif; ?>
    </div>

  </div><!-- /.admin-content -->
</div><!-- /.admin-layout -->
</div><!-- /.admin-wrap -->
</body>
</html>
