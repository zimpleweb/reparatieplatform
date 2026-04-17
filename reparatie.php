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
    <p>Onze monteur komt naar u toe. U hoeft de tv niet in te pakken of ergens heen te rijden. Normaal binnen 2–3 werkdagen een afspraak, 3 maanden garantie op de reparatie.</p>
  </div>
</div>

<!-- Voordelen -->
<div style="background:white; padding:5rem 0;">
  <div class="section" style="padding-top:0;padding-bottom:0;">
    <h2 class="section-title">Waarom reparatie aan huis?</h2>
    <p class="section-lead">Een tv meenemen naar een servicecentrum is omslachtig. Wij komen gewoon bij u langs.</p>
    <div class="cards-grid">
      <div class="adv-card">
        <div class="adv-num">01</div>
        <div class="adv-card-icon">&#128343;</div>
        <h3>Snel afspraak</h3>
        <p>Normaal binnen 2–3 werkdagen een monteur bij u thuis. De reparatie zelf duurt gemiddeld 1 à 2 uur, afhankelijk van het defect.</p>
        <span class="adv-tag">Binnen 3 werkdagen</span>
      </div>
      <div class="adv-card">
        <div class="adv-num">02</div>
        <div class="adv-card-icon">&#128176;</div>
        <h3>Prijsindicatie vooraf</h3>
        <p>U weet van tevoren wat het kost. Geen losse eindjes of onverwachte rekening achteraf. De indicatie is gebaseerd op het defect en het model.</p>
        <span class="adv-tag">Geen verrassingen</span>
      </div>
      <div class="adv-card featured">
        <div class="adv-num">03</div>
        <div class="adv-card-icon">&#9989;</div>
        <h3>3 maanden garantie</h3>
        <p>Op elke reparatie geven we 3 maanden garantie. Mocht hetzelfde probleem terugkomen, komt de monteur terug zonder meerkosten.</p>
        <span class="adv-tag">3 maanden garantie</span>
      </div>
      <div class="adv-card featured">
        <div class="adv-num">04</div>
        <div class="adv-card-icon">&#128295;</div>
        <h3>Specialist per merk</h3>
        <p>We repareren Samsung, LG, Sony, Philips en meer. Zowel LED als OLED. Onze technici kennen de bekende zwakke plekken per modelserie.</p>
        <span class="adv-tag">Alle grote merken</span>
      </div>
    </div>
  </div>
</div>

<!-- FAQ (witte achtergrond) -->
<div class="section-light">
  <div class="section" style="padding-top:4rem;padding-bottom:4rem;">
    <h2 class="section-title">Veelgestelde vragen</h2>
    <p class="section-lead">De vragen die we het vaakst krijgen, direct beantwoord.</p>
    <div class="faq-lijst-fancy">
      <div class="faq-fancy-item">
        <button class="faq-fancy-q faq-q">
          <span class="faq-fancy-icon">&#128343;</span>
          <span>Welke merken repareert u?</span>
        </button>
        <div class="faq-fancy-a faq-a">
          <p>Samsung, LG, Sony, Philips, Panasonic, Hisense en TCL. Zowel LED, OLED als QLED. Voor Philips en Sony hebben we ook toegang tot originele onderdelen, wat bij onbekendere merken niet altijd het geval is.</p>
        </div>
      </div>
      <div class="faq-fancy-item">
        <button class="faq-fancy-q faq-q">
          <span class="faq-fancy-icon">&#128176;</span>
          <span>Wat zijn de kosten van een reparatie?</span>
        </button>
        <div class="faq-fancy-a faq-a">
          <p>Dat hangt af van het defect en het model. Een backlight-reparatie kost gemiddeld €80–€150. Een stroomvoedingsprobleem zit daar iets onder, rond de €60–€100. U ontvangt altijd een prijsindicatie vóór de reparatie — u beslist daarna of u ermee door wilt.</p>
        </div>
      </div>
      <div class="faq-fancy-item">
        <button class="faq-fancy-q faq-q">
          <span class="faq-fancy-icon">&#127968;</span>
          <span>Wordt de reparatie vergoed door mijn verzekering?</span>
        </button>
        <div class="faq-fancy-a faq-a">
          <p>Soms wel. Bij een inboedelverzekering met dekking voor elektronica is vergoeding mogelijk, maar uw verzekeraar heeft dan meestal een officieel taxatierapport nodig. Dat kunnen we voor u opstellen — vraag er naar bij uw adviesaanvraag.</p>
        </div>
      </div>
      <div class="faq-fancy-item">
        <button class="faq-fancy-q faq-q">
          <span class="faq-fancy-icon">&#9878;</span>
          <span>Is reparatie zinvol of kan ik beter een nieuwe kopen?</span>
        </button>
        <div class="faq-fancy-a faq-a">
          <p>Als de reparatiekosten meer dan de helft van de huidige vervangingswaarde zijn, is een nieuwe tv vaak goedkoper. Onze specialist rekent dat voor u door en adviseert eerlijk — ook als dat betekent dat reparatie niet de beste keuze is.</p>
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
        Vraag eerst gratis advies aan. Als reparatie aan huis de juiste route is, plannen we een afspraak in uw regio.
      </p>
      <a href="<?= BASE_URL ?>/advies.php" class="btn-primary" style="margin:0 auto;">
        Gratis advies aanvragen
        <span class="btn-primary-arrow">&rarr;</span>
      </a>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>