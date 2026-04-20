<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/advies_regels.php';

$pageTitle       = 'Gratis advies aanvragen – Televisie kapot? | Reparatieplatform.nl';
$pageDescription = 'Vraag gratis persoonlijk advies aan over garantie, coulanceregeling, reparatie of taxatie van uw defecte televisie.';
$canonicalUrl    = '/advies.php';

$r   = getAdviesRegels();
$rJs = json_encode($r, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE);

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

<!-- Zo werkt het – Stap-wizard component -->
<div style="background: var(--surface); border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); padding: 5rem 0;">
  <div class="section" style="padding-top:0;padding-bottom:0;">
    <div style="text-align:center;margin-bottom:3rem;">
      <div style="display:inline-flex;align-items:center;gap:.5rem;background:var(--accent-light);border:1px solid #b2ddd4;border-radius:999px;padding:.3rem 1rem;font-size:.8rem;font-weight:700;color:var(--accent);margin-bottom:1rem;">
        &#128274; Gratis &amp; vrijblijvend
      </div>
      <h2 class="section-title" style="margin-bottom:.75rem;">Zo werkt het</h2>
      <p style="font-size:1rem;color:var(--muted);max-width:48ch;margin:0 auto;line-height:1.75;">
        Geen technische kennis nodig. Beschrijf het probleem en wij regelen de rest.
      </p>
    </div>
    <?php include __DIR__ . '/includes/stap-wizard.php'; ?>
    <div style="text-align:center;margin-top:3rem;">
      <a href="#advies" class="btn-primary">
        Gratis advies aanvragen
        <span class="btn-primary-arrow">&darr;</span>
      </a>
    </div>
  </div>
</div>

<!-- Klantenomgeving banner -->
<div class="status-check-wrap">
  <div class="section" style="max-width:680px;margin:0 auto;padding:0 1.5rem;">
    <div class="status-check-box" style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
      <div>
        <h3 style="margin-bottom:.3rem;">&#128274; Al een aanvraag ingediend?</h3>
        <p class="lead" style="margin:0;">Bekijk de status, upload documenten en volg uw traject via uw persoonlijke klantenomgeving.</p>
      </div>
      <a href="<?= BASE_URL ?>/mijn-aanvraag.php" class="btn-check" style="white-space:nowrap;text-decoration:none;">
        Mijn aanvraag bekijken &rarr;
      </a>
    </div>
  </div>
</div>

