## Extra regels voor status "Gesloten" en verbeteringen aanvragen.php + formulieren

* Voeg een nieuwe status toe: "Gesloten". Deze status is bedoeld voor oninteressante inzendingen en voor gevallen waarbij coulance bij winkel of fabrikant is gelukt, of wanneer een TV niet repareerbaar is.

* Los het probleem op dat wisselen naar status "Recycling" niet werkt in admin/aanvragen.php (status wordt niet bijgewerkt).

* Pas het Coulance-formulier aan:
  - Verwijder de vraag "Heeft u de aankoopbon nog?"
  - Bij keuze van een winkel: toon een tekst met link/knop naar de support- of contactpagina van die winkel.
  - Bij vervolgstap fabrikant: doe hetzelfde met een link/knop naar de supportpagina van de vooraf ingevulde fabrikant.
  - Als het merk/model niet repareerbaar is (volgens database/instellingen): geef melding dat reparatie door ons niet mogelijk is, maar een andere gespecialiseerde reparateur wellicht wel. Geen optie voor reparatieadvies.
  - Als het merk/model wel repareerbaar is:
    - Bij "Nee" coulance bij shop of fabrikant: vraag of een reparatieadvies kan worden gestart.
    - Bij Ja → zet status om naar "Reparatie in afwachting" + melding in admin.
    - Bij Nee → zet status op "Gesloten".

* Recycling formulier:
  - Vraag of er interesse is in verduurzaming/recycling van de televisie.
  - Bij Nee → optie om inzending af te sluiten (status "Gesloten").
  - Bij Ja → toon formulier gerelateerd aan reparatieformulier, inclusief foto's, in de trant van recycling.

* Alle wijzigingen mogen in één commit.
* Volg ook de bestaande “aanvragen refactor” en security audit regels.
