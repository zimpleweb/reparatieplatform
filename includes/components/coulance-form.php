<?php
// Component: coulance-form.php
// Vereist: $inzending (array), lockedField() beschikbaar in scope
$coulanceShops = [
    'MediaMarkt', 'Coolblue', 'BCC', 'Expert', 'Electro World',
    'Bol.com', 'Amazon.nl', 'Wehkamp', 'HIFI Club', 'Samsung Store',
    'LG Store', 'Makro', 'Dixons', 'Mediaplanet', 'Marktplaats',
];
$huidigWinkel = $inzending['winkel_naam'] ?? '';
?>
<div class="portal-action-card card-warning">
  <div class="portal-action-header">
    <div class="portal-action-icon icon-warning">🤝</div>
    <div>
      <h3>Coulancetraject</h3>
      <p>Vul de gegevens in en volg de stappen om uw coulanceverzoek te verwerken.</p>
    </div>
  </div>

  <form class="portal-form" method="POST" action="<?= BASE_URL ?>/api/aanvulling.php"
        id="coulance-form">
    <input type="hidden" name="csrf_token"  value="<?= csrf() ?>" />
    <input type="hidden" name="aanvraag_id" value="<?= (int)$inzending['id'] ?>" />
    <input type="hidden" name="casenummer"  value="<?= htmlspecialchars($inzending['casenummer'], ENT_QUOTES, 'UTF-8') ?>" />
    <input type="hidden" name="type"        value="coulance" />
    <input type="hidden" name="resultaat"   id="coulance-resultaat" value="" />

    <!-- Stap 1: Aankoopinformatie + winkel -->
    <div id="coulance-stap-1">
      <div class="portal-form-section">Aankoopinformatie</div>
      <div class="portal-fields-row">
        <div class="portal-field">
          <label>Exacte verkoopprijs *</label>
          <input type="text" name="verkoopprijs" required placeholder="Bijv. 499,00"
                 value="<?= htmlspecialchars($inzending['verkoopprijs'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
        </div>
        <div class="portal-field">
          <label>Heeft u de aankoopbon nog?</label>
          <div class="portal-radio-group">
            <label class="portal-radio-label">
              <input type="radio" name="heeft_bon_coulance" value="ja"
                     <?= ($inzending['heeft_bon'] ?? '') == 1 ? 'checked' : '' ?> />
              Ja
            </label>
            <label class="portal-radio-label">
              <input type="radio" name="heeft_bon_coulance" value="nee"
                     <?= ($inzending['heeft_bon'] ?? '') === 0 ? 'checked' : '' ?> />
              Nee
            </label>
          </div>
        </div>
      </div>

      <div class="portal-form-section">Winkel</div>
      <div class="portal-field">
        <label>Waar heeft u de TV gekocht? *</label>
        <select name="winkel_naam" required>
          <option value="">— Selecteer winkel —</option>
          <?php foreach ($coulanceShops as $shop): ?>
            <option value="<?= htmlspecialchars($shop, ENT_QUOTES, 'UTF-8') ?>"
                    <?= $huidigWinkel === $shop ? 'selected' : '' ?>>
              <?= htmlspecialchars($shop, ENT_QUOTES, 'UTF-8') ?>
            </option>
          <?php endforeach; ?>
          <option value="Anders"
                  <?= ($huidigWinkel && !in_array($huidigWinkel, $coulanceShops)) ? 'selected' : '' ?>>
            Anders
          </option>
        </select>
      </div>

      <button type="button" class="portal-submit-btn btn-warning" onclick="coulanceStap(2)">
        Volgende &rarr;
      </button>
    </div><!-- /#coulance-stap-1 -->

    <!-- Stap 2: Resultaat bij de winkel -->
    <div id="coulance-stap-2" style="display:none;">
      <div class="portal-form-section">Resultaat bij de winkel</div>
      <p style="font-size:.9rem;color:#374151;margin-bottom:1rem;line-height:1.65;">
        Is het met de winkel gelukt om een coulanceoplossing te bereiken?
      </p>
      <div class="portal-radio-group" style="margin-bottom:1.25rem;">
        <label class="portal-radio-label">
          <input type="radio" name="coulance_winkel_resultaat" value="1" id="winkel-ja" />
          Ja
        </label>
        <label class="portal-radio-label">
          <input type="radio" name="coulance_winkel_resultaat" value="0" id="winkel-nee" />
          Nee
        </label>
      </div>
      <div id="winkel-ja-actie" style="display:none;">
        <div class="portal-info-banner" style="background:#f0fdf4;border-left:4px solid #16a34a;color:#166534;">
          &#10003; De coulance is geslaagd. U kunt de aanvraag nu afsluiten.
        </div>
        <button type="button" class="portal-submit-btn" onclick="coulanceVerzenden('winkel_gelukt')"
                style="background:#16a34a;">
          Aanvraag afsluiten &#10003;
        </button>
      </div>
      <div id="winkel-nee-actie" style="display:none;">
        <p style="font-size:.88rem;color:#92400e;margin-bottom:.75rem;">
          U kunt het coulanceverzoek nog proberen bij de fabrikant.
        </p>
        <button type="button" class="portal-submit-btn btn-warning" onclick="coulanceStap(3)">
          Doorgaan naar fabrikant &rarr;
        </button>
      </div>
    </div><!-- /#coulance-stap-2 -->

    <!-- Stap 3: Resultaat bij de fabrikant -->
    <div id="coulance-stap-3" style="display:none;">
      <div class="portal-form-section">Resultaat bij de fabrikant</div>
      <p style="font-size:.9rem;color:#374151;margin-bottom:1rem;line-height:1.65;">
        Is het bij de fabrikant gelukt om een coulanceoplossing te bereiken?
      </p>
      <div class="portal-radio-group" style="margin-bottom:1.25rem;">
        <label class="portal-radio-label">
          <input type="radio" name="coulance_fabrikant_resultaat" value="1" id="fabrikant-ja" />
          Ja
        </label>
        <label class="portal-radio-label">
          <input type="radio" name="coulance_fabrikant_resultaat" value="0" id="fabrikant-nee" />
          Nee
        </label>
      </div>
      <div id="fabrikant-ja-actie" style="display:none;">
        <div class="portal-info-banner" style="background:#f0fdf4;border-left:4px solid #16a34a;color:#166534;">
          &#10003; De coulance is geslaagd. U kunt de aanvraag nu afsluiten.
        </div>
        <button type="button" class="portal-submit-btn" onclick="coulanceVerzenden('fabrikant_gelukt')"
                style="background:#16a34a;">
          Aanvraag afsluiten &#10003;
        </button>
      </div>
      <div id="fabrikant-nee-actie" style="display:none;">
        <div class="portal-info-banner" style="background:#fffbeb;border-left:4px solid #f59e0b;color:#92400e;">
          Helaas is coulance niet gelukt. Indien uw model repareerbaar is, kunnen wij een reparatieaanvraag voor u starten.
        </div>
        <button type="button" class="portal-submit-btn" onclick="coulanceVerzenden('niet_gelukt')">
          Reparatieaanvraag starten &rarr;
        </button>
      </div>
    </div><!-- /#coulance-stap-3 -->
  </form>
