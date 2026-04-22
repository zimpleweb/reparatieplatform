## Extra regels voor Security Audit (professioneel niveau)

* Doe een professionele AppSec audit volgens OWASP Top 10 + PHP-specifieke risico’s (SQLi, XSS, CSRF, auth bypass, IDOR, session hijacking, input validation, error disclosure, etc.).
* Prioriteit: volledige admin/ map (incl. login.php, 2fa-setup.php, aanvragen.php, admins.php, database.php, advies-instellingen.php), volledige api/ map, includes/db.php, includes/functions.php én data-verwerkende bestanden (index.php, advies.php, mijn-aanvraag.php, taxatie.php, reparatie.php).
* Output ALLEEN Critical & High findings eerst. Houd elke beschrijving kort (max 2 zinnen + file + regelindicatie).
* Gebruik altijd unified diff voor fixes.
* Houd alle wijzigingen atomic (één commit per kleine stap / per issue).
* Volg ook de bestaande “aanvragen refactor” regels waar relevant.
* Begin altijd met een korte summary lijst. Wacht op mijn commando “fix [nummer]” of “deep dive [nummer]” voordat je een patch maakt en commit.
