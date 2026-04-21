<?php
session_start();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

// ── SQL migratie ──────────────────────────────────────────────────
try {
    $cols = db()->query('SHOW COLUMNS FROM aanvragen_log')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('gelezen', $cols)) {
        db()->exec('ALTER TABLE aanvragen_log ADD COLUMN gelezen TINYINT(1) NOT NULL DEFAULT 0');
    }
    if (!in_array('gearchiveerd', $cols)) {
        db()->exec('ALTER TABLE aanvragen_log ADD COLUMN gearchiveerd TINYINT(1) NOT NULL DEFAULT 0');
    }
} catch (\Exception $e) {}

// ── Archief-instellingen ──────────────────────────────────────────
$archief_dagen = 21;
try {
    $row = db()->query("SELECT waarde FROM instellingen WHERE sleutel = 'melding_archief_dagen' LIMIT 1")->fetch();
    if ($row) $archief_dagen = max(1, (int)$row['waarde']);
} catch (\Exception $e) {}

// ── Instellingen opslaan ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sla_instellingen'])) {
    $nieuwe_dagen = max(1, (int)($_POST['archief_dagen'] ?? 21));
    try {
        $exists = db()->query("SELECT COUNT(*) FROM instellingen WHERE sleutel = 'melding_archief_dagen'")->fetchColumn();
        if ($exists) {
            db()->prepare("UPDATE instellingen SET waarde=? WHERE sleutel='melding_archief_dagen'")->execute([$nieuwe_dagen]);
        } else {
            db()->prepare("INSERT INTO instellingen (sleutel, waarde) VALUES ('melding_archief_dagen', ?)")->execute([$nieuwe_dagen]);
        }
        $archief_dagen = $nieuwe_dagen;
    } catch (\Exception $e) {}
}

// ── Bulk acties ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_actie']) && !empty($_POST['selectie'])) {
    $ids = array_filter(array_map('intval', (array)$_POST['selectie']));
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $bulk = $_POST['bulk_actie'];
        try {
            if ($bulk === 'gelezen')           db()->prepare("UPDATE aanvragen_log SET gelezen=1 WHERE id IN ($placeholders)")->execute($ids);
            elseif ($bulk === 'ongelezen')     db()->prepare("UPDATE aanvragen_log SET gelezen=0 WHERE id IN ($placeholders)")->execute($ids);
            elseif ($bulk === 'archiveren')    db()->prepare("UPDATE aanvragen_log SET gearchiveerd=1 WHERE id IN ($placeholders)")->execute($ids);
            elseif ($bulk === 'dearchiveren')  db()->prepare("UPDATE aanvragen_log SET gearchiveerd=0 WHERE id IN ($placeholders)")->execute($ids);
        } catch (\Exception $e) {}
    }
    $qs = http_build_query(array_filter([
        'type'    => $_GET['type']    ?? '',
        'door'    => $_GET['door']    ?? '',
        'zoek'    => $_GET['zoek']    ?? '',
        'archief' => $_GET['archief'] ?? '',
    ]));
    header('Location: ' . BASE_URL . '/admin/meldingen.php' . ($qs ? '?'.$qs : ''));
    exit;
}

// ── Markeer melding als gelezen ───────────────────────────────────
if (isset($_GET['lees']) && is_numeric($_GET['lees'])) {
    try { db()->prepare('UPDATE aanvragen_log SET gelezen=1 WHERE id=?')->execute([(int)$_GET['lees']]); } catch (\Exception $e) {}
}

// ── Filterparameters ──────────────────────────────────────────────
$filterType    = trim($_GET['type']    ?? '');
$filterDoor    = trim($_GET['door']    ?? '');
$filterZoek    = trim($_GET['zoek']    ?? '');
$filterArchief = isset($_GET['archief']) && $_GET['archief'] === '1';

// ── Auto-archiveer meldingen ──────────────────────────────────────
try {
    db()->prepare("UPDATE aanvragen_log SET gearchiveerd=1 WHERE gearchiveerd=0 AND aangemaakt < DATE_SUB(NOW(), INTERVAL ? DAY)")->execute([$archief_dagen]);
} catch (\Exception $e) {}

// ── Query opbouwen ────────────────────────────────────────────────
$where  = ['1=1'];
$params = [];
$where[] = $filterArchief ? 'l.gearchiveerd = 1' : 'l.gearchiveerd = 0';

