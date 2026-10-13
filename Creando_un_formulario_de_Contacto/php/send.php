<?php

/**
 * Envía el formulario mediante Microsoft Graph con credenciales de aplicación.
 */
function enviarCorreoContacto($nombre, $correo, $mensaje)
{
    $config = [
        'tenant_id' => getenv('O365_TENANT_ID'),
        'client_id' => getenv('O365_CLIENT_ID'),
        'client_secret' => getenv('O365_CLIENT_SECRET'),
        'email_remitente' => getenv('O365_EMAIL_FROM'),
        'email_destino' => getenv('O365_EMAIL_TO'),
    ];

    foreach ($config as $clave => $valor) {
        if ($valor === false || $valor === '') {
            throw new RuntimeException('Falta configurar la variable de entorno para ' . $clave . '.');
        }
    }

    if (!function_exists('curl_init')) {
        throw new RuntimeException('La extensión cURL de PHP no está habilitada.');
    }

    $tokenUrl = 'https://login.microsoftonline.com/'
        . rawurlencode($config['tenant_id'])
        . '/oauth2/v2.0/token';

    $tokenResponse = solicitarHttp(
        $tokenUrl,
        ['Content-Type: application/x-www-form-urlencoded'],
        http_build_query([
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'scope' => 'https://graph.microsoft.com/.default',
            'grant_type' => 'client_credentials',
        ], '', '&', PHP_QUERY_RFC3986)
    );

    $tokenData = json_decode($tokenResponse['body'], true);
    if ($tokenResponse['status'] < 200 || $tokenResponse['status'] >= 300
        || !is_array($tokenData) || empty($tokenData['access_token'])) {
        throw new RuntimeException(
            'Microsoft no entregó un token de acceso. Respuesta: ' . $tokenResponse['body']
        );
    }

    $nombreHtml = htmlspecialchars($nombre, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $correoHtml = htmlspecialchars($correo, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $mensajeHtml = nl2br(htmlspecialchars($mensaje, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));

    $logoPath = __DIR__ . '/../Iconos/SBC_TRANSPORT.png';
    if (!is_readable($logoPath)) {
        throw new RuntimeException('No se encontró el logotipo para adjuntarlo al correo.');
    }

    $logoBytes = file_get_contents($logoPath);
    if ($logoBytes === false) {
        throw new RuntimeException('No se pudo leer el logotipo para adjuntarlo al correo.');
    }

    $graphUrl = 'https://graph.microsoft.com/v1.0/users/'
        . rawurlencode($config['email_remitente'])
        . '/sendMail';

    $payload = json_encode([
        'message' => [
            'subject' => 'Nuevo mensaje de contacto desde el sitio web',
            'body' => [
                'contentType' => 'HTML',
                'content' => '<!DOCTYPE html>'
                    . '<html lang="es"><head><meta charset="UTF-8"></head>'
                    . '<body style="margin:0;padding:0;background-color:#f3f4f6;'
                    . 'font-family:Arial,Helvetica,sans-serif;color:#1f2937;">'
                    . '<table role="presentation" cellpadding="0" cellspacing="0" border="0"'
                    . ' width="100%" bgcolor="#f3f4f6"><tr><td align="center"'
                    . ' style="padding:24px 12px;">'
                    . '<table role="presentation" align="center" cellpadding="0" cellspacing="0" border="0"'
                    . ' width="600" style="width:100%;max-width:600px;background-color:#ffffff;'
                    . 'border:1px solid #e5e7eb;border-top:5px solid #087b94;border-radius:8px;">'
                    . '<tr><td style="padding:24px 32px 20px;border-bottom:1px solid #e5e7eb;">'
                    . '<img src="cid:sbcTransportLogo" width="210" alt="SMARTT - Transporte de carga pesada"'
                    . ' style="display:block;width:210px;max-width:100%;height:auto;border:0;margin:0 0 20px;">'
                    . '<p style="margin:0 0 8px;color:#087b94;font-size:12px;font-weight:bold;'
                    . 'letter-spacing:1px;text-transform:uppercase;">Formulario de contacto</p>'
                    . '<h1 style="margin:0;font-size:22px;line-height:1.4;color:#111827;">'
                    . 'Nuevo mensaje recibido</h1>'
                    . '<p style="margin:10px 0 0;font-size:14px;line-height:1.6;color:#6b7280;">'
                    . 'Se ha recibido una nueva consulta desde el sitio web.</p>'
                    . '</td></tr>'
                    . '<tr><td style="padding:24px 32px;">'
                    . '<table role="presentation" cellpadding="0" cellspacing="0" border="0"'
                    . ' width="100%" style="font-size:14px;line-height:1.6;">'
                    . '<tr><td style="padding:0 0 16px;color:#6b7280;width:100px;'
                    . 'vertical-align:top;">Nombre</td><td style="padding:0 0 16px;color:#111827;'
                    . 'font-weight:bold;vertical-align:top;">' . $nombreHtml . '</td></tr>'
                    . '<tr><td style="padding:0 0 16px;color:#6b7280;vertical-align:top;">'
                    . 'Correo</td><td style="padding:0 0 16px;vertical-align:top;"><a href="mailto:'
                    . $correoHtml . '" style="color:#087b94;text-decoration:underline;">'
                    . $correoHtml . '</a></td></tr>'
                    . '</table>'
                    . '<p style="margin:8px 0 8px;color:#6b7280;font-size:14px;">Mensaje</p>'
                    . '<div style="padding:16px;background-color:#f9fafb;border-left:3px solid #8dbb3f;'
                    . 'border-radius:4px;font-size:14px;line-height:1.7;color:#111827;">'
                    . $mensajeHtml . '</div>'
                    . '<p style="margin:24px 0 0;font-size:13px;line-height:1.6;color:#6b7280;">'
                    . 'Puedes responder directamente a este correo para contactar a '
                    . $nombreHtml . '.</p>'
                    . '</td></tr>'
                    . '<tr><td style="padding:16px 32px;background-color:#f9fafb;'
                    . 'border-top:1px solid #e5e7eb;font-size:12px;color:#9ca3af;">'
                    . 'Notificación automática del formulario de contacto.</td></tr>'
                    . '</table></td></tr></table></body></html>',
            ],
            'attachments' => [[
                '@odata.type' => '#microsoft.graph.fileAttachment',
                'name' => 'SBC_TRANSPORT.png',
                'contentType' => 'image/png',
                'contentBytes' => base64_encode($logoBytes),
                'isInline' => true,
                'contentId' => 'sbcTransportLogo',
            ]],
            'toRecipients' => [[
                'emailAddress' => ['address' => $config['email_destino']],
            ]],
            'replyTo' => [[
                'emailAddress' => [
                    'address' => $correo,
                    'name' => $nombre,
                ],
            ]],
        ],
        'saveToSentItems' => true,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if ($payload === false) {
        throw new RuntimeException('No se pudo preparar el mensaje para Microsoft Graph.');
    }

    $sendResponse = solicitarHttp(
        $graphUrl,
        [
            'Authorization: Bearer ' . $tokenData['access_token'],
            'Content-Type: application/json',
        ],
        $payload
    );

    if ($sendResponse['status'] !== 202) {
        throw new RuntimeException(
            'Microsoft Graph no aceptó el correo. Código HTTP '
            . $sendResponse['status'] . '. Respuesta: ' . $sendResponse['body']
        );
    }
}

/**
 * Ejecuta una solicitud HTTP POST y devuelve el código y el cuerpo recibidos.
 */
function solicitarHttp($url, array $headers, $body)
{
    $curl = curl_init($url);
    if ($curl === false) {
        throw new RuntimeException('No se pudo iniciar una solicitud HTTP.');
    }

    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
    ]);

    $responseBody = curl_exec($curl);
    $curlError = curl_error($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($responseBody === false) {
        throw new RuntimeException('Falló la solicitud a Microsoft: ' . $curlError);
    }

    return [
        'status' => $status,
        'body' => $responseBody,
    ];
}
