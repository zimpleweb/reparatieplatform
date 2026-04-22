<?php
session_start();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';
requireAdmin();

$successMsg = '';
$errorMsg   = '';

// ── Zorg dat de tabellen bestaan ──────────────────────────────────
try {
    db()->exec("CREATE TABLE IF NOT EXISTS mail_templates (
        id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        slug        VARCHAR(80)  NOT NULL UNIQUE,
        label       VARCHAR(120) NOT NULL,
        richting    ENUM('inzender','admin') NOT NULL DEFAULT 'inzender',
        subject     VARCHAR(255) NOT NULL,
        body_html   MEDIUMTEXT   NOT NULL,
        actief      TINYINT(1)   NOT NULL DEFAULT 1,
        bijgewerkt  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (\PDOException $e) { /* tabel bestaat al of rechten ontbreken */ }

try {
    db()->exec("CREATE TABLE IF NOT EXISTS standaard_berichten (
        id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        titel      VARCHAR(120) NOT NULL,
        tekst      TEXT NOT NULL,
        aangemaakt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (\PDOException $e) { /* tabel bestaat al of rechten ontbreken */ }

// ── Sla standaardtemplates op als ze nog niet in DB staan ─────────
$defaults = [
    ['slug' => 'inzender_bevestiging',       'label' => 'Inzender: Ontvangstbevestiging',    'richting' => 'inzender'],
    ['slug' => 'inzender_advies',            'label' => 'Inzender: Advies klaar',             'richting' => 'inzender'],
    ['slug' => 'admin_nieuwe_inzending',     'label' => 'Admin: Nieuwe inzending',            'richting' => 'admin'],
    ['slug' => 'admin_nieuw_chatbericht',    'label' => 'Admin: Nieuw chatbericht',           'richting' => 'admin'],
    ['slug' => 'admin_formulier_ingevuld',   'label' => 'Admin: Formulier ingevuld door klant','richting' => 'admin'],
    ['slug' => 'inzender_nieuw_chatbericht', 'label' => 'Inzender: Nieuw chatbericht',        'richting' => 'inzender'],
    ['slug' => 'inzender_status_gewijzigd',  'label' => 'Inzender: Status gewijzigd',         'richting' => 'inzender'],
];
foreach ($defaults as $d) {
    $check = db()->prepare('SELECT subject, body_html FROM mail_templates WHERE slug = ?');
    $check->execute([$d['slug']]);
    $row = $check->fetch(PDO::FETCH_ASSOC);
    $tpl = mailTemplateDefault($d['slug']);
    if (!$row) {
        db()->prepare('INSERT INTO mail_templates (slug, label, richting, subject, body_html) VALUES (?, ?, ?, ?, ?)')
             ->execute([$d['slug'], $d['label'], $d['richting'], $tpl['subject'], $tpl['body_html']]);
    } elseif (empty($row['subject']) || empty($row['body_html'])) {
        db()->prepare('UPDATE mail_templates SET subject = ?, body_html = ? WHERE slug = ?')
             ->execute([$tpl['subject'], $tpl['body_html'], $d['slug']]);
    }
}

// ── Opslaan / bijwerken ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        $errorMsg = 'Ongeldig verzoek.';
    } else {
        $slug     = trim($_POST['slug'] ?? '');
        $subject  = trim($_POST['subject'] ?? '');
        $bodyHtml = $_POST['body_html'] ?? '';
        $actief   = isset($_POST['actief']) ? 1 : 0;

        if (!$slug || !$subject || !$bodyHtml) {
            $errorMsg = 'Onderwerp en inhoud zijn verplicht.';
        } else {
            $check = db()->prepare('SELECT COUNT(*) FROM mail_templates WHERE slug = ?');
            $check->execute([$slug]);
            if ($check->fetchColumn() > 0) {
                db()->prepare('UPDATE mail_templates SET subject = ?, body_html = ?, actief = ? WHERE slug = ?')
                     ->execute([$subject, $bodyHtml, $actief, $slug]);
            } else {
                db()->prepare('INSERT INTO mail_templates (slug, label, richting, subject, body_html, actief) VALUES (?, ?, ?, ?, ?, ?)')
                     ->execute([$slug, $slug, 'inzender', $subject, $bodyHtml, $actief]);
            }
            $successMsg = 'Template opgeslagen.';
        }
    }
}

// ── Teruggezetten naar standaard ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        $errorMsg = 'Ongeldig verzoek.';
    } else {
        $slug = trim($_POST['slug'] ?? '');
        $tpl  = mailTemplateDefault($slug);
        if ($tpl['subject'] !== 'Bericht van ReparatiePlatform.nl') {
            db()->prepare('UPDATE mail_templates SET subject = ?, body_html = ? WHERE slug = ?')
                 ->execute([$tpl['subject'], $tpl['body_html'], $slug]);
            $successMsg = 'Template teruggezet naar standaard.';
        } else {
            $errorMsg = 'Geen standaard beschikbaar voor dit template.';
        }
    }
}

