<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$msg  = '';
$fout = '';

// ── Filterparameters ──────────────────────────────────────────────
$filterStatus = trim($_GET['status'] ?? '');
$filterRoute  = trim($_GET['route']  ?? '');
$filterZoek   = trim($_GET['zoek']   ?? '');

$statusLabels = [
    'inzending'    => ['tekst' => 'Ontvangen',           'badge' => 'badge-blue'],
    'doorgestuurd' => ['tekst' => 'Aanvulling nodig',    'badge' => 'badge-orange'],
    'aanvraag'     => ['tekst' => 'Aanvraag ontvangen',  'badge' => 'badge-green'],
    'coulance'     => ['tekst' => 'Coulance',            'badge' => 'badge-yellow'],
    'recycling'    => ['tekst' => 'Recycling',           'badge' => 'badge-purple'],
    'behandeld'    => ['tekst' => 'Behandeld',           'badge' => 'badge-green'],
    'archief'      => ['tekst' => 'Archief',             'badge' => 'badge-gray'],
];

$statusDefinitief = ['doorgestuurd', 'coulance', 'recycling', 'behandeld', 'archief'];

// ── Detailweergave ────────────────────────────────────────────────
$detail = null;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $ds = db()->prepare('SELECT * FROM aanvragen WHERE id=?');
    $ds->execute([$_GET['id']]);
    $detail = $ds->fetch() ?: null;
    if ($detail) {
        try {
            $ls = db()->prepare('SELECT * FROM aanvragen_log WHERE aanvraag_id=? ORDER BY aangemaakt ASC');
            $ls->execute([$detail['id']]);
            $detail['log'] = $ls->fetchAll();
        } catch (\PDOException $e) { $detail['log'] = []; }

        // ── Foto-pad validatie (path traversal fix) ──────────────── ← FIX
        $uploadBase = realpath(__DIR__ . '/../uploads');
        foreach (['foto_defect', 'foto_label', 'foto_bon'] as $fotoKey) {
            if (!empty($detail[$fotoKey])) {
                $absPath = realpath(__DIR__ . '/../' . $detail[$fotoKey]);
                // Alleen tonen als het pad daadwerkelijk binnen uploads/ valt
                if ($absPath === false || strpos($absPath, $uploadBase) !== 0) {
                    $detail[$fotoKey] = null;
                }
            }
        }
    }
}

// ── Lijstquery met filters ────────────────────────────────────────
$where = ['1=1']; $params = [];
if ($filterStatus) { $where[] = 'status = ?'; $params[] = $filterStatus; }
if ($filterRoute)  { $where[] = '(geadviseerde_route = ? OR advies_type = ?)'; $params[] = $filterRoute; $params[] = $filterRoute; }
if ($filterZoek)   {
    $where[] = '(merk LIKE ? OR modelnummer LIKE ? OR email LIKE ? OR casenummer LIKE ?)';
    $like = '%' . $filterZoek . '%';
    $params = array_merge($params, [$like, $like, $like, $like]);
}
$sql = 'SELECT * FROM aanvragen WHERE ' . implode(' AND ', $where) . ' ORDER BY id DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$aanvragen = $stmt->fetchAll();

