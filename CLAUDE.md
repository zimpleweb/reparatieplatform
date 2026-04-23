## Extra regels voor TV-pagina formulier

* Op TV-pagina's (bijv. /tv/... of /nieuw/tv/...) toon onderaan bij "Wat zijn de mogelijkheden voor jouw..." hetzelfde stappenplan formulier als op advies.php / index.php.
* Gebruik bij voorkeur het bestaande component (stap-wizard of vergelijkbaar) voor hergebruik.
* Bij stap 2 (TV gegevens): vul automatisch Merk en Modelnummer in op basis van de huidige pagina URL/slug (bijv. hisense-43a6g → Merk: Hisense, Model: 43a6g).
* Doe dit dynamisch voor alle TV-pagina's (geen aparte bestanden per model).
* Als SQL-wijziging nodig is: geef SQL-code apart voor phpMyAdmin.
* Alle wijzigingen in **één enkele commit**.

Maak dit als één atomic commit met unified diff voor CLAUDE.md.
