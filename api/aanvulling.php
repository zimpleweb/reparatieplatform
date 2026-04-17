<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf($_POST['csrf_token'] ?? '')) {
    redirect(BASE_URL . '/mijn-aanvraag.php?error=csrf');
}

$id    = (int)  ($_POST['aanvraag_id'] ?? 0);
$cn    = trim(  $_POST['casenummer']   ?? '');
$actie = trim(  $_POST['actie']        ?? '');
$naam  = trim(  $_POST['naam']         ?? '');
$tel   = trim(  $_POST['telefoon']     ?? '');
$adr   = trim(  $_POST['adres']        ?? '');

if (!$id || !$cn) redirect(BASE_URL . '/mijn-aanvraag.php?error=ongeldig');

// Haal de aanvraag op en controleer casenummer
$stmt = db()->prepare('SELECT id, status, advies_type, email, casenummer FROM aanvragen WHERE id=? AND casenummer=?');
$stmt->execute([$id, $cn]);
$rij = $stmt->fetch();

if (!$rij) redirect(BASE_URL . '/mijn-aanvraag.php?error=ongeldig');

// ── Speciale actie: coulance mislukt → omzetten naar reparatie ───
if ($actie === 'coulance_naar_reparatie') {
    if ($rij['status'] !== 'coulance') redirect(BASE_URL . '/advies.php?error=ongeldig');
    db()->prepare("UPDATE aanvragen SET status='doorgestuurd', advies_type='reparatie' WHERE id=?")
       ->execute([$id]);
    try {
        db()->prepare('INSERT INTO aanvragen_log (aanvraag_id, actie, gedaan_door) VALUES (?,?,?)')
           ->execute([$id, 'Coulance mislukt — omgezet naar reparatieaanvraag', 'klant']);
    } catch (\PDOException $e) {}
    redirect(BASE_URL . '/mijn-aanvraag.php?verzonden=3');
}

// ── Aanvullende gegevens (doorgestuurd of recycling_aanvraag) ────
$toegestaanStatussen = ['doorgestuurd', 'recycling'];
if (!in_array($rij['status'], $toegestaanStatussen) && $actie !== 'recycling_aanvraag') {
    redirect(BASE_URL . '/mijn-aanvraag.php?error=ongeldig');
}
if (!$naam || !$tel || !$adr) {
    redirect(BASE_URL . '/mijn-aanvraag.php?error=onvolledig');
}

// ── Fotoupload ───────────────────────────────────────────────────
$uploadDir = __DIR__ . '/../uploads/aanvragen/' . $id . '/';
if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);

$uploads = [];
foreach (['foto_defect', 'foto_label', 'foto_bon'] as $veld) {
    if (!isset($_FILES[$veld]) || $_FILES[$veld]['error'] !== UPLOAD_ERR_OK) continue;
    $mime = mime_content_type($_FILES[$veld]['tmp_name']);
    if (!in_array($mime, ['image/jpeg','image/png','image/webp','image/gif'])) continue;
    if ($_FILES[$veld]['size'] > 10 * 1024 * 1024) continue;
    $ext  = strtolower(pathinfo($_FILES[$veld]['name'], PATHINFO_EXTENSION));
    $naam_bestand = bin2hex(random_bytes(10)) . '.' . $ext;
    if (move_uploaded_file($_FILES[$veld]['tmp_name'], $uploadDir . $naam_bestand)) {
        $uploads[$veld] = 'uploads/aanvragen/' . $id . '/' . $naam_bestand;
    }
}

// ── Status bepalen ───────────────────────────────────────────────
$nieuweStatus = ($actie === 'recycling_aanvraag') ? 'recycling' : 'aanvraag';
$logActie     = ($actie === 'recycling_aanvraag')
    ? 'Recyclingverzoek ingediend door klant'
    : ucfirst($rij['advies_type'] ?? 'aanvraag') . ' ingediend door klant';

// ── Bijwerken ────────────────────────────────────────────────────
$sql = 'UPDATE aanvragen SET naam=?, telefoon=?, adres=?, status=?';
$params = [$naam, $tel, $adr, $nieuweStatus];

foreach (['foto_defect','foto_label','foto_bon'] as $v) {
    if (isset($uploads[$v])) { $sql .= ", $v=?"; $params[] = $uploads[$v]; }
}
$sql .= ' WHERE id=?';
$params[] = $id;

db()->prepare($sql)->execute($params);

try {
    db()->prepare('INSERT INTO aanvragen_log (aanvraag_id, actie, gedaan_door) VALUES (?,?,?)')
       ->execute([$id, $logActie, 'klant']);
} catch (\PDOException $e) {}

redirect(BASE_URL . '/mijn-aanvraag.php?verzonden=2');
