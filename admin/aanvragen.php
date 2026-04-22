<?php
session_start();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$msg  = '';
$fout = '';

$filterStatus = trim($_GET['status'] ?? '');
$filterRoute  = trim($_GET['route']  ?? '');
$filterZoek   = trim($_GET['zoek']   ?? '');

$statusLabels = [
    // ── Initieel ──────────────────────────────────────────────────────────
    'inzending'            => ['tekst' => 'Ontvangen',             'badge' => 'badge-blue'],
    // ── Reparatie ─────────────────────────────────────────────────────────
    'reparatie_afwachting' => ['tekst' => 'Reparatie afwachting',  'badge' => 'badge-yellow'],
    'reparatie_ingevuld'   => ['tekst' => 'Reparatie ingevuld',    'badge' => 'badge-green'],
    // ── Taxatie ───────────────────────────────────────────────────────────
    'taxatie_afwachting'   => ['tekst' => 'Taxatie afwachting',    'badge' => 'badge-yellow'],
    'taxatie_ingevuld'     => ['tekst' => 'Taxatie ingevuld',      'badge' => 'badge-blue'],
    // ── Garantie ──────────────────────────────────────────────────────────
    'garantie_afwachting'  => ['tekst' => 'Garantie afwachting',   'badge' => 'badge-yellow'],
    'garantie_ingevuld'    => ['tekst' => 'Garantie ingevuld',     'badge' => 'badge-purple'],
    // ── Coulance ──────────────────────────────────────────────────────────
    'coulance_afwachting'  => ['tekst' => 'Coulance afwachting',   'badge' => 'badge-yellow'],
    'coulance_ingevuld'    => ['tekst' => 'Coulance ingevuld',     'badge' => 'badge-orange'],
    // ── Recycling ─────────────────────────────────────────────────────────
    'recycling_afwachting' => ['tekst' => 'Recycling afwachting',  'badge' => 'badge-yellow'],
    'recycling_ingevuld'   => ['tekst' => 'Recycling ingevuld',    'badge' => 'badge-gray'],
    // ── Eindstatus ────────────────────────────────────────────────────────
    'afgewezen'            => ['tekst' => 'Afgewezen',             'badge' => 'badge-red'],
    // ── Legacy (backwards-compatibel) ─────────────────────────────────────
    'doorgestuurd'         => ['tekst' => 'Aanvulling nodig',      'badge' => 'badge-orange'],
    'aanvraag'             => ['tekst' => 'Aanvraag ontvangen',    'badge' => 'badge-green'],
    'coulance'             => ['tekst' => 'Coulance',              'badge' => 'badge-yellow'],
    'recycling'            => ['tekst' => 'Recycling',             'badge' => 'badge-purple'],
    'behandeld'            => ['tekst' => 'Behandeld',             'badge' => 'badge-green'],
    'archief'              => ['tekst' => 'Archief',               'badge' => 'badge-gray'],
];

$statusDefinitief = [
    'afgewezen',
    'reparatie_ingevuld', 'taxatie_ingevuld', 'garantie_ingevuld',
    'coulance_ingevuld',  'recycling_ingevuld',
    'behandeld', 'archief',
];

$aanvraagTypes = [
    'reparatie' => ['label' => 'Reparatie',  'kleur' => '#16a34a', 'tekst' => '#fff'],
    'taxatie'   => ['label' => 'Taxatie',    'kleur' => '#2563eb', 'tekst' => '#fff'],
    'coulance'  => ['label' => 'Coulance',   'kleur' => '#d97706', 'tekst' => '#fff'],
    'garantie'  => ['label' => 'Garantie',   'kleur' => '#7c3aed', 'tekst' => '#fff'],
    'recycling' => ['label' => 'Recycling',  'kleur' => '#0f766e', 'tekst' => '#fff'],
];

// ── POST: bericht sturen ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'bericht') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        $fout = 'Ongeldig beveiligingstoken.';
    } else {
        $aanvraagId = (int)($_POST['aanvraag_id'] ?? 0);
        $berichtTxt = trim($_POST['bericht'] ?? '');
        if ($aanvraagId && $berichtTxt !== '') {
            try {
                $ins = db()->prepare(
                    'INSERT INTO aanvragen_log (aanvraag_id, actie, opmerking, aangemaakt)
                     VALUES (?, ?, ?, NOW())'
                );
                $ins->execute([$aanvraagId, 'Bericht verstuurd aan klant', $berichtTxt]);
                $msg = 'Bericht opgeslagen in de activiteitenlog.';
            } catch (\PDOException $e) {
                $fout = 'Kon bericht niet opslaan: ' . h($e->getMessage());
            }
        } else {
            $fout = 'Bericht mag niet leeg zijn.';
        }
        $qs = http_build_query(array_filter([
            'id'     => $aanvraagId,
            'status' => $filterStatus,
            'route'  => $filterRoute,
            'zoek'   => $filterZoek,
            'saved'  => '1',
        ]));
        header('Location: ?' . $qs);
        exit;
    }
}

// ── POST: status wijzigen ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'status') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        $fout = 'Ongeldig beveiligingstoken.';
    } else {
        $aanvraagId  = (int)($_POST['aanvraag_id'] ?? 0);
        $nieuwStatus = trim($_POST['nieuw_status'] ?? '');
        $toegestaan  = array_keys($statusLabels);
        if ($aanvraagId && in_array($nieuwStatus, $toegestaan, true)) {
            db()->prepare('UPDATE aanvragen SET status=? WHERE id=?')
               ->execute([$nieuwStatus, $aanvraagId]);
            try {
                $ins = db()->prepare(
                    'INSERT INTO aanvragen_log (aanvraag_id, actie, aangemaakt)
                     VALUES (?, ?, NOW())'
                );
                $ins->execute([$aanvraagId, 'Status gewijzigd naar: ' . ($statusLabels[$nieuwStatus]['tekst'] ?? $nieuwStatus)]);
            } catch (\PDOException $e) {}
            $qs = http_build_query(array_filter([
                'id'     => $aanvraagId,
                'status' => $filterStatus,
                'route'  => $filterRoute,
                'zoek'   => $filterZoek,
                'saved'  => '1',
            ]));
            header('Location: ?' . $qs);
            exit;
        } else {
            $fout = 'Ongeldige statuswaarde.';
        }
    }
}

