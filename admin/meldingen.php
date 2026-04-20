<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

// ── SQL migratie: kolommen toevoegen indien nog niet aanwezig ─────
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
    $ids = array_map('intval', (array)$_POST['selectie']);
    $ids = array_filter($ids);
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $bulk = $_POST['bulk_actie'];
        try {
            if ($bulk === 'gelezen') {
                db()->prepare("UPDATE aanvragen_log SET gelezen=1 WHERE id IN ($placeholders)")->execute($ids);
            } elseif ($bulk === 'ongelezen') {
                db()->prepare("UPDATE aanvragen_log SET gelezen=0 WHERE id IN ($placeholders)")->execute($ids);
            } elseif ($bulk === 'archiveren') {
                db()->prepare("UPDATE aanvragen_log SET gearchiveerd=1 WHERE id IN ($placeholders)")->execute($ids);
            } elseif ($bulk === 'dearchiveren') {
                db()->prepare("UPDATE aanvragen_log SET gearchiveerd=0 WHERE id IN ($placeholders)")->execute($ids);
            }
        } catch (\Exception $e) {}
    }
    // redirect om dubbelposting te voorkomen
    $qs = http_build_query(array_filter([
        'type'    => $_GET['type']    ?? '',
        'door'    => $_GET['door']    ?? '',
        'zoek'    => $_GET['zoek']    ?? '',
        'archief' => $_GET['archief'] ?? '',
    ]));
    header('Location: ' . BASE_URL . '/admin/meldingen.php' . ($qs ? '?'.$qs : ''));
    exit;
}

// ── Markeer melding als gelezen via GET ───────────────────────────
if (isset($_GET['lees']) && is_numeric($_GET['lees'])) {
    try {
        db()->prepare('UPDATE aanvragen_log SET gelezen=1 WHERE id=?')->execute([(int)$_GET['lees']]);
    } catch (\Exception $e) {}
}

// ── Filterparameters ──────────────────────────────────────────────
$filterType    = trim($_GET['type']    ?? '');
$filterDoor    = trim($_GET['door']    ?? '');
$filterZoek    = trim($_GET['zoek']    ?? '');
$filterArchief = isset($_GET['archief']) && $_GET['archief'] === '1';

// ── Auto-archiveer meldingen ouder dan x dagen ────────────────────
try {
    db()->prepare(
        "UPDATE aanvragen_log SET gearchiveerd=1
         WHERE gearchiveerd=0 AND aangemaakt < DATE_SUB(NOW(), INTERVAL ? DAY)"
    )->execute([$archief_dagen]);
} catch (\Exception $e) {}

// ── Query opbouwen ────────────────────────────────────────────────
$where  = ['1=1'];
$params = [];

// Archief filter
if ($filterArchief) {
    $where[] = 'l.gearchiveerd = 1';
} else {
    $where[] = 'l.gearchiveerd = 0';
}

