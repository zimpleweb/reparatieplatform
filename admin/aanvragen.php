<?php
session_start();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$msg  = '';
$fout = '';

$filterStatus = trim($_GET['status'] ?? '');
$filterRoute  = trim($_GET['route']  ?? '');
$filterZoek   = trim($_GET['zoek']   ?? '');

$statusLabels = [
    'inzending'    => ['tekst' => 'Ontvangen',          'badge' => 'badge-blue'],
    'doorgestuurd' => ['tekst' => 'Aanvulling nodig',   'badge' => 'badge-orange'],
    'aanvraag'     => ['tekst' => 'Aanvraag ontvangen', 'badge' => 'badge-green'],
    'coulance'     => ['tekst' => 'Coulance',           'badge' => 'badge-yellow'],
    'recycling'    => ['tekst' => 'Recycling',          'badge' => 'badge-purple'],
    'behandeld'    => ['tekst' => 'Behandeld',          'badge' => 'badge-green'],
    'archief'      => ['tekst' => 'Archief',            'badge' => 'badge-gray'],
];

$statusDefinitief = ['doorgestuurd', 'coulance', 'recycling', 'behandeld', 'archief'];

// Aanvraag-type opties (voor de gekleurde buttons en het select-menu)
$aanvraagTypes = [
    'reparatie' => ['label' => 'Reparatie',  'kleur' => '#16a34a', 'tekst' => '#fff'],
    'taxatie'   => ['label' => 'Taxatie',    'kleur' => '#2563eb', 'tekst' => '#fff'],
    'coulance'  => ['label' => 'Coulance',   'kleur' => '#d97706', 'tekst' => '#fff'],
    'garantie'  => ['label' => 'Garantie',   'kleur' => '#7c3aed', 'tekst' => '#fff'],
    'recycling' => ['label' => 'Recycling',  'kleur' => '#0f766e', 'tekst' => '#fff'],
];

// ── POST: bericht sturen ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'bericht') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        $fout = 'Ongeldig beveiligingstoken.';
    } else {
        $aanvraagId = (int)($_POST['aanvraag_id'] ?? 0);
        $berichtTxt = trim($_POST['bericht'] ?? '');
        if ($aanvraagId && $berichtTxt !== '') {
            // Sla op in log
            try {
                $ins = db()->prepare(
                    'INSERT INTO aanvragen_log (aanvraag_id, actie, opmerking, aangemaakt)
                     VALUES (?, ?, ?, NOW())'
                );
                $ins->execute([$aanvraagId, 'Bericht verstuurd aan klant', $berichtTxt]);
                $msg = 'Bericht opgeslagen in de activiteitenlog.';
            } catch (\PDOException $e) {
                $fout = 'Kon bericht niet opslaan: ' . h($e->getMessage());
            }
        } else {
            $fout = 'Bericht mag niet leeg zijn.';
        }
        // Redirect terug naar de detailpagina
        $qs = http_build_query(array_filter([
            'id'     => $aanvraagId,
            'status' => $filterStatus,
            'route'  => $filterRoute,
            'zoek'   => $filterZoek,
            'saved'  => '1',
        ]));
        header('Location: ?' . $qs);
        exit;
    }
}

// ── POST: status wijzigen ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'status') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        $fout = 'Ongeldig beveiligingstoken.';
    } else {
        $aanvraagId  = (int)($_POST['aanvraag_id'] ?? 0);
        $nieuwStatus = trim($_POST['nieuw_status'] ?? '');
        $toegestaan  = array_keys($statusLabels);
        if ($aanvraagId && in_array($nieuwStatus, $toegestaan, true)) {
            db()->prepare('UPDATE aanvragen SET status=? WHERE id=?')
               ->execute([$nieuwStatus, $aanvraagId]);
            try {
                $ins = db()->prepare(
                    'INSERT INTO aanvragen_log (aanvraag_id, actie, aangemaakt)
                     VALUES (?, ?, NOW())'
                );
                $ins->execute([$aanvraagId, 'Status gewijzigd naar: ' . ($statusLabels[$nieuwStatus]['tekst'] ?? $nieuwStatus)]);
            } catch (\PDOException $e) {}
            $qs = http_build_query(array_filter([
                'id'     => $aanvraagId,
                'status' => $filterStatus,
                'route'  => $filterRoute,
                'zoek'   => $filterZoek,
                'saved'  => '1',
            ]));
            header('Location: ?' . $qs);
            exit;
        } else {
            $fout = 'Ongeldige statuswaarde.';
        }
    }
}