// ── POST: advies kiezen (primaire flow: inzending → [type]_afwachting) ───────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'kies_advies') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        $fout = 'Ongeldig beveiligingstoken.';
    } else {
        $aanvraagId   = (int)($_POST['aanvraag_id'] ?? 0);
        $gekozenAdvies = trim($_POST['gekozen_advies'] ?? '');
        $toegestaan   = array_keys($aanvraagTypes);
        $isAfwijzen   = ($gekozenAdvies === 'afwijzen');

        if ($aanvraagId && ($isAfwijzen || in_array($gekozenAdvies, $toegestaan, true))) {
            $nieuweStatus = $isAfwijzen ? 'afgewezen' : $gekozenAdvies . '_afwachting';
            $logTekst     = $isAfwijzen
                ? 'Inzending afgewezen'
                : 'Advies gekozen: ' . ($aanvraagTypes[$gekozenAdvies]['label'] ?? $gekozenAdvies)
                  . ' → status ' . ($statusLabels[$nieuweStatus]['tekst'] ?? $nieuweStatus);

            $pdo = db();
            $pdo->prepare(
                'UPDATE aanvragen SET gekozen_advies=?, status=? WHERE id=?'
            )->execute([$isAfwijzen ? null : $gekozenAdvies, $nieuweStatus, $aanvraagId]);

            try {
                $pdo->prepare(
                    'INSERT INTO aanvragen_log (aanvraag_id, actie, aangemaakt)
                     VALUES (?, ?, NOW())'
                )->execute([$aanvraagId, $logTekst]);
            } catch (\PDOException $e) {}

            $qs = http_build_query(array_filter([
                'id'     => $aanvraagId,
                'status' => $filterStatus,
                'route'  => $filterRoute,
                'zoek'   => $filterZoek,
                'saved'  => '1',
            ]));
            header('Location: ?' . $qs);
            exit;
        } else {
            $fout = 'Ongeldig advies gekozen.';
        }
    }
}

// ── POST: aanvraag-type toekennen / wijzigen ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'set_type') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        $fout = 'Ongeldig beveiligingstoken.';
    } else {
        $aanvraagId = (int)($_POST['aanvraag_id'] ?? 0);
        $nieuwType  = trim($_POST['aanvraag_type'] ?? '');
        $toegestaan = array_keys($aanvraagTypes);
        if ($aanvraagId && in_array($nieuwType, $toegestaan, true)) {
            $pdo = db();
            // Bepaal of de status mee moet wijzigen (alleen als huidige status een *_afwachting is)
            $huidigRow = $pdo->prepare('SELECT status FROM aanvragen WHERE id=?');
            $huidigRow->execute([$aanvraagId]);
            $huidigStatus = $huidigRow->fetchColumn() ?: '';
            $afwachtingStatussen = ['reparatie_afwachting','taxatie_afwachting','garantie_afwachting',
                                    'coulance_afwachting','recycling_afwachting'];
            $nieuweStatus = in_array($huidigStatus, $afwachtingStatussen, true)
                ? $nieuwType . '_afwachting'
                : null;
            try {
                if ($nieuweStatus) {
                    $pdo->prepare('UPDATE aanvragen SET aanvraag_type=?, gekozen_advies=?, status=? WHERE id=?')
                       ->execute([$nieuwType, $nieuwType, $nieuweStatus, $aanvraagId]);
                } else {
                    $pdo->prepare('UPDATE aanvragen SET aanvraag_type=? WHERE id=?')
                       ->execute([$nieuwType, $aanvraagId]);
                }
            } catch (\PDOException $e) {
                try {
                    if ($nieuweStatus) {
                        $pdo->prepare('UPDATE aanvragen SET advies_type=?, gekozen_advies=?, status=? WHERE id=?')
                           ->execute([$nieuwType, $nieuwType, $nieuweStatus, $aanvraagId]);
                    } else {
                        $pdo->prepare('UPDATE aanvragen SET advies_type=? WHERE id=?')
                           ->execute([$nieuwType, $aanvraagId]);
                    }
                } catch (\PDOException $e2) {}
            }
            $logTekst = 'Aanvraagtype ingesteld op: ' . ($aanvraagTypes[$nieuwType]['label'] ?? $nieuwType);
            if ($nieuweStatus) {
                $logTekst .= ' → status ' . ($statusLabels[$nieuweStatus]['tekst'] ?? $nieuweStatus);
            }
            try {
                $pdo->prepare(
                    'INSERT INTO aanvragen_log (aanvraag_id, actie, aangemaakt)
                     VALUES (?, ?, NOW())'
                )->execute([$aanvraagId, $logTekst]);
            } catch (\PDOException $e) {}
            $qs = http_build_query(array_filter([
                'id'     => $aanvraagId,
                'status' => $filterStatus,
                'route'  => $filterRoute,
                'zoek'   => $filterZoek,
                'saved'  => '1',
            ]));
            header('Location: ?' . $qs);
            exit;
        } else {
            $fout = 'Ongeldig aanvraagtype.';
        }
    }
}

// ── POST: aanvraag-type wijzigen via lijst (optiemenu) ────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'set_type_lijst') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        $fout = 'Ongeldig beveiligingstoken.';
    } else {
        $aanvraagId = (int)($_POST['aanvraag_id'] ?? 0);
        $nieuwType  = trim($_POST['aanvraag_type'] ?? '');
        $toegestaan = array_keys($aanvraagTypes);
        if ($aanvraagId && in_array($nieuwType, $toegestaan, true)) {
            $pdo = db();
            // Bepaal of de status mee moet wijzigen (alleen als huidige status een *_afwachting is)
            $huidigRow = $pdo->prepare('SELECT status FROM aanvragen WHERE id=?');
            $huidigRow->execute([$aanvraagId]);
            $huidigStatus = $huidigRow->fetchColumn() ?: '';
            $afwachtingStatussen = ['reparatie_afwachting','taxatie_afwachting','garantie_afwachting',
                                    'coulance_afwachting','recycling_afwachting'];
            $nieuweStatus = in_array($huidigStatus, $afwachtingStatussen, true)
                ? $nieuwType . '_afwachting'
                : null;
            try {
                if ($nieuweStatus) {
                    $pdo->prepare('UPDATE aanvragen SET aanvraag_type=?, gekozen_advies=?, status=? WHERE id=?')
                       ->execute([$nieuwType, $nieuwType, $nieuweStatus, $aanvraagId]);
                } else {
                    $pdo->prepare('UPDATE aanvragen SET aanvraag_type=? WHERE id=?')
                       ->execute([$nieuwType, $aanvraagId]);
                }
            } catch (\PDOException $e) {
                try {
                    if ($nieuweStatus) {
                        $pdo->prepare('UPDATE aanvragen SET advies_type=?, gekozen_advies=?, status=? WHERE id=?')
                           ->execute([$nieuwType, $nieuwType, $nieuweStatus, $aanvraagId]);
                    } else {
                        $pdo->prepare('UPDATE aanvragen SET advies_type=? WHERE id=?')
                           ->execute([$nieuwType, $aanvraagId]);
                    }
                } catch (\PDOException $e2) {}
            }
            $logTekst = 'Aanvraagtype gewijzigd naar: ' . ($aanvraagTypes[$nieuwType]['label'] ?? $nieuwType);
            if ($nieuweStatus) {
                $logTekst .= ' → status ' . ($statusLabels[$nieuweStatus]['tekst'] ?? $nieuweStatus);
            }
            try {
                $pdo->prepare(
                    'INSERT INTO aanvragen_log (aanvraag_id, actie, aangemaakt)
                     VALUES (?, ?, NOW())'
                )->execute([$aanvraagId, $logTekst]);
            } catch (\PDOException $e) {}
            $qs = http_build_query(array_filter([
                'status' => $filterStatus,
                'route'  => $filterRoute,
                'zoek'   => $filterZoek,
                'saved'  => '1',
            ]));
            header('Location: ?' . $qs);
            exit;
        } else {
            $fout = 'Ongeldig aanvraagtype.';
        }
    }
}

