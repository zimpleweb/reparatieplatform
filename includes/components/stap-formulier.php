<?php
/**
 * Component: stap-formulier.php
 * Herbruikbaar stappenplan-formulier (zelfde als advies.php).
 * Vereist: $stappenConfig, $aantalStappen, $klachtRoutingJs, $rJs zijn beschikbaar in de scope.
 */
?>
<?php if (isset($_GET['error'])): ?>
  <div class="alert alert-error">Er is iets misgegaan. Controleer je gegevens en probeer het opnieuw.</div>
<?php endif; ?>

<!-- Voortgangsbalk -->
<div class="stap-progress" role="progressbar" aria-label="Stappenplan">
  <?php foreach ($stappenConfig as $si => $stap): ?>
  <?php $isLaatste = ($si === $aantalStappen - 1); ?>
  <div class="stap-step <?= $si === 0 ? 'actief huidig' : '' ?>" data-stap="<?= (int)$stap['nummer'] ?>">
    <div class="stap-dot">
      <span class="stap-dot-nr"><?= (int)$stap['nummer'] ?></span>
      <svg class="stap-dot-check" viewBox="0 0 14 14" fill="none">
        <polyline points="2,7 6,11 12,3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </div>
    <div class="stap-step-label"><?= h($stap['label']) ?></div>
  </div>
  <?php if (!$isLaatste): ?>
  <div class="stap-lijn <?= $si === 0 ? 'actief' : '' ?>"></div>
  <?php endif; ?>
  <?php endforeach; ?>
</div>
<style>
.stap-dot { position: relative; }
.stap-dot-nr    { display: block; }
.stap-dot-check { display: none; width: 14px; height: 14px; }
.stap-step.actief:not(.huidig) .stap-dot-nr    { display: none; }
.stap-step.actief:not(.huidig) .stap-dot-check { display: block; }
</style>

<form action="<?= BASE_URL ?>/api/send-advies.php" method="POST" id="advies-form" data-recaptcha="advies_aanvragen">
  <input type="hidden" name="csrf_token"          value="<?= csrf() ?>" />
  <input type="hidden" name="geadviseerde_route"  id="geadviseerde_route"  value="" />
  <input type="hidden" name="coulance_kans"       id="coulance_kans"       value="" />
  <input type="hidden" name="model_repareerbaar"  id="model_repareerbaar"  value="" />

  <?php foreach ($stappenConfig as $si => $stap): ?>
  <?php
    $nr      = (int)$stap['nummer'];
    $isFirst = ($si === 0);
    $isLast  = ($si === $aantalStappen - 1);
    $prevNr  = $nr - 1;
    $nextNr  = $nr + 1;
  ?>
  <!-- STAP <?= $nr ?>: <?= h($stap['titel']) ?> -->
  <div class="form-stap" id="stap-<?= $nr ?>"<?= $isFirst ? '' : ' style="display:none;"' ?>>
    <div class="stap-header">
      <h3><?= h($stap['titel']) ?></h3>
      <p><?= h($stap['lead']) ?></p>
    </div>

    <?php if ($nr === 1): ?>
    <div class="route-keuze-grid">
      <label class="route-keuze" data-type="storing">
        <input type="radio" name="situatie" value="storing" required />
        <div class="route-keuze-inner">
          <div class="route-keuze-icon">&#128295;</div>
          <strong>Technisch defect of storing</strong>
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

    <?php elseif ($nr === 2): ?>
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

    <?php elseif ($nr === 3): ?>
    <div class="field">
      <label>Type klacht *</label>
      <select name="klacht_type" id="klacht_type" required onchange="berekenRoute()">
        <option value="">Selecteer type probleem</option>
        <optgroup label="Beeldproblemen">
          <option value="gebarsten_scherm">Kapot of gebarsten scherm</option>
          <option value="strepen">Strepen of lijnen in beeld</option>
          <option value="geen_beeld">Geen beeld, wel geluid</option>
          <option value="backlight">Donkere vlekken of backlight-uitval</option>
          <option value="flikkering">Bevroren beeld of flikkering</option>
          <option value="kleur">Kleurproblemen of sterk verkleurde pixels</option>
        </optgroup>
        <optgroup label="Stroom &amp; hardware">
          <option value="niet_aan">TV gaat niet aan</option>
          <option value="stroomstoot">Schade na stroomstoot of blikseminslag</option>
          <option value="oververhitting">Oververhitting of stopt na korte tijd</option>
        </optgroup>
        <optgroup label="Software &amp; bediening">
          <option value="software">Software of Smart TV werkt niet</option>
          <option value="afstandsbediening">Afstandsbediening of bediening reageert niet</option>
          <option value="geluid">Geen of slecht geluid, beeld werkt wel</option>
        </optgroup>
        <optgroup label="Overig">
          <option value="anders">Anders of niet in de lijst</option>
        </optgroup>
      </select>
    </div>
    <div class="field">
      <label>Omschrijving</label>
      <textarea name="omschrijving" rows="4" placeholder="Bijv: zwarte strepen rechts, donkere vlek linksonder, scherm flikkert na 10 minuten..."></textarea>
    </div>

    <?php elseif ($isLast): ?>
    <div class="field">
      <label>E-mailadres *</label>
      <input type="email" name="email" placeholder="naam@email.nl" required />
      <p class="field-hint">Geen spam. Alleen je advies.</p>
    </div>
    <div id="route-samenvatting" class="route-samenvatting"></div>
    <div class="disclaimer-box">
      &#9888;&#65039; Het advies van Reparatieplatform.nl is indicatief en vrijblijvend.
      Aan dit advies kunnen geen rechten worden ontleend.
    </div>

    <?php else: ?>
    <div class="field">
      <p style="color:#64748b;font-size:.875rem;">
        Deze stap is toegevoegd via de admin. Inhoud kan hier uitgebreid worden.
      </p>
    </div>
    <?php endif; ?>

    <!-- Navigatieknoppen -->
    <div class="stap-nav">
      <?php if (!$isFirst): ?>
      <button type="button" class="stap-terug" onclick="naarStap(<?= $prevNr ?>)">&larr; Terug</button>
      <?php endif; ?>
      <?php if (!$isLast): ?>
        <?php if ($nr === 2): ?>
        <button type="button" class="stap-volgende" onclick="naarStapMetGarantieCheck(<?= $nextNr ?>)">Volgende &rarr;</button>
        <?php else: ?>
        <button type="button" class="stap-volgende" onclick="naarStap(<?= $nextNr ?>)">Volgende &rarr;</button>
        <?php endif; ?>
      <?php else: ?>
      <button type="submit" class="submit-btn">Verstuur en ontvang gratis advies &rarr;</button>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>

