# 📺 Reparatieplatform.nl

Een PHP-gebaseerd webplatform dat consumenten begeleidt bij het bepalen van de beste route wanneer hun televisie defect is — garantie, coulance, reparatie, taxatie of recycling.

---

## 🚀 Functionaliteiten

- **Gratis adviesformulier** (`advies.php`) — stapsgewijs formulier (4 stappen) dat automatisch de beste route bepaalt op basis van merk, model, aanschafjaar, aankooplocatie en klachttype
- **Routing engine** — volledig configureerbaar via de admin, op basis van regels uit de database (garantietermijn, coulancematrix, reparatielijst, etc.)
- **TV-modellendatabase** (`admin/modellen.php`) — beheer van alle bekende TV-modellen met repareerbaar/taxatie-vlaggen en lazy loading per 35 stuks
- **Advies instellingen** (`admin/advies-instellingen.php`) — instelbare regels per route: merkenlijsten, leeftijdsgrenzen, kansmatrix coulance, uitsluitingen
- **Model-uitzonderingen** — modellen die afwijken van de merk-standaard (positief/negatief) zijn zichtbaar in zowel modellenbeheer als advies-instellingen
- **Aanvragen beheer** (`admin/aanvragen.php`) — overzicht van ingediende adviesaanvragen
- **Klachtenbeheer** (`admin/klachten.php`) — overzicht en opvolging van klachten
- **Taxatieformulier** (`taxatie.php`) — aparte route voor schade-taxatie
- **TV-pagina's** (`tv/`) — individuele pagina's per TV-model met SEO-vriendelijke slugs

---

## 🛠️ Technische stack

| Onderdeel | Technologie |
|---|---|
| Backend | PHP 8+ |
| Database | MySQL / MariaDB (PDO) |
| Frontend | Vanilla HTML/CSS/JS |
| Fonts | Google Fonts (Inter, Epilogue) |
| CSS | Custom admin stylesheet + component library |
| Hosting | Apache (.htaccess routing) |

---

## 📁 Structuur

```
reparatieplatform/
├── admin/                  # Beveiligde beheerpagina's
│   ├── dashboard.php
│   ├── modellen.php        # TV-modellenbeheer (lazy loading, uitzonderingen)
│   ├── advies-instellingen.php  # Routeregels & merkinstellingen
│   ├── aanvragen.php
│   └── klachten.php
├── api/                    # Interne API-endpoints
│   ├── send-advies.php
│   └── check-repareerbaar.php
├── includes/               # Gedeelde PHP-bestanden
│   ├── db.php
│   ├── functions.php
│   ├── advies_regels.php   # Adviesregels laden/opslaan
│   └── header.php / footer.php
├── assets/                 # CSS, JS, afbeeldingen
├── sql/                    # Database-migraties
├── tv/                     # TV-modelpagina's (SEO)
├── advies.php              # Hoofdformulier
├── taxatie.php
├── reparatie.php
├── index.php
└── .htaccess
```

---

## ⚙️ Installatie

1. **Clone de repository**
   ```bash
   git clone https://github.com/zimpleweb/reparatieplatform.git
   ```

2. **Database aanmaken** — importeer de SQL-bestanden uit `sql/`:
   ```bash
   mysql -u root -p reparatieplatform < sql/schema.sql
   mysql -u root -p reparatieplatform < sql/advies_regels.sql
   ```

3. **Configuratie** — stel de databaseverbinding in via `includes/db.php`

4. **Webserver** — zorg dat Apache mod_rewrite aan staat (`.htaccess` is meegeleverd)

5. **Admin toegang** — log in via `/admin/login.php`

---

## 🔗 Routes & Advieslogica

Het systeem kent 5 mogelijke adviezen:

| Route | Wanneer |
|---|---|
| ✅ **Garantie** | TV jonger dan ingestelde termijn, in NL gekocht, geen uitgesloten klacht |
| 🤝 **Coulance** | TV buiten garantietermijn maar binnen coulanceperiode, kans berekend via prijsmatrix |
| 🔧 **Reparatie** | TV repareerbaar (via DB-vlag), binnen reparatieleeftijd |
| 📋 **Taxatie** | Externe schade (stroom, brand, inbraak, val) |
| ♻️ **Recycling** | TV te oud, niet repareerbaar, of geen andere route van toepassing |

Alle grenzen en merkenlijsten zijn instelbaar via **Admin → Advies instellingen**.

---

## 👤 Beheer

- Admin URL: `/admin/`
- Model-uitzonderingen (afwijkend van merk-standaard) zijn zichtbaar in zowel **TV Modellen** als **Advies instellingen**
- Wijzigingen in advies-instellingen zijn **direct actief** in het adviesformulier

---

## 📄 Licentie

Privé project — © Zimpleweb. Alle rechten voorbehouden.