<!-- Formulier -->
<div class="form-wrap" id="advies">
  <div class="form-inner">

    <!-- Links -->
    <div class="form-left">
      <h2 class="section-title">Vraag gratis<br>advies aan</h2>
      <p class="section-lead">Wij filteren automatisch de beste route — garantie, coulance, reparatie of taxatie.</p>
      <div class="outcome-list">
        <div class="outcome-item"><div class="oi-icon oi-blue">&#128737;</div> Garantie aanspreken bij de winkel of fabrikant</div>
        <div class="outcome-item"><div class="oi-icon oi-yellow">&#129309;</div> Coulanceregeling bespreken met de verkoper</div>
        <div class="outcome-item"><div class="oi-icon oi-orange">&#128295;</div> Reparatie aan huis door gespecialiseerde monteur</div>
        <div class="outcome-item"><div class="oi-icon oi-purple">&#128203;</div> Taxatierapport opstellen voor uw verzekeraar</div>
        <div class="outcome-item"><div class="oi-icon" style="background:#d1fae5;color:#065f46">&#9851;</div> Recycling: verantwoorde verwerking van uw televisie</div>
      </div>
      <div id="routing-indicator" style="display:none;" class="routing-indicator">
        <div class="routing-label">Mogelijke route op basis van uw antwoorden:</div>
        <div id="routing-badge" class="routing-badge"></div>
        <div id="routing-toelichting" class="routing-toelichting"></div>
      </div>
    </div>

    <!-- Rechts: formulier -->
    <div class="form-right">
      <div class="form-card">

        <?php if (isset($_GET['error'])): ?>
          <div class="alert alert-error">Er is iets misgegaan. Controleer uw gegevens en probeer het opnieuw.</div>
        <?php endif; ?>

        <!-- Voortgangsbalk -->
        <div class="stap-progress" role="progressbar" aria-label="Stappenplan">
          <div class="stap-step actief huidig" data-stap="1">
            <div class="stap-dot">
              <span class="stap-dot-nr">1</span>
              <svg class="stap-dot-check" viewBox="0 0 14 14" fill="none"><polyline points="2,7 6,11 12,3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            <div class="stap-step-label">Situatie</div>
          </div>
          <div class="stap-lijn actief"></div>
          <div class="stap-step" data-stap="2">
            <div class="stap-dot">
              <span class="stap-dot-nr">2</span>
              <svg class="stap-dot-check" viewBox="0 0 14 14" fill="none"><polyline points="2,7 6,11 12,3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            <div class="stap-step-label">TV&nbsp;gegevens</div>
          </div>
          <div class="stap-lijn"></div>
          <div class="stap-step" data-stap="3">
            <div class="stap-dot">
              <span class="stap-dot-nr">3</span>
              <svg class="stap-dot-check" viewBox="0 0 14 14" fill="none"><polyline points="2,7 6,11 12,3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            <div class="stap-step-label">Defect</div>
          </div>
          <div class="stap-lijn"></div>
          <div class="stap-step" data-stap="4">
            <div class="stap-dot">
              <span class="stap-dot-nr">4</span>
              <svg class="stap-dot-check" viewBox="0 0 14 14" fill="none"><polyline points="2,7 6,11 12,3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            <div class="stap-step-label">Contact</div>
          </div>
        </div>
        <style>
        .stap-dot { position: relative; }
        .stap-dot-nr  { display: block; }
        .stap-dot-check { display: none; width: 14px; height: 14px; }
        .stap-step.actief:not(.huidig) .stap-dot-nr  { display: none; }
        .stap-step.actief:not(.huidig) .stap-dot-check { display: block; }
        </style>

        <form action="<?= BASE_URL ?>/api/send-advies.php" method="POST" id="advies-form">
          <input type="hidden" name="csrf_token"         value="<?= csrf() ?>" />
          <input type="hidden" name="geadviseerde_route" id="geadviseerde_route" value="" />
          <input type="hidden" name="coulance_kans"      id="coulance_kans"    value="" />
          <input type="hidden" name="model_repareerbaar" id="model_repareerbaar" value="" />

          <!-- STAP 1 -->
          <div class="form-stap" id="stap-1">
            <div class="stap-header">
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

          <!-- STAP 2 -->
          <div class="form-stap" id="stap-2" style="display:none;">
            <div class="stap-header">
              <h3>Over uw televisie</h3>
              <p>Merk, model en aankoopinformatie bepalen de route.</p>
            </div>
            <div class="field-row">
              <div class="field">
                <label>Merk *</label>
                <select name="merk" id="merk" required onchange="resetRepareerbaar()">
                  <option value="">Selecteer merk</option>
                  <?php foreach (getMerken() as $m): ?>
                  <option value="<?= h($m) ?>"><?= h($m) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="field">
                <label>Modelnummer *</label>
                <input type="text" name="modelnummer" id="modelnummer"
                       placeholder="Bijv. UE55CU8000" required
                       oninput="resetRepareerbaar()" />
                <p class="field-hint">Staat achter op de tv of via Instellingen &rarr; Ondersteuning</p>
              </div>
            </div>

            <div id="repareerbaar-feedback" style="display:none;"></div>

            <div class="field-row">
              <div class="field">
                <label>Aanschafjaar *</label>
                <select name="aanschafjaar" id="aanschafjaar" required onchange="berekenRoute()">
                  <option value="">Selecteer jaar</option>
                  <?php for ($y = date('Y'); $y >= 2010; $y--): ?>
                  <option value="<?= $y ?>"><?= $y ?></option>
                  <?php endfor; ?>
                  <option value="2009">Ouder dan 2010</option>
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

            <div class="field">
              <label>Waar gekocht?</label>
              <select name="aankoop_locatie" id="aankoop_locatie" onchange="berekenRoute()">
                <option value="nl">In Nederland</option>
                <option value="buitenland">Buiten Nederland (let op: andere garantieregels)</option>
                <option value="onbekend">Weet ik niet meer</option>
              </select>
            </div>

            <div id="garantie-feedback" class="garantie-feedback" style="display:none;"></div>

            <div class="stap-nav">
              <button type="button" class="stap-terug" onclick="naarStap(1)">&larr; Terug</button>
              <button type="button" class="stap-volgende" onclick="naarStapMetCheck(3)">Volgende &rarr;</button>
            </div>
          </div>

          <!-- STAP 3 -->
          <div class="form-stap" id="stap-3" style="display:none;">
            <div class="stap-header">
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

          <!-- STAP 4 -->
          <div class="form-stap" id="stap-4" style="display:none;">
            <div class="stap-header">
              <h3>Uw contactgegevens</h3>
              <p>Hier sturen wij uw persoonlijk advies naartoe.</p>
            </div>
            <div class="field">
              <label>E-mailadres *</label>
              <input type="email" name="email" placeholder="naam@email.nl" required />
              <p class="field-hint">Geen spam. Alleen uw advies.</p>
            </div>
            <div id="route-samenvatting" class="route-samenvatting"></div>
            <div class="disclaimer-box">
              &#9888;&#65039; Het advies van Reparatieplatform.nl is indicatief en vrijblijvend.
              Aan dit advies kunnen geen rechten worden ontleend.
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
const REGELS = <?= $rJs ?>;
const HUIDIG_JAAR = <?= date('Y') ?>;

