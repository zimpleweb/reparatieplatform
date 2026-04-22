## Extra regels voor status-flow en formulierweergave in admin/aanvragen.php + mijn-aanvraag.php

* Wanneer in admin/aanvragen.php handmatig het advies-type wordt gewijzigd (bijv. van Coulance naar Reparatie), moet de status ook correct meeveranderen. 
  Voorbeeld: "Coulance afwachting" moet worden "Reparatie afwachting" (of de juiste nieuwe status volgens de flow).
* De status in het detailoverzicht van een aanvraag moet altijd overeenkomen met het gekozen advies-type.
* In mijn-aanvraag.php moet de status correct worden weergegeven en mee-updaten bij statuswijzigingen.
* Bij status "Aanvulling nodig" moet voor alle advies-types (Reparatie, Taxatie, Coulance, Recycling) hetzelfde reparatieformulier worden getoond aan de inzender.
* Controleer en los op waarom het formulier soms verdwijnt of helemaal niet wordt weergegeven bij status "Aanvulling nodig".
* Zorg voor consistente status-flow tussen overzicht, detail en klantportal (mijn-aanvraag.php).
* Volg ook de bestaande “aanvragen refactor” regels waar relevant.
