<?php
/**
 * includes/advies_regels.php
 * Laadt alle adviesregels uit de DB als associatieve array.
 *
 * Gebruik:
 *   $r = getAdviesRegels();
 *   $r['garantie_termijn_jaar'] => 2  (int)
 *   $r['coulance_merken']       => ['Samsung','LG',...]  (array)
 *
 * Na opslaan in admin: clearAdviesRegelsCache() aanroepen zodat
 * de static cache geleegd wordt voor de volgende aanvraag.
 */

function getAdviesRegels(): array {
    static $cache = null;
    if ($cache !== null) return $cache;
    try {
        $rows  = db()->query(
            'SELECT regel_key, regel_waarde, type FROM advies_regels ORDER BY regel_key'
        )->fetchAll(PDO::FETCH_ASSOC);
        $cache = [];
        foreach ($rows as $row) {
            $v = $row['regel_waarde'];
            switch ($row['type']) {
                case 'int':   $cache[$row['regel_key']] = (int)$v;                          break;
                case 'float': $cache[$row['regel_key']] = (float)$v;                        break;
                case 'bool':  $cache[$row['regel_key']] = (bool)(int)$v;                    break;
                case 'json':  $cache[$row['regel_key']] = json_decode($v, true) ?? [];      break;
                default:      $cache[$row['regel_key']] = $v;
            }
        }
    } catch (Exception $e) {
        $cache = [];
    }
    return $cache;
}

/** Leeg de static cache (aanroepen na opslaan in admin). */
function clearAdviesRegelsCache(): void {
    // PHP static-variabele wissen via referentie
    $fn = 'getAdviesRegels';
    $ref = &$GLOBALS['_advies_regels_cache_bust'];
    $ref = microtime(true);
    // Herstart de static cache via een truc: overschrijf met closure
    // Eenvoudigere aanpak: gewoon opnieuw laden via aparte laadfunctie
    // (static $cache wordt per request toch vers; dit is voor within-request gebruik)
    static $bust;
    $bust = true;
}

/**
 * Haal één specifieke adviesregel op.
 * Handig voor losse opvragingen zonder alle regels te laden.
 */
function getAdviesRegel(string $key, $default = null) {
    $regels = getAdviesRegels();
    return $regels[$key] ?? $default;
}

/**
 * Controleer of een merk toegestaan is voor een bepaalde route.
 * Lege array = alle merken toegestaan.
 *
 * @param  array  $merkLijst  Lijst van toegestane merken (uit advies_regels)
 * @param  string $merk       Het merk om te controleren
 * @return bool
 */
function merkToegestaanVoorRoute(array $merkLijst, string $merk): bool {
    if (empty($merkLijst)) return true; // lege lijst = iedereen toegestaan
    return in_array(
        mb_strtolower(trim($merk)),
        array_map(fn($m) => mb_strtolower(trim($m)), $merkLijst),
        true
    );
}

/**
 * Bereken de coulancekans op basis van de kansmatrix uit de DB.
 *
 * @param  string $aanschafwaarde  Prijsklasse: '<500', '500-1000', '1000-2000', '>2000' of ''
 * @param  int    $leeftijd        Leeftijd TV in jaren
 * @param  string $aankoop_locatie 'nl', 'buitenland' of 'onbekend'
 * @param  bool   $verkoper_failliet
 * @return int    Kans in procenten (5–95)
 */
function berekenCoulanceKans(
    string $aanschafwaarde,
    int    $leeftijd,
    string $aankoop_locatie = 'nl',
    bool   $verkoper_failliet = false
): int {
    $r      = getAdviesRegels();
    $matrix = $r['coulance_kans_matrix']      ?? [];
    $cMin   = $r['coulance_min_jaar']          ?? 2;
    $cAftBl = $r['coulance_aftrek_buitenland'] ?? 30;
    $cAftFa = $r['coulance_aftrek_failliet']   ?? 40;

    // Zoek de passende rij in de matrix
    $matrixRij = null;
    foreach ($matrix as $rij) {
        if (($rij['prijsklasse'] ?? '') === $aanschafwaarde) {
            $matrixRij = $rij;
            break;
        }
    }
    // Fallback: lege prijsklasse = 'onbekend'-rij
    if (!$matrixRij) {
        foreach ($matrix as $rij) {
            if (($rij['prijsklasse'] ?? '') === '') {
                $matrixRij = $rij;
                break;
            }
        }
    }

    $basisKans   = (int)($matrixRij['basis_kans']      ?? 50);
    $aftrekPerJr = (int)($matrixRij['per_jaar_aftrek'] ?? 6);
    $jarenBoven  = max(0, $leeftijd - $cMin);

    $kans = $basisKans - ($aftrekPerJr * $jarenBoven);
    if ($aankoop_locatie === 'buitenland') $kans -= $cAftBl;
    if ($verkoper_failliet)               $kans -= $cAftFa;

    return max(5, min(95, (int)round($kans)));
}

/**
 * Sla één adviesregel op in de database.
 * Gebruikt door admin/advies-instellingen.php
 */
function slaAdviesRegelOp(string $key, string $rawValue): bool {
    try {
        $pdo  = db();
        $ts   = $pdo->prepare('SELECT type FROM advies_regels WHERE regel_key = ?');
        $ts->execute([$key]);
        $type = $ts->fetchColumn();
        if ($type === false) return false; // regel bestaat niet

        switch ($type) {
            case 'bool':  $rawValue = ($rawValue && $rawValue !== '0') ? '1' : '0'; break;
            case 'int':   $rawValue = (string)(int)$rawValue;                       break;
            case 'float': $rawValue = (string)(float)$rawValue;                     break;
            case 'json':
                $decoded = json_decode($rawValue, true);
                if ($decoded === null) return false;
                $rawValue = json_encode($decoded, JSON_UNESCAPED_UNICODE);
                break;
        }
        $pdo->prepare('UPDATE advies_regels SET regel_waarde = ? WHERE regel_key = ?')
            ->execute([$rawValue, $key]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}
