<?php
ob_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

error_reporting(0);
ini_set('display_errors', 0);

ob_clean();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');

// ── Rate limiting: max 120 zoekopdrachten per IP per minuut ──
$rlKey = 'ac_' . (filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP) !== false
    ? $_SERVER['REMOTE_ADDR'] : 'unknown');
$rl = rateLimitBekijk($rlKey);
if ($rl['geblokkeerd']) {
    http_response_code(429);
    echo '[]';
    exit;
}
rateLimitMislukt($rlKey, 120, 60);

$q = trim($_GET['q'] ?? '');

if (strlen($q) < 2) {
    echo '[]';
    exit;
}

try {
    $stmt = db()->prepare("
        SELECT modelnummer, merk, serie, slug
        FROM tv_modellen
        WHERE actief = 1
          AND (
            modelnummer LIKE :q
            OR serie     LIKE :q2
            OR merk      LIKE :q3
          )
        ORDER BY
          CASE WHEN modelnummer LIKE :q4 THEN 0 ELSE 1 END,
          modelnummer
        LIMIT 20
    ");

    $like = '%' . $q . '%';
    $stmt->bindValue(':q',  $like);
    $stmt->bindValue(':q2', $like);
    $stmt->bindValue(':q3', $like);
    $stmt->bindValue(':q4', $q . '%');
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(array_values($rows), JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo '[]';
}
exit;