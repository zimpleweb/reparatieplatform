## Extra regels voor notificaties, dashboard.php, aanvragen.php en mijn-aanvraag.php

* Wanneer een inzending in behandeling/afwachting is en de inzender het aanvullende formulier invult:
  - Toon in het overzicht op dashboard.php en aanvragen.php een groen bolletje links van het Casenummer.
  - Dit groene bolletje moet verschijnen zodra de status naar "Aanvraag ontvangen" gaat (of vergelijkbare status na inzending van aanvullend formulier).

* Verbeter het recente inzendingen overzicht op dashboard.php:
  - Voeg het Casenummer toe aan de kolommen.
  - Toon de huidige status van de inzending.
  - Plaats het groene bolletje links van het Casenummer wanneer de status "Aanvraag ontvangen" is.

* In het individuele overzicht van een inzending (aanvragen.php?id=XX):
  - Toon alle ingezonden foto's met een klikbare link ernaast.
  - Maak een lightbox-functionaliteit zodat foto's direct vergroot kunnen worden.

* In mijn-aanvraag.php:
  - Wanneer een formulier is ingevuld en de status "Ingediend" is, moet het ingevulde formulier nog steeds zichtbaar zijn.
  - Het formulier mag dan alleen in leesmodus zijn (geen invoervelden meer bewerkbaar, geen verzendknop).

* Alle wijzigingen mogen in één commit.
* Volg ook de bestaande “aanvragen refactor”, status-flow en security audit regels.