function merkToegestaan(merkLijst, merk) {
  if (!merkLijst || merkLijst.length === 0) return true;
  return merkLijst.map(m => m.toLowerCase()).includes((merk || '').toLowerCase());
}

let _rep    = { geladen: false, gevonden: false, repareerbaar: false };
let _repTimer = null;

function resetRepareerbaar() {
  _rep = { geladen: false, gevonden: false, repareerbaar: false };
  const fb = document.getElementById('repareerbaar-feedback');
  if (fb) fb.style.display = 'none';
  document.getElementById('model_repareerbaar').value = '';
  clearTimeout(_repTimer);
  _repTimer = setTimeout(checkRepareerbaar, 600);
}

function checkRepareerbaar() {
  const merk  = document.getElementById('merk')?.value || '';
  const model = document.getElementById('modelnummer')?.value?.trim() || '';
  if (!merk || model.length < 3) return;
  fetch(`<?= BASE_URL ?>/api/check-repareerbaar.php?merk=${encodeURIComponent(merk)}&modelnummer=${encodeURIComponent(model)}`)
    .then(r => r.json())
    .then(d => {
      _rep = { geladen: true, gevonden: d.gevonden, repareerbaar: d.repareerbaar };
      document.getElementById('model_repareerbaar').value = d.repareerbaar ? 'ja' : 'nee';
      toonRepFeedback();
      berekenRoute();
    }).catch(() => {});
}

function toonRepFeedback() {
  const fb = document.getElementById('repareerbaar-feedback');
  if (!fb || !_rep.geladen) return;
  if (!_rep.gevonden) { fb.style.display = 'none'; return; }
  if (_rep.repareerbaar) {
    fb.className = 'rep-feedback rep-ok';
    fb.innerHTML = '<span class="rep-ico">&#9989;</span> Dit model staat in onze database als <strong>repareerbaar</strong>.';
  } else {
    fb.className = 'rep-feedback rep-nee';
    fb.innerHTML = '<span class="rep-ico">&#9851;</span> Dit model staat in onze database als <strong>niet-repareerbaar</strong>. Wij begeleiden u richting verantwoorde recycling.';
  }
  fb.style.display = 'block';
}

