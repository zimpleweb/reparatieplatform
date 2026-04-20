<?php $cur = basename($_SERVER['PHP_SELF'], '.php'); ?>
<?php
// reCAPTCHA v3 instelling ophalen (veilig, leeg als tabel niet bestaat)
$recaptcha_site_key = '';
$recaptcha_enabled  = false;
try {
    $rcRow = db()->query("SELECT setting_value FROM site_settings WHERE setting_key = 'recaptcha_site_key' LIMIT 1")->fetch();
    $rcOn  = db()->query("SELECT setting_value FROM site_settings WHERE setting_key = 'recaptcha_enabled' LIMIT 1")->fetch();
    if ($rcRow && !empty($rcRow['setting_value'])) $recaptcha_site_key = $rcRow['setting_value'];
    if ($rcOn  && $rcOn['setting_value'] === '1') $recaptcha_enabled = true;
} catch (Exception $e) { /* tabel bestaat nog niet */ }
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= h($pageTitle ?? 'Reparatieplatform.nl') ?></title>
  <meta name="description" content="<?= h($pageDescription ?? 'Televisie kapot? Gratis persoonlijk advies over garantie, reparatie aan huis of taxatie voor uw verzekeraar.') ?>" />
  <?php if (!empty($canonicalUrl)): ?>
  <link rel="canonical" href="https://reparatieplatform.nl<?= h($canonicalUrl) ?>" />
  <?php endif; ?>
  <?php if (!empty($schemaJson)): ?>
  <script type="application/ld+json"><?= $schemaJson ?></script>
  <?php endif; ?>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Epilogue:wght@700;800;900&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/base.css" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/nav.css" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/components.css" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/home.css" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/database.css" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/model.css" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/footer.css" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/advies-routing.css" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/klantenomgeving.css?v=<?= filemtime(__DIR__.'/../assets/css/klantenomgeving.css') ?>" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css" />
  <style>
    @media (max-width: 768px) {
      .nav-logo img { height: 44px; }
    }
  </style>
  <?php if ($recaptcha_enabled && $recaptcha_site_key): ?>
  <!-- reCAPTCHA v3 -->
  <script src="https://www.google.com/recaptcha/api.js?render=<?= h($recaptcha_site_key) ?>"></script>
  <script>
  // Globale helper: voeg reCAPTCHA token toe aan elk formulier met data-recaptcha
  document.addEventListener('DOMContentLoaded', function () {
    var forms = document.querySelectorAll('form[data-recaptcha]');
    forms.forEach(function (form) {
      var action = form.getAttribute('data-recaptcha') || 'submit';
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        grecaptcha.ready(function () {
          grecaptcha.execute('<?= h($recaptcha_site_key) ?>', { action: action }).then(function (token) {
            var input = form.querySelector('input[name="recaptcha_token"]');
            if (!input) {
              input = document.createElement('input');
              input.type = 'hidden';
              input.name = 'recaptcha_token';
              form.appendChild(input);
            }
            input.value = token;
            form.submit();
          });
        });
      });
    });
  });
  </script>
  <?php endif; ?>
</head>
<body>
<nav>
  <a href="<?= BASE_URL ?>/" class="nav-logo">
    <img
      src="https://reparatieplatform.nl/wp-content/uploads/2025/06/REPARATIEPLATFORM-LOGO-WEBSITE-1200x336.png"
      alt="ReparatiePlatform"
      height="34"
    />
  </a>
  <button class="nav-toggle" id="navToggle" aria-label="Menu openen">&#9776;</button>
  <div class="nav-menu" id="navMenu">
    <a href="<?= BASE_URL ?>/hoe-het-werkt.php"  <?= $cur==='hoe-het-werkt'  ? 'class="active"':'' ?>>Hoe het werkt</a>
    <a href="<?= BASE_URL ?>/advies.php"         <?= $cur==='advies'         ? 'class="active"':'' ?>>Advies</a>
    <a href="<?= BASE_URL ?>/reparatie.php"      <?= $cur==='reparatie'      ? 'class="active"':'' ?>>Reparatie</a>
    <a href="<?= BASE_URL ?>/taxatie.php"        <?= $cur==='taxatie'        ? 'class="active"':'' ?>>Taxatie</a>
    <a href="<?= BASE_URL ?>/database.php"       <?= $cur==='database'       ? 'class="active"':'' ?>>Database</a>
    <a href="<?= BASE_URL ?>/mijn-aanvraag.php"  <?= $cur==='mijn-aanvraag'  ? 'class="active"':'' ?>>Mijn aanvraag</a>
  </div>
  <a href="<?= BASE_URL ?>/#advies" class="nav-btn">
    <span class="nav-btn-dot"></span>
    Vraag gratis advies aan
  </a>
</nav>