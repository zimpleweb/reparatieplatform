<?php
// Beveiligde foto-endpoint — alleen toegankelijk voor ingelogde admins.
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isAdmin()) {
    header('Location: /admin/login.php');
    exit;
}

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
header('Cache-Control: private, max-age=3600');
header('X-Content-Type-Options: nosniff');
readfile($absPath);
