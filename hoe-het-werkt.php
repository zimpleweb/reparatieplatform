<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle       = 'Hoe het werkt – Van defecte tv naar oplossing | Reparatieplatform.nl';
$pageDescription = 'Ontdek hoe Reparatieplatform.nl werkt. In drie stappen van defecte televisie naar persoonlijk advies over garantie, reparatie of taxatie.';
$canonicalUrl    = '/hoe-het-werkt.php';

include __DIR__ . '/includes/header.php';
?>

<!-- HERO: donker blok -->
<div class="page-header-hero-only">
  <div class="page-header-stappen-inner">

    <!-- Breadcrumb -->
    <div class="breadcrumb">
      <a href="<?= BASE_URL ?>/">Home</a><span class="sep">/</span>
      <span style="color:rgba(255,255,255,.4)">Hoe het werkt</span>
    </div>

    <!-- Hero tekst -->
    <h1>Hoe werkt<br>Reparatieplatform.nl?</h1>
    <p class="hero-lead">Televisie kapot? Wij helpen je uitzoeken wat de beste stap is. Gratis en zonder verplichtingen.</p>

    <!-- Badge -->
    <div class="hero-badge">&#9881; Eenvoudig proces</div>

  </div>
</div>

<!-- STAPPEN SECTIE: apart blok met lichte achtergrond -->
<div class="stappen-sectie-licht">
  <div class="stappen-sectie-inner">

    <h2 class="stappen-titel-licht">In drie stappen geholpen</h2>
    <p class="stappen-lead-licht">Geen technische kennis nodig. Vertel ons wat er speelt en wij denken met je mee.</p>

    <div class="zowerkhet-steps-licht" id="stappen">
      <div class="zowerkhet-step-licht">
        <span class="zowerkhet-step-num-licht">Stap 01</span>
        <div class="zowerkhet-step-icon-licht">&#128221;</div>
        <h3>Formulier invullen</h3>
        <p>Vul het korte adviesformulier in met merk, modelnummer en een omschrijving van het probleem. Het duurt minder dan twee minuten en je hebt er geen technische kennis voor nodig.</p>
        <ul class="stap-check-lijst">
          <li><span class="stap-check">&#10003;</span> Merk en modelnummer (staat achter op de tv)</li>
          <li><span class="stap-check">&#10003;</span> Korte omschrijving van het defect</li>
          <li><span class="stap-check">&#10003;</span> Je e-mailadres voor het advies</li>
        </ul>
        <span class="zowerkhet-step-badge-licht">&#10003; Gratis</span>
      </div>
      <div class="zowerkhet-step-licht">
        <span class="zowerkhet-step-num-licht">Stap 02</span>
        <div class="zowerkhet-step-icon-licht">&#128269;</div>
        <h3>Wij bekijken jouw situatie</h3>
        <p>Een specialist kijkt naar jouw aanvraag en beoordeelt de mogelijkheden: garantie, coulance, reparatie of taxatie. Je krijgt eerlijk advies, ook als reparatie niet de slimste keuze is.</p>
        <ul class="stap-check-lijst">
          <li><span class="stap-check">&#10003;</span> Garantie en coulance worden nagekeken</li>
          <li><span class="stap-check">&#10003;</span> Reparatiemogelijkheden in beeld gebracht</li>
          <li><span class="stap-check">&#10003;</span> Eerlijk advies, ook als reparatie niet loont</li>
        </ul>
        <span class="zowerkhet-step-badge-licht">&#10003; Persoonlijk advies</span>
      </div>
      <div class="zowerkhet-step-licht">
        <span class="zowerkhet-step-num-licht">Stap 03</span>
        <div class="zowerkhet-step-icon-licht">&#128233;</div>
        <h3>Advies binnen 24 uur per e-mail</h3>
        <p>Je ontvangt een helder persoonlijk advies per e-mail met duidelijke vervolgstappen. Of het nu gaat om garantie, coulance, reparatie of taxatie, wij wijzen je de weg.</p>
        <ul class="stap-check-lijst">
          <li><span class="stap-check">&#10003;</span> Reactie binnen één werkdag</li>
          <li><span class="stap-check">&#10003;</span> Concrete vervolgstappen</li>
          <li><span class="stap-check">&#10003;</span> Volledig gratis en vrijblijvend</li>
        </ul>
        <span class="zowerkhet-step-badge-licht">&#10003; Binnen 1 werkdag</span>
      </div>
    </div>

  </div>
</div>

