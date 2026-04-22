<?php
// Beveiligde foto-endpoint — alleen toegankelijk voor ingelogde admins.
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

requireAdmin();

$pad = trim($_GET['pad'] ?? '');
if (!$pad) { http_response_code(400); exit('Pad ontbreekt.'); }

$uploadBase = realpath(__DIR__ . '/../uploads/aanvragen');
if (!$uploadBase) { http_response_code(404); exit('Bestand niet gevonden.'); }

$absPath = realpath(__DIR__ . '/../' . $pad);
if (!$absPath || strpos($absPath, $uploadBase . DIRECTORY_SEPARATOR) !== 0 || !is_file($absPath)) {
    http_response_code(404);
    exit('Bestand niet gevonden.');
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $absPath);
finfo_close($finfo);

$allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
if (!in_array($mime, $allowedMimes, true)) {
    http_response_code(403);
    exit('Bestandstype niet toegestaan.');
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($absPath));
header('Content-Disposition: inline; filename="' . basename($absPath) . '"');
header('Cache-Control: private, no-store');
readfile($absPath);
