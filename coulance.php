<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle       = 'Coulanceregeling televisie – Buiten garantie toch vergoed? | Reparatieplatform.nl';
$pageDescription = 'Garantie verlopen maar televisie al snel kapot? Ontdek hoe de coulanceregeling werkt, welke fabrikanten meedoen en hoe u een aanvraag indient.';
$canonicalUrl    = '/coulance.php';

include __DIR__ . '/includes/header.php';
?>

<!-- HERO + INTRO: gecombineerd donker blok -->
<div class="page-header-stappen">
  <div class="page-header-stappen-inner">

    <div class="breadcrumb">
      <a href="<?= BASE_URL ?>/">Home</a><span class="sep">/</span>
      <span style="color:rgba(255,255,255,.4)">Coulanceregeling</span>
    </div>

    <h1>Coulanceregeling<br>bij uw televisie</h1>
    <p class="hero-lead">Garantie verlopen maar televisie al snel stuk? Veel fabrikanten vergoeden reparatie of vervanging buiten de garantietermijn — als u weet hoe u het aanvraagt.</p>

    <div class="hero-badge">&#129309; Kans op vergoeding buiten garantie</div>

    <h2 class="stappen-titel">Hoe werkt de coulanceregeling?</h2>
    <p class="stappen-lead">Een coulanceregeling is geen wettelijk recht, maar een vrijwillige tegemoetkoming van de fabrikant of winkel. Succes hangt af van merk, leeftijd en uw aanpak.</p>

    <div class="zowerkhet-steps">
      <div class="zowerkhet-step">
        <span class="zowerkhet-step-num">Stap 01</span>
        <div class="zowerkhet-step-icon">&#128269;</div>
        <h3>Controleer uw kans</h3>
        <p>Vraag gratis ons advies aan. Wij toetsen merk, aanschafjaar en klachttype aan de actuele coulanceregels van de fabrikant en schatten uw kans op vergoeding.</p>
        <span class="zowerkhet-step-badge">&#10003; Gratis check</span>
      </div>
      <div class="zowerkhet-step">
        <span class="zowerkhet-step-num">Stap 02</span>
        <div class="zowerkhet-step-icon">&#128222;</div>
        <h3>Fabrikant benaderen</h3>
        <p>Wij adviseren u hoe u de klantenservice het beste kunt benaderen, welke documentatie u nodig heeft en welke bewoordingen de meeste kans geven op een positieve uitkomst.</p>
        <span class="zowerkhet-step-badge">&#10003; Persoonlijk advies</span>
      </div>
      <div class="zowerkhet-step">
        <span class="zowerkhet-step-num">Stap 03</span>
        <div class="zowerkhet-step-icon">&#9989;</div>
        <h3>Vergoeding of alternatief</h3>
        <p>Bij toekenning betaalt de fabrikant reparatie of vervanging (geheel of gedeeltelijk). Wijst de fabrikant af? Dan adviseren wij over reparatie of taxatie als vervolgstap.</p>
        <span class="zowerkhet-step-badge">&#10003; Altijd een vervolgstap</span>
      </div>
    </div>

  </div>
</div>

