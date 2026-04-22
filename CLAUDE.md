## Extra regels voor index.php contactformulier (bijgewerkt)

* De knop 'Verstuur en ontvang gratis advies →' mag GEEN redirect veroorzaken.
* Bij succesvolle verzending: toon op dezelfde pagina (exact op de plek van het formulier) de melding:
  "Bedankt voor je bericht. We kijken het zo snel mogelijk door."
* Bij fout: toon een duidelijke foutmelding, maar los de onderliggende problemen op.
* De mailactie moet werken en net zo betrouwbaar zijn als de mailactie op advies.php.
* Los het huidige probleem op waarbij de melding "Er is iets misgegaan. Controleer uw gegevens en probeer het opnieuw." verschijnt en de mail niet wordt verzonden.
* Gebruik de bestaande Brevo (Sendinblue) functionaliteit uit mailer.php (getSetting + mailSend). Niet wijzigen wat al werkt, alleen hergebruiken.
* mailSend() moet bij Brevo-falen terugvallen op PHP mail() als fallback, zodat de mail altijd aankomt.
* send-contact.php haalt admin-e-mailadressen op uit de admins-tabel (net als send-advies.php), met info@zimpleweb.nl als hardcoded fallback.
* Reply-To moet het ingevoerde e-mailadres zijn en alle velden moeten duidelijk in de mail staan.
