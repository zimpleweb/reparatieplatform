<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle       = 'Coulanceregeling televisie – Buiten garantie toch vergoed? | Reparatieplatform.nl';
$pageDescription = 'Garantie verlopen maar televisie al snel kapot? Ontdek hoe de coulanceregeling werkt, welke fabrikanten meedoen en hoe je een aanvraag indient.';
$canonicalUrl    = '/coulance.php';

include __DIR__ . '/includes/header.php';
?>

<!-- HERO: donker blok -->
<div class="page-header-hero-only">
  <div class="page-header-stappen-inner">

    <div class="breadcrumb">
      <a href="<?= BASE_URL ?>/">Home</a><span class="sep">/</span>
      <span style="color:rgba(255,255,255,.4)">Coulanceregeling</span>
    </div>

    <h1>Coulanceregeling<br>bij je televisie</h1>
    <p class="hero-lead">Garantie verlopen maar televisie al snel stuk? Veel fabrikanten vergoeden reparatie of vervanging buiten de garantietermijn als je weet hoe je het aanpakt. Wij helpen je daar gratis bij.</p>

    <div class="hero-badge">&#129309; Kans op vergoeding buiten garantie</div>

  </div>
</div>

<!-- STAPPEN SECTIE: apart blok met lichte achtergrond -->
<div class="stappen-sectie-licht">
  <div class="stappen-sectie-inner">

    <h2 class="stappen-titel-licht">Hoe werkt de coulanceregeling?</h2>
    <p class="stappen-lead-licht">Een coulanceregeling is geen wettelijk recht, maar een vrijwillige tegemoetkoming van de fabrikant of winkel. Of het lukt hangt af van het merk, de leeftijd van je televisie en hoe je het aanpakt.</p>

    <div class="zowerkhet-steps-licht">
      <div class="zowerkhet-step-licht">
        <span class="zowerkhet-step-num-licht">Stap 01</span>
        <div class="zowerkhet-step-icon-licht">&#128269;</div>
        <h3>Controleer je kans</h3>
        <p>Vraag gratis ons advies aan. Wij toetsen merk, aanschafjaar en klachttype aan de actuele coulanceregels van de fabrikant en geven je een eerlijke inschatting van de kans op vergoeding.</p>
        <span class="zowerkhet-step-badge-licht">&#10003; Gratis check</span>
      </div>
      <div class="zowerkhet-step-licht">
        <span class="zowerkhet-step-num-licht">Stap 02</span>
        <div class="zowerkhet-step-icon-licht">&#128222;</div>
        <h3>Fabrikant benaderen</h3>
        <p>Wij adviseren je hoe je de klantenservice het beste benadert, welke documenten je nodig hebt en welke aanpak de meeste kans geeft op een positieve uitkomst. Ons advies is gratis en vrijblijvend.</p>
        <span class="zowerkhet-step-badge-licht">&#10003; Persoonlijk advies</span>
      </div>
      <div class="zowerkhet-step-licht">
        <span class="zowerkhet-step-num-licht">Stap 03</span>
        <div class="zowerkhet-step-icon-licht">&#9989;</div>
        <h3>Vergoeding of vrijblijvend alternatief</h3>
        <p>Bij toekenning betaalt de fabrikant de reparatie of vervanging, geheel of gedeeltelijk. Gaat de verkoper of het merk niet mee met de coulance? Dan kun je altijd alsnog terecht voor vrijblijvend reparatieadvies.</p>
        <span class="zowerkhet-step-badge-licht">&#10003; Altijd een vervolgstap</span>
      </div>
    </div>

  </div>
</div>

<style>
/* ── Hero only: donker blok ── */
.page-header-hero-only {
  background: var(--ink, #0d1117);
  padding: 5rem 2.5rem 4rem;
  position: relative;
  overflow: hidden;
}
.page-header-hero-only::before {
  content: '';
  position: absolute;
  top: -100px; right: -100px;
  width: 400px; height: 400px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(40,120,100,.2) 0%, transparent 70%);
  pointer-events: none;
}
.page-header-stappen-inner {
  max-width: 1280px;
  margin: 0 auto;
  position: relative;
}
.page-header-hero-only h1 {
  font-size: clamp(2rem, 3.5vw, 3rem);
  font-weight: 800;
  color: white;
  letter-spacing: -.03em;
  margin-bottom: .75rem;
}
.page-header-hero-only .hero-lead {
  font-size: 1rem;
  color: rgba(255,255,255,.55);
  max-width: 560px;
  margin-bottom: 2.5rem;
}
.hero-badge {
  display: inline-flex;
  align-items: center;
  gap: .45rem;
  background: rgba(40,120,100,.15);
  border: 1px solid rgba(40,120,100,.3);
  border-radius: 999px;
  padding: .3rem 1rem;
  font-size: .75rem;
  font-weight: 700;
  color: #4ecb9e;
  margin-bottom: 1.1rem;
  letter-spacing: .04em;
}