</form>

<!-- Garantie direct-advies panel -->
<div id="garantie-advies-panel" style="display:none;padding:.25rem 0;">
  <div style="margin-bottom:1rem;">
    <div style="display:inline-flex;align-items:center;gap:.4rem;background:rgba(40,120,100,.1);border:1px solid rgba(40,120,100,.3);border-radius:999px;padding:.28rem .9rem;font-size:.75rem;font-weight:700;color:#287864;letter-spacing:.04em;margin-bottom:.65rem;">&#9989; Wettelijke garantie</div>
    <h3 style="font-size:1rem;font-weight:800;color:#1a2332;margin:0 0 .35rem;">Uw televisie valt (mogelijk) onder garantie</h3>
    <p style="font-size:.84rem;color:#475569;line-height:1.6;margin:0;">Op basis van het aanschafjaar valt uw televisie waarschijnlijk nog binnen de wettelijke garantietermijn. Neem contact op met de winkel of het merk.</p>
  </div>
  <div style="margin-bottom:.75rem;">
    <div style="font-size:.75rem;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.5rem;">&#128722; Neem contact op via:</div>
    <div id="garantie-shops-list" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:.4rem;"></div>
    <div id="garantie-merk-wrap" style="display:none;margin-top:.7rem;">
      <div style="font-size:.75rem;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.45rem;">&#127981; Of direct bij het merk:</div>
      <div id="garantie-merk-link"></div>
    </div>
  </div>
  <div style="background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:10px;padding:.75rem .9rem;font-size:.8rem;color:#14532d;line-height:1.6;margin-bottom:.85rem;">
    <strong>Uw rechten:</strong> Bij een defect binnen de garantietermijn heeft u recht op gratis reparatie, vervanging of terugbetaling. De verkoper is primair verantwoordelijk.
  </div>
  <button type="button" onclick="garantieTerug()" style="background:none;border:1.5px solid #e2e8f0;border-radius:8px;padding:.4rem .9rem;font-size:.8rem;font-weight:600;color:#475569;cursor:pointer;font-family:inherit;transition:border-color .15s,color .15s;" onmouseover="this.style.borderColor='#287864';this.style.color='#287864';" onmouseout="this.style.borderColor='#e2e8f0';this.style.color='#475569';">&#8592; Terug</button>
