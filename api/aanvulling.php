<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf($_POST['csrf_token'] ?? '')) {
    redirect(BASE_URL . '/mijn-aanvraag.php?error=csrf');
}

$id    = (int) ($_POST['aanvraag_id'] ?? 0);
$cn    = trim( $_POST['casenummer']   ?? '');
$actie = trim( $_POST['actie']        ?? '');

if (!$id || !$cn) redirect(BASE_URL . '/mijn-aanvraag.php?error=ongeldig');

$stmt = db()->prepare('SELECT id, status, advies_type, email, casenummer FROM aanvragen WHERE id=? AND casenummer=?');
$stmt->execute([$id, $cn]);
$rij = $stmt->fetch();

if (!$rij) redirect(BASE_URL . '/mijn-aanvraag.php?error=ongeldig');

// ── Bericht van klant (elke status) ─────────────────────────────
if ($actie === 'bericht') {
    $tekst = trim($_POST['bericht_tekst'] ?? '');
    if ($tekst) {
        try {
            db()->prepare('INSERT INTO aanvragen_log (aanvraag_id, actie, opmerking, gedaan_door) VALUES (?,?,?,?)')
               ->execute([$id, 'Bericht klant', $tekst, 'klant']);
        } catch (\PDOException $e) {}
        try {
            $aanvraag = db()->prepare('SELECT casenummer, email, merk, modelnummer FROM aanvragen WHERE id=?');
            $aanvraag->execute([$id]);
            $av = $aanvraag->fetch();
            if ($av) {
                $mailVars = [
                    'casenummer'   => $av['casenummer'] ?? $cn,
                    'email'        => $av['email'] ?? '',
                    'merk'         => $av['merk'] ?? '',
                    'modelnummer'  => $av['modelnummer'] ?? '',
                    'datum_bericht'=> date('d-m-Y H:i'),
                    'chatbericht'  => $tekst,
                ];
                $adminEmails = db()->query("SELECT email FROM admins WHERE email IS NOT NULL AND email != ''")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($adminEmails as $adminEmail) {
                    @sendMail($adminEmail, 'admin_nieuw_chatbericht', $mailVars);
                }
            }
        } catch (\PDOException $e) {}
    }
    redirect(BASE_URL . '/mijn-aanvraag.php');
}

// ── Coulance mislukt → omzetten naar reparatie ───────────────────
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

// ── Recyclingverzoek ─────────────────────────────────────────────
if ($actie === 'recycling_aanvraag') {
    if ($rij['status'] !== 'recycling') redirect(BASE_URL . '/mijn-aanvraag.php?error=ongeldig');
    $naam = trim($_POST['naam']     ?? '');
    $tel  = trim($_POST['telefoon'] ?? '');
    $adr  = trim($_POST['adres']    ?? '');
    if (!$naam || !$tel || !$adr) redirect(BASE_URL . '/mijn-aanvraag.php?error=onvolledig');
    db()->prepare("UPDATE aanvragen SET naam=?, telefoon=?, adres=?, status='recycling' WHERE id=?")
       ->execute([$naam, $tel, $adr, $id]);
    try {
        db()->prepare('INSERT INTO aanvragen_log (aanvraag_id, actie, gedaan_door) VALUES (?,?,?)')
           ->execute([$id, 'Recyclingverzoek ingediend door klant', 'klant']);
    } catch (\PDOException $e) {}
    redirect(BASE_URL . '/mijn-aanvraag.php?verzonden=2');
}

// ── Aanvulling: alleen bij doorgestuurd ──────────────────────────
if ($rij['status'] !== 'doorgestuurd') {
    redirect(BASE_URL . '/mijn-aanvraag.php?error=ongeldig');
}

$type      = trim($_POST['type'] ?? '');
$uploadDir = __DIR__ . '/../uploads/aanvragen/' . $id . '/';
if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);

