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
$prefillMerk         = $tv['merk'];
$prefillModelnummer  = $tv['modelnummer'];
$suppressRepFeedback = true; // Model is bekend; repareerbaar-feedback niet tonen

// ── Rep/Tax blok: één gecombineerde tekst ─────────────────────────────────
$heeftRep     = !empty($tv['repareerbaar']);
$heeftTaxatie = !empty($tv['taxatie']);
$blokTitel    = '';
$blokTekst    = '';
if ($heeftRep && $heeftTaxatie) {
    $blokTitel = !empty($tv['reparatie_titel']) ? $tv['reparatie_titel'] : 'Reparatie & taxatie mogelijk';
    $blokTekst = trim(
        (!empty($tv['reparatie_tekst']) ? $tv['reparatie_tekst'].' ' : '').
        (!empty($tv['taxatie_tekst'])   ? $tv['taxatie_tekst']        : '')
    );
    if (!$blokTekst) $blokTekst = 'Dit model kan worden gerepareerd aan huis. Ook is een officieel taxatierapport voor uw verzekeraar mogelijk.';
} elseif ($heeftTaxatie) {
    $blokTitel = !empty($tv['taxatie_titel']) ? $tv['taxatie_titel'] : 'Taxatie mogelijk';
    $blokTekst = !empty($tv['taxatie_tekst']) ? $tv['taxatie_tekst'] : 'Voor dit model kan een officieel taxatierapport voor uw verzekeraar worden opgesteld.';
} elseif ($heeftRep) {
    $blokTitel = !empty($tv['reparatie_titel']) ? $tv['reparatie_titel'] : 'Reparatie mogelijk';
    $blokTekst = !empty($tv['reparatie_tekst']) ? $tv['reparatie_tekst'] : 'Dit model kan worden gerepareerd aan huis door een gespecialiseerde monteur.';
}

include __DIR__ . '/../includes/header.php';
?>

<div class="breadcrumb-bar">
  <div class="breadcrumb">
    <a href="/">Home</a><span class="sep">/</span>
    <a href="/nieuw/">Database</a><span class="sep">/</span>
    <a href="/nieuw/database.php?merk=<?= urlencode($tv['merk']) ?>"><?= h($tv['merk']) ?></a><span class="sep">/</span>
    <a href="/nieuw/database.php?merk=<?= urlencode($tv['merk']) ?>"><?= h($tv['serie']) ?></a><span class="sep">/</span>
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
        <span class="info-icon"><?= $heeftRep ? '&#10003;' : '&#10007;' ?></span>
        <p><strong>Reparatie</strong></p>
      </div>
      <div class="info-row">
        <span class="info-icon"><?= $heeftTaxatie ? '&#10003;' : '&#10007;' ?></span>
        <p><strong>Taxatie</strong></p>
      </div>
      <?php if ($heeftRep || $heeftTaxatie): ?>
      <div class="rep-tax-blok">
        <h5><?= h($blokTitel) ?></h5>
        <p><?= h($blokTekst) ?></p>
      </div>
      <?php endif; ?>
    </div>

    <?php if (!empty($related)): ?>
    <div class="related-card">
      <h4>Vergelijkbare modellen</h4>
      <div class="related-list">
        <?php foreach ($related as $relTv): ?>
        <a href="/nieuw/tv/<?= h($relTv['slug']) ?>" class="related-link">
          <?= h($relTv['merk'].' '.$relTv['modelnummer']) ?>
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

