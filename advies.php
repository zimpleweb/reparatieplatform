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

<!-- Hoe het werkt -->
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
        <div class="outcome-item"><div class="oi-icon" style="background:#d1fae5;color:#065f46">&#9854;</div> Recycling: verantwoorde verwerking van uw televisie</div>
      </div>
      <!-- Routing-indicator: wordt live gevuld door JS -->
      <div id="routing-indicator" style="display:none;" class="routing-indicator">
        <div class="routing-label">Mogelijke route op basis van uw antwoorden:</div>
        <div id="routing-badge" class="routing-badge"></div>
        <div id="routing-toelichting" class="routing-toelichting"></div>
      </div>
    </div>

    <!-- Rechterkant: het formulier -->
    <div class="form-right">
      <div class="form-card">

        <?php if (isset($_GET['verzonden'])): ?>
          <div class="alert alert-success">&#10003; Uw aanvraag is ontvangen! U ontvangt zo snel mogelijk een advies per e-mail.</div>
        <?php elseif (isset($_GET['error'])): ?>
          <div class="alert alert-error">Er is iets misgegaan. Controleer uw gegevens en probeer het opnieuw.</div>
        <?php endif; ?>

        <!-- Voortgangsbalk -->
        <div class="stap-progress">
          <div class="stap-dot actief" data-stap="1"></div>
          <div class="stap-lijn"></div>
          <div class="stap-dot" data-stap="2"></div>
          <div class="stap-lijn"></div>
          <div class="stap-dot" data-stap="3"></div>
          <div class="stap-lijn"></div>
          <div class="stap-dot" data-stap="4"></div>
        </div>

        <form action="<?= BASE_URL ?>/api/send-advies.php" method="POST" id="advies-form">
          <input type="hidden" name="csrf_token" value="<?= csrf() ?>" />
          <input type="hidden" name="geadviseerde_route" id="geadviseerde_route" value="" />
          <input type="hidden" name="coulance_kans" id="coulance_kans" value="" />
          <input type="hidden" name="model_repareerbaar" id="model_repareerbaar" value="" />

          <!-- ============================================================
               STAP 1 — Schade of storing?
               ============================================================ -->
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
            <div class="stap-nav">
              <button type="button" class="stap-volgende" onclick="naarStap(2)">Volgende &rarr;</button>
            </div>
          </div>

          <!-- ============================================================
               STAP 2 — TV-gegevens + aankoop
               ============================================================ -->
          <div class="form-stap" id="stap-2" style="display:none;">
            <div class="stap-header">
              <span class="stap-nr">Stap 2 van 4</span>
              <h3>Over uw televisie</h3>
              <p>Merk, model en aankoopinformatie bepalen de route.</p>
            </div>

            <!-- Merk + modelnummer -->
            <div class="field-row">
              <div class="field">
                <label>Merk *</label>
                <select name="merk" id="merk" required onchange="resetRepareerbaar()">
                  <option value="">Selecteer merk</option>
                  <?php foreach (['Samsung','Philips','Sony','LG','Panasonic','Hisense','TCL','Anders'] as $m): ?>
                  <option value="<?= h($m) ?>"><?= h($m) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="field">
                <label>Modelnummer *</label>
                <input type="text" name="modelnummer" id="modelnummer" placeholder="Bijv. UE55CU8000" required
                       oninput="resetRepareerbaar()" />
                <p class="field-hint">Staat achter op de tv of via Instellingen &rarr; Ondersteuning</p>
              </div>
            </div>

            <!-- Repareerbaar-check feedback -->
            <div id="repareerbaar-feedback" style="display:none;"></div>

            <!-- Aanschafjaar + waarde -->
            <div class="field-row">
              <div class="field">
                <label>Aanschafjaar *</label>
                <select name="aanschafjaar" id="aanschafjaar" required onchange="berekenRoute()">
                  <option value="">Selecteer jaar</option>
                  <option value="2026">2026</option>
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
                  <option value="500-1000">&euro;500 &ndash; &euro;1.000</option>
                  <option value="1000-2000">&euro;1.000 &ndash; &euro;2.000</option>
                  <option value=">2000">Meer dan &euro;2.000</option>
                </select>
              </div>
            </div>

            <!-- Verkoper -->
            <div class="field">
              <label>Waar gekocht?</label>
              <select name="aankoop_locatie" id="aankoop_locatie" onchange="berekenRoute()">
                <option value="nl">In Nederland</option>
                <option value="buitenland">Buiten Nederland (let op: andere garantieregels)</option>
                <option value="onbekend">Weet ik niet meer</option>
              </select>
            </div>

            <!-- Failliet: direct bij de verkoopvraag -->
            <div class="field field-failliet">
              <label class="checkbox-label">
                <input type="checkbox" name="verkoper_failliet" id="verkoper_failliet" onchange="berekenRoute()" />
                <span>De winkel waar ik het heb gekocht is failliet gegaan</span>
              </label>
              <p class="field-hint">Dit heeft invloed op garantieaanspraken (faillissement is een uitzonderingspositie).</p>
            </div>

            <!-- Live route-feedback -->
            <div id="garantie-feedback" class="garantie-feedback" style="display:none;"></div>

            <div class="stap-nav">
              <button type="button" class="stap-terug" onclick="naarStap(1)">&larr; Terug</button>
              <button type="button" class="stap-volgende" onclick="naarStapMetCheck(3)">Volgende &rarr;</button>
            </div>
          </div>

          <!-- ============================================================
               STAP 3 — Het defect
               ============================================================ -->
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

          <!-- ============================================================
               STAP 4 — Contactgegevens + samenvatting
               ============================================================ -->
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
    </div><!-- /.form-right -->
  </div>
