## Extra regels voor individuele TV-pagina's (tv/index.php)

* Verwijder op individuele TV-pagina's (bijv. /tv/lg-43nano756qa) het rechter gedeelte "Gratis advies aanvragen" inclusief het hele formulier.
* Onder de Specificaties-kolom: voeg een nieuwe sectie "Reparatie & Taxatie" toe. Toon "Reparatie mogelijk" en/of "Taxatie mogelijk" alleen als dit in de database staat voor dat merk/model.
* Verplaats de bestaande Specificaties + inhoud naar de plek waar voorheen "Reparatiemogelijkheden" stond.
* Op de oude plek van Specificaties: toon nu "Bekende defecten" (gegevens uit de database via admin).
* Direct onder de H1 titel (bijv. "LG 50NANO756QA") de model-sub vervangen door een duidelijke call-to-action naar het stappenplan onderaan de pagina.
* Gebruik waar mogelijk bestaande componenten.
* Als SQL-wijziging nodig is: geef SQL-code apart voor phpMyAdmin.
* Alle wijzigingen in **één enkele commit**.

Maak dit als één atomic commit met unified diff voor CLAUDE.md.