</div>

<script>
const REGELS              = <?= $rJs ?>;
const KLACHT_ROUTING      = <?= $klachtRoutingJs ?>;
const HUIDIG_JAAR         = <?= date('Y') ?>;
const AANTAL_STAPPEN      = <?= $aantalStappen ?>;
const GARANTIE_SHOPS      = <?= $garantieShopsJs ?? '[]' ?>;
const GARANTIE_MERK_URLS  = <?= $garantieMerkUrlsJs ?? '{}' ?>;

function merkToegestaan(merkLijst, merk) {
  if (!merkLijst || merkLijst.length === 0) return true;
  return merkLijst.map(m => m.toLowerCase()).includes((merk || '').toLowerCase());
}
function klachtGeblokkeerd(lijst, klacht) {
  if (!klacht || !lijst || lijst.length === 0) return false;
  return lijst.includes(klacht);
}

let _rep      = { geladen: false, gevonden: false, repareerbaar: false, taxatie: false };
let _repTimer = null;

function resetRepareerbaar() {
  _rep = { geladen: false, gevonden: false, repareerbaar: false, taxatie: false };
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
      _rep = { geladen: true, gevonden: !!d.gevonden, repareerbaar: !!d.repareerbaar, taxatie: !!d.taxatie };
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
    fb.innerHTML = '<span class="rep-ico">&#9851;</span> Dit model staat in onze database als <strong>niet-repareerbaar</strong>. Wij begeleiden je richting verantwoorde recycling.';
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
function naarStapMetGarantieCheck(nr) {
  const huidig = document.querySelector('.form-stap:not([style*="display:none"])');
  for (const el of (huidig?.querySelectorAll('[required]') || [])) {
    if (!el.value) { el.focus(); el.reportValidity(); return; }
  }
  if (!_rep.geladen) checkRepareerbaar();
  if ((document.getElementById('geadviseerde_route')?.value || '') === 'garantie') {
    toonGarantiePanel(); return;
  }
  _toonStap(nr);
}
function toonGarantiePanel() {
  const panel = document.getElementById('garantie-advies-panel');
  if (!panel) return;
  document.querySelectorAll('.form-stap').forEach(s => s.style.display = 'none');
  _vulGarantiePanel();
  panel.style.display = 'block';
  panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
}
function garantieTerug() {
  const panel = document.getElementById('garantie-advies-panel');
  if (panel) panel.style.display = 'none';
  document.querySelectorAll('.form-stap').forEach(s => s.style.display = 'none');
  const stap2 = document.getElementById('stap-2');
  if (stap2) { stap2.style.display = 'block'; stap2.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
}
function _vulGarantiePanel() {
  const merk      = document.getElementById('merk')?.value || '';
  const shopsList = document.getElementById('garantie-shops-list');
  if (shopsList) {
    shopsList.innerHTML = GARANTIE_SHOPS.length > 0
      ? GARANTIE_SHOPS.map(s => s.support_url
          ? `<a href="${s.support_url}" target="_blank" rel="noopener noreferrer" style="display:flex;align-items:center;justify-content:space-between;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:9px;padding:.5rem .8rem;font-size:.82rem;font-weight:600;color:#1a2332;text-decoration:none;">${s.naam} <span style="opacity:.6;font-size:.8em;">&#8599;</span></a>`
          : `<span style="display:flex;align-items:center;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:9px;padding:.5rem .8rem;font-size:.82rem;font-weight:600;color:#64748b;">${s.naam}</span>`
        ).join('')
      : '<p style="font-size:.82rem;color:#64748b;">Neem contact op met de winkel waar u de televisie heeft gekocht.</p>';
  }
  const merkWrap = document.getElementById('garantie-merk-wrap');
  const merkLink = document.getElementById('garantie-merk-link');
  if (merk && GARANTIE_MERK_URLS[merk] && merkWrap && merkLink) {
    merkLink.innerHTML = `<a href="${GARANTIE_MERK_URLS[merk]}" target="_blank" rel="noopener noreferrer" style="display:inline-flex;align-items:center;gap:.4rem;background:#eff6ff;border:1.5px solid #bfdbfe;border-radius:9px;padding:.5rem .9rem;font-size:.82rem;font-weight:600;color:#1e40af;text-decoration:none;">&#127981; ${merk} ondersteuning <span style="opacity:.7;font-size:.8em;">&#8599;</span></a>`;
    merkWrap.style.display = 'block';
  } else if (merkWrap) { merkWrap.style.display = 'none'; }
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
  if (nr === AANTAL_STAPPEN) vulSamenvatting();
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
  const leeftijd   = aanschJaar ? (HUIDIG_JAAR - aanschJaar) : null;

  const gTermijn          = REGELS.garantie_termijn_jaar          ?? 2;
  const gAlleenNl         = REGELS.garantie_alleen_nl             ?? true;
  const gMerken           = REGELS.garantie_merken                ?? [];
  const cMin              = REGELS.coulance_min_jaar              ?? 2;
  const cMax              = REGELS.coulance_max_jaar              ?? 5;
  const cMerken           = REGELS.coulance_merken                ?? [];
  const cMatrix           = REGELS.coulance_kans_matrix           ?? [];
  const cAftrekBuitenland = parseInt(REGELS.coulance_aftrek_buitenland ?? 30);
  const repMin            = REGELS.reparatie_min_jaar             ?? 2;
  const repMax            = REGELS.reparatie_max_jaar             ?? 10;
  const vereistRep        = REGELS.reparatie_vereist_repareerbaar ?? true;
  const repMerken         = REGELS.reparatie_merken               ?? [];
  const recycMin          = REGELS.recycling_min_jaar             ?? 10;
  const taxBijSchade      = REGELS.taxatie_bij_schade             ?? true;
  const taxMerken         = REGELS.taxatie_merken                 ?? [];

  const gUitsluit   = KLACHT_ROUTING.garantie_uitsluiten  ?? ['gebarsten_scherm'];
  const cUitsluit   = KLACHT_ROUTING.coulance_uitsluiten  ?? ['gebarsten_scherm'];
  const repUitsluit = KLACHT_ROUTING.reparatie_uitsluiten ?? ['gebarsten_scherm'];
  const taxInclude  = KLACHT_ROUTING.taxatie_include      ?? ['gebarsten_scherm','stroomstoot'];
  const taxUitsluit = KLACHT_ROUTING.taxatie_uitsluiten   ?? [];

  const kanRep     = !klachtGeblokkeerd(repUitsluit, klacht) && (!vereistRep || !_rep.geladen || _rep.repareerbaar) && merkToegestaan(repMerken, merk);
  const kanTaxatie = !klachtGeblokkeerd(taxUitsluit, klacht) && merkToegestaan(taxMerken, merk) && (!_rep.geladen || _rep.taxatie || situatie === 'schade');

  let route = '', badge = '', toel = '', kans = 0;

  if (situatie === 'schade') {
    if (taxBijSchade && kanTaxatie) { route='taxatie'; badge='&#128203; Taxatierapport'; toel='Omdat er sprake is van externe schade is een taxatierapport de juiste route voor je verzekeraar. De schadetaxatie kost 49 euro.'; }
    else if (kanRep) { route='reparatie'; badge='&#128295; Reparatie aan huis'; toel='Externe schade, maar dit merk of model komt niet in aanmerking voor taxatie. Reparatie aan huis is de meest geschikte optie.'; }
    else { route='recycling'; badge='&#9851; Recycling'; toel='Dit model is niet repareerbaar en komt niet in aanmerking voor taxatie. Wij begeleiden je richting verantwoorde recycling.'; }
  } else if (situatie === 'storing' && leeftijd !== null) {
    const isNl=locatie==='nl', merkGarantie=merkToegestaan(gMerken,merk), merkCoulance=merkToegestaan(cMerken,merk);
    const isGUitsluit=klachtGeblokkeerd(gUitsluit,klacht), isCUitsluit=klachtGeblokkeerd(cUitsluit,klacht);
    if (taxInclude.length>0 && taxInclude.includes(klacht) && kanTaxatie) { route='taxatie'; badge='&#128203; Schadetaxatie'; toel='Dit type defect (bijv. gebarsten scherm, stroomstoot) is doorgaans een verzekeringskwestie. Een taxatierapport is de aangewezen route. Kosten: 49 euro.'; }
    else if (leeftijd<=gTermijn && !isGUitsluit && merkGarantie && (!gAlleenNl||isNl)) { route='garantie'; badge='&#9989; Garantie'; toel='Op basis van het aanschafjaar valt je televisie waarschijnlijk nog onder de wettelijke garantietermijn van '+gTermijn+' jaar. Wij laten je zien hoe je dit aanpakt.'; }
    else if (leeftijd<=gTermijn && locatie==='buitenland') { if(kanRep){route='reparatie';badge='&#128295; Reparatie aan huis';toel='Televisies buiten Nederland gekocht vallen buiten de Nederlandse garantieregels. Reparatie is de meest praktische optie.';}else{route='recycling';badge='&#9851; Recycling';toel='Dit model is niet repareerbaar. Wij begeleiden je richting verantwoorde recycling.';} }
    if (!route && leeftijd>cMin && leeftijd<=cMax && !isCUitsluit && merkCoulance) {
      const matrixRij=cMatrix.find(m=>m.prijsklasse===waarde)||cMatrix.find(m=>m.prijsklasse==='');
      const basisKans=parseInt(matrixRij?.basis_kans??50), aftrekPerJr=parseInt(matrixRij?.per_jaar_aftrek??6);
      kans=Math.max(5,Math.min(95,Math.round(basisKans-(aftrekPerJr*Math.max(0,leeftijd-cMin)))));
      if(locatie==='buitenland') kans=Math.max(5,kans-cAftrekBuitenland);
      route='coulance'; badge='&#129309; Coulanceregeling ('+kans+'% kans)'; toel='Je televisie is '+leeftijd+' jaar oud. Garantie is verlopen, maar veel fabrikanten bieden nog coulance aan. Wij schatten de kans op <strong>'+kans+'%</strong>. Gaat de verkoper of het merk niet mee? Dan helpen wij je alsnog met vrijblijvend reparatieadvies.';
    } else if (!route && leeftijd>=repMin && leeftijd<=repMax) {
      if(kanRep){route='reparatie';badge='&#128295; Reparatie aan huis';toel='Garantie en coulance zijn niet meer van toepassing. Reparatie aan huis is de meest kostenefficiënte oplossing.';}else{route='recycling';badge='&#9851; Recycling';toel='Dit model staat als niet-repareerbaar in onze database. Wij begeleiden je richting verantwoorde recycling.';}
    } else if (!route && leeftijd>recycMin) { route='recycling'; badge='&#9851; Recycling'; toel='Een televisie ouder dan '+recycMin+' jaar. De reparatiekosten overtreffen vaak de waarde. Wij adviseren je eerlijk over recycling of doorverkoop.'; }
    else if (!route && klacht==='gebarsten_scherm') { if(kanRep){route='reparatie';badge='&#128295; Schermvervanging';toel='Een gebarsten scherm valt nooit onder de garantie, maar schermvervanging is in veel gevallen kostenefficiënt.';}else{route='recycling';badge='&#9851; Recycling';toel='Schermschade op een niet-repareerbaar model. Wij adviseren richting verantwoorde recycling.';} }
  }

  const ind=document.getElementById('routing-indicator'), bdg=document.getElementById('routing-badge'), tl=document.getElementById('routing-toelichting');
  if (route && ind) { bdg.innerHTML=badge; tl.innerHTML=toel; ind.style.display='block'; document.getElementById('geadviseerde_route').value=route; document.getElementById('coulance_kans').value=kans||''; }
  else if (ind) { ind.style.display='none'; }

  const gPanel = document.getElementById('garantie-advies-panel');
  if (gPanel && gPanel.style.display !== 'none') {
    if (route !== 'garantie') { garantieTerug(); }
    else { _vulGarantiePanel(); }
  }
}

function vulSamenvatting() {
  const el=document.getElementById('route-samenvatting');
  const badge=document.getElementById('routing-badge')?.innerHTML||'';
  const toel=document.getElementById('routing-toelichting')?.innerHTML||'';
  if (!el||!badge) { if(el) el.style.display='none'; return; }
  el.innerHTML=`<div class="samenvatting-label">Jouw verwachte route:</div><div class="samenvatting-badge">${badge}</div><div class="samenvatting-toel">${toel}</div>`;
  el.style.display='block';
}
</script>
