<?php
/**
 * Mailer – sends HTML emails using PHP mail() with proper RFC 2822 headers.
 * Templates are fetched from the mail_templates DB table (key → subject/body_html).
 * Falls back to a hardcoded default when a template is not found in the DB.
 */

function getMailTemplate(string $key): array {
    static $cache = [];
    if (isset($cache[$key])) return $cache[$key];
    try {
        $s = db()->prepare('SELECT subject, body_html FROM mail_templates WHERE slug = ? AND actief = 1 LIMIT 1');
        $s->execute([$key]);
        $row = $s->fetch(PDO::FETCH_ASSOC);
        if ($row) { $cache[$key] = $row; return $row; }
    } catch (\Exception $e) {}
    $cache[$key] = mailTemplateDefault($key);
    return $cache[$key];
}

function sendMail(string $to, string $templateKey, array $vars = []): bool {
    $tpl = getMailTemplate($templateKey);
    $subject = mailMerge($tpl['subject'], $vars);
    $body    = mailMerge($tpl['body_html'], $vars);
    return mailSend($to, $subject, $body);
}

function mailSend(string $to, string $subject, string $bodyHtml): bool {
    $fromName  = 'ReparatiePlatform.nl';
    $fromEmail = 'noreply@reparatieplatform.nl';
    $boundary  = '----=_Part_' . md5(uniqid());

    $headers  = "From: {$fromName} <{$fromEmail}>\r\n";
    $headers .= "Reply-To: {$fromEmail}\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
    $headers .= "X-Mailer: ReparatiePlatform/1.0\r\n";
    $headers .= "X-Priority: 3\r\n";

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
    return @mail($to, $encodedSubject, $message, $headers);
}