/* ── Stappen sectie licht ── */
.stappen-sectie-licht {
  background: #f8fafc;
  padding: 4rem 2.5rem;
}
.stappen-sectie-inner {
  max-width: 1280px;
  margin: 0 auto;
}
.stappen-titel-licht {
  font-size: clamp(1.5rem, 2.2vw, 2rem);
  font-weight: 800;
  color: #1a2332;
  letter-spacing: -.025em;
  margin-bottom: .5rem;
  text-align: center;
}
.stappen-lead-licht {
  font-size: 1rem;
  color: #64748b;
  max-width: 56ch;
  margin: 0 auto 2.5rem;
  text-align: center;
  line-height: 1.75;
}
.zowerkhet-steps-licht {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1.5rem;
}
.zowerkhet-step-licht {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 2rem 1.75rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  transition: border-color .2s ease, box-shadow .2s ease;
}
.zowerkhet-step-licht:hover {
  border-color: #287864;
  box-shadow: 0 4px 16px rgba(40,120,100,.1);
}
.zowerkhet-step-num-licht {
  font-size: .7rem;
  font-weight: 800;
  letter-spacing: .12em;
  color: #287864;
  text-transform: uppercase;
}
.zowerkhet-step-icon-licht { font-size: 1.75rem; line-height: 1; }
.zowerkhet-step-licht h3 {
  font-size: 1.05rem;
  font-weight: 800;
  color: #1a2332;
  letter-spacing: -.02em;
  margin: 0;
}
.zowerkhet-step-licht p {
  font-size: .875rem;
  color: #475569;
  line-height: 1.7;
  margin: 0;
  max-width: 36ch;
}
.zowerkhet-step-badge-licht {
  display: inline-flex;
  align-items: center;
  gap: .35rem;
  background: rgba(40,120,100,.08);
  border: 1px solid rgba(40,120,100,.25);
  border-radius: 999px;
  padding: .25rem .75rem;
  font-size: .72rem;
  font-weight: 700;
  color: #287864;
  margin-top: auto;
  width: fit-content;
}
@media (max-width: 768px) {
  .page-header-hero-only { padding: 4rem 1.25rem 3rem; }
  .stappen-sectie-licht { padding: 3rem 1.25rem; }
  .zowerkhet-steps-licht { grid-template-columns: 1fr; }
  .zowerkhet-step-licht { padding: 1.5rem 1.25rem; }
}
</style>

<!-- WIT TUSSENBLOK: wat is coulance precies -->
<div style="background:#fff; border-top:1px solid #eee; border-bottom:1px solid #eee;">
  <div class="section" style="padding-top:4rem;padding-bottom:4rem;">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:3rem;align-items:start;">
      <div>
        <h2 class="section-title">Wat is een coulanceregeling?</h2>
        <p style="color:#555;line-height:1.8;margin-bottom:1.25rem;">
          Een coulanceregeling is een vrijwillige tegemoetkoming van een fabrikant of winkel als je televisie buiten de garantietermijn kapot gaat, terwijl het defect mogelijk door een fabricage- of ontwerpfout is ontstaan.
        </p>
        <p style="color:#555;line-height:1.8;margin-bottom:1.25rem;">
          De wettelijke garantie vervalt in Nederland na twee jaar bij de verkoper. Fabrikanten kunnen daarna op eigen initiatief nog steeds besluiten je tegemoet te komen. Je hebt hier geen wettelijk recht op, maar met de juiste onderbouwing is de kans op een positieve uitkomst aanzienlijk.
        </p>
        <p style="color:#555;line-height:1.8;">
          Garantie en coulance worden bij Reparatieplatform.nl als advies getoond. Gaat een verkoper of merk niet mee met de coulance? Dan kun je altijd alsnog terecht voor vrijblijvend reparatieadvies. Wij helpen je verder, welke route het ook wordt.
        </p>
      </div>
      <div>
        <div class="coulance-info-kaart">
          <h3>&#128204; Wanneer kun je coulance aanvragen?</h3>
          <ul class="coulance-checklist">
            <li><span class="ci-check">&#10003;</span> Televisie is 2 tot 6 jaar oud</li>
            <li><span class="ci-check">&#10003;</span> Defect lijkt op een fabrieks- of ontwerpfout</li>
            <li><span class="ci-check">&#10003;</span> Televisie is gekocht bij een erkende winkel</li>
            <li><span class="ci-check">&#10003;</span> Je hebt het aankoopbewijs (of kunt het achterhalen)</li>
            <li><span class="ci-check ci-neut">&#8722;</span> Fysieke schade of valschade: niet in aanmerking</li>
            <li><span class="ci-check ci-neut">&#8722;</span> Buiten Nederland gekocht: kans lager, maar niet nul</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