// ── Testmail versturen ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'testmail') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        $errorMsg = 'Ongeldig verzoek.';
    } else {
        $slug   = trim($_POST['slug'] ?? '');
        $testTo = filter_var(trim($_POST['test_email'] ?? ''), FILTER_VALIDATE_EMAIL);
        if (!$testTo) {
            $errorMsg = 'Voer een geldig e-mailadres in voor de testmail.';
        } else {
            $vars = [
                'casenummer'         => '2026-04-TEST',
                'merk'               => 'Samsung',
                'modelnummer'        => 'UE55CU8000',
                'aanschafjaar'       => '2022',
                'geadviseerde_route' => 'Garantie',
                'situatie'           => 'storing',
                'klacht_type'        => 'geen_beeld',
                'omschrijving'       => 'TV gaat niet meer aan na stroomstoring.',
                'email'              => $testTo,
                'advies_toelichting' => 'Uw televisie valt nog binnen de garantietermijn van 2 jaar.',
                'bericht'            => 'Dit is een testbericht.',
                'chatbericht'        => 'Dit is een testbericht via de chat.',
                'datum_bericht'      => date('d-m-Y H:i'),
                'status_oud'         => 'In behandeling',
                'status_nieuw'       => 'Advies gegeven',
                'toelichting_status' => 'Uw aanvraag is volledig afgehandeld.',
            ];
            $ok = sendMail($testTo, $slug, $vars);
            if ($ok) {
                $successMsg = "Testmail verstuurd naar {$testTo}.";
            } else {
                $errorMsg = 'Versturen mislukt. Controleer de mailconfiguratie van de server.';
            }
        }
    }
}

// ── Standaardberichten: opslaan ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'sb_save') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        $errorMsg = 'Ongeldig verzoek.';
    } else {
        $sbId    = (int)($_POST['sb_id'] ?? 0);
        $sbTitel = trim($_POST['sb_titel'] ?? '');
        $sbTekst = trim($_POST['sb_tekst'] ?? '');
        if (!$sbTitel || !$sbTekst) {
            $errorMsg = 'Titel en tekst zijn verplicht.';
        } elseif ($sbId > 0) {
            db()->prepare('UPDATE standaard_berichten SET titel=?, tekst=? WHERE id=?')
               ->execute([$sbTitel, $sbTekst, $sbId]);
            $successMsg = 'Standaardbericht bijgewerkt.';
        } else {
            db()->prepare('INSERT INTO standaard_berichten (titel, tekst) VALUES (?, ?)')
               ->execute([$sbTitel, $sbTekst]);
            $successMsg = 'Standaardbericht toegevoegd.';
        }
    }
}

// ── Standaardberichten: verwijderen ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'sb_delete') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        $errorMsg = 'Ongeldig verzoek.';
    } else {
        $sbId = (int)($_POST['sb_id'] ?? 0);
        if ($sbId > 0) {
            db()->prepare('DELETE FROM standaard_berichten WHERE id=?')->execute([$sbId]);
            $successMsg = 'Standaardbericht verwijderd.';
        }
    }
}

// ── Templates ophalen ─────────────────────────────────────────────
$templates = db()->query('SELECT * FROM mail_templates ORDER BY richting, slug')->fetchAll();

