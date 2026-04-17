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
    <p>Je beschrijft het probleem, wij beoordelen de situatie en sturen je advies per mail. Drie stappen, geen gedoe, geen kosten.</p>
  </div>
</div>

<!-- Stap voor stap -->
<div style="background:white; padding:5rem 0;">
  <div class="section" style="padding-top:0;padding-bottom:0;">
    <h2 class="section-title">In drie stappen geholpen</h2>
    <p class="section-lead">U hoeft de tv niet in te leveren, nergens heen te rijden en geen technische kennis te hebben. Gewoon het formulier invullen.</p>

    <div class="htw-steps">

      <div class="htw-step">
        <div class="htw-step-left">
          <div class="htw-step-nr">01</div>
          <div class="htw-step-line"></div>
        </div>
        <div class="htw-step-right">
          <div class="htw-step-icon">&#128221;</div>
          <h3>Formulier invullen</h3>
          <p>Het formulier heeft vier stappen. U geeft het merk en modelnummer op (staat achter op de tv of via Instellingen → Ondersteuning), het aanschafjaar en een omschrijving van het probleem. Bijv. &ldquo;zwarte strepen rechts in beeld&rdquo; of &ldquo;tv gaat niet meer aan na onweer&rdquo;.</p>
          <ul class="htw-list">
            <li>&#10003; Merk en modelnummer</li>
            <li>&#10003; Korte omschrijving van het defect</li>
            <li>&#10003; Uw e-mailadres voor het advies</li>
          </ul>
        </div>
      </div>

      <div class="htw-step">
        <div class="htw-step-left">
          <div class="htw-step-nr">02</div>
          <div class="htw-step-line"></div>
        </div>
        <div class="htw-step-right">
          <div class="htw-step-icon">&#128269;</div>
          <h3>We beoordelen uw aanvraag</h3>
          <p>Een specialist bekijkt uw aanvraag: valt het nog onder garantie, is er kans op coulance, of is reparatie of taxatie de beste route? We kijken ook naar het model zelf — sommige tv's zijn bekend om specifieke defecten waarvoor de fabrikant soms toch over de brug komt.</p>
          <ul class="htw-list">
            <li>&#10003; Garantie- en coulancecheck</li>
            <li>&#10003; Reparatiemogelijkheden per model</li>
            <li>&#10003; Eerlijk advies — ook als reparatie niet loont</li>
          </ul>
        </div>
      </div>

      <div class="htw-step htw-step-last">
        <div class="htw-step-left">
          <div class="htw-step-nr">03</div>
        </div>
        <div class="htw-step-right">
          <div class="htw-step-icon">&#128233;</div>
          <h3>Advies per e-mail, binnen 24 uur</h3>
          <p>U ontvangt een mail met de route die het meeste oplevert en wat u concreet moet doen. Dat kan zijn: garantie claimen bij de winkel, een coulanceregeling aanvragen, een monteur inplannen of een taxatierapport aanvragen voor uw verzekeraar.</p>
          <ul class="htw-list">
            <li>&#10003; Reactie binnen één werkdag</li>
            <li>&#10003; Concrete vervolgstappen, geen vaagtaal</li>
            <li>&#10003; Volledig gratis, geen verplichtingen</li>
          </ul>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- Welke opties zijn er -->
<div class="section-light">
  <div class="section" style="padding-top:4rem;padding-bottom:4rem;">
    <h2 class="section-title">Welke routes zijn er?</h2>
    <p class="section-lead">Uw situatie bepaalt welke route we adviseren. Er zijn er vier.</p>
    <div class="cards-grid">
      <a href="<?= BASE_URL ?>/garantie.php" class="adv-card" style="text-decoration:none;">
        <div class="adv-num">01</div>
        <div class="adv-card-icon">&#9989;</div>
        <h3>Garantie</h3>
        <p>Tv nog geen twee jaar oud? Dan heeft u waarschijnlijk recht op gratis reparatie of vervanging. We leggen uit hoe u dat aanvraagt — want de winkel geeft dat niet altijd vrijwillig toe.</p>
        <span class="adv-tag">Wettelijk recht</span>
      </a>
      <a href="<?= BASE_URL ?>/coulance.php" class="adv-card" style="text-decoration:none;">
        <div class="adv-num">02</div>
        <div class="adv-card-icon">&#129309;</div>
        <h3>Coulanceregeling</h3>
        <p>Garantietermijn verlopen, maar de tv ging eerder kapot dan je mag verwachten? Veel fabrikanten lossen dat toch op, mits je het op de juiste manier aanpakt.</p>
        <span class="adv-tag">Kans op vergoeding</span>
      </a>
      <a href="<?= BASE_URL ?>/reparatie.php" class="adv-card featured" style="text-decoration:none;">
        <div class="adv-num">03</div>
        <div class="adv-card-icon">&#128295;</div>
        <h3>Reparatie aan huis</h3>
        <p>Onze monteur komt bij u thuis. Vaste prijsindicatie vooraf, normaal binnen 2–3 werkdagen een afspraak en 3 maanden garantie op de reparatie.</p>
        <span class="adv-tag">Ons specialisme</span>
      </a>
      <a href="<?= BASE_URL ?>/taxatie.php" class="adv-card featured" style="text-decoration:none;">
        <div class="adv-num">04</div>
        <div class="adv-card-icon">&#128196;</div>
        <h3>Taxatierapport</h3>
        <p>Schade door stroomstoot, brand of inbraak? Uw verzekeraar vraagt dan om een officieel taxatierapport. Wij stellen dat op, inclusief dagwaarde en herstelkostenindicatie.</p>
        <span class="adv-tag">Geaccepteerd door verzekeraars</span>
      </a>
    </div>
  </div>
