## Extra regels voor TV-pagina's (tv/index.php) - Info tekst & links

* Op individuele TV-pagina's (bijv. /tv/lg-43nano756qa):
  - Het info-blok (ID card) met tekst "Reparatie van de [Merk Model]" moet dynamisch één van de drie teksten tonen op basis van database:
    1. Beide (reparatie + taxatie mogelijk) → gecombineerde tekst over reparatie én taxatie
    2. Alleen taxatie → tekst over alleen taxatie
    3. Alleen reparatie → tekst over alleen reparatie
  - "Beschrijf jouw probleem" moet een anchor link worden naar het stappenplan formulier onderaan dezelfde pagina.
* Houd alle voorgaande TV-pagina regels van kracht (breadcrumbs, links /nieuw/, Reparatie & Taxatie sectie, etc.).
* Gebruik bestaande componenten en database-velden waar mogelijk.
* Als SQL-wijziging nodig is: geef SQL-code apart voor phpMyAdmin.
* Alle wijzigingen in **één enkele commit**.

Maak dit als één atomic commit met unified diff voor CLAUDE.md.
