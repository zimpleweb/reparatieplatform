Update CLAUDE.md met extra feature regels.

Voeg onderaan (na de bestaande feature sectie) dit nieuwe blok toe:

## Extra regels voor index.php contactformulier

* Na succesvolle verzending: toon een success melding op dezelfde pagina (op de plek van het formulier) met tekst: "Bedankt voor je bericht. We kijken het zo snel mogelijk door."
* Redirect NIET naar reparatieplatform.nl, maar blijf op de huidige pagina (de URL waar index.php op staat).
* Los het probleem op dat de mailactie niet werkt.
* Houd alle wijzigingen atomic, unified diff, één commit per kleine stap.
* Volg ook de eerdere regels voor directe mail naar info@zimpleweb.nl met Reply-To.

Maak dit als één atomic commit met unified diff.
