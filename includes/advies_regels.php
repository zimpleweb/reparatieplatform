<?php
/**
 * includes/advies_regels.php
 * Laadt alle adviesregels uit de DB als associatieve array.
 * Gebruik: $r = getAdviesRegels();
 *          $r['garantie_termijn_jaar'] => '2'
 */
function getAdviesRegels(): array {
    static $cache = null;
    if ($cache !== null) return $cache;
    try {
        $rows  = db()->query('SELECT regel_key, regel_waarde, type FROM advies_regels')->fetchAll(PDO::FETCH_ASSOC);
        $cache = [];
        foreach ($rows as $row) {
            $v = $row['regel_waarde'];
            switch ($row['type']) {
                case 'int':   $cache[$row['regel_key']] = (int)$v;           break;
                case 'float': $cache[$row['regel_key']] = (float)$v;         break;
                case 'bool':  $cache[$row['regel_key']] = (bool)(int)$v;     break;
                case 'json':  $cache[$row['regel_key']] = json_decode($v, true) ?? []; break;
                default:      $cache[$row['regel_key']] = $v;
            }
        }
    } catch (Exception $e) {
        $cache = [];
    }
    return $cache;
}