function naarStap(nr) {
  const huidig = document.querySelector('.form-stap:not([style*="display:none"])');
  for (const el of (huidig?.querySelectorAll('[required]') || [])) {
    if (!el.value) { el.focus(); el.reportValidity(); return; }
  }
  _toonStap(nr);
}
function naarStapMetCheck(nr) {
  const huidig = document.querySelector('.form-stap:not([style*="display:none"])');
  for (const el of (huidig?.querySelectorAll('[required]') || [])) {
    if (!el.value) { el.focus(); el.reportValidity(); return; }
  }
  if (!_rep.geladen) checkRepareerbaar();
  _toonStap(nr);
}
function _toonStap(nr) {
  document.querySelectorAll('.form-stap').forEach(s => s.style.display = 'none');
  const doel = document.getElementById('stap-' + nr);
  if (doel) { doel.style.display = 'block'; doel.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
  document.querySelectorAll('.stap-step').forEach((d, i) => {
    d.classList.toggle('actief', i < nr);
    d.classList.toggle('huidig', i === nr - 1);
  });
  document.querySelectorAll('.stap-lijn').forEach((l, i) => {
    l.classList.toggle('actief', i < nr - 1);
  });
  berekenRoute();
  if (nr === 4) vulSamenvatting();
}

document.querySelectorAll('.route-keuze input[type=radio]').forEach(r => {
  r.addEventListener('change', function() {
    document.querySelectorAll('.route-keuze').forEach(k => k.classList.remove('geselecteerd'));
    this.closest('.route-keuze').classList.add('geselecteerd');
    berekenRoute();
  });
});

function berekenRoute() {
  const situatie   = document.querySelector('[name=situatie]:checked')?.value || '';
  const merk       = document.getElementById('merk')?.value || '';
  const aanschJaar = parseInt(document.getElementById('aanschafjaar')?.value) || null;
  const waarde     = document.getElementById('aanschafwaarde')?.value || '';
  const locatie    = document.getElementById('aankoop_locatie')?.value || 'nl';
  const klacht     = document.getElementById('klacht_type')?.value || '';

  const leeftijd = aanschJaar ? (HUIDIG_JAAR - aanschJaar) : null;

  const gTermijn          = REGELS.garantie_termijn_jaar          ?? 2;
  const gAlleenNl         = REGELS.garantie_alleen_nl             ?? true;
  const gUitsluit         = REGELS.garantie_uitsluiten_klachten   ?? ['gebarsten_scherm'];
  const gMerken           = REGELS.garantie_merken                ?? [];

  const cMin              = REGELS.coulance_min_jaar              ?? 2;
  const cMax              = REGELS.coulance_max_jaar              ?? 5;
  const cUitsluit         = REGELS.coulance_uitsluiten_klachten   ?? ['gebarsten_scherm'];
  const cMerken           = REGELS.coulance_merken                ?? [];
  const cMatrix           = REGELS.coulance_kans_matrix           ?? [];
  const cAftrekBuitenland = REGELS.coulance_aftrek_buitenland     ?? 30;

  const repMin            = REGELS.reparatie_min_jaar             ?? 2;
  const repMax            = REGELS.reparatie_max_jaar             ?? 10;
  const vereistRep        = REGELS.reparatie_vereist_repareerbaar ?? true;
  const repMerken         = REGELS.reparatie_merken               ?? [];

  const recycMin          = REGELS.recycling_min_jaar             ?? 10;
  const taxBijSchade      = REGELS.taxatie_bij_schade             ?? true;
  const taxMerken         = REGELS.taxatie_merken                 ?? [];

  const kanRep = (!vereistRep || !_rep.geladen || _rep.repareerbaar)
                 && merkToegestaan(repMerken, merk);

  let route = '', badge = '', toel = '', kans = 0;

  if (situatie === 'schade' && taxBijSchade) {
    if (!merk || merkToegestaan(taxMerken, merk)) {
      route = 'taxatie';
      badge = '&#128203; Taxatierapport';
      toel  = 'Omdat er sprake is van externe schade is een taxatierapport de juiste route voor uw verzekeraar.';
    } else {
      if (kanRep) {
        route = 'reparatie';
        badge = '&#128295; Reparatie aan huis';
        toel  = 'Externe schade, maar dit merk komt niet in aanmerking voor taxatie. Reparatie aan huis is de meest geschikte optie.';
      } else {
        route = 'recycling';
        badge = '&#9851; Recycling';
        toel  = 'Dit model is niet repareerbaar en komt niet in aanmerking voor taxatie. Wij begeleiden u richting verantwoorde recycling.';
      }
    }
  }

  else if (situatie === 'storing' && leeftijd !== null) {
    const isGUitsluit = gUitsluit.includes(klacht);
    const isCUitsluit = cUitsluit.includes(klacht);
    const isNl        = locatie === 'nl';
    const merkGarantie = merkToegestaan(gMerken, merk);
    const merkCoulance = merkToegestaan(cMerken, merk);

    if (leeftijd <= gTermijn && !isGUitsluit && (!gAlleenNl || isNl) && merkGarantie) {
      route = 'garantie';
      badge = '&#9989; Garantie';
      toel  = 'Op basis van het aanschafjaar valt uw televisie waarschijnlijk nog onder de wettelijke garantietermijn van ' + gTermijn + ' jaar.';
    }
    else if (leeftijd <= gTermijn && locatie === 'buitenland') {
      if (kanRep) {
        route = 'reparatie';
        badge = '&#128295; Reparatie aan huis';
        toel  = 'Televisies buiten Nederland gekocht vallen buiten Nederlandse garantieregels. Reparatie is de meest praktische optie.';
      } else {
        route = 'recycling'; badge = '&#9851; Recycling';
        toel  = 'Dit model is niet repareerbaar. Wij begeleiden u richting verantwoorde recycling.';
      }
    }
    else if (leeftijd <= gTermijn && !merkGarantie) {
      if (merkCoulance && leeftijd > cMin && leeftijd <= cMax && !isCUitsluit) {
        // val door naar coulance-blok
      } else if (kanRep) {
        route = 'reparatie';
        badge = '&#128295; Reparatie aan huis';
        toel  = 'Dit merk komt niet in aanmerking voor de garantieroute via ons platform. Reparatie aan huis is de meest geschikte optie.';
      } else {
        route = 'recycling'; badge = '&#9851; Recycling';
        toel  = 'Dit model is niet repareerbaar en het merk komt niet in aanmerking voor garantie.';
      }
    }

    if (!route && leeftijd > cMin && leeftijd <= cMax && !isCUitsluit && merkCoulance) {
      const matrixRij = cMatrix.find(m => m.prijsklasse === waarde)
                     || cMatrix.find(m => m.prijsklasse === '');
      const basisKans   = matrixRij?.basis_kans      ?? 50;
      const aftrekPerJr = matrixRij?.per_jaar_aftrek ?? 6;
      const jarenBoven  = leeftijd - cMin;
      kans = Math.max(5, Math.min(95, Math.round(basisKans - (aftrekPerJr * jarenBoven))));
      if (locatie === 'buitenland') kans = Math.max(5, kans - cAftrekBuitenland);
      route = 'coulance';
      badge = '&#129309; Coulanceregeling (' + kans + '% kans)';
      toel  = 'Uw televisie is ' + leeftijd + ' jaar oud. Garantie is verlopen, maar veel fabrikanten bieden coulance aan. Wij schatten de kans op <strong>' + kans + '%</strong>.';
    }
    else if (!route && leeftijd > repMin && leeftijd <= repMax) {
      if (kanRep) {
        route = 'reparatie';
        badge = '&#128295; Reparatie aan huis';
        toel  = 'Garantie en coulance zijn niet meer van toepassing. Reparatie aan huis is de meest kostenefficiënte oplossing.';
      } else {
        route = 'recycling'; badge = '&#9851; Recycling';
        toel  = 'Dit model staat als niet-repareerbaar in onze database. Wij begeleiden u richting verantwoorde recycling.';
      }
    }
    else if (!route && leeftijd > recycMin) {
      route = 'recycling';
      badge = '&#9851; Recycling';
      toel  = 'Een televisie ouder dan ' + recycMin + ' jaar — reparatiekosten overtreffen vaak de waarde. Wij adviseren eerlijk over recycling of doorverkoop.';
    }
    else if (!route && klacht === 'gebarsten_scherm') {
      if (kanRep) {
        route = 'reparatie'; badge = '&#128295; Schermvervanging';
        toel  = 'Een gebarsten scherm valt nooit onder garantie. Wij kijken of schermvervanging economisch zinvol is.';
      } else {
        route = 'recycling'; badge = '&#9851; Recycling';
        toel  = 'Dit model is niet repareerbaar. Verantwoorde recycling is de beste route.';
      }
    }
  }

  document.getElementById('geadviseerde_route').value = route;
  document.getElementById('coulance_kans').value      = kans;

  const ind     = document.getElementById('routing-indicator');
  const badgeEl = document.getElementById('routing-badge');
  const toelEl  = document.getElementById('routing-toelichting');
  if (route && ind) {
    ind.style.display   = 'block';
    badgeEl.innerHTML   = badge;
    badgeEl.className   = 'routing-badge route-' + route.replace(/[^a-z]/g, '');
    toelEl.innerHTML    = toel;
  } else if (ind) {
    ind.style.display = 'none';
  }

  const fb = document.getElementById('garantie-feedback');
  if (fb && route) {
    fb.style.display = 'block';
    fb.innerHTML  = '<strong>Voorlopige route:</strong> ' + badge + '<br><small>' + toel + '</small>';
    fb.className  = 'garantie-feedback feedback-' + route.replace(/[^a-z]/g, '');
  } else if (fb) {
    fb.style.display = 'none';
  }
}

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