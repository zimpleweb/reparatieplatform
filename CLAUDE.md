## Extra regels voor index.php contactformulier (bijgewerkt)

* Na succesvolle verzending: toon een success melding op dezelfde pagina (op de plek van het formulier) met exact de tekst: "Bedankt voor je bericht. We kijken het zo snel mogelijk door."
* Er mag GEEN redirect plaatsvinden naar reparatieplatform.nl of enige andere URL — blijf altijd op de huidige pagina/URL waar index.php op staat.
* Zorg dat de mailactie werkt: verstuur een directe e-mail naar info@zimpleweb.nl met Reply-To = het ingevoerde e-mailadres en alle velden duidelijk leesbaar in de mail.
* Los het huidige probleem op waarbij na het invullen een redirect optreedt en er geen mail aankomt.
* Houd alle wijzigingen atomic, unified diff, één commit per kleine stap.
* Volg ook alle eerdere regels voor directe mail en security.
