## Extra regels voor Reparatie & Taxatie + links op TV-pagina's

* Op individuele TV-pagina's (tv/index.php, bijv. /tv/lg-43nano756qa):
  - Corrigeer breadcrumbs en links naar "vergelijkbare modellen" naar /nieuw/... in plaats van root (/).
  - In de "Reparatie & Taxatie" sectie:
    - Altijd beide opties tonen: "Reparatie" en "Taxatie" met ✓ of ✕ op basis van database.
    - Toon daarnaast maximaal 2 tekstblokken (alleen als ingeschakeld in DB):
      - "Reparatie" met eigen titel + beschrijvende tekst
      - "Taxatie" met eigen titel + beschrijvende tekst
* Gebruik bestaande componenten en database-velden waar mogelijk.
* Als SQL-wijziging nodig is: geef SQL-code apart voor phpMyAdmin.
* Alle wijzigingen in **één enkele commit**.

Maak dit als één atomic commit met unified diff voor CLAUDE.md.
