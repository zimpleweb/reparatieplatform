<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf($_POST['csrf_token'] ?? '')) {
    redirect(BASE_URL . '/mijn-aanvraag.php?error=csrf');
}

$id    = (int)  ($_POST['aanvraag_id'] ?? 0);
$cn    = trim(  $_POST['casenummer']   ?? '');
$actie = trim(  $_POST['actie']        ?? '');

if (!$id || !$cn) redirect(BASE_URL . '/mijn-aanvraag.php?error=ongeldig');

$stmt = db()->prepare(
    'SELECT id, status, advies_type, email, casenummer, modelnummer, model_repareerbaar
     FROM aanvragen WHERE id=? AND casenummer=?'
);
$stmt->execute([$id, $cn]);
$rij = $stmt->fetch();

if (!$rij) redirect(BASE_URL . '/mijn-aanvraag.php?error=ongeldig');

// ── Bericht van klant (elke status) ─────────────────────────────────────────
if ($actie === 'bericht') {
    $tekst = trim($_POST['bericht_tekst'] ?? '');
    if ($tekst) {
        try {
            db()->prepare('INSERT INTO aanvragen_log (aanvraag_id, actie, opmerking, gedaan_door) VALUES (?,?,?,?)')
               ->execute([$id, 'Bericht klant', $tekst, 'klant']);
        } catch (\PDOException $e) {}
        try {
            $av = db()->prepare('SELECT casenummer, email, merk, modelnummer FROM aanvragen WHERE id=?');
            $av->execute([$id]);
            $avRow = $av->fetch();
            if ($avRow) {
                $mailVars = [
                    'casenummer'    => $avRow['casenummer']  ?? $cn,
                    'email'         => $avRow['email']       ?? '',
                    'merk'          => $avRow['merk']        ?? '',
                    'modelnummer'   => $avRow['modelnummer'] ?? '',
                    'datum_bericht' => date('d-m-Y H:i'),
                    'chatbericht'   => $tekst,
                ];
                $adminEmails = db()->query(
                    "SELECT email FROM admins WHERE email IS NOT NULL AND email != ''"
                )->fetchAll(PDO::FETCH_COLUMN);
                foreach ($adminEmails as $adminEmail) {
                    @sendMail($adminEmail, 'admin_nieuw_chatbericht', $mailVars);
                }
            }
        } catch (\PDOException $e) {}
    }
    redirect(BASE_URL . '/mijn-aanvraag.php');
}

// ── Legacy: Coulance mislukt → omzetten naar reparatie ───────────────────────
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

// ── Legacy: Recyclingverzoek (status = recycling) ────────────────────────────
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

// ── Aanvulling: alleen voor *_afwachting statussen ───────────────────────────
$toegestaneAfwachting = [
    'doorgestuurd',
    'reparatie_afwachting', 'taxatie_afwachting',
    'garantie_afwachting',  'coulance_afwachting',
    'recycling_afwachting',
];
if (!in_array($rij['status'], $toegestaneAfwachting, true)) {
    redirect(BASE_URL . '/mijn-aanvraag.php?error=ongeldig');
}

// Nieuwe status na indienen — type-specifiek voor nieuwe flow, legacy voor doorgestuurd
$nieuweStatusNaIndienen = match($rij['status']) {
    'reparatie_afwachting' => 'reparatie_ingevuld',
    'taxatie_afwachting'   => 'taxatie_ingevuld',
    'garantie_afwachting'  => 'garantie_ingevuld',
    'coulance_afwachting'  => 'coulance_ingevuld',
    'recycling_afwachting' => 'recycling_ingevuld',
    default                => 'aanvraag',
};

$type = trim($_POST['type'] ?? '');

// ── Beveiligde uploadmap: uploads/aanvragen/YYYY/MM/DD/last4/ ───────────────
$casenummer = $rij['casenummer'];
$datumPad   = date('Y') . '/' . date('m') . '/' . date('d');
$last4      = str_pad(substr(preg_replace('/\D/', '', $casenummer), -4), 4, '0', STR_PAD_LEFT);
$uploadDir  = __DIR__ . '/../uploads/aanvragen/' . $datumPad . '/' . $last4 . '/';
$relBase    = 'uploads/aanvragen/' . $datumPad . '/' . $last4 . '/';
if (!is_dir($uploadDir)) @mkdir($uploadDir, 0750, true);

