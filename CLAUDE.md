## Extra regels voor Volledige Security Audit 2026

* Voer een professionele security audit uit op de gehele site (OWASP Top 10 2021 + PHP-specifieke risico’s + DDoS-bescherming).
* Prioriteit: admin/, api/, includes/, alle data-verwerkende bestanden (index.php, advies.php, mijn-aanvraag.php, tv/index.php, reparatie.php, taxatie.php, etc.).
* Let specifiek op:
  - SQL Injection, XSS, CSRF, IDOR, auth bypass, session security
  - Onveilige file uploads
  - Rate limiting (brute force, login, API)
  - DDoS-mitigatie (rate limits, bot detection, CAPTCHA waar nodig)
  - Error disclosure, sensitive data exposure
  - Input validation & sanitization
* Geef eventuele SQL-wijzigingen apart in SQL-code voor phpMyAdmin.
* Alle wijzigingen in **één enkele commit** (of logische groepen als het te groot wordt).

Maak dit als één atomic commit met unified diff voor CLAUDE.md.
