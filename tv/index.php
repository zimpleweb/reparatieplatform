<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/advies_regels.php';

$slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($_GET['slug'] ?? ''));
$tv   = getTv($slug);

if (!$tv) {
    http_response_code(404);
    $pageTitle = 'Televisie niet gevonden | Reparatieplatform.nl';
    include __DIR__ . '/../includes/header.php';
    echo '<div style="text-align:center;padding:6rem 2rem;">
            <h1 style="font-family:Epilogue,sans-serif;font-size:2rem;margin-bottom:1rem;">Televisie niet gevonden</h1>
            <p style="color:#6b7280;margin-bottom:2rem;">Dit model staat nog niet in onze database.</p>
            <a href="/database.php" style="background:#287864;color:white;padding:.85rem 2rem;border-radius:999px;font-weight:700;">Bekijk alle modellen</a>
          </div>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}

$related = getRelated($tv['id'], $tv['merk'], $tv['serie']);

$pageTitle       = $tv['merk'].' '.$tv['modelnummer'].' kapot of defect? Reparatie & advies | Reparatieplatform.nl';
$pageDescription = $tv['merk'].' '.$tv['modelnummer'].' defect? Veelvoorkomende klachten en gratis advies over reparatie, garantie of taxatie voor uw verzekeraar.';
$canonicalUrl    = '/tv/'.$tv['slug'];

$schema = [
    '@context'    => 'https://schema.org',
    '@type'       => 'Product',
    'name'        => $tv['merk'].' '.$tv['modelnummer'],
    'brand'       => ['@type'=>'Brand','name'=>$tv['merk']],
    'description' => $tv['beschrijving'],
];
if (!empty($tv['klachten'])) {
    $faq = ['@context'=>'https://schema.org','@type'=>'FAQPage','mainEntity'=>[]];
    foreach ($tv['klachten'] as $k) {
        $faq['mainEntity'][] = [
            '@type'          => 'Question',
            'name'           => $k['titel'],
            'acceptedAnswer' => ['@type'=>'Answer','text'=>$k['omschrijving']],
        ];
    }
    $schemaJson = json_encode($schema).'</script><script type="application/ld+json">'.json_encode($faq);
} else {
    $schemaJson = json_encode($schema);
}

// ── Stappenplan configuratie voor formulier ────────────────────────────────
$r   = getAdviesRegels();
$rJs = json_encode($r, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE);

$garantieShops = [];
try {
    $garantieShops = db()->query("SELECT naam, support_url FROM coulance_shops WHERE actief=1 ORDER BY volgorde, naam LIMIT 30")->fetchAll(PDO::FETCH_ASSOC);
} catch (\Exception $e) {}

$garantieMerkUrls = [];
try {
    $colCheck = db()->query("SHOW COLUMNS FROM tv_modellen LIKE 'support_url'")->fetchAll();
    if (!empty($colCheck)) {
        $mrows = db()->query("SELECT DISTINCT merk, MAX(support_url) AS support_url FROM tv_modellen WHERE support_url != '' AND actief=1 GROUP BY merk ORDER BY merk")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($mrows as $mr) { $garantieMerkUrls[$mr['merk']] = $mr['support_url']; }
    }
} catch (\Exception $e) {}

$garantieShopsJs    = json_encode(array_values($garantieShops), JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE);
$garantieMerkUrlsJs = json_encode($garantieMerkUrls,            JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE);

$stappenConfig = [];
if (!empty($r['stappen_config']) && is_array($r['stappen_config'])) {
    $stappenConfig = $r['stappen_config'];
}
if (empty($stappenConfig)) {
    $stappenConfig = [
        ['nummer'=>1,'label'=>'Situatie',    'titel'=>'Wat is er aan de hand?',   'lead'=>'Dit bepaalt direct welke route het meest geschikt is.'],
        ['nummer'=>2,'label'=>'TV gegevens', 'titel'=>'Over je televisie',         'lead'=>'Merk, model en aankoopinformatie bepalen de route.'],
        ['nummer'=>3,'label'=>'Defect',      'titel'=>'Beschrijf het defect',      'lead'=>'Hoe specifieker, hoe beter het advies.'],
        ['nummer'=>4,'label'=>'Contact',     'titel'=>'Je contactgegevens',        'lead'=>'Hier sturen wij je persoonlijk advies naartoe.'],
    ];
}
$aantalStappen = count($stappenConfig);

$reparatieUitsluitKlachten = (!empty($r['reparatie_uitsluiten_klachten']) && is_array($r['reparatie_uitsluiten_klachten']))
    ? $r['reparatie_uitsluiten_klachten'] : ['gebarsten_scherm'];
$taxatieIncludeKlachten = (!empty($r['taxatie_include_klachten']) && is_array($r['taxatie_include_klachten']))
    ? $r['taxatie_include_klachten'] : ['gebarsten_scherm', 'stroomstoot'];
