<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle       = 'Hoe het werkt – Van defecte tv naar oplossing | Reparatieplatform.nl';
$pageDescription = 'Ontdek hoe Reparatieplatform.nl werkt. In drie stappen van defecte televisie naar persoonlijk advies over garantie, reparatie of taxatie.';
$canonicalUrl    = '/hoe-het-werkt.php';

include __DIR__ . '/includes/header.php';
?>

<div class="page-header">
  <div class="page-header-inner">
    <div class="breadcrumb">
      <a href="<?= BASE_URL ?>/">Home</a><span class="sep">/</span>
      <span style="color:rgba(255,255,255,.4)">Hoe het werkt</span>
    </div>
    <h1>Hoe werkt<br>Reparatieplatform.nl?</h1>
    <p>Van defecte televisie naar de beste oplossing — in drie eenvoudige stappen. Gratis en vrijblijvend.</p>
  </div>
</div>

<!-- Stap voor stap -->
<div style="background:white; padding:5rem 0;">
  <div class="section" style="padding-top:0;padding-bottom:0;">
    <h2 class="section-title">In drie stappen geholpen</h2>
    <p class="section-lead">Geen technische kennis nodig. Beschrijf het probleem en wij regelen de rest.</p>

    <div class="htw-stappen">

      <div class="htw-stap">
        <div class="htw-stap-badge">
          <span class="htw-stap-emoji">&#128221;</span>
          <span class="htw-stap-nr">01</span>
        </div>
        <div class="htw-stap-lijn" aria-hidden="true"></div>
        <div class="htw-stap-body">
          <h3>Formulier invullen</h3>
          <p>Vul het korte adviesformulier in met het merk, het modelnummer en een omschrijving van het probleem. Dit kost minder dan twee minuten — geen technische kennis vereist.</p>
          <ul class="htw-checklist">
            <li><span class="htw-vink">&#10003;</span> Merk en modelnummer (staat achter op de tv)</li>
            <li><span class="htw-vink">&#10003;</span> Korte omschrijving van het defect</li>
            <li><span class="htw-vink">&#10003;</span> Uw e-mailadres voor het advies</li>
          </ul>
        </div>
      </div>

      <div class="htw-stap">
        <div class="htw-stap-badge">
          <span class="htw-stap-emoji">&#128269;</span>
          <span class="htw-stap-nr">02</span>
        </div>
        <div class="htw-stap-lijn" aria-hidden="true"></div>
        <div class="htw-stap-body">
          <h3>Wij analyseren uw situatie</h3>
          <p>Een specialist bekijkt uw aanvraag en toetst aan de garantie- en coulanceregelingen van de fabrikant, de reparatiemogelijkheden en de waarde van het toestel. Advies op maat, niet van een script.</p>
          <ul class="htw-checklist">
            <li><span class="htw-vink">&#10003;</span> Garantie- en coulancecheck</li>
            <li><span class="htw-vink">&#10003;</span> Reparatiemogelijkheden in kaart</li>
            <li><span class="htw-vink">&#10003;</span> Eerlijk advies — ook als reparatie niet loont</li>
          </ul>
        </div>
      </div>

      <div class="htw-stap htw-stap-last">
        <div class="htw-stap-badge">
          <span class="htw-stap-emoji">&#128233;</span>
          <span class="htw-stap-nr">03</span>
        </div>
        <div class="htw-stap-body">
          <h3>Persoonlijk advies binnen 24 uur</h3>
          <p>U ontvangt een helder persoonlijk advies per e-mail met concrete vervolgstappen — garantie, coulance, reparatie of taxatie. Wij wijzen u de beste weg.</p>
          <ul class="htw-checklist">
            <li><span class="htw-vink">&#10003;</span> Reactie binnen één werkdag</li>
            <li><span class="htw-vink">&#10003;</span> Duidelijke vervolgstappen</li>
            <li><span class="htw-vink">&#10003;</span> Volledig gratis en vrijblijvend</li>
          </ul>
        </div>
      </div>

    </div>
  </div>
</div>