/* ── Defecten + Reparatie/Taxatie: 2 kolommen, gelijke hoogte ── */
.defecten-reptax-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
  align-items: stretch;
  margin-bottom: 1.5rem;
}
.defecten-reptax-col {
  display: flex;
  flex-direction: column;
}
.defecten-reptax-col .model-card,
.defecten-reptax-col .info-card {
  flex: 1;
  margin-bottom: 0;
}
.rep-tax-blok {
  margin-top: 1rem;
  padding-top: .85rem;
  border-top: 1px solid var(--border, #e5e7eb);
}
.rep-tax-blok h5 {
  font-size: .875rem;
  font-weight: 700;
  color: var(--ink, #1a2332);
  margin: 0 0 .4rem;
}
.rep-tax-blok p {
  font-size: .82rem;
  color: var(--muted, #6b7280);
  line-height: 1.65;
  margin: 0;
}

/* ── Zo werkt het (lichte variant, identiek aan advies.php) ── */
.stappen-sectie-licht {
  background: #f8fafc;
  padding: 4rem 2.5rem;
}
.stappen-sectie-inner {
  max-width: 1280px;
  margin: 0 auto;
}
.stappen-titel-licht {
  font-size: clamp(1.5rem, 2.2vw, 2rem);
  font-weight: 800;
  color: #1a2332;
  letter-spacing: -.025em;
  margin-bottom: .5rem;
  text-align: center;
}
.stappen-lead-licht {
  font-size: 1rem;
  color: #64748b;
  max-width: 48ch;
  margin: 0 auto 2.5rem;
  text-align: center;
  line-height: 1.75;
}
.zowerkhet-steps-licht {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1.5rem;
}
.zowerkhet-step-licht {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 2rem 1.75rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  transition: border-color .2s ease, box-shadow .2s ease;
}
.zowerkhet-step-licht:hover {
  border-color: #287864;
  box-shadow: 0 4px 16px rgba(40,120,100,.1);
}
.zowerkhet-step-num-licht {
  font-size: .7rem;
  font-weight: 800;
  letter-spacing: .12em;
  color: #287864;
  text-transform: uppercase;
}
.zowerkhet-step-icon-licht { font-size: 1.75rem; line-height: 1; }
.zowerkhet-step-licht h3 {
  font-size: 1.05rem;
  font-weight: 800;
  color: #1a2332;
  letter-spacing: -.02em;
  margin: 0;
}
.zowerkhet-step-licht p {
  font-size: .875rem;
  color: #475569;
  line-height: 1.7;
  margin: 0;
  max-width: 36ch;
}
.zowerkhet-step-badge-licht {
  display: inline-flex;
  align-items: center;
  gap: .35rem;
  background: rgba(40,120,100,.08);
  border: 1px solid rgba(40,120,100,.25);
  border-radius: 999px;
  padding: .25rem .75rem;
  font-size: .72rem;
  font-weight: 700;
  color: #287864;
  margin-top: auto;
  width: fit-content;
}

@media (max-width: 768px) {
  .stappen-sectie-licht { padding: 3rem 1.25rem; }
  .zowerkhet-steps-licht { grid-template-columns: 1fr; }
  .zowerkhet-step-licht { padding: 1.5rem 1.25rem; }
}
@media (max-width: 640px) {
  .defecten-reptax-row { grid-template-columns: 1fr; }
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

<!-- Zo werkt het: identiek aan advies.php (stap 02 zonder repareerbaar-advies) -->
<div class="stappen-sectie-licht">
  <div class="stappen-sectie-inner">

    <h2 class="stappen-titel-licht">Zo werkt het</h2>
    <p class="stappen-lead-licht">Geen technische kennis nodig. Beschrijf het probleem en wij denken met je mee.</p>

    <div class="zowerkhet-steps-licht">
      <div class="zowerkhet-step-licht">
        <span class="zowerkhet-step-num-licht">Stap 01</span>
        <div class="zowerkhet-step-icon-licht">&#128221;</div>
        <h3>Formulier invullen</h3>
        <p>Vul het aankoopjaar en een korte omschrijving in. Duurt minder dan twee minuten en je hebt er geen technische kennis voor nodig.</p>
        <span class="zowerkhet-step-badge-licht">&#10003; Gratis</span>
      </div>
      <div class="zowerkhet-step-licht">
        <span class="zowerkhet-step-num-licht">Stap 02</span>
        <div class="zowerkhet-step-icon-licht">&#128269;</div>
        <h3>Wij bekijken jouw situatie</h3>
        <p>Een specialist beoordeelt je aanvraag op garantie, coulance, reparatiemogelijkheden en de waarde van je televisie.</p>
        <span class="zowerkhet-step-badge-licht">&#10003; Persoonlijk advies</span>
      </div>
      <div class="zowerkhet-step-licht">
        <span class="zowerkhet-step-num-licht">Stap 03</span>
        <div class="zowerkhet-step-icon-licht">&#128233;</div>
        <h3>Advies binnen 24 uur</h3>
        <p>Je ontvangt een helder advies per e-mail met concrete vervolgstappen &mdash; of het nu garantie, coulance, reparatie of taxatie is.</p>
        <span class="zowerkhet-step-badge-licht">&#10003; Binnen 1 werkdag</span>
      </div>
    </div>

  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