// ── POST: aanvraag-type toekennen / wijzigen ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'set_type') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        $fout = 'Ongeldig beveiligingstoken.';
    } else {
        $aanvraagId = (int)($_POST['aanvraag_id'] ?? 0);
        $nieuwType  = trim($_POST['aanvraag_type'] ?? '');
        $toegestaan = array_keys($aanvraagTypes);
        if ($aanvraagId && in_array($nieuwType, $toegestaan, true)) {
            // Probeer het veld aan te passen (kolom kan 'aanvraag_type' of 'advies_type' heten)
            try {
                db()->prepare('UPDATE aanvragen SET aanvraag_type=? WHERE id=?')
                   ->execute([$nieuwType, $aanvraagId]);
            } catch (\PDOException $e) {
                try {
                    db()->prepare('UPDATE aanvragen SET advies_type=? WHERE id=?')
                       ->execute([$nieuwType, $aanvraagId]);
                } catch (\PDOException $e2) {}
            }
            // Log de wijziging
            try {
                $ins = db()->prepare(
                    'INSERT INTO aanvragen_log (aanvraag_id, actie, aangemaakt)
                     VALUES (?, ?, NOW())'
                );
                $ins->execute([$aanvraagId, 'Aanvraagtype ingesteld op: ' . ($aanvraagTypes[$nieuwType]['label'] ?? $nieuwType)]);
            } catch (\PDOException $e) {}
            $qs = http_build_query(array_filter([
                'id'     => $aanvraagId,
                'status' => $filterStatus,
                'route'  => $filterRoute,
                'zoek'   => $filterZoek,
                'saved'  => '1',
            ]));
            header('Location: ?' . $qs);
            exit;
        } else {
            $fout = 'Ongeldig aanvraagtype.';
        }
    }
}

// ── POST: aanvraag-type wijzigen via lijst (optiemenu) ────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'set_type_lijst') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        $fout = 'Ongeldig beveiligingstoken.';
    } else {
        $aanvraagId = (int)($_POST['aanvraag_id'] ?? 0);
        $nieuwType  = trim($_POST['aanvraag_type'] ?? '');
        $toegestaan = array_keys($aanvraagTypes);
        if ($aanvraagId && in_array($nieuwType, $toegestaan, true)) {
            try {
                db()->prepare('UPDATE aanvragen SET aanvraag_type=? WHERE id=?')
                   ->execute([$nieuwType, $aanvraagId]);
            } catch (\PDOException $e) {
                try {
                    db()->prepare('UPDATE aanvragen SET advies_type=? WHERE id=?')
                       ->execute([$nieuwType, $aanvraagId]);
                } catch (\PDOException $e2) {}
            }
            try {
                $ins = db()->prepare(
                    'INSERT INTO aanvragen_log (aanvraag_id, actie, aangemaakt)
                     VALUES (?, ?, NOW())'
                );
                $ins->execute([$aanvraagId, 'Aanvraagtype gewijzigd naar: ' . ($aanvraagTypes[$nieuwType]['label'] ?? $nieuwType)]);
            } catch (\PDOException $e) {}
            $qs = http_build_query(array_filter([
                'status' => $filterStatus,
                'route'  => $filterRoute,
                'zoek'   => $filterZoek,
                'saved'  => '1',
            ]));
            header('Location: ?' . $qs);
            exit;
        } else {
            $fout = 'Ongeldig aanvraagtype.';
        }
    }
}

// ── Detail ophalen ────────────────────────────────────────────────────────
$detail = null;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $ds = db()->prepare('SELECT * FROM aanvragen WHERE id=?');
    $ds->execute([$_GET['id']]);
    $detail = $ds->fetch() ?: null;
    if ($detail) {
        try {
            $ls = db()->prepare(
                'SELECT * FROM aanvragen_log WHERE aanvraag_id=? ORDER BY aangemaakt ASC'
            );
            $ls->execute([$detail['id']]);
            $detail['log'] = $ls->fetchAll();
        } catch (\PDOException $e) { $detail['log'] = []; }

        $uploadBase = realpath(__DIR__ . '/../uploads');
        foreach (['foto_defect', 'foto_label', 'foto_bon'] as $fotoKey) {
            if (!empty($detail[$fotoKey])) {
                $absPath = realpath(__DIR__ . '/../' . $detail[$fotoKey]);
                if ($absPath === false || strpos($absPath, $uploadBase) !== 0) {
                    $detail[$fotoKey] = null;
                }
            }
        }
    }
}