</div>

<script>
(function () {
  function coulanceStap(nr) {
    [1, 2, 3].forEach(function (n) {
      document.getElementById('coulance-stap-' + n).style.display = (n === nr) ? '' : 'none';
    });
  }

  document.getElementById('winkel-ja').addEventListener('change', function () {
    document.getElementById('winkel-ja-actie').style.display = '';
    document.getElementById('winkel-nee-actie').style.display = 'none';
  });
  document.getElementById('winkel-nee').addEventListener('change', function () {
    document.getElementById('winkel-ja-actie').style.display = 'none';
    document.getElementById('winkel-nee-actie').style.display = '';
  });
  document.getElementById('fabrikant-ja').addEventListener('change', function () {
    document.getElementById('fabrikant-ja-actie').style.display = '';
    document.getElementById('fabrikant-nee-actie').style.display = 'none';
  });
  document.getElementById('fabrikant-nee').addEventListener('change', function () {
    document.getElementById('fabrikant-ja-actie').style.display = 'none';
    document.getElementById('fabrikant-nee-actie').style.display = '';
  });

  window.coulanceStap = coulanceStap;
  window.coulanceVerzenden = function (resultaat) {
    document.getElementById('coulance-resultaat').value = resultaat;
    document.getElementById('coulance-form').submit();
  };
}());
</script>
