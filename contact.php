<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle       = 'Contact | Reparatieplatform.nl';
$pageDescription = 'Neem contact op met Reparatieplatform.nl. Vragen over uw televisie, aanvraag of advies? Wij helpen u graag.';
$canonicalUrl    = '/contact.php';

include __DIR__ . '/includes/header.php';
?>

<div class="page-header">
  <div class="page-header-inner">
    <div class="breadcrumb">
      <a href="<?= BASE_URL ?>/">Home</a><span class="sep">/</span>
      <span style="color:rgba(255,255,255,.4)">Contact</span>
    </div>
    <h1>Contact</h1>
    <p>Heeft u een vraag over uw televisie, een lopende aanvraag of ons advies? Stuur ons een bericht — we reageren doorgaans binnen één werkdag.</p>
  </div>
</div>

<div style="background:white;padding:4rem 0;">
  <div class="section" style="padding-top:0;padding-bottom:0;max-width:700px;">

    <div id="contact-succes" style="display:none;" class="alert alert-success">
      &#10003; Bedankt voor uw bericht! We reageren zo snel mogelijk, uiterlijk binnen één werkdag.
    </div>
    <div id="contact-fout" style="display:none;" class="alert alert-error">
      Er is iets misgegaan. Controleer uw gegevens en probeer het opnieuw.
    </div>

    <form id="contact-form-algemeen" action="<?= BASE_URL ?>/api/contact-algemeen.php" method="POST" novalidate>
      <input type="hidden" name="csrf_token" value="<?= csrf() ?>">

      <div class="field-row">
        <div class="field">
          <label>Naam *</label>
          <input type="text" name="naam" placeholder="Voor- en achternaam" required>
        </div>
        <div class="field">
          <label>E-mailadres *</label>
          <input type="email" name="email" placeholder="naam@email.nl" required>
        </div>
      </div>

      <div class="field">
        <label>Onderwerp *</label>
        <select name="onderwerp" required>
          <option value="">Kies een onderwerp</option>
          <option value="Vraag over mijn aanvraag">Vraag over mijn aanvraag</option>
          <option value="Vraag over advies">Vraag over advies</option>
          <option value="Vraag over reparatie">Vraag over reparatie</option>
          <option value="Vraag over taxatie">Vraag over taxatie</option>
          <option value="Klacht of opmerking">Klacht of opmerking</option>
          <option value="Overig">Overig</option>
        </select>
      </div>

      <div class="field">
        <label>Uw bericht *</label>
        <textarea name="bericht" rows="6" placeholder="Beschrijf uw vraag of opmerking zo uitgebreid mogelijk..." required></textarea>
      </div>

      <div class="field">
        <label>Casenummer <span style="font-weight:400;color:#6b7280;">(optioneel)</span></label>
        <input type="text" name="casenummer" placeholder="Bijv. RP-2025-0001">
        <p class="field-hint">Heeft u al een lopende aanvraag? Voeg dan uw casenummer toe zodat we snel kunnen opzoeken.</p>
      </div>

      <button type="submit" class="submit-btn" style="margin-top:.5rem;">Bericht versturen &rarr;</button>
    </form>

    <div style="margin-top:3rem;padding-top:2rem;border-top:1px solid #e5e4e0;">
      <h3 style="font-size:1rem;font-weight:700;margin-bottom:1rem;color:#0d0f14;">Veelgestelde vragen</h3>
      <div style="display:grid;gap:.75rem;">
        <details style="border:1px solid #e5e4e0;border-radius:10px;padding:.75rem 1rem;">
          <summary style="cursor:pointer;font-weight:600;font-size:.9rem;">Hoe lang duurt het voordat ik een reactie ontvang?</summary>
          <p style="margin-top:.5rem;font-size:.875rem;color:#374151;line-height:1.6;">We reageren doorgaans binnen één werkdag op uw bericht. In drukke periodes kan dit iets langer duren.</p>
        </details>
        <details style="border:1px solid #e5e4e0;border-radius:10px;padding:.75rem 1rem;">
          <summary style="cursor:pointer;font-weight:600;font-size:.9rem;">Hoe kan ik mijn aanvraag volgen?</summary>
          <p style="margin-top:.5rem;font-size:.875rem;color:#374151;line-height:1.6;">Via <a href="<?= BASE_URL ?>/mijn-aanvraag.php" style="color:#01696f;">Mijn aanvraag</a> kunt u met uw e-mailadres en casenummer de status van uw aanvraag bekijken en aanvullen.</p>
        </details>
        <details style="border:1px solid #e5e4e0;border-radius:10px;padding:.75rem 1rem;">
          <summary style="cursor:pointer;font-weight:600;font-size:.9rem;">Is het advies van Reparatieplatform gratis?</summary>
          <p style="margin-top:.5rem;font-size:.875rem;color:#374151;line-height:1.6;">Ja, het advies is volledig gratis en vrijblijvend. Alleen voor een taxatierapport (voor verzekeraars) worden kosten in rekening gebracht.</p>
        </details>
      </div>
    </div>

  </div>
</div>

<script>
(function () {
  var form    = document.getElementById('contact-form-algemeen');
  var succes  = document.getElementById('contact-succes');
  var fout    = document.getElementById('contact-fout');

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    fout.style.display = 'none';

    fetch('<?= BASE_URL ?>/api/contact-algemeen.php', {
      method: 'POST',
      body: new FormData(form)
    })
      .then(function (r) { return r.json(); })
      .then(function (json) {
        if (json.ok) {
          form.style.display = 'none';
          succes.style.display = '';
          succes.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
          fout.style.display = '';
        }
      })
      .catch(function () {
        fout.style.display = '';
      });
  });
}());
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