// ── Detail ophalen ────────────────────────────────────────────────────────
$detail = null;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $ds = db()->prepare('SELECT * FROM aanvragen WHERE id=?');
    $ds->execute([$_GET['id']]);
    $detail = $ds->fetch() ?: null;
    if ($detail) {
        try {
            $ls = db()->prepare(
                'SELECT * FROM aanvragen_log WHERE aanvraag_id=? ORDER BY aangemaakt ASC'
            );
            $ls->execute([$detail['id']]);
            $detail['log'] = $ls->fetchAll();
        } catch (\PDOException $e) { $detail['log'] = []; }

        $uploadBase = realpath(__DIR__ . '/../uploads');
        foreach (['foto_defect', 'foto_label', 'foto_bon'] as $fotoKey) {
            if (!empty($detail[$fotoKey])) {
                $absPath = realpath(__DIR__ . '/../' . $detail[$fotoKey]);
                if ($absPath === false || strpos($absPath, $uploadBase) !== 0) {
                    $detail[$fotoKey] = null;
                }
            }
        }
    }
}

// ── Lijst ophalen ─────────────────────────────────────────────────────────
$where = ['1=1']; $params = [];
if ($filterStatus) { $where[] = 'status = ?'; $params[] = $filterStatus; }
if ($filterRoute)  {
    $where[] = '(geadviseerde_route = ? OR advies_type = ? OR aanvraag_type = ?)';
    $params[] = $filterRoute; $params[] = $filterRoute; $params[] = $filterRoute;
}
if ($filterZoek) {
    $where[] = '(merk LIKE ? OR modelnummer LIKE ? OR email LIKE ? OR casenummer LIKE ?)';
    $like = '%' . $filterZoek . '%';
    $params = array_merge($params, [$like, $like, $like, $like]);
}
$sql  = 'SELECT * FROM aanvragen WHERE ' . implode(' AND ', $where) . ' ORDER BY id DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$aanvragen = $stmt->fetchAll();

$TOEGESTANE_KOLOMMEN = ['aangemaakt_op', 'created_at', 'id'];
$datumKolom = 'created_at';
try {
    $cols = db()->query("SHOW COLUMNS FROM aanvragen LIKE 'aangemaakt_op'")->fetchColumn();
    if ($cols) $datumKolom = 'aangemaakt_op';
} catch (\Exception $e) {}
if (!in_array($datumKolom, $TOEGESTANE_KOLOMMEN, true)) $datumKolom = 'id';