function mailMerge(string $text, array $vars): string {
    // Handle {{#key}}...{{/key}} conditional blocks (show block only if var is non-empty)
    $text = preg_replace_callback(
        '/\{\{#(\w+)\}\}(.*?)\{\{\/\1\}\}/s',
        function ($m) use ($vars) { return !empty($vars[$m[1]]) ? $m[2] : ''; },
        $text
    );
    foreach ($vars as $k => $v) {
        $text = str_replace('{{' . $k . '}}', htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'), $text);
    }
    return $text;
}

function mailWrap(string $title, string $body, string $cta = '', string $ctaUrl = ''): string {
    $ctaBlock = '';
    if ($cta && $ctaUrl) {
        $ctaBlock = '<div style="text-align:center;margin:32px 0;">
            <a href="' . htmlspecialchars($ctaUrl, ENT_QUOTES, 'UTF-8') . '"
               style="background:#287864;color:#fff;text-decoration:none;padding:14px 32px;border-radius:999px;font-family:Arial,sans-serif;font-size:15px;font-weight:700;display:inline-block;">'
            . htmlspecialchars($cta, ENT_QUOTES, 'UTF-8') . '</a></div>';
    }
    return '<!DOCTYPE html><html lang="nl"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title></head>
<body style="margin:0;padding:0;background:#f5f4f1;font-family:Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f4f1;padding:40px 16px;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">
  <!-- Header -->
  <tr><td style="background:#0d0f14;border-radius:16px 16px 0 0;padding:28px 40px;">
    <p style="margin:0;font-family:Arial,sans-serif;font-size:12px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#287864;">ReparatiePlatform.nl</p>
    <h1 style="margin:8px 0 0;font-size:22px;font-weight:800;color:#fff;">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1>
  </td></tr>
  <!-- Body -->
  <tr><td style="background:#fff;padding:36px 40px;color:#0d0f14;font-size:15px;line-height:1.7;">
    ' . $body . $ctaBlock . '
  </td></tr>
  <!-- Footer -->
  <tr><td style="background:#f5f4f1;border-radius:0 0 16px 16px;padding:20px 40px;text-align:center;">
    <p style="margin:0;font-size:12px;color:#9ca3af;">ReparatiePlatform.nl &mdash; Advies over uw televisie. Gratis en vrijblijvend.</p>
    <p style="margin:6px 0 0;font-size:11px;color:#d1d5db;">Aan het advies kunnen geen rechten worden ontleend.</p>
  </td></tr>
</table>
</td></tr>
</table>
</body></html>';
}

/* ─────────────────────────────────────────────────────────────
   Default hardcoded templates (used as fallback + initial data)
───────────────────────────────────────────────────────────── */
function mailTemplateDefault(string $key): array {
    return match ($key) {
        'inzender_bevestiging' => [
            'subject' => 'Uw aanvraag is ontvangen – {{casenummer}}',
            'body_html' => mailWrap(
                'Aanvraag ontvangen',
                '<p>Beste,</p>
                <p>Bedankt voor uw aanvraag bij ReparatiePlatform.nl. We hebben uw inzending goed ontvangen en gaan er zo snel mogelijk mee aan de slag.</p>
                <table width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0;border:1.5px solid #e5e4e0;border-radius:12px;overflow:hidden;">
                  <tr style="background:#f5f4f1;"><td style="padding:12px 16px;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6b7280;">Aanvraagdetails</td></tr>
                  <tr><td style="padding:8px 16px 4px;"><strong>Zaaknummer:</strong> {{casenummer}}</td></tr>
                  <tr><td style="padding:4px 16px;"><strong>Televisie:</strong> {{merk}} {{modelnummer}}</td></tr>
                  <tr><td style="padding:4px 16px;"><strong>Aanschafjaar:</strong> {{aanschafjaar}}</td></tr>
                  <tr><td style="padding:4px 16px 12px;"><strong>Geadviseerde route:</strong> {{geadviseerde_route}}</td></tr>
                </table>
                <p>U ontvangt ons persoonlijk advies binnen <strong>één werkdag</strong>. U kunt de status van uw aanvraag altijd bekijken via de knop hieronder.</p>',
                'Mijn aanvraag bekijken',
                'https://reparatieplatform.nl/mijn-aanvraag.php'
            ),
        ],
        'inzender_advies' => [
            'subject' => 'Uw advies is klaar – {{casenummer}}',
            'body_html' => mailWrap(
                'Uw advies is klaar',
                '<p>Beste,</p>
                <p>Goed nieuws! Wij hebben uw aanvraag <strong>{{casenummer}}</strong> beoordeeld en uw persoonlijke advies staat voor u klaar.</p>
                <div style="background:#e8f4f1;border-left:4px solid #287864;border-radius:0 12px 12px 0;padding:16px 20px;margin:24px 0;">
                  <p style="margin:0;font-weight:700;color:#287864;">Geadviseerde route: {{geadviseerde_route}}</p>
                  <p style="margin:8px 0 0;font-size:14px;color:#0d0f14;">{{advies_toelichting}}</p>
                </div>
                <p>Bekijk het volledige advies inclusief de vervolgstappen via uw persoonlijke klantenomgeving.</p>',
                'Mijn advies bekijken',
                'https://reparatieplatform.nl/mijn-aanvraag.php'
            ),
        ],
        'admin_nieuwe_inzending' => [
            'subject' => '[Admin] Nieuwe inzending – {{casenummer}}',
            'body_html' => mailWrap(
                'Nieuwe inzending ontvangen',
                '<p>Er is een nieuwe aanvraag binnengekomen via het adviesformulier.</p>
                <table width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0;border:1.5px solid #e5e4e0;border-radius:12px;overflow:hidden;">
                  <tr style="background:#0d0f14;"><td style="padding:10px 16px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#287864;">Inzendingsdetails</td></tr>
                  <tr><td style="padding:8px 16px 4px;"><strong>Zaaknummer:</strong> {{casenummer}}</td></tr>
                  <tr><td style="padding:4px 16px;"><strong>E-mail inzender:</strong> {{email}}</td></tr>
                  <tr><td style="padding:4px 16px;"><strong>Televisie:</strong> {{merk}} {{modelnummer}}</td></tr>
                  <tr><td style="padding:4px 16px;"><strong>Aanschafjaar:</strong> {{aanschafjaar}}</td></tr>
                  <tr><td style="padding:4px 16px;"><strong>Situatie:</strong> {{situatie}}</td></tr>
                  <tr><td style="padding:4px 16px;"><strong>Klacht:</strong> {{klacht_type}}</td></tr>
                  <tr><td style="padding:4px 16px;"><strong>Geadviseerde route:</strong> {{geadviseerde_route}}</td></tr>
                  <tr><td style="padding:4px 16px 12px;"><strong>Omschrijving:</strong> {{omschrijving}}</td></tr>
                </table>',
                'Bekijken in admin',
                'https://reparatieplatform.nl/admin/aanvragen.php'
            ),
        ],

        // ── NIEUW: Chat-berichten en statuswijziging ──────────────────────

        'admin_nieuw_chatbericht' => [
            'subject' => '[Admin] Nieuw chatbericht – {{casenummer}}',
            'body_html' => mailWrap(
                'Nieuw chatbericht ontvangen',
                '<p>Er is een nieuw bericht ontvangen via de chat voor inzending <strong>{{casenummer}}</strong>.</p>
                <table width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0;border:1.5px solid #e5e4e0;border-radius:12px;overflow:hidden;">
                  <tr style="background:#0d0f14;"><td style="padding:10px 16px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#287864;">Berichtdetails</td></tr>
                  <tr><td style="padding:8px 16px 4px;"><strong>Zaaknummer:</strong> {{casenummer}}</td></tr>
                  <tr><td style="padding:4px 16px;"><strong>Inzender:</strong> {{email}}</td></tr>
                  <tr><td style="padding:4px 16px;"><strong>Televisie:</strong> {{merk}} {{modelnummer}}</td></tr>
                  <tr><td style="padding:4px 16px 12px;"><strong>Ontvangen op:</strong> {{datum_bericht}}</td></tr>
                </table>
                <div style="background:#f8fafc;border:1.5px solid #e5e4e0;border-radius:12px;padding:20px 24px;margin:0 0 24px;">
                  <p style="margin:0 0 6px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#6b7280;">Bericht van inzender</p>
                  <p style="margin:0;font-size:15px;color:#0d0f14;line-height:1.65;">{{chatbericht}}</p>
                </div>
                <p style="font-size:13px;color:#6b7280;">Reageer zo snel mogelijk via het adminpaneel zodat de inzender geholpen wordt.</p>',
                'Bekijken &amp; beantwoorden',
                'https://reparatieplatform.nl/admin/aanvragen.php'
            ),
        ],

        'inzender_nieuw_chatbericht' => [
            'subject' => 'Nieuw bericht over uw aanvraag – {{casenummer}}',
            'body_html' => mailWrap(
                'U heeft een nieuw bericht',
                '<p>Beste,</p>
                <p>U heeft een nieuw bericht ontvangen van ons team over uw aanvraag <strong>{{casenummer}}</strong>.</p>
                <div style="background:#f8fafc;border:1.5px solid #e5e4e0;border-radius:12px;padding:20px 24px;margin:24px 0;">
                  <p style="margin:0 0 6px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#6b7280;">Bericht van ReparatiePlatform.nl</p>
                  <p style="margin:0;font-size:15px;color:#0d0f14;line-height:1.65;">{{chatbericht}}</p>
                </div>
                <table width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;border:1.5px solid #e5e4e0;border-radius:12px;overflow:hidden;">
                  <tr style="background:#f5f4f1;"><td style="padding:10px 16px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6b7280;">Uw aanvraag</td></tr>
                  <tr><td style="padding:8px 16px 4px;"><strong>Zaaknummer:</strong> {{casenummer}}</td></tr>
                  <tr><td style="padding:4px 16px 12px;"><strong>Televisie:</strong> {{merk}} {{modelnummer}}</td></tr>
                </table>
                <p>U kunt direct reageren via uw persoonlijke aanvraagpagina.</p>',
                'Naar mijn aanvraag',
                'https://reparatieplatform.nl/mijn-aanvraag.php'
            ),
        ],

        'inzender_status_gewijzigd' => [
            'subject' => 'Status gewijzigd voor uw aanvraag – {{casenummer}}',
            'body_html' => mailWrap(
                'Status van uw aanvraag gewijzigd',
                '<p>Beste,</p>
                <p>De status van uw aanvraag <strong>{{casenummer}}</strong> is bijgewerkt door ons team.</p>
                <table width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0;border:1.5px solid #e5e4e0;border-radius:12px;overflow:hidden;">
                  <tr style="background:#f5f4f1;"><td style="padding:10px 16px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6b7280;">Statuswijziging</td></tr>
                  <tr><td style="padding:8px 16px 4px;"><strong>Zaaknummer:</strong> {{casenummer}}</td></tr>
                  <tr><td style="padding:4px 16px;"><strong>Televisie:</strong> {{merk}} {{modelnummer}}</td></tr>
                  <tr><td style="padding:4px 16px;"><strong>Vorige status:</strong> <span style="color:#64748b;">{{status_oud}}</span></td></tr>
                  <tr><td style="padding:4px 16px 12px;"><strong>Nieuwe status:</strong> <span style="font-weight:700;color:#287864;">{{status_nieuw}}</span></td></tr>
                </table>
                {{#toelichting_status}}
                <div style="background:#e8f4f1;border-left:4px solid #287864;border-radius:0 12px 12px 0;padding:16px 20px;margin:0 0 24px;">
                  <p style="margin:0;font-weight:700;color:#287864;font-size:13px;text-transform:uppercase;letter-spacing:.06em;">Toelichting</p>
                  <p style="margin:8px 0 0;font-size:14px;color:#0d0f14;line-height:1.65;">{{toelichting_status}}</p>
                </div>
                {{/toelichting_status}}
                <p>Bekijk uw aanvraag voor meer details en de volgende stappen.</p>',
                'Mijn aanvraag bekijken',
                'https://reparatieplatform.nl/mijn-aanvraag.php'
            ),
        ],

        'admin_formulier_ingevuld' => [
            'subject' => '[Admin] Formulier ingevuld – {{casenummer}}',
            'body_html' => mailWrap(
                'Formulier ingevuld door klant',
                '<p>De klant heeft het aanvraagformulier ingevuld voor inzending <strong>{{casenummer}}</strong>.</p>
                <table width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0;border:1.5px solid #e5e4e0;border-radius:12px;overflow:hidden;">
                  <tr style="background:#0d0f14;"><td style="padding:10px 16px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#287864;">Aanvraagdetails</td></tr>
                  <tr><td style="padding:8px 16px 4px;"><strong>Zaaknummer:</strong> {{casenummer}}</td></tr>
                  <tr><td style="padding:4px 16px;"><strong>E-mail inzender:</strong> {{email}}</td></tr>
                  <tr><td style="padding:4px 16px;"><strong>Televisie:</strong> {{merk}} {{modelnummer}}</td></tr>
                  <tr><td style="padding:4px 16px 12px;"><strong>Type aanvraag:</strong> {{aanvraag_type}}</td></tr>
                </table>
                <p style="font-size:13px;color:#6b7280;">Bekijk de volledige aanvraag in het adminpaneel.</p>',
                'Bekijken in admin',
                'https://reparatieplatform.nl/admin/aanvragen.php'
            ),
        ],

        default => ['subject' => 'Bericht van ReparatiePlatform.nl', 'body_html' => mailWrap('Bericht', '<p>{{bericht}}</p>')],
    };
}

/* ── Haal alle beschikbare template-slugs + defaults op ── */
function getAllMailTemplates(): array {
    $defaults = [
        'inzender_bevestiging'       => ['label' => 'Inzender: Ontvangstbevestiging',         'richting' => 'inzender'],
        'inzender_advies'            => ['label' => 'Inzender: Advies klaar',                  'richting' => 'inzender'],
        'admin_nieuwe_inzending'     => ['label' => 'Admin: Nieuwe inzending',                 'richting' => 'admin'],
        'admin_nieuw_chatbericht'    => ['label' => 'Admin: Nieuw chatbericht',                'richting' => 'admin'],
        'admin_formulier_ingevuld'   => ['label' => 'Admin: Formulier ingevuld door klant',   'richting' => 'admin'],
        'inzender_nieuw_chatbericht' => ['label' => 'Inzender: Nieuw chatbericht',             'richting' => 'inzender'],
        'inzender_status_gewijzigd'  => ['label' => 'Inzender: Status gewijzigd',             'richting' => 'inzender'],
    ];
    $result = [];
    foreach ($defaults as $slug => $meta) {
        $tpl = getMailTemplate($slug);
        $result[] = array_merge(['slug' => $slug], $meta, $tpl);
    }
    return $result;
}