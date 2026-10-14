<?php

// // Configuración SMTP Microsoft 365
// require_once '/home/smartbusinessper/Vcanova/correos.php';




require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'Libro_de_Reclamaciones'
    . DIRECTORY_SEPARATOR . 'PHPMailer-master' . DIRECTORY_SEPARATOR . 'PHPMailer-master'
    . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Exception.php';
require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'Libro_de_Reclamaciones'
    . DIRECTORY_SEPARATOR . 'PHPMailer-master' . DIRECTORY_SEPARATOR . 'PHPMailer-master'
    . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'PHPMailer.php';
require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'Libro_de_Reclamaciones'
    . DIRECTORY_SEPARATOR . 'PHPMailer-master' . DIRECTORY_SEPARATOR . 'PHPMailer-master'
    . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Envía el formulario de contacto usando SMTP si hay credenciales configuradas;
 * de lo contrario usa el transporte local de PHP (mail()) para que funcione en
 * XAMPP sin depender de variables de entorno.
 */
// $correosPath = __DIR__ . '/../Vcanova/correos.php';
function enviarCorreoContacto($nombre, $correo, $mensaje)
{
    $cuenta = 'contactenos@smartt.com.pe';
    $contrasena = getenv('CONTACTO_SMTP_PASSWORD') ?: getenv('SMTP_PASSWORD') ?: '';
    $servidor = getenv('CONTACTO_SMTP_HOST') ?: getenv('SMTP_HOST') ?: '';
    $puerto = getenv('CONTACTO_SMTP_PORT') ?: getenv('SMTP_PORT') ?: '587';

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('La dirección de correo no es válida.');
    }

    if (!ctype_digit((string) $puerto)
        || (int) $puerto < 1
        || (int) $puerto > 65535) {
        throw new InvalidArgumentException('El puerto SMTP no es válido.');
    }

    $escape = static function ($valor) {
        return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    };
    $nombreHtml = $escape($nombre);
    $correoHtml = $escape($correo);
    $mensajeHtml = nl2br($escape($mensaje));

    $logoPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
        . 'Iconos' . DIRECTORY_SEPARATOR . 'SBC_TRANSPORT.png';
    if (!is_readable($logoPath)) {
        throw new RuntimeException('No se encontró el logotipo para adjuntarlo al correo.');
    }

    $mailer = new PHPMailer(true);

    if ($contrasena !== '' && $servidor !== '') {
        $mailer->isSMTP();
        $mailer->Host = $servidor;
        $mailer->SMTPAuth = true;
        $mailer->Username = $cuenta;
        $mailer->Password = $contrasena;
        $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mailer->Port = (int) $puerto;
    } else {
        $mailer->isMail();
    }

    $mailer->CharSet = PHPMailer::CHARSET_UTF8;
    $mailer->setFrom($cuenta, 'SMARTT | Contacto');
    $mailer->addAddress($cuenta);
    $mailer->addReplyTo($correo, $nombre);
    if (!$mailer->addEmbeddedImage($logoPath, 'sbcTransportLogo', 'SBC_TRANSPORT.png')) {
        throw new RuntimeException('No se pudo adjuntar el logotipo al correo de contacto.');
    }

    $mailer->isHTML(true);
    $fechaRecepcion = date('d/m/Y H:i:s');
    $mailer->Subject = 'Nuevo mensaje de contacto | SMARTT';
    $mailer->Body = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"></head>'
        . '<body style="margin:0;padding:32px 16px;background-color:#eef3f6;font-family:Arial,Helvetica,sans-serif;color:#10384d;">'
        . '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;">'
        . '<tr><td align="center">'
        . '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:700px;background:#ffffff;border:1px solid #dfe7eb;border-radius:12px;overflow:hidden;">'
        . '<tr><td style="padding:26px 28px 10px;background:#ffffff;border-bottom:3px solid #0e8ca6;">'
        . '<img src="cid:sbcTransportLogo" width="240" alt="SMARTT" style="display:block;max-width:240px;height:auto;">'
        . '</td></tr>'
        . '<tr><td style="padding:32px 28px 8px;">'
        . '<div style="font-size:12px;letter-spacing:1.5px;color:#0e8ca6;font-weight:700;text-transform:uppercase;">Contacto web</div>'
        . '<h2 style="margin:10px 0 8px;font-size:32px;line-height:1.2;color:#0f172a;">Nuevo mensaje recibido</h2>'
        . '<p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#475569;">Se ha registrado una nueva consulta desde el sitio web de SMARTT.</p>'
        . '</td></tr>'
        . '<tr><td style="padding:0 28px 18px;">'
        . '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:separate;border-spacing:0;">'
        . '<tr><td style="padding:12px 0;border-bottom:1px solid #edf2f7;font-size:15px;color:#334155;"><strong style="display:inline-block;width:140px;color:#0f172a;">Nombre:</strong> ' . $nombreHtml . '</td></tr>'
        . '<tr><td style="padding:12px 0;border-bottom:1px solid #edf2f7;font-size:15px;color:#334155;"><strong style="display:inline-block;width:140px;color:#0f172a;">Correo:</strong> ' . $correoHtml . '</td></tr>'
        . '<tr><td style="padding:12px 0;border-bottom:1px solid #edf2f7;font-size:15px;color:#334155;"><strong style="display:inline-block;width:140px;color:#0f172a;">Fecha:</strong> ' . $fechaRecepcion . '</td></tr>'
        . '<tr><td style="padding:12px 0 6px;font-size:15px;color:#334155;"><strong style="display:block;margin-bottom:8px;color:#0f172a;">Mensaje:</strong>'
        . '<div style="background:#f8fafc;border:1px solid #e2e8f0;border-left:4px solid #0e8ca6;border-radius:8px;padding:16px 18px;line-height:1.7;white-space:normal;">' . $mensajeHtml . '</div>'
        . '</td></tr>'
        . '</table>'
        . '</td></tr>'
        . '<tr><td style="padding:24px 28px 30px;">'
        . '<p style="margin:0;font-size:13px;line-height:1.6;color:#64748b;">Por favor responder a este correo utilizando la dirección del remitente.</p>'
        . '</td></tr>'
        . '</table>'
        . '</td></tr>'
        . '</table>'
        . '</body></html>';
    $mailer->AltBody = "Nuevo mensaje recibido\n\n"
        . 'Nombre: ' . $nombre . "\n"
        . 'Correo: ' . $correo . "\n"
        . 'Fecha: ' . $fechaRecepcion . "\n\n"
        . "Mensaje:\n" . $mensaje;

    $mailer->send();
}
