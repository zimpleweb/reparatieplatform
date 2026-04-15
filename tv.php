<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$slug = trim($_GET['slug'] ?? '');
if (!$slug) { http_response_code(404); include __DIR__ . '/404.php'; exit; }

$stmt = db()->prepare('SELECT * FROM tv_modellen WHERE slug = ? AND actief = 1 LIMIT 1');
$stmt->execute([$slug]);
$tv = $stmt->fetch();

if (!$tv) { http_response_code(404); include __DIR__ . '/404.php'; exit; }

$klachten = db()->prepare(
    'SELECT * FROM klachten WHERE tv_model_id = ?
     ORDER BY FIELD(frequentie,"hoog","middel","laag")'
);
$klachten->execute([$tv['id']]);
$klachten = $klachten->fetchAll();

$vergelijkStmt = db()->prepare(
    'SELECT * FROM tv_modellen
     WHERE merk = ? AND id != ? AND actief = 1
     ORDER BY modelnummer ASC LIMIT 4'
);
$vergelijkStmt->execute([$tv['merk'], $tv['id']]);
$vergelijk = $vergelijkStmt->fetchAll();

$repareerbareMerken = ['Philips', 'Samsung', 'LG', 'Sony'];
$merkRepareerbaar   = in_array($tv['merk'], $repareerbareMerken);

$pageTitle       = $tv['merk'] . ' ' . $tv['modelnummer'] . ' defect of kapot? | Reparatieplatform.nl';
$pageDescription = $tv['merk'] . ' ' . $tv['modelnummer'] . ' stuk, defect of schade? Bekijk bekende klachten, reparatiemogelijkheden en vraag gratis advies aan.';
$canonicalUrl    = '/tv/' . $tv['slug'];

$schemaJson = json_encode([
    '@context'    => 'https://schema.org',
    '@type'       => 'Product',
    'name'        => $tv['merk'] . ' ' . $tv['modelnummer'],
    'brand'       => ['@type' => 'Brand', 'name' => $tv['merk']],
    'description' => $pageDescription,
]);

include __DIR__ . '/includes/header.php';
include __DIR__ . '/tv/partials/header.php';
?>

<div class="page-main">
  <div class="model-layout">

    <div class="model-main">
      <?php include __DIR__ . '/tv/partials/over.php'; ?>
      <?php include __DIR__ . '/tv/partials/defecten.php'; ?>
      <?php include __DIR__ . '/tv/partials/wizard.php'; ?>
      <?php include __DIR__ . '/tv/partials/faq.php'; ?>
      <?php include __DIR__ . '/tv/partials/hulp.php'; ?>
    </div>

    <aside class="sidebar">
      <?php include __DIR__ . '/tv/partials/sidebar-defecten.php'; ?>
      <?php include __DIR__ . '/tv/partials/sidebar-specs.php'; ?>
      <?php include __DIR__ . '/tv/partials/sidebar-modellen.php'; ?>
    </aside>

  </div>
</div>

<script>
  const WIZ_MERK = <?= json_encode($tv['merk']) ?>;
  const WIZ_BASE = <?= json_encode(BASE_URL) ?>;
</script>
<script src="<?= BASE_URL ?>/assets/js/tv-wizard.js" defer></script>

<?php include __DIR__ . '/includes/footer.php'; ?>