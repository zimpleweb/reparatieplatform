## Extra regels voor UI/UX & Performance verbeteringen

* Logo in het menu (nav-logo) op de root/homepage en alle publieke pagina's: maak het 25% groter.
* Humanize alle teksten op de publieke pagina's (niet in /admin): vervang AI-achtige teksten door natuurlijke, zakelijke, professionele en vriendelijke Nederlandse teksten die passen bij een reparatieplatform.
* Controleer de admin-pagina's (vooral aanvragen.php) op schaalbaarheid voor >1000 inzendingen. Voeg indien nodig pagination of betere laadfuncties toe.
* Als er SQL-wijzigingen nodig zijn: geef de SQL-code apart voor phpMyAdmin.
* Alle wijzigingen in **één enkele commit** (niet atomic per stap).

Maak dit als één atomic commit met unified diff voor CLAUDE.md.
