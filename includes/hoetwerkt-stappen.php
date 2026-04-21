<?php
// Component: hoetwerkt-stappen.php
?>
<div class="stappen-sectie-licht">
  <div class="stappen-sectie-inner">
    <h2 class="stappen-titel-licht">In drie stappen geholpen</h2>
    <p class="stappen-lead-licht">Geen technische kennis nodig. Vertel ons wat er speelt en wij denken met je mee.</p>

    <div class="zowerkhet-steps-licht" id="stappen">
      <div class="zowerkhet-step-licht">
        <span class="zowerkhet-step-num-licht">Stap 01</span>
        <div class="zowerkhet-step-icon-licht">&#128221;</div>
        <h3>Formulier invullen</h3>
        <p>Vul het korte adviesformulier in met merk, modelnummer en een omschrijving van het probleem. Het duurt minder dan twee minuten.</p>
        <ul class="stap-check-lijst">
          <li><span class="stap-check">&#10003;</span> Merk en modelnummer (staat achter op de tv)</li>
          <li><span class="stap-check">&#10003;</span> Korte omschrijving van het defect</li>
          <li><span class="stap-check">&#10003;</span> Je e-mailadres voor het advies</li>
        </ul>
        <span class="zowerkhet-step-badge-licht">&#10003; Gratis</span>
      </div>
      <div class="zowerkhet-step-licht">
        <span class="zowerkhet-step-num-licht">Stap 02</span>
        <div class="zowerkhet-step-icon-licht">&#128269;</div>
        <h3>Wij bekijken jouw situatie</h3>
        <p>Een specialist kijkt naar jouw aanvraag en beoordeelt de mogelijkheden: garantie, coulance, reparatie of taxatie.</p>
        <ul class="stap-check-lijst">
          <li><span class="stap-check">&#10003;</span> Garantie en coulance worden nagekeken</li>
          <li><span class="stap-check">&#10003;</span> Reparatiemogelijkheden in beeld gebracht</li>
          <li><span class="stap-check">&#10003;</span> Eerlijk advies, ook als reparatie niet loont</li>
        </ul>
        <span class="zowerkhet-step-badge-licht">&#10003; Persoonlijk advies</span>
      </div>
      <div class="zowerkhet-step-licht">
        <span class="zowerkhet-step-num-licht">Stap 03</span>
        <div class="zowerkhet-step-icon-licht">&#128233;</div>
        <h3>Advies binnen 24 uur per e-mail</h3>
        <p>Je ontvangt een helder persoonlijk advies per e-mail met duidelijke vervolgstappen voor garantie, coulance, reparatie of taxatie.</p>
        <ul class="stap-check-lijst">
          <li><span class="stap-check">&#10003;</span> Reactie binnen één werkdag</li>
          <li><span class="stap-check">&#10003;</span> Concrete vervolgstappen</li>
          <li><span class="stap-check">&#10003;</span> Volledig gratis en vrijblijvend</li>
        </ul>
        <span class="zowerkhet-step-badge-licht">&#10003; Binnen 1 werkdag</span>
      </div>
    </div>
  </div>
</div>

<!-- Vertrouwensbalk -->
<div class="vertrouwen-balk">
  <div class="section" style="padding-top:2.5rem;padding-bottom:2.5rem;">
    <div class="vertrouwen-items">
      <div class="vertrouwen-item"><span class="vertrouwen-icon">&#9989;</span><span><strong>100% gratis</strong> advies</span></div>
      <div class="vertrouwen-item"><span class="vertrouwen-icon">&#128338;</span><span>Reactie <strong>binnen 24 uur</strong></span></div>
      <div class="vertrouwen-item"><span class="vertrouwen-icon">&#128274;</span><span><strong>Vrijblijvend</strong> en zonder verplichtingen</span></div>
      <div class="vertrouwen-item"><span class="vertrouwen-icon">&#127464;&#127473;</span><span>Door heel <strong>Nederland</strong></span></div>
    </div>
  </div>
</div>

<style>
.stappen-sectie-licht { background: #f8fafc; padding: 4rem 2.5rem; }
.stappen-sectie-inner { max-width: 1280px; margin: 0 auto; }
.stappen-titel-licht { font-size: clamp(1.5rem, 2.2vw, 2rem); font-weight: 800; color: #1a2332; letter-spacing: -.025em; margin-bottom: .5rem; text-align: center; }
.stappen-lead-licht  { font-size: 1rem; color: #64748b; max-width: 48ch; margin: 0 auto 2.5rem; text-align: center; line-height: 1.75; }
.zowerkhet-steps-licht { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; }
.zowerkhet-step-licht { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 2rem 1.75rem; display: flex; flex-direction: column; gap: 1rem; transition: border-color .2s, box-shadow .2s; }
.zowerkhet-step-licht:hover { border-color: #287864; box-shadow: 0 4px 16px rgba(40,120,100,.1); }
.zowerkhet-step-num-licht  { font-size: .7rem; font-weight: 800; letter-spacing: .12em; color: #287864; text-transform: uppercase; }
.zowerkhet-step-icon-licht { font-size: 1.75rem; line-height: 1; }
.zowerkhet-step-licht h3   { font-size: 1.05rem; font-weight: 800; color: #1a2332; letter-spacing: -.02em; margin: 0; }
.zowerkhet-step-licht p    { font-size: .875rem; color: #475569; line-height: 1.7; margin: 0; max-width: 36ch; }
.stap-check-lijst { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: .35rem; }
.stap-check-lijst li { font-size: .8rem; color: #64748b; display: flex; gap: .5rem; }
.stap-check { color: #287864; font-weight: 700; }
.zowerkhet-step-badge-licht { display: inline-flex; align-items: center; gap: .35rem; background: rgba(40,120,100,.08); border: 1px solid rgba(40,120,100,.25); border-radius: 999px; padding: .25rem .75rem; font-size: .72rem; font-weight: 700; color: #287864; margin-top: auto; width: fit-content; }
.vertrouwen-balk { background: #fff; border-top: 1px solid #eee; border-bottom: 1px solid #eee; }
.vertrouwen-items { display: flex; flex-wrap: wrap; gap: 1.5rem 3rem; justify-content: center; align-items: center; }
.vertrouwen-item { display: flex; align-items: center; gap: .6rem; font-size: .92rem; color: #444; }
.vertrouwen-icon { font-size: 1.2rem; }
@media (max-width: 768px) {
  .stappen-sectie-licht { padding: 3rem 1.25rem; }
  .zowerkhet-steps-licht { grid-template-columns: 1fr; }
  .zowerkhet-step-licht { padding: 1.5rem 1.25rem; }
}
@media (max-width: 640px) {
  .vertrouwen-items { flex-direction: column; gap: 1rem; align-items: flex-start; }
}
</style>