</div>

<script>
// ── Constanten ───────────────────────────────────────────────────
const HUIDIG_JAAR = <?= date('Y') ?>;

// Repareerbaar-status ophalen via API
let _repCheck = { geladen: false, gevonden: false, repareerbaar: false, status: '' };
let _repTimer  = null;

function resetRepareerbaar() {
  _repCheck = { geladen: false, gevonden: false, repareerbaar: false, status: '' };
  const fb = document.getElementById('repareerbaar-feedback');
  if (fb) fb.style.display = 'none';
  document.getElementById('model_repareerbaar').value = '';
  clearTimeout(_repTimer);
  _repTimer = setTimeout(checkRepareerbaar, 600);
}

function checkRepareerbaar() {
  const merk  = document.getElementById('merk')?.value || '';
  const model = document.getElementById('modelnummer')?.value?.trim() || '';
  if (!merk || merk === 'Anders' || model.length < 3) return;

  fetch(`<?= BASE_URL ?>/api/check-repareerbaar.php?merk=${encodeURIComponent(merk)}&modelnummer=${encodeURIComponent(model)}`)
    .then(r => r.json())
    .then(data => {
      _repCheck = { geladen: true, gevonden: data.gevonden, repareerbaar: data.repareerbaar, status: data.status || '' };
      document.getElementById('model_repareerbaar').value = data.repareerbaar ? 'ja' : 'nee';
      toonRepareerbaar();
      berekenRoute();
    })
    .catch(() => {});
}

function toonRepareerbaar() {
  const fb = document.getElementById('repareerbaar-feedback');
  if (!fb || !_repCheck.geladen) return;
  if (!_repCheck.gevonden) {
    fb.style.display = 'none';
    return;
  }
  if (_repCheck.repareerbaar) {
    fb.className  = 'rep-feedback rep-ok';
    fb.innerHTML  = '<span class="rep-ico">&#9989;</span> Dit model staat in onze database als <strong>repareerbaar</strong>. Reparatie aan huis behoort tot de opties.';
  } else {
    fb.className  = 'rep-feedback rep-nee';
    fb.innerHTML  = '<span class="rep-ico">&#9851;</span> Dit model staat in onze database als <strong>niet-repareerbaar</strong>. Wij begeleiden u richting verantwoorde recycling.';
  }
  fb.style.display = 'block';
}

