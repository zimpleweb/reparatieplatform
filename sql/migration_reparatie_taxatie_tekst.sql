-- Voeg tekstvelden toe voor Reparatie & Taxatie blokken op TV-pagina's
ALTER TABLE tv_modellen
  ADD COLUMN reparatie_titel VARCHAR(255) NULL DEFAULT NULL AFTER taxatie,
  ADD COLUMN reparatie_tekst TEXT         NULL DEFAULT NULL AFTER reparatie_titel,
  ADD COLUMN taxatie_titel   VARCHAR(255) NULL DEFAULT NULL AFTER reparatie_tekst,
  ADD COLUMN taxatie_tekst   TEXT         NULL DEFAULT NULL AFTER taxatie_titel;
