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

<!-- Stappen -->
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

<!-- Formulier: slim meerstaps -->
<div class="form-wrap" id="advies">
  <div class="form-inner">

    <!-- Linkerkant uitleg -->
    <div class="form-left">
      <h2 class="section-title">Vraag gratis<br>advies aan</h2>
      <p class="section-lead">Wij filteren automatisch de beste route — garantie, coulance, reparatie of taxatie.</p>
      <div class="outcome-list">
        <div class="outcome-item"><div class="oi-icon oi-blue">&#128737;</div> Garantie aanspreken bij de winkel of fabrikant</div>
        <div class="outcome-item"><div class="oi-icon oi-yellow">&#129309;</div> Coulanceregeling bespreken met de verkoper</div>
        <div class="outcome-item"><div class="oi-icon oi-orange">&#128295;</div> Reparatie aan huis door gespecialiseerde monteur</div>
        <div class="outcome-item"><div class="oi-icon oi-purple">&#128203;</div> Taxatierapport opstellen voor uw verzekeraar</div>
        <div class="outcome-item"><div class="oi-icon" style="background:#d1fae5;color:#065f46">&#9854;</div> Second life: doorverkoop of verantwoorde recycling</div>
      </div>
      <!-- Routing-indicator: wordt live gevuld door JS -->
      <div id="routing-indicator" style="display:none;" class="routing-indicator">
        <div class="routing-label">Mogelijke route op basis van uw antwoorden:</div>
        <div id="routing-badge" class="routing-badge"></div>
        <div id="routing-toelichting" class="routing-toelichting"></div>
      </div>
    </div>

    <!-- Rechterkant: het formulier -->
    <div>
      <div class="form-card">

        <?php if (isset($_GET['verzonden'])): ?>
          <div class="alert alert-success">&#10003; Uw aanvraag is ontvangen! U ontvangt zo snel mogelijk een advies per e-mail.</div>
        <?php elseif (isset($_GET['error'])): ?>
          <div class="alert alert-error">Er is iets misgegaan. Controleer uw gegevens en probeer het opnieuw.</div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>/api/send-advies.php" method="POST" id="advies-form">
          <input type="hidden" name="csrf_token" value="<?= csrf() ?>" />
          <input type="hidden" name="geadviseerde_route" id="geadviseerde_route" value="" />
          <input type="hidden" name="coulance_kans" id="coulance_kans" value="" />

          <!-- STAP 1: Schade of storing? -->
          <div class="form-stap" id="stap-1">
            <div class="stap-header">
              <span class="stap-nr">Stap 1 van 4</span>
              <h3>Wat is er aan de hand?</h3>
              <p>Dit bepaalt direct welke route het meest geschikt is.</p>
            </div>
            <div class="route-keuze-grid">
              <label class="route-keuze" data-type="storing">
                <input type="radio" name="situatie" value="storing" required />
                <div class="route-keuze-inner">
                  <div class="route-keuze-icon">&#128295;</div>
                  <strong>Technisch defect / storing</strong>
                  <span>TV doet het niet meer, beeld of geluidsproblemen, software-issues</span>
                </div>
              </label>
              <label class="route-keuze" data-type="schade">
                <input type="radio" name="situatie" value="schade" />
                <div class="route-keuze-inner">
                  <div class="route-keuze-icon">&#9889;</div>
                  <strong>Schade door externe oorzaak</strong>
                  <span>Stormschade, stroomstoot, brand, inbraak, valschade</span>
                </div>
              </label>
            </div>
            <button type="button" class="stap-volgende" onclick="naarStap(2)">Volgende &rarr;</button>
          </div>

          <!-- STAP 2: Aanschafinfo + garantiecheck -->
          <div class="form-stap" id="stap-2" style="display:none;">
            <div class="stap-header">
              <span class="stap-nr">Stap 2 van 4</span>
              <h3>Wanneer en waar gekocht?</h3>
              <p>Dit bepaalt of garantie of coulance van toepassing kan zijn.</p>
            </div>
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
                <label>Aanschafjaar *</label>
                <select name="aanschafjaar" id="aanschafjaar" required onchange="berekenRoute()">
                  <option value="">Selecteer jaar</option>
                  <option value="2025">2025</option>
                  <option value="2024">2024</option>
                  <option value="2023">2023</option>
                  <option value="2022">2022</option>
                  <option value="2021">2021</option>
                  <option value="2020">2020</option>
                  <option value="2019">2019</option>
                  <option value="2018">2018</option>
                  <option value="ouder">Ouder dan 2018</option>
                </select>
              </div>
              <div class="field">
                <label>Aanschafwaarde (indicatief)</label>
                <select name="aanschafwaarde" id="aanschafwaarde" onchange="berekenRoute()">
                  <option value="">Onbekend</option>
                  <option value="<500">Minder dan &euro;500</option>
                  <option value="500-1000">&euro;500 – &euro;1.000</option>
                  <option value="1000-2000">&euro;1.000 – &euro;2.000</option>
                  <option value=">2000">Meer dan &euro;2.000</option>
                </select>
              </div>
            </div>
            <div class="field">
              <label>Waar gekocht?</label>
              <select name="aankoop_locatie" id="aankoop_locatie" onchange="berekenRoute()">
                <option value="nl">In Nederland</option>
                <option value="buitenland">Buiten Nederland (let op: andere garantieregels)</option>
                <option value="onbekend">Weet ik niet meer</option>
              </select>
            </div>
            <div class="field">
              <label>
                <input type="checkbox" name="verkoper_failliet" id="verkoper_failliet" onchange="berekenRoute()" />
                De winkel waar ik het heb gekocht is failliet gegaan
              </label>
              <p class="field-hint">Dit heeft invloed op garantieaanspraken (faillissement is een uitzonderingspositie).</p>
            </div>
            <!-- Live feedback blok -->
            <div id="garantie-feedback" class="garantie-feedback" style="display:none;"></div>
            <div class="stap-nav">
              <button type="button" class="stap-terug" onclick="naarStap(1)">&larr; Terug</button>
              <button type="button" class="stap-volgende" onclick="naarStap(3)">Volgende &rarr;</button>
            </div>
          </div>

          <!-- STAP 3: Het defect -->
          <div class="form-stap" id="stap-3" style="display:none;">
            <div class="stap-header">
              <span class="stap-nr">Stap 3 van 4</span>
              <h3>Beschrijf het defect</h3>
              <p>Hoe specifieker, hoe beter het advies.</p>
            </div>
            <div class="field">
              <label>Type klacht *</label>
              <select name="klacht_type" id="klacht_type" required onchange="berekenRoute()">
                <option value="">Selecteer type probleem</option>
                <optgroup label="Beeldproblemen">
                  <option value="gebarsten_scherm">Kapot / gebarsten scherm</option>
                  <option value="strepen">Strepen of lijnen in beeld</option>
                  <option value="geen_beeld">Geen beeld, wel geluid</option>
                  <option value="backlight">Donkere vlekken / backlight-uitval</option>
                  <option value="flikkering">Bevroren beeld of flikkering</option>
                  <option value="kleur">Kleurproblemen of erg verkleurde pixels</option>
                </optgroup>
                <optgroup label="Stroom &amp; hardware">
                  <option value="niet_aan">TV gaat niet aan</option>
                  <option value="stroomstoot">Schade na stroomstoot / blikseminslag</option>
                  <option value="oververhitting">Oververhitting / stopt na korte tijd</option>
                </optgroup>
                <optgroup label="Software &amp; bediening">
                  <option value="software">Software / Smart TV werkt niet</option>
                  <option value="afstandsbediening">Afstandsbediening / bediening reageert niet</option>
                  <option value="geluid">Geen of slecht geluid, beeld werkt wel</option>
                </optgroup>
                <optgroup label="Overig">
                  <option value="anders">Anders / niet in de lijst</option>
                </optgroup>
              </select>
            </div>
            <div class="field">
              <label>Omschrijving</label>
              <textarea name="omschrijving" rows="4" placeholder="Bijv: zwarte strepen rechts, donkere vlek linksonder, scherm flikkert na 10 minuten..."></textarea>
            </div>
            <div class="stap-nav">
              <button type="button" class="stap-terug" onclick="naarStap(2)">&larr; Terug</button>
              <button type="button" class="stap-volgende" onclick="naarStap(4)">Volgende &rarr;</button>
            </div>
          </div>

          <!-- STAP 4: Contactgegevens + samenvatting -->
          <div class="form-stap" id="stap-4" style="display:none;">
            <div class="stap-header">
              <span class="stap-nr">Stap 4 van 4</span>
              <h3>Uw contactgegevens</h3>
              <p>Hier sturen wij uw persoonlijk advies naartoe.</p>
            </div>
            <div class="field">
              <label>E-mailadres *</label>
              <input type="email" name="email" placeholder="naam@email.nl" required />
              <p class="field-hint">Geen spam. Alleen uw advies.</p>
            </div>
            <!-- Routeoverzicht -->
            <div id="route-samenvatting" class="route-samenvatting"></div>
            <div class="disclaimer-box">
              &#9888;&#65039; Het advies van Reparatieplatform.nl is indicatief en vrijblijvend.
              Aan dit advies kunnen geen rechten worden ontleend. Vergissingen worden actief
              bewaakt en gecorrigeerd door een menselijke specialist.
            </div>
            <div class="stap-nav">
              <button type="button" class="stap-terug" onclick="naarStap(3)">&larr; Terug</button>
              <button type="submit" class="submit-btn">Verstuur en ontvang gratis advies &rarr;</button>
            </div>
          </div>

        </form>
      </div>
    </div>
  </div>
