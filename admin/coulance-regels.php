<?php
/**
 * admin/coulance-regels.php
 * Beheer van shops (coulance/garantie) en merk support-URLs.
 */
session_start();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

// ── DB-migraties ──────────────────────────────────────────────────────────
try {
    db()->exec("
        CREATE TABLE IF NOT EXISTS coulance_shops (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            naam        VARCHAR(200) NOT NULL,
            support_url VARCHAR(500) NOT NULL DEFAULT '',
            actief      TINYINT(1)  NOT NULL DEFAULT 1,
            volgorde    INT         NOT NULL DEFAULT 0,
            aangemaakt  TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
} catch (\Exception $e) {}

// Voeg support_url kolom toe aan tv_modellen als die nog niet bestaat
try {
    $cols = db()->query("SHOW COLUMNS FROM tv_modellen LIKE 'support_url'")->fetchAll();
    if (empty($cols)) {
        db()->exec("ALTER TABLE tv_modellen ADD COLUMN support_url VARCHAR(500) NOT NULL DEFAULT '' AFTER slug");
    }
} catch (\Exception $e) {}

$msg  = '';
$type = 'success';

// ── Acties: shops ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $msg = 'Beveiligingstoken ongeldig.'; $type = 'error';
    } else {
        $act = $_POST['action'];

        if ($act === 'add_shop') {
            $naam = trim($_POST['shop_naam'] ?? '');
            $url  = trim($_POST['shop_url']  ?? '');
            if (!$naam) { $msg = 'Naam is verplicht.'; $type = 'error'; }
            else {
                $cnt = (int) db()->query("SELECT COUNT(*) FROM coulance_shops")->fetchColumn();
                if ($cnt >= 30) { $msg = 'Maximum van 30 shops bereikt.'; $type = 'error'; }
                else {
                    $max_vol = (int) db()->query("SELECT COALESCE(MAX(volgorde),0) FROM coulance_shops")->fetchColumn();
                    db()->prepare("INSERT INTO coulance_shops (naam, support_url, actief, volgorde) VALUES (?,?,1,?)")
                        ->execute([$naam, $url, $max_vol + 1]);
                    $msg = "Shop '{$naam}' toegevoegd.";
                }
            }
        }

        if ($act === 'update_shop') {
            $id   = (int)  ($_POST['shop_id']   ?? 0);
            $naam = trim(  $_POST['shop_naam']  ?? '');
            $url  = trim(  $_POST['shop_url']   ?? '');
            $act2 = (int)  ($_POST['shop_actief'] ?? 0);
            $vol  = (int)  ($_POST['shop_volgorde'] ?? 0);
            if (!$id || !$naam) { $msg = 'Ongeldig verzoek.'; $type = 'error'; }
            else {
                db()->prepare("UPDATE coulance_shops SET naam=?, support_url=?, actief=?, volgorde=? WHERE id=?")
                    ->execute([$naam, $url, $act2, $vol, $id]);
                $msg = 'Shop bijgewerkt.';
            }
        }

        if ($act === 'delete_shop') {
            $id = (int) ($_POST['shop_id'] ?? 0);
            if ($id) {
                db()->prepare("DELETE FROM coulance_shops WHERE id=?")->execute([$id]);
                $msg = 'Shop verwijderd.';
            }
        }

        if ($act === 'save_merk_urls') {
            $merken = $_POST['merk_url'] ?? [];
            foreach ($merken as $merk => $url) {
                $merk = trim($merk);
                $url  = trim($url);
                if ($merk) {
                    db()->prepare("UPDATE tv_modellen SET support_url=? WHERE merk=?")
                        ->execute([$url, $merk]);
                }
            }
            $msg = 'Merk support-links opgeslagen.';
        }
    }
}

// ── Data ophalen ──────────────────────────────────────────────────────────
$shops = [];
try {
    $shops = db()->query("SELECT * FROM coulance_shops ORDER BY volgorde, naam")->fetchAll();
} catch (\Exception $e) {}

$merken = [];
try {
    $merken = db()->query("SELECT DISTINCT merk, COALESCE(MAX(support_url),'') AS support_url FROM tv_modellen GROUP BY merk ORDER BY merk")->fetchAll();
} catch (\Exception $e) {}

$shopCount = count($shops);

