## Extra regels voor TV-pagina's & stappenplan

* Op tv/index.php én op individuele TV-pagina's (bijv. /nieuw/tv/hisense-43a6g) moet onderaan het volledige stappenplan formulier komen te staan (hetzelfde als op advies.php en index.php).
* Gebruik bij voorkeur een herbruikbaar component om duplicatie te voorkomen.
* In stap 2 (TV gegevens) op een individuele TV-pagina: vul automatisch Merk en Modelnummer in op basis van de URL/slug (bijv. hisense-43a6g → Merk: Hisense, Model: 43a6g).
* Werk volledig dynamisch — geen aparte scripts per TV-model.
* Als SQL-wijziging nodig is: geef SQL-code apart voor phpMyAdmin.
* Alle wijzigingen in **één enkele commit**.

Maak dit als één atomic commit met unified diff voor CLAUDE.md.