<style>
/* ── In drie stappen geholpen ─────────────────────────────────── */
.htw-stappen {
  display: flex;
  flex-direction: column;
  gap: 0;
  max-width: 780px;
}
.htw-stap {
  display: grid;
  grid-template-columns: 72px 2px 1fr;
  gap: 0 2rem;
  padding-bottom: 2.75rem;
}
.htw-stap-last {
  padding-bottom: 0;
  grid-template-columns: 72px 1fr;
}
.htw-stap-badge {
  width: 64px; height: 64px;
  border-radius: 50%;
  background: var(--accent-light, #e8f4f1);
  border: 2px solid #b2ddd4;
  display: flex; align-items: center; justify-content: center;
  position: relative;
  flex-shrink: 0;
  transition: transform .25s ease, box-shadow .25s ease;
}
.htw-stap:hover .htw-stap-badge {
  transform: scale(1.06);
  box-shadow: 0 8px 24px rgba(40,120,100,.15);
}
.htw-stap-emoji {
  font-size: 1.55rem; line-height: 1;
}
.htw-stap-nr {
  position: absolute;
  top: -6px; right: -6px;
  width: 24px; height: 24px;
  border-radius: 50%;
  background: var(--accent, #287864);
  color: #fff;
  font-size: .62rem; font-weight: 800;
  display: flex; align-items: center; justify-content: center;
  border: 2px solid #fff;
  letter-spacing: -.01em;
}
.htw-stap-lijn {
  width: 2px;
  background: linear-gradient(to bottom, var(--accent, #287864) 0%, var(--border, #e5e4e0) 100%);
  border-radius: 2px;
  margin: 0 auto;
  opacity: .4;
}
.htw-stap-body {
  padding-top: .75rem;
}
.htw-stap-body h3 {
  font-size: 1.15rem; font-weight: 800;
  color: var(--ink, #0d0f14);
  margin-bottom: .5rem;
  letter-spacing: -.02em;
}
.htw-stap-body > p {
  font-size: .9rem; color: var(--muted, #6b7280);
  line-height: 1.7; margin: 0 0 1rem;
  max-width: 56ch;
}
.htw-checklist {
  list-style: none; padding: 0; margin: 0;
  display: flex; flex-direction: column; gap: .45rem;
}
.htw-checklist li {
  display: flex; align-items: flex-start; gap: .6rem;
  font-size: .875rem; color: var(--ink, #0d0f14); font-weight: 500;
}
.htw-vink {
  display: flex; align-items: center; justify-content: center;
  width: 20px; height: 20px; border-radius: 50%;
  background: var(--accent-light, #e8f4f1);
  color: var(--accent, #287864);
  font-size: .7rem; font-weight: 700;
  flex-shrink: 0; margin-top: .05rem;
}
@media (max-width: 600px) {
  .htw-stap { grid-template-columns: 56px 2px 1fr; gap: 0 1.25rem; }
  .htw-stap-last { grid-template-columns: 56px 1fr; }
  .htw-stap-badge { width: 52px; height: 52px; }
  .htw-stap-emoji { font-size: 1.3rem; }
  .htw-stap-body h3 { font-size: 1rem; }
}
</style>

<!-- Welke opties zijn er -->
<div class="section-light">
  <div class="section" style="padding-top:4rem;padding-bottom:4rem;">
    <h2 class="section-title">Welke oplossingen bieden wij?</h2>
    <p class="section-lead">Afhankelijk van uw situatie adviseren wij één van de volgende routes.</p>
    <div class="cards-grid">
      <a href="<?= BASE_URL ?>/garantie.php" class="adv-card" style="text-decoration:none;">
        <div class="adv-num">01</div>
        <div class="adv-card-icon">&#9989;</div>
        <h3>Garantie</h3>
        <p>Televisie nog binnen de garantietermijn? Ontdek of u recht hebt op gratis reparatie of vervanging door de fabrikant.</p>
        <span class="adv-tag">Wettelijk recht</span>
      </a>
      <a href="<?= BASE_URL ?>/coulance.php" class="adv-card" style="text-decoration:none;">
        <div class="adv-num">02</div>
        <div class="adv-card-icon">&#129309;</div>
        <h3>Coulanceregeling</h3>
        <p>Garantie verlopen maar televisie al snel kapot? Veel fabrikanten bieden een coulanceregeling aan buiten de garantietermijn.</p>
        <span class="adv-tag">Kans op vergoeding</span>
      </a>
      <a href="<?= BASE_URL ?>/reparatie.php" class="adv-card featured" style="text-decoration:none;">
        <div class="adv-num">03</div>
        <div class="adv-card-icon">&#128295;</div>
        <h3>Reparatie aan huis</h3>
        <p>Een gecertificeerde monteur komt bij u thuis. Transparante prijzen, snel geholpen en drie maanden garantie op de reparatie.</p>
        <span class="adv-tag">Ons specialisme</span>
      </a>
      <a href="<?= BASE_URL ?>/taxatie.php" class="adv-card featured" style="text-decoration:none;">
        <div class="adv-num">04</div>
        <div class="adv-card-icon">&#128196;</div>
        <h3>Taxatierapport</h3>
        <p>Schade door stroom, brand of inbraak? Wij stellen een officieel taxatierapport op dat geaccepteerd wordt door uw verzekeraar.</p>
        <span class="adv-tag">Geaccepteerd door verzekeraars</span>
      </a>
    </div>
  </div>
</div>

<!-- Veelgestelde vragen -->
<div style="background:white; padding:5rem 0;">
  <div class="section" style="padding-top:0;padding-bottom:0;">
    <h2 class="section-title">Veelgestelde vragen</h2>
    <p class="section-lead">Alles wat u wilt weten over hoe Reparatieplatform.nl werkt.</p>
    <div class="faq-lijst-fancy">
      <div class="faq-fancy-item">
        <button class="faq-fancy-q faq-q">
          <span class="faq-fancy-icon">&#128176;</span>
          <span>Wat kost het advies?</span>
        </button>
        <div class="faq-fancy-a faq-a">
          <p>Het advies is volledig gratis en vrijblijvend. U betaalt niets voor het aanvragen of ontvangen van advies. Aan dit advies kunnen geen rechten worden ontleend.</p>
        </div>
      </div>
      <div class="faq-fancy-item">
        <button class="faq-fancy-q faq-q">
          <span class="faq-fancy-icon">&#128343;</span>
          <span>Hoe snel ontvang ik een reactie?</span>
        </button>
        <div class="faq-fancy-a faq-a">
          <p>U ontvangt binnen één werkdag een persoonlijk advies per e-mail. In drukke periodes kan dit uitlopen tot 48 uur.</p>
        </div>
      </div>
      <div class="faq-fancy-item">
        <button class="faq-fancy-q faq-q">
          <span class="faq-fancy-icon">&#127968;</span>
          <span>Werkt Reparatieplatform.nl door heel Nederland?</span>
        </button>
        <div class="faq-fancy-a faq-a">
          <p>Ja, wij adviseren consumenten door heel Nederland. Reparatie aan huis is beschikbaar in de meeste regio's. Bij de aanvraag wordt uw postcode gecontroleerd op beschikbaarheid.</p>
        </div>
      </div>
      <div class="faq-fancy-item">
        <button class="faq-fancy-q faq-q">
          <span class="faq-fancy-icon">&#128196;</span>
          <span>Voor welke merken kunt u adviseren?</span>
        </button>
        <div class="faq-fancy-a faq-a">
          <p>Wij adviseren voor alle grote televisiemerken: Samsung, LG, Sony, Philips, Panasonic, Hisense, TCL en meer. Zowel voor LED, OLED als QLED modellen.</p>
        </div>
      </div>
      <div class="faq-fancy-item">
        <button class="faq-fancy-q faq-q">
          <span class="faq-fancy-icon">&#128737;</span>
          <span>Is het advies bindend?</span>
        </button>
        <div class="faq-fancy-a faq-a">
          <p>Nee. Het advies van Reparatieplatform.nl is altijd indicatief en vrijblijvend. U beslist zelf welke vervolgstappen u neemt. Aan het advies kunnen geen rechten worden ontleend.</p>
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
        Vraag gratis en vrijblijvend advies aan. Wij helpen u binnen één werkdag verder.
      </p>
      <a href="<?= BASE_URL ?>/advies.php" class="btn-primary" style="margin:0 auto;">
        Gratis advies aanvragen
        <span class="btn-primary-arrow">&rarr;</span>
      </a>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
