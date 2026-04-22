<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle       = 'Televisie reparatie aan huis | Reparatieplatform.nl';
$pageDescription = 'Laat uw televisie repareren aan huis door een erkende specialist. Snelle service, vaste prijzen en garantie op de reparatie.';
$canonicalUrl    = '/reparatie.php';

include __DIR__ . '/includes/header.php';
?>

<div class="page-header">
  <div class="page-header-inner">
    <div class="breadcrumb">
      <a href="<?= BASE_URL ?>/">Home</a><span class="sep">/</span>
      <span style="color:rgba(255,255,255,.4)">Reparatie aan huis</span>
    </div>
    <h1>Televisie reparatie<br>aan huis</h1>
    <p>Een erkende specialist komt bij je thuis. Snel geholpen, transparante prijzen en drie maanden garantie op de reparatie.</p>
  </div>
</div>

<!-- Voordelen -->
<div style="background:white; padding:5rem 0;">
  <div class="section" style="padding-top:0;padding-bottom:0;">
    <h2 class="section-title">Waarom reparatie aan huis?</h2>
    <p class="section-lead">Je televisie hoeft nergens naartoe. Onze specialist komt gewoon bij je langs en lost het ter plekke op.</p>
    <div class="cards-grid">
      <div class="adv-card">
        <div class="adv-num">01</div>
        <div class="adv-card-icon">&#128343;</div>
        <h3>Snel</h3>
        <p>In de meeste gevallen maak je binnen 2 tot 3 werkdagen een afspraak. De reparatie zelf duurt gemiddeld 1 à 2 uur.</p>
        <span class="adv-tag">Binnen 3 werkdagen</span>
      </div>
      <div class="adv-card">
        <div class="adv-num">02</div>
        <div class="adv-card-icon">&#128176;</div>
        <h3>Vaste prijzen</h3>
        <p>Geen verrassingen achteraf. Je ontvangt vooraf een heldere prijsindicatie op basis van het defect en het model.</p>
        <span class="adv-tag">Transparant</span>
      </div>
      <div class="adv-card featured">
        <div class="adv-num">03</div>
        <div class="adv-card-icon">&#9989;</div>
        <h3>Garantie op reparatie</h3>
        <p>Op elke reparatie staat 3 maanden garantie. Mocht het probleem terugkomen, lossen wij het kosteloos op. Geen gedoe.</p>
        <span class="adv-tag">3 maanden garantie</span>
      </div>
      <div class="adv-card featured">
        <div class="adv-num">04</div>
        <div class="adv-card-icon">&#128295;</div>
        <h3>Erkende specialist</h3>
        <p>Onze technici zijn opgeleid voor alle grote merken: Samsung, LG, Sony, Philips en meer. Zowel LED, OLED als QLED.</p>
        <span class="adv-tag">Alle grote merken</span>
      </div>
    </div>
  </div>
</div>

<!-- FAQ -->
<div class="section-light">
  <div class="section" style="padding-top:4rem;padding-bottom:4rem;">
    <h2 class="section-title">Veelgestelde vragen</h2>
    <p class="section-lead">Alles wat je wil weten over reparatie aan huis.</p>
    <div class="faq-lijst-fancy">
      <div class="faq-fancy-item">
        <button class="faq-fancy-q faq-q">
          <span class="faq-fancy-icon">&#128343;</span>
          <span>Welke merken repareren jullie?</span>
        </button>
        <div class="faq-fancy-a faq-a">
          <p>Wij repareren alle grote merken: Samsung, LG, Sony, Philips, Panasonic, Hisense en TCL. Zowel LED, OLED als QLED televisies.</p>
        </div>
      </div>
      <div class="faq-fancy-item">
        <button class="faq-fancy-q faq-q">
          <span class="faq-fancy-icon">&#128176;</span>
          <span>Wat kost een reparatie?</span>
        </button>
        <div class="faq-fancy-a faq-a">
          <p>De kosten hangen af van het defect en het model. Een backlight reparatie kost gemiddeld 80 tot 150 euro, een stroomvoedingsprobleem gemiddeld 60 tot 100 euro. Je ontvangt altijd een prijsindicatie vóór de reparatie begint.</p>
        </div>
      </div>
      <div class="faq-fancy-item">
        <button class="faq-fancy-q faq-q">
          <span class="faq-fancy-icon">&#127968;</span>
          <span>Vergoedt mijn verzekering de reparatie?</span>
        </button>
        <div class="faq-fancy-a faq-a">
          <p>Bij een inboedelverzekering met dekking voor elektronica kan de reparatie vergoed worden. Voor je verzekeraar heb je mogelijk een taxatierapport nodig. Wij stellen dat graag voor je op voor 49 euro.</p>
        </div>
      </div>
      <div class="faq-fancy-item">
        <button class="faq-fancy-q faq-q">
          <span class="faq-fancy-icon">&#9878;</span>
          <span>Is repareren slim of kan ik beter een nieuwe kopen?</span>
        </button>
        <div class="faq-fancy-a faq-a">
          <p>Als vuistregel geldt: als de reparatiekosten meer dan 50% van de nieuwwaarde bedragen, is vervanging voordeliger. Onze specialist adviseert je eerlijk, ook als dat betekent dat reparatie niet loont. Het advies is gratis en vrijblijvend.</p>
        </div>
      </div>
      <div class="faq-fancy-item">
        <button class="faq-fancy-q faq-q">
          <span class="faq-fancy-icon">&#9989;</span>
          <span>Wat houdt de garantie op reparatie in?</span>
        </button>
        <div class="faq-fancy-a faq-a">
          <p>Na elke reparatie geldt er 3 maanden garantie op het uitgevoerde werk en de vervangen onderdelen. Komt hetzelfde probleem terug binnen die periode? Dan komt onze specialist kosteloos terug.</p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- CTA -->
<div class="duurzaam-wrap">
  <div class="duurzaam-inner" style="grid-template-columns:1fr; text-align:center; gap:2rem;">
    <div>
      <h2 class="section-title">Reparatie aanvragen</h2>
      <p class="section-lead" style="max-width:480px;margin:0 auto 2rem;">
        Vraag gratis advies aan en wij beoordelen of reparatie aan huis de beste optie is voor jouw situatie. Het advies is volledig gratis.
      </p>
      <a href="<?= BASE_URL ?>/advies.php" class="btn-primary" style="margin:0 auto;">
        Gratis advies aanvragen
        <span class="btn-primary-arrow">&rarr;</span>
      </a>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>