function stuurAdminFormulierMail(int $id, string $aanvraagType): void {
    try {
        $s = db()->prepare('SELECT casenummer, email, merk, modelnummer FROM aanvragen WHERE id=?');
        $s->execute([$id]);
        $av = $s->fetch();
        if (!$av) return;
        $mailVars = [
            'casenummer'   => $av['casenummer'] ?? '',
            'email'        => $av['email'] ?? '',
            'merk'         => $av['merk'] ?? '',
            'modelnummer'  => $av['modelnummer'] ?? '',
            'aanvraag_type'=> ucfirst($aanvraagType),
        ];
        $adminEmails = db()->query("SELECT email FROM admins WHERE email IS NOT NULL AND email != ''")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($adminEmails as $adminEmail) {
            @sendMail($adminEmail, 'admin_formulier_ingevuld', $mailVars);
        }
    } catch (\PDOException $e) {}
}

function uploadFoto(string $veld, string $uploadDir, int $id): ?string {
    if (!isset($_FILES[$veld]) || $_FILES[$veld]['error'] !== UPLOAD_ERR_OK) return null;
    $mime = mime_content_type($_FILES[$veld]['tmp_name']);
    if (!in_array($mime, ['image/jpeg','image/png','image/webp','image/gif'])) return null;
    if ($_FILES[$veld]['size'] > 10 * 1024 * 1024) return null;
    $ext  = strtolower(pathinfo($_FILES[$veld]['name'], PATHINFO_EXTENSION));
    $bestand = bin2hex(random_bytes(10)) . '.' . $ext;
    if (move_uploaded_file($_FILES[$veld]['tmp_name'], $uploadDir . $bestand)) {
        return 'uploads/aanvragen/' . $id . '/' . $bestand;
    }
    return null;
}

// ── Reparatieaanvraag ────────────────────────────────────────────
if ($type === 'reparatie') {
    $naam         = trim($_POST['naam']         ?? '');
    $plaats       = trim($_POST['plaats']       ?? '');
    $tel          = trim($_POST['telefoon']     ?? '');
    $modelnummer  = trim($_POST['modelnummer']  ?? '');
    $omschrijving = trim($_POST['omschrijving'] ?? '');
    if (!$naam || !$plaats || !$tel || !$modelnummer || !$omschrijving) {
        redirect(BASE_URL . '/mijn-aanvraag.php?error=onvolledig');
    }
    $fotoDefect = uploadFoto('foto_defect', $uploadDir, $id);
    $fotoLabel  = uploadFoto('foto_label',  $uploadDir, $id);
    $sql    = "UPDATE aanvragen SET naam=?, telefoon=?, plaats=?, omschrijving=?, modelnummer=?, status='aanvraag'";
    $params = [$naam, $tel, $plaats, $omschrijving, $modelnummer];
    if ($fotoDefect) { $sql .= ', foto_defect=?'; $params[] = $fotoDefect; }
    if ($fotoLabel)  { $sql .= ', foto_label=?';  $params[] = $fotoLabel; }
    $sql .= ' WHERE id=?'; $params[] = $id;
    db()->prepare($sql)->execute($params);
    try {
        db()->prepare('INSERT INTO aanvragen_log (aanvraag_id, actie, gedaan_door) VALUES (?,?,?)')
           ->execute([$id, 'Reparatieaanvraag ingediend door klant', 'klant']);
    } catch (\PDOException $e) {}
    stuurAdminFormulierMail($id, 'reparatie');
    redirect(BASE_URL . '/mijn-aanvraag.php?verzonden=2');
}

