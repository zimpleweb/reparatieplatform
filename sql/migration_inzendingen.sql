-- ================================================================
-- sql/migration_inzendingen.sql
-- Migratie: casenummer, uitgebreide status en logboek per inzending
-- Voer dit script EEN keer uit na deployment.
-- ================================================================

-- Nieuwe kolommen aan aanvragen toevoegen
ALTER TABLE `aanvragen`
  ADD COLUMN `casenummer`         VARCHAR(20)   NULL DEFAULT NULL AFTER `id`,
  ADD COLUMN `naam`               VARCHAR(100)  NULL DEFAULT NULL AFTER `email`,
  ADD COLUMN `telefoon`           VARCHAR(30)   NULL DEFAULT NULL AFTER `naam`,
  ADD COLUMN `adres`              VARCHAR(200)  NULL DEFAULT NULL AFTER `telefoon`,
  ADD COLUMN `model_repareerbaar` VARCHAR(10)   NULL DEFAULT NULL AFTER `coulance_kans`,
  ADD COLUMN `foto_defect`        VARCHAR(255)  NULL DEFAULT NULL,
  ADD COLUMN `foto_label`         VARCHAR(255)  NULL DEFAULT NULL,
  ADD COLUMN `foto_bon`           VARCHAR(255)  NULL DEFAULT NULL;

-- Unieke index voor casenummer
ALTER TABLE `aanvragen`
  ADD UNIQUE KEY `uq_casenummer` (`casenummer`);

-- Stap 1: status uitbreiden met tijdelijke overgangswaarde zodat bestaande 'nieuw' records bewaard blijven
ALTER TABLE `aanvragen`
  MODIFY COLUMN `status` ENUM(
    'inzending','nieuw','doorgestuurd','aanvraag',
    'coulance','recycling','behandeld','archief'
  ) NOT NULL DEFAULT 'inzending';

-- Stap 2: bestaande 'nieuw' records omzetten
UPDATE `aanvragen` SET `status` = 'inzending' WHERE `status` = 'nieuw';

-- Stap 3: 'nieuw' uit de enum verwijderen
ALTER TABLE `aanvragen`
  MODIFY COLUMN `status` ENUM(
    'inzending','doorgestuurd','aanvraag',
    'coulance','recycling','behandeld','archief'
  ) NOT NULL DEFAULT 'inzending';

-- ================================================================
-- Logboek per inzending
-- ================================================================
CREATE TABLE IF NOT EXISTS `aanvragen_log` (
  `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `aanvraag_id`  INT UNSIGNED  NOT NULL,
  `actie`        VARCHAR(120)  NOT NULL,
  `opmerking`    TEXT          NULL,
  `gedaan_door`  VARCHAR(60)   NOT NULL DEFAULT 'system',
  `aangemaakt`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_aanvraag_id` (`aanvraag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- Upload directory (aanmaken via PHP; dit is alleen documentatie)
-- Pad: uploads/aanvragen/{id}/
-- ================================================================
