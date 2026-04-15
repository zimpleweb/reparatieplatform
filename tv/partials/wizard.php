<div class="model-card wizard-card" id="wizardCard">

  <div class="wizard-card-header">
    <div class="wizard-card-icon">&#128269;</div>
    <div>
      <div class="model-card-title" style="border:none;padding:0;margin:0;">
        Wat zijn de mogelijkheden voor jouw <?= h($tv['modelnummer']) ?>?
      </div>
      <p class="model-card-intro" style="margin:0;">
        Beantwoord drie korte vragen en ontdek direct of jouw televisie in aanmerking
        komt voor garantie, coulance, reparatie of taxatie.
      </p>
    </div>
  </div>

  <!-- Stap indicators -->
  <div class="wizard-steps">
    <div class="wizard-step active" data-step="1">
      <span class="wizard-step-num">1</span>
      <span class="wizard-step-label">Aankoopdatum</span>
    </div>
    <div class="wizard-step-line"></div>
    <div class="wizard-step" data-step="2">
      <span class="wizard-step-num">2</span>
      <span class="wizard-step-label">Aanschafprijs</span>
    </div>
    <div class="wizard-step-line"></div>
    <div class="wizard-step" data-step="3">
      <span class="wizard-step-num">3</span>
      <span class="wizard-step-label">Type probleem</span>
    </div>
  </div>

  <!-- Stap 1 -->
  <div class="wizard-pane active" id="wizStep1">
    <p class="wizard-label">Wanneer heb je de televisie gekocht?</p>
    <div class="wizard-date-row">
      <div class="wizard-field-wrap">
        <label class="wizard-sublabel" for="wizMaand">Maand</label>
        <select class="wizard-select" id="wizMaand">
          <option value="">— maand —</option>
          <?php
          $maanden = ['Januari','Februari','Maart','April','Mei','Juni',
                      'Juli','Augustus','September','Oktober','November','December'];
          foreach ($maanden as $i => $naam) {
              printf('<option value="%d">%s</option>', $i + 1, $naam);
          }
          ?>
        </select>
      </div>
      <div class="wizard-field-wrap">
        <label class="wizard-sublabel" for="wizJaar">Jaar</label>
        <select class="wizard-select" id="wizJaar">
          <option value="">— jaar —</option>
          <?php
          $nu = (int)date('Y');
          for ($y = $nu; $y >= $nu - 12; $y--) {
              echo "<option value=\"$y\">$y</option>\n";
          }
          ?>
        </select>
      </div>
    </div>
    <div class="wizard-error" id="wizErr1"></div>
    <div class="wizard-nav wizard-nav-right">
      <button class="wizard-next" onclick="wizardNext(1)">Volgende &rarr;</button>
    </div>
  </div>

  <!-- Stap 2 -->
  <div class="wizard-pane" id="wizStep2">
    <p class="wizard-label">Voor hoeveel heb je de televisie gekocht?</p>
    <p class="wizard-hint">Weet je het niet precies? Geef een schatting.</p>
    <div class="wizard-price-wrap">
      <span class="wizard-price-prefix">&euro;</span>
      <input type="number" class="wizard-input" id="wizPrijs"
             placeholder="bijv. 499" min="0" max="9999" step="1" />
    </div>
    <div class="wizard-error" id="wizErr2"></div>
    <div class="wizard-nav">
      <button class="wizard-back" onclick="wizardBack(2)">&larr; Terug</button>
      <button class="wizard-next" onclick="wizardNext(2)">Volgende &rarr;</button>
    </div>
  </div>

  <!-- Stap 3 -->
  <div class="wizard-pane" id="wizStep3">
    <p class="wizard-label">Wat is er aan de hand?</p>
    <div class="wizard-keuzes">
      <button class="wizard-keuze" data-value="defect" onclick="wizardKeuze(this)">
        <span class="wizard-keuze-icon">&#128683;</span>
        <div class="wizard-keuze-tekst">
          <span class="wizard-keuze-label">Defect of kapot</span>
          <span class="wizard-keuze-sub">Geen beeld, strepen, TV doet het niet meer</span>
        </div>
        <span class="wizard-keuze-arrow">&rarr;</span>
      </button>
      <button class="wizard-keuze" data-value="schade" onclick="wizardKeuze(this)">
        <span class="wizard-keuze-icon">&#128657;</span>
        <div class="wizard-keuze-tekst">
          <span class="wizard-keuze-label">Schade door val of stoot</span>
          <span class="wizard-keuze-sub">Gebarsten scherm, deuken, fysieke beschadiging</span>
        </div>
        <span class="wizard-keuze-arrow">&rarr;</span>
      </button>
    </div>
    <div class="wizard-error" id="wizErr3"></div>
    <div class="wizard-nav">
      <button class="wizard-back" onclick="wizardBack(3)">&larr; Terug</button>
    </div>
  </div>

  <!-- Resultaat -->
  <div class="wizard-pane" id="wizResult"></div>

</div>