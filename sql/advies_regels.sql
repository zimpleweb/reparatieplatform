-- ============================================================
-- Advies Regels: installeer dit eenmalig op de server
-- ============================================================

CREATE TABLE IF NOT EXISTS advies_regels (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  regel_key   VARCHAR(80)  NOT NULL UNIQUE,
  regel_waarde TEXT        NOT NULL,
  label       VARCHAR(120) NOT NULL,
  omschrijving VARCHAR(255) DEFAULT '',
  type        ENUM('int','float','json','text','bool') NOT NULL DEFAULT 'text',
  updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Garantie ───────────────────────────────────────────────────
INSERT INTO advies_regels (regel_key, regel_waarde, label, omschrijving, type) VALUES
('garantie_termijn_jaar', '2',
  'Garantietermijn (jaren)',
  'Maximale leeftijd televisie (in jaren) om in aanmerking te komen voor garantie.',
  'int'),
('garantie_alleen_nl', '1',
  'Garantie alleen bij aankoop in Nederland',
  'Als 1: garantieroute alleen voor in Nederland gekochte televisies.',
  'bool'),
('garantie_uitsluiten_klachten', '["gebarsten_scherm"]',
  'Klachten uitgesloten van garantie (JSON array)',
  'Klachtcodes die nooit in aanmerking komen voor garantie.',
  'json'),
-- ── Coulance ──────────────────────────────────────────────────
('coulance_min_jaar', '2',
  'Coulance: minimale leeftijd (jaren)',
  'TV moet minimaal X jaar oud zijn voor coulancetraject.',
  'int'),
('coulance_max_jaar', '5',
  'Coulance: maximale leeftijd (jaren)',
  'TV mag maximaal X jaar oud zijn voor coulancetraject.',
  'int'),
('coulance_uitsluiten_klachten', '["gebarsten_scherm"]',
  'Klachten uitgesloten van coulance (JSON array)',
  'Klachtcodes die nooit in aanmerking komen voor coulance.',
  'json'),
-- Kansmatrix: [{ "prijsklasse": "<500", "basis_kans": 30, "per_jaar_aftrek": 8 }, ...]
('coulance_kans_matrix', '[{"prijsklasse":"<500","basis_kans":30,"per_jaar_aftrek":8},{"prijsklasse":"500-1000","basis_kans":55,"per_jaar_aftrek":6},{"prijsklasse":"1000-2000","basis_kans":70,"per_jaar_aftrek":5},{"prijsklasse":">2000","basis_kans":85,"per_jaar_aftrek":4},{"prijsklasse":"","basis_kans":50,"per_jaar_aftrek":6}]',
  'Coulance kansmatrix per prijsklasse (JSON)',
  'Per prijsklasse: basis_kans (%) en aftrek per jaar dat TV ouder is dan coulance_min_jaar. Prijsklasse leeg = onbekend.',
  'json'),
('coulance_aftrek_buitenland', '30',
  'Coulance: kansaftrek bij aankoop buitenland (%)',
  'Percentage dat van de coulancekans wordt afgetrokken bij aankoop buiten Nederland.',
  'int'),
('coulance_aftrek_failliet', '40',
  'Coulance: kansaftrek bij failliet verkoper (%)',
  'Percentage dat van de coulancekans wordt afgetrokken als de verkoper failliet is.',
  'int'),
-- ── Reparatie ─────────────────────────────────────────────────
('reparatie_min_jaar', '2',
  'Reparatie: minimale leeftijd TV (jaren)',
  'TV moet minimaal X jaar oud zijn voor reparatieroute (als garantie/coulance niet van toepassing is).',
  'int'),
('reparatie_max_jaar', '10',
  'Reparatie: maximale leeftijd TV (jaren)',
  'TV mag maximaal X jaar oud zijn voor reparatieroute.',
  'int'),
('reparatie_vereist_repareerbaar', '1',
  'Reparatie vereist repareerbaar=1 in database',
  'Als 1: reparatieroute alleen voor modellen met repareerbaar=1 in de TV-database.',
  'bool'),
-- ── Recycling / Second life ───────────────────────────────────
('recycling_min_jaar', '10',
  'Recycling: minimale leeftijd TV (jaren)',
  'TV moet minimaal X jaar oud zijn voor recyclingroute.',
  'int'),
-- ── Taxatie ───────────────────────────────────────────────────
('taxatie_bij_schade', '1',
  'Taxatie automatisch bij externe schade',
  'Als 1: bij situatie=schade altijd doorsturen naar taxatieroute.',
  'bool'),
('taxatie_merken', '["Samsung","Philips","Sony","LG","Panasonic","Hisense","TCL","Anders"]',
  'Merken waarvoor taxatie mogelijk is (JSON array)',
  'Lijst van merken waarvoor een taxatierapport opgesteld kan worden.',
  'json')
ON DUPLICATE KEY UPDATE regel_waarde = VALUES(regel_waarde);