<style>
/* ── Hero only: donker blok zonder stappen ── */
.page-header-hero-only {
  background: var(--ink, #0d1117);
  padding: 5rem 2.5rem 4rem;
  position: relative;
  overflow: hidden;
}
.page-header-hero-only::before {
  content: '';
  position: absolute;
  top: -100px; right: -100px;
  width: 400px; height: 400px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(40,120,100,.2) 0%, transparent 70%);
  pointer-events: none;
}
.page-header-stappen-inner {
  max-width: 1280px;
  margin: 0 auto;
  position: relative;
}
.page-header-hero-only h1 {
  font-size: clamp(2rem, 3.5vw, 3rem);
  font-weight: 800;
  color: white;
  letter-spacing: -.03em;
  margin-bottom: .75rem;
}
.page-header-hero-only .hero-lead {
  font-size: 1rem;
  color: rgba(255,255,255,.55);
  max-width: 520px;
  margin-bottom: 2.5rem;
}
.hero-badge {
  display: inline-flex;
  align-items: center;
  gap: .45rem;
  background: rgba(40,120,100,.15);
  border: 1px solid rgba(40,120,100,.3);
  border-radius: 999px;
  padding: .3rem 1rem;
  font-size: .75rem;
  font-weight: 700;
  color: #4ecb9e;
  margin-bottom: 1.1rem;
  letter-spacing: .04em;
}

/* ── Stappen sectie licht ── */
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
  position: relative;
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
.zowerkhet-step-icon-licht {
  font-size: 1.75rem;
  line-height: 1;
}
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
.stap-check-lijst {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: .35rem;
}
.stap-check-lijst li {
  font-size: .8rem;
  color: #64748b;
  display: flex;
  gap: .5rem;
}
.stap-check {
  color: #287864;
  font-weight: 700;
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
  .page-header-hero-only { padding: 4rem 1.25rem 3rem; }
  .stappen-sectie-licht { padding: 3rem 1.25rem; }
  .zowerkhet-steps-licht { grid-template-columns: 1fr; }
  .zowerkhet-step-licht { padding: 1.5rem 1.25rem; }
}
</style>

<!-- WIT TUSSENBLOK: vertrouwensrij als kleurscheiding -->
<div class="vertrouwen-balk">
  <div class="section" style="padding-top:2.5rem;padding-bottom:2.5rem;">
    <div class="vertrouwen-items">
      <div class="vertrouwen-item">
        <span class="vertrouwen-icon">&#9989;</span>
        <span><strong>100% gratis</strong> advies</span>
      </div>
      <div class="vertrouwen-item">
        <span class="vertrouwen-icon">&#128338;</span>
        <span>Reactie <strong>binnen 24 uur</strong></span>
      </div>
      <div class="vertrouwen-item">
        <span class="vertrouwen-icon">&#128274;</span>
        <span><strong>Vrijblijvend</strong> en zonder verplichtingen</span>
      </div>
      <div class="vertrouwen-item">
        <span class="vertrouwen-icon">&#127464;&#127473;</span>
        <span>Door heel <strong>Nederland</strong></span>
      </div>
    </div>
  </div>
</div>

<style>
.vertrouwen-balk {
  background: #fff;
  border-top: 1px solid #eee;
  border-bottom: 1px solid #eee;
}
.vertrouwen-items {
  display: flex;
  flex-wrap: wrap;
  gap: 1.5rem 3rem;
  justify-content: center;
  align-items: center;
}
.vertrouwen-item {
  display: flex;
  align-items: center;
  gap: .6rem;
  font-size: .92rem;
  color: #444;
}
.vertrouwen-icon { font-size: 1.2rem; }
@media (max-width: 640px) {
  .vertrouwen-items { flex-direction: column; gap: 1rem; align-items: flex-start; }
}
</style>

<!-- Welke opties zijn er -->
<div class="section-light">
  <div class="section" style="padding-top:4rem;padding-bottom:4rem;">
    <h2 class="section-title">Wat kunnen wij voor je doen?</h2>
    <p class="section-lead">Afhankelijk van jouw situatie kijken wij welke route het beste bij je past. Garantie en coulance worden als advies getoond. Als een verkoper of merk niet meewerkt met de coulance, kun je altijd terecht voor vrijblijvend reparatieadvies.</p>
    <div class="cards-grid">
      <a href="<?= BASE_URL ?>/garantie.php" class="adv-card" style="text-decoration:none;">
        <div class="adv-num">01</div>
        <div class="adv-card-icon">&#9989;</div>
        <h3>Garantie</h3>
        <p>Televisie nog binnen de garantietermijn? Wij helpen je uitzoeken of je recht hebt op gratis reparatie of vervanging door de fabrikant of verkoper.</p>
        <span class="adv-tag">Gratis advies</span>
      </a>
      <a href="<?= BASE_URL ?>/coulance.php" class="adv-card" style="text-decoration:none;">
        <div class="adv-num">02</div>
        <div class="adv-card-icon">&#129309;</div>
        <h3>Coulanceregeling</h3>
        <p>Garantie verlopen maar televisie snel stuk? Veel fabrikanten bieden buiten de garantietermijn nog coulance aan. Wij laten je zien hoe je dat het beste aanpakt. Werkt de verkoper niet mee? Dan help je we je verder met reparatieadvies.</p>
        <span class="adv-tag">Gratis advies</span>
      </a>
      <a href="<?= BASE_URL ?>/reparatie.php" class="adv-card featured" style="text-decoration:none;">
        <div class="adv-num">03</div>
        <div class="adv-card-icon">&#128295;</div>
        <h3>Reparatie aan huis</h3>
        <p>Een gecertificeerde monteur komt bij je thuis. Transparante prijzen, snel geholpen en drie maanden garantie op de reparatie.</p>
        <span class="adv-tag">Ons specialisme</span>
      </a>
      <a href="<?= BASE_URL ?>/taxatie.php" class="adv-card featured" style="text-decoration:none;">
        <div class="adv-num">04</div>
        <div class="adv-card-icon">&#128196;</div>
        <h3>Schadetaxatie</h3>
        <p>Schade door stroom, brand of inbraak? Wij stellen een officieel taxatierapport op voor je verzekeraar. De schadetaxatie kost 49 euro.</p>
        <span class="adv-tag">49 euro</span>
      </a>
    </div>
  </div>
