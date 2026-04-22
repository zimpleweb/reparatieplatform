## Extra regels voor index.php contactformulier (bijgewerkt)

* De knop 'Verstuur en ontvang gratis advies →' mag GEEN redirect veroorzaken.
* Bij succesvolle verzending: toon op dezelfde pagina (exact op de plek van het formulier) de melding:  
  "Bedankt voor je bericht. We kijken het zo snel mogelijk door."
* Bij fout: toon een duidelijke foutmelding, maar los de onderliggende problemen op.
* De mailactie moet werken en net zo betrouwbaar zijn als de mailactie op advies.php.
* Los het huidige probleem op waarbij de melding "Er is iets misgegaan. Controleer uw gegevens en probeer het opnieuw." verschijnt en de mail niet wordt verzonden.
* Gebruik indien nodig de bestaande Brevo (Sendinblue) functionaliteit uit de admin-sectie (niet wijzigen, alleen hergebruiken).
* Reply-To moet het ingevoerde e-mailadres zijn en alle velden moeten duidelijk in de mail staan.
* Houd alle wijzigingen atomic, unified diff, één commit per kleine stap.
