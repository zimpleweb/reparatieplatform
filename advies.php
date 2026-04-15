<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle       = 'Gratis advies aanvragen – Televisie kapot? | Reparatieplatform.nl';
$pageDescription = 'Vraag gratis persoonlijk advies aan over garantie, coulanceregeling, reparatie of taxatie van uw defecte televisie.';
$canonicalUrl    = '/advies.php';

include __DIR__ . '/includes/header.php';
?>

<div class="page-header">
  <div class="page-header-inner">
    <div class="breadcrumb">
      <a href="<?= BASE_URL ?>/">Home</a><span class="sep">/</span>
      <span style="color:rgba(255,255,255,.4)">Advies aanvragen</span>
    </div>
    <h1>Gratis advies aanvragen</h1>
    <p>Vertel ons wat er mis is met je televisie — wij geven eerlijk en persoonlijk advies binnen 24 uur.</p>
  </div>
</div>

<!-- Stappen (lichte achtergrond, want header al donker) -->
<div class="section-light">
  <div class="section" style="padding-top:4rem;padding-bottom:4rem;">
    <h2 class="section-title">Zo werkt het</h2>
    <p class="section-lead">Geen technische kennis nodig. Beschrijf het probleem en wij regelen de rest.</p>
    <div class="steps-grid-light">
      <div class="step-light">
        <div class="step-light-nr">01</div>
        <div class="step-light-icon">&#128221;</div>
        <h3>Formulier invullen</h3>
        <p>Geef je merk, modelnummer en een korte omschrijving. Klaar in minder dan 2 minuten.</p>
      </div>
      <div class="step-light">
        <div class="step-light-nr">02</div>
        <div class="step-light-icon">&#128269;</div>
        <h3>Wij analyseren</h3>
        <p>Een specialist bekijkt jouw situatie en toetst aan garantie- en coulanceregels van de fabrikant.</p>
      </div>
      <div class="step-light">
        <div class="step-light-nr">03</div>
        <div class="step-light-icon">&#128233;</div>
        <h3>Persoonlijk advies</h3>
        <p>Je ontvangt binnen 24 uur een helder advies met concrete vervolgstappen — gratis en vrijblijvend.</p>
      </div>
    </div>
  </div>
</div>

<!-- Adviesopties -->
<div style="background:white; padding:5rem 0;">
  <div class="section" style="padding-top:0;padding-bottom:0;">
    <h2 class="section-title">Welk advies past bij jou?</h2>
    <p class="section-lead">Niet zeker welke route het beste is? Vul het formulier in — wij bepalen het voor je.</p>
    <div class="cards-grid">
      <a href="<?= BASE_URL ?>/garantie.php" class="adv-card" style="text-decoration:none;">
        <div class="adv-num">01</div>
        <div class="adv-card-icon">&#9989;</div>
        <h3>Garantie</h3>
        <p>Televisie binnen de garantietermijn kapot? Ontdek of je recht hebt op gratis reparatie of vervanging.</p>
        <span class="adv-tag">Wettelijk recht</span>
      </a>
      <a href="<?= BASE_URL ?>/coulance.php" class="adv-card" style="text-decoration:none;">
        <div class="adv-num">02</div>
        <div class="adv-card-icon">&#129309;</div>
        <h3>Coulanceregeling</h3>
        <p>Garantie verlopen maar televisie al snel kapot? Veel fabrikanten bieden een coulanceregeling aan.</p>
        <span class="adv-tag">Kans op vergoeding</span>
      </a>
      <a href="<?= BASE_URL ?>/reparatie.php" class="adv-card featured" style="text-decoration:none;">
        <div class="adv-num">03</div>
        <div class="adv-card-icon">&#128295;</div>
        <h3>Reparatie aan huis</h3>
        <p>Een gecertificeerde monteur komt bij u thuis. Transparante prijzen, garantie op de reparatie.</p>
        <span class="adv-tag">Ons specialisme</span>
      </a>
      <a href="<?= BASE_URL ?>/taxatie.php" class="adv-card featured" style="text-decoration:none;">
        <div class="adv-num">04</div>
        <div class="adv-card-icon">&#128196;</div>
        <h3>Taxatierapport</h3>
        <p>Schade door stroom, brand of inbraak? Een officieel taxatierapport voor uw verzekeraar.</p>
        <span class="adv-tag">Geaccepteerd door verzekeraars</span>
      </a>
    </div>
  </div>
