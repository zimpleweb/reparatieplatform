<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';

$_origin = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
         . '://' . $_SERVER['HTTP_HOST'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf($_POST['csrf_token'] ?? '')) {
    redirect($_origin . '/?error=csrf#advies');
}

$merk         = strip_tags(trim($_POST['merk']         ?? ''));
$modelnummer  = strip_tags(trim($_POST['modelnummer']   ?? ''));
$aanschafjaar = strip_tags(trim($_POST['aanschafjaar']  ?? ''));
$klacht_type  = strip_tags(trim($_POST['klacht_type']   ?? ''));
$omschrijving = strip_tags(trim($_POST['omschrijving']  ?? ''));
$email        = filter_var(trim($_POST['email']         ?? ''), FILTER_VALIDATE_EMAIL);

if (!$email || !$merk || !$modelnummer || !$klacht_type) {
    redirect($_origin . '/?error=1#advies');
}

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

$fromName  = 'ReparatiePlatform.nl';
$fromEmail = 'noreply@reparatieplatform.nl';
$boundary  = '----=_Part_' . md5(uniqid('', true));
$subject   = 'Contactformulier: ' . $merk . ' ' . $modelnummer;

$headers  = "From: {$fromName} <{$fromEmail}>\r\n";
$headers .= "Reply-To: {$email}\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
$headers .= "X-Mailer: ReparatiePlatform/1.0\r\n";

$bodyText = strip_tags(preg_replace('#<br\s*/?>|</p>|</div>|</li>#i', "\n", $bodyHtml));
$bodyText = html_entity_decode($bodyText, ENT_QUOTES, 'UTF-8');
$bodyText = preg_replace("/\n{3,}/", "\n\n", trim($bodyText));

$message  = "--{$boundary}\r\n";
$message .= "Content-Type: text/plain; charset=UTF-8\r\n";
$message .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
$message .= quoted_printable_encode($bodyText) . "\r\n";
$message .= "--{$boundary}\r\n";
$message .= "Content-Type: text/html; charset=UTF-8\r\n";
$message .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
$message .= quoted_printable_encode($bodyHtml) . "\r\n";
$message .= "--{$boundary}--";

$encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
$sent = @mail('info@zimpleweb.nl', $encodedSubject, $message, $headers, '-f ' . $fromEmail);

redirect($_origin . ($sent ? '/?verzonden=1#advies' : '/?error=1#advies'));
