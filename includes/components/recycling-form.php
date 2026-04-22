<?php
// Component: recycling-form.php
// Vereist: $inzending (array), lockedField() beschikbaar in scope
?>
<div class="portal-action-card card-purple">
  <div class="portal-action-header">
    <div class="portal-action-icon icon-purple">&#9851;</div>
    <div>
      <h3>Recyclingverzoek</h3>
      <p>Geef aan of u interesse heeft in verantwoorde recycling van uw televisie.</p>
    </div>
  </div>

  <form class="portal-form" method="POST" action="<?= BASE_URL ?>/api/aanvulling.php"
        id="recycling-form" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token"  value="<?= csrf() ?>" />
    <input type="hidden" name="aanvraag_id" value="<?= (int)$inzending['id'] ?>" />
    <input type="hidden" name="casenummer"  value="<?= htmlspecialchars($inzending['casenummer'], ENT_QUOTES, 'UTF-8') ?>" />
    <input type="hidden" name="type"        value="recycling" />
    <input type="hidden" name="resultaat"   id="recycling-resultaat" value="" />

    <!-- Stap 1: Interesse? -->
    <div id="recycling-stap-1">
      <div class="portal-form-section">Verduurzaming &amp; Recycling</div>
      <p style="font-size:.9rem;color:#374151;margin-bottom:1rem;line-height:1.65;">
        Heeft u interesse in verantwoorde verduurzaming of recycling van uw televisie?
      </p>
      <div class="portal-radio-group" style="margin-bottom:1.25rem;">
        <label class="portal-radio-label">
          <input type="radio" name="recycling_interesse" value="ja" id="recycle-ja" />
          Ja, ik wil mijn televisie laten recyclen
        </label>
        <label class="portal-radio-label">
          <input type="radio" name="recycling_interesse" value="nee" id="recycle-nee" />
          Nee, ik wil mijn inzending afsluiten
        </label>
      </div>
      <div id="recycle-nee-actie" style="display:none;">
        <div class="portal-info-banner" style="background:#fffbeb;border-left:4px solid #f59e0b;color:#92400e;">
          U kunt de inzending afsluiten. Mocht u later van gedachten veranderen, neem dan contact met ons op.
        </div>
        <button type="button" class="portal-submit-btn btn-secondary"
                onclick="recyclingVerzenden('niet_geinteresseerd')">
          Inzending afsluiten
        </button>
      </div>
      <div id="recycle-ja-actie" style="display:none;">
        <button type="button" class="portal-submit-btn btn-purple" onclick="recyclingStap(2)">
          Doorgaan &rarr;
        </button>
      </div>
    </div><!-- /#recycling-stap-1 -->

    <!-- Stap 2: Contactgegevens + recyclingvragen + foto's -->
    <div id="recycling-stap-2" style="display:none;">

      <div class="portal-form-section">Contactgegevens</div>
      <div class="portal-fields-row">
        <div class="portal-field">
          <label>Naam *</label>
          <input type="text" name="naam"
                 value="<?= htmlspecialchars($inzending['naam'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
        </div>
        <div class="portal-field">
          <label>E-mail</label>
          <?= lockedField($inzending['email'], 'email') ?>
        </div>
      </div>
      <div class="portal-field">
        <label>Ophaaladres *</label>
        <input type="text" name="adres" placeholder="Straat + huisnummer"
               value="<?= htmlspecialchars($inzending['adres'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
      </div>
      <div class="portal-fields-row">
        <div class="portal-field">
          <label>Postcode *</label>
          <input type="text" name="postcode"
                 value="<?= htmlspecialchars($inzending['postcode'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
        </div>
        <div class="portal-field">
          <label>Plaats *</label>
          <input type="text" name="plaats"
                 value="<?= htmlspecialchars($inzending['plaats'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
        </div>
      </div>
      <div class="portal-field">
        <label>Telefoonnummer *</label>
        <input type="tel" name="telefoon"
               value="<?= htmlspecialchars($inzending['telefoon'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
      </div>

      <div class="portal-form-section">Televisie</div>
      <div class="portal-fields-row">
        <div class="portal-field">
          <label>Merk TV</label>
          <?= lockedField($inzending['merk']) ?>
        </div>
        <div class="portal-field">
          <label>Modelnummer</label>
          <?= lockedField($inzending['modelnummer']) ?>
        </div>
      </div>
      <div class="portal-field">
        <label>Klachtomschrijving <em class="portal-form-optional">(optioneel)</em></label>
        <textarea name="omschrijving" rows="3"><?= htmlspecialchars($inzending['omschrijving'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
      </div>

      <div class="portal-form-section">Recyclinginformatie</div>
      <div class="portal-field">
        <label>Ophaalvoorkeur</label>
        <select name="ophaalvoorkeur">
          <option value="">— Geen voorkeur —</option>
          <option value="ochtend">Ochtend (08:00&ndash;12:00)</option>
          <option value="middag">Middag (12:00&ndash;17:00)</option>
          <option value="avond">Avond (17:00&ndash;21:00)</option>
        </select>
      </div>
      <div class="portal-field">
        <label>Aanvullende informatie <em class="portal-form-optional">(optioneel)</em></label>
        <textarea name="recycling_toelichting" rows="3"
                  placeholder="Bijv. extra schade, verpakking beschikbaar, bijzondere bereikbaarheid&hellip;"></textarea>
      </div>

      <div class="portal-form-section">Foto's <em class="portal-form-optional">(optioneel)</em></div>
      <div class="portal-fields-row">
        <div class="portal-field">
          <label>Foto van het gehele toestel</label>
          <input type="file" name="foto_toestel" accept="image/*" />
        </div>
        <div class="portal-field">
          <label>Foto van het defect</label>
          <input type="file" name="foto_defect" accept="image/*" />
        </div>
      </div>
      <p class="portal-upload-hint">Maximaal 10 MB per foto. Toegestane formaten: JPG, PNG, WebP.</p>

      <button type="button" class="portal-submit-btn btn-purple"
              onclick="recyclingVerzenden('geinteresseerd')">
        Recyclingverzoek indienen &rarr;
      </button>
    </div><!-- /#recycling-stap-2 -->
  </form>
</div>

<script>
(function () {
  document.getElementById('recycle-ja').addEventListener('change', function () {
    document.getElementById('recycle-ja-actie').style.display = '';
    document.getElementById('recycle-nee-actie').style.display = 'none';
  });
  document.getElementById('recycle-nee').addEventListener('change', function () {
    document.getElementById('recycle-nee-actie').style.display = '';
    document.getElementById('recycle-ja-actie').style.display = 'none';
  });

  window.recyclingStap = function (nr) {
    [1, 2].forEach(function (n) {
      document.getElementById('recycling-stap-' + n).style.display = (n === nr) ? '' : 'none';
    });
  };
  window.recyclingVerzenden = function (resultaat) {
    document.getElementById('recycling-resultaat').value = resultaat;
    document.getElementById('recycling-form').submit();
  };
}());
</script>
