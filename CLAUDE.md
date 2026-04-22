## Extra regels voor index.php - Stappenplan integratie

* Verwijder het volledige huidige contactformulier op index.php (inclusief alle bijbehorende HTML, PHP, JavaScript en eventuele gekoppelde bestanden die alleen voor dit formulier bedoeld zijn).
* Vervang het verwijderde formulier door het stappenplan-formulier dat ook op advies.php staat.
* Het stappenplan is (vermoedelijk) een component. Verwijs daarom alleen naar dit component op index.php in plaats van code te dupliceren.
* Na vervanging: op de plek van het oude formulier moet nu het stappenplan-formulier zichtbaar zijn.
* Zorg dat het stappenplan op index.php exact hetzelfde werkt als op advies.php (inclusief mailactie / inzending).
* Verwijder na implementatie alle dode code die alleen bij het oude formulier hoorde.
