-- ================================================================
-- sql/advies_regels.sql
-- Advies routing regels – reparatieplatform
-- ================================================================
-- Voer dit script EEN keer uit bij installatie of migratie.
-- Bestaande rijen worden NIET overschreven (INSERT IGNORE).
-- Gebruik de admin-pagina /admin/advies-instellingen.php
-- om waarden aan te passen.
-- ================================================================

CREATE TABLE IF NOT EXISTS `advies_regels` (
  `id`           INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `regel_key`    VARCHAR(80)      NOT NULL,
  `regel_waarde` TEXT             NOT NULL DEFAULT '',
  `type`         ENUM('string','int','float','bool','json') NOT NULL DEFAULT 'string',
  `label`        VARCHAR(120)     NOT NULL DEFAULT '',
  `omschrijving` VARCHAR(255)     NOT NULL DEFAULT '',
  `groep`        VARCHAR(60)      NOT NULL DEFAULT 'algemeen',
  `volgorde`     TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `aangemaakt`   DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `bijgewerkt`   DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_regel_key` (`regel_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- GARANTIE
-- ================================================================
INSERT IGNORE INTO `advies_regels`
  (`regel_key`, `regel_waarde`, `type`, `label`, `omschrijving`, `groep`, `volgorde`)
VALUES
  ('garantie_termijn_jaar',
   '2', 'int',
   'Garantietermijn (jaren)',
   'TV jonger dan X jaar komt in aanmerking voor de garantieroute.',
   'garantie', 10),

  ('garantie_alleen_nl',
   '1', 'bool',
   'Garantie alleen bij aankoop in Nederland',
   'Als 1: buitenlandse aankopen gaan naar reparatie of coulance, niet naar garantie.',
   'garantie', 20),

  ('garantie_merken',
   '[]', 'json',
   'Merken in aanmerking voor garantie',
   'JSON-array van merknamen. Lege array = alle merken toegestaan.',
   'garantie', 30),

  ('garantie_uitsluiten_klachten',
   '["gebarsten_scherm"]', 'json',
   'Klachten uitgesloten van garantie',
   'JSON-array van klachtcodes die nooit in aanmerking komen voor garantie.',
   'garantie', 40);

-- ================================================================
-- COULANCE
-- ================================================================
INSERT IGNORE INTO `advies_regels`
  (`regel_key`, `regel_waarde`, `type`, `label`, `omschrijving`, `groep`, `volgorde`)
VALUES
  ('coulance_min_jaar',
   '2', 'int',
   'Coulance: minimale leeftijd TV (jaren)',
   'TV moet minimaal X jaar oud zijn voor de coulanceroute.',
   'coulance', 10),

  ('coulance_max_jaar',
   '5', 'int',
   'Coulance: maximale leeftijd TV (jaren)',
   'TV mag maximaal X jaar oud zijn voor de coulanceroute.',
   'coulance', 20),

  ('coulance_merken',
   '[]', 'json',
   'Merken in aanmerking voor coulance',
   'JSON-array van merknamen. Lege array = alle merken toegestaan.',
   'coulance', 30),

  ('coulance_uitsluiten_klachten',
   '["gebarsten_scherm"]', 'json',
   'Klachten uitgesloten van coulance',
   'JSON-array van klachtcodes die nooit in aanmerking komen voor coulance.',
   'coulance', 40),

  ('coulance_kans_matrix',
   '[{"prijsklasse":"","basis_kans":40,"per_jaar_aftrek":6},{"prijsklasse":"<500","basis_kans":35,"per_jaar_aftrek":7},{"prijsklasse":"500-1000","basis_kans":50,"per_jaar_aftrek":6},{"prijsklasse":"1000-2000","basis_kans":65,"per_jaar_aftrek":5},{"prijsklasse":">2000","basis_kans":80,"per_jaar_aftrek":4}]',
   'json',
   'Coulance kansmatrix per prijsklasse',
   'Array met per prijsklasse: basis_kans (%) en per_jaar_aftrek (%). Prijsklassen: leeg=onbekend, <500, 500-1000, 1000-2000, >2000.',
   'coulance', 50),

  ('coulance_aftrek_buitenland',
   '30', 'int',
   'Kansaftrek buitenland-aankoop (%)',
   'Wordt afgetrokken van de berekende coulancekans als TV in het buitenland gekocht is.',
   'coulance', 60),

  ('coulance_aftrek_failliet',
   '40', 'int',
   'Kansaftrek failliet verkoper (%)',
   'Wordt afgetrokken van de berekende coulancekans als de verkoper failliet is.',
   'coulance', 70);

-- ================================================================
-- REPARATIE
-- ================================================================
INSERT IGNORE INTO `advies_regels`
  (`regel_key`, `regel_waarde`, `type`, `label`, `omschrijving`, `groep`, `volgorde`)
VALUES
  ('reparatie_min_jaar',
   '2', 'int',
   'Reparatie: minimale leeftijd TV (jaren)',
   'TV moet minimaal X jaar oud zijn voor de reparatieroute.',
   'reparatie', 10),

  ('reparatie_max_jaar',
   '10', 'int',
   'Reparatie: maximale leeftijd TV (jaren)',
   'TV mag maximaal X jaar oud zijn. Ouder = recycling.',
   'reparatie', 20),

  ('reparatie_vereist_repareerbaar',
   '1', 'bool',
   'Reparatieroute vereist repareerbaar=1 in de TV-database',
   'Als 1: alleen modellen met repareerbaar-vlag in tv_modellen komen in aanmerking.',
   'reparatie', 30),

  ('reparatie_merken',
   '[]', 'json',
   'Merken toegestaan voor reparatieroute',
   'JSON-array van merknamen. Lege array = alle repareerbare modellen toegestaan.',
   'reparatie', 40);

-- ================================================================
-- TAXATIE
-- ================================================================
INSERT IGNORE INTO `advies_regels`
  (`regel_key`, `regel_waarde`, `type`, `label`, `omschrijving`, `groep`, `volgorde`)
VALUES
  ('taxatie_bij_schade',
   '1', 'bool',
   'Taxatie automatisch bij externe schade',
   'Als 1: klachten zoals stroom/brand/inbraak/valschade leiden automatisch naar de taxatieroute.',
   'taxatie', 10),

  ('taxatie_merken',
   '[]', 'json',
   'Merken waarvoor taxatie mogelijk is',
   'JSON-array van merknamen. Lege array = alle merken toegestaan.',
   'taxatie', 20);

-- ================================================================
-- RECYCLING
-- ================================================================
INSERT IGNORE INTO `advies_regels`
  (`regel_key`, `regel_waarde`, `type`, `label`, `omschrijving`, `groep`, `volgorde`)
VALUES
  ('recycling_min_jaar',
   '10', 'int',
   'Recycling: minimale leeftijd TV (jaren)',
   'TV ouder dan X jaar gaat automatisch naar recycling als geen andere route van toepassing is.',
   'recycling', 10);

-- ================================================================
-- WEERGAVE-CHECK (uncomment om te controleren)
-- ================================================================
-- SELECT groep, volgorde, regel_key, type, regel_waarde, label
-- FROM   advies_regels
-- ORDER  BY groep, volgorde;
