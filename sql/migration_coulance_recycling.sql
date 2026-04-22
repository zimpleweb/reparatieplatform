-- ================================================================
-- sql/migration_coulance_recycling.sql
-- Migratie: extra velden voor coulance- en recyclingformulieren
-- Voer dit script EEN keer uit na migration_formulieren.sql.
-- ================================================================

ALTER TABLE `aanvragen`
  ADD COLUMN `verkoopprijs`                VARCHAR(20)   NULL DEFAULT NULL AFTER `aankoopbedrag`,
  ADD COLUMN `winkel_naam`                 VARCHAR(150)  NULL DEFAULT NULL AFTER `naam_verzekeraar`,
  ADD COLUMN `coulance_winkel_resultaat`   TINYINT(1)    NULL DEFAULT NULL AFTER `winkel_naam`,
  ADD COLUMN `coulance_fabrikant_resultaat` TINYINT(1)   NULL DEFAULT NULL AFTER `coulance_winkel_resultaat`,
  ADD COLUMN `recycling_interesse`         TINYINT(1)    NULL DEFAULT NULL AFTER `coulance_fabrikant_resultaat`,
  ADD COLUMN `recycling_ophaalvoorkeur`    VARCHAR(20)   NULL DEFAULT NULL AFTER `recycling_interesse`;
