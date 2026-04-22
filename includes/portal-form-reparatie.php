<?php
// Sub-component: portal-form-reparatie.php
// Variabelen vereist: $inzending (array)
?>
<div class="portal-action-card">
  <div class="portal-action-header">
    <div class="portal-action-icon">🔧</div>
    <div>
      <h3>Reparatieaanvraag</h3>
      <p>Vul uw gegevens aan om de reparatieaanvraag te voltooien.</p>
    </div>
  </div>
  <form class="portal-form" method="POST" action="<?= BASE_URL ?>/api/aanvulling.php" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token"  value="<?= csrf() ?>" />
    <input type="hidden" name="aanvraag_id" value="<?= (int)$inzending['id'] ?>" />
    <input type="hidden" name="casenummer"  value="<?= htmlspecialchars($inzending['casenummer'], ENT_QUOTES, 'UTF-8') ?>" />
    <input type="hidden" name="type"        value="reparatie" />

    <div class="portal-form-section">Contactgegevens</div>
    <div class="portal-fields-row">
      <div class="portal-field">
        <label>Naam *</label>
        <input type="text" name="naam" required value="<?= htmlspecialchars($inzending['naam'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
      </div>
      <div class="portal-field">
        <label>E-mail</label>
        <?= lockedField($inzending['email'], 'email') ?>
      </div>
    </div>
    <div class="portal-fields-row">
      <div class="portal-field">
        <label>Plaats *</label>
        <input type="text" name="plaats" required value="<?= htmlspecialchars($inzending['plaats'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
      </div>
      <div class="portal-field">
        <label>Telefoon *</label>
        <input type="tel" name="telefoon" required value="<?= htmlspecialchars($inzending['telefoon'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
      </div>
    </div>

    <div class="portal-form-section">Televisie</div>
    <div class="portal-fields-row">
      <div class="portal-field">
        <label>Merk televisie</label>
        <?= lockedField($inzending['merk']) ?>
      </div>
      <div class="portal-field">
        <label>Model televisie</label>
        <?= lockedField($inzending['modelnummer']) ?>
      </div>
    </div>

    <div class="portal-form-section">Klacht</div>
    <div class="portal-field">
      <label>Klachtomschrijving *</label>
      <textarea name="omschrijving" required rows="4"><?= htmlspecialchars($inzending['omschrijving'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
    </div>

    <div class="portal-form-section">Foto's <em class="portal-form-optional">(niet verplicht)</em></div>
    <div class="portal-fields-row">
      <div class="portal-field">
        <label>Foto van de klacht</label>
        <span class="portal-field-hint">Foto van het defect (optioneel).</span>
        <input type="file" name="foto_defect" accept="image/*" onchange="fotoPreview(this,'prev_defect_rep')" />
        <img id="prev_defect_rep" src="" alt="Preview" style="display:none;max-width:100%;max-height:160px;margin-top:.5rem;border-radius:6px;border:1px solid #e5e4e0;">
      </div>
      <div class="portal-field">
        <label>Foto van het modelnummer</label>
        <span class="portal-field-hint">Foto van het modelnummersticker (optioneel).</span>
        <input type="file" name="foto_label" accept="image/*" onchange="fotoPreview(this,'prev_label_rep')" />
        <img id="prev_label_rep" src="" alt="Preview" style="display:none;max-width:100%;max-height:160px;margin-top:.5rem;border-radius:6px;border:1px solid #e5e4e0;">
      </div>
    </div>
    <p class="portal-upload-hint">Maximaal 10 MB per foto. Toegestane formaten: JPG, PNG, WebP.</p>

    <button type="submit" class="portal-submit-btn">Reparatieaanvraag indienen &rarr;</button>
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