// ── Standaardberichten ophalen ────────────────────────────────────
$standaardBerichten = [];
try {
    $standaardBerichten = db()->query('SELECT * FROM standaard_berichten ORDER BY id ASC')->fetchAll();
} catch (\PDOException $e) {}
$editSbId = (int)($_GET['editbericht'] ?? 0);
$editSb   = null;
foreach ($standaardBerichten as $sb) {
    if ((int)$sb['id'] === $editSbId) { $editSb = $sb; break; }
}

// Actieve template voor bewerking
$editSlug = $_GET['edit'] ?? ($templates[0]['slug'] ?? '');
$editTpl  = null;
foreach ($templates as $t) {
    if ($t['slug'] === $editSlug) { $editTpl = $t; break; }
}
if (!$editTpl && !empty($templates)) $editTpl = $templates[0];

$adminActivePage = 'mailtemplates';
require_once __DIR__ . '/includes/admin-header.php';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Mailtemplates &ndash; Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Epilogue:wght@700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/base.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/components.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
  <meta name="robots" content="noindex,nofollow">
</head>
<body>
<div class="adm-page">

  <h1 class="adm-page-title">&#128140; Mailtemplates</h1>

  <?php if ($successMsg): ?>
    <div class="alert alert-success">&#10003; <?= h($successMsg) ?></div>
  <?php endif; ?>
  <?php if ($errorMsg): ?>
    <div class="alert alert-error">&#9888; <?= h($errorMsg) ?></div>
  <?php endif; ?>

  <div class="tpl-layout">

    <!-- Lijst van templates -->
    <div class="tpl-list admin-card" style="padding:1rem;">
      <p class="tpl-list-title">Templates</p>
      <?php foreach ($templates as $t): ?>
      <a href="?edit=<?= h(urlencode($t['slug'])) ?>"
         class="tpl-item <?= $t['slug'] === ($editTpl['slug'] ?? '') ? 'active' : '' ?>">
        <?= h($t['label']) ?>
        <span class="tpl-badge tpl-badge-<?= h($t['richting']) ?>"><?= h($t['richting']) ?></span>
        <?php if (!$t['actief']): ?>
          <span class="tpl-badge tpl-badge-off">uit</span>
        <?php endif; ?>
      </a>
      <?php endforeach; ?>
      <div class="tpl-list-footer">
        <p class="tpl-list-note">
          &#128274; Templates worden automatisch aangemaakt bij eerste bezoek. Wijzigingen zijn direct actief.
        </p>
      </div>
    </div>

    <!-- Bewerken -->
    <?php if ($editTpl): ?>
    <div>
      <div class="admin-card">
        <div class="tpl-edit-header">
          <div>
            <h2 class="tpl-edit-title"><?= h($editTpl['label']) ?></h2>
            <p class="tpl-edit-meta">
              Slug: <code class="tpl-slug-code"><?= h($editTpl['slug']) ?></code>
              &mdash; Richting:
              <span class="tpl-badge tpl-badge-<?= h($editTpl['richting']) ?>"><?= h($editTpl['richting']) ?></span>
            </p>
          </div>
          <form method="POST" style="margin:0;">
            <input type="hidden" name="csrf"   value="<?= csrf() ?>">
            <input type="hidden" name="action" value="reset">
            <input type="hidden" name="slug"   value="<?= h($editTpl['slug']) ?>">
            <button type="submit" class="btn btn-secondary btn-sm"
                    onclick="return confirm('Template terugzetten naar standaard? Uw aanpassingen gaan verloren.')">
              &#8635; Standaard herstellen
            </button>
          </form>
        </div>

        <!-- Tabs -->
        <div class="tab-bar">
          <button class="tab-btn active" onclick="switchTab('bewerk', this)">&#9998; Bewerken</button>
          <button class="tab-btn" onclick="switchTab('preview', this)">&#128065; Voorbeeld</button>
          <button class="tab-btn" onclick="switchTab('testmail', this)">&#9993; Testmail</button>
        </div>

        <!-- Tab: Bewerken -->
        <div class="tab-pane active" id="tab-bewerk">
          <div class="vars-box">
            <strong>&#128274; Beschikbare variabelen (gebruik <code>{{variabele}}</code>):</strong>
            <code>{{casenummer}}</code> &nbsp;
            <code>{{merk}}</code> &nbsp;
            <code>{{modelnummer}}</code> &nbsp;
            <code>{{aanschafjaar}}</code> &nbsp;
            <code>{{geadviseerde_route}}</code> &nbsp;
            <code>{{situatie}}</code> &nbsp;
            <code>{{klacht_type}}</code> &nbsp;
            <code>{{omschrijving}}</code> &nbsp;
            <code>{{email}}</code> &nbsp;
            <code>{{advies_toelichting}}</code> &nbsp;
            <code>{{chatbericht}}</code> &nbsp;
            <code>{{datum_bericht}}</code> &nbsp;
            <code>{{status_oud}}</code> &nbsp;
            <code>{{status_nieuw}}</code> &nbsp;
            <code>{{toelichting_status}}</code>
          </div>
          <form method="POST" id="editForm">
            <input type="hidden" name="csrf"   value="<?= csrf() ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="slug"   value="<?= h($editTpl['slug']) ?>">
            <div class="field">
              <label>Onderwerpregel *</label>
              <input type="text" name="subject" value="<?= h($editTpl['subject']) ?>" required />
            </div>
            <div class="field">
              <label>HTML-inhoud (volledig e-mailbody) *</label>
              <textarea name="body_html" class="body-editor" id="bodyEditor"
                        oninput="updatePreview()"><?= htmlspecialchars($editTpl['body_html'], ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            <div class="form-check field">
              <label>
                <input type="checkbox" name="actief" value="1" <?= $editTpl['actief'] ? 'checked' : '' ?>>
                Template actief (verzending ingeschakeld)
              </label>
            </div>
            <div class="tpl-save-row">
              <button type="submit" class="btn btn-primary">&#128190; Opslaan</button>
              <span class="tpl-bijgewerkt">Bijgewerkt: <?= h($editTpl['bijgewerkt'] ?? '—') ?></span>
            </div>
          </form>
        </div>

        <!-- Tab: Voorbeeld -->
        <div class="tab-pane" id="tab-preview">
          <div class="preview-wrap">
            <div class="preview-hdr">E-mail voorbeeld (gesimuleerde variabelen)</div>
            <div class="preview-body">
              <iframe class="preview-frame" id="previewFrame"></iframe>
            </div>
          </div>
        </div>

        <!-- Tab: Testmail -->
        <div class="tab-pane" id="tab-testmail">
          <div class="vars-box">
            <strong>&#9993; Testmail versturen</strong>
            Verstuur een testmail met gesimuleerde variabelen naar een e-mailadres naar keuze.
            Controleer of de mail correct aankomt en eruitziet in uw e-mailclient.
          </div>
          <form method="POST">
            <input type="hidden" name="csrf"   value="<?= csrf() ?>">
            <input type="hidden" name="action" value="testmail">
            <input type="hidden" name="slug"   value="<?= h($editTpl['slug']) ?>">
            <div class="testmail-row">
              <div class="field">
                <label>Verstuur testmail naar</label>
                <input type="email" name="test_email" placeholder="uw@email.nl" required />
              </div>
              <button type="submit" class="btn btn-primary">&#9993; Stuur testmail</button>
            </div>
          </form>
        </div>

      </div><!-- /.admin-card -->
    </div>
    <?php else: ?>
    <div class="admin-card">
      <p class="tpl-leeg-msg">Geen templates gevonden. Ververs de pagina.</p>
    </div>
    <?php endif; ?>

  </div><!-- /.tpl-layout -->

  <!-- ── Standaardberichten ────────────────────────────────────────── -->
  <h2 class="adm-page-title" style="margin-top:2rem;">&#128172; Standaardberichten</h2>
  <p class="adm-page-subtitle">Snel-antwoorden die admins kunnen kiezen bij het sturen van een bericht aan de klant.</p>

  <div class="tpl-layout">

    <!-- Formulier: toevoegen / bewerken -->
    <div class="admin-card" style="padding:1.25rem;">
      <p class="tpl-list-title"><?= $editSb ? 'Bewerk bericht' : 'Nieuw bericht' ?></p>
      <form method="POST">
        <input type="hidden" name="csrf"    value="<?= csrf() ?>">
        <input type="hidden" name="action"  value="sb_save">
        <input type="hidden" name="sb_id"   value="<?= $editSb ? (int)$editSb['id'] : 0 ?>">
        <div class="field">
          <label>Titel *</label>
          <input type="text" name="sb_titel"
                 value="<?= h($editSb['titel'] ?? '') ?>"
                 placeholder="Bijv. Garantieverzoek ontvangen" required>
        </div>
        <div class="field">
          <label>Berichttekst *</label>
          <textarea name="sb_tekst" style="min-height:100px;" required
                    placeholder="De volledige tekst die in het berichtveld wordt geplaatst..."><?= h($editSb['tekst'] ?? '') ?></textarea>
        </div>
        <div style="display:flex;gap:.5rem;align-items:center;">
          <button type="submit" class="btn btn-primary btn-sm">
            <?= $editSb ? '&#128190; Opslaan' : '+ Toevoegen' ?>
          </button>
          <?php if ($editSb): ?>
            <a href="?#standaardberichten" class="btn btn-secondary btn-sm">Annuleren</a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- Lijst -->
    <div class="admin-card" style="padding:1rem;">
      <p class="tpl-list-title"><?= count($standaardBerichten) ?> berichten</p>
      <?php if (empty($standaardBerichten)): ?>
        <p style="font-size:.85rem;color:var(--adm-faint);">Nog geen standaardberichten aangemaakt.</p>
      <?php else: ?>
      <table class="admin-table">
        <thead>
          <tr><th>Titel</th><th>Tekst (preview)</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($standaardBerichten as $sb): ?>
        <tr>
          <td style="font-weight:600;white-space:nowrap;"><?= h($sb['titel']) ?></td>
          <td style="font-size:.8rem;color:var(--adm-muted);">
            <?= h(mb_strimwidth($sb['tekst'], 0, 80, '…')) ?>
          </td>
          <td style="white-space:nowrap;">
            <a href="?editbericht=<?= (int)$sb['id'] ?>#standaardberichten"
               class="btn btn-secondary btn-sm">&#9998; Bewerk</a>
            <form method="POST" style="display:inline;margin:0;"
                  onsubmit="return confirm('Standaardbericht verwijderen?')">
              <input type="hidden" name="csrf"   value="<?= csrf() ?>">
              <input type="hidden" name="action" value="sb_delete">
              <input type="hidden" name="sb_id"  value="<?= (int)$sb['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm">&#128465;</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>

  </div><!-- /.tpl-layout standaardberichten -->

</div><!-- /.adm-page -->

<script>
const PREVIEW_VARS = {
  casenummer:         '2026-04-1000',
  merk:               'Samsung',
  modelnummer:        'UE55CU8000',
  aanschafjaar:       '2022',
  geadviseerde_route: 'Garantie',
  situatie:           'Technisch defect',
  klacht_type:        'geen_beeld',
  omschrijving:       'TV gaat niet meer aan na stroomstoring.',
  email:              'gebruiker@voorbeeld.nl',
  advies_toelichting: 'Uw televisie valt nog binnen de garantietermijn.',
  bericht:            'Dit is een testbericht.',
  chatbericht:        'Dit is een testbericht via de chat.',
  datum_bericht:      '20-04-2026 15:45',
  status_oud:         'In behandeling',
  status_nieuw:       'Advies gegeven',
  toelichting_status: 'Uw aanvraag is volledig afgehandeld.',
};

function renderVars(html) {
  return html.replace(/\{\{(\w+)\}\}/g, (_, k) => PREVIEW_VARS[k] || '{{' + k + '}}');
}

function updatePreview() {
  const frame = document.getElementById('previewFrame');
  if (!frame) return;
  const raw = document.getElementById('bodyEditor')?.value || '';
  frame.srcdoc = renderVars(raw);
}

function switchTab(name, btn) {
  document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.getElementById('tab-' + name).classList.add('active');
  btn.classList.add('active');
  if (name === 'preview') updatePreview();
}

updatePreview();
</script>
</body>
</html>