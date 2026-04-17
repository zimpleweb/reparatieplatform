-- ================================================================
-- sql/migration_formulieren.sql
-- Migratie: uitgebreide velden voor reparatie- en taxatieformulieren
-- Voer dit script EEN keer uit na deployment.
-- ================================================================

ALTER TABLE `aanvragen`
  ADD COLUMN `plaats`            VARCHAR(100)   NULL DEFAULT NULL AFTER `adres`,
  ADD COLUMN `postcode`          VARCHAR(10)    NULL DEFAULT NULL AFTER `plaats`,
  ADD COLUMN `serienummer`       VARCHAR(100)   NULL DEFAULT NULL AFTER `postcode`,
  ADD COLUMN `reden_schade`      VARCHAR(100)   NULL DEFAULT NULL AFTER `serienummer`,
  ADD COLUMN `aankoopbedrag`     VARCHAR(20)    NULL DEFAULT NULL AFTER `reden_schade`,
  ADD COLUMN `aankoopdatum`      DATE           NULL DEFAULT NULL AFTER `aankoopbedrag`,
  ADD COLUMN `heeft_bon`         TINYINT(1)     NULL DEFAULT NULL AFTER `aankoopdatum`,
  ADD COLUMN `naam_verzekeraar`  VARCHAR(150)   NULL DEFAULT NULL AFTER `heeft_bon`,
  ADD COLUMN `polisnummer`       VARCHAR(100)   NULL DEFAULT NULL AFTER `naam_verzekeraar`,
  ADD COLUMN `foto_toestel`      VARCHAR(255)   NULL DEFAULT NULL AFTER `foto_bon`,
  ADD COLUMN `foto_extra`        VARCHAR(255)   NULL DEFAULT NULL AFTER `foto_toestel`;
