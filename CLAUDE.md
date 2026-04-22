## Extra regels voor foto-beveiliging, standaardberichten en klachten-systeem

* Foto-beveiliging (kritiek):
  - Directe links naar foto's (bijv. via /api/foto.php?pad=...) mogen NIET publiek toegankelijk zijn.
  - Alleen ingelogde admins mogen foto's kunnen openen via directe link. 
  - Indien nodig: forceer login via admin/login.php of voeg auth-check toe in de foto-handler.

* Standaardberichten in mailtemplates.php:
  - Voeg functionaliteit toe voor standaardberichten (kolom "Standaardberichten").
  - In aanvragen.php bij "Bericht sturen aan klant" moet boven het tekstveld en onder de verzendknop een dropdown/ keuzemenu komen met standaardantwoorden.
  - Bij selectie van een standaardbericht wordt de tekst automatisch in het tekstveld geplaatst.

* Klachten-systeem uitbreiding (admin/klachten.php):
  - Het moet mogelijk zijn om klachten in te stellen per Merk, per Serie én per Model.
  - Een klacht ingesteld op Merk-niveau moet zichtbaar zijn voor alle series en modellen onder dat merk.
  - Een klacht op Serie-niveau moet zichtbaar zijn voor alle modellen onder die serie.

* Alle wijzigingen mogen in één commit.
* Volg ook de bestaande security audit regels (vooral bestandsbeveiliging en toegang tot uploads).