<style>
.page-header-stappen {
  background: var(--ink, #0d1117);
  padding: 5rem 2.5rem 5rem;
  position: relative;
  overflow: hidden;
}
.page-header-stappen::before {
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
.page-header-stappen h1 {
  font-size: clamp(2rem, 3.5vw, 3rem);
  font-weight: 800;
  color: white;
  letter-spacing: -.03em;
  margin-bottom: .75rem;
}
.page-header-stappen .hero-lead {
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
.stappen-titel {
  font-size: clamp(1.5rem, 2.2vw, 2rem);
  font-weight: 800;
  color: #fff;
  letter-spacing: -.025em;
  margin-bottom: .5rem;
  text-align: center;
}
.stappen-lead {
  font-size: 1rem;
  color: rgba(255,255,255,.55);
  max-width: 52ch;
  margin: 0 auto 2.5rem;
  text-align: center;
  line-height: 1.75;
}
.zowerkhet-steps {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1.5rem;
}
.zowerkhet-step {
  background: #161b22;
  border: 1px solid rgba(255,255,255,.08);
  border-radius: 14px;
  padding: 2rem 1.75rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  transition: border-color .2s ease, transform .2s ease;
  position: relative;
  overflow: hidden;
}
.zowerkhet-step::before {
  content: '';
  position: absolute;
  inset: 0;
  background: radial-gradient(ellipse at top left, rgba(40,120,100,.12) 0%, transparent 65%);
  pointer-events: none;
}
.zowerkhet-step:hover {
  border-color: rgba(40,120,100,.5);
  transform: translateY(-3px);
}
.zowerkhet-step-num {
  font-size: .7rem;
  font-weight: 800;
  letter-spacing: .12em;
  color: var(--accent, #287864);
  text-transform: uppercase;
}
.zowerkhet-step-icon { font-size: 1.75rem; line-height: 1; }
.zowerkhet-step h3 {
  font-size: 1.05rem;
  font-weight: 800;
  color: #fff;
  letter-spacing: -.02em;
  margin: 0;
}
.zowerkhet-step p {
  font-size: .875rem;
  color: rgba(255,255,255,.5);
  line-height: 1.7;
  margin: 0;
  max-width: 36ch;
}
.zowerkhet-step-badge {
  display: inline-flex;
  align-items: center;
  gap: .35rem;
  background: rgba(40,120,100,.15);
  border: 1px solid rgba(40,120,100,.35);
  border-radius: 999px;
  padding: .25rem .75rem;
  font-size: .72rem;
  font-weight: 700;
  color: #4ecb9e;
  margin-top: auto;
  width: fit-content;
}
@media (max-width: 768px) {
  .page-header-stappen { padding: 4rem 1.25rem 3.5rem; }
  .zowerkhet-steps { grid-template-columns: 1fr; }
  .zowerkhet-step  { padding: 1.5rem 1.25rem; }
}
</style>

<!-- WIT TUSSENBLOK: wat is coulance precies -->
<div style="background:#fff; border-top:1px solid #eee; border-bottom:1px solid #eee;">
  <div class="section" style="padding-top:4rem;padding-bottom:4rem;">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:3rem;align-items:start;">
      <div>
        <h2 class="section-title">Wat is een coulanceregeling?</h2>
        <p style="color:#555;line-height:1.8;margin-bottom:1.25rem;">
          Een coulanceregeling is een vrijwillige tegemoetkoming van een fabrikant of winkel als uw televisie buiten de garantietermijn kapot gaat, maar het defect mogelijk te wijten is aan een fabricagefout of ontwerpfout.
        </p>
        <p style="color:#555;line-height:1.8;margin-bottom:1.25rem;">
          De wettelijke garantie (conformiteitsrecht) vervalt in Nederland na twee jaar bij de verkoper. Fabrikanten kunnen daarbovenop een eigen garantie geven, maar ook <em>daarna</em> kunnen zij — volledig naar eigen goeddunken — besluiten om toch tegemoet te komen.
        </p>
        <p style="color:#555;line-height:1.8;">
          U heeft hier geen wettelijk recht op, maar met de juiste onderbouwing en aanpak is de kans op een positieve uitkomst aanzienlijk. Reparatieplatform.nl helpt u die kans zo groot mogelijk te maken.
        </p>
      </div>
      <div>
        <div class="coulance-info-kaart">
          <h3>&#128204; Wanneer kunt u coulance aanvragen?</h3>
          <ul class="coulance-checklist">
            <li><span class="ci-check">&#10003;</span> Televisie is 2–6 jaar oud</li>
            <li><span class="ci-check">&#10003;</span> Defect lijkt op een fabrieks- of ontwerpfout</li>
            <li><span class="ci-check">&#10003;</span> Televisie is gekocht bij een erkende winkel</li>
            <li><span class="ci-check">&#10003;</span> U heeft het aankoopbewijs (of kunt het achterhalen)</li>
            <li><span class="ci-check ci-neut">&#8722;</span> Fysieke schade of valschade: <em>niet</em> in aanmerking</li>
            <li><span class="ci-check ci-neut">&#8722;</span> Gekocht in het buitenland: kans lager, niet nul</li>
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
    <p class="section-lead">De bereidheid verschilt sterk per fabrikant. Onderstaand overzicht is indicatief op basis van onze ervaringen.</p>

    <div class="merken-coulance-grid">

      <div class="merk-coulance-kaart kans-hoog">
        <div class="merk-coulance-header">
          <span class="merk-naam">Samsung</span>
          <span class="kans-badge kans-badge-hoog">Hoge kans</span>
        </div>
        <p>Samsung heeft een actief coulancebeleid voor QLED- en OLED-modellen met bekende paneelproblemen. Aanvragen via de Samsung klantenservice met serienummer en aankoopbewijs.</p>
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
        <p>Sony behandelt coulanceverzoeken per geval. OLED- en premium Bravia-modellen krijgen meer ruimte dan instapmodellen. Documenteer het defect goed met foto's en een duidelijke omschrijving.</p>
        <div class="merk-termijn">Gangbare coulancetermijn: <strong>tot ca. 4 jaar</strong></div>
      </div>

      <div class="merk-coulance-kaart kans-gemiddeld">
        <div class="merk-coulance-header">
          <span class="merk-naam">Philips</span>
          <span class="kans-badge kans-badge-gemiddeld">Gemiddelde kans</span>
        </div>
        <p>Philips (TP Vision) heeft een beperkt coulancebeleid. Succes hangt sterk af van het model en de aard van het defect. Voor premium OLED-modellen is de kans het grootst.</p>
        <div class="merk-termijn">Gangbare coulancetermijn: <strong>tot ca. 3–4 jaar</strong></div>
      </div>

      <div class="merk-coulance-kaart kans-laag">
        <div class="merk-coulance-header">
          <span class="merk-naam">Hisense / TCL</span>
          <span class="kans-badge kans-badge-laag">Lage kans</span>
        </div>
        <p>Budget-fabrikanten hanteren over het algemeen een strikt garantiebeleid zonder ruimte voor coulance buiten de wettelijke termijn. Aanvragen zijn mogelijk, maar slagen zelden.</p>
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
      &#9888; Bovenstaande informatie is indicatief. Coulancebeleid kan per model, aankoopdatum en situatie afwijken. Vraag altijd persoonlijk advies aan voor een nauwkeurige inschatting.
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
.merk-coulance-kaart.kans-hoog     { border-left-color: #287864; }
.merk-coulance-kaart.kans-gemiddeld { border-left-color: #d19900; }
.merk-coulance-kaart.kans-laag     { border-left-color: #c0392b; }
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
          <p>Garantie is een wettelijk recht (conformiteitsrecht): tot twee jaar na aankoop kunt u bij de verkoper terecht als het product niet aan de koopovereenkomst voldoet. Coulance is vrijwillig: de fabrikant of winkel besluit zelf, zonder wettelijke verplichting, om u tegemoet te komen. Aan coulance kunnen dan ook geen rechten worden ontleend.</p>
        </div>
      </div>
      <div class="faq-fancy-item">
        <button class="faq-fancy-q faq-q">
          <span class="faq-fancy-icon">&#128176;</span>
          <span>Kost een coulanceaanvraag geld?</span>
        </button>
        <div class="faq-fancy-a faq-a">
          <p>Het advies van Reparatieplatform.nl is volledig gratis en vrijblijvend. De coulanceaanvraag zelf dient u rechtstreeks in bij de fabrikant of winkel — dat is ook kosteloos. Als de fabrikant een technicus stuurt voor diagnose, kan hij eventueel voorrijkosten in rekening brengen; dat verschilt per merk.</p>
        </div>
      </div>
      <div class="faq-fancy-item">
        <button class="faq-fancy-q faq-q">
          <span class="faq-fancy-icon">&#128336;</span>
          <span>Hoe lang duurt een coulanceprocedure?</span>
        </button>
        <div class="faq-fancy-a faq-a">
          <p>Een eerste reactie van de fabrikant komt doorgaans binnen 5–10 werkdagen. Als een technicus langs moet voor diagnose, kan het totale traject 2–4 weken duren. Onze ervaring is dat goed gedocumenteerde aanvragen sneller worden afgehandeld.</p>
        </div>
      </div>
      <div class="faq-fancy-item">
        <button class="faq-fancy-q faq-q">
          <span class="faq-fancy-icon">&#128196;</span>
          <span>Welke documenten heb ik nodig?</span>
        </button>
        <div class="faq-fancy-a faq-a">
          <p>In de meeste gevallen zijn het aankoopbewijs (kassabon, factuur of bankafschrift), het serienummer van de televisie en een duidelijke omschrijving van het defect (eventueel met foto's) voldoende. Wij adviseren u in ons persoonlijk advies precies welke documentatie voor uw situatie relevant is.</p>
        </div>
      </div>
      <div class="faq-fancy-item">
        <button class="faq-fancy-q faq-q">
          <span class="faq-fancy-icon">&#9888;&#65039;</span>
          <span>Wat als de fabrikant de coulanceaanvraag afwijst?</span>
        </button>
        <div class="faq-fancy-a faq-a">
          <p>Bij afwijzing adviseren wij over de beste vervolgstap: reparatie aan huis door een gecertificeerde monteur, of een taxatierapport als er sprake is van verzekeringsdekking. Er is altijd een route — ook als coulance niet lukt.</p>
        </div>
      </div>
      <div class="faq-fancy-item">
        <button class="faq-fancy-q faq-q">
          <span class="faq-fancy-icon">&#127968;</span>
          <span>Kan ik coulance aanvragen als ik de televisie tweedehands heb gekocht?</span>
        </button>
        <div class="faq-fancy-a faq-a">
          <p>Coulance is in principe gebonden aan de originele koper, omdat het de relatie tussen fabrikant en eerste afnemer betreft. Bij een tweedehands aankoop is de kans aanzienlijk lager, maar niet nul — zeker als het een bekende serie-fout betreft. Vraag ons advies aan voor een eerlijke inschatting van uw specifieke situatie.</p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- CTA -->
<div class="duurzaam-wrap">
  <div class="duurzaam-inner" style="grid-template-columns:1fr; text-align:center; gap:2rem;">
    <div>
      <h2 class="section-title">Weet u niet of u in aanmerking komt?</h2>
      <p class="section-lead" style="max-width:500px;margin:0 auto 2rem;">
        Vraag gratis advies aan. Wij controleren uw situatie en schatten de kans op coulance — eerlijk en zonder verplichtingen.
      </p>
      <a href="<?= BASE_URL ?>/advies.php" class="btn-primary" style="margin:0 auto;">
        Gratis coulancecheck aanvragen
        <span class="btn-primary-arrow">&rarr;</span>
      </a>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>