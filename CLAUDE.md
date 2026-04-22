## Extra regels voor route-selectie, type-weergave en groene bolletje

* Wanneer een nieuwe inzending met status 'Ontvangen' binnenkomt en in admin/aanvragen.php een route (advies-type) wordt gekozen:
  - Het gekozen type moet correct worden opgeslagen.
  - Het type mag niet leeg blijven staan in het algemene overzicht van aanvragen.php.
  - De route die gekozen wordt bepaalt het type (bijv. Reparatie, Taxatie, Coulance, Recycling).

* In de afzonderlijke inzending (aanvragen.php?id=XX) moet het type zichtbaar zijn naast de status (bijv. "Status: Ontvangen | Type: Reparatie").

* Het groene bolletje (notificatie dat aanvullend formulier is ingevuld) moet ook werken voor Taxatie, Recycling en Coulance wanneer de inzender het betreffende formulier heeft ingevuld. Pas dit aan indien nodig.

* Alle wijzigingen mogen in één commit.
* Volg ook de bestaande “aanvragen refactor”, status-flow en security audit regels.
