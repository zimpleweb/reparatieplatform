<?php
// Component: taxatie-form.php
// Vereist: $inzending (array), lockedField() beschikbaar in scope
?>
<div class="portal-action-card">
  <div class="portal-action-header">
    <div class="portal-action-icon">📋</div>
    <div>
      <h3>Taxatieaanvraag</h3>
      <p>Vul uw gegevens in om de taxatieaanvraag te voltooien.</p>
    </div>
  </div>

  <div class="portal-info-banner">
    Na het invullen van het formulier sturen wij u een factuur van <strong>&euro;60 inclusief btw</strong>
    voor productonderzoek, registratie- en administratiekosten en eventuele recyclingkosten.
    Na betaling maken wij het rapport op.
  </div>

  <form class="portal-form" method="POST" action="<?= BASE_URL ?>/api/aanvulling.php" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token"  value="<?= csrf() ?>" />
    <input type="hidden" name="aanvraag_id" value="<?= (int)$inzending['id'] ?>" />
    <input type="hidden" name="casenummer"  value="<?= htmlspecialchars($inzending['casenummer'], ENT_QUOTES, 'UTF-8') ?>" />
    <input type="hidden" name="type"        value="taxatie" />

    <div class="portal-form-section">Contactgegevens</div>
    <div class="portal-field">
      <label>Voor- en achternaam *</label>
      <input type="text" name="naam" required
             value="<?= htmlspecialchars($inzending['naam'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
    </div>
    <div class="portal-field">
      <label>Adres *</label>
      <input type="text" name="adres" required placeholder="Straat + huisnummer"
             value="<?= htmlspecialchars($inzending['adres'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
    </div>
    <div class="portal-fields-row">
      <div class="portal-field">
        <label>Postcode *</label>
        <input type="text" name="postcode" required
               value="<?= htmlspecialchars($inzending['postcode'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
      </div>
      <div class="portal-field">
        <label>Plaats *</label>
        <input type="text" name="plaats" required
               value="<?= htmlspecialchars($inzending['plaats'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
      </div>
    </div>
    <div class="portal-fields-row">
      <div class="portal-field">
        <label>E-mail</label>
        <?= lockedField($inzending['email'], 'email') ?>
      </div>
      <div class="portal-field">
        <label>Telefoonnummer *</label>
        <input type="tel" name="telefoon" required
               value="<?= htmlspecialchars($inzending['telefoon'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
      </div>
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
      <label>Serienummer *</label>
      <input type="text" name="serienummer" required
             value="<?= htmlspecialchars($inzending['serienummer'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
    </div>

    <div class="portal-form-section">Schade</div>
    <div class="portal-field">
      <label>Reden schade *</label>
      <div class="portal-radio-group">
        <?php foreach ([
          'Iets tegen scherm gekomen',
          'De TV is gevallen',
          'Water/vochtschade',
          'Anders namelijk...',
        ] as $opt): ?>
          <label class="portal-radio-label">
            <input type="radio" name="reden_schade" value="<?= htmlspecialchars($opt, ENT_QUOTES, 'UTF-8') ?>"
                   required <?= ($inzending['reden_schade'] ?? '') === $opt ? 'checked' : '' ?> />
            <?= htmlspecialchars($opt, ENT_QUOTES, 'UTF-8') ?>
          </label>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="portal-field">
      <label>Beschrijving <em class="portal-form-optional">(optioneel)</em></label>
      <textarea name="beschrijving" rows="3"><?= htmlspecialchars($inzending['omschrijving'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
    </div>

    <div class="portal-form-section">Aankoop</div>
    <div class="portal-fields-row">
      <div class="portal-field">
        <label>Aankoopbedrag *</label>
        <input type="text" name="aankoopbedrag" required placeholder="Bijv. 799,00"
               value="<?= htmlspecialchars($inzending['aankoopbedrag'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
      </div>
      <div class="portal-field">
        <label>Aankoopdatum *</label>
        <input type="date" name="aankoopdatum" required
               value="<?= htmlspecialchars($inzending['aankoopdatum'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
      </div>
    </div>
    <div class="portal-field">
      <label>Heeft u een bon/aankoopbewijs?</label>
      <div class="portal-radio-group">
        <?php foreach (['ja' => 'Ja', 'nee' => 'Nee', 'kwijt' => 'Niet meer in bezit'] as $val => $lbl):
          $huidig = $inzending['heeft_bon'] ?? '';
          $checked = ($huidig == 1 && $val === 'ja') || ($huidig == 0 && $val === 'nee') || ($huidig == 2 && $val === 'kwijt');
        ?>
          <label class="portal-radio-label">
            <input type="radio" name="heeft_bon" value="<?= $val ?>"
                   <?= $checked ? 'checked' : '' ?> />
            <?= htmlspecialchars($lbl, ENT_QUOTES, 'UTF-8') ?>
          </label>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="portal-form-section">Verzekering</div>
    <div class="portal-fields-row">
      <div class="portal-field">
        <label>Naam verzekeringsmaatschappij *</label>
        <input type="text" name="naam_verzekeraar" required
               value="<?= htmlspecialchars($inzending['naam_verzekeraar'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
      </div>
      <div class="portal-field">
        <label>Polisnummer *</label>
        <input type="text" name="polisnummer" required
               value="<?= htmlspecialchars($inzending['polisnummer'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
      </div>
    </div>

    <div class="portal-form-section">Foto's</div>
    <div class="portal-fields-row">
      <div class="portal-field">
        <label>Foto van het gehele toestel *</label>
        <input type="file" name="foto_toestel" accept="image/*" required onchange="fotoPreview(this,'prev_toestel_tax')" />
        <img id="prev_toestel_tax" src="" alt="Preview" style="display:none;max-width:100%;max-height:160px;margin-top:.5rem;border-radius:6px;border:1px solid #e5e4e0;">
      </div>
      <div class="portal-field">
        <label>Foto van de schade *</label>
        <input type="file" name="foto_defect" accept="image/*" required onchange="fotoPreview(this,'prev_defect_tax')" />
        <img id="prev_defect_tax" src="" alt="Preview" style="display:none;max-width:100%;max-height:160px;margin-top:.5rem;border-radius:6px;border:1px solid #e5e4e0;">
      </div>
    </div>
    <div class="portal-fields-row">
      <div class="portal-field">
        <label>Foto achterkant (modelnummer zichtbaar) *</label>
        <input type="file" name="foto_label" accept="image/*" required onchange="fotoPreview(this,'prev_label_tax')" />
        <img id="prev_label_tax" src="" alt="Preview" style="display:none;max-width:100%;max-height:160px;margin-top:.5rem;border-radius:6px;border:1px solid #e5e4e0;">
      </div>
      <div class="portal-field">
        <label>Extra foto <em class="portal-form-optional">(bijv. aankoopfactuur)</em></label>
        <input type="file" name="foto_extra" accept="image/*" onchange="fotoPreview(this,'prev_extra_tax')" />
        <img id="prev_extra_tax" src="" alt="Preview" style="display:none;max-width:100%;max-height:160px;margin-top:.5rem;border-radius:6px;border:1px solid #e5e4e0;">
      </div>
    </div>
    <p class="portal-upload-hint">Maximaal 10 MB per foto. Toegestane formaten: JPG, PNG, WebP.</p>

    <button type="submit" class="portal-submit-btn">Taxatieaanvraag indienen &rarr;</button>
  </form>
</div>
<script>
function fotoPreview(input, previewId) {
  var img = document.getElementById(previewId);
  if (!img) return;
  if (input.files && input.files[0]) {
    var reader = new FileReader();
    reader.onload = function(e) { img.src = e.target.result; img.style.display = 'block'; };
    reader.readAsDataURL(input.files[0]);
  } else {
    img.style.display = 'none';
  }
}
</script>
