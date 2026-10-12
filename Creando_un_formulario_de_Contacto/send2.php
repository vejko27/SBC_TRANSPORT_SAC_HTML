<?php
/*
 * Configuracion de Microsoft 365 / Microsoft Entra ID:
 *
 * 1. Entra a https://entra.microsoft.com con una cuenta administradora.
 * 2. Ve a Identidad > Aplicaciones > Registros de aplicaciones > Nuevo registro.
 *    Registra la aplicacion y copia "Id. de aplicacion (cliente)" e "Id. de
 *    directorio (inquilino)" de la pagina Informacion general.
 * 3. En la aplicacion, ve a Permisos de API > Agregar un permiso >
 *    Microsoft Graph > Permisos de aplicacion > Mail.Send. Pulsa
 *    "Conceder consentimiento de administrador".
 * 4. Ve a Certificados y secretos > Nuevo secreto de cliente. Guarda el
 *    VALOR del secreto en un lugar seguro; solo se muestra una vez.
 * 5. Configura en el entorno de Apache/PHP las variables:
 *    O365_TENANT_ID, O365_CLIENT_ID, O365_CLIENT_SECRET, O365_FROM_EMAIL.
 *    O365_TO_EMAIL es opcional; si no se configura, el mensaje se envia al
 *    mismo buzon indicado en O365_FROM_EMAIL.
 *    En Windows, configura estas variables para el servicio/usuario que
 *    ejecuta Apache y reinicia Apache. No guardes el secreto en este archivo.
 * 6. El buzon de O365_FROM_EMAIL debe existir en el tenant. El permiso
 *    Mail.Send de aplicacion puede ser muy amplio: antes de produccion,
 *    limita la aplicacion al buzon necesario mediante Exchange Online
 *    Application RBAC o una politica de acceso de aplicaciones.
 *
 * Esto usa OAuth de aplicacion y Microsoft Graph; no requiere la contraseña
 * del buzon. Requiere la extension cURL de PHP habilitada.
 *
 * Para probar este archivo con el formulario, cambia en index.php:
 *     require_once __DIR__ . '/send.php';
 * por:
 *     require_once __DIR__ . '/send2.php';
 */

function enviarCorreoContacto($nombre, $correo, $mensaje)
{
    $tenantId = getenv('O365_TENANT_ID');
    $clientId = getenv('O365_CLIENT_ID');
    $clientSecret = getenv('O365_CLIENT_SECRET');
    $fromEmail = getenv('O365_FROM_EMAIL');
    $toEmail = getenv('O365_TO_EMAIL') ?: $fromEmail;

    if (!$tenantId || !$clientId || !$clientSecret || !$fromEmail) {
        throw new RuntimeException('Faltan variables de entorno de Microsoft Graph.');
    }

    if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL) ||
        !filter_var($toEmail, FILTER_VALIDATE_EMAIL) ||
        !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Una direccion de correo no es valida.');
    }

    $tokenResponse = graphPostForm(
        'https://login.microsoftonline.com/' . rawurlencode($tenantId) . '/oauth2/v2.0/token',
        [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'scope' => 'https://graph.microsoft.com/.default',
            'grant_type' => 'client_credentials',
        ]
    );

    if (empty($tokenResponse['access_token'])) {
        throw new RuntimeException('Microsoft Entra ID no devolvio un token de acceso.');
    }

    $nombreHtml = htmlspecialchars($nombre, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $correoHtml = htmlspecialchars($correo, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $mensajeHtml = nl2br(htmlspecialchars($mensaje, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));

    $mailMessage = [
        'message' => [
            'subject' => 'Nuevo mensaje desde el formulario',
            'body' => [
                'contentType' => 'HTML',
                'content' => '<h3>Nuevo mensaje recibido</h3>'
                    . '<p><strong>Nombre:</strong> ' . $nombreHtml . '</p>'
                    . '<p><strong>Correo:</strong> ' . $correoHtml . '</p>'
                    . '<p><strong>Mensaje:</strong><br>' . $mensajeHtml . '</p>',
            ],
            'toRecipients' => [
                ['emailAddress' => ['address' => $toEmail]],
            ],
            'replyTo' => [
                ['emailAddress' => ['address' => $correo, 'name' => $nombre]],
            ],
        ],
        'saveToSentItems' => true,
    ];

    graphPostJson(
        'https://graph.microsoft.com/v1.0/users/' . rawurlencode($fromEmail) . '/sendMail',
        $tokenResponse['access_token'],
        $mailMessage
    );
}

function graphPostForm($url, array $fields)
{
    return graphCurlRequest($url, [
        'Content-Type: application/x-www-form-urlencoded',
    ], http_build_query($fields), true);
}

function graphPostJson($url, $accessToken, array $data)
{
    graphCurlRequest($url, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json',
    ], json_encode($data, JSON_THROW_ON_ERROR), false);
}

function graphCurlRequest($url, array $headers, $body, $decodeJson)
{
    if (!function_exists('curl_init')) {
        throw new RuntimeException('La extension cURL de PHP no esta habilitada.');
    }

    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($curl);
    $curlError = curl_error($curl);
    $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($response === false) {
        throw new RuntimeException('Error de conexion con Microsoft Graph: ' . $curlError);
    }

    if ($statusCode < 200 || $statusCode >= 300) {
        throw new RuntimeException(
            'Microsoft Graph respondio con HTTP ' . $statusCode . ': ' . $response
        );
    }

    if (!$decodeJson) {
        return null;
    }

    $decoded = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($decoded)) {
        throw new RuntimeException('La respuesta de Microsoft Graph no tiene el formato esperado.');
    }

    return $decoded;
}
