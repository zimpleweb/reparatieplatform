## Extra regels voor foto-beveiliging, mail bij coulance, mail log en garantie/coulance instellingen

* Foto-beveiliging (herhaald & kritiek):
  - Directe foto-links (bijv. via api/foto.php?pad=...) mogen absoluut niet publiek toegankelijk zijn.
  - Alleen ingelogde admins (via admin/login.php) mogen foto's kunnen bekijken. 
  - Zorg voor een sterke auth-check in de foto-handler zodat niet-ingelogde gebruikers de bestanden niet kunnen openen.

* Mail bij coulance en andere types:
  - Controleer waarom er bij coulance-inzendingen geen mailings plaatsvinden.
  - Zorg dat mailacties (bevestiging, statuswijziging, etc.) ook werken voor alle advies-types (Coulance, Taxatie, Recycling, Garantie, etc.), niet alleen Reparatie.

* Mail Log in meldingen.php:
  - Voeg een nieuw tabblad "Mail Log" toe.
  - Toon een log van alle verzonden e-mails met: e-mailadres, onderwerp, inhoud (of samenvatting), datum-tijd.
  - Sla deze logs op in de database (nieuwe tabel of bestaande uitbreiden).

* Nieuw submenu onder Instellingen in admin:
  - Voeg "Coulance Regels" toe.
  - Hierin: beheer van shops (max 30) met naam + supportpagina link (toevoegen, wijzigen, verwijderen).
  - Per merk in de database ook een supportpagina instellen.
  - Shops en garantie-regels mogen in de database worden opgeslagen (vergelijkbaar met bestaande advies-instellingen).

* Garantie-functionaliteit:
  - Garantie heeft nog geen formulier in admin en mijn-aanvraag.php.
  - Pas het stappenplan op index.php en advies.php aan.
  - Als een TV binnen de garantietermijn valt (volgens advies-instellingen.php / database), toon dan direct advies in het stappenplan: "Wettelijke garantie geldt – neem contact op met de winkel/merk".
  - Gebruik dezelfde shops-lijst als bij coulance, plus optie voor merk met bijbehorende supportlink.
  - Bij garantie geen volledige inzending aanmaken; toon alleen het advies met shop/merk keuze en supportlinks.

* Alle wijzigingen mogen in één commit.
* Volg bestaande security audit regels (vooral foto-toegang) en aanvragen refactor regels.
