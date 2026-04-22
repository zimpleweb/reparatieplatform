## Extra regels voor foto-beveiliging en mailacties

* Foto-beveiliging:
  - Alle geüploade foto's mogen NIET publiek toegankelijk zijn.
  - Zorg dat foto's alleen zichtbaar zijn voor ingelogde admins (en eventueel de betreffende ingelogde inzender).
  - Blokkeer directe toegang via URL voor niet-geautoriseerde gebruikers (bijv. via .htaccess, auth-check in PHP of bestandsrechten).

* Mailacties controleren en implementeren:
  - Bericht van admin naar inzending → mail naar de inzender
  - Bericht van inzender naar admin → mail naar admin
  - Statuswijziging (voor alle statussen) → notificatie mail naar de inzender
  - Melding wanneer een inzender een aanvullend formulier heeft ingevuld → mail naar admin

* Maak (of gebruik bestaande) mailtemplates in admin/mailtemplates.php voor bovenstaande mailacties.
* Zorg voor nette, duidelijke templates met alle relevante informatie (casenummer, status, link naar inzending, etc.).

* Alle wijzigingen mogen in één commit.
* Volg ook de bestaande security audit regels (vooral bestandsbeveiliging en input/output validatie).
