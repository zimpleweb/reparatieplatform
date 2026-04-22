<?php
function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
function redirect(string $url): void {
    header('Location: ' . $url); exit;
}
function csrf(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function verifyCsrf(string $t): bool {
    return !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $t);
}
function slugify(string $t): string {
    return trim(preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($t, 'UTF-8')), '-');
}
function getMerken(): array {
    return db()->query('SELECT DISTINCT merk FROM tv_modellen WHERE actief=1 ORDER BY merk')
               ->fetchAll(PDO::FETCH_COLUMN);
}
function getSeries(string $merk = ''): array {
    if ($merk) {
        $s = db()->prepare('SELECT DISTINCT serie FROM tv_modellen WHERE merk=? AND actief=1 ORDER BY serie');
        $s->execute([$merk]);
    } else {
        $s = db()->query('SELECT DISTINCT serie FROM tv_modellen WHERE actief=1 ORDER BY serie');
    }
    return $s->fetchAll(PDO::FETCH_COLUMN);
}
function getAllTvs(array $f = []): array {
    $where = ['actief = 1']; $p = [];
    if (!empty($f['merk']))  { $where[] = 'merk = ?';  $p[] = $f['merk']; }
    if (!empty($f['serie'])) { $where[] = 'serie = ?'; $p[] = $f['serie']; }
    if (!empty($f['q'])) {
        $where[] = '(modelnummer LIKE ? OR serie LIKE ? OR merk LIKE ?)';
        $p[] = '%'.$f['q'].'%'; $p[] = '%'.$f['q'].'%'; $p[] = '%'.$f['q'].'%';
    }
    $stmt = db()->prepare(
        'SELECT * FROM tv_modellen WHERE '.implode(' AND ', $where).' ORDER BY merk,serie,modelnummer'
    );
    $stmt->execute($p);
    return $stmt->fetchAll();
}
function getTv(string $slug): ?array {
    $s = db()->prepare('SELECT * FROM tv_modellen WHERE slug=? AND actief=1 LIMIT 1');
    $s->execute([$slug]);
    $tv = $s->fetch();
    if (!$tv) return null;
    $k = db()->prepare(
        'SELECT * FROM klachten WHERE tv_model_id=? ORDER BY FIELD(frequentie,"hoog","middel","laag")'
    );
    $k->execute([$tv['id']]);
    $tv['klachten'] = $k->fetchAll();
    return $tv;
}
function getRelated(int $id, string $merk, string $serie, int $limit = 4): array {
    $s = db()->prepare(
        'SELECT * FROM tv_modellen WHERE actief=1 AND id != ? AND (serie=? OR merk=?) ORDER BY serie=? DESC LIMIT ?'
    );
    $s->execute([$id, $serie, $merk, $serie, $limit]);
    return $s->fetchAll();
}
function isAdmin(): bool {
    return isset($_SESSION['admin']) && $_SESSION['admin'] === true;
}
function requireAdmin(): void {
    if (!isAdmin()) redirect(BASE_URL . '/admin/login.php');
}

/**
 * Haal een waarde op uit de site_settings tabel.
 * Cached na eerste aanroep binnen dezelfde request.
 */
function getSetting(string $key, string $default = ''): string {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            foreach (db()->query("SELECT setting_key, setting_value FROM site_settings") as $row) {
                $cache[$row['setting_key']] = $row['setting_value'];
            }
        } catch (\Throwable $e) {
            // Tabel bestaat nog niet — geeft gewoon default terug
        }
    }
    return $cache[$key] ?? $default;
}

/**
 * Valideer een reCAPTCHA v3 token server-side.
 *
 * Gebruik in API-bestanden:
 *   if (!verifyRecaptcha($_POST['recaptcha_token'] ?? '')) {
 *       http_response_code(429);
 *       exit(json_encode(['error' => 'Spam gedetecteerd.']));
 *   }
 *
 * @param  string $token   Het token uit $_POST['recaptcha_token']
 * @param  string $action  Optioneel: verwachte actienaam voor extra verificatie
 * @return bool            true = mens/doorlaten, false = bot/blokkeren
 */
function verifyRecaptcha(string $token, string $action = ''): bool {
    try {
        $enabled   = getSetting('recaptcha_enabled')    === '1';
        $secretKey = getSetting('recaptcha_secret_key');
        $threshold = (float) getSetting('recaptcha_threshold', '0.5');
    } catch (\Throwable $e) {
        return true; // Instellingen niet bereikbaar — niet blokkeren
    }

    // Uitgeschakeld of geen sleutel/token ingesteld → doorlaten
    if (!$enabled || empty($secretKey) || empty($token)) {
        return true;
    }

    $response = @file_get_contents(
        'https://www.google.com/recaptcha/api/siteverify?' .
        http_build_query(['secret' => $secretKey, 'response' => $token])
    );

    // Google niet bereikbaar → fail open (niet blokkeren)
    if ($response === false) {
        return true;
    }

    $data = json_decode($response, true);

    // Token ongeldig
    if (empty($data['success'])) {
        return false;
    }

    // Score te laag (bot-waarschijnlijkheid te hoog)
    if (isset($data['score']) && $data['score'] < $threshold) {
        return false;
    }

    // Optioneel: actienaam controleren
    if ($action !== '' && isset($data['action']) && $data['action'] !== $action) {
        return false;
    }

    return true;
}