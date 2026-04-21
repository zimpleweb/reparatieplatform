<?php
session_start();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$rcEnabled   = getSetting('recaptcha_enabled') === '1';
$rcSiteKey   = getSetting('recaptcha_site_key');
$rcSecretKey = getSetting('recaptcha_secret_key');
$rcThreshold = getSetting('recaptcha_threshold', '0.5');
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Instellingen &ndash; Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/base.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/components.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
  <meta name="robots" content="noindex,nofollow">
</head>
<body>
<?php $adminActivePage = 'instellingen'; require_once __DIR__ . '/includes/admin-header.php'; ?>
<div class="adm-page"><h1>Instellingen</h1><p>reCAPTCHA: <?= $rcEnabled ? 'aan' : 'uit' ?></p></div>
</body>
</html>