if ($filterType === 'bericht')          $where[] = "l.actie LIKE 'Bericht%'";
elseif ($filterType === 'inzending')    $where[] = "l.actie LIKE 'Inzending%'";
elseif ($filterType === 'doorgestuurd') $where[] = "l.actie LIKE 'Doorgestuurd%'";
elseif ($filterType === 'ingediend')    $where[] = "l.actie LIKE '%ingediend%'";
elseif ($filterType === 'status')       $where[] = "l.actie NOT LIKE 'Bericht%' AND l.actie NOT LIKE 'Inzending%' AND l.actie NOT LIKE 'Doorgestuurd%' AND l.actie NOT LIKE '%ingediend%'";

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
        ORDER BY l.gelezen ASC, l.aangemaakt DESC
        LIMIT 300';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$meldingen = $stmt->fetchAll();

// ── Aantallen voor tabs ───────────────────────────────────────────
$typeCondities = [
    'bericht'      => "actie LIKE 'Bericht%'",
    'inzending'    => "actie LIKE 'Inzending%'",
    'doorgestuurd' => "actie LIKE 'Doorgestuurd%'",
    'ingediend'    => "actie LIKE '%ingediend%'",
];
$aantallen = ['alle' => 0];
try {
    $aantallen['alle'] = (int)db()->query('SELECT COUNT(*) FROM aanvragen_log WHERE gearchiveerd=0')->fetchColumn();
    foreach ($typeCondities as $k => $cond) {
        $aantallen[$k] = (int)db()->query("SELECT COUNT(*) FROM aanvragen_log WHERE gearchiveerd=0 AND $cond")->fetchColumn();
    }
    $aantallen['status'] = $aantallen['alle'] - ($aantallen['bericht'] + $aantallen['inzending'] + $aantallen['doorgestuurd'] + $aantallen['ingediend']);
} catch (\Exception $e) {}

$ongelezen_totaal = 0;
try { $ongelezen_totaal = (int)db()->query('SELECT COUNT(*) FROM aanvragen_log WHERE gelezen=0 AND gearchiveerd=0')->fetchColumn(); } catch (\Exception $e) {}
$archief_totaal = 0;
try { $archief_totaal = (int)db()->query('SELECT COUNT(*) FROM aanvragen_log WHERE gearchiveerd=1')->fetchColumn(); } catch (\Exception $e) {}

// ── Helper functies ───────────────────────────────────────────────
function melding_type(string $actie): string {
    $a = strtolower($actie);
    if (str_starts_with($a, 'bericht'))      return 'bericht';
    if (str_starts_with($a, 'inzending'))    return 'inzending';
    if (str_starts_with($a, 'doorgestuurd')) return 'doorgestuurd';
    if (str_contains($a, 'ingediend'))       return 'ingediend';
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
    return match($door) { 'klant' => 'door-klant', 'admin' => 'door-admin', default => 'door-system' };
}