</div>

<!-- Veelgestelde vragen -->
<div style="background:white; padding:5rem 0;">
  <div class="section" style="padding-top:0;padding-bottom:0;">
    <h2 class="section-title">Veelgestelde vragen</h2>
    <p class="section-lead">Korte antwoorden op de vragen die we het vaakst krijgen.</p>
    <div class="faq-lijst-fancy">
      <div class="faq-fancy-item">
        <button class="faq-fancy-q faq-q">
          <span class="faq-fancy-icon">&#128176;</span>
          <span>Wat kost het advies?</span>
        </button>
        <div class="faq-fancy-a faq-a">
          <p>Het advies is gratis. U betaalt niets voor het aanvragen of ontvangen ervan. Alleen als u daarna kiest voor reparatie of een taxatierapport, zijn daar kosten aan verbonden. Aan het advies zelf kunnen geen rechten worden ontleend.</p>
        </div>
      </div>
      <div class="faq-fancy-item">
        <button class="faq-fancy-q faq-q">
          <span class="faq-fancy-icon">&#128343;</span>
          <span>Hoe snel ontvang ik een reactie?</span>
        </button>
        <div class="faq-fancy-a faq-a">
          <p>Normaal gesproken binnen één werkdag. In drukke periodes soms 48 uur. U ontvangt een mail zodra het advies klaarstaat, met een directe link naar uw aanvraag.</p>
        </div>
      </div>
      <div class="faq-fancy-item">
        <button class="faq-fancy-q faq-q">
          <span class="faq-fancy-icon">&#127968;</span>
          <span>Werkt Reparatieplatform.nl door heel Nederland?</span>
        </button>
        <div class="faq-fancy-a faq-a">
          <p>Advies: door heel Nederland. Reparatie aan huis: beschikbaar in de meeste regio's. Of uw postcode binnen het werkgebied valt, ziet u zodra de reparatieroute voor u van toepassing is.</p>
        </div>
      </div>
      <div class="faq-fancy-item">
        <button class="faq-fancy-q faq-q">
          <span class="faq-fancy-icon">&#128196;</span>
          <span>Voor welke merken kunt u adviseren?</span>
        </button>
        <div class="faq-fancy-a faq-a">
          <p>Voor alle grote merken: Samsung, LG, Sony, Philips, Panasonic, Hisense, TCL en meer. LED, OLED en QLED — het maakt niet uit. Als er een modelnummer op de achterkant staat, kunnen we ermee aan de slag.</p>
        </div>
      </div>
      <div class="faq-fancy-item">
        <button class="faq-fancy-q faq-q">
          <span class="faq-fancy-icon">&#128737;</span>
          <span>Is het advies bindend?</span>
        </button>
        <div class="faq-fancy-a faq-a">
          <p>Nee. U ontvangt een indicatief advies op basis van de informatie die u geeft. De uiteindelijke beslissing ligt bij u. Aan het advies kunnen geen rechten worden ontleend.</p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- CTA -->
<div class="duurzaam-wrap">
  <div class="duurzaam-inner" style="grid-template-columns:1fr; text-align:center; gap:2rem;">
    <div>
      <h2 class="section-title">Klaar?</h2>
      <p class="section-lead" style="max-width:480px;margin:0 auto 2rem;">
        Vul het formulier in en u hoort binnen één werkdag wat de beste route is. Gratis, geen verplichtingen.
      </p>
      <a href="<?= BASE_URL ?>/advies.php" class="btn-primary" style="margin:0 auto;">
        Gratis advies aanvragen
        <span class="btn-primary-arrow">&rarr;</span>
      </a>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