if ($filterType === 'bericht')       { $where[] = "l.actie LIKE 'Bericht%'"; }
elseif ($filterType === 'inzending') { $where[] = "l.actie LIKE 'Inzending%'"; }
elseif ($filterType === 'doorgestuurd') { $where[] = "l.actie LIKE 'Doorgestuurd%'"; }
elseif ($filterType === 'ingediend') { $where[] = "l.actie LIKE '%ingediend%'"; }
elseif ($filterType === 'status')    {
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
        ORDER BY l.gelezen ASC, l.aangemaakt DESC
        LIMIT 300';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$meldingen = $stmt->fetchAll();

// ── Aantallen voor tabs (niet-gearchiveerd) ───────────────────────
$typeCondities = [
    'bericht'      => "actie LIKE 'Bericht%'",
    'inzending'    => "actie LIKE 'Inzending%'",
    'doorgestuurd' => "actie LIKE 'Doorgestuurd%'",
    'ingediend'    => "actie LIKE '%ingediend%'",
];
$aantallen = ['alle' => 0];
try {
    $aantallen['alle'] = (int) db()->query('SELECT COUNT(*) FROM aanvragen_log WHERE gearchiveerd=0')->fetchColumn();
    foreach ($typeCondities as $k => $cond) {
        $aantallen[$k] = (int) db()->query("SELECT COUNT(*) FROM aanvragen_log WHERE gearchiveerd=0 AND $cond")->fetchColumn();
    }
    $aantallen['status'] = $aantallen['alle']
        - ($aantallen['bericht'] + $aantallen['inzending'] + $aantallen['doorgestuurd'] + $aantallen['ingediend']);
} catch (\Exception $e) {}

// Ongelezen teller voor menu
$ongelezen_totaal = 0;
try {
    $ongelezen_totaal = (int) db()->query('SELECT COUNT(*) FROM aanvragen_log WHERE gelezen=0 AND gearchiveerd=0')->fetchColumn();
} catch (\Exception $e) {}

// Archief teller
$archief_totaal = 0;
try {
    $archief_totaal = (int) db()->query('SELECT COUNT(*) FROM aanvragen_log WHERE gearchiveerd=1')->fetchColumn();
} catch (\Exception $e) {}

// ── Helper functies ───────────────────────────────────────────────
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
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin-meldingen.css">
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
    <a href="<?= BASE_URL ?>/admin/meldingen.php" class="active">
      <span class="icon">&#128276;</span> Meldingen
      <?php if ($ongelezen_totaal > 0): ?>
        <span class="sidebar-badge"><?= $ongelezen_totaal ?></span>
      <?php endif; ?>
    </a>
    <a href="<?= BASE_URL ?>/admin/modellen.php"><span class="icon">&#128250;</span> TV Modellen</a>
    <a href="<?= BASE_URL ?>/admin/klachten.php"><span class="icon">&#9888;</span> Klachten</a>
    <a href="<?= BASE_URL ?>/admin/advies-instellingen.php"><span class="icon">&#9881;</span> Advies instellingen</a>
    <a href="<?= BASE_URL ?>/admin/mailtemplates.php"><span class="icon">&#128140;</span> Mailtemplates</a>
    <a href="<?= BASE_URL ?>/admin/admins.php"><span class="icon">&#128100;</span> Admin accounts</a>
    <a href="<?= BASE_URL ?>/" target="_blank"><span class="icon">&#127760;</span> Website bekijken</a>
  </div>

  <div class="admin-content">
    <div class="meldingen-header">
      <h1>Meldingen</h1>
      <button class="btn btn-secondary btn-sm" onclick="toggleInstellingen()">&#9881; Instellingen</button>
    </div>

    <!-- ── Instellingen panel ─────────────────────────────────── -->
    <div class="instellingen-panel" id="instellingenPanel" style="display:none;">
      <div class="admin-card" style="margin-bottom:1rem;">
        <h4 style="margin-bottom:.75rem;">Archief instellingen</h4>
        <form method="POST">
          <input type="hidden" name="sla_instellingen" value="1" />
          <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;">
            <label style="font-size:.875rem;color:#374151;">
              Meldingen automatisch archiveren na
              <input type="number" name="archief_dagen" value="<?= (int)$archief_dagen ?>" min="1" max="365"
                style="width:70px;padding:.35rem .5rem;border:1.5px solid #d1d5db;border-radius:6px;font-size:.875rem;margin:0 .35rem;" />
              dagen
            </label>
            <button type="submit" class="btn btn-secondary btn-sm">Opslaan</button>
          </div>
          <p style="font-size:.78rem;color:#94a3b8;margin-top:.4rem;">
            Meldingen ouder dan <?= (int)$archief_dagen ?> dagen worden automatisch naar het archief verplaatst.
          </p>
        </form>
      </div>
    </div>

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
          $active = (!$filterArchief && $filterType === $key) ? 'active' : '';
          $url = '?' . http_build_query(array_filter(['type' => $key, 'door' => $filterDoor, 'zoek' => $filterZoek]));
      ?>
        <a href="<?= $url ?>" class="m-tab <?= $active ?>">
          <?= h($tab['label']) ?>
          <span class="m-tab-count"><?= $tab['count'] ?></span>
        </a>
      <?php endforeach; ?>
      <!-- Archief tab -->
      <?php $archActive = $filterArchief ? 'active' : ''; ?>
      <a href="?archief=1" class="m-tab m-tab-archief <?= $archActive ?>">
        &#128193; Archief
        <?php if ($archief_totaal > 0): ?>
          <span class="m-tab-count"><?= $archief_totaal ?></span>
        <?php endif; ?>
      </a>
    </div>

    <!-- ── Filterbar ─────────────────────────────────────────── -->
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

    <!-- ── Bulk acties ────────────────────────────────────────── -->
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
        <button type="submit" class="btn btn-secondary btn-sm" onclick="return bevestigBulk()">Uitvoeren</button>
        <button type="button" class="btn btn-secondary btn-sm" onclick="deselecteerAlles()">&#10005; Selectie wissen</button>
      </div>

      <!-- ── Resultaten ────────────────────────────────────────── -->
      <div class="admin-card" style="padding:0;overflow:hidden;">
        <?php if (empty($meldingen)): ?>
          <p style="padding:2rem;text-align:center;color:#94a3b8;font-size:.9rem;">
            <?= $filterArchief ? 'Geen gearchiveerde meldingen.' : 'Geen meldingen gevonden.' ?>
          </p>
        <?php else: ?>
          <table class="m-tabel">
            <thead>
              <tr>
                <th class="m-check-col">
                  <input type="checkbox" id="selectAll" title="Alles selecteren" />
                </th>
                <th>Datum</th>
                <th>Type</th>
                <th>Casenummer</th>
                <th>Apparaat</th>
                <th>Actie / Bericht</th>
                <th>Door</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($meldingen as $m):
                $type    = melding_type($m['actie']);
                $gelezen = (bool)($m['gelezen'] ?? false);
                $rowClass = $gelezen ? '' : 'rij-ongelezen';
            ?>
              <tr class="<?= $rowClass ?>">
                <td class="m-check-col">
                  <input type="checkbox" name="selectie[]" value="<?= (int)$m['id'] ?>" class="melding-check" />
                </td>
                <td class="m-datum" title="<?= h($m['aangemaakt']) ?>">
                  <?= date('d-m-y', strtotime($m['aangemaakt'])) ?><br>
                  <span><?= date('H:i', strtotime($m['aangemaakt'])) ?></span>
                </td>
                <td>
                  <span class="m-badge <?= melding_type_css($type) ?>"><?= melding_type_label($type) ?></span>
                  <?php if (!$gelezen): ?>
                    <span class="ongelezen-dot" title="Ongelezen"></span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($m['aanvraag_id_link']): ?>
                    <a href="<?= BASE_URL ?>/admin/meldingen.php?lees=<?= (int)$m['id'] ?>#terug"
                       onclick="window.location='<?= BASE_URL ?>/admin/aanvragen.php?id=<?= (int)$m['aanvraag_id_link'] ?>'; return false;"
                       data-lees="<?= (int)$m['id'] ?>"
                       data-href="<?= BASE_URL ?>/admin/aanvragen.php?id=<?= (int)$m['aanvraag_id_link'] ?>"
                       class="m-case melding-link">
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
                <td>
                  <?php if (!$gelezen): ?>
                    <a href="?lees=<?= (int)$m['id'] ?>&type=<?= h($filterType) ?>&door=<?= h($filterDoor) ?>&zoek=<?= h($filterZoek) ?><?= $filterArchief ? '&archief=1' : '' ?>"
                       class="btn btn-sm btn-secondary" title="Markeer als gelezen">&#10003;</a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
          <div style="padding:.75rem 1.25rem;font-size:.775rem;color:#94a3b8;border-top:1px solid #f1f5f9;">
            <?= count($meldingen) ?> melding<?= count($meldingen) !== 1 ? 'en' : '' ?> getoond (max 300)
            <?php if (!$filterArchief): ?>
              &bull; <strong><?= $ongelezen_totaal ?></strong> ongelezen
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </form><!-- /bulkForm -->

  </div><!-- /.admin-content -->
</div><!-- /.admin-layout -->
</div><!-- /.admin-wrap -->

<script>
// ── Instellingen toggle ───────────────────────────────────────────
function toggleInstellingen() {
  var p = document.getElementById('instellingenPanel');
  p.style.display = p.style.display === 'none' ? 'block' : 'none';
}

// ── Selectie & bulk ───────────────────────────────────────────────
var selectAll   = document.getElementById('selectAll');
var bulkBalk    = document.getElementById('bulkBalk');
var bulkAantal  = document.getElementById('bulkAantal');
var checkboxes  = document.querySelectorAll('.melding-check');

function updateBulkBalk() {
  var aangevinkt = document.querySelectorAll('.melding-check:checked').length;
  bulkAantal.textContent = aangevinkt;
  bulkBalk.style.display = aangevinkt > 0 ? 'flex' : 'none';
}

if (selectAll) {
  selectAll.addEventListener('change', function() {
    checkboxes.forEach(cb => cb.checked = this.checked);
    updateBulkBalk();
  });
}
checkboxes.forEach(cb => cb.addEventListener('change', function() {
  if (!this.checked && selectAll) selectAll.checked = false;
  updateBulkBalk();
}));

function deselecteerAlles() {
  checkboxes.forEach(cb => cb.checked = false);
  if (selectAll) selectAll.checked = false;
  updateBulkBalk();
}

function bevestigBulk() {
  var actie = document.getElementById('bulkActie').value;
  if (!actie) { alert('Kies eerst een actie.'); return false; }
  var n = document.querySelectorAll('.melding-check:checked').length;
  return confirm(n + ' melding(en) – actie: ' + actie + '.\nDoorgaan?');
}

// ── Melding-link: markeer als gelezen via AJAX, ga dan naar aanvraag ──
document.querySelectorAll('.melding-link').forEach(function(link) {
  link.addEventListener('click', function(e) {
    e.preventDefault();
    var lees = this.dataset.lees;
    var href = this.dataset.href;
    if (lees) {
      fetch('<?= BASE_URL ?>/admin/meldingen.php?lees=' + lees, {method:'GET',credentials:'same-origin'})
        .finally(function() { window.location.href = href; });
    } else {
      window.location.href = href;
    }
  });
});
</script>
</body>
</html>