<?php
// Component: portal-login.php
// Variabelen vereist: $loginFout (bool)
?>
<div class="portal-login-wrap">
  <div class="portal-login-card">
    <div class="portal-login-icon">🔐</div>
    <h2>Aanvraag inzien</h2>
    <p class="lead">Voer uw casenummer en e-mailadres in om uw persoonlijke klantenomgeving te openen.</p>

    <?php if ($loginFout): ?>
      <div class="portal-alert alert-error">
        <span>⚠</span>
        <span>Geen aanvraag gevonden met dit casenummer en e-mailadres. Controleer de gegevens en probeer opnieuw.</span>
      </div>
    <?php endif; ?>

    <form method="POST">
      <input type="hidden" name="check_case" value="1" />
      <div class="portal-field">
        <label for="casenummer_check">Casenummer</label>
        <input type="text" id="casenummer_check" name="casenummer_check"
               placeholder="Bijv. 2026-04-1000"
               value="<?= htmlspecialchars($_POST['casenummer_check'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
               autocomplete="off" required />
      </div>
      <div class="portal-field">
        <label for="email_check">E-mailadres</label>
        <input type="email" id="email_check" name="email_check"
               placeholder="uw@email.nl"
               value="<?= htmlspecialchars($_POST['email_check'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
               required />
      </div>
      <button type="submit" class="portal-login-btn">Mijn aanvraag bekijken &rarr;</button>
    </form>

    <p class="portal-login-hint">
      Nog geen aanvraag gedaan?
      <a href="<?= BASE_URL ?>/advies.php">Vraag gratis advies aan &rarr;</a>
    </p>
  </div>
</div>