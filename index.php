<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/advies_regels.php';

$pageTitle       = 'Televisie kapot? Gratis advies op maat | Reparatieplatform.nl';
$pageDescription = 'Televisie kapot of defect? Ontvang gratis persoonlijk advies: garantie, reparatie aan huis of taxatie voor uw verzekeraar.';
$canonicalUrl    = '/';
$merken          = getMerken();

// ── Stappenplan configuratie (zelfde als advies.php) ──────────────────────
$r   = getAdviesRegels();
$rJs = json_encode($r, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE);

// ── Shops & merk-urls voor garantie/coulance panel ────────────────────────
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

include __DIR__ . '/includes/header.php';
?>

<style>
/* ── Hero: 25% kleiner ──────────────────────────────────────── */
.hero {
  min-height: 60vh !important; /* was ~80vh, nu 25% kleiner */
}

/* ── Zo werkt het – nieuwe sectie-stijl ─────────────────────── */
.zowerkhet-section {
  background: #0d1117;
  padding: 5rem 0;
  border-top: none;
}
.zowerkhet-section .section-title {
  color: #fff;
}
.zowerkhet-section .section-lead {
  color: rgba(255,255,255,.55);
}
.zowerkhet-steps {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1.5rem;
  margin-top: 3rem;
}
.zowerkhet-step {
  background: #161b22;
  border: 1px solid rgba(255,255,255,.08);
  border-radius: 14px;
  padding: 2rem 1.75rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  transition: border-color .2s ease, transform .2s ease;
  position: relative;
  overflow: hidden;
}
.zowerkhet-step::before {
  content: '';
  position: absolute;
  inset: 0;
  background: radial-gradient(ellipse at top left, rgba(40,120,100,.12) 0%, transparent 65%);
  pointer-events: none;
}
.zowerkhet-step:hover {
  border-color: rgba(40,120,100,.5);
  transform: translateY(-3px);
}
.zowerkhet-step-num {
  font-size: .7rem;
  font-weight: 800;
  letter-spacing: .12em;
  color: var(--accent, #287864);
  text-transform: uppercase;
}
.zowerkhet-step-icon {
  font-size: 1.75rem;
  line-height: 1;
}
.zowerkhet-step h3 {
  font-size: 1.05rem;
  font-weight: 800;
  color: #fff;
  letter-spacing: -.02em;
  margin: 0;
}
.zowerkhet-step p {
  font-size: .875rem;
  color: rgba(255,255,255,.5);
  line-height: 1.7;
  margin: 0;
  max-width: 36ch;
}
.zowerkhet-step-badge {
  display: inline-flex;
  align-items: center;
  gap: .35rem;
  background: rgba(40,120,100,.15);
  border: 1px solid rgba(40,120,100,.35);
  border-radius: 999px;
  padding: .25rem .75rem;
  font-size: .72rem;
  font-weight: 700;
  color: #4ecb9e;
  margin-top: auto;
  width: fit-content;
}
@media (max-width: 640px) {
  .zowerkhet-steps { grid-template-columns: 1fr; }
  .zowerkhet-step { padding: 1.5rem 1.25rem; }
}
</style>

<section>
  <div class="hero">
    <div class="hero-bg"></div>
    <div>
      <div class="hero-eyebrow">
        <span class="hero-eyebrow-dot">&#10003;</span>
        Gratis advies &mdash; geen verplichtingen
      </div>
      <h1>Je televisie<br>is kapot.<br><em>Wat nu?</em></h1>
      <p class="hero-sub">
        Beschrijf het probleem en ontvang binnen &eacute;&eacute;n werkdag eerlijk advies.
        Of je recht hebt op garantie, reparatie aan huis of vergoeding via de verzekeraar.
      </p>
      <div class="hero-actions">
        <a href="#advies" class="btn-primary">
          Vraag gratis advies aan
          <span class="btn-primary-arrow">&rarr;</span>
        </a>
        <a href="#hoe" class="btn-ghost">Hoe werkt het? &darr;</a>
      </div>
      <div class="hero-badges">
        <div class="hero-badge"><span>&#127968;</span> Reparatie aan huis</div>
        <div class="hero-badge"><span>&#128203;</span> Taxatie voor verzekeraars</div>
        <div class="hero-badge"><span>&#127807;</span> Duurzaam repareren</div>
      </div>
    </div>
    <div>
      <div class="hero-card">
        <span class="hero-card-tag">Jouw televisie, ons advies</span>
        <h3>Kapotte televisie?<br>Wij helpen je verder.</h3>
        <p>Vertel ons wat er mis is en ontvang binnen &eacute;&eacute;n werkdag een eerlijk advies, helemaal gratis.</p>
        <?php $wizardCompact = true; include __DIR__ . '/includes/stap-wizard.php'; ?>
      </div>
    </div>
  </div>
</section>

<!-- Zo werkt het – strak donkere sectie -->
<div class="zowerkhet-section" id="hoe">
  <div class="section" style="padding-top:0;padding-bottom:0;">
    <div style="text-align:center;margin-bottom:0;">
      <div style="display:inline-flex;align-items:center;gap:.45rem;background:rgba(40,120,100,.15);border:1px solid rgba(40,120,100,.3);border-radius:999px;padding:.3rem 1rem;font-size:.75rem;font-weight:700;color:#4ecb9e;margin-bottom:1.1rem;letter-spacing:.04em;">
        &#9881; Zo simpel werkt het
      </div>
      <h2 class="section-title">Zo werkt het</h2>
      <p class="section-lead" style="max-width:48ch;margin:.6rem auto 0;">
        Geen technische kennis nodig. Beschrijf wat er mis is en wij regelen de rest.
      </p>
    </div>
    <div class="zowerkhet-steps">
      <div class="zowerkhet-step">
        <span class="zowerkhet-step-num">Stap 01</span>
        <div class="zowerkhet-step-icon">&#128221;</div>
        <h3>Formulier invullen</h3>
        <p>Vul merk, modelnummer en een korte omschrijving in. Duurt minder dan twee minuten &mdash; geen technische kennis vereist.</p>
        <span class="zowerkhet-step-badge">&#10003; Gratis</span>
      </div>
      <div class="zowerkhet-step">
        <span class="zowerkhet-step-num">Stap 02</span>
        <div class="zowerkhet-step-icon">&#128269;</div>
        <h3>We kijken jouw situatie na</h3>
        <p>Een specialist bekijkt jouw aanvraag en toetst alle opties: garantie, coulance, reparatiemogelijkheden en de waarde van je tv.</p>
        <span class="zowerkhet-step-badge">&#10003; Persoonlijk advies</span>
      </div>
      <div class="zowerkhet-step">
        <span class="zowerkhet-step-num">Stap 03</span>
        <div class="zowerkhet-step-icon">&#128233;</div>
        <h3>Advies binnen 24 uur</h3>
        <p>Je ontvangt een helder advies per e-mail met concrete vervolgstappen &mdash; of het nu garantie, coulance, reparatie of taxatie is.</p>
        <span class="zowerkhet-step-badge">&#10003; Binnen 1 werkdag</span>
      </div>
    </div>
  </div>
</div>

<div class="form-wrap form-wrap--featured" id="advies">
  <div class="form-inner">
    <div class="form-left">
      <span class="form-cta-eyebrow">&#128221; Gratis advies &mdash; binnen 24 uur</span>
      <h2 class="section-title">Vraag gratis<br>advies aan</h2>
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
        <?php include __DIR__ . '/includes/components/stap-formulier.php'; ?>
      </div>
    </div>
  </div>
</div>

<style>
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

<div class="section">
  <h2 class="section-title">Wat kun je verwachten?</h2>
  <p class="section-lead">Op basis van jouw situatie ontvang je een van de volgende adviezen, met duidelijke vervolgstappen.</p>
  <div class="cards-grid">
    <div class="adv-card">
      <div class="adv-num">01</div>
      <div class="adv-card-icon">&#128737;</div>
      <h3>Garantie aanspreken</h3>
      <p>Je tv valt nog onder de wettelijke garantie. We leggen je stap voor stap uit hoe je dit aanpakt bij de winkel of fabrikant.</p>
      <span class="adv-tag">Kosteloos</span>
    </div>
    <div class="adv-card">
      <div class="adv-num">02</div>
      <div class="adv-card-icon">&#129309;</div>
      <h3>Coulanceregeling</h3>
      <p>De garantie is verlopen maar er is via de winkel toch iets mogelijk. Wij kijken samen wat haalbaar is.</p>
      <span class="adv-tag">Kans op vergoeding</span>
    </div>
    <div class="adv-card featured">
      <div class="adv-num">03</div>
      <div class="adv-card-icon">&#128295;</div>
      <h3>Reparatie aan huis</h3>
      <p>Onze monteur komt bij jou thuis. Gespecialiseerd in LED-strips en schermen van Samsung, Philips, Sony en LG.</p>
      <span class="adv-tag">Ons specialisme</span>
    </div>
    <div class="adv-card featured">
      <div class="adv-num">04</div>
      <div class="adv-card-icon">&#128203;</div>
      <h3>Taxatierapport</h3>
      <p>Een officieel taxatierapport voor je verzekeraar, met een aanbeveling voor reparatie, vergoeding of recycling.</p>
      <span class="adv-tag">Geaccepteerd door verzekeraars</span>
    </div>
  </div>
</div>

<div class="duurzaam-wrap">
  <div class="duurzaam-inner">
    <div>
      <h2 class="section-title">Repareren is beter<br>voor mens en planeet</h2>
      <p class="section-lead">
        Een nieuwe televisie heeft een enorme milieu-impact. Door te repareren bespaar je CO&#8322;
        en voorkom je elektronisch afval. Dankzij de EU Right to Repair-wetgeving zijn fabrikanten
        verplicht om reparatie betaalbaar en toegankelijk te houden.
      </p>
    </div>
    <div class="green-stats">
      <div class="green-stat"><strong>~300 kg</strong><span>CO&#8322; bespaard per gerepareerde tv t.o.v. nieuwe aankoop</span></div>
      <div class="green-stat"><strong>EU wet</strong><span>Right to Repair: fabrikanten verplicht tot repareerbaarheid</span></div>
      <div class="green-stat"><strong>+1 jaar</strong><span>Garantieverlenging na reparatie onder nieuwe EU-regels</span></div>
      <div class="green-stat"><strong>Minder afval</strong><span>Jij draagt bij aan een circulaire economie</span></div>
    </div>
  </div>
</div>

<div style="background:white;padding:5rem 0;">
  <div class="section" style="padding-top:0;padding-bottom:0;">
    <div class="merken-grid">
      <div>
        <h2 class="section-title" style="font-size:1.75rem;">Merken</h2>
        <p style="font-size:.9rem;color:var(--muted);margin-bottom:1.5rem;line-height:1.7;">We zijn gespecialiseerd in de populairste televisiemerken.</p>
        <div class="merken-row">
          <?php foreach ($merken as $m): ?>
          <a href="/database.php?merk=<?= urlencode($m) ?>" class="merk-pill"><?= h($m) ?></a>
          <?php endforeach; ?>
        </div>
      </div>
      <div>
        <h2 class="section-title" style="font-size:1.75rem;">Veelvoorkomende klachten</h2>
        <p style="font-size:.9rem;color:var(--muted);margin-bottom:1.5rem;line-height:1.7;">Herken je jouw klacht? Klik voor meer info per model.</p>
        <div class="klacht-grid">
          <?php foreach (['Kapot scherm','Strepen in beeld','Geen beeld wel geluid','TV gaat niet aan','Donkere vlekken','Backlight defect','LED strip kapot','Scherm flikkert','Zwart beeld','Halve scherm donker','Witte vlekken','Pixeldefect'] as $k): ?>
          <a href="/database.php?q=<?= urlencode($k) ?>" class="klacht-pill"><?= h($k) ?></a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>