.coulance-info-kaart {
  background: #f7f9f8;
  border: 1px solid #d4e4de;
  border-radius: 14px;
  padding: 2rem 1.75rem;
}
.coulance-info-kaart h3 {
  font-size: 1rem;
  font-weight: 800;
  color: #1a2e28;
  margin-bottom: 1.25rem;
}
.coulance-checklist {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: .65rem;
}
.coulance-checklist li {
  display: flex;
  align-items: flex-start;
  gap: .6rem;
  font-size: .9rem;
  color: #444;
  line-height: 1.5;
}
.ci-check {
  color: #287864;
  font-weight: 700;
  flex-shrink: 0;
  margin-top: .1rem;
}
.ci-check.ci-neut { color: #999; }
@media (max-width: 768px) {
  .section > div[style*="grid-template-columns:1fr 1fr"] {
    grid-template-columns: 1fr !important;
  }
}
</style>

<!-- Merken overzicht -->
<div class="section-light">
  <div class="section" style="padding-top:4rem;padding-bottom:4rem;">
    <h2 class="section-title">Coulance per merk</h2>
    <p class="section-lead">De bereidheid verschilt sterk per fabrikant. Onderstaand overzicht is indicatief op basis van onze ervaringen en is geen garantie op uitkomst.</p>

    <div class="merken-coulance-grid">

      <div class="merk-coulance-kaart kans-hoog">
        <div class="merk-coulance-header">
          <span class="merk-naam">Samsung</span>
          <span class="kans-badge kans-badge-hoog">Hoge kans</span>
        </div>
        <p>Samsung heeft een actief coulancebeleid voor QLED- en OLED-modellen met bekende paneelproblemen. Aanvragen verlopen via de Samsung klantenservice met serienummer en aankoopbewijs.</p>
        <div class="merk-termijn">Gangbare coulancetermijn: <strong>tot ca. 5 jaar</strong></div>
      </div>

      <div class="merk-coulance-kaart kans-hoog">
        <div class="merk-coulance-header">
          <span class="merk-naam">LG</span>
          <span class="kans-badge kans-badge-hoog">Hoge kans</span>
        </div>
        <p>LG staat bekend om een soepel coulancebeleid, met name voor OLED-modellen met burn-in of backlight-problemen. Directe escalatie naar de fabrikant werkt vaak beter dan via de winkel.</p>
        <div class="merk-termijn">Gangbare coulancetermijn: <strong>tot ca. 5 jaar</strong></div>
      </div>

      <div class="merk-coulance-kaart kans-gemiddeld">
        <div class="merk-coulance-header">
          <span class="merk-naam">Sony</span>
          <span class="kans-badge kans-badge-gemiddeld">Gemiddelde kans</span>
        </div>
        <p>Sony behandelt coulanceverzoeken per geval. OLED- en premium Bravia-modellen krijgen meer ruimte dan instapmodellen. Een goede documentatie met foto's en een heldere omschrijving vergroot de kans aanzienlijk.</p>
        <div class="merk-termijn">Gangbare coulancetermijn: <strong>tot ca. 4 jaar</strong></div>
      </div>

      <div class="merk-coulance-kaart kans-gemiddeld">
        <div class="merk-coulance-header">
          <span class="merk-naam">Philips</span>
          <span class="kans-badge kans-badge-gemiddeld">Gemiddelde kans</span>
        </div>
        <p>Philips (TP Vision) heeft een beperkt coulancebeleid. Het succes hangt sterk af van het model en de aard van het defect. Voor premium OLED-modellen is de kans het grootst.</p>
        <div class="merk-termijn">Gangbare coulancetermijn: <strong>tot ca. 3 à 4 jaar</strong></div>
      </div>

      <div class="merk-coulance-kaart kans-laag">
        <div class="merk-coulance-header">
          <span class="merk-naam">Hisense / TCL</span>
          <span class="kans-badge kans-badge-laag">Lage kans</span>
        </div>
        <p>Budgetfabrikanten hanteren over het algemeen een strikt garantiebeleid zonder veel ruimte buiten de wettelijke termijn. Aanvragen zijn mogelijk, maar slagen zelden. Reparatieadvies is dan de meest praktische route.</p>
        <div class="merk-termijn">Gangbare coulancetermijn: <strong>zelden buiten garantie</strong></div>
      </div>

      <div class="merk-coulance-kaart kans-gemiddeld">
        <div class="merk-coulance-header">
          <span class="merk-naam">Panasonic</span>
          <span class="kans-badge kans-badge-gemiddeld">Gemiddelde kans</span>
        </div>
        <p>Panasonic behandelt coulanceverzoeken positief voor OLED-modellen met bekende paneel- of backlight-problemen. Aanvragen verlopen via de Nederlandse klantenservice.</p>
        <div class="merk-termijn">Gangbare coulancetermijn: <strong>tot ca. 4 jaar</strong></div>
      </div>

    </div>

    <p style="margin-top:2rem;font-size:.83rem;color:#888;text-align:center;">
      &#9888; Dit overzicht is indicatief. Het coulancebeleid kan per model, aankoopdatum en situatie afwijken. Vraag altijd persoonlijk advies aan voor een nauwkeurige inschatting van jouw specifieke situatie.
    </p>
  </div>
</div>

<style>
.merken-coulance-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1.5rem;
}
.merk-coulance-kaart {
  background: #fff;
  border-radius: 12px;
  padding: 1.75rem;
  display: flex;
  flex-direction: column;
  gap: .85rem;
  border-left: 4px solid #ccc;
  box-shadow: 0 2px 8px rgba(0,0,0,.05);
}
.merk-coulance-kaart.kans-hoog      { border-left-color: #287864; }
.merk-coulance-kaart.kans-gemiddeld { border-left-color: #d19900; }
.merk-coulance-kaart.kans-laag      { border-left-color: #c0392b; }
.merk-coulance-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: .5rem;
}
.merk-naam { font-weight: 800; font-size: 1.05rem; color: #1a1a1a; }
.kans-badge {
  font-size: .72rem;
  font-weight: 700;
  border-radius: 999px;
  padding: .2rem .7rem;
}
.kans-badge-hoog      { background: #d1fae5; color: #065f46; }
.kans-badge-gemiddeld { background: #fef3c7; color: #92400e; }
.kans-badge-laag      { background: #fee2e2; color: #991b1b; }
.merk-coulance-kaart p {
  font-size: .875rem;
  color: #555;
  line-height: 1.7;
  margin: 0;
}
.merk-termijn {
  font-size: .8rem;
  color: #777;
  margin-top: auto;
}
</style>

<!-- Veelgestelde vragen -->
<div style="background:white;padding:5rem 0;border-top:1px solid #eee;">
  <div class="section" style="padding-top:0;padding-bottom:0;">
    <h2 class="section-title">Veelgestelde vragen over coulance</h2>
    <p class="section-lead">Antwoorden op de meest gestelde vragen over de coulanceregeling bij televisies.</p>
    <div class="faq-lijst-fancy">
      <div class="faq-fancy-item">
        <button class="faq-fancy-q faq-q">
          <span class="faq-fancy-icon">&#128204;</span>
          <span>Wat is het verschil tussen garantie en coulance?</span>
        </button>
        <div class="faq-fancy-a faq-a">
          <p>Garantie is een wettelijk recht: tot twee jaar na aankoop kun je bij de verkoper terecht als het product niet aan de koopovereenkomst voldoet. Coulance is vrijwillig: de fabrikant of winkel besluit zelf of hij je tegemoetkomt, zonder daartoe verplicht te zijn. Aan coulance kunnen dan ook geen rechten worden ontleend.</p>
        </div>
      </div>
      <div class="faq-fancy-item">
        <button class="faq-fancy-q faq-q">
          <span class="faq-fancy-icon">&#128176;</span>
          <span>Kost een coulanceaanvraag geld?</span>
        </button>
        <div class="faq-fancy-a faq-a">
          <p>Het advies van Reparatieplatform.nl is volledig gratis en vrijblijvend. De coulanceaanvraag zelf dien je rechtstreeks in bij de fabrikant of winkel en dat is ook kosteloos. Als de fabrikant een technicus stuurt voor diagnose, kunnen er voorrijkosten in rekening worden gebracht. Dit verschilt per merk.</p>
        </div>
      </div>
      <div class="faq-fancy-item">
        <button class="faq-fancy-q faq-q">
          <span class="faq-fancy-icon">&#128336;</span>
          <span>Hoe lang duurt een coulanceprocedure?</span>
        </button>
        <div class="faq-fancy-a faq-a">
          <p>Een eerste reactie van de fabrikant volgt doorgaans binnen 5 tot 10 werkdagen. Als er een technicus langs moet komen voor diagnose, kan het totale traject 2 tot 4 weken duren. Goed gedocumenteerde aanvragen worden over het algemeen sneller afgehandeld.</p>
        </div>
      </div>
      <div class="faq-fancy-item">
        <button class="faq-fancy-q faq-q">
          <span class="faq-fancy-icon">&#128196;</span>
          <span>Welke documenten heb ik nodig?</span>
        </button>
        <div class="faq-fancy-a faq-a">
          <p>In de meeste gevallen zijn het aankoopbewijs (kassabon, factuur of bankafschrift), het serienummer van de televisie en een duidelijke omschrijving van het defect met foto's voldoende. In ons persoonlijk advies leggen wij precies uit welke documentatie voor jouw situatie het meest relevant is.</p>
        </div>
      </div>
      <div class="faq-fancy-item">
        <button class="faq-fancy-q faq-q">
          <span class="faq-fancy-icon">&#9888;&#65039;</span>
          <span>Wat als de fabrikant of verkoper de coulanceaanvraag afwijst?</span>
        </button>
        <div class="faq-fancy-a faq-a">
          <p>Gaat een verkoper of merk niet mee met de coulance? Dan kun je altijd alsnog terecht voor vrijblijvend reparatieadvies. Wij adviseren je over de beste vervolgstap: reparatie aan huis door een gecertificeerde monteur, of een taxatierapport als er sprake is van verzekeringsdekking. Er is altijd een route.</p>
        </div>
      </div>
      <div class="faq-fancy-item">
        <button class="faq-fancy-q faq-q">
          <span class="faq-fancy-icon">&#127968;</span>
          <span>Kan ik coulance aanvragen als ik de televisie tweedehands heb gekocht?</span>
        </button>
        <div class="faq-fancy-a faq-a">
          <p>Coulance is in principe gebonden aan de originele koper, omdat het de relatie tussen fabrikant en eerste afnemer betreft. Bij een tweedehands aankoop is de kans aanzienlijk lager, maar niet nul, zeker bij bekende seriefouten. Vraag ons gratis advies aan voor een eerlijke inschatting van jouw situatie.</p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- CTA -->
<div class="duurzaam-wrap">
  <div class="duurzaam-inner" style="grid-template-columns:1fr; text-align:center; gap:2rem;">
    <div>
      <h2 class="section-title">Weet je niet of je in aanmerking komt?</h2>
      <p class="section-lead" style="max-width:500px;margin:0 auto 2rem;">
        Vraag gratis advies aan. Wij controleren je situatie en geven je een eerlijke inschatting van de kans op coulance, zonder verplichtingen. Lukt coulance niet? Dan helpen wij je alsnog verder met reparatieadvies.
      </p>
      <a href="<?= BASE_URL ?>/advies.php" class="btn-primary" style="margin:0 auto;">
        Gratis coulancecheck aanvragen
        <span class="btn-primary-arrow">&rarr;</span>
      </a>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>