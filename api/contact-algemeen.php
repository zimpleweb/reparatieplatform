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

$naam       = strip_tags(trim($_POST['naam']       ?? ''));
$email      = filter_var(trim($_POST['email']      ?? ''), FILTER_VALIDATE_EMAIL);
$onderwerp  = strip_tags(trim($_POST['onderwerp']  ?? ''));
$bericht    = strip_tags(trim($_POST['bericht']    ?? ''));
$casenummer = strip_tags(trim($_POST['casenummer'] ?? ''));

if (!$naam || !$email || !$onderwerp || !$bericht) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'validation']);
    exit;
}

$subject  = 'Contactformulier: ' . $onderwerp . ($casenummer ? ' [' . $casenummer . ']' : '');
$bodyHtml = mailWrap(
    'Contactformulier – ' . htmlspecialchars($onderwerp, ENT_QUOTES, 'UTF-8'),
    '<p>Nieuw bericht via het contactformulier op Reparatieplatform.nl.</p>
    <table width="100%" cellpadding="0" cellspacing="0"
           style="margin:24px 0;border:1.5px solid #e5e4e0;border-radius:12px;overflow:hidden;">
      <tr style="background:#0d0f14;">
        <td style="padding:10px 16px;font-size:12px;font-weight:700;text-transform:uppercase;
                   letter-spacing:.08em;color:#287864;">Contactgegevens</td>
      </tr>
      <tr><td style="padding:8px 16px 4px;">
        <strong>Naam:</strong> ' . htmlspecialchars($naam, ENT_QUOTES, 'UTF-8') . '
      </td></tr>
      <tr><td style="padding:4px 16px;">
        <strong>E-mail:</strong> ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '
      </td></tr>
      <tr><td style="padding:4px 16px;">
        <strong>Onderwerp:</strong> ' . htmlspecialchars($onderwerp, ENT_QUOTES, 'UTF-8') . '
      </td></tr>'
      . ($casenummer ? '<tr><td style="padding:4px 16px;">
        <strong>Casenummer:</strong> ' . htmlspecialchars($casenummer, ENT_QUOTES, 'UTF-8') . '
      </td></tr>' : '') .
      '<tr><td style="padding:4px 16px 12px;">
        <strong>Bericht:</strong><br>'
            . nl2br(htmlspecialchars($bericht, ENT_QUOTES, 'UTF-8')) . '
      </td></tr>
    </table>'
);

$recipients = ['info@zimpleweb.nl'];
try {
    $adminEmails = db()->query(
        "SELECT email FROM admins WHERE email IS NOT NULL AND email != ''"
    )->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($adminEmails)) {
        $recipients = $adminEmails;
    }
} catch (\PDOException $e) {}

$sent = false;
foreach ($recipients as $recipient) {
    if (mailSend($recipient, $subject, $bodyHtml, $email)) {
        $sent = true;
    }
}

echo json_encode(['ok' => $sent]);
