-- ================================================================
-- sql/migration_advies_type.sql
-- Migratie: advies_type kolom toevoegen aan aanvragen
-- Voer dit script EEN keer uit na migration_inzendingen.sql.
-- ================================================================

ALTER TABLE `aanvragen`
  ADD COLUMN `advies_type` VARCHAR(30) NULL DEFAULT NULL AFTER `geadviseerde_route`;