function stuurAdminFormulierMail(int $id, string $aanvraagType): void {
    try {
        $s = db()->prepare('SELECT casenummer, email, merk, modelnummer FROM aanvragen WHERE id=?');
        $s->execute([$id]);
        $av = $s->fetch();
        if (!$av) return;
        $mailVars = [
            'casenummer'    => $av['casenummer']  ?? '',
            'email'         => $av['email']       ?? '',
            'merk'          => $av['merk']        ?? '',
            'modelnummer'   => $av['modelnummer'] ?? '',
            'aanvraag_type' => ucfirst($aanvraagType),
        ];
        $adminEmails = db()->query(
            "SELECT email FROM admins WHERE email IS NOT NULL AND email != ''"
        )->fetchAll(PDO::FETCH_COLUMN);
        foreach ($adminEmails as $adminEmail) {
            @sendMail($adminEmail, 'admin_formulier_ingevuld', $mailVars);
        }
    } catch (\PDOException $e) {}
}

function uploadFoto(string $veld, string $uploadDir, string $relBase): ?string {
    if (!isset($_FILES[$veld]) || $_FILES[$veld]['error'] !== UPLOAD_ERR_OK) return null;
    if ($_FILES[$veld]['size'] > 10 * 1024 * 1024) return null;

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $_FILES[$veld]['tmp_name']);
    finfo_close($finfo);

    $mimeNaarExt = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];
    if (!isset($mimeNaarExt[$mime])) return null;

    $bestand = bin2hex(random_bytes(10)) . '.' . $mimeNaarExt[$mime];
    if (move_uploaded_file($_FILES[$veld]['tmp_name'], $uploadDir . $bestand)) {
        return $relBase . $bestand;
    }
    return null;
}

// ── Reparatieaanvraag ────────────────────────────────────────────────────────
if ($type === 'reparatie') {
    $naam         = trim($_POST['naam']         ?? '');
    $plaats       = trim($_POST['plaats']       ?? '');
    $tel          = trim($_POST['telefoon']     ?? '');
    $modelnummer  = trim($_POST['modelnummer']  ?? $rij['modelnummer'] ?? '');
    $omschrijving = trim($_POST['omschrijving'] ?? '');
    if (!$naam || !$plaats || !$tel || !$omschrijving) {
        redirect(BASE_URL . '/mijn-aanvraag.php?error=onvolledig');
    }
    $fotoDefect = uploadFoto('foto_defect', $uploadDir, $relBase);
    $fotoLabel  = uploadFoto('foto_label',  $uploadDir, $relBase);
    $sql    = 'UPDATE aanvragen SET naam=?, telefoon=?, plaats=?, omschrijving=?, status=?';
    $params = [$naam, $tel, $plaats, $omschrijving, $nieuweStatusNaIndienen];
    if ($modelnummer) { $sql .= ', modelnummer=?'; $params[] = $modelnummer; }
    if ($fotoDefect)  { $sql .= ', foto_defect=?'; $params[] = $fotoDefect; }
    if ($fotoLabel)   { $sql .= ', foto_label=?';  $params[] = $fotoLabel; }
    $sql .= ' WHERE id=?'; $params[] = $id;
    db()->prepare($sql)->execute($params);
    try {
        db()->prepare('INSERT INTO aanvragen_log (aanvraag_id, actie, gedaan_door) VALUES (?,?,?)')
           ->execute([$id, 'Reparatieaanvraag ingediend door klant', 'klant']);
    } catch (\PDOException $e) {}
    stuurAdminFormulierMail($id, 'reparatie');
    redirect(BASE_URL . '/mijn-aanvraag.php?verzonden=2');
}