// ── Stap-navigatie ───────────────────────────────────────────────
function naarStap(nr) {
  const huidig   = document.querySelector('.form-stap:not([style*="display:none"])');
  const required = huidig ? huidig.querySelectorAll('[required]') : [];
  for (const el of required) {
    if (!el.value) { el.focus(); el.reportValidity(); return; }
  }
  _toonStap(nr);
}

// Stap 2 → 3: ook repareerbaar-API afwachten als nodig
function naarStapMetCheck(nr) {
  const huidig   = document.querySelector('.form-stap:not([style*="display:none"])');
  const required = huidig ? huidig.querySelectorAll('[required]') : [];
  for (const el of required) {
    if (!el.value) { el.focus(); el.reportValidity(); return; }
  }
  // Trigger check als nog niet gedaan
  if (!_repCheck.geladen) checkRepareerbaar();
  _toonStap(nr);
}

function _toonStap(nr) {
  document.querySelectorAll('.form-stap').forEach(s => s.style.display = 'none');
  const doel = document.getElementById('stap-' + nr);
  if (doel) { doel.style.display = 'block'; doel.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
  // Voortgangsbollen
  document.querySelectorAll('.stap-dot').forEach((d, i) => {
    d.classList.toggle('actief',   i < nr);
    d.classList.toggle('huidig',   i === nr - 1);
  });
  berekenRoute();
  if (nr === 4) vulSamenvatting();
}

// ── Keuze-kaarten (stap 1) ───────────────────────────────────────
document.querySelectorAll('.route-keuze input[type=radio]').forEach(r => {
  r.addEventListener('change', function() {
    document.querySelectorAll('.route-keuze').forEach(k => k.classList.remove('geselecteerd'));
    this.closest('.route-keuze').classList.add('geselecteerd');
    berekenRoute();
  });
});

// ── Routing engine ───────────────────────────────────────────────
function berekenRoute() {
  const situatie       = document.querySelector('[name=situatie]:checked')?.value || '';
  const aanschafjaar   = parseInt(document.getElementById('aanschafjaar')?.value) || null;
  const aanschafwaarde = document.getElementById('aanschafwaarde')?.value || '';
  const locatie        = document.getElementById('aankoop_locatie')?.value || 'nl';
  const failliet       = document.getElementById('verkoper_failliet')?.checked || false;
  const klacht         = document.getElementById('klacht_type')?.value || '';

  // Repareerbaar vanuit DB-check; als niet gevonden → aanname: onbekend (geen blokkade)
  const repKnown       = _repCheck.geladen && _repCheck.gevonden;
  const kanRepareren   = !repKnown || _repCheck.repareerbaar;

  let route = '', badge = '', toelichting = '', coulanceKans = 0;

  if (situatie === 'schade') {
    route       = 'taxatie';
    badge       = '&#128196; Taxatierapport';
    toelichting = 'Omdat er sprake is van externe schade (stroom, brand, inbraak of val), is een taxatierapport de juiste route voor uw verzekeraar.';
  } else if (situatie === 'storing') {
    const leeftijd        = aanschafjaar ? (HUIDIG_JAAR - aanschafjaar) : null;
    const geenGarantie    = klacht === 'gebarsten_scherm';

    if (leeftijd !== null && leeftijd <= 2 && !geenGarantie && locatie === 'nl' && !failliet) {
      route       = 'garantie';
      badge       = '&#9989; Garantie';
      toelichting = 'Op basis van het aanschafjaar valt uw televisie waarschijnlijk nog onder de wettelijke garantietermijn. Wij begeleiden u naar de verkoper of fabrikant.';
    } else if (leeftijd !== null && leeftijd <= 2 && (locatie === 'buitenland' || failliet)) {
      if (kanRepareren) {
        route       = 'reparatie';
        badge       = '&#128295; Reparatie (bijz. situatie)';
        toelichting = failliet
          ? 'Uw verkoper is failliet gegaan. Directe garantie is niet meer mogelijk. Reparatie aan huis is de meest praktische optie.'
          : 'Televisies buiten Nederland gekocht vallen doorgaans buiten de Nederlandse garantieregels. Reparatie aan huis is de meest praktische optie.';
      } else {
        route       = 'recycling';
        badge       = '&#9851; Recycling';
        toelichting = 'Dit model is niet repareerbaar. Wij begeleiden u richting verantwoorde recycling of inruilmogelijkheden.';
      }
    } else if (leeftijd !== null && leeftijd > 2 && leeftijd <= 5 && !geenGarantie) {
      let kans = 60;
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
        + 'Wij schatten de kans op een vergoeding op <strong>' + kans + '%</strong>.';
    } else if (leeftijd !== null && leeftijd > 5 && leeftijd <= 10) {
      if (kanRepareren) {
        route       = 'reparatie';
        badge       = '&#128295; Reparatie aan huis';
        toelichting = 'Garantie en coulance zijn niet meer van toepassing. Reparatie aan huis is de meest kosteneffiënte oplossing als het defect technisch herstelbaar is.';
      } else {
        route       = 'recycling';
        badge       = '&#9851; Recycling';
        toelichting = 'Dit model staat als niet-repareerbaar in onze database. Wij begeleiden u richting verantwoorde recycling of doorverwijzing naar een inruilprogramma.';
      }
    } else if (leeftijd !== null && leeftijd > 10) {
      if (kanRepareren) {
        route       = 'secondlife';
        badge       = '&#9854; Second life advisering';
        toelichting = 'Een televisie ouder dan 10 jaar heeft een hogere kans dat reparatiekosten de waarde overtreffen. Wij adviseren eerlijk over doorverkoop of verantwoorde recycling.';
      } else {
        route       = 'recycling';
        badge       = '&#9851; Recycling';
        toelichting = 'Dit model is niet repareerbaar en is ouder dan 10 jaar. Verantwoorde recycling is de meest zinvolle route.';
      }
    } else if (klacht === 'gebarsten_scherm') {
      if (kanRepareren) {
        route       = 'reparatie';
        badge       = '&#128295; Schermvervanging';
        toelichting = 'Een gebarsten scherm valt nooit onder garantie. Wij kijken of schermvervanging economisch zinvol is.';
      } else {
        route       = 'recycling';
        badge       = '&#9851; Recycling';
        toelichting = 'Dit model is niet repareerbaar. Verantwoorde recycling of doorverwijzing naar een inruilprogramma is de beste route.';
      }
    }
  }

  document.getElementById('geadviseerde_route').value = route;
  document.getElementById('coulance_kans').value      = coulanceKans;

  // Update routing-indicator (linkerkant)
  const ind     = document.getElementById('routing-indicator');
  const badgeEl = document.getElementById('routing-badge');
  const toel    = document.getElementById('routing-toelichting');
  if (route && ind) {
    ind.style.display = 'block';
    badgeEl.innerHTML = badge;
    badgeEl.className = 'routing-badge route-' + route.replace(/[^a-z]/g, '');
    toel.innerHTML    = toelichting;
  } else if (ind) {
    ind.style.display = 'none';
  }

  // Update feedback-blok in stap 2
  const fb = document.getElementById('garantie-feedback');
  if (fb && route) {
    fb.style.display = 'block';
    fb.innerHTML  = '<strong>Voorlopige route:</strong> ' + badge + '<br><small>' + toelichting + '</small>';
    fb.className  = 'garantie-feedback feedback-' + route.replace(/[^a-z]/g, '');
  } else if (fb) {
    fb.style.display = 'none';
  }
}

// ── Stap 4 samenvatting ──────────────────────────────────────────
function vulSamenvatting() {
  const merk      = document.getElementById('merk')?.value || '—';
  const model     = document.getElementById('modelnummer')?.value || '—';
  const jaar      = document.getElementById('aanschafjaar')?.value || '—';
  const klachtEl  = document.getElementById('klacht_type');
  const klachtTxt = klachtEl?.options[klachtEl.selectedIndex]?.text || '—';
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