</div>

<script>
// ── Stap-navigatie ──────────────────────────────────────────────
function naarStap(nr) {
  // Valideer huidige stap vóór verdergaan
  const huidig = document.querySelector('.form-stap:not([style*="display:none"])');
  const required = huidig ? huidig.querySelectorAll('[required]') : [];
  for (const el of required) {
    if (!el.value) {
      el.focus();
      el.reportValidity();
      return;
    }
  }
  document.querySelectorAll('.form-stap').forEach(s => s.style.display = 'none');
  const doel = document.getElementById('stap-' + nr);
  if (doel) doel.style.display = 'block';
  // Voortgangsbalk updaten
  document.querySelectorAll('.stap-dot').forEach((d, i) => {
    d.classList.toggle('actief', i < nr);
  });
  berekenRoute();
  if (nr === 4) vulSamenvatting();
}

// ── Keuze-kaarten activeren ──────────────────────────────────────
document.querySelectorAll('.route-keuze input[type=radio]').forEach(r => {
  r.addEventListener('change', function() {
    document.querySelectorAll('.route-keuze').forEach(k => k.classList.remove('geselecteerd'));
    this.closest('.route-keuze').classList.add('geselecteerd');
    berekenRoute();
  });
});

// ── Routing engine ───────────────────────────────────────────────
const HUIDIG_JAAR = <?= date('Y') ?>;