// ── Taxatieaanvraag ──────────────────────────────────────────────────────────
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
    $heeftBonRaw     = trim($_POST['heeft_bon']        ?? '');
    $naamVerzekeraar = trim($_POST['naam_verzekeraar'] ?? '');
    $polisnummer     = trim($_POST['polisnummer']      ?? '');

    if (!$naam || !$adres || !$postcode || !$plaats || !$tel || !$serienummer
        || !$redenSchade || !$aankoopbedrag || !$aankoopdatum
        || !$naamVerzekeraar || !$polisnummer) {
        redirect(BASE_URL . '/mijn-aanvraag.php?error=onvolledig');
    }

    $heeftBon = match($heeftBonRaw) {
        'ja'    => 1,
        'nee'   => 0,
        'kwijt' => 2,
        default => null,
    };

    $fotoToestel = uploadFoto('foto_toestel', $uploadDir, $relBase);
    $fotoDefect  = uploadFoto('foto_defect',  $uploadDir, $relBase);
    $fotoLabel   = uploadFoto('foto_label',   $uploadDir, $relBase);
    $fotoExtra   = uploadFoto('foto_extra',   $uploadDir, $relBase);

    if (!$fotoToestel || !$fotoDefect || !$fotoLabel) {
        redirect(BASE_URL . '/mijn-aanvraag.php?error=foto_verplicht');
    }

    $sql = 'UPDATE aanvragen SET naam=?, adres=?, postcode=?, plaats=?, telefoon=?,
            serienummer=?, reden_schade=?, omschrijving=?, aankoopbedrag=?, aankoopdatum=?,
            heeft_bon=?, naam_verzekeraar=?, polisnummer=?,
            foto_toestel=?, foto_defect=?, foto_label=?, status=?';
    $params = [
        $naam, $adres, $postcode, $plaats, $tel,
        $serienummer, $redenSchade, $beschrijving, $aankoopbedrag, $aankoopdatum ?: null,
        $heeftBon, $naamVerzekeraar, $polisnummer,
        $fotoToestel, $fotoDefect, $fotoLabel,
        $nieuweStatusNaIndienen,
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

// ── Coulanceaanvraag ─────────────────────────────────────────────────────────
if ($type === 'coulance') {
    $verkoopprijs        = trim($_POST['verkoopprijs']            ?? '');
    $winkelNaam          = trim($_POST['winkel_naam']             ?? '');
    $resultaat           = trim($_POST['resultaat']               ?? '');
    $winkelRaw           = $_POST['coulance_winkel_resultaat']    ?? null;
    $fabrikantRaw        = $_POST['coulance_fabrikant_resultaat'] ?? null;

    if (!$verkoopprijs || !$winkelNaam || !$resultaat) {
        redirect(BASE_URL . '/mijn-aanvraag.php?error=onvolledig');
    }

    $winkelResultaat    = ($winkelRaw    !== null && $winkelRaw    !== '') ? (int)$winkelRaw    : null;
    $fabrikantResultaat = ($fabrikantRaw !== null && $fabrikantRaw !== '') ? (int)$fabrikantRaw : null;

    switch ($resultaat) {
        case 'winkel_gelukt':
            $statusCoulance = 'gesloten';
            $logActie       = 'Coulance gelukt via winkel — aanvraag gesloten door klant';
            break;
        case 'fabrikant_gelukt':
            $statusCoulance = 'gesloten';
            $logActie       = 'Coulance gelukt via fabrikant — aanvraag gesloten door klant';
            break;
        case 'reparatie_starten':
            $statusCoulance = 'reparatie_afwachting';
            $logActie       = 'Coulance niet gelukt — reparatieaanvraag gestart door klant';
            break;
        case 'afsluiten':
            $statusCoulance = 'gesloten';
            $logActie       = 'Coulance niet gelukt — inzending gesloten door klant';
            break;
        case 'niet_gelukt':
            // Legacy-pad: gebruik voor niet-repareerbare modellen
            $statusCoulance = 'gesloten';
            $logActie       = 'Coulance niet gelukt — aanvraag gesloten';
            break;
        default:
            redirect(BASE_URL . '/mijn-aanvraag.php?error=ongeldig');
    }

    $sql = 'UPDATE aanvragen SET verkoopprijs=?, winkel_naam=?,
            coulance_winkel_resultaat=?, coulance_fabrikant_resultaat=?, status=?
            WHERE id=?';
    db()->prepare($sql)->execute([
        $verkoopprijs, $winkelNaam,
        $winkelResultaat, $fabrikantResultaat,
        $statusCoulance, $id,
    ]);
    try {
        db()->prepare('INSERT INTO aanvragen_log (aanvraag_id, actie, gedaan_door) VALUES (?,?,?)')
           ->execute([$id, $logActie, 'klant']);
    } catch (\PDOException $e) {}
    stuurAdminFormulierMail($id, 'coulance');
    redirect(BASE_URL . '/mijn-aanvraag.php?verzonden=' . ($statusCoulance === 'reparatie_afwachting' ? '3' : '2'));
}

// ── Recyclingaanvraag (nieuw, vanuit recycling_afwachting) ───────────────────
if ($type === 'recycling') {
    $resultaat = trim($_POST['resultaat'] ?? '');

    if ($resultaat === 'niet_geinteresseerd') {
        db()->prepare("UPDATE aanvragen SET recycling_interesse=0, status='gesloten' WHERE id=?")
           ->execute([$id]);
        try {
            db()->prepare('INSERT INTO aanvragen_log (aanvraag_id, actie, gedaan_door) VALUES (?,?,?)')
               ->execute([$id, 'Geen interesse in recycling — inzending gesloten door klant', 'klant']);
        } catch (\PDOException $e) {}
        redirect(BASE_URL . '/mijn-aanvraag.php?verzonden=2');
    }

    if ($resultaat === 'geinteresseerd') {
        $naam         = trim($_POST['naam']                  ?? '');
        $tel          = trim($_POST['telefoon']              ?? '');
        $adres        = trim($_POST['adres']                 ?? '');
        $postcode     = trim($_POST['postcode']              ?? '');
        $plaats       = trim($_POST['plaats']                ?? '');
        $omschrijving = trim($_POST['omschrijving']          ?? '');
        $ophaal       = trim($_POST['ophaalvoorkeur']        ?? '');
        $toelichting  = trim($_POST['recycling_toelichting'] ?? '');

        if (!$naam || !$tel || !$adres || !$postcode || !$plaats) {
            redirect(BASE_URL . '/mijn-aanvraag.php?error=onvolledig');
        }

        $fotoToestel = uploadFoto('foto_toestel', $uploadDir, $relBase);
        $fotoDefect  = uploadFoto('foto_defect',  $uploadDir, $relBase);

        $notitie = $omschrijving;
        if ($toelichting) $notitie .= ($notitie ? "\n" : '') . $toelichting;

        $sql = 'UPDATE aanvragen SET naam=?, telefoon=?, adres=?, postcode=?, plaats=?,
                omschrijving=?, recycling_interesse=1, recycling_ophaalvoorkeur=?, status=?';
        $params = [$naam, $tel, $adres, $postcode, $plaats, $notitie, $ophaal ?: null, $nieuweStatusNaIndienen];
        if ($fotoToestel) { $sql .= ', foto_toestel=?'; $params[] = $fotoToestel; }
        if ($fotoDefect)  { $sql .= ', foto_defect=?';  $params[] = $fotoDefect; }
        $sql .= ' WHERE id=?'; $params[] = $id;
        db()->prepare($sql)->execute($params);
        try {
            db()->prepare('INSERT INTO aanvragen_log (aanvraag_id, actie, gedaan_door) VALUES (?,?,?)')
               ->execute([$id, 'Recyclingverzoek ingediend door klant', 'klant']);
        } catch (\PDOException $e) {}
        stuurAdminFormulierMail($id, 'recycling');
        redirect(BASE_URL . '/mijn-aanvraag.php?verzonden=2');
    }

    redirect(BASE_URL . '/mijn-aanvraag.php?error=ongeldig');
}

// ── Generiek fallback (garantie of onbekend type) ────────────────────────────
$naam = trim($_POST['naam']     ?? '');
$tel  = trim($_POST['telefoon'] ?? '');
$adr  = trim($_POST['adres']    ?? '');
if (!$naam || !$tel || !$adr) redirect(BASE_URL . '/mijn-aanvraag.php?error=onvolledig');

$uploads = [];
foreach (['foto_defect', 'foto_label', 'foto_bon'] as $veld) {
    $pad = uploadFoto($veld, $uploadDir, $relBase);
    if ($pad) $uploads[$veld] = $pad;
}

$logActie = ucfirst($rij['advies_type'] ?? 'aanvraag') . ' ingediend door klant';
$sql      = 'UPDATE aanvragen SET naam=?, telefoon=?, adres=?, status=?';
$params   = [$naam, $tel, $adr, $nieuweStatusNaIndienen];
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
