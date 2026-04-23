## Extra regels voor individuele TV-pagina's (tv/index.php)

* Op individuele TV-pagina's (bijv. /tv/lg-43nano756qa):
  - Corrigeer breadcrumbs en links naar vergelijkbare modellen zodat ze exact dezelfde structuur volgen als in database.php (naar /nieuw/... in plaats van root).
  - Reparatie & Taxatie sectie:
    - Toon één gecombineerde tekstkop afhankelijk van database:
      1. Beide (reparatie + taxatie mogelijk) → tekst over reparatie én taxatie
      2. Alleen taxatie → tekst over taxatie
      3. Alleen reparatie → tekst over reparatie
  - Zorg dat de kolommen aan de linkerkant (Bekende defecten & Reparatie/Taxatie) exact dezelfde hoogte hebben (verwijder extra ruimte).
  - Stappenplan onderaan: maak dit identiek aan het stappenplan op index.php en advies.php.
    - Verwijder in stap 2 elk advies over "wel/niet repareerbaar".
    - Enige verschil: vul Merk en Modelnummer automatisch in vanuit de URL (bijv. lg-43nano756qa → Merk: LG, Model: 43NANO756QA).
* Gebruik bestaande componenten waar mogelijk.
* Als SQL-wijziging nodig is: geef SQL-code apart voor phpMyAdmin.
* Alle wijzigingen in **één enkele commit**.

Maak dit als één atomic commit met unified diff voor CLAUDE.md.
