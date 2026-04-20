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

<!-- In drie stappen geholpen – nieuwe stijl -->
<div class="zowerkhet-section" id="stappen">
  <div class="section" style="padding-top:0;padding-bottom:0;">
    <div style="text-align:center;margin-bottom:0;">
      <div style="display:inline-flex;align-items:center;gap:.45rem;background:rgba(40,120,100,.15);border:1px solid rgba(40,120,100,.3);border-radius:999px;padding:.3rem 1rem;font-size:.75rem;font-weight:700;color:#4ecb9e;margin-bottom:1.1rem;letter-spacing:.04em;">
        &#9881; Eenvoudig proces
      </div>
      <h2 class="section-title">In drie stappen geholpen</h2>
      <p class="section-lead" style="max-width:48ch;margin:.6rem auto 0;">
        Geen technische kennis nodig. Beschrijf het probleem en wij regelen de rest.
      </p>
    </div>
    <div class="zowerkhet-steps">
      <div class="zowerkhet-step">
        <span class="zowerkhet-step-num">Stap 01</span>
        <div class="zowerkhet-step-icon">&#128221;</div>
        <h3>Formulier invullen</h3>
        <p>Vul het korte adviesformulier in met merk, modelnummer en een omschrijving van het probleem. Kost minder dan twee minuten — geen technische kennis vereist.</p>
        <ul style="list-style:none;padding:0;margin:.5rem 0 0;display:flex;flex-direction:column;gap:.35rem;">
          <li style="font-size:.8rem;color:rgba(255,255,255,.45);display:flex;gap:.5rem;"><span style="color:#4ecb9e;">&#10003;</span> Merk en modelnummer (staat achter op de tv)</li>
          <li style="font-size:.8rem;color:rgba(255,255,255,.45);display:flex;gap:.5rem;"><span style="color:#4ecb9e;">&#10003;</span> Korte omschrijving van het defect</li>
          <li style="font-size:.8rem;color:rgba(255,255,255,.45);display:flex;gap:.5rem;"><span style="color:#4ecb9e;">&#10003;</span> Uw e-mailadres voor het advies</li>
        </ul>
        <span class="zowerkhet-step-badge">&#10003; Gratis</span>
      </div>
      <div class="zowerkhet-step">
        <span class="zowerkhet-step-num">Stap 02</span>
        <div class="zowerkhet-step-icon">&#128269;</div>
        <h3>Wij analyseren uw situatie</h3>
        <p>Een specialist bekijkt uw aanvraag en toetst aan garantie- en coulanceregelingen, reparatiemogelijkheden en de waarde van het toestel. Advies op maat, niet van een script.</p>
        <ul style="list-style:none;padding:0;margin:.5rem 0 0;display:flex;flex-direction:column;gap:.35rem;">
          <li style="font-size:.8rem;color:rgba(255,255,255,.45);display:flex;gap:.5rem;"><span style="color:#4ecb9e;">&#10003;</span> Garantie- en coulancecheck</li>
          <li style="font-size:.8rem;color:rgba(255,255,255,.45);display:flex;gap:.5rem;"><span style="color:#4ecb9e;">&#10003;</span> Reparatiemogelijkheden in kaart</li>
          <li style="font-size:.8rem;color:rgba(255,255,255,.45);display:flex;gap:.5rem;"><span style="color:#4ecb9e;">&#10003;</span> Eerlijk advies — ook als reparatie niet loont</li>
        </ul>
        <span class="zowerkhet-step-badge">&#10003; Persoonlijk advies</span>
      </div>
      <div class="zowerkhet-step">
        <span class="zowerkhet-step-num">Stap 03</span>
        <div class="zowerkhet-step-icon">&#128233;</div>
        <h3>Persoonlijk advies binnen 24 uur</h3>
        <p>U ontvangt een helder persoonlijk advies per e-mail met concrete vervolgstappen — garantie, coulance, reparatie of taxatie. Wij wijzen u de beste weg.</p>
        <ul style="list-style:none;padding:0;margin:.5rem 0 0;display:flex;flex-direction:column;gap:.35rem;">
          <li style="font-size:.8rem;color:rgba(255,255,255,.45);display:flex;gap:.5rem;"><span style="color:#4ecb9e;">&#10003;</span> Reactie binnen één werkdag</li>
          <li style="font-size:.8rem;color:rgba(255,255,255,.45);display:flex;gap:.5rem;"><span style="color:#4ecb9e;">&#10003;</span> Duidelijke vervolgstappen</li>
          <li style="font-size:.8rem;color:rgba(255,255,255,.45);display:flex;gap:.5rem;"><span style="color:#4ecb9e;">&#10003;</span> Volledig gratis en vrijblijvend</li>
        </ul>
        <span class="zowerkhet-step-badge">&#10003; Binnen 1 werkdag</span>
      </div>
    </div>
  </div>
</div>

<style>
/* ── Zo werkt het / In drie stappen – gedeelde stijl ───────── */
.zowerkhet-section {
  background: #0d1117;
  padding: 5rem 0;
}
.zowerkhet-section .section-title { color: #fff; }
.zowerkhet-section .section-lead  { color: rgba(255,255,255,.55); }
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
.zowerkhet-step-icon { font-size: 1.75rem; line-height: 1; }
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
  .zowerkhet-step  { padding: 1.5rem 1.25rem; }
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