// ── Lijst ophalen ─────────────────────────────────────────────────────────
$where = ['1=1']; $params = [];
if ($filterStatus) { $where[] = 'status = ?'; $params[] = $filterStatus; }
if ($filterRoute)  {
    $where[] = '(geadviseerde_route = ? OR advies_type = ? OR aanvraag_type = ?)';
    $params[] = $filterRoute; $params[] = $filterRoute; $params[] = $filterRoute;
}
if ($filterZoek) {
    $where[] = '(merk LIKE ? OR modelnummer LIKE ? OR email LIKE ? OR casenummer LIKE ?)';
    $like = '%' . $filterZoek . '%';
    $params = array_merge($params, [$like, $like, $like, $like]);
}
$sql  = 'SELECT * FROM aanvragen WHERE ' . implode(' AND ', $where) . ' ORDER BY id DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$aanvragen = $stmt->fetchAll();

$TOEGESTANE_KOLOMMEN = ['aangemaakt_op', 'created_at', 'id'];
$datumKolom = 'created_at';
try {
    $cols = db()->query("SHOW COLUMNS FROM aanvragen LIKE 'aangemaakt_op'")->fetchColumn();
    if ($cols) $datumKolom = 'aangemaakt_op';
} catch (\Exception $e) {}
if (!in_array($datumKolom, $TOEGESTANE_KOLOMMEN, true)) $datumKolom = 'id';

