## Extra regels voor admin/aanvragen.php fixes, logo, error 500, uploads en overige verbeteringen

* In een individuele inzending (aanvragen.php?id=XX) werkt het tandwieltje / "Advies handmatig wijzigen" niet correct. Bij wijzigen van bijv. Coulance naar Reparatie moet de status en indeling ook daadwerkelijk naar Reparatie (of de juiste nieuwe status) worden gezet. Los dit op.

* Het wijzigen naar status "Recycling" werkt niet in het algemene overzicht van aanvragen.php.

* Verander het logo in de admin hoofdmenu (alle admin pagina's) naar:  
  https://reparatieplatform.nl/wp-content/uploads/2025/06/REPARATIEPLATFORM-LOGO-WEBSITE-1200x336.png  
  (vervang het huidige adm-logo).

* Los de PHP Fatal errors op in api/aanvulling.php:
  - Unknown column 'verkoopprijs'
  - Unknown column 'plaats'
  Geef indien nodig de exacte SQL ALTER TABLE queries zodat ik ze in phpMyAdmin kan draaien.

* Bij uploaden van foto's in alle formulieren (zowel klant als admin):
  - Toon een preview van de geüploade foto's voor de inzender.
  - Toon ook previews in de admin bij het openen van een individuele inzending.

* Maak pagina contact.php aan en vul deze in (met werkend contactformulier).

* Wanneer een inzending in behandeling/afwachting is en de inzender het aanvullende formulier invult, moet dit zichtbaar zijn in het overzicht op dashboard.php en aanvragen.php (bijv. met een groen bolletje of notificatie bij de betreffende inzending).

* Alle wijzigingen mogen in één commit.
* Volg ook de bestaande “aanvragen refactor”, status-flow en security audit regels.