$adminActivePage = 'coulance-regels';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Coulance Regels &ndash; Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Epilogue:wght@700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/base.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/components.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
  <meta name="robots" content="noindex,nofollow">
  <style>
    .cr-tabs    { display:flex; gap:.4rem; border-bottom:2px solid var(--adm-border); margin-bottom:1.5rem; padding-bottom:.75rem; }
    .cr-tab     { background:none; border:none; font-family:var(--adm-font); font-size:.875rem; font-weight:600; color:var(--adm-muted); padding:.35rem .85rem; border-radius:6px; cursor:pointer; transition:background .15s,color .15s; }
    .cr-tab:hover { color:var(--adm-ink); background:var(--adm-nav-hover); }
    .cr-tab.active { color:var(--adm-accent); background:var(--adm-accent-light); }
    .cr-panel   { display:none; }
    .cr-panel.active { display:block; }

    .shop-row   { display:grid; grid-template-columns:1fr 1fr 80px 70px auto; gap:.6rem; align-items:center; background:var(--adm-surface-2); border:1px solid var(--adm-border); border-radius:var(--adm-radius-lg); padding:.75rem 1rem; margin-bottom:.5rem; }
    .shop-row input[type=text]  { width:100%; padding:.4rem .65rem; border:1.5px solid var(--adm-border); border-radius:var(--adm-radius); font-size:.83rem; font-family:var(--adm-font); color:var(--adm-ink); background:var(--adm-surface); }
    .shop-row input[type=number]{ width:70px; padding:.4rem .5rem; border:1.5px solid var(--adm-border); border-radius:var(--adm-radius); font-size:.83rem; font-family:var(--adm-font); }
    .shop-row input:focus       { outline:none; border-color:var(--adm-accent); }
    .shop-check-label { display:flex; align-items:center; gap:.3rem; font-size:.8rem; color:var(--adm-text); cursor:pointer; white-space:nowrap; }
    .shop-check-label input { width:15px; height:15px; accent-color:var(--adm-accent); }

    .add-shop-form { background:var(--adm-surface); border:1.5px dashed var(--adm-border); border-radius:var(--adm-radius-lg); padding:1rem 1.1rem; margin-top:1rem; }
    .add-shop-form h4 { font-size:.85rem; font-weight:700; color:var(--adm-ink); margin-bottom:.75rem; }
    .add-shop-grid { display:grid; grid-template-columns:1fr 1fr; gap:.6rem; }
    @media(max-width:640px) { .shop-row { grid-template-columns:1fr; } .add-shop-grid { grid-template-columns:1fr; } }

    .merk-row { display:grid; grid-template-columns:140px 1fr; gap:.75rem; align-items:center; border-bottom:1px solid #f3f2ef; padding:.5rem 0; }
    .merk-row:last-child { border-bottom:none; }
    .merk-naam { font-size:.85rem; font-weight:600; color:var(--adm-ink); }
    .merk-row input[type=text] { width:100%; padding:.4rem .65rem; border:1.5px solid var(--adm-border); border-radius:var(--adm-radius); font-size:.83rem; font-family:var(--adm-font); color:var(--adm-ink); background:var(--adm-surface); }
    .merk-row input:focus { outline:none; border-color:var(--adm-accent); }

    .shop-quota { font-size:.78rem; color:var(--adm-muted); margin-bottom:.85rem; }
    .shop-quota strong { color:var(--adm-ink); }
  </style>
</head>
<body>
<?php require_once __DIR__ . '/includes/admin-header.php'; ?>

<div class="adm-page">

  <h1 class="adm-page-title">&#129309; Coulance &amp; Garantie Regels</h1>
  <p class="adm-page-subtitle">
    Beheer shops met support-links (gebruikt bij garantie- en coulance-advies) en stel per merk een support-URL in.
  </p>

  <?php if ($msg): ?>
  <div class="alert alert-<?= $type ?>"><?= h($msg) ?></div>
  <?php endif; ?>

  <div class="cr-tabs">
    <button class="cr-tab active" onclick="crTab('shops')">&#127978; Shops</button>
    <button class="cr-tab" onclick="crTab('merken')">&#127981; Merk support-links</button>
  </div>

  <!-- ══ SHOPS TAB ════════════════════════════════════════════════════ -->
  <div class="cr-panel active" id="cr-shops">
    <div class="admin-card">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.75rem;gap:.5rem;flex-wrap:wrap;">
        <h2 style="margin:0;">Shops / verkoopkanalen</h2>
        <span class="shop-quota"><strong><?= $shopCount ?></strong> / 30 shops</span>
      </div>
      <p style="font-size:.8rem;color:var(--adm-muted);margin-bottom:1rem;">
        Shops worden getoond wanneer een TV onder garantie of coulance valt, zodat de klant direct naar de juiste support-pagina kan.
        Maximum 30 shops.
      </p>

      <?php if (empty($shops)): ?>
        <p style="color:var(--adm-muted);font-size:.875rem;">Nog geen shops toegevoegd.</p>
      <?php else: ?>
        <?php foreach ($shops as $shop): ?>
        <form method="POST">
          <input type="hidden" name="action"        value="update_shop">
          <input type="hidden" name="csrf_token"    value="<?= csrf() ?>">
          <input type="hidden" name="shop_id"       value="<?= (int)$shop['id'] ?>">
          <div class="shop-row">
            <input type="text"   name="shop_naam"     value="<?= h($shop['naam']) ?>"        placeholder="Naam shop"          required>
            <input type="text"   name="shop_url"      value="<?= h($shop['support_url']) ?>" placeholder="https://…/support" >
            <label class="shop-check-label">
              <input type="checkbox" name="shop_actief" value="1" <?= $shop['actief'] ? 'checked' : '' ?>>
              Actief
            </label>
            <input type="number" name="shop_volgorde" value="<?= (int)$shop['volgorde'] ?>" min="0" max="999" placeholder="Volgorde">
            <div style="display:flex;gap:.4rem;flex-wrap:nowrap;">
              <button type="submit" class="btn btn-primary btn-sm">Opslaan</button>
              <button type="button" class="btn btn-danger btn-sm"
                onclick="if(confirm('Shop \'<?= h(addslashes($shop['naam'])) ?>\' verwijderen?')){
                  this.closest('form').querySelector('[name=action]').value='delete_shop';
                  this.closest('form').submit();}">&#10005;</button>
            </div>
          </div>
        </form>
        <?php endforeach; ?>
      <?php endif; ?>

      <?php if ($shopCount < 30): ?>
      <div class="add-shop-form">
        <h4>&#43; Nieuwe shop toevoegen</h4>
        <form method="POST">
          <input type="hidden" name="action"     value="add_shop">
          <input type="hidden" name="csrf_token" value="<?= csrf() ?>">
          <div class="add-shop-grid">
            <div>
              <label class="field-label" style="font-size:.75rem;font-weight:600;color:var(--adm-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:.3rem;display:block;">
                Naam *
              </label>
              <input type="text" name="shop_naam" placeholder="Bijv. MediaMarkt" required
                     style="width:100%;padding:.45rem .7rem;border:1.5px solid var(--adm-border);border-radius:var(--adm-radius);font-size:.875rem;font-family:var(--adm-font);">
            </div>
            <div>
              <label class="field-label" style="font-size:.75rem;font-weight:600;color:var(--adm-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:.3rem;display:block;">
                Support-URL
              </label>
              <input type="text" name="shop_url" placeholder="https://www.mediamarkt.nl/service"
                     style="width:100%;padding:.45rem .7rem;border:1.5px solid var(--adm-border);border-radius:var(--adm-radius);font-size:.875rem;font-family:var(--adm-font);">
            </div>
          </div>
          <div style="margin-top:.75rem;">
            <button type="submit" class="btn btn-primary">&#43; Toevoegen</button>
          </div>
        </form>
      </div>
      <?php else: ?>
      <div class="alert alert-warning" style="margin-top:.75rem;">Maximum van 30 shops bereikt.</div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ══ MERK SUPPORT-LINKS TAB ══════════════════════════════════════ -->
  <div class="cr-panel" id="cr-merken">
    <div class="admin-card">
      <h2>Merk support-links</h2>
      <p style="font-size:.8rem;color:var(--adm-muted);margin-bottom:1.25rem;">
        Stel per merk een directe support-URL in. Deze link wordt getoond bij garantie-advies naast de shop-opties.
        Laat leeg om geen merk-link te tonen.
      </p>
      <?php if (empty($merken)): ?>
        <p style="color:var(--adm-muted);font-size:.875rem;">Geen merken gevonden in de database.</p>
      <?php else: ?>
      <form method="POST">
        <input type="hidden" name="action"     value="save_merk_urls">
        <input type="hidden" name="csrf_token" value="<?= csrf() ?>">
        <div style="background:var(--adm-surface);border:1px solid var(--adm-border);border-radius:var(--adm-radius-lg);overflow:hidden;">
          <?php foreach ($merken as $mr): ?>
          <div class="merk-row" style="padding:.6rem 1.1rem;">
            <div class="merk-naam"><?= h($mr['merk']) ?></div>
            <input type="text"
                   name="merk_url[<?= h($mr['merk']) ?>]"
                   value="<?= h($mr['support_url']) ?>"
                   placeholder="https://www.<?= h(strtolower($mr['merk'])) ?>.com/support">
          </div>
          <?php endforeach; ?>
        </div>
        <div style="margin-top:1rem;">
          <button type="submit" class="btn btn-primary">&#128190; Support-links opslaan</button>
        </div>
      </form>
      <?php endif; ?>
    </div>
  </div>

</div><!-- /.adm-page -->

<script>
function crTab(id) {
  document.querySelectorAll('.cr-tab').forEach((t, i) => {
    const panels = ['shops','merken'];
    t.classList.toggle('active', panels[i] === id);
  });
  document.querySelectorAll('.cr-panel').forEach(p => {
    p.classList.toggle('active', p.id === 'cr-' + id);
  });
}
</script>
</body>
</html>