$adminActivePage = 'aanvragen';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Inzendingen &ndash; Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/base.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/components.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
  <meta name="robots" content="noindex,nofollow">
  <style>
    /* ── Filterbalk ── */
    .filter-bar {
      display: flex; flex-direction: row; align-items: center;
      gap: .6rem; flex-wrap: wrap; margin-bottom: 1.5rem;
    }
    .filter-bar .field { margin: 0; }
    .filter-bar select, .filter-bar input[type=text] {
      padding: .5rem .85rem; border: 1.5px solid var(--border, #d1d5db);
      border-radius: 8px; font-size: .85rem; font-family: inherit;
      background: #fff; height: 38px;
    }
    .filter-bar input[type=text] { width: 240px; }
    .filter-bar button {
      padding: 0 1.1rem; height: 38px; background: var(--ink, #0f172a);
      color: #fff; border: none; border-radius: 8px; font-size: .85rem;
      font-weight: 600; cursor: pointer; white-space: nowrap;
    }
    .filter-bar .btn-secondary {
      height: 38px; display: inline-flex; align-items: center; white-space: nowrap;
    }

    /* ── Extra badge kleuren ── */
    .badge-orange { background: #fef3c7; color: #92400e; }
    .badge-yellow { background: #fef9c3; color: #78350f; }
    .badge-purple { background: #ede9fe; color: #5b21b6; }

    /* ── Detail kaart ── */
    .detail-card { border: 2px solid var(--accent); }
    .detail-header {
      display: flex; align-items: center; justify-content: space-between;
      flex-wrap: wrap; gap: .5rem; margin-bottom: 1.25rem;
    }
    .detail-header h2 { margin: 0; font-size: 1.35rem; font-weight: 800; color: #0f172a; }
    .detail-casenr {
      font-size: .8rem; font-weight: 700; color: #1d4ed8; letter-spacing: .03em;
    }
    .detail-casenr a { color: #1d4ed8; text-decoration: none; }
    .detail-casenr a:hover { text-decoration: underline; }
    .detail-header-right { display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; }
    .detail-section {
      margin-bottom: 1.25rem; padding-bottom: 1.25rem;
      border-bottom: 1px solid #f1f5f9;
    }
    .detail-section:last-child { border-bottom: none; }
    .detail-section h4 {
      font-size: .75rem; font-weight: 700; text-transform: uppercase;
      letter-spacing: .08em; color: #94a3b8; margin-bottom: .7rem;
    }
    .specs-grid {
      display: grid; grid-template-columns: 140px 1fr;
      gap: .3rem .75rem; font-size: .875rem;
    }
    .specs-grid .lbl { color: #64748b; font-size: .82rem; }
    .specs-grid .val { color: #0f172a; font-weight: 500; }

    /* ── Foto's ── */
    .fotos-wrap { display: flex; gap: 1rem; flex-wrap: wrap; }
    .foto-item { text-align: center; }
    .foto-img {
      max-width: 120px; max-height: 80px; border-radius: 6px;
      border: 1px solid #e2e8f0;
    }
    .foto-lbl { font-size: .72rem; color: #64748b; margin-top: .3rem; }

    /* ── Berichten & acties ── */
    .berichten-sectie { padding: 0 !important; border: none !important; }
    .berichten-kolommen {
      display: grid; grid-template-columns: 1fr 1fr; gap: 0;
      border-top: 1px solid #f1f5f9;
    }
    .berichten-overzicht, .bericht-sturen { padding: 1.25rem; }
    .berichten-overzicht { border-right: 1px solid #f1f5f9; }
    @media (max-width: 768px) {
      .berichten-kolommen { grid-template-columns: 1fr; }
      .berichten-overzicht { border-right: none; border-bottom: 1px solid #f1f5f9; }
    }
    .log-lijst { max-height: 320px; overflow-y: auto; }
    .log-item {
      display: flex; gap: .75rem; padding: .4rem 0;
      border-bottom: 1px solid #f8fafc; align-items: flex-start;
    }
    .log-item:last-child { border-bottom: none; }
    .log-time { font-size: .72rem; color: #94a3b8; white-space: nowrap; min-width: 80px; margin-top: .15rem; }
    .log-tekst { font-size: .83rem; color: #374151; line-height: 1.5; }
    .log-tekst small { display: block; color: #64748b; font-size: .78rem; }
    .log-leeg { font-size: .82rem; color: #94a3b8; }
    .opmerking-field {
      width: 100%; padding: .5rem .75rem; border: 1.5px solid #d1d5db; border-radius: 7px;
      font-size: .85rem; font-family: inherit; margin-top: .5rem; resize: vertical; min-height: 60px;
    }
    .bericht-footer { margin-top: .6rem; }
    .actie-separator {
      text-align: center; position: relative; margin: 1rem 0 .75rem;
      border-top: 1px solid #e5e7eb;
    }
    .actie-separator span {
      background: #fff; padding: 0 .75rem; font-size: .75rem; color: #94a3b8;
      position: relative; top: -.65rem;
    }
    .actie-info { font-size: .82rem; color: #64748b; margin-bottom: .6rem; }

    /* ── Aanvraagtype gekleurde buttons ── */
    .aanvraagtype-buttons { display: flex; gap: .45rem; flex-wrap: wrap; margin-bottom: .75rem; }
    .btn-type {
      padding: .45rem .9rem; border: none; border-radius: 8px; font-size: .82rem;
      font-weight: 700; cursor: pointer; transition: opacity .15s, transform .1s;
      white-space: nowrap;
    }
    .btn-type:hover { opacity: .85; }
    .btn-type:active { transform: scale(.97); }
    .btn-type.active-type {
      outline: 3px solid #0f172a; outline-offset: 2px;
    }
    .btn-type-reparatie { background: #16a34a; color: #fff; }
    .btn-type-taxatie   { background: #2563eb; color: #fff; }
    .btn-type-coulance  { background: #d97706; color: #fff; }
    .btn-type-garantie  { background: #7c3aed; color: #fff; }
    .btn-type-recycling { background: #0f766e; color: #fff; }

    /* ── Status actieknoppen (detail) ── */
    .actie-knoppen { display: flex; gap: .5rem; flex-wrap: wrap; margin-top: .6rem; }
    .btn-actie {
      padding: .5rem .9rem; border: none; border-radius: 8px; font-size: .82rem;
      font-weight: 700; cursor: pointer; transition: opacity .15s;
    }
    .btn-actie:hover { opacity: .85; }
    .btn-coulance  { background: #d97706; color: #fff; }
    .btn-recycling { background: #0f766e; color: #fff; }
    .btn-archief   { background: #94a3b8; color: #fff; }
    .btn-behandeld { background: #475569; color: #fff; }

    /* ── Aanvraagtype wijzigen select (detail, via optiemenu) ── */
    .type-select-wrap { margin-top: .5rem; }
    .type-select-wrap select {
      padding: .45rem .75rem; border: 1.5px solid #d1d5db; border-radius: 7px;
      font-size: .85rem; font-family: inherit; background: #fff; cursor: pointer;
    }
    .type-select-wrap button {
      margin-left: .4rem; padding: .45rem .85rem; background: #0f172a; color: #fff;
      border: none; border-radius: 7px; font-size: .82rem; font-weight: 700; cursor: pointer;
    }
    .type-select-wrap button:hover { background: #1e293b; }

    /* ── Tabel casenummer klikbaar ── */
    .casenr-col a {
      font-size: .78rem; font-weight: 700; color: #1d4ed8; letter-spacing: .03em;
      text-decoration: none;
    }
    .casenr-col a:hover { text-decoration: underline; }

    /* ── Optiemenu (lijst) ── */
    .optiemenu-wrap { position: relative; }
    .optiemenu-btn {
      display: flex; flex-direction: column; align-items: center; justify-content: center;
      gap: 3px; width: 36px; height: 36px; border-radius: 8px; border: 1.5px solid #d1d5db;
      background: #fff; cursor: pointer; transition: background .15s, border-color .15s;
    }
    .optiemenu-btn:hover { background: #f8fafc; border-color: #94a3b8; }
    .optiemenu-btn span { display: block; width: 5px; height: 5px; border-radius: 50%; background: #64748b; }
    .optiemenu-dropdown {
      display: none; position: absolute; right: 0; top: calc(100% + 6px); z-index: 200;
      background: #fff; border: 1.5px solid #e2e8f0; border-radius: 10px;
      box-shadow: 0 8px 24px rgba(0,0,0,.1); min-width: 200px; overflow: hidden;
    }
    .optiemenu-wrap.open .optiemenu-dropdown { display: block; }
    .optiemenu-header {
      padding: .5rem .9rem; font-size: .72rem; font-weight: 700; text-transform: uppercase;
      letter-spacing: .06em; color: #94a3b8; border-bottom: 1px solid #f1f5f9;
    }
    .optiemenu-item {
      display: block; width: 100%; text-align: left;
      padding: .55rem .9rem; font-size: .85rem; font-weight: 600; cursor: pointer;
      border: none; background: none; color: #374151; transition: background .1s;
    }
    .optiemenu-item:hover { background: #f8fafc; }
    .optiemenu-item.danger { color: #b91c1c; }
    .optiemenu-item.danger:hover { background: #fef2f2; }
    .optiemenu-divider { border: none; border-top: 1px solid #f1f5f9; margin: .25rem 0; }
    /* Type-submenu kleurbollen in optiemenu */
    .optiemenu-type-dot {
      display: inline-block; width: 9px; height: 9px; border-radius: 50%;
      margin-right: .5rem; vertical-align: middle;
    }
  </style>
</head>
<body>

<?php require_once __DIR__ . '/includes/admin-header.php'; ?>

<div class="adm-page">
  <div class="page-header">
    <div>
      <h1 class="page-title">Inzendingen</h1>
      <p class="page-subtitle">Overzicht van alle aanvragen en inzendingen.</p>
    </div>
  </div>

  <?php if ($msg):  ?><div class="alert alert-success"><?= h($msg) ?></div><?php endif; ?>
  <?php if ($fout): ?><div class="alert alert-error"><?= h($fout) ?></div><?php endif; ?>
  <?php if (isset($_GET['saved'])): ?><div class="alert alert-success">&#10003; Wijziging opgeslagen.</div><?php endif; ?>

  <?php if ($detail): ?>
  <?php
    $sl = $statusLabels[$detail['status']] ?? ['tekst' => $detail['status'], 'badge' => 'badge-gray'];
    $isDefinitief = in_array($detail['status'], $statusDefinitief);
    // Huidig aanvraagtype
    $huidigType = $detail['aanvraag_type'] ?? $detail['advies_type'] ?? '';
  ?>

  <div class="admin-card detail-card">
    <div class="detail-header">
      <div>
        <h2>Aanvraag #<?= (int)$detail['id'] ?></h2>
        <?php if (!empty($detail['casenummer'])): ?>
          <span class="detail-casenr">
            <a href="?id=<?= (int)$detail['id'] ?>&<?= h(http_build_query(array_filter(['status'=>$filterStatus,'route'=>$filterRoute,'zoek'=>$filterZoek]))) ?>">
              <?= h($detail['casenummer']) ?>
            </a>
          </span>
        <?php endif; ?>
      </div>
      <div class="detail-header-right">
        <span class="badge <?= $sl['badge'] ?>"><?= h($sl['tekst']) ?></span>
        <a href="?<?= h(http_build_query(array_filter(['status'=>$filterStatus,'route'=>$filterRoute,'zoek'=>$filterZoek]))) ?>"
           class="btn btn-sm btn-secondary">&larr; Terug naar lijst</a>
      </div>
    </div>

    <!-- Klantgegevens -->
    <div class="detail-section">
      <h4>Klantgegevens</h4>
      <div class="specs-grid">
        <span class="lbl">E-mail</span><span class="val"><?= h($detail['email'] ?? '—') ?></span>
        <span class="lbl">Naam</span><span class="val"><?= h($detail['naam'] ?? '—') ?></span>
        <span class="lbl">Telefoon</span><span class="val"><?= h($detail['telefoon'] ?? '—') ?></span>
        <span class="lbl">Adres</span><span class="val"><?= h(trim(($detail['straat']??'').' '.($detail['huisnummer']??''))) ?: '—' ?></span>
        <span class="lbl">Postcode / Plaats</span><span class="val"><?= h(trim(($detail['postcode']??'').' '.($detail['woonplaats']??''))) ?: '—' ?></span>
      </div>
    </div>

    <!-- TV-gegevens -->
    <div class="detail-section">
      <h4>TV-gegevens</h4>
      <div class="specs-grid">
        <span class="lbl">Merk</span><span class="val"><?= h($detail['merk'] ?? '—') ?></span>
        <span class="lbl">Modelnummer</span><span class="val"><?= h($detail['modelnummer'] ?? '—') ?></span>
        <span class="lbl">Serienummer</span><span class="val"><?= h($detail['serienummer'] ?? '—') ?></span>
        <span class="lbl">Aankoopjaar</span><span class="val"><?= h($detail['aankoopjaar'] ?? '—') ?></span>
        <span class="lbl">Defect</span><span class="val"><?= h($detail['defect_omschrijving'] ?? '—') ?></span>
        <span class="lbl">Route</span><span class="val"><?= h($detail['geadviseerde_route'] ?? $detail['advies_type'] ?? '—') ?></span>
      </div>
    </div>

    <!-- Foto's -->
    <?php if (!empty($detail['foto_defect']) || !empty($detail['foto_label']) || !empty($detail['foto_bon'])): ?>
    <div class="detail-section">
      <h4>Foto's</h4>
      <div class="fotos-wrap">
        <?php foreach (['foto_defect' => "Defect", 'foto_label' => "Label", 'foto_bon' => "Aankoopbon"] as $fk => $fl): ?>
          <?php if (!empty($detail[$fk])): ?>
          <div class="foto-item">
            <a href="<?= BASE_URL ?>/<?= h($detail[$fk]) ?>" target="_blank">
              <img src="<?= BASE_URL ?>/<?= h($detail[$fk]) ?>" alt="<?= $fl ?>" class="foto-img" loading="lazy">
            </a>
            <div class="foto-lbl"><?= $fl ?></div>
          </div>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Berichten & acties -->
    <div class="detail-section berichten-sectie">
      <div class="berichten-kolommen">

        <!-- Links: activiteitenlog -->
        <div class="berichten-overzicht">
          <h4>Activiteitenlog</h4>
          <?php if (empty($detail['log'])): ?>
            <p class="log-leeg">Nog geen activiteit geregistreerd.</p>
          <?php else: ?>
          <div class="log-lijst">
            <?php foreach (array_reverse($detail['log']) as $lg): ?>
            <div class="log-item">
              <span class="log-time"><?= h(substr($lg['aangemaakt'] ?? '', 0, 16)) ?></span>
              <span class="log-tekst">
                <?= h($lg['actie'] ?? '') ?>
                <?php if (!empty($lg['opmerking'])): ?>
                  <small><?= h($lg['opmerking']) ?></small>
                <?php endif; ?>
              </span>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>

        <!-- Rechts: acties -->
        <div class="bericht-sturen">

          <!-- Bericht sturen -->
          <h4>Bericht sturen aan klant</h4>
          <form method="POST">
            <input type="hidden" name="csrf"        value="<?= csrf() ?>">
            <input type="hidden" name="action"      value="bericht">
            <input type="hidden" name="aanvraag_id" value="<?= (int)$detail['id'] ?>">
            <textarea name="bericht" class="opmerking-field" placeholder="Typ hier uw bericht aan de klant…"></textarea>
            <div class="bericht-footer">
              <button type="submit" class="btn btn-primary btn-sm">Verzenden</button>
            </div>
          </form>

          <!-- Aanvraagtype toekennen (gekleurde buttons) -->
          <div class="actie-separator"><span>Aanvraagtype</span></div>
          <p class="actie-info">
            Huidig type: <strong><?= $huidigType ? h($aanvraagTypes[$huidigType]['label'] ?? $huidigType) : '— niet ingesteld —' ?></strong>
          </p>
          <div class="aanvraagtype-buttons">
            <?php foreach ($aanvraagTypes as $typeSlug => $typeInfo): ?>
            <form method="POST" style="margin:0;">
              <input type="hidden" name="csrf"          value="<?= csrf() ?>">
              <input type="hidden" name="action"        value="set_type">
              <input type="hidden" name="aanvraag_id"   value="<?= (int)$detail['id'] ?>">
              <input type="hidden" name="aanvraag_type" value="<?= h($typeSlug) ?>">
              <button type="submit"
                class="btn-type btn-type-<?= h($typeSlug) ?><?= ($huidigType === $typeSlug) ? ' active-type' : '' ?>">
                <?= h($typeInfo['label']) ?>
              </button>
            </form>
            <?php endforeach; ?>
          </div>

          <?php if ($huidigType): ?>
          <!-- Type wijzigen via selectmenu (detail) -->
          <div class="actie-separator"><span>Type wijzigen via menu</span></div>
          <form method="POST" style="display:flex;align-items:center;flex-wrap:wrap;gap:.4rem;">
            <input type="hidden" name="csrf"        value="<?= csrf() ?>">
            <input type="hidden" name="action"      value="set_type">
            <input type="hidden" name="aanvraag_id" value="<?= (int)$detail['id'] ?>">
            <div class="type-select-wrap" style="display:contents;">
              <select name="aanvraag_type">
                <?php foreach ($aanvraagTypes as $ts => $ti): ?>
                  <option value="<?= h($ts) ?>" <?= $huidigType === $ts ? 'selected' : '' ?>>
                    <?= h($ti['label']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <button type="submit">Wijzigen</button>
            </div>
          </form>
          <?php endif; ?>

          <!-- Status wijzigen -->
          <div class="actie-separator"><span>Status wijzigen</span></div>
          <p class="actie-info">Huidige status: <strong><?= h($sl['tekst']) ?></strong></p>
          <div class="actie-knoppen">
            <form method="POST" style="margin:0;">
              <input type="hidden" name="csrf"         value="<?= csrf() ?>">
              <input type="hidden" name="action"       value="status">
              <input type="hidden" name="aanvraag_id"  value="<?= (int)$detail['id'] ?>">
              <input type="hidden" name="nieuw_status" value="doorgestuurd">
              <button type="submit" class="btn-actie btn-coulance">Aanvulling nodig</button>
            </form>
            <form method="POST" style="margin:0;">
              <input type="hidden" name="csrf"         value="<?= csrf() ?>">
              <input type="hidden" name="action"       value="status">
              <input type="hidden" name="aanvraag_id"  value="<?= (int)$detail['id'] ?>">
              <input type="hidden" name="nieuw_status" value="behandeld">
              <button type="submit" class="btn-actie btn-behandeld">Behandeld</button>
            </form>
            <form method="POST" style="margin:0;">
              <input type="hidden" name="csrf"         value="<?= csrf() ?>">
              <input type="hidden" name="action"       value="status">
              <input type="hidden" name="aanvraag_id"  value="<?= (int)$detail['id'] ?>">
              <input type="hidden" name="nieuw_status" value="archief">
              <button type="submit" class="btn-actie btn-archief">Archiveren</button>
            </form>
          </div>

        </div><!-- /.bericht-sturen -->
      </div><!-- /.berichten-kolommen -->
    </div><!-- /.berichten-sectie -->

  </div><!-- /.detail-card -->

  <?php else: ?>

  <!-- ═══════════════════════════════
       Lijst: overzicht aanvragen
  ═══════════════════════════════ -->
  <div class="filter-bar">
    <form method="GET" id="filter-form" style="display:contents;">
      <select name="status" onchange="this.form.submit()">
        <option value="">Alle statussen</option>
        <?php foreach ($statusLabels as $val => $lbl): ?>
          <option value="<?= $val ?>" <?= $filterStatus === $val ? 'selected' : '' ?>><?= h($lbl['tekst']) ?></option>
        <?php endforeach; ?>
      </select>
      <input type="text" name="zoek" placeholder="Zoek op e-mail, merk, model, casenr…" value="<?= h($filterZoek) ?>">
      <button type="submit">Zoeken</button>
      <?php if ($filterStatus || $filterZoek || $filterRoute): ?>
        <a href="?" class="btn btn-sm btn-secondary">Wis filters</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="admin-card">
    <h2><?= count($aanvragen) ?> aanvragen</h2>
    <?php if (empty($aanvragen)): ?>
      <p style="color:#94a3b8;padding:1rem 0;">Geen aanvragen gevonden.</p>
    <?php else: ?>
    <table class="admin-table">
      <thead>
        <tr>
          <th>Casenr.</th>
          <th>E-mail</th>
          <th>Merk / Model</th>
          <th>Route</th>
          <th>Type</th>
          <th>Status</th>
          <th>Datum</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($aanvragen as $r):
        $sl        = $statusLabels[$r['status']] ?? ['tekst' => $r['status'], 'badge' => 'badge-gray'];
        $rType     = $r['aanvraag_type'] ?? $r['advies_type'] ?? '';
        $rTypeInfo = $aanvraagTypes[$rType] ?? null;
        $qs        = http_build_query(array_filter(['status' => $filterStatus, 'route' => $filterRoute, 'zoek' => $filterZoek]));
      ?>
      <tr>
        <td class="casenr-col">
          <a href="?id=<?= $r['id'] ?><?= $qs ? '&'.$qs : '' ?>">
            <?= h($r['casenummer'] ?? '#'.$r['id']) ?>
          </a>
        </td>
        <td style="font-size:.85rem;"><?= h($r['email'] ?? '—') ?></td>
        <td style="font-size:.85rem;"><?= h(($r['merk']??'').' '.($r['modelnummer']??'')) ?></td>
        <td style="font-size:.82rem;color:#64748b;"><?= h($r['geadviseerde_route'] ?? $r['advies_type'] ?? '—') ?></td>
        <td>
          <?php if ($rTypeInfo): ?>
            <span style="display:inline-flex;align-items:center;gap:.35rem;font-size:.78rem;font-weight:700;
              background:<?= h($rTypeInfo['kleur']) ?>;color:<?= h($rTypeInfo['tekst']) ?>;
              padding:.2rem .55rem;border-radius:6px;">
              <?= h($rTypeInfo['label']) ?>
            </span>
          <?php else: ?>
            <span style="font-size:.78rem;color:#94a3b8;">—</span>
          <?php endif; ?>
        </td>
        <td><span class="badge <?= $sl['badge'] ?>"><?= h($sl['tekst']) ?></span></td>
        <td style="font-size:.8rem;color:#94a3b8;"><?= h($r[$datumKolom] ?? '—') ?></td>
        <td>
          <div class="optiemenu-wrap">
            <button type="button" class="optiemenu-btn" onclick="toggleOptiemenu(this)" aria-label="Opties">
              <span></span><span></span><span></span>
            </button>
            <div class="optiemenu-dropdown">
              <div class="optiemenu-header">Acties</div>
              <a href="?id=<?= $r['id'] ?><?= $qs ? '&'.$qs : '' ?>"
                 class="optiemenu-item">&#128065; Openen</a>
              <hr class="optiemenu-divider">
              <div class="optiemenu-header" style="padding-top:.35rem;">Aanvraagtype</div>
              <?php foreach ($aanvraagTypes as $ts => $ti): ?>
              <form method="POST" style="margin:0;">
                <input type="hidden" name="csrf"          value="<?= csrf() ?>">
                <input type="hidden" name="action"        value="set_type_lijst">
                <input type="hidden" name="aanvraag_id"   value="<?= (int)$r['id'] ?>">
                <input type="hidden" name="aanvraag_type" value="<?= h($ts) ?>">
                <button type="submit" class="optiemenu-item<?= $rType === $ts ? ' active-type' : '' ?>">
                  <span class="optiemenu-type-dot" style="background:<?= h($ti['kleur']) ?>;"></span>
                  <?= h($ti['label']) ?>
                  <?= $rType === $ts ? ' ✓' : '' ?>
                </button>
              </form>
              <?php endforeach; ?>
              <hr class="optiemenu-divider">
              <div class="optiemenu-header" style="padding-top:.35rem;">Status</div>
              <?php foreach ($statusLabels as $sv => $si): ?>
              <form method="POST" style="margin:0;">
                <input type="hidden" name="csrf"         value="<?= csrf() ?>">
                <input type="hidden" name="action"       value="status">
                <input type="hidden" name="aanvraag_id"  value="<?= (int)$r['id'] ?>">
                <input type="hidden" name="nieuw_status" value="<?= h($sv) ?>">
                <button type="submit" class="optiemenu-item<?= $r['status'] === $sv ? '' : '' ?>">
                  <?= h($si['tekst']) ?>
                  <?= $r['status'] === $sv ? ' ✓' : '' ?>
                </button>
              </form>
              <?php endforeach; ?>
            </div>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

  <?php endif; ?>

</div><!-- /.adm-page -->

<script>
function toggleOptiemenu(btn) {
  const wrap   = btn.closest('.optiemenu-wrap');
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