$adminActivePage = 'aanvragen';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Inzendingen &ndash; Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Epilogue:wght@700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/base.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/components.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
  <meta name="robots" content="noindex,nofollow">
  <style>
    /* ── Aanvragen-specifiek (niet in admin.css) ── */

    /* Foto labels */
    .foto-img  { max-width:120px;max-height:80px;border-radius:6px;border:1px solid var(--adm-border); }
    .foto-lbl  { font-size:.72rem;color:var(--adm-muted);margin-top:.3rem; }

    /* Berichten & acties layout */
    .berichten-kolommen  { display:grid;grid-template-columns:1fr 1fr;gap:0;border-top:1px solid var(--adm-border); }
    .berichten-overzicht { padding:1.25rem;border-right:1px solid var(--adm-border); }
    .bericht-sturen      { padding:1.25rem; }
    @media (max-width:768px) {
      .berichten-kolommen  { grid-template-columns:1fr; }
      .berichten-overzicht { border-right:none;border-bottom:1px solid var(--adm-border); }
    }

    /* Activiteitenlog */
    .log-lijst  { max-height:320px;overflow-y:auto; }
    .log-item   { display:flex;gap:.75rem;padding:.4rem 0;border-bottom:1px solid var(--adm-surface-2);align-items:flex-start; }
    .log-item:last-child { border-bottom:none; }
    .log-time   { font-size:.72rem;color:var(--adm-faint);white-space:nowrap;min-width:80px;margin-top:.15rem; }
    .log-tekst  { font-size:.83rem;color:var(--adm-text);line-height:1.5; }
    .log-tekst small { display:block;color:var(--adm-muted);font-size:.78rem; }
    .log-leeg   { font-size:.82rem;color:var(--adm-faint); }

    /* Bericht form */
    .opmerking-field { width:100%;padding:.5rem .75rem;border:1.5px solid var(--adm-border);border-radius:7px;font-size:.85rem;font-family:inherit;margin-top:.5rem;resize:vertical;min-height:60px;background:var(--adm-surface-2);color:var(--adm-ink); }
    .opmerking-field:focus { outline:none;border-color:var(--adm-accent);box-shadow:0 0 0 3px var(--adm-accent-ring); }
    .bericht-footer { margin-top:.6rem; }

    /* Actie-separator */
    .actie-separator { text-align:center;position:relative;margin:1rem 0 .75rem;border-top:1px solid var(--adm-border); }
    .actie-separator span { background:var(--adm-surface);padding:0 .75rem;font-size:.75rem;color:var(--adm-faint);position:relative;top:-.65rem; }
    .actie-info { font-size:.82rem;color:var(--adm-muted);margin-bottom:.6rem; }

    /* Aanvraagtype gekleurde buttons */
    .aanvraagtype-buttons { display:flex;gap:.45rem;flex-wrap:wrap;margin-bottom:.75rem; }
    .btn-type             { padding:.45rem .9rem;border:none;border-radius:8px;font-size:.82rem;font-weight:700;cursor:pointer;transition:opacity .15s,transform .1s;white-space:nowrap; }
    .btn-type:hover       { opacity:.85; }
    .btn-type:active      { transform:scale(.97); }
    .btn-type.active-type { outline:3px solid var(--adm-ink);outline-offset:2px; }
    .btn-type-reparatie   { background:#16a34a;color:#fff; }
    .btn-type-taxatie     { background:#2563eb;color:#fff; }
    .btn-type-coulance    { background:#d97706;color:#fff; }
    .btn-type-garantie    { background:#7c3aed;color:#fff; }
    .btn-type-recycling   { background:#0f766e;color:#fff; }

    /* Status actieknoppen (detail) */
    .actie-knoppen { display:flex;gap:.5rem;flex-wrap:wrap;margin-top:.6rem; }
    .btn-actie        { padding:.5rem .9rem;border:none;border-radius:8px;font-size:.82rem;font-weight:700;cursor:pointer;transition:opacity .15s; }
    .btn-actie:hover  { opacity:.85; }
    .btn-coulance     { background:#d97706;color:#fff; }
    .btn-recycling    { background:#0f766e;color:#fff; }
    .btn-archief      { background:#94a3b8;color:#fff; }
    .btn-behandeld    { background:#475569;color:#fff; }

    /* Type-select wijzigen (detail) */
    .type-select-wrap select  { padding:.45rem .75rem;border:1.5px solid var(--adm-border);border-radius:7px;font-size:.85rem;font-family:inherit;background:var(--adm-surface);cursor:pointer;color:var(--adm-ink); }
    .type-select-wrap button  { margin-left:.4rem;padding:.45rem .85rem;background:var(--adm-ink);color:#fff;border:none;border-radius:7px;font-size:.82rem;font-weight:700;cursor:pointer; }
    .type-select-wrap button:hover { background:var(--adm-accent); }

    /* ── Nieuw: advies-voorstel blok ── */
    .advies-voorstel-blok { display:flex;align-items:flex-start;gap:1rem;flex-wrap:wrap;background:var(--adm-surface-2);border:1.5px solid var(--adm-border);border-radius:10px;padding:1rem 1.25rem; }
    .advies-voorstel-type { font-size:.95rem;font-weight:800;color:var(--adm-ink);margin-bottom:.2rem; }
    .advies-voorstel-info { font-size:.83rem;color:var(--adm-muted);line-height:1.55; }

    /* ── Nieuw: keuze-knoppen (inzending route-selectie) ── */
    .keuze-knoppen      { display:flex;gap:.5rem;flex-wrap:wrap; }
    .btn-keuze          { padding:.55rem 1.1rem;border:1.5px solid transparent;border-radius:8px;font-size:.83rem;font-weight:700;cursor:pointer;transition:opacity .15s,transform .1s;white-space:nowrap; }
    .btn-keuze:hover    { opacity:.85; }
    .btn-keuze:active   { transform:scale(.97); }
    .btn-keuze-doorgaan { background:#16a34a;color:#fff;padding:.6rem 1.4rem;font-size:.88rem; }
    .btn-keuze-afwijzen { background:#dc2626;color:#fff; }
    .btn-keuze-alt      { background:var(--adm-surface);color:var(--adm-text); }

    /* ── Nieuw: tandwiel/instellingen knop in header ── */
    .tandwiel-btn       { display:flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:8px;border:1.5px solid var(--adm-border);background:var(--adm-surface);cursor:pointer;font-size:1rem;color:var(--adm-muted);transition:background .15s,border-color .15s; }
    .tandwiel-btn:hover { background:var(--adm-bg);border-color:var(--adm-muted); }

    /* ── Nieuw: formulier-placeholder (afwachting) ── */
    .formulier-placeholder      { display:flex;align-items:flex-start;gap:1rem;background:#fefce8;border:1.5px solid #fde047;border-radius:10px;padding:1rem 1.25rem; }
    .formulier-placeholder-icon { font-size:1.5rem;line-height:1;flex-shrink:0;margin-top:.1rem; }
    .formulier-placeholder strong { display:block;font-size:.9rem;color:var(--adm-ink);margin-bottom:.25rem; }
    .formulier-placeholder p    { font-size:.83rem;color:var(--adm-muted);margin:0;line-height:1.55; }

    /* ── Nieuw: beoordeling 3-knoppen (ingevuld) ── */
    .actie-3knoppen             { display:flex;gap:.5rem;flex-wrap:wrap;align-items:flex-start; }
    .btn-beoordeling            { padding:.55rem 1.1rem;border:none;border-radius:8px;font-size:.83rem;font-weight:700;cursor:pointer;transition:opacity .15s;white-space:nowrap; }
    .btn-beoordeling:hover      { opacity:.85; }
    .btn-beoordeling-groen      { background:#16a34a;color:#fff; }
    .btn-beoordeling-oranje     { background:#d97706;color:#fff; }
    .btn-beoordeling-rood       { background:#dc2626;color:#fff; }
    .wijzig-dropdown            { position:absolute;top:calc(100% + 6px);left:0;z-index:100;background:var(--adm-surface);border:1.5px solid var(--adm-border);border-radius:10px;box-shadow:var(--adm-shadow-md);padding:.75rem;min-width:220px; }

    /* Casenummer kolom */
    .casenr-col a { font-size:.78rem;font-weight:700;color:#1d4ed8;letter-spacing:.03em;text-decoration:none; }
    .casenr-col a:hover { text-decoration:underline; }

    /* Optiemenu */
    .optiemenu-wrap       { position:relative; }
    .optiemenu-btn        { display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px;width:36px;height:36px;border-radius:8px;border:1.5px solid var(--adm-border);background:var(--adm-surface);cursor:pointer;transition:background .15s,border-color .15s; }
    .optiemenu-btn:hover  { background:var(--adm-bg);border-color:var(--adm-muted); }
    .optiemenu-btn span   { display:block;width:5px;height:5px;border-radius:50%;background:var(--adm-muted); }
    .optiemenu-dropdown   { display:none;position:absolute;right:0;top:calc(100% + 6px);z-index:200;background:var(--adm-surface);border:1.5px solid var(--adm-border);border-radius:10px;box-shadow:var(--adm-shadow-md);min-width:200px;overflow:hidden; }
    .optiemenu-wrap.open .optiemenu-dropdown { display:block; }
    .optiemenu-header     { padding:.5rem .9rem;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--adm-faint);border-bottom:1px solid var(--adm-border); }
    .optiemenu-item       { display:block;width:100%;text-align:left;padding:.55rem .9rem;font-size:.85rem;font-weight:600;cursor:pointer;border:none;background:none;color:var(--adm-text);transition:background .1s; }
    .optiemenu-item:hover { background:var(--adm-bg); }
    .optiemenu-item.danger       { color:#b91c1c; }
    .optiemenu-item.danger:hover { background:#fef2f2; }
    .optiemenu-divider    { border:none;border-top:1px solid var(--adm-border);margin:.25rem 0; }
    .optiemenu-type-dot   { display:inline-block;width:9px;height:9px;border-radius:50%;margin-right:.5rem;vertical-align:middle; }
  </style>
</head>
<body>

<?php require_once __DIR__ . '/includes/admin-header.php'; ?>

<div class="adm-page">
  <div class="page-header-row">
    <div>
      <h1 class="adm-page-title">Inzendingen</h1>
      <p class="adm-page-subtitle">Overzicht van alle aanvragen en inzendingen.</p>
    </div>
  </div>

  <?php if ($msg):  ?><div class="alert alert-success"><?= h($msg) ?></div><?php endif; ?>
  <?php if ($fout): ?><div class="alert alert-error"><?= h($fout) ?></div><?php endif; ?>
  <?php if (isset($_GET['saved'])): ?><div class="alert alert-success">&#10003; Wijziging opgeslagen.</div><?php endif; ?>

  <?php if ($detail): ?>
  <?php
    $sl           = $statusLabels[$detail['status']] ?? ['tekst' => $detail['status'], 'badge' => 'badge-gray'];
    $isDefinitief = in_array($detail['status'], $statusDefinitief);
    $huidigType   = $detail['gekozen_advies'] ?? $detail['aanvraag_type'] ?? $detail['advies_type'] ?? '';
    $routeAdvies  = $detail['geadviseerde_route'] ?? '';
    $isInzending  = ($detail['status'] === 'inzending');
    $backQs       = http_build_query(array_filter(['status'=>$filterStatus,'route'=>$filterRoute,'zoek'=>$filterZoek]));
    $routeLabels  = [
      'reparatie' => 'Op basis van merk, model en klacht is reparatie aan huis de meest geschikte route.',
      'taxatie'   => 'Er is sprake van externe schade; een taxatierapport is de aangewezen route voor de verzekeraar (€49).',
      'garantie'  => 'De televisie valt waarschijnlijk nog binnen de wettelijke garantietermijn.',
      'coulance'  => 'De garantie is verlopen, maar er is kans op een coulanceregeling bij de fabrikant of verkoper.',
      'recycling' => 'Dit model staat als niet-repareerbaar in de database; verantwoorde recycling is de aangewezen route.',
    ];
  ?>

  <div class="admin-card detail-card">

    <!-- ── Header (altijd zichtbaar) ─────────────────────────────────────── -->
    <div class="detail-header">
      <div>
        <h2>Aanvraag #<?= (int)$detail['id'] ?></h2>
        <?php if (!empty($detail['casenummer'])): ?>
          <span class="detail-casenr">
            <a href="?id=<?= (int)$detail['id'] ?><?= $backQs ? '&'.$backQs : '' ?>">
              <?= h($detail['casenummer']) ?>
            </a>
          </span>
        <?php endif; ?>
      </div>
      <div class="detail-header-right">
        <span class="badge <?= $sl['badge'] ?>"><?= h($sl['tekst']) ?></span>
        <!-- Tandwiel: handmatige advies-override (altijd beschikbaar) -->
        <div class="optiemenu-wrap">
          <button type="button" class="tandwiel-btn" onclick="toggleOptiemenu(this)" title="Instellingen" aria-label="Instellingen">&#9881;</button>
          <div class="optiemenu-dropdown">
            <div class="optiemenu-header">Advies handmatig wijzigen</div>
            <?php foreach ($aanvraagTypes as $ts => $ti): ?>
            <form method="POST" style="margin:0;">
              <input type="hidden" name="csrf"          value="<?= csrf() ?>">
              <input type="hidden" name="action"        value="set_type">
              <input type="hidden" name="aanvraag_id"   value="<?= (int)$detail['id'] ?>">
              <input type="hidden" name="aanvraag_type" value="<?= h($ts) ?>">
              <button type="submit" class="optiemenu-item<?= $huidigType === $ts ? ' active-type' : '' ?>">
                <span class="optiemenu-type-dot" style="background:<?= h($ti['kleur']) ?>;"></span>
                <?= h($ti['label']) ?><?= $huidigType === $ts ? ' ✓' : '' ?>
              </button>
            </form>
            <?php endforeach; ?>
          </div>
        </div>
        <a href="?<?= h($backQs) ?>" class="btn btn-sm btn-secondary">&larr; Terug naar lijst</a>
      </div>
    </div>

    <?php if ($isInzending): ?>

    <!-- ══ INZENDING-WEERGAVE ══════════════════════════════════════════════ -->

    <!-- Geadviseerd advies -->
    <div class="detail-section">
      <h4>Geadviseerd advies</h4>
      <?php $rtInfo = $aanvraagTypes[$routeAdvies] ?? null; ?>
      <?php if ($rtInfo): ?>
      <div class="advies-voorstel-blok">
        <span style="display:inline-flex;align-items:center;background:<?= h($rtInfo['kleur']) ?>;color:#fff;padding:.35rem .9rem;border-radius:8px;font-size:.83rem;font-weight:800;white-space:nowrap;flex-shrink:0;">
          <?= h($rtInfo['label']) ?>
        </span>
        <div>
          <div class="advies-voorstel-type"><?= h($rtInfo['label']) ?></div>
          <div class="advies-voorstel-info"><?= h($routeLabels[$routeAdvies] ?? '') ?></div>
        </div>
      </div>
      <?php else: ?>
      <p style="font-size:.85rem;color:var(--adm-faint);">Geen advies bepaald via het stappenplan.</p>
      <?php endif; ?>
    </div>

    <!-- Klantgegevens -->
    <div class="detail-section">
      <h4>Klantgegevens</h4>
      <div class="specs-grid">
        <span class="lbl">E-mail</span>            <span class="val"><?= h($detail['email']    ?? '—') ?></span>
        <span class="lbl">Naam</span>              <span class="val"><?= h($detail['naam']     ?? '—') ?></span>
        <span class="lbl">Telefoon</span>          <span class="val"><?= h($detail['telefoon'] ?? '—') ?></span>
        <span class="lbl">Adres</span>             <span class="val"><?= h($detail['adres']    ?? '—') ?></span>
        <span class="lbl">Postcode / Plaats</span> <span class="val"><?= h(trim(($detail['postcode']??'').' '.($detail['plaats']??$detail['woonplaats']??''))) ?: '—' ?></span>
      </div>
    </div>

    <!-- TV-gegevens -->
    <div class="detail-section">
      <h4>TV-gegevens</h4>
      <div class="specs-grid">
        <span class="lbl">Merk</span>              <span class="val"><?= h($detail['merk']          ?? '—') ?></span>
        <span class="lbl">Modelnummer</span>        <span class="val"><?= h($detail['modelnummer']   ?? '—') ?></span>
        <span class="lbl">Serienummer</span>        <span class="val"><?= h($detail['serienummer']   ?? '—') ?></span>
        <span class="lbl">Aankoopjaar</span>        <span class="val"><?= h($detail['aanschafjaar']  ?? $detail['aankoopjaar'] ?? '—') ?></span>
        <span class="lbl">Aanschafwaarde</span>     <span class="val"><?= h($detail['aanschafwaarde'] ?? '—') ?></span>
        <span class="lbl">Situatie</span>           <span class="val"><?= h($detail['situatie']      ?? '—') ?></span>
        <span class="lbl">Klacht</span>             <span class="val"><?= h($detail['klacht_type']   ?? '—') ?></span>
        <span class="lbl">Omschrijving</span>       <span class="val"><?= h($detail['omschrijving']  ?? '—') ?></span>
        <span class="lbl">Geadviseerde route</span> <span class="val">
          <?= h($detail['geadviseerde_route'] ?? '—') ?>
          <?= $detail['coulance_kans'] ? ' <span style="color:var(--adm-muted);font-size:.8rem;">(' . (int)$detail['coulance_kans'] . '% kans)</span>' : '' ?>
        </span>
      </div>
    </div>

    <!-- Foto's -->
    <?php if (!empty($detail['foto_defect']) || !empty($detail['foto_label']) || !empty($detail['foto_bon'])): ?>
    <div class="detail-section">
      <h4>Foto's</h4>
      <div class="fotos-wrap">
        <?php foreach (['foto_defect' => 'Defect', 'foto_label' => 'Label', 'foto_bon' => 'Aankoopbon'] as $fk => $fl): ?>
          <?php if (!empty($detail[$fk])): ?>
          <div class="foto-item">
            <a href="<?= BASE_URL ?>/<?= h($detail[$fk]) ?>" target="_blank">
              <img src="<?= BASE_URL ?>/<?= h($detail[$fk]) ?>" alt="<?= $fl ?>" class="foto-img" loading="lazy">
            </a>
            <div class="foto-lbl"><?= $fl ?></div>
          </div>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Keuze-knoppen -->
    <div class="detail-section">
      <h4>Kies een route</h4>
      <p style="font-size:.85rem;color:var(--adm-muted);margin-bottom:.75rem;">
        Ga door met het voorgestelde advies of kies een andere route. De inzender ziet daarna het bijbehorende formulier.
      </p>
      <div class="keuze-knoppen">

        <?php if ($routeAdvies && isset($aanvraagTypes[$routeAdvies])): ?>
        <form method="POST" style="margin:0;">
          <input type="hidden" name="csrf"           value="<?= csrf() ?>">
          <input type="hidden" name="action"         value="kies_advies">
          <input type="hidden" name="aanvraag_id"    value="<?= (int)$detail['id'] ?>">
          <input type="hidden" name="gekozen_advies" value="<?= h($routeAdvies) ?>">
          <button type="submit" class="btn-keuze btn-keuze-doorgaan">
            &#10003; Doorgaan met <?= h($aanvraagTypes[$routeAdvies]['label']) ?>
          </button>
        </form>
        <?php endif; ?>

        <?php foreach ($aanvraagTypes as $typeSlug => $typeInfo): ?>
          <?php if ($typeSlug === $routeAdvies) continue; ?>
          <form method="POST" style="margin:0;">
            <input type="hidden" name="csrf"           value="<?= csrf() ?>">
            <input type="hidden" name="action"         value="kies_advies">
            <input type="hidden" name="aanvraag_id"    value="<?= (int)$detail['id'] ?>">
            <input type="hidden" name="gekozen_advies" value="<?= h($typeSlug) ?>">
            <button type="submit" class="btn-keuze btn-keuze-alt"
                    style="border-color:<?= h($typeInfo['kleur']) ?>;color:<?= h($typeInfo['kleur']) ?>;">
              <?= h($typeInfo['label']) ?>
            </button>
          </form>
        <?php endforeach; ?>

        <form method="POST" style="margin:0;">
          <input type="hidden" name="csrf"           value="<?= csrf() ?>">
          <input type="hidden" name="action"         value="kies_advies">
          <input type="hidden" name="aanvraag_id"    value="<?= (int)$detail['id'] ?>">
          <input type="hidden" name="gekozen_advies" value="afwijzen">
          <button type="submit" class="btn-keuze btn-keuze-afwijzen">&#10007; Afwijzen</button>
        </form>

      </div>
    </div>

    <?php else: ?>

    <!-- ══ OVERIGE STATUSSEN ═════════════════════════════════════════════════ -->
    <?php
      $afwachtingTypes = ['reparatie_afwachting','taxatie_afwachting','garantie_afwachting',
                          'coulance_afwachting','recycling_afwachting'];
      $ingevuldTypes   = ['reparatie_ingevuld','taxatie_ingevuld','garantie_ingevuld',
                          'coulance_ingevuld','recycling_ingevuld'];
      $isAfwachting = in_array($detail['status'], $afwachtingTypes);
      $isIngevuld   = in_array($detail['status'], $ingevuldTypes);
      $statusType   = $huidigType ?: str_replace(['_afwachting','_ingevuld'], '', $detail['status']);
      $stInfo       = $aanvraagTypes[$statusType] ?? null;
    ?>

    <?php if ($isAfwachting || $isIngevuld): ?>

    <!-- Gekozen route (stap 5 + 6) -->
    <div class="detail-section">
      <h4>Gekozen route</h4>
      <?php if ($stInfo): ?>
      <div class="advies-voorstel-blok">
        <span style="display:inline-flex;align-items:center;background:<?= h($stInfo['kleur']) ?>;color:#fff;padding:.35rem .9rem;border-radius:8px;font-size:.83rem;font-weight:800;white-space:nowrap;flex-shrink:0;">
          <?= h($stInfo['label']) ?>
        </span>
        <div>
          <div class="advies-voorstel-type"><?= h($stInfo['label']) ?></div>
          <div class="advies-voorstel-info"><?= h($routeLabels[$statusType] ?? '') ?></div>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Klantgegevens -->
    <div class="detail-section">
      <h4>Klantgegevens</h4>
      <div class="specs-grid">
        <span class="lbl">E-mail</span>            <span class="val"><?= h($detail['email']    ?? '—') ?></span>
        <span class="lbl">Naam</span>              <span class="val"><?= h($detail['naam']     ?? '—') ?></span>
        <span class="lbl">Telefoon</span>          <span class="val"><?= h($detail['telefoon'] ?? '—') ?></span>
        <span class="lbl">Adres</span>             <span class="val"><?= h($detail['adres']    ?? '—') ?></span>
        <span class="lbl">Postcode / Plaats</span> <span class="val"><?= h(trim(($detail['postcode']??'').' '.($detail['plaats']??$detail['woonplaats']??''))) ?: '—' ?></span>
      </div>
    </div>

    <!-- TV-gegevens -->
    <div class="detail-section">
      <h4>TV-gegevens</h4>
      <div class="specs-grid">
        <span class="lbl">Merk</span>        <span class="val"><?= h($detail['merk']        ?? '—') ?></span>
        <span class="lbl">Modelnummer</span>  <span class="val"><?= h($detail['modelnummer'] ?? '—') ?></span>
        <span class="lbl">Serienummer</span>  <span class="val"><?= h($detail['serienummer'] ?? '—') ?></span>
        <span class="lbl">Aankoopjaar</span>  <span class="val"><?= h($detail['aanschafjaar'] ?? $detail['aankoopjaar'] ?? '—') ?></span>
        <span class="lbl">Klacht</span>       <span class="val"><?= h($detail['klacht_type'] ?? '—') ?></span>
        <span class="lbl">Omschrijving</span> <span class="val"><?= h($detail['omschrijving'] ?? '—') ?></span>
      </div>
    </div>

    <?php if ($isAfwachting): ?>
    <!-- ── STAP 5: Wacht op formulier van klant ────────────────────────── -->
    <div class="detail-section">
      <div class="formulier-placeholder">
        <div class="formulier-placeholder-icon">&#8987;</div>
        <div>
          <strong>Wacht op formulier van klant</strong>
          <p>De inzender heeft het <?= $stInfo ? h($stInfo['label']) : 'vereiste' ?>-formulier nog niet ingevuld. Zodra de klant dit doet, wijzigt de status automatisch naar &ldquo;<?= $stInfo ? h($stInfo['label']) : '' ?> ingevuld&rdquo;.</p>
        </div>
      </div>
    </div>

    <?php elseif ($isIngevuld): ?>
    <!-- ── STAP 6: Ingevuld formulier + beoordeling ─────────────────────── -->
    <div class="detail-section">
      <h4>Ingevuld formulier</h4>
      <div class="specs-grid">
        <?php if (!empty($detail['reden_schade'])): ?>
        <span class="lbl">Reden schade</span>  <span class="val"><?= h($detail['reden_schade']) ?></span>
        <?php endif; ?>
        <?php if (!empty($detail['aankoopbedrag'])): ?>
        <span class="lbl">Aankoopbedrag</span> <span class="val"><?= h($detail['aankoopbedrag']) ?></span>
        <?php endif; ?>
        <?php if (!empty($detail['aankoopdatum'])): ?>
        <span class="lbl">Aankoopdatum</span>  <span class="val"><?= h($detail['aankoopdatum']) ?></span>
        <?php endif; ?>
        <?php if (isset($detail['heeft_bon']) && $detail['heeft_bon'] !== null): ?>
        <span class="lbl">Aankoopbon</span>    <span class="val"><?= $detail['heeft_bon'] ? 'Ja' : 'Nee' ?></span>
        <?php endif; ?>
        <?php if (!empty($detail['naam_verzekeraar'])): ?>
        <span class="lbl">Verzekeraar</span>   <span class="val"><?= h($detail['naam_verzekeraar']) ?></span>
        <?php endif; ?>
        <?php if (!empty($detail['polisnummer'])): ?>
        <span class="lbl">Polisnummer</span>   <span class="val"><?= h($detail['polisnummer']) ?></span>
        <?php endif; ?>
      </div>
    </div>

    <div class="detail-section">
      <h4>Beoordeling</h4>
      <div class="actie-3knoppen">
        <form method="POST" style="margin:0;">
          <input type="hidden" name="csrf"         value="<?= csrf() ?>">
          <input type="hidden" name="action"       value="status">
          <input type="hidden" name="aanvraag_id"  value="<?= (int)$detail['id'] ?>">
          <input type="hidden" name="nieuw_status" value="behandeld">
          <button type="submit" class="btn-beoordeling btn-beoordeling-groen">&#10003; Doorgaan</button>
        </form>
        <div style="position:relative;">
          <button type="button" class="btn-beoordeling btn-beoordeling-oranje"
                  onclick="var d=this.nextElementSibling;d.style.display=d.style.display==='block'?'none':'block'">
            &#9998; Wijzigen
          </button>
          <div class="wijzig-dropdown" style="display:none;">
            <form method="POST">
              <input type="hidden" name="csrf"        value="<?= csrf() ?>">
              <input type="hidden" name="action"      value="status">
              <input type="hidden" name="aanvraag_id" value="<?= (int)$detail['id'] ?>">
              <select name="nieuw_status" style="padding:.4rem .6rem;border:1.5px solid var(--adm-border);border-radius:7px;font-size:.83rem;margin-bottom:.4rem;width:100%;">
                <?php
                  $skipWijzig = array_merge($afwachtingTypes, ['inzending']);
                  foreach ($statusLabels as $sv => $si):
                    if (in_array($sv, $skipWijzig)) continue;
                ?>
                <option value="<?= h($sv) ?>" <?= $detail['status'] === $sv ? 'selected' : '' ?>><?= h($si['tekst']) ?></option>
                <?php endforeach; ?>
              </select>
              <button type="submit" class="btn btn-primary btn-sm" style="width:100%;">Opslaan</button>
            </form>
          </div>
        </div>
        <form method="POST" style="margin:0;">
          <input type="hidden" name="csrf"           value="<?= csrf() ?>">
          <input type="hidden" name="action"         value="kies_advies">
          <input type="hidden" name="aanvraag_id"    value="<?= (int)$detail['id'] ?>">
          <input type="hidden" name="gekozen_advies" value="afwijzen">
          <button type="submit" class="btn-beoordeling btn-beoordeling-rood">&#10007; Afwijzen</button>
        </form>
      </div>
    </div>

    <?php endif; // afwachting vs ingevuld ?>

    <?php else: ?>
    <!-- ── Legacy/eindstatussen (behandeld, archief, afgewezen, …) ────────── -->
    <!-- Klantgegevens -->
    <div class="detail-section">
      <h4>Klantgegevens</h4>
      <div class="specs-grid">
        <span class="lbl">E-mail</span><span class="val"><?= h($detail['email'] ?? '—') ?></span>
        <span class="lbl">Naam</span><span class="val"><?= h($detail['naam'] ?? '—') ?></span>
        <span class="lbl">Telefoon</span><span class="val"><?= h($detail['telefoon'] ?? '—') ?></span>
        <span class="lbl">Adres</span><span class="val"><?= h($detail['adres'] ?? '—') ?></span>
        <span class="lbl">Postcode / Plaats</span><span class="val"><?= h(trim(($detail['postcode']??'').' '.($detail['woonplaats']??''))) ?: '—' ?></span>
      </div>
    </div>
    <!-- TV-gegevens -->
    <div class="detail-section">
      <h4>TV-gegevens</h4>
      <div class="specs-grid">
        <span class="lbl">Merk</span><span class="val"><?= h($detail['merk'] ?? '—') ?></span>
        <span class="lbl">Modelnummer</span><span class="val"><?= h($detail['modelnummer'] ?? '—') ?></span>
        <span class="lbl">Serienummer</span><span class="val"><?= h($detail['serienummer'] ?? '—') ?></span>
        <span class="lbl">Aankoopjaar</span><span class="val"><?= h($detail['aankoopjaar'] ?? '—') ?></span>
        <span class="lbl">Route</span><span class="val"><?= h($detail['geadviseerde_route'] ?? $detail['advies_type'] ?? '—') ?></span>
      </div>
    </div>
    <!-- Status wijzigen -->
    <div class="detail-section">
      <div class="actie-knoppen">
        <form method="POST" style="margin:0;">
          <input type="hidden" name="csrf"         value="<?= csrf() ?>">
          <input type="hidden" name="action"       value="status">
          <input type="hidden" name="aanvraag_id"  value="<?= (int)$detail['id'] ?>">
          <input type="hidden" name="nieuw_status" value="behandeld">
          <button type="submit" class="btn-actie btn-behandeld">Behandeld</button>
        </form>
        <form method="POST" style="margin:0;">
          <input type="hidden" name="csrf"         value="<?= csrf() ?>">
          <input type="hidden" name="action"       value="status">
          <input type="hidden" name="aanvraag_id"  value="<?= (int)$detail['id'] ?>">
          <input type="hidden" name="nieuw_status" value="archief">
          <button type="submit" class="btn-actie btn-archief">Archiveren</button>
        </form>
      </div>
    </div>

    <?php endif; // afwachting/ingevuld/legacy ?>

    <!-- ── STAP 7: Chat — alleen zichtbaar als status ≠ inzending ───────── -->
    <div class="detail-section" style="padding:0;border:none;">
      <div class="berichten-kolommen">
        <div class="berichten-overzicht">
          <h4>Activiteitenlog</h4>
          <?php if (empty($detail['log'])): ?>
            <p class="log-leeg">Nog geen activiteit geregistreerd.</p>
          <?php else: ?>
          <div class="log-lijst">
            <?php foreach (array_reverse($detail['log']) as $lg): ?>
            <div class="log-item">
              <span class="log-time"><?= h(substr($lg['aangemaakt'] ?? '', 0, 16)) ?></span>
              <span class="log-tekst">
                <?= h($lg['actie'] ?? '') ?>
                <?php if (!empty($lg['opmerking'])): ?>
                  <small><?= h($lg['opmerking']) ?></small>
                <?php endif; ?>
              </span>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
        <div class="bericht-sturen">
          <h4>Bericht sturen aan klant</h4>
          <form method="POST">
            <input type="hidden" name="csrf"        value="<?= csrf() ?>">
            <input type="hidden" name="action"      value="bericht">
            <input type="hidden" name="aanvraag_id" value="<?= (int)$detail['id'] ?>">
            <textarea name="bericht" class="opmerking-field" placeholder="Typ hier uw bericht aan de klant…"></textarea>
            <div class="bericht-footer">
              <button type="submit" class="btn btn-primary btn-sm">Verzenden</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <?php endif; // einde $isInzending (stap 7: chat alleen voor niet-inzending) ?>

  </div><!-- /.detail-card -->

  <?php else: ?>

  <!-- Lijst: overzicht aanvragen -->
  <div class="filter-bar">
    <form method="GET" id="filter-form" style="display:contents;">
      <select name="status" onchange="this.form.submit()">
        <option value="">Alle statussen</option>
        <?php foreach ($statusLabels as $val => $lbl): ?>
          <option value="<?= $val ?>" <?= $filterStatus === $val ? 'selected' : '' ?>><?= h($lbl['tekst']) ?></option>
        <?php endforeach; ?>
      </select>
      <input type="text" name="zoek" placeholder="Zoek op e-mail, merk, model, casenr…" value="<?= h($filterZoek) ?>">
      <button type="submit">Zoeken</button>
      <?php if ($filterStatus || $filterZoek || $filterRoute): ?>
        <a href="?" class="btn btn-sm btn-secondary">Wis filters</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="admin-card">
    <h2><?= count($aanvragen) ?> aanvragen</h2>
    <?php if (empty($aanvragen)): ?>
      <p style="color:var(--adm-faint);padding:1rem 0;">Geen aanvragen gevonden.</p>
    <?php else: ?>
    <table class="admin-table">
      <thead>
        <tr>
          <th>Casenr.</th>
          <th>E-mail</th>
          <th>Merk / Model</th>
          <th>Route</th>
          <th>Type</th>
          <th>Status</th>
          <th>Datum</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($aanvragen as $r):
        $sl        = $statusLabels[$r['status']] ?? ['tekst' => $r['status'], 'badge' => 'badge-gray'];
        $rType     = $r['aanvraag_type'] ?? $r['advies_type'] ?? '';
        $rTypeInfo = $aanvraagTypes[$rType] ?? null;
        $qs        = http_build_query(array_filter(['status' => $filterStatus, 'route' => $filterRoute, 'zoek' => $filterZoek]));
      ?>
      <tr>
        <td class="casenr-col">
          <a href="?id=<?= $r['id'] ?><?= $qs ? '&'.$qs : '' ?>">
            <?= h($r['casenummer'] ?? '#'.$r['id']) ?>
          </a>
        </td>
        <td style="font-size:.85rem;"><?= h($r['email'] ?? '—') ?></td>
        <td style="font-size:.85rem;"><?= h(($r['merk']??'').' '.($r['modelnummer']??'')) ?></td>
        <td style="font-size:.82rem;color:var(--adm-muted);"><?= h($r['geadviseerde_route'] ?? $r['advies_type'] ?? '—') ?></td>
        <td>
          <?php if ($rTypeInfo): ?>
            <span style="display:inline-flex;align-items:center;gap:.35rem;font-size:.78rem;font-weight:700;
              background:<?= h($rTypeInfo['kleur']) ?>;color:<?= h($rTypeInfo['tekst']) ?>;
              padding:.2rem .55rem;border-radius:6px;">
              <?= h($rTypeInfo['label']) ?>
            </span>
          <?php else: ?>
            <span style="font-size:.78rem;color:var(--adm-faint);">—</span>
          <?php endif; ?>
        </td>
        <td><span class="badge <?= $sl['badge'] ?>"><?= h($sl['tekst']) ?></span></td>
        <td style="font-size:.8rem;color:var(--adm-faint);"><?= h($r[$datumKolom] ?? '—') ?></td>
        <td>
          <div class="optiemenu-wrap">
            <button type="button" class="optiemenu-btn" onclick="toggleOptiemenu(this)" aria-label="Opties">
              <span></span><span></span><span></span>
            </button>
            <div class="optiemenu-dropdown">
              <div class="optiemenu-header">Acties</div>
              <a href="?id=<?= $r['id'] ?><?= $qs ? '&'.$qs : '' ?>"
                 class="optiemenu-item">&#128065; Openen</a>
              <hr class="optiemenu-divider">
              <div class="optiemenu-header" style="padding-top:.35rem;">Aanvraagtype</div>
              <?php foreach ($aanvraagTypes as $ts => $ti): ?>
              <form method="POST" style="margin:0;">
                <input type="hidden" name="csrf"          value="<?= csrf() ?>">
                <input type="hidden" name="action"        value="set_type_lijst">
                <input type="hidden" name="aanvraag_id"   value="<?= (int)$r['id'] ?>">
                <input type="hidden" name="aanvraag_type" value="<?= h($ts) ?>">
                <button type="submit" class="optiemenu-item<?= $rType === $ts ? ' active-type' : '' ?>">
                  <span class="optiemenu-type-dot" style="background:<?= h($ti['kleur']) ?>;"></span>
                  <?= h($ti['label']) ?>
                  <?= $rType === $ts ? ' ✓' : '' ?>
                </button>
              </form>
              <?php endforeach; ?>
              <hr class="optiemenu-divider">
              <div class="optiemenu-header" style="padding-top:.35rem;">Status</div>
              <?php foreach ($statusLabels as $sv => $si): ?>
              <form method="POST" style="margin:0;">
                <input type="hidden" name="csrf"         value="<?= csrf() ?>">
                <input type="hidden" name="action"       value="status">
                <input type="hidden" name="aanvraag_id"  value="<?= (int)$r['id'] ?>">
                <input type="hidden" name="nieuw_status" value="<?= h($sv) ?>">
                <button type="submit" class="optiemenu-item">
                  <?= h($si['tekst']) ?>
                  <?= $r['status'] === $sv ? ' ✓' : '' ?>
                </button>
              </form>
              <?php endforeach; ?>
            </div>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

  <?php endif; ?>

</div><!-- /.adm-page -->

<script>
function toggleOptiemenu(btn) {
  var wrap   = btn.closest('.optiemenu-wrap');
  var isOpen = wrap.classList.contains('open');
  document.querySelectorAll('.optiemenu-wrap.open').forEach(function(w){ w.classList.remove('open'); });
  if (!isOpen) wrap.classList.add('open');
}
document.addEventListener('click', function(e) {
  if (!e.target.closest('.optiemenu-wrap')) {
    document.querySelectorAll('.optiemenu-wrap.open').forEach(function(w){ w.classList.remove('open'); });
  }
});
</script>
</body>
</html>