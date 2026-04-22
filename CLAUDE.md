Update CLAUDE.md met extra regels voor nieuwe features.

Voeg onderaan (na de bestaande Security Audit regels) dit nieuwe blok toe:

## Extra regels voor nieuwe features (contact & advies.php)

* index.php algemeen formulier: maak het een directe mailactie naar info@zimpleweb.nl met Reply-To = ingevoerde e-mailadres. Stuur alle velden duidelijk in de mail. Maak bij voorkeur een eigen component (bijv. includes/components/contact-form.php). Houd veilig (geen injecties).
* advies.php: maak het stappenplan formulier prominenter (verplaats hoger op de pagina, geef meer visuele nadruk als belangrijkste CTA). Houd rest van de pagina intact.
* Gebruik altijd atomic changes, unified diff, één commit per kleine stap.
* Volg ook de bestaande Security Audit en aanvragen refactor regels waar relevant.

Maak dit als één atomic commit met een duidelijke commit message. Gebruik unified diff voor de wijziging in CLAUDE.md.
