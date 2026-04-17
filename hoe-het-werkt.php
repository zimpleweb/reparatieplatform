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

    <div class="htw-steps">

      <div class="htw-step">
        <div class="htw-step-left">
          <div class="htw-step-nr">01</div>
          <div class="htw-step-line"></div>
        </div>
        <div class="htw-step-right">
          <div class="htw-step-icon">&#128221;</div>
          <h3>Formulier invullen</h3>
          <p>Vul het korte adviesformulier in met het merk, het modelnummer en een omschrijving van het probleem. Dit kost minder dan twee minuten. U hoeft geen technische kennis te hebben — een simpele beschrijving volstaat.</p>
          <ul class="htw-list">
            <li>&#10003; Merk en modelnummer (staat achter op de tv)</li>
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
          <h3>Wij analyseren uw situatie</h3>
          <p>Een specialist bekijkt uw aanvraag en toetst deze aan de garantie- en coulanceregelingen van de fabrikant, de huidige reparatiemogelijkheden en de waarde van het toestel. Zo krijgt u een advies dat past bij uw specifieke situatie.</p>
          <ul class="htw-list">
            <li>&#10003; Garantie- en coulancecheck</li>
            <li>&#10003; Reparatiemogelijkheden in kaart</li>
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
          <h3>Persoonlijk advies binnen 24 uur</h3>
          <p>U ontvangt een helder en persoonlijk advies per e-mail met concrete vervolgstappen. Of dat nu garantie aanspreken, een coulanceregeling, reparatie aan huis of een taxatierapport is — wij wijzen u de beste weg.</p>
          <ul class="htw-list">
            <li>&#10003; Reactie binnen één werkdag</li>
            <li>&#10003; Duidelijke vervolgstappen</li>
            <li>&#10003; Volledig gratis en vrijblijvend</li>
          </ul>
        </div>
      </div>

    </div>
  </div>
</div>

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
