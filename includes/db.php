<?php
/**
 * includes/db.php
 * Database verbinding (SQLite) + constanten.
 * Dit bestand staat in .gitignore — bevat omgeving-specifieke instellingen.
 */

define('BASE_URL', '');

$_DB_PATH = __DIR__ . '/../data/reparatieplatform.sqlite';

function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    global $_DB_PATH;
    $pdo = new PDO('sqlite:' . $_DB_PATH, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // SUBSTRING_INDEX(str, delim, count) — MySQL-compatibele functie voor SQLite
    $pdo->sqliteCreateFunction('SUBSTRING_INDEX', function (string $str, string $delim, int $count): string {
        $parts = explode($delim, $str);
        if ($count > 0) {
            return implode($delim, array_slice($parts, 0, $count));
        }
        return implode($delim, array_slice($parts, max(0, count($parts) + $count)));
    }, 3);

    _dbInit($pdo);
    return $pdo;
}

function _dbInit(PDO $pdo): void {
    $pdo->exec("PRAGMA journal_mode=WAL");
    $pdo->exec("PRAGMA foreign_keys=ON");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS aanvragen (
            id               INTEGER PRIMARY KEY AUTOINCREMENT,
            casenummer       VARCHAR(20)  UNIQUE,
            merk             VARCHAR(100),
            modelnummer      VARCHAR(100),
            aanschafjaar     INTEGER,
            aanschafwaarde   VARCHAR(20),
            aankoop_locatie  VARCHAR(20)  DEFAULT 'nl',
            situatie         VARCHAR(20),
            klacht_type      VARCHAR(80),
            omschrijving     TEXT,
            email            VARCHAR(200),
            naam             VARCHAR(100),
            telefoon         VARCHAR(30),
            adres            VARCHAR(200),
            geadviseerde_route VARCHAR(30),
            coulance_kans    INTEGER      DEFAULT 0,
            model_repareerbaar VARCHAR(10),
            foto_defect      VARCHAR(255),
            foto_label       VARCHAR(255),
            foto_bon         VARCHAR(255),
            ip               VARCHAR(45),
            token            VARCHAR(128),
            advies_type      VARCHAR(30),
            status           TEXT         NOT NULL DEFAULT 'inzending',
            aangemaakt       DATETIME     DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS aanvragen_log (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            aanvraag_id  INTEGER      NOT NULL,
            actie        VARCHAR(120) NOT NULL,
            opmerking    TEXT,
            gedaan_door  VARCHAR(60)  NOT NULL DEFAULT 'systeem',
            aangemaakt   DATETIME     DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS advies_regels (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            regel_key     VARCHAR(80)  NOT NULL UNIQUE,
            regel_waarde  TEXT         NOT NULL DEFAULT '',
            type          TEXT         NOT NULL DEFAULT 'string',
            label         VARCHAR(120) NOT NULL DEFAULT '',
            omschrijving  VARCHAR(255) NOT NULL DEFAULT '',
            groep         VARCHAR(60)  NOT NULL DEFAULT 'algemeen',
            volgorde      INTEGER      NOT NULL DEFAULT 0,
            aangemaakt    DATETIME     DEFAULT CURRENT_TIMESTAMP,
            bijgewerkt    DATETIME     DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tv_modellen (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            merk         VARCHAR(100),
            serie        VARCHAR(100),
            modelnummer  VARCHAR(100),
            repareerbaar INTEGER      DEFAULT 1,
            taxatie      INTEGER      DEFAULT 0,
            actief       INTEGER      DEFAULT 1,
            slug         VARCHAR(200) UNIQUE,
            aangemaakt   DATETIME     DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS klachten (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            tv_model_id  INTEGER NOT NULL,
            omschrijving TEXT,
            frequentie   TEXT DEFAULT 'middel',
            aangemaakt   DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admins (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            gebruiker  VARCHAR(80) NOT NULL UNIQUE,
            wachtwoord VARCHAR(255) NOT NULL,
            aangemaakt DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    _seedAdviesRegels($pdo);
    _seedAdmin($pdo);
}

function _seedAdviesRegels(PDO $pdo): void {
    $regels = [
        // garantie
        ['garantie_termijn_jaar',       '2',      'int',    'Garantietermijn (jaren)',                       'TV jonger dan X jaar komt in aanmerking voor de garantieroute.',                                                                                                                                                                                                                                  'garantie', 10],
        ['garantie_alleen_nl',          '1',      'bool',   'Garantie alleen bij aankoop in Nederland',      'Als 1: buitenlandse aankopen gaan naar reparatie of coulance, niet naar garantie.',                                                                                                                                                                                                              'garantie', 20],
        ['garantie_merken',             '[]',     'json',   'Merken in aanmerking voor garantie',            'JSON-array van merknamen. Lege array = alle merken toegestaan.',                                                                                                                                                                                                                                 'garantie', 30],
        ['garantie_uitsluiten_klachten','["gebarsten_scherm"]','json','Klachten uitgesloten van garantie',   'JSON-array van klachtcodes die nooit in aanmerking komen voor garantie.',                                                                                                                                                                                                                        'garantie', 40],
        // coulance
        ['coulance_min_jaar',           '2',      'int',    'Coulance: minimale leeftijd TV (jaren)',        'TV moet minimaal X jaar oud zijn voor de coulanceroute.',                                                                                                                                                                                                                                        'coulance', 10],
        ['coulance_max_jaar',           '5',      'int',    'Coulance: maximale leeftijd TV (jaren)',        'TV mag maximaal X jaar oud zijn voor de coulanceroute.',                                                                                                                                                                                                                                         'coulance', 20],
        ['coulance_merken',             '[]',     'json',   'Merken in aanmerking voor coulance',            'JSON-array van merknamen. Lege array = alle merken toegestaan.',                                                                                                                                                                                                                                 'coulance', 30],
        ['coulance_uitsluiten_klachten','["gebarsten_scherm"]','json','Klachten uitgesloten van coulance',   'JSON-array van klachtcodes die nooit in aanmerking komen voor coulance.',                                                                                                                                                                                                                        'coulance', 40],
        ['coulance_kans_matrix',        '[{"prijsklasse":"","basis_kans":40,"per_jaar_aftrek":6},{"prijsklasse":"<500","basis_kans":35,"per_jaar_aftrek":7},{"prijsklasse":"500-1000","basis_kans":50,"per_jaar_aftrek":6},{"prijsklasse":"1000-2000","basis_kans":65,"per_jaar_aftrek":5},{"prijsklasse":">2000","basis_kans":80,"per_jaar_aftrek":4}]', 'json', 'Coulance kansmatrix per prijsklasse', 'Array met per prijsklasse: basis_kans (%) en per_jaar_aftrek (%).',                                                                                                                                        'coulance', 50],
        ['coulance_aftrek_buitenland',  '30',     'int',    'Kansaftrek buitenland-aankoop (%)',             'Wordt afgetrokken van de berekende coulancekans als TV in het buitenland gekocht is.',                                                                                                                                                                                                           'coulance', 60],
        ['coulance_aftrek_failliet',    '40',     'int',    'Kansaftrek failliet verkoper (%)',              'Wordt afgetrokken als de verkoper failliet is gegaan.',                                                                                                                                                                                                                                          'coulance', 70],
        // reparatie
        ['reparatie_min_jaar',          '2',      'int',    'Reparatie: minimale leeftijd TV (jaren)',       'TV moet minimaal X jaar oud zijn voor de reparatieroute.',                                                                                                                                                                                                                                       'reparatie', 10],
        ['reparatie_max_jaar',          '10',     'int',    'Reparatie: maximale leeftijd TV (jaren)',       'TV mag maximaal X jaar oud zijn voor de reparatieroute.',                                                                                                                                                                                                                                        'reparatie', 20],
        ['reparatie_vereist_repareerbaar','1',    'bool',   'Reparatie vereist repareerbaar-vlag',          'Als 1: model moet repareerbaar=1 hebben in de database. Als 0: alle modellen.',                                                                                                                                                                                                                  'reparatie', 30],
        ['reparatie_merken',            '[]',     'json',   'Merken in aanmerking voor reparatie',          'JSON-array van merknamen. Lege array = alle merken toegestaan.',                                                                                                                                                                                                                                  'reparatie', 40],
        // taxatie
        ['taxatie_bij_schade',          '1',      'bool',   'Taxatie bij externe schade',                   'Als 1: situatie=schade leidt altijd naar taxatie.',                                                                                                                                                                                                                                              'taxatie',  10],
        ['taxatie_merken',              '[]',     'json',   'Merken in aanmerking voor taxatie',            'JSON-array van merknamen. Lege array = alle merken toegestaan.',                                                                                                                                                                                                                                  'taxatie',  20],
        // recycling
        ['recycling_min_jaar',          '10',     'int',    'Recycling: minimale leeftijd TV (jaren)',      'TV van minimaal X jaar oud gaat naar recycling als geen andere route van toepassing is.',                                                                                                                                                                                                         'recycling',10],
    ];

    $stmt = $pdo->prepare(
        "INSERT OR IGNORE INTO advies_regels (regel_key, regel_waarde, type, label, omschrijving, groep, volgorde)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    foreach ($regels as $r) {
        $stmt->execute($r);
    }
}

function _seedAdmin(PDO $pdo): void {
    $check = $pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();
    if ($check == 0) {
        $pdo->prepare("INSERT INTO admins (gebruiker, wachtwoord) VALUES (?, ?)")
            ->execute(['admin', password_hash('admin', PASSWORD_DEFAULT)]);
    }
}