</div>

<!-- Veelgestelde vragen -->
<div style="background:white; padding:5rem 0;">
  <div class="section" style="padding-top:0;padding-bottom:0;">
    <h2 class="section-title">Veelgestelde vragen</h2>
    <p class="section-lead">Alles wat je wil weten over hoe Reparatieplatform.nl werkt.</p>
    <div class="faq-lijst-fancy">
      <div class="faq-fancy-item">
        <button class="faq-fancy-q faq-q">
          <span class="faq-fancy-icon">&#128176;</span>
          <span>Wat kost het advies?</span>
        </button>
        <div class="faq-fancy-a faq-a">
          <p>Het reparatieadvies is volledig gratis en vrijblijvend. Je betaalt niets voor het aanvragen of ontvangen van advies. Alleen de schadetaxatie kost 49 euro. Aan het advies kunnen geen rechten worden ontleend.</p>
        </div>
      </div>
      <div class="faq-fancy-item">
        <button class="faq-fancy-q faq-q">
          <span class="faq-fancy-icon">&#128343;</span>
          <span>Hoe snel krijg ik een reactie?</span>
        </button>
        <div class="faq-fancy-a faq-a">
          <p>Je ontvangt binnen één werkdag een persoonlijk advies per e-mail. In drukke periodes kan dit oplopen tot 48 uur.</p>
        </div>
      </div>
      <div class="faq-fancy-item">
        <button class="faq-fancy-q faq-q">
          <span class="faq-fancy-icon">&#127968;</span>
          <span>Werkt Reparatieplatform.nl door heel Nederland?</span>
        </button>
        <div class="faq-fancy-a faq-a">
          <p>Ja, wij adviseren consumenten door heel Nederland. Reparatie aan huis is beschikbaar in de meeste regio's. Bij de aanvraag controleren wij je postcode op beschikbaarheid.</p>
        </div>
      </div>
      <div class="faq-fancy-item">
        <button class="faq-fancy-q faq-q">
          <span class="faq-fancy-icon">&#128196;</span>
          <span>Voor welke merken kunnen jullie adviseren?</span>
        </button>
        <div class="faq-fancy-a faq-a">
          <p>Wij adviseren voor alle grote televisiemerken: Samsung, LG, Sony, Philips, Panasonic, Hisense, TCL en meer. Zowel voor LED, OLED als QLED modellen.</p>
        </div>
      </div>
      <div class="faq-fancy-item">
        <button class="faq-fancy-q faq-q">
          <span class="faq-fancy-icon">&#129309;</span>
          <span>Wat als de verkoper niet meewerkt met coulance?</span>
        </button>
        <div class="faq-fancy-a faq-a">
          <p>Garantie en coulance worden bij ons als advies getoond op de website. Gaat een verkoper of merk niet mee met de coulance? Dan kun je altijd alsnog terecht voor vrijblijvend reparatieadvies. Wij helpen je verder, ook als de eerste route niet lukt.</p>
        </div>
      </div>
      <div class="faq-fancy-item">
        <button class="faq-fancy-q faq-q">
          <span class="faq-fancy-icon">&#128737;</span>
          <span>Is het advies bindend?</span>
        </button>
        <div class="faq-fancy-a faq-a">
          <p>Nee. Het advies van Reparatieplatform.nl is altijd indicatief en vrijblijvend. Je beslist zelf welke stappen je neemt. Aan het advies kunnen geen rechten worden ontleend.</p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- CTA -->
<div class="duurzaam-wrap">
  <div class="duurzaam-inner" style="grid-template-columns:1fr; text-align:center; gap:2rem;">
    <div>
      <h2 class="section-title">Klaar om te beginnen?</h2>
      <p class="section-lead" style="max-width:480px;margin:0 auto 2rem;">
        Vraag gratis en vrijblijvend advies aan. Het reparatieadvies kost je niets en wij helpen je binnen één werkdag verder.
      </p>
      <a href="<?= BASE_URL ?>/advies.php" class="btn-primary" style="margin:0 auto;">
        Gratis advies aanvragen
        <span class="btn-primary-arrow">&rarr;</span>
      </a>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>