<?php
// Contact form component – homepage (index.php)
// Vereist: csrf() en h() uit functions.php (geladen door de pagina)
?>
<div id="contact-success" style="display:none;margin-bottom:1.5rem;" class="alert alert-success">
  &#10003; Bedankt voor je bericht. We kijken het zo snel mogelijk door.
</div>
<div id="contact-error" style="display:none;margin-bottom:1.5rem;" class="alert alert-error">
  Er is iets misgegaan. Controleer uw gegevens en probeer het opnieuw.
</div>
<form id="contact-form" action="/api/send-contact.php" method="POST">
  <input type="hidden" name="csrf_token" value="<?= csrf() ?>" />
  <div class="field-row">
    <div class="field">
      <label>Merk *</label>
      <select name="merk" required>
        <option value="">Selecteer merk</option>
        <?php foreach (['Samsung','Philips','Sony','LG','Panasonic','Hisense','TCL','Anders'] as $m): ?>
        <option value="<?= h($m) ?>"><?= h($m) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Modelnummer *</label>
      <input type="text" name="modelnummer" placeholder="Bijv. UE55CU8000" required />
      <p class="field-hint">Staat achter op de tv of via Instellingen &rarr; Ondersteuning</p>
    </div>
  </div>
  <div class="field-row">
    <div class="field">
      <label>Aanschafjaar</label>
      <select name="aanschafjaar">
        <option value="">Onbekend</option>
        <option>2024 &ndash; 2025</option>
        <option>2022 &ndash; 2023</option>
        <option>2020 &ndash; 2021</option>
        <option>2018 &ndash; 2019</option>
        <option>Ouder dan 2018</option>
      </select>
    </div>
    <div class="field">
      <label>Type klacht *</label>
      <select name="klacht_type" required>
        <option value="">Selecteer klacht</option>
        <option>Kapot / gebarsten scherm</option>
        <option>Strepen of lijnen in beeld</option>
        <option>Geen beeld, wel geluid</option>
        <option>Donkere vlekken / backlight-uitval</option>
        <option>TV gaat niet aan</option>
        <option>Bevroren beeld of flikkering</option>
        <option>Anders</option>
      </select>
    </div>
  </div>
  <div class="field">
    <label>Omschrijving</label>
    <textarea name="omschrijving" placeholder="Bijv: zwarte strepen rechts, donkere vlek linksonder, scherm flikkert..."></textarea>
  </div>
  <div class="field">
    <label>E-mailadres *</label>
    <input type="email" name="email" placeholder="naam@email.nl" required />
    <p class="field-hint">Hier sturen we je advies naartoe. Geen spam.</p>
  </div>
  <div class="disclaimer-box">
    &#9888;&#65039; Het advies van Reparatieplatform.nl is indicatief en vrijblijvend.
    Aan dit advies kunnen geen rechten worden ontleend.
    Reparatieplatform.nl is een initiatief van TV Reparatie Service Nederland.
  </div>
  <button type="submit" class="submit-btn">Verstuur en ontvang gratis advies &rarr;</button>
</form>
<script>
(function () {
  var form    = document.getElementById('contact-form');
  var success = document.getElementById('contact-success');
  var error   = document.getElementById('contact-error');

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    error.style.display = 'none';

    fetch('/api/send-contact.php', { method: 'POST', body: new FormData(form) })
      .then(function (r) { return r.json(); })
      .then(function (json) {
        if (json.ok) {
          form.style.display = 'none';
          success.style.display = '';
          success.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
          error.style.display = '';
        }
      })
      .catch(function () {
        error.style.display = '';
      });
  });
}());
</script>
