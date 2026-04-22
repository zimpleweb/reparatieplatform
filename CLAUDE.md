## Extra regels voor index.php contactformulier (bijgewerkt 22 april 2026)

* De knop 'Verstuur en ontvang gratis advies →' mag GEEN redirect veroorzaken naar reparatieplatform.nl of enige andere URL.
* Na klikken op de knop moet een mailactie worden getriggerd naar info@zimpleweb.nl.
* Reply-To moet het ingevoerde e-mailadres van de gebruiker zijn.
* Alle ingevoerde velden moeten duidelijk leesbaar in de e-mail staan.
* Na succesvolle verzending: toon op dezelfde pagina (op de exacte plek van het formulier) de melding:  
  "Bedankt voor je bericht. We kijken het zo snel mogelijk door."
* Er mag absoluut geen doorverwijzing (redirect) plaatsvinden — blijf altijd op de huidige URL.
* De mailactie werkt momenteel niet. Los dit op.
* Je mag gebruik maken van de bestaande Brevo (Sendinblue) SMTP/API die al in de admin sectie aanwezig is. Wijzig die API-code niet, maar hergebruik de functionaliteit voor deze mail.
* Houd alle wijzigingen atomic, unified diff, één commit per kleine stap.