// ── Taxatieaanvraag ──────────────────────────────────────────────
if ($type === 'taxatie') {
    $naam            = trim($_POST['naam']             ?? '');
    $adres           = trim($_POST['adres']            ?? '');
    $postcode        = trim($_POST['postcode']         ?? '');
    $plaats          = trim($_POST['plaats']           ?? '');
    $tel             = trim($_POST['telefoon']         ?? '');
    $serienummer     = trim($_POST['serienummer']      ?? '');
    $redenSchade     = trim($_POST['reden_schade']     ?? '');
    $beschrijving    = trim($_POST['beschrijving']     ?? '');
    $aankoopbedrag   = trim($_POST['aankoopbedrag']    ?? '');
    $aankoopdatum    = trim($_POST['aankoopdatum']     ?? '');
    $heeftBon        = isset($_POST['heeft_bon']) ? 1 : 0;
    $naamVerzekeraar = trim($_POST['naam_verzekeraar'] ?? '');
    $polisnummer     = trim($_POST['polisnummer']      ?? '');

    if (!$naam || !$adres || !$postcode || !$plaats || !$tel || !$serienummer
        || !$redenSchade || !$aankoopbedrag || !$aankoopdatum
        || !$naamVerzekeraar || !$polisnummer) {
        redirect(BASE_URL . '/mijn-aanvraag.php?error=onvolledig');
    }

    $fotoToestel = uploadFoto('foto_toestel', $uploadDir, $id);
    $fotoDefect  = uploadFoto('foto_defect',  $uploadDir, $id);
    $fotoLabel   = uploadFoto('foto_label',   $uploadDir, $id);
    $fotoExtra   = uploadFoto('foto_extra',   $uploadDir, $id);

    if (!$fotoToestel || !$fotoDefect || !$fotoLabel) {
        redirect(BASE_URL . '/mijn-aanvraag.php?error=foto_verplicht');
    }

    $sql = "UPDATE aanvragen SET naam=?, adres=?, postcode=?, plaats=?, telefoon=?,
            serienummer=?, reden_schade=?, omschrijving=?, aankoopbedrag=?, aankoopdatum=?,
            heeft_bon=?, naam_verzekeraar=?, polisnummer=?,
            foto_toestel=?, foto_defect=?, foto_label=?, status='aanvraag'";
    $params = [
        $naam, $adres, $postcode, $plaats, $tel,
        $serienummer, $redenSchade, $beschrijving, $aankoopbedrag, $aankoopdatum ?: null,
        $heeftBon, $naamVerzekeraar, $polisnummer,
        $fotoToestel, $fotoDefect, $fotoLabel,
    ];
    if ($fotoExtra) { $sql .= ', foto_extra=?'; $params[] = $fotoExtra; }
    $sql .= ' WHERE id=?'; $params[] = $id;
    db()->prepare($sql)->execute($params);
    try {
        db()->prepare('INSERT INTO aanvragen_log (aanvraag_id, actie, gedaan_door) VALUES (?,?,?)')
           ->execute([$id, 'Taxatieaanvraag ingediend door klant', 'klant']);
    } catch (\PDOException $e) {}
    stuurAdminFormulierMail($id, 'taxatie');
    redirect(BASE_URL . '/mijn-aanvraag.php?verzonden=2');
}

// ── Generiek (overige typen doorgestuurd) ────────────────────────
$naam = trim($_POST['naam']     ?? '');
$tel  = trim($_POST['telefoon'] ?? '');
$adr  = trim($_POST['adres']    ?? '');
if (!$naam || !$tel || !$adr) redirect(BASE_URL . '/mijn-aanvraag.php?error=onvolledig');

$uploads = [];
foreach (['foto_defect', 'foto_label', 'foto_bon'] as $veld) {
    $pad = uploadFoto($veld, $uploadDir, $id);
    if ($pad) $uploads[$veld] = $pad;
}

$logActie = ucfirst($rij['advies_type'] ?? 'aanvraag') . ' ingediend door klant';
$sql      = "UPDATE aanvragen SET naam=?, telefoon=?, adres=?, status='aanvraag'";
$params   = [$naam, $tel, $adr];
foreach (['foto_defect', 'foto_label', 'foto_bon'] as $v) {
    if (isset($uploads[$v])) { $sql .= ", $v=?"; $params[] = $uploads[$v]; }
}
$sql .= ' WHERE id=?'; $params[] = $id;
db()->prepare($sql)->execute($params);

try {
    db()->prepare('INSERT INTO aanvragen_log (aanvraag_id, actie, gedaan_door) VALUES (?,?,?)')
       ->execute([$id, $logActie, 'klant']);
} catch (\PDOException $e) {}
stuurAdminFormulierMail($id, $rij['advies_type'] ?? 'aanvraag');
redirect(BASE_URL . '/mijn-aanvraag.php?verzonden=2');
