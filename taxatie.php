<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle       = 'Taxatierapport televisie | Reparatieplatform.nl';
$pageDescription = 'Officieel taxatierapport voor uw defecte televisie. Nodig voor uw verzekeraar of bij een schadeclaim. Snel en betrouwbaar.';
$canonicalUrl    = '/taxatie.php';

include __DIR__ . '/includes/header.php';
?>

<div class="page-header">
  <div class="page-header-inner">
    <div class="breadcrumb">
      <a href="<?= BASE_URL ?>/">Home</a><span class="sep">/</span>
      <span style="color:rgba(255,255,255,.4)">Taxatierapport</span>
    </div>
    <h1>Schadetaxatie<br>televisie</h1>
    <p>Officieel rapport voor je verzekeraar of schadeclaim. Opgesteld door een erkende specialist voor 49 euro.</p>
  </div>
</div>

<!-- Uitleg (wit) -->
<div style="background:white; padding:5rem 0;">
  <div class="form-inner" style="max-width:1280px;margin:0 auto;padding:0 2.5rem;">
    <div class="form-left">
      <h2 class="section-title">Wat is een<br>taxatierapport?</h2>
      <p class="section-lead">
        Een officieel document waarin de waarde van je televisie wordt vastgesteld op het moment van het defect of de schade.
        Je verzekeraar gebruikt dit rapport om te bepalen welk bedrag je vergoed krijgt.
      </p>
      <p class="section-lead" style="margin-top:1rem;">
        De schadetaxatie kost 49 euro. Het reparatieadvies is altijd gratis. Weet je nog niet zeker welke route je nodig hebt? Vraag dan eerst gratis advies aan.
      </p>
      <div class="outcome-list">
        <div class="outcome-item"><div class="oi-icon oi-blue">&#127968;</div> Inboedelverzekering schadeclaim</div>
        <div class="outcome-item"><div class="oi-icon oi-orange">&#128196;</div> Aansprakelijkheidsstelling derden</div>
        <div class="outcome-item"><div class="oi-icon oi-yellow">&#129309;</div> Garantie of coulanceclaim bij fabrikant</div>
        <div class="outcome-item"><div class="oi-icon oi-purple">&#9878;</div> Juridische procedure of geschil</div>
      </div>
    </div>
    <div>
      <div class="form-card">
        <h3>Wat staat er in het rapport?</h3>
        <p>Een volledig en erkend taxatierapport met alle gegevens die je verzekeraar nodig heeft.</p>
        <div class="rapport-inhoud">
          <?php foreach ([
            'Merk, model en serienummer',
            'Technische beoordeling van het defect',
            'Dagwaarde op schadedatum',
            'Herstelkostenindicatie',
            'Handtekening erkende specialist',
            'Officieel bedrijfsstempel',
          ] as $item): ?>
          <div class="rapport-item">
            <span class="rapport-check">&#10003;</span>
            <span><?= $item ?></span>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="taxatie-prijs">
          <div>
            <span class="taxatie-prijs-label">Kosten schadetaxatie</span>
            <span class="taxatie-prijs-bedrag">&euro;49,&ndash;</span>
            <span class="taxatie-prijs-sub">incl. BTW &mdash; digitaal rapport binnen 2 werkdagen</span>
          </div>
        </div>
        <a href="<?= BASE_URL ?>/advies.php" class="submit-btn" style="display:flex;text-decoration:none;margin-top:1.5rem;">
          Taxatie aanvragen &rarr;
        </a>
        <div class="disclaimer-box" style="margin-top:1rem;margin-bottom:0;">
          &#9888;&#65039; Ons rapport voldoet aan de eisen van de meeste Nederlandse verzekeraars.
          Twijfel je? Neem dan eerst contact op met je verzekeraar om de exacte eisen te bespreken.
        </div>
      </div>
    </div>
  </div>
</div>

<!-- FAQ -->
<div class="section-light">
  <div class="section" style="padding-top:4rem;padding-bottom:4rem;">
    <h2 class="section-title">Veelgestelde vragen</h2>
    <p class="section-lead">Alles wat je wil weten over de schadetaxatie.</p>
    <div class="faq-lijst-fancy">
      <div class="faq-fancy-item">
        <button class="faq-fancy-q faq-q">
          <span class="faq-fancy-icon">&#128176;</span>
          <span>Wat kost de taxatie precies?</span>
        </button>
        <div class="faq-fancy-a faq-a">
          <p>De schadetaxatie kost 49 euro inclusief BTW. Het reparatieadvies is altijd gratis. Weet je nog niet zeker welke route je nodig hebt? Vraag dan eerst vrijblijvend gratis advies aan via ons formulier.</p>
        </div>
      </div>
      <div class="faq-fancy-item">
        <button class="faq-fancy-q faq-q">
          <span class="faq-fancy-icon">&#128338;</span>
          <span>Hoe snel ontvang ik het rapport?</span>
        </button>
        <div class="faq-fancy-a faq-a">
          <p>Na ontvangst van je gegevens stellen wij het rapport binnen 2 werkdagen op. Heb je spoed? Neem contact op voor de mogelijkheden.</p>
        </div>
      </div>
      <div class="faq-fancy-item">
        <button class="faq-fancy-q faq-q">
          <span class="faq-fancy-icon">&#9989;</span>
          <span>Accepteert mijn verzekeraar dit rapport?</span>
        </button>
        <div class="faq-fancy-a faq-a">
          <p>Ons rapport wordt opgesteld door een erkende televisietechnicus en voldoet aan de eisen van de meeste Nederlandse verzekeraars. Twijfel je? Neem dan eerst contact op met je verzekeraar om de exacte eisen te bespreken.</p>
        </div>
      </div>
      <div class="faq-fancy-item">
        <button class="faq-fancy-q faq-q">
          <span class="faq-fancy-icon">&#128269;</span>
          <span>Moet de televisie fysiek worden gekeurd?</span>
        </button>
        <div class="faq-fancy-a faq-a">
          <p>In de meeste gevallen kunnen wij het rapport opstellen op basis van foto's, het modelnummer en een beschrijving van het defect. Bij complexe schades kan een fysieke keuring nodig zijn.</p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- CTA -->
<div class="duurzaam-wrap">
  <div class="duurzaam-inner" style="grid-template-columns:1fr; text-align:center; gap:2rem;">
    <div>
      <h2 class="section-title">Direct een taxatierapport aanvragen</h2>
      <p class="section-lead" style="max-width:480px;margin:0 auto 2rem;">
        Vul het formulier in en ontvang je officiële rapport binnen 2 werkdagen. De schadetaxatie kost 49 euro.
      </p>
      <a href="<?= BASE_URL ?>/advies.php" class="btn-primary" style="margin:0 auto;">
        Taxatie aanvragen
        <span class="btn-primary-arrow">&rarr;</span>
      </a>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>