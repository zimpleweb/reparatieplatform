<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle       = 'Televisie kapot? Gratis advies op maat | Reparatieplatform.nl';
$pageDescription = 'Televisie kapot of defect? Ontvang gratis persoonlijk advies: garantie, reparatie aan huis of taxatie voor uw verzekeraar.';
$canonicalUrl    = '/';
$merken          = getMerken();

include __DIR__ . '/includes/header.php';
?>

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
        <div class="hero-card-steps">
          <div class="hero-card-step"><span class="step-circle">1</span> Modelnummer &amp; klacht invullen</div>
          <div class="hero-card-step"><span class="step-circle">2</span> Persoonlijk advies ontvangen</div>
          <div class="hero-card-step"><span class="step-circle">3</span> Beste oplossing kiezen</div>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="how-wrap" id="hoe">
  <div class="how-inner">
    <h2 class="section-title">Zo werkt het</h2>
    <p class="section-lead">Geen technische kennis nodig. Beschrijf wat er mis is en wij regelen de rest.</p>
    <div class="steps-grid">
      <div class="step-item">
        <div class="step-n">01</div>
        <div class="step-icon">&#128221;</div>
        <h3>Formulier invullen</h3>
        <p>Geef je merk, modelnummer en een korte omschrijving van het probleem. Klaar in twee minuten.</p>
      </div>
      <div class="step-item">
        <div class="step-n">02</div>
        <div class="step-icon">&#128269;</div>
        <h3>Wij beoordelen je situatie</h3>
        <p>Een specialist bekijkt je gegevens en bepaalt de beste optie: garantie, coulance, reparatie of taxatie.</p>
      </div>
      <div class="step-item">
        <div class="step-n">03</div>
        <div class="step-icon">&#128233;</div>
        <h3>Advies per e-mail</h3>
        <p>Je ontvangt een e-mail met een directe link naar jouw persoonlijk advies en de concrete vervolgstappen.</p>
      </div>
    </div>
  </div>
</div>

<div class="form-wrap" id="advies">
  <div class="form-inner">
    <div class="form-left">
      <h2 class="section-title">Wat is er mis<br>met je televisie?</h2>
      <p class="section-lead">Vul je gegevens in en ontvang zo snel mogelijk een persoonlijk advies &mdash; gratis en vrijblijvend.</p>
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
        <p>Vijf velden en je bent klaar. Je ontvangt binnen &eacute;&eacute;n werkdag een reactie.</p>
        <?php if (isset($_GET['verzonden'])): ?>
          <div class="alert alert-success" style="margin-bottom:1.5rem;">&#10003; Uw aanvraag is ontvangen! U ontvangt zo snel mogelijk een advies per e-mail.</div>
        <?php elseif (isset($_GET['error'])): ?>
          <div class="alert alert-error" style="margin-bottom:1.5rem;">Er is iets misgegaan. Controleer uw gegevens en probeer het opnieuw.</div>
        <?php endif; ?>
        <form action="/api/send-advies.php" method="POST">
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
            Reparatieplatform.nl is een initiatief van TV Reparatie Service Nederland.
          </div>
          <button type="submit" class="submit-btn">Verstuur en ontvang gratis advies &rarr;</button>
        </form>
      </div>
    </div>
  </div>
</div>

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

<div style="background:white; padding:5rem 0;">
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