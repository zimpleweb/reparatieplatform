<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'csrf']);
    exit;
}

// ── Rate limiting: max 10 berichten per IP per 10 minuten ──────
$rlKey = 'contact_' . (filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP) !== false
    ? $_SERVER['REMOTE_ADDR'] : 'unknown');
$rl = rateLimitBekijk($rlKey);
if ($rl['geblokkeerd']) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'error' => 'ratelimit']);
    exit;
}
rateLimitMislukt($rlKey, 10, 600);

// ── reCAPTCHA v3 ────────────────────────────────────────────────
if (!verifyRecaptcha($_POST['recaptcha_token'] ?? '', 'contact')) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'error' => 'captcha']);
    exit;
}

$merk         = strip_tags(trim($_POST['merk']         ?? ''));
$modelnummer  = strip_tags(trim($_POST['modelnummer']   ?? ''));
$aanschafjaar = strip_tags(trim($_POST['aanschafjaar']  ?? ''));
$klacht_type  = strip_tags(trim($_POST['klacht_type']   ?? ''));
$omschrijving = strip_tags(trim($_POST['omschrijving']  ?? ''));
$email        = filter_var(trim($_POST['email']         ?? ''), FILTER_VALIDATE_EMAIL);

if (!$email || !$merk || !$modelnummer || !$klacht_type) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'validation']);
    exit;
}

$subject  = 'Contactformulier: ' . $merk . ' ' . $modelnummer;
$bodyHtml = mailWrap(
    'Contactformulier – ' . htmlspecialchars($merk, ENT_QUOTES, 'UTF-8')
        . ' ' . htmlspecialchars($modelnummer, ENT_QUOTES, 'UTF-8'),
    '<p>Nieuw bericht via het contactformulier op de homepage.</p>
    <table width="100%" cellpadding="0" cellspacing="0"
           style="margin:24px 0;border:1.5px solid #e5e4e0;border-radius:12px;overflow:hidden;">
      <tr style="background:#0d0f14;">
        <td style="padding:10px 16px;font-size:12px;font-weight:700;text-transform:uppercase;
                   letter-spacing:.08em;color:#287864;">Formuliergegevens</td>
      </tr>
      <tr><td style="padding:8px 16px 4px;">
        <strong>E-mail:</strong> ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '
      </td></tr>
      <tr><td style="padding:4px 16px;">
        <strong>Merk:</strong> ' . htmlspecialchars($merk, ENT_QUOTES, 'UTF-8') . '
      </td></tr>
      <tr><td style="padding:4px 16px;">
        <strong>Modelnummer:</strong> ' . htmlspecialchars($modelnummer, ENT_QUOTES, 'UTF-8') . '
      </td></tr>
      <tr><td style="padding:4px 16px;">
        <strong>Aanschafjaar:</strong> '
            . htmlspecialchars($aanschafjaar ?: 'Onbekend', ENT_QUOTES, 'UTF-8') . '
      </td></tr>
      <tr><td style="padding:4px 16px;">
        <strong>Klacht:</strong> ' . htmlspecialchars($klacht_type, ENT_QUOTES, 'UTF-8') . '
      </td></tr>
      <tr><td style="padding:4px 16px 12px;">
        <strong>Omschrijving:</strong><br>'
            . nl2br(htmlspecialchars($omschrijving ?: '—', ENT_QUOTES, 'UTF-8')) . '
      </td></tr>
    </table>'
);

// Fetch admin recipients from DB (same approach as send-advies.php)
$recipients = ['info@zimpleweb.nl'];
try {
    $adminEmails = db()->query(
        "SELECT email FROM admins WHERE email IS NOT NULL AND email != ''"
    )->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($adminEmails)) {
        $recipients = $adminEmails;
    }
} catch (\PDOException $e) { /* admins-tabel of email-kolom ontbreekt */ }

$sent = false;
foreach ($recipients as $recipient) {
    if (mailSend($recipient, $subject, $bodyHtml, $email)) {
        $sent = true;
    }
}

echo json_encode(['ok' => $sent]);