$adminActivePage = 'meldingen';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Meldingen &ndash; Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Epilogue:wght@700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/base.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/components.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
  <meta name="robots" content="noindex,nofollow">
  <style>
    /* ── Meldingen-specifieke stijlen (klein, geen conflicts) ── */
    .meldingen-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: .75rem;
      margin-bottom: 1.25rem;
    }
    .meldingen-header h1 {
      font-family: var(--adm-font-display);
      font-size: 1.35rem;
      font-weight: 800;
      color: var(--adm-ink);
      margin: 0;
      letter-spacing: -.025em;
    }

    /* Archief instellingen inline form */
    .instellingen-panel .admin-card { margin-bottom: 1rem; }
    .instellingen-panel h4 {
      font-size: .85rem;
      font-weight: 700;
      color: var(--adm-ink);
      margin-bottom: .75rem;
    }
    .inst-form-row {
      display: flex;
      align-items: center;
      gap: .75rem;
      flex-wrap: wrap;
    }
    .inst-form-row label {
      font-size: .875rem;
      color: var(--adm-text);
      display: flex;
      align-items: center;
      gap: .35rem;
      flex-wrap: wrap;
    }
    .inst-dagen-input {
      width: 70px;
      padding: .35rem .5rem;
      border: 1.5px solid var(--adm-border);
      border-radius: 6px;
      font-size: .875rem;
      font-family: var(--adm-font);
      color: var(--adm-ink);
      background: var(--adm-surface);
    }
    .inst-hint {
      font-size: .78rem;
      color: var(--adm-faint);
      margin-top: .4rem;
    }

    /* ── Tabs ── */
    .m-tabs {
      display: flex;
      gap: .25rem;
      flex-wrap: wrap;
      border-bottom: 2px solid var(--adm-border);
      margin-bottom: 1rem;
      padding-bottom: .75rem;
    }
    .m-tab {
      display: inline-flex;
      align-items: center;
      gap: .35rem;
      padding: .35rem .75rem;
      border-radius: 7px;
      font-size: .8rem;
      font-weight: 600;
      color: var(--adm-muted);
      text-decoration: none;
      transition: background .15s, color .15s;
      white-space: nowrap;
    }
    .m-tab:hover          { background: var(--adm-nav-hover); color: var(--adm-ink); }
    .m-tab.active         { background: var(--adm-accent-light); color: var(--adm-accent); }
    .m-tab-archief        { margin-left: auto; }
    .m-tab-count {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 18px;
      height: 18px;
      padding: 0 4px;
      border-radius: 99px;
      background: var(--adm-border);
      color: var(--adm-muted);
      font-size: .68rem;
      font-weight: 700;
    }
    .m-tab.active .m-tab-count { background: rgba(1,105,111,.15); color: var(--adm-accent); }

    /* ── Filterbar ── */
    .m-filterbar {
      display: flex;
      align-items: center;
      gap: .6rem;
      flex-wrap: wrap;
      margin-bottom: 1.25rem;
    }
    .m-filterbar select,
    .m-filterbar input[type=text] {
      padding: .5rem .85rem;
      border: 1.5px solid var(--adm-border);
      border-radius: var(--adm-radius);
      font-size: .85rem;
      font-family: var(--adm-font);
      background: var(--adm-surface);
      color: var(--adm-ink);
      height: 38px;
    }
    .m-filterbar input[type=text] { width: 240px; }
    .m-filterbar button {
      padding: 0 1.1rem;
      height: 38px;
      background: var(--adm-ink);
      color: #fff;
      border: none;
      border-radius: var(--adm-radius);
      font-size: .85rem;
      font-weight: 600;
      font-family: var(--adm-font);
      cursor: pointer;
      transition: background .15s;
    }
    .m-filterbar button:hover { background: #1f2937; }
    .m-reset {
      font-size: .8rem;
      color: var(--adm-muted);
      text-decoration: none;
      padding: .4rem .6rem;
      border-radius: 6px;
      transition: background .15s, color .15s;
    }
    .m-reset:hover { background: #fee2e2; color: #dc2626; }

    /* ── Bulk balk ── */
    .bulk-balk {
      display: flex;
      align-items: center;
      gap: .75rem;
      background: var(--adm-accent-light);
      border: 1px solid rgba(1,105,111,.25);
      border-radius: var(--adm-radius);
      padding: .6rem 1rem;
      margin-bottom: .85rem;
      flex-wrap: wrap;
    }
    .bulk-info {
      font-size: .875rem;
      font-weight: 700;
      color: var(--adm-accent);
    }
    .bulk-balk select {
      padding: .4rem .75rem;
      border: 1.5px solid var(--adm-border);
      border-radius: 7px;
      font-size: .85rem;
      font-family: var(--adm-font);
      background: var(--adm-surface);
      color: var(--adm-ink);
    }
    .bulk-balk .btn { margin-left: auto; }

    /* ── Tabel ── */
    .m-table-wrap {
      background: var(--adm-surface);
      border: 1px solid var(--adm-border);
      border-radius: var(--adm-radius-xl);
      overflow: hidden;
      box-shadow: var(--adm-shadow-sm);
    }
    .m-table { width: 100%; border-collapse: collapse; }
    .m-table thead th {
      padding: .55rem 1rem;
      text-align: left;
      font-size: .7rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .07em;
      color: var(--adm-faint);
      border-bottom: 1.5px solid var(--adm-border);
      background: var(--adm-surface);
      white-space: nowrap;
    }
    .m-table thead th:first-child { width: 40px; text-align: center; }
    .m-table tbody tr { transition: background .1s; }
    .m-table tbody tr:hover td { background: var(--adm-surface-2); }
    .m-table tbody tr.is-ongelezen td { background: #f0fdf8; }
    .m-table tbody tr.is-ongelezen:hover td { background: #e6faf4; }
    .m-table td {
      padding: .65rem 1rem;
      border-bottom: 1px solid #f3f2ef;
      vertical-align: middle;
      font-size: .85rem;
      color: var(--adm-ink);
    }
    .m-table tr:last-child td { border-bottom: none; }
    .m-table td:first-child { text-align: center; }

    /* Melding type badges */
    .mt-badge {
      display: inline-block;
      font-size: .66rem;
      font-weight: 700;
      padding: .15rem .5rem;
      border-radius: 99px;
      text-transform: uppercase;
      letter-spacing: .04em;
      white-space: nowrap;
    }
    .mt-bericht      { background: #dbeafe; color: #1e40af; }
    .mt-inzending    { background: #dcfce7; color: #166534; }
    .mt-doorgestuurd { background: #fef9c3; color: #854d0e; }
    .mt-ingediend    { background: #ede9fe; color: #5b21b6; }
    .mt-status       { background: #f1f5f9; color: #475569; }

    /* Door badges */
    .door-badge {
      display: inline-block;
      font-size: .66rem;
      font-weight: 700;
      padding: .12rem .45rem;
      border-radius: 99px;
      text-transform: uppercase;
      letter-spacing: .04em;
    }
    .door-klant  { background: #e0f2fe; color: #0369a1; }
    .door-admin  { background: #fce7f3; color: #9d174d; }
    .door-system { background: #f1f5f9; color: #64748b; }

    /* Ongelezen stip */
    .ongelezen-dot {
      display: inline-block;
      width: 7px; height: 7px;
      border-radius: 50%;
      background: var(--adm-accent);
      margin-right: .35rem;
      flex-shrink: 0;
    }

    /* Aanvraag link in tabel */
    .m-aanvraag-link {
      font-size: .78rem;
      font-weight: 700;
      color: #1d4ed8;
      text-decoration: none;
    }
    .m-aanvraag-link:hover { text-decoration: underline; }
    .m-aanvraag-info {
      font-size: .75rem;
      color: var(--adm-muted);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      max-width: 180px;
    }

    /* Actie tekst */
    .m-actie-text {
      font-size: .85rem;
      font-weight: 600;
      color: var(--adm-ink);
      margin-bottom: .15rem;
    }
    .m-actie-text.is-bold { font-weight: 700; }
    .m-opmerking {
      font-size: .78rem;
      color: var(--adm-muted);
      line-height: 1.45;
      max-width: 340px;
      white-space: pre-wrap;
    }
    .m-datum { font-size: .75rem; color: var(--adm-faint); white-space: nowrap; }

    /* Lees-link */
    .m-lees-link {
      font-size: .75rem;
      color: var(--adm-accent);
      text-decoration: none;
      white-space: nowrap;
    }
    .m-lees-link:hover { text-decoration: underline; }

    /* Leeg state */
    .m-leeg {
      text-align: center;
      padding: 4rem 1rem;
      color: var(--adm-muted);
    }
    .m-leeg-icon { font-size: 2rem; margin-bottom: .75rem; opacity: .4; }
    .m-leeg h3 { font-size: 1rem; font-weight: 700; color: var(--adm-ink); margin-bottom: .3rem; }
    .m-leeg p  { font-size: .875rem; color: var(--adm-muted); }

    /* Archief banner */
    .archief-banner {
      background: #fef9c3;
      border: 1px solid #fde68a;
      border-radius: var(--adm-radius);
      padding: .65rem 1rem;
      font-size: .85rem;
      color: #78350f;
      margin-bottom: 1.25rem;
      display: flex;
      align-items: center;
      gap: .6rem;
    }

    @media (max-width: 768px) {
      .m-table thead th:nth-child(3),
      .m-table td:nth-child(3) { display: none; }
      .m-filterbar input[type=text] { width: 100%; }
      .m-tabs { gap: .15rem; }
      .m-tab-archief { margin-left: 0; }
    }
  </style>
</head>
<body>
<div class="admin-wrap">

<?php require_once __DIR__ . '/includes/admin-header.php'; ?>

<div class="adm-page">

  <div class="meldingen-header">
    <h1>Meldingen</h1>
    <button class="btn btn-secondary btn-sm" onclick="toggleInstellingen()">&#9881; Instellingen</button>
  </div>

  <!-- Instellingen panel -->
  <div class="instellingen-panel" id="instellingenPanel" style="display:none;">
    <div class="admin-card">
      <h4>Archief instellingen</h4>
      <form method="POST">
        <input type="hidden" name="sla_instellingen" value="1" />
        <div class="inst-form-row">
          <label>
            Meldingen automatisch archiveren na
            <input type="number" name="archief_dagen" value="<?= (int)$archief_dagen ?>" min="1" max="365"
              class="inst-dagen-input" />
            dagen
          </label>
          <button type="submit" class="btn btn-secondary btn-sm">Opslaan</button>
        </div>
        <p class="inst-hint">
          Meldingen ouder dan <?= (int)$archief_dagen ?> dagen worden automatisch naar het archief verplaatst.
        </p>
      </form>
    </div>
  </div>

  <?php if ($filterArchief): ?>
  <div class="archief-banner">
    &#128193; Je bekijkt het archief &mdash; <?= $archief_totaal ?> gearchiveerde meldingen
  </div>
  <?php endif; ?>

  <!-- Type-tabs -->
  <div class="m-tabs">
    <?php
    $tabs = [
        ''             => ['label' => 'Alle',                'count' => $aantallen['alle']],
        'bericht'      => ['label' => 'Berichten',           'count' => $aantallen['bericht']      ?? 0],
        'inzending'    => ['label' => 'Inzendingen',         'count' => $aantallen['inzending']    ?? 0],
        'doorgestuurd' => ['label' => 'Doorsturingen',       'count' => $aantallen['doorgestuurd'] ?? 0],
        'ingediend'    => ['label' => 'Aanvragen ingediend', 'count' => $aantallen['ingediend']    ?? 0],
        'status'       => ['label' => 'Statuswijzigingen',   'count' => $aantallen['status']       ?? 0],
    ];
    foreach ($tabs as $key => $tab):
        $active = (!$filterArchief && $filterType === $key) ? 'active' : '';
        $url = '?' . http_build_query(array_filter(['type' => $key, 'door' => $filterDoor, 'zoek' => $filterZoek]));
    ?>
      <a href="<?= $url ?>" class="m-tab <?= $active ?>">
        <?= h($tab['label']) ?> <span class="m-tab-count"><?= $tab['count'] ?></span>
      </a>
    <?php endforeach; ?>
    <?php $archActive = $filterArchief ? 'active' : ''; ?>
    <a href="?archief=1" class="m-tab m-tab-archief <?= $archActive ?>">
      &#128193; Archief
      <?php if ($archief_totaal > 0): ?><span class="m-tab-count"><?= $archief_totaal ?></span><?php endif; ?>
    </a>
  </div>

  <!-- Filterbar -->
  <form method="GET" class="m-filterbar">
    <input type="hidden" name="type" value="<?= h($filterType) ?>" />
    <?php if ($filterArchief): ?><input type="hidden" name="archief" value="1" /><?php endif; ?>
    <select name="door" onchange="this.form.submit()">
      <option value="">Iedereen</option>
      <option value="klant"  <?= $filterDoor === 'klant'  ? 'selected' : '' ?>>Klant</option>
      <option value="admin"  <?= $filterDoor === 'admin'  ? 'selected' : '' ?>>Admin</option>
      <option value="system" <?= $filterDoor === 'system' ? 'selected' : '' ?>>Systeem</option>
    </select>
    <input type="text" name="zoek" value="<?= h($filterZoek) ?>" placeholder="Zoek op casenummer, e-mail, tekst…" />
    <button type="submit">Zoeken</button>
    <?php if ($filterDoor || $filterZoek): ?>
      <a href="?type=<?= h($filterType) ?><?= $filterArchief ? '&archief=1' : '' ?>" class="m-reset">&#10005; Wissen</a>
    <?php endif; ?>
  </form>

  <!-- Bulk acties + tabel -->
  <form method="POST" id="bulkForm">
    <?php foreach ($_GET as $k => $v): ?>
      <input type="hidden" name="<?= h($k) ?>" value="<?= h($v) ?>" />
    <?php endforeach; ?>

    <div class="bulk-balk" id="bulkBalk" style="display:none;">
      <span class="bulk-info"><span id="bulkAantal">0</span> geselecteerd</span>
      <select name="bulk_actie" id="bulkActie">
        <option value="">-- Kies actie --</option>
        <?php if (!$filterArchief): ?>
          <option value="gelezen">Markeer als gelezen</option>
          <option value="ongelezen">Markeer als ongelezen</option>
          <option value="archiveren">Naar archief</option>
        <?php else: ?>
          <option value="dearchiveren">Terughalen uit archief</option>
          <option value="gelezen">Markeer als gelezen</option>
          <option value="ongelezen">Markeer als ongelezen</option>
        <?php endif; ?>
      </select>
      <button type="submit" class="btn btn-primary btn-sm">Uitvoeren</button>
    </div>

    <div class="m-table-wrap">
      <?php if (empty($meldingen)): ?>
        <div class="m-leeg">
          <div class="m-leeg-icon">&#128203;</div>
          <h3><?= $filterArchief ? 'Geen gearchiveerde meldingen' : 'Geen meldingen' ?></h3>
          <p><?= $filterArchief ? 'Het archief is leeg.' : 'Er zijn momenteel geen meldingen.' ?></p>
        </div>
      <?php else: ?>
      <table class="m-table">
        <thead>
          <tr>
            <th><input type="checkbox" id="selectAll" title="Alles selecteren"></th>
            <th>Type</th>
            <th>Aanvraag</th>
            <th>Actie / Opmerking</th>
            <th>Door</th>
            <th>Datum</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($meldingen as $m):
            $type     = melding_type($m['actie']);
            $typeCss  = melding_type_css($type);
            $typeLabel= melding_type_label($type);
            $doorCss  = door_css($m['gedaan_door'] ?? 'system');
            $isOngelezen = !(bool)$m['gelezen'];
            $rowClass = $isOngelezen ? 'is-ongelezen' : '';
          ?>
          <tr class="<?= $rowClass ?>">
            <td><input type="checkbox" name="selectie[]" value="<?= (int)$m['id'] ?>" class="bulk-cb"></td>
            <td><span class="mt-badge <?= $typeCss ?>"><?= h($typeLabel) ?></span></td>
            <td>
              <?php if (!empty($m['casenummer'])): ?>
                <a href="aanvragen.php?id=<?= (int)$m['aanvraag_id_link'] ?>" class="m-aanvraag-link">
                  <?= h($m['casenummer']) ?>
                </a>
                <div class="m-aanvraag-info">
                  <?= h(trim(($m['merk'] ?? '') . ' ' . ($m['modelnummer'] ?? ''))) ?>
                </div>
              <?php else: ?>
                <span class="m-datum">—</span>
              <?php endif; ?>
            </td>
            <td>
              <div class="m-actie-text <?= $isOngelezen ? 'is-bold' : '' ?>">
                <?php if ($isOngelezen): ?><span class="ongelezen-dot"></span><?php endif; ?>
                <?= h($m['actie']) ?>
              </div>
              <?php if (!empty($m['opmerking'])): ?>
                <div class="m-opmerking"><?= h(mb_substr($m['opmerking'], 0, 200)) ?><?= mb_strlen($m['opmerking']) > 200 ? '…' : '' ?></div>
              <?php endif; ?>
            </td>
            <td><span class="door-badge <?= $doorCss ?>"><?= h(ucfirst($m['gedaan_door'] ?? 'systeem')) ?></span></td>
            <td class="m-datum"><?= date('d-m-Y H:i', strtotime($m['aangemaakt'])) ?></td>
            <td>
              <?php if ($isOngelezen): ?>
                <a href="?lees=<?= (int)$m['id'] ?>&type=<?= h($filterType) ?>&door=<?= h($filterDoor) ?>&zoek=<?= h($filterZoek) ?><?= $filterArchief ? '&archief=1' : '' ?>"
                   class="m-lees-link">Gelezen</a>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </form>

</div><!-- /.adm-page -->
</div><!-- /.admin-wrap -->

<script>
function toggleInstellingen() {
  const p = document.getElementById('instellingenPanel');
  p.style.display = p.style.display === 'none' ? 'block' : 'none';
}

// Bulk selectie
const selectAll = document.getElementById('selectAll');
const bulkBalk  = document.getElementById('bulkBalk');
const bulkAantal= document.getElementById('bulkAantal');

function updateBulk() {
  const checked = document.querySelectorAll('.bulk-cb:checked');
  bulkAantal.textContent = checked.length;
  bulkBalk.style.display = checked.length > 0 ? 'flex' : 'none';
}

if (selectAll) {
  selectAll.addEventListener('change', () => {
    document.querySelectorAll('.bulk-cb').forEach(cb => cb.checked = selectAll.checked);
    updateBulk();
  });
}
document.querySelectorAll('.bulk-cb').forEach(cb => cb.addEventListener('change', updateBulk));

document.getElementById('bulkForm')?.addEventListener('submit', function(e) {
  const actie = document.getElementById('bulkActie')?.value;
  if (!actie) { e.preventDefault(); alert('Kies een bulk-actie.'); }
});
</script>
</body>
</html>