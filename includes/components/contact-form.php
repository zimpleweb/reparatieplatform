<?php
// Contact form component – homepage (index.php)
// Vereist: csrf() en h() uit functions.php (geladen door de pagina)
?>
<?php if (isset($_GET['verzonden'])): ?>
  <div class="alert alert-success" style="margin-bottom:1.5rem;">&#10003; Uw bericht is verzonden! We nemen zo snel mogelijk contact op.</div>
<?php elseif (isset($_GET['error'])): ?>
  <div class="alert alert-error" style="margin-bottom:1.5rem;">Er is iets misgegaan. Controleer uw gegevens en probeer het opnieuw.</div>
<?php endif; ?>
<form action="/api/send-contact.php" method="POST">
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