function berekenRoute() {
  const situatie        = document.querySelector('[name=situatie]:checked')?.value || '';
  const aanschafjaarEl  = document.getElementById('aanschafjaar');
  const aanschafwaardeEl= document.getElementById('aanschafwaarde');
  const locatieEl       = document.getElementById('aankoop_locatie');
  const faillietEl      = document.getElementById('verkoper_failliet');
  const klachtEl        = document.getElementById('klacht_type');

  const aanschafjaar    = aanschafjaarEl  ? parseInt(aanschafjaarEl.value)  : null;
  const aanschafwaarde  = aanschafwaardeEl ? aanschafwaardeEl.value          : '';
  const locatie         = locatieEl       ? locatieEl.value                  : 'nl';
  const failliet        = faillietEl      ? faillietEl.checked               : false;
  const klacht          = klachtEl        ? klachtEl.value                   : '';

  let route        = '';
  let badge        = '';
  let toelichting  = '';
  let coulanceKans = 0;

  // --- Schade-route: altijd taxatie ---
  if (situatie === 'schade') {
    route       = 'taxatie';
    badge       = '&#128196; Taxatierapport';
    toelichting = 'Omdat er sprake is van externe schade (stroom, brand, inbraak of val), is een taxatierapport de juiste route voor uw verzekeraar.';
  }

  // --- Storingsroute: bepaal garantie / coulance / reparatie / second life ---
  else if (situatie === 'storing') {
    const leeftijd = aanschafjaar ? (HUIDIG_JAAR - aanschafjaar) : null;

    // Schermbreuk = nooit garantie
    const geenGarantieKlachten = ['gebarsten_scherm'];
    const isGeenGarantie = geenGarantieKlachten.includes(klacht);

    // Garantiecheck: < 2 jaar, in NL gekocht, verkoper niet failliet
    if (leeftijd !== null && leeftijd <= 2 && !isGeenGarantie && locatie === 'nl' && !failliet) {
      route       = 'garantie';
      badge       = '&#9989; Garantie';
      toelichting = 'Op basis van het aanschafjaar valt uw televisie waarschijnlijk nog onder de wettelijke garantietermijn. Wij begeleiden u naar de verkoper of fabrikant.';
    }
    // Garantie maar buitenland of failliet: bijzondere situatie
    else if (leeftijd !== null && leeftijd <= 2 && (locatie === 'buitenland' || failliet)) {
      route       = 'reparatie';
      badge       = '&#128295; Reparatie (bijz. situatie)';
      toelichting = (failliet)
        ? 'Uw verkoper is failliet gegaan. Dit is een uitzonderingspositie waardoor directe garantie bij de verkoper niet meer mogelijk is. Reparatie aan huis is de meest praktische optie.'
        : 'Televisies buiten Nederland aangeschaft vallen doorgaans buiten de standaard Nederlandse garantieregels. Reparatie aan huis is de meest praktische optie.';
    }
    // Coulance: 2-5 jaar, kijk ook naar aanschafwaarde
    else if (leeftijd !== null && leeftijd > 2 && leeftijd <= 5 && !isGeenGarantie) {
      // Kansberekening coulance (0-100)
      let kans = 60; // basiskans als de tv 2-5 jaar oud is
      if (leeftijd <= 3) kans += 20;
      if (aanschafwaarde === '>2000' || aanschafwaarde === '1000-2000') kans += 15;
      if (aanschafwaarde === '<500') kans -= 20;
      if (locatie === 'buitenland') kans -= 30;
      if (failliet) kans -= 40;
      kans = Math.max(5, Math.min(95, kans));
      coulanceKans = kans;

      route       = 'coulance';
      badge       = '&#129309; Coulanceregeling (' + kans + '% kans)';
      toelichting = 'Uw televisie is ' + leeftijd + ' jaar oud. Garantie is verlopen, maar veel fabrikanten bieden een coulanceregeling aan. '
        + 'Op basis van aanschafjaar en waarde schatten wij de kans op een vergoeding op <strong>' + kans + '%</strong>. '
        + 'Wij begeleiden u in dit traject.';
    }
    // Reparatie: 5-10 jaar
    else if (leeftijd !== null && leeftijd > 5 && leeftijd <= 10) {
      route       = 'reparatie';
      badge       = '&#128295; Reparatie aan huis';
      toelichting = 'Garantie en coulance zijn niet meer van toepassing. Reparatie aan huis is de meest kostenefficiënte oplossing als het defect technisch herstelbaar is.';
    }
    // Second life: ouder dan 10 jaar of gebarsten scherm oud toestel
    else if (leeftijd !== null && leeftijd > 10) {
      route       = 'second-life';
      badge       = '&#9854; Second life advisering';
      toelichting = 'Een televisie ouder dan 10 jaar heeft een hogere kans dat reparatiekosten de waarde overtreffen. Wij adviseren u eerlijk over doorverkoop, donatie of verantwoorde recycling als alternatief voor reparatie.';
    }
    // Klacht gebarsten scherm: altijd reparatie of second life
    else if (klacht === 'gebarsten_scherm') {
      route       = 'reparatie';
      badge       = '&#128295; Reparatie / Second life';
      toelichting = 'Een gebarsten scherm valt nooit onder garantie (dit is gebruikersschade). Wij kijken of schermsvervanging economisch zinvol is of dat second life de betere keuze is.';
    }
  }

  // Update hidden velden
  document.getElementById('geadviseerde_route').value = route;
  document.getElementById('coulance_kans').value = coulanceKans;

  // Update indicator links (stap 1-3)
  const ind = document.getElementById('routing-indicator');
  const badgeEl = document.getElementById('routing-badge');
  const toel = document.getElementById('routing-toelichting');
  if (route && ind) {
    ind.style.display = 'block';
    badgeEl.innerHTML = badge;
    badgeEl.className = 'routing-badge route-' + route.replace(/[^a-z]/g,'');
    toel.innerHTML = toelichting;
  } else if (ind) {
    ind.style.display = 'none';
  }

  // Update garantie-feedback blok in stap 2
  const fb = document.getElementById('garantie-feedback');
  if (fb && route) {
    fb.style.display = 'block';
    fb.innerHTML = '<strong>Voorlopige route:</strong> ' + badge + '<br><small>' + toelichting + '</small>';
    fb.className = 'garantie-feedback feedback-' + route.replace(/[^a-z]/g,'');
  } else if (fb) {
    fb.style.display = 'none';
  }
}

// ── Stap 4 samenvatting ──────────────────────────────────────────
function vulSamenvatting() {
  const merk      = document.querySelector('[name=merk]')?.value || '—';
  const model     = document.querySelector('[name=modelnummer]')?.value || '—';
  const jaar      = document.getElementById('aanschafjaar')?.value || '—';
  const klacht    = document.getElementById('klacht_type');
  const klachtTxt = klacht?.options[klacht.selectedIndex]?.text || '—';
  const route     = document.getElementById('geadviseerde_route')?.value || '';
  const badge     = document.getElementById('routing-badge')?.innerHTML || '';

  const el = document.getElementById('route-samenvatting');
  if (!el) return;
  el.innerHTML = `
    <div class="samenvatting-titel">&#128203; Uw aanvraag in één oogopslag</div>
    <div class="samenvatting-rij"><span>Televisie</span><strong>${merk} ${model}</strong></div>
    <div class="samenvatting-rij"><span>Aanschafjaar</span><strong>${jaar}</strong></div>
    <div class="samenvatting-rij"><span>Klacht</span><strong>${klachtTxt}</strong></div>
    <div class="samenvatting-rij"><span>Geadviseerde route</span><strong>${badge}</strong></div>
  `;
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
