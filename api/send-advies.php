<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf($_POST['csrf_token'] ?? '')) {
    redirect('/?error=csrf');
}

$merk         = trim($_POST['merk']         ?? '');
$modelnummer  = trim($_POST['modelnummer']  ?? '');
$aanschafjaar = trim($_POST['aanschafjaar'] ?? '');
$klacht_type  = trim($_POST['klacht_type']  ?? '');
$omschrijving = trim($_POST['omschrijving'] ?? '');
$email        = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);

if (!$email || !$merk || !$modelnummer) {
    redirect('/?error=2');
}

$token = bin2hex(random_bytes(32));

$stmt = db()->prepare('
    INSERT INTO aanvragen (merk, modelnummer, aanschafjaar, klacht_type, omschrijving, email, ip, token)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
');
$stmt->execute([$merk, $modelnummer, $aanschafjaar, $klacht_type, $omschrijving, $email, $_SERVER['REMOTE_ADDR'], $token]);

$klantMail = "Bedankt voor uw aanvraag!\n\n"
    . "Wij hebben uw gegevens ontvangen en sturen u zo snel mogelijk (binnen 1 werkdag) een persoonlijk advies.\n\n"
    . "Uw televisie: $merk $modelnummer\n"
    . "Klacht: $klacht_type\n\n"
    . "Met vriendelijke groet,\nReparatieplatform.nl\nonderdeel van TV Reparatie Service Nederland";

mail(
    $email,
    'Uw adviesaanvraag is ontvangen – Reparatieplatform.nl',
    $klantMail,
    "From: noreply@reparatieplatform.nl\r\nContent-Type: text/plain; charset=UTF-8"
);

$adminMail = "Nieuwe adviesaanvraag ontvangen\n\n"
    . "E-mail: $email\n"
    . "TV: $merk $modelnummer\n"
    . "Aanschafjaar: $aanschafjaar\n"
    . "Klacht: $klacht_type\n\n"
    . "Omschrijving:\n$omschrijving\n\n"
    . "Behandelen via:\nhttps://reparatieplatform.nl/admin/aanvragen.php";

mail(
    'info@tvreparatieservicenederland.nl',
    "Nieuwe aanvraag: $merk $modelnummer",
    $adminMail,
    "From: noreply@reparatieplatform.nl\r\nContent-Type: text/plain; charset=UTF-8"
);

redirect('/?verzonden=1');