<?php
// Beveiligde foto-endpoint — alleen toegankelijk voor admins en ingelogde portal-gebruikers.
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$isAdmin  = isAdmin();
$isPortal = !empty($_SESSION['portal_case']) && !empty($_SESSION['portal_email']);

if (!$isAdmin && !$isPortal) {
    http_response_code(403);
    exit('Toegang geweigerd.');
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

// Portal-gebruiker: controleer dat het bestand bij hun aanvraag hoort
if (!$isAdmin) {
    $cn = $_SESSION['portal_case'];
    $em = strtolower($_SESSION['portal_email']);
    $fotoKolommen = ['foto_defect', 'foto_label', 'foto_bon', 'foto_toestel', 'foto_extra'];
    $placeholders = implode(' OR ', array_map(fn($k) => "$k = ?", $fotoKolommen));
    $sql  = "SELECT id FROM aanvragen WHERE casenummer = ? AND LOWER(email) = ? AND ($placeholders)";
    $stmt = db()->prepare($sql);
    $stmt->execute([$cn, $em, ...array_fill(0, count($fotoKolommen), $pad)]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        exit('Toegang geweigerd.');
    }
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