$garantieUitsluitKlachten = (!empty($r['garantie_uitsluiten_klachten']) && is_array($r['garantie_uitsluiten_klachten']))
    ? $r['garantie_uitsluiten_klachten'] : ['gebarsten_scherm'];
$coulanceUitsluitKlachten = (!empty($r['coulance_uitsluiten_klachten']) && is_array($r['coulance_uitsluiten_klachten']))
    ? $r['coulance_uitsluiten_klachten'] : ['gebarsten_scherm'];
$taxatieUitsluitKlachten = (!empty($r['taxatie_uitsluiten_klachten']) && is_array($r['taxatie_uitsluiten_klachten']))
    ? $r['taxatie_uitsluiten_klachten'] : [];

$klachtRoutingJs = json_encode([
    'reparatie_uitsluiten' => $reparatieUitsluitKlachten,
    'taxatie_include'      => $taxatieIncludeKlachten,
    'garantie_uitsluiten'  => $garantieUitsluitKlachten,
    'coulance_uitsluiten'  => $coulanceUitsluitKlachten,
    'taxatie_uitsluiten'   => $taxatieUitsluitKlachten,
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE);

// Pre-fill stap 2 vanuit TV-model (afgeleid van URL/slug)
$prefillMerk        = $tv['merk'];
$prefillModelnummer = $tv['modelnummer'];

include __DIR__ . '/../includes/header.php';
?>

<div class="breadcrumb-bar">
  <div class="breadcrumb">
    <a href="/">Home</a><span class="sep">/</span>
    <a href="/nieuw/">Database</a><span class="sep">/</span>
    <a href="/nieuw/<?= slugify($tv['merk']) ?>"><?= h($tv['merk']) ?></a><span class="sep">/</span>
    <a href="/nieuw/<?= slugify($tv['merk']) ?>/<?= slugify($tv['serie']) ?>"><?= h($tv['serie']) ?></a><span class="sep">/</span>
    <span><?= h($tv['modelnummer']) ?></span>
  </div>
</div>

<div class="model-page">

  <div class="content">

    <div class="model-header">
      <div class="model-icon-wrap">&#128250;</div>
      <div>
        <div class="model-meta">
          <span class="meta-tag"><?= h($tv['merk']) ?></span>
          <span class="meta-tag"><?= h($tv['serie']) ?></span>
          <?php if ($tv['repareerbaar']): ?>
          <span class="meta-tag green">&#10003; Repareerbaar</span>
          <?php endif; ?>
        </div>
        <h1><?= h($tv['merk'].' '.$tv['modelnummer']) ?></h1>
        <a href="#advies" class="model-cta-pill">Bekijk de mogelijkheden voor jouw <?= h($tv['modelnummer']) ?> &darr;</a>
      </div>
    </div>

    <?php $klachten = $tv['klachten']; include __DIR__ . '/partials/defecten.php'; ?>

    <div class="card">
      <h2>Reparatie van de <?= h($tv['merk'].' '.$tv['modelnummer']) ?></h2>
      <p>
        De <?= h($tv['merk'].' '.$tv['modelnummer']) ?> is een televisie van <?= h($tv['merk']) ?>.
        Wij zijn gespecialiseerd in het repareren van <?= h($tv['merk']) ?>-televisies aan huis.
        LED strips, T-CON boards en backlight-problemen zijn onze specialiteit.
      </p>
      <p>
        Heb je een beschadigde <?= h($tv['modelnummer']) ?> en wil je weten of reparatie loont?
        Of heb je een taxatierapport nodig voor je verzekeraar?
        Vraag gratis en vrijblijvend advies aan via het <a href="#advies">stappenplan hieronder</a>.
      </p>
    </div>

    <?php if (!empty($tv['klachten'])): ?>
    <div class="card">
      <h2>Veelgestelde vragen</h2>
      <?php foreach ($tv['klachten'] as $k): ?>
      <div class="faq-item">
        <div class="faq-q"><?= h($k['titel']) ?></div>
        <div class="faq-a"><?= h($k['omschrijving']) ?></div>
      </div>
      <?php endforeach; ?>
      <div class="faq-item">
        <div class="faq-q">Kan de <?= h($tv['modelnummer']) ?> aan huis worden gerepareerd?</div>
        <div class="faq-a">
          Ja, wij repareren de <?= h($tv['merk'].' '.$tv['modelnummer']) ?> bij u thuis.
          Vraag een gratis advies aan en wij laten u weten wat de mogelijkheden zijn.
        </div>
      </div>
      <div class="faq-item">
        <div class="faq-q">Wat kost een taxatierapport voor mijn <?= h($tv['modelnummer']) ?>?</div>
        <div class="faq-a">
          Vraag vrijblijvend een advies aan via het formulier.
          Wij stellen een officieel taxatierapport op dat geaccepteerd wordt door uw verzekeraar.
        </div>
      </div>
    </div>
    <?php endif; ?>

  </div>

  <aside class="sidebar">

    <?php include __DIR__ . '/partials/sidebar-specs.php'; ?>

    <div class="info-card">
      <h4>Reparatie &amp; Taxatie</h4>
      <div class="info-row">
        <span class="info-icon"><?= $tv['repareerbaar'] ? '&#10003;' : '&#10007;' ?></span>
        <p><strong>Reparatie</strong></p>
      </div>
      <div class="info-row">
        <span class="info-icon"><?= $tv['taxatie'] ? '&#10003;' : '&#10007;' ?></span>
        <p><strong>Taxatie</strong></p>
      </div>
      <?php if ($tv['repareerbaar'] && !empty($tv['reparatie_titel'])): ?>
      <div class="rep-tax-blok">
        <h5><?= h($tv['reparatie_titel']) ?></h5>
        <?php if (!empty($tv['reparatie_tekst'])): ?><p><?= h($tv['reparatie_tekst']) ?></p><?php endif; ?>
      </div>
      <?php endif; ?>
      <?php if ($tv['taxatie'] && !empty($tv['taxatie_titel'])): ?>
      <div class="rep-tax-blok">
        <h5><?= h($tv['taxatie_titel']) ?></h5>
        <?php if (!empty($tv['taxatie_tekst'])): ?><p><?= h($tv['taxatie_tekst']) ?></p><?php endif; ?>
      </div>
      <?php endif; ?>
    </div>

    <?php if (!empty($related)): ?>
    <div class="related-card">
      <h4>Vergelijkbare modellen</h4>
      <div class="related-list">
        <?php foreach ($related as $r): ?>
        <a href="/nieuw/<?= h($r['slug']) ?>" class="related-link">
          <?= h($r['merk'].' '.$r['modelnummer']) ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </aside>

</div>

<style>
.model-cta-pill {
  display: inline-block;
  margin-top: .5rem;
  padding: .55rem 1.25rem;
  background: #287864;
  color: #fff;
  border-radius: 999px;
  font-size: .9rem;
  font-weight: 700;
  text-decoration: none;
  transition: background .15s;
}
.model-cta-pill:hover { background: #1d5f4f; }
.form-wrap--featured {
  border-top: 4px solid #287864;
  background: linear-gradient(180deg, #f0f9f5 0%, #ffffff 80%);
}
.form-cta-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  background: rgba(40,120,100,.1);
  border: 1px solid rgba(40,120,100,.3);
  border-radius: 999px;
  padding: .3rem 1rem;
  font-size: .8rem;
  font-weight: 700;
  color: #287864;
  letter-spacing: .04em;
  margin-bottom: 1rem;
}
</style>

<div class="form-wrap form-wrap--featured" id="advies">
  <div class="form-inner">

    <div class="form-left">
      <span class="form-cta-eyebrow">&#128221; Gratis advies &mdash; binnen 24 uur</span>
      <h2 class="section-title">Wat zijn de mogelijkheden<br>voor jouw <?= h($tv['merk'].' '.$tv['modelnummer']) ?>?</h2>
      <p class="section-lead">Op basis van jouw antwoorden kijken wij automatisch welke route het beste bij je past: garantie, coulance, reparatie of taxatie.</p>
      <div class="outcome-list">
        <div class="outcome-item"><div class="oi-icon oi-blue">&#128737;</div> Garantie aanspreken bij de winkel of fabrikant</div>
        <div class="outcome-item"><div class="oi-icon oi-yellow">&#129309;</div> Coulanceregeling bespreken met de verkoper</div>
        <div class="outcome-item"><div class="oi-icon oi-orange">&#128295;</div> Reparatie aan huis door gespecialiseerde monteur</div>
        <div class="outcome-item"><div class="oi-icon oi-purple">&#128203;</div> Taxatierapport opstellen voor uw verzekeraar</div>
        <div class="outcome-item"><div class="oi-icon" style="background:#d1fae5;color:#065f46">&#9851;</div> Recycling: verantwoorde verwerking van je televisie</div>
      </div>
      <div id="routing-indicator" style="display:none;" class="routing-indicator">
        <div class="routing-label">Mogelijke route op basis van je antwoorden:</div>
        <div id="routing-badge" class="routing-badge"></div>
        <div id="routing-toelichting" class="routing-toelichting"></div>
      </div>
    </div>

    <div class="form-right">
      <div class="form-card">
        <h3>Beschrijf het probleem</h3>
        <p>Doorloop de stappen en ontvang binnen &eacute;&eacute;n werkdag een reactie.</p>
        <?php include __DIR__ . '/../includes/components/stap-formulier.php'; ?>
      </div>
    </div>

  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  if (document.getElementById('merk')?.value) {
    resetRepareerbaar();
    berekenRoute();
  }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>