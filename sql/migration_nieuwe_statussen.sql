-- ================================================================
-- sql/migration_nieuwe_statussen.sql
-- Migratie: nieuwe status-waarden + gekozen_advies kolom
-- Uitvoeren via phpMyAdmin of CLI, één keer na deployment.
-- ================================================================

-- Stap 1: status ENUM uitbreiden met afwachting/ingevuld-waarden per adviestype
-- De oude waarden (doorgestuurd, aanvraag, coulance, recycling, behandeld, archief)
-- blijven staan voor backwards-compatibiliteit.
ALTER TABLE `aanvragen`
  MODIFY COLUMN `status` ENUM(
    'inzending',
    'reparatie_afwachting',  'reparatie_ingevuld',
    'taxatie_afwachting',    'taxatie_ingevuld',
    'garantie_afwachting',   'garantie_ingevuld',
    'coulance_afwachting',   'coulance_ingevuld',
    'recycling_afwachting',  'recycling_ingevuld',
    'afgewezen',
    'doorgestuurd', 'aanvraag', 'coulance', 'recycling', 'behandeld', 'archief'
  ) NOT NULL DEFAULT 'inzending';

-- Stap 2: gekozen_advies kolom toevoegen
-- Slaat de admin-keuze op (reparatie/taxatie/garantie/coulance/recycling/afgewezen)
-- los van de automatisch berekende geadviseerde_route.
ALTER TABLE `aanvragen`
  ADD COLUMN `gekozen_advies` VARCHAR(30) NULL DEFAULT NULL
  AFTER `advies_type`;
