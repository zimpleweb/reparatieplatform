## Extra regels voor formulieren in mijn-aanvraag.php (per advies-type)

* In mijn-aanvraag.php moet iedere advies-status in behandeling (afwachting) een eigen specifiek formulier tonen:
  - Reparatie → behoud het bestaande reparatieformulier
  - Taxatie → toon een eigen Taxatieformulier (zie velden hieronder)
  - Coulance → toon een eigen Coulance-formulier (zie beschrijving hieronder)
  - Recycling → toon een eigen Recycling-formulier (gerelateerd aan reparatieformulier + recycling-vraag)

* Maak ieder formulier bij voorkeur een apart component in `includes/components/` (bijv. `taxatie-form.php`, `coulance-form.php`, `recycling-form.php`) zodat mijn-aanvraag.php overzichtelijk blijft en geen duplicatie ontstaat.

**Taxatie formulier velden:**
- Taxatieaanvraag
- Voor- en achternaam *, Adres *, Postcode *, Plaats *, E-mail *, Telefoonnummer *
- Merk TV *, Modelnummer *, Serienummer *
- Reden schade * (radio/opties: Iets tegen scherm gekomen, De TV is gevallen, Water/vochtschade, Anders namelijk...)
- Beschrijving (optioneel)
- Aankoopbedrag *, Aankoopdatum *
- Heeft u een bon/aankoopbewijs? (Ja/Nee/Drie)
- Naam verzekeringsmaatschappij *, Polisnummer *
- Foto van het gehele toestel *, Foto van de schade *, Foto achterkant (modelnummer zichtbaar) *, Extra foto (bijv. aankoopfactuur)

* Na invullen Taxatie: toon tekst: "Na het invullen van het formulier sturen wij u een factuur van 60 euro inclusief btw voor product onderzoek, registratie- en administratiekosten en eventuele recyclingkosten. Na betaling maken wij het rapport op."

**Coulance formulier:**
- Vraag naar exacte verkoopprijs en of bon nog in bezit is (geen foto)
- Vraag naar winkel/shop waar TV gekocht is (dropdown met 15 populaire shops + "Anders")
- Vraag: "Is het met de winkel gelukt voor coulance?" (Ja → optie om inzending te sluiten | Nee → vraag naar fabrikant)
- Bij Nee fabrikant: vraag of coulance bij fabrikant gelukt is (Ja → sluiten | Nee → status wijzigen naar "Aanvraag reparatie in afwachting" indien model repareerbaar is)

**Recycling formulier:**
- Vraag of interesse in verduurzaming/recycling (Ja/Nee)
- Bij Nee → optie om inzending af te sluiten
- Bij Ja → toon formulier gerelateerd aan reparatieformulier + extra recycling-vragen + foto's

* Foto-uploads moeten VEILIG gebeuren:
  - Opslaan in een beveiligde map (alleen toegankelijk voor admins en ingelogde inzenders)
  - Bij verwijderen van een inzending → automatisch alle bijbehorende foto's verwijderen
  - Sorteer foto's in mappen: jaar/maand/dag/laatste-4-cijfers-casenummer

* Alle wijzigingen mogen in één commit.
* Volg ook de bestaande “aanvragen refactor” en security audit regels.
