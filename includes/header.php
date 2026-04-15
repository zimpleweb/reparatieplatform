<?php $cur = basename($_SERVER['PHP_SELF'], '.php'); ?>
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
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css" />
  <style>
    @media (max-width: 768px) {
      .nav-logo img { height: 44px; }
    }
  </style>
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
    <a href="<?= BASE_URL ?>/hoe-het-werkt.php" <?= $cur==='hoe-het-werkt' ? 'class="active"':'' ?>>Hoe het werkt</a>
    <a href="<?= BASE_URL ?>/advies.php"        <?= $cur==='advies'        ? 'class="active"':'' ?>>Advies</a>
    <a href="<?= BASE_URL ?>/reparatie.php"     <?= $cur==='reparatie'     ? 'class="active"':'' ?>>Reparatie</a>
    <a href="<?= BASE_URL ?>/taxatie.php"       <?= $cur==='taxatie'       ? 'class="active"':'' ?>>Taxatie</a>
    <a href="<?= BASE_URL ?>/database.php"      <?= $cur==='database'      ? 'class="active"':'' ?>>Database</a>
  </div>
  <a href="<?= BASE_URL ?>/#advies" class="nav-btn">
    <span class="nav-btn-dot"></span>
    Vraag gratis advies aan
  </a>
</nav>
