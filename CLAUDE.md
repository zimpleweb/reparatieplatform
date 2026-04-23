## Extra regels voor TV-pagina layout & links (tv/index.php)

* Op individuele TV-pagina's (bijv. /tv/lg-43nano756qa):
  - Plaats de "Reparatie & Taxatie" sectie tussen Specificaties en Vergelijkbare modellen.
  - Corrigeer alle links in "Vergelijkbare modellen" zodat ze beginnen met /nieuw/tv/... (bijv. /nieuw/tv/philips-55oled807).
  - Corrigeer breadcrumbs:
    - Link naar merk gaat naar /nieuw/database.php?merk=Naam
    - Link naar serie gaat naar dezelfde URL als het merk (geen aparte serie-URL).
    - Voorbeeld: Home / Database / Samsung / Crystal UHD / UE65CU8000 → Crystal UHD linkt naar Samsung URL.
* Houd de Reparatie & Taxatie logica zoals eerder gedefinieerd (✓/✕ + één tekstblok afhankelijk van DB).
* Alle wijzigingen in **één enkele commit**.

Maak dit als één atomic commit met unified diff voor CLAUDE.md.