// ── Whitelist datumkolom (SQL injection fix) ──────────────────────── ← FIX
$TOEGESTANE_KOLOMMEN = ['aangemaakt_op', 'created_at', 'id'];
$datumKolom = 'created_at';
try {
    $cols = db()->query('SHOW COLUMNS FROM aanvragen LIKE \'aangemaakt_op\'')->fetchColumn();
    if ($cols) $datumKolom = 'aangemaakt_op';
} catch (\Exception $e) {}
if (!in_array($datumKolom, $TOEGESTANE_KOLOMMEN, true)) $datumKolom = 'id'; // ← FIX
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8"><title>Inzendingen &ndash; Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Epilogue:wght@800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/base.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/components.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin-aanvragen.css">
  <meta name="robots" content="noindex,nofollow">
  <style>
    .filter-bar {
      display: flex;
      flex-direction: row;
      align-items: center;
      gap: .6rem;
      flex-wrap: wrap;
      margin-bottom: 1.5rem;
    }
    .filter-bar .field { margin: 0; }
    .filter-bar select {
      padding: .5rem .85rem;
      border: 1.5px solid var(--border, #d1d5db);
      border-radius: 8px;
      font-size: .85rem;
      font-family: inherit;
      background: #fff;
      height: 38px;
    }
    .filter-bar input[type=text] {
      padding: .5rem .85rem;
      border: 1.5px solid var(--border, #d1d5db);
      border-radius: 8px;
      font-size: .85rem;
      font-family: inherit;
      background: #fff;
      height: 38px;
      width: 240px;
    }
    .filter-bar button {
      padding: 0 1.1rem;
      height: 38px;
      background: var(--ink, #0f172a);
      color: #fff;
      border: none;
      border-radius: 8px;
      font-size: .85rem;
      font-weight: 600;
      cursor: pointer;
      white-space: nowrap;
    }
    .filter-bar .btn-secondary {
      height: 38px;
      display: inline-flex;
      align-items: center;
      white-space: nowrap;
    }

    .badge-orange { background:#fef3c7; color:#92400e; }
    .badge-yellow { background:#fef9c3; color:#78350f; }
    .badge-purple { background:#ede9fe; color:#5b21b6; }

    .detail-card { border: 2px solid var(--accent); }
    .detail-header {
      display:flex; align-items:center; justify-content:space-between;
      flex-wrap:wrap; gap:.5rem; margin-bottom:1.25rem; }
    .detail-header h2 { margin:0; }
    .detail-casenr { font-size:.8rem; font-weight:700; color:#1d4ed8; }
    .detail-header-right { display:flex; align-items:center; gap:.75rem; flex-wrap:wrap; }

    .detail-section { margin-bottom:1.25rem; padding-bottom:1.25rem; border-bottom:1px solid #f1f5f9; }
    .detail-section:last-child { border-bottom:none; }
    .detail-section h4 {
      font-size:.75rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em;
      color:#94a3b8; margin-bottom:.7rem; }

    .specs-grid { display:grid; grid-template-columns:140px 1fr; gap:.3rem .75rem; font-size:.875rem; }
    .specs-grid .lbl { color:#64748b; font-size:.82rem; }
    .specs-grid .val { color:#0f172a; font-weight:500; }

    .fotos-wrap { display:flex; gap:1rem; flex-wrap:wrap; }
    .foto-item { text-align:center; }
    .foto-img { max-width:120px; max-height:80px; border-radius:6px; border:1px solid #e2e8f0; }
    .foto-lbl { font-size:.72rem; color:#64748b; margin-top:.3rem; }

    .berichten-sectie { padding:0!important; border:none!important; }
    .berichten-kolommen {
      display:grid; grid-template-columns:1fr 1fr; gap:0;
      border-top:1px solid #f1f5f9; }
    .berichten-overzicht, .bericht-sturen { padding:1.25rem; }
    .berichten-overzicht { border-right:1px solid #f1f5f9; }
    @media (max-width:768px) {
      .berichten-kolommen { grid-template-columns:1fr; }
      .berichten-overzicht { border-right:none; border-bottom:1px solid #f1f5f9; }
    }

    .log-lijst { max-height:320px; overflow-y:auto; }
    .log-item { display:flex; gap:.75rem; padding:.4rem 0; border-bottom:1px solid #f8fafc; align-items:flex-start; }
    .log-item:last-child { border-bottom:none; }
    .log-time { font-size:.72rem; color:#94a3b8; white-space:nowrap; min-width:80px; margin-top:.15rem; }
    .log-tekst { font-size:.83rem; color:#374151; line-height:1.5; }
    .log-tekst small { display:block; color:#64748b; font-size:.78rem; }
    .log-tekst .log-door { color:#cbd5e1; }
    .log-leeg { font-size:.82rem; color:#94a3b8; }

    .opmerking-field {
      width:100%; padding:.5rem .75rem; border:1.5px solid #d1d5db; border-radius:7px;
      font-size:.85rem; font-family:inherit; margin-top:.5rem; resize:vertical; min-height:60px; }
    .bericht-footer { margin-top:.6rem; }

    .actie-separator {
      text-align:center; position:relative; margin:1rem 0 .75rem;
      border-top:1px solid #e5e7eb; }
    .actie-separator span {
      background:#fff; padding:0 .75rem; font-size:.75rem; color:#94a3b8;
      position:relative; top:-.65rem; }
    .actie-info { font-size:.82rem; color:#64748b; margin-bottom:.6rem; }

    .actie-knoppen { display:flex; gap:.5rem; flex-wrap:wrap; margin-top:.6rem; }
    .btn-actie {
      padding:.5rem .9rem; border:none; border-radius:8px; font-size:.82rem;
      font-weight:700; cursor:pointer; transition:opacity .15s; }
    .btn-actie:hover { opacity:.85; }
    .btn-reparatie { background:#16a34a; color:#fff; }
    .btn-taxatie   { background:#2563eb; color:#fff; }
    .btn-coulance  { background:#d97706; color:#fff; }
    .btn-recycling { background:#5b3a29; color:#fff; }
    .btn-archief   { background:#94a3b8; color:#fff; }
    .btn-behandeld { background:#475569; color:#fff; }

    .casenr-col { font-size:.78rem; font-weight:700; color:#1d4ed8; letter-spacing:.03em; }

    .optiemenu-wrap { position:relative; }
    .optiemenu-btn {
      display:flex; flex-direction:column; align-items:center; justify-content:center;
      gap:3px; width:36px; height:36px; border-radius:8px; border:1.5px solid #d1d5db;
      background:#fff; cursor:pointer; transition:background .15s, border-color .15s; }
    .optiemenu-btn:hover { background:#f8fafc; border-color:#94a3b8; }
    .optiemenu-btn span { display:block; width:5px; height:5px; border-radius:50%; background:#64748b; }
    .optiemenu-dropdown {
      display:none; position:absolute; right:0; top:calc(100% + 6px); z-index:200;
      background:#fff; border:1.5px solid #e2e8f0; border-radius:10px;
      box-shadow:0 8px 24px rgba(0,0,0,.1); min-width:190px; overflow:hidden; }
    .optiemenu-wrap.open .optiemenu-dropdown { display:block; }
    .optiemenu-header {
      padding:.5rem .9rem; font-size:.72rem; font-weight:700; text-transform:uppercase;
      letter-spacing:.06em; color:#94a3b8; border-bottom:1px solid #f1f5f9; }
    .optiemenu-item {
      display:block; width:100%; text-align:left;
      padding:.55rem .9rem; font-size:.85rem; font-weight:600; cursor:pointer;
      background:none; border:none; border-bottom:1px solid #f8fafc; transition:background .12s; }
    .optiemenu-item:last-child { border-bottom:none; }
    .optiemenu-item:hover { background:#f8fafc; }
    .item-reparatie:hover { background:#f0fdf4; color:#16a34a; }
    .item-taxatie:hover   { background:#eff6ff; color:#2563eb; }
    .item-coulance:hover  { background:#fffbeb; color:#d97706; }
    .item-recycling:hover { background:#fdf4ff; color:#5b3a29; }
    .item-behandeld:hover { background:#f1f5f9; color:#475569; }
    .item-archief:hover   { background:#f8fafc; color:#64748b; }
  </style>
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
    <a href="<?= BASE_URL ?>/admin/aanvragen.php" class="active"><span class="icon">&#128236;</span> Inzendingen</a>
    <a href="<?= BASE_URL ?>/admin/meldingen.php"><span class="icon">&#128276;</span> Meldingen</a>
    <a href="<?= BASE_URL ?>/admin/modellen.php"><span class="icon">&#128250;</span> TV Modellen</a>
    <a href="<?= BASE_URL ?>/admin/klachten.php"><span class="icon">&#9888;</span> Klachten</a>
    <a href="<?= BASE_URL ?>/admin/advies-instellingen.php"><span class="icon">&#9881;</span> Advies instellingen</a>
    <a href="<?= BASE_URL ?>/admin/mailtemplates.php"><span class="icon">&#128140;</span> Mailtemplates</a>
    <a href="<?= BASE_URL ?>/admin/admins.php"><span class="icon">&#128100;</span> Admin accounts</a>
    <a href="<?= BASE_URL ?>/" target="_blank"><span class="icon">&#127760;</span> Website bekijken</a>
  </div>
  <div class="admin-content">
    <h1>Inzendingen</h1>

    <?php if ($msg):  ?><div class="alert alert-success"><?= h($msg) ?></div><?php endif; ?>
    <?php if ($fout): ?><div class="alert alert-error"><?= h($fout) ?></div><?php endif; ?>
    <?php if (isset($_GET['saved'])): ?><div class="alert alert-success">Wijziging opgeslagen.</div><?php endif; ?>

    <?php if ($detail): ?>
    <?php
      $sl = $statusLabels[$detail['status']] ?? ['tekst'=>$detail['status'],'badge'=>'badge-gray'];
      $isDefinitief = in_array($detail['status'], $statusDefinitief);
    ?>
    <div class="admin-card detail-card">
      <div class="detail-header">
        <div>
          <h2><?= h($detail['merk'].' '.$detail['modelnummer']) ?></h2>
          <?php if (!empty($detail['casenummer'])): ?>
            <span class="detail-casenr">&#128230; <?= h($detail['casenummer']) ?></span>
          <?php endif; ?>
        </div>
        <div class="detail-header-right">
          <span class="badge <?= $sl['badge'] ?>"><?= h($sl['tekst']) ?></span>

          <?php if ($isDefinitief): ?>
          <div class="optiemenu-wrap">
            <button class="optiemenu-btn" aria-label="Meer opties" onclick="toggleOptiemenu(this)">
              <span></span><span></span><span></span>
            </button>
            <div class="optiemenu-dropdown">
              <div class="optiemenu-header">Status wijzigen</div>
              <form method="POST" action="<?= BASE_URL ?>/api/admin-actie.php">
                <input type="hidden" name="csrf" value="<?= csrf() ?>"> <!-- ← FIX -->
                <input type="hidden" name="id" value="<?= (int)$detail['id'] ?>" />
                <input type="hidden" name="opmerking" value="" />
                <button type="submit" name="actie" value="doorzetten_reparatie" class="optiemenu-item item-reparatie">&#128295; Reparatie</button>
                <button type="submit" name="actie" value="doorzetten_taxatie"   class="optiemenu-item item-taxatie">&#128203; Taxatie</button>
                <button type="submit" name="actie" value="coulance"             class="optiemenu-item item-coulance">&#129309; Coulance</button>
                <button type="submit" name="actie" value="recycling"            class="optiemenu-item item-recycling">&#9851; Recycling</button>
                <button type="submit" name="actie" value="behandeld"            class="optiemenu-item item-behandeld">&#10003; Behandeld</button>
                <button type="submit" name="actie" value="archiveren"           class="optiemenu-item item-archief">&#128193; Archiveren</button>
              </form>
            </div>
          </div>
          <?php endif; ?>

          <a href="<?= BASE_URL ?>/admin/aanvragen.php?<?= http_build_query(array_filter(['status'=>$filterStatus,'route'=>$filterRoute,'zoek'=>$filterZoek])) ?>" class="btn btn-secondary btn-sm">&#8592; Terug</a>
        </div>
      </div>

      <div class="detail-section">
        <h4>Inzendinggegevens</h4>
        <div class="specs-grid">
          <span class="lbl">Casenummer</span>  <span class="val"><?= h($detail['casenummer'] ?? '—') ?></span>
          <span class="lbl">Datum</span>        <span class="val"><?= h($detail[$datumKolom] ?? '') ?></span>
          <span class="lbl">E-mail</span>       <span class="val"><a href="mailto:<?= h($detail['email']) ?>"><?= h($detail['email']) ?></a></span>
          <span class="lbl">Merk / model</span> <span class="val"><?= h($detail['merk'].' '.$detail['modelnummer']) ?></span>
          <span class="lbl">Aanschafjaar</span> <span class="val"><?= h($detail['aanschafjaar'] ?? '—') ?></span>
          <span class="lbl">Aanschafwaarde</span><span class="val"><?= h($detail['aanschafwaarde'] ?? '—') ?></span>
          <span class="lbl">Aankoop</span>      <span class="val"><?= h($detail['aankoop_locatie'] ?? 'nl') ?></span>
          <span class="lbl">Situatie</span>     <span class="val"><?= h($detail['situatie'] ?? '—') ?></span>
          <span class="lbl">Klachttype</span>   <span class="val"><?= h($detail['klacht_type'] ?? '—') ?></span>
          <span class="lbl">Geadv. route</span> <span class="val"><?= h($detail['geadviseerde_route'] ?? '—') ?>
            <?php if ($detail['coulance_kans'] ?? 0): ?> (<?= (int)$detail['coulance_kans'] ?>%<?php endif; ?>)</span>
          <?php if (!empty($detail['omschrijving'])): ?>
          <span class="lbl">Omschrijving</span> <span class="val" style="font-style:italic;"><?= h($detail['omschrijving']) ?></span>
          <?php endif; ?>
        </div>
      </div>

      <?php if (!empty($detail['naam']) || !empty($detail['telefoon']) || !empty($detail['adres'])): ?>
      <div class="detail-section">
        <h4>Klantgegevens (ingevuld na doorzetten)</h4>
        <div class="specs-grid">
          <?php if ($detail['naam']): ?>    <span class="lbl">Naam</span>     <span class="val"><?= h($detail['naam']) ?></span><?php endif; ?>
          <?php if ($detail['telefoon']): ?><span class="lbl">Telefoon</span> <span class="val"><?= h($detail['telefoon']) ?></span><?php endif; ?>
          <?php if ($detail['adres']): ?>   <span class="lbl">Adres</span>    <span class="val"><?= h($detail['adres']) ?></span><?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

      <?php if (!empty($detail['foto_defect']) || !empty($detail['foto_label']) || !empty($detail['foto_bon'])): ?>
      <div class="detail-section">
        <h4>Foto's</h4>
        <div class="fotos-wrap">
          <?php foreach (['foto_defect'=>'Defect','foto_label'=>'Label/S/N','foto_bon'=>'Aankoopbon'] as $k=>$lbl): ?>
            <?php if (!empty($detail[$k])): ?>
            <div class="foto-item">
              <a href="<?= BASE_URL ?>/<?= h($detail[$k]) ?>" target="_blank">
                <img src="<?= BASE_URL ?>/<?= h($detail[$k]) ?>" alt="<?= $lbl ?>" class="foto-img" loading="lazy" />
              </a>
              <div class="foto-lbl"><?= $lbl ?></div>
            </div>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <div class="detail-section berichten-sectie">
        <div class="berichten-kolommen">

          <div class="berichten-overzicht">
            <h4>Berichtenoverzicht</h4>
            <?php if (!empty($detail['log'])): ?>
              <div class="log-lijst">
              <?php foreach (array_reverse($detail['log']) as $le): ?>
                <div class="log-item">
                  <span class="log-time"><?= date('d-m H:i', strtotime($le['aangemaakt'])) ?></span>
                  <span class="log-tekst"><?= h($le['actie']) ?>
                    <?php if ($le['opmerking']): ?><small><?= h($le['opmerking']) ?></small><?php endif; ?>
                    <small class="log-door"><?= h($le['gedaan_door']) ?></small>
                  </span>
                </div>
              <?php endforeach; ?>
              </div>
            <?php else: ?>
              <p class="log-leeg">Nog geen log-entries.</p>
            <?php endif; ?>
          </div>

          <div class="bericht-sturen">
            <h4>Bericht sturen naar klant</h4>
            <form method="POST" action="<?= BASE_URL ?>/api/admin-actie.php">
              <input type="hidden" name="csrf" value="<?= csrf() ?>"> <!-- ← FIX -->
              <input type="hidden" name="id" value="<?= (int)$detail['id'] ?>" />
              <textarea name="opmerking" class="opmerking-field" placeholder="Typ hier uw bericht aan de klant..." rows="4" required></textarea>
              <div class="bericht-footer">
                <button type="submit" name="actie" value="bericht_admin" class="btn-actie btn-reparatie">&#128172; Bericht versturen</button>
              </div>
            </form>

            <?php if (!$isDefinitief): ?>
            <div class="actie-separator"><span>of voer een actie uit</span></div>
            <p class="actie-info">
              Status: <strong><?= h($sl['tekst']) ?></strong> &bull;
              Adviestype: <strong><?= h($detail['advies_type'] ?? 'niet bepaald') ?></strong>
            </p>
            <form method="POST" action="<?= BASE_URL ?>/api/admin-actie.php">
              <input type="hidden" name="csrf" value="<?= csrf() ?>"> <!-- ← FIX -->
              <input type="hidden" name="id" value="<?= (int)$detail['id'] ?>" />
              <textarea name="opmerking" class="opmerking-field" placeholder="Optionele opmerking bij actie..." rows="2"></textarea>
              <div class="actie-knoppen">
                <button type="submit" name="actie" value="doorzetten_reparatie" class="btn-actie btn-reparatie">&#128295; Reparatie</button>
                <button type="submit" name="actie" value="doorzetten_taxatie"   class="btn-actie btn-taxatie">&#128203; Taxatie</button>
                <button type="submit" name="actie" value="coulance"             class="btn-actie btn-coulance">&#129309; Coulance</button>
                <button type="submit" name="actie" value="recycling"            class="btn-actie btn-recycling">&#9851; Recycling</button>
                <button type="submit" name="actie" value="behandeld"            class="btn-actie btn-behandeld">&#10003; Behandeld</button>
                <button type="submit" name="actie" value="archiveren"           class="btn-actie btn-archief">Archiveren</button>
              </div>
            </form>
            <?php endif; ?>
          </div>

        </div>
      </div>

    </div>
    <?php endif; ?>

    <div class="admin-card">
      <form method="GET" class="filter-bar">
        <select name="status">
          <option value="">Alle statussen</option>
          <?php foreach ($statusLabels as $k => $v): ?>
            <option value="<?= $k ?>" <?= $filterStatus===$k?'selected':'' ?>><?= h($v['tekst']) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="route">
          <option value="">Alle routes</option>
          <?php foreach (['garantie','coulance','reparatie','taxatie','recycling'] as $r): ?>
            <option value="<?= $r ?>" <?= $filterRoute===$r?'selected':'' ?>><?= ucfirst($r) ?></option>
          <?php endforeach; ?>
        </select>
        <input type="text" name="zoek" value="<?= h($filterZoek) ?>" placeholder="Zoek op merk, email, casenummer…" />
        <button type="submit">Filteren</button>
        <?php if ($filterStatus || $filterRoute || $filterZoek): ?>
          <a href="<?= BASE_URL ?>/admin/aanvragen.php" class="btn btn-secondary btn-sm">Wis filters</a>
        <?php endif; ?>
      </form>

      <h2>Inzendingen (<?= count($aanvragen) ?>)</h2>
      <?php if (empty($aanvragen)): ?>
        <p style="color:var(--muted);font-size:.875rem;">Geen inzendingen gevonden.</p>
      <?php else: ?>
      <table class="admin-table">
        <thead>
          <tr>
            <th>Casenummer</th>
            <th>Datum</th>
            <th>TV</th>
            <th>E-mail</th>
            <th>Route</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($aanvragen as $r): ?>
        <tr>
          <td class="casenr-col"><?= h($r['casenummer'] ?? '#'.$r['id']) ?></td>
          <td style="white-space:nowrap;font-size:.78rem;color:#64748b;"><?= date('d-m-y H:i', strtotime($r[$datumKolom] ?? '')) ?></td>
          <td><strong style="font-size:.875rem;"><?= h($r['merk'].' '.$r['modelnummer']) ?></strong></td>
          <td style="font-size:.8rem;"><?= h($r['email']) ?></td>
          <td>
            <?php $rt = $r['advies_type'] ?: $r['geadviseerde_route']; ?>
            <?php if ($rt): ?><span class="badge badge-blue" style="font-size:.72rem;"><?= h($rt) ?></span><?php else: ?>—<?php endif; ?>
          </td>
          <td>
            <?php $sl2 = $statusLabels[$r['status']] ?? ['tekst'=>$r['status'],'badge'=>'badge-gray']; ?>
            <span class="badge <?= $sl2['badge'] ?>" style="font-size:.72rem;"><?= h($sl2['tekst']) ?></span>
          </td>
          <td>
            <a href="?id=<?= $r['id'] ?>&<?= http_build_query(array_filter(['status'=>$filterStatus,'route'=>$filterRoute,'zoek'=>$filterZoek])) ?>"
               class="btn btn-sm btn-secondary">Openen</a>
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

<script>
function toggleOptiemenu(btn) {
  const wrap = btn.closest('.optiemenu-wrap');
  const isOpen = wrap.classList.contains('open');
  document.querySelectorAll('.optiemenu-wrap.open').forEach(w => w.classList.remove('open'));
  if (!isOpen) wrap.classList.add('open');
}
document.addEventListener('click', function(e) {
  if (!e.target.closest('.optiemenu-wrap')) {
    document.querySelectorAll('.optiemenu-wrap.open').forEach(w => w.classList.remove('open'));
  }
});
</script>
</body>
</html>