</div>

<!-- Formulier -->
<div class="form-wrap" id="advies">
  <div class="form-inner">
    <div class="form-left">
      <h2 class="section-title">Vraag gratis<br>advies aan</h2>
      <p class="section-lead">Vul je gegevens in en ontvang zo snel mogelijk een persoonlijk advies — gratis en vrijblijvend.</p>
      <div class="outcome-list">
        <div class="outcome-item"><div class="oi-icon oi-blue">&#128737;</div> Garantie aanspreken bij de winkel of fabrikant</div>
        <div class="outcome-item"><div class="oi-icon oi-yellow">&#129309;</div> Coulanceregeling bespreken met de verkoper</div>
        <div class="outcome-item"><div class="oi-icon oi-orange">&#128295;</div> Reparatie aan huis door gespecialiseerde monteur</div>
        <div class="outcome-item"><div class="oi-icon oi-purple">&#128203;</div> Taxatierapport opstellen voor uw verzekeraar</div>
      </div>
    </div>
    <div>
      <div class="form-card">
        <h3>Beschrijf het probleem</h3>
        <p>Vijf velden en je bent klaar. Je ontvangt binnen één werkdag een reactie.</p>
        <?php if (isset($_GET['verzonden'])): ?>
          <div class="alert alert-success">&#10003; Uw aanvraag is ontvangen! U ontvangt zo snel mogelijk een advies per e-mail.</div>
        <?php elseif (isset($_GET['error'])): ?>
          <div class="alert alert-error">Er is iets misgegaan. Controleer uw gegevens en probeer het opnieuw.</div>
        <?php endif; ?>
        <form action="<?= BASE_URL ?>/api/aanvraag.php" method="POST">
          <input type="hidden" name="csrf_token" value="<?= csrf() ?>" />
          <div class="field-row">
            <div class="field">
              <label>Merk *</label>
              <select name="merk" required>
                <option value="">Selecteer merk</option>
                <?php foreach (['Samsung','Philips','Sony','LG','Panasonic','Hisense','TCL','Anders'] as $m): ?>
                <option value="<?= h($m) ?>"><?= h($m) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="field">
              <label>Modelnummer *</label>
              <input type="text" name="modelnummer" placeholder="Bijv. UE55CU8000" required />
              <p class="field-hint">Staat achter op de tv of via Instellingen &rarr; Ondersteuning</p>
            </div>
          </div>
          <div class="field-row">
            <div class="field">
              <label>Aanschafjaar</label>
              <select name="aanschafjaar">
                <option value="">Onbekend</option>
                <option>2024 &ndash; 2025</option>
                <option>2022 &ndash; 2023</option>
                <option>2020 &ndash; 2021</option>
                <option>2018 &ndash; 2019</option>
                <option>Ouder dan 2018</option>
              </select>
            </div>
            <div class="field">
              <label>Type klacht *</label>
              <select name="klacht_type" required>
                <option value="">Selecteer klacht</option>
                <option>Kapot / gebarsten scherm</option>
                <option>Strepen of lijnen in beeld</option>
                <option>Geen beeld, wel geluid</option>
                <option>Donkere vlekken / backlight-uitval</option>
                <option>TV gaat niet aan</option>
                <option>Bevroren beeld of flikkering</option>
                <option>Anders</option>
              </select>
            </div>
          </div>
          <div class="field">
            <label>Omschrijving</label>
            <textarea name="omschrijving" placeholder="Bijv: zwarte strepen rechts, donkere vlek linksonder, scherm flikkert..."></textarea>
          </div>
          <div class="field">
            <label>E-mailadres *</label>
            <input type="email" name="email" placeholder="naam@email.nl" required />
            <p class="field-hint">Hier sturen we je advies naartoe. Geen spam.</p>
          </div>
          <div class="disclaimer-box">
            &#9888;&#65039; Het advies van Reparatieplatform.nl is indicatief en vrijblijvend.
            Aan dit advies kunnen geen rechten worden ontleend.
          </div>
          <button type="submit" class="submit-btn">Verstuur en ontvang gratis advies &rarr;</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>