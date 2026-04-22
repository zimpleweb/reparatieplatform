# Extra regels voor deze feature (aanvragen refactor)

- In admin/aanvragen.php: volledig nieuwe structuur en indeling voor inzendingen
- Advies komt uit advies.php (stappenplan → Reparatie / Taxatie / Garantie / Coulance / Recycling)
- Nieuwe status-flow per advies-type: [Type] afwachting → [Type] ingevuld
- Na keuze in admin: toon specifieke formulier aan gebruiker
- Chat alleen zichtbaar als status = een advies-type (niet meer "inzending")
- Na ingevuld formulier: groen (doorgaan), oranje (wijzigen), rood (afwijzen)
- Houd alle wijzigingen atomic (één commit per kleine stap)
- Gebruik altijd unified diff
