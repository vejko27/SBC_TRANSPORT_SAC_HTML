<?php

require_once __DIR__ . '/PHPMailer-master/PHPMailer-master/src/Exception.php';
require_once __DIR__ . '/PHPMailer-master/PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer-master/PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Envía una reclamación por SMTP autenticado de Microsoft 365.
 *
 * Cambia las cuentas en la configuración del entorno de Apache, no aquí:
 * - O365_SMTP_USERNAME: buzón usado para autenticar y enviar.
 * - O365_SMTP_FROM: dirección visible del remitente (por defecto, el usuario).
 * - O365_SMTP_TO: destinatario de las reclamaciones. Para esta prueba, si no se
 *   configura, se utiliza victor.canova@gruposbc.com.pe.
 * - O365_SMTP_PASSWORD: contraseña o credencial SMTP del buzón remitente.
 * En XAMPP, define estas variables en la configuración de Apache fuera de
 * htdocs y reinicia Apache. No guardes valores secretos en el proyecto.
 * No guardes contraseñas en este archivo ni en el repositorio.
 */
function enviarCorreoReclamacion(
    array $datos,
    $numeroRegistro,
    DateTimeImmutable $fechaRegistro
)
{
    $usuario = getenv('O365_SMTP_USERNAME') ?: 'impresora1@gruposbc.com.pe';
    $contrasena = getenv('O365_SMTP_PASSWORD');
    $remitente = getenv('O365_SMTP_FROM') ?: $usuario;
    $destinatario = getenv('O365_SMTP_TO') ?: 'victor.canova@gruposbc.com.pe';
    $servidor = getenv('O365_SMTP_HOST') ?: 'smtp.office365.com';
    $puerto = getenv('O365_SMTP_PORT') ?: '587';

    if (!$usuario || !$contrasena || !$remitente || !$destinatario) {
        throw new RuntimeException('Falta configurar el correo SMTP de Microsoft 365 en Apache.');
    }

    if (!filter_var($usuario, FILTER_VALIDATE_EMAIL)
        || !filter_var($remitente, FILTER_VALIDATE_EMAIL)
        || !filter_var($destinatario, FILTER_VALIDATE_EMAIL)
        || !ctype_digit((string) $puerto)
        || (int) $puerto < 1
        || (int) $puerto > 65535) {
        throw new RuntimeException('La configuración SMTP de Microsoft 365 no es válida.');
    }

    $etiquetas = [
        'nombres' => 'Nombres',
        'apellidos' => 'Apellidos',
        'tipo_documento' => 'Tipo de documento',
        'numero_documento' => 'Número de documento',
        'correo' => 'Correo electrónico',
        'telefono' => 'Teléfono',
        'direccion' => 'Dirección',
        'menor_edad' => '¿Es menor de edad?',
        'representante' => 'Padre, madre o representante',
        'relacionado_a' => 'Relacionado a',
        'fecha_incidente' => 'Fecha del incidente',
        'monto' => 'Monto reclamado (S/)',
        'detalle_servicio' => 'Detalle del producto o servicio',
        'tipo_solicitud' => 'Tipo de solicitud',
        'detalle_reclamacion' => 'Detalle de la reclamación o queja',
        'pedido' => 'Pedido del consumidor',
    ];

    $escape = static function ($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    };

    $filas = '';
    $fechaTexto = $fechaRegistro->format('d/m/Y H:i:s');
    $textoPlano = "Se recibió una nueva solicitud en el Libro de Reclamaciones.\n"
        . 'Número de registro: ' . $numeroRegistro . "\n"
        . 'Fecha de registro: ' . $fechaTexto . "\n\n";
    foreach ($etiquetas as $campo => $etiqueta) {
        if ($campo === 'representante' && $datos['menor_edad'] !== 'si') {
            continue;
        }

        $valor = trim((string) $datos[$campo]);
        $filas .= '<tr>'
            . '<th align="left" style="padding:10px;border-bottom:1px solid #e5e7eb;">'
            . $escape($etiqueta)
            . '</th><td style="padding:10px;border-bottom:1px solid #e5e7eb;">'
            . nl2br($escape($valor))
            . '</td></tr>';
        $textoPlano .= $etiqueta . ":\n" . $valor . "\n\n";
    }

    $mailer = new PHPMailer(true);
    $mailer->isSMTP();
    $mailer->Host = $servidor;
    $mailer->SMTPAuth = true;
    $mailer->Username = $usuario;
    $mailer->Password = $contrasena;
    $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mailer->Port = (int) $puerto;
    $mailer->CharSet = PHPMailer::CHARSET_UTF8;

    $mailer->setFrom($remitente, 'SMARTT | Libro de Reclamaciones');
    $mailer->addAddress($destinatario);
    $mailer->addReplyTo($datos['correo'], $datos['nombres'] . ' ' . $datos['apellidos']);
    $mailer->isHTML(true);
    $mailer->Subject = 'Registro ' . $numeroRegistro . ' - '
        . strtolower($datos['tipo_solicitud']) . ' SMARTT';
    $mailer->Body = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"></head>'
        . '<body style="font-family:Arial,sans-serif;color:#1c2933;">'
        . '<h2 style="color:#006d88;">Nueva solicitud del Libro de Reclamaciones</h2>'
        . '<p><strong>Número de registro:</strong> ' . htmlspecialchars($numeroRegistro, ENT_QUOTES, 'UTF-8') . '</p>'
        . '<p><strong>Fecha de registro:</strong> ' . htmlspecialchars($fechaTexto, ENT_QUOTES, 'UTF-8') . '</p>'
        . '<table style="width:100%;max-width:760px;border-collapse:collapse;">'
        . $filas
        . '</table></body></html>';
    $mailer->AltBody = $textoPlano;
    $mailer->send();
}

/**
 * Valida y normaliza los campos recibidos antes de crear el correo.
 */
function validarDatosReclamacion(array $entrada)
{
    $camposObligatorios = [
        'nombres',
        'apellidos',
        'tipo_documento',
        'numero_documento',
        'correo',
        'telefono',
        'direccion',
        'menor_edad',
        'relacionado_a',
        'fecha_incidente',
        'monto',
        'detalle_servicio',
        'tipo_solicitud',
        'detalle_reclamacion',
        'pedido',
    ];

    $datos = [];
    foreach ($camposObligatorios as $campo) {
        $valor = $entrada[$campo] ?? '';
        if (!is_string($valor)) {
            throw new InvalidArgumentException('Revisa los datos ingresados e intenta nuevamente.');
        }
        $datos[$campo] = trim($valor);
        if ($datos[$campo] === '') {
            throw new InvalidArgumentException('Completa todos los campos obligatorios.');
        }
    }

    $datos['representante'] = isset($entrada['representante']) && is_string($entrada['representante'])
        ? trim($entrada['representante'])
        : '';

    if (!in_array($datos['tipo_documento'], ['DNI', 'CE', 'Pasaporte'], true)
        || !in_array($datos['menor_edad'], ['si', 'no'], true)
        || !in_array($datos['relacionado_a'], ['Servicio', 'Producto'], true)
        || !in_array($datos['tipo_solicitud'], ['Reclamo', 'Queja'], true)) {
        throw new InvalidArgumentException('Selecciona opciones válidas en el formulario.');
    }

    if (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Ingresa un correo electrónico válido.');
    }

    if ($datos['menor_edad'] === 'si' && $datos['representante'] === '') {
        throw new InvalidArgumentException('Ingresa el nombre del padre, madre o representante.');
    }

    $fecha = DateTime::createFromFormat('!Y-m-d', $datos['fecha_incidente']);
    if (!$fecha || $fecha->format('Y-m-d') !== $datos['fecha_incidente']) {
        throw new InvalidArgumentException('Ingresa una fecha de incidente válida.');
    }

    if (!is_numeric($datos['monto']) || (float) $datos['monto'] < 0) {
        throw new InvalidArgumentException('Ingresa un monto válido.');
    }

    $limites = [
        'nombres' => 120,
        'apellidos' => 120,
        'numero_documento' => 32,
        'telefono' => 32,
        'direccion' => 240,
        'representante' => 240,
        'detalle_servicio' => 3000,
        'detalle_reclamacion' => 6000,
        'pedido' => 3000,
    ];
    foreach ($limites as $campo => $limite) {
        if (function_exists('mb_strlen')) {
            $longitud = mb_strlen($datos[$campo], 'UTF-8');
        } else {
            $longitud = strlen($datos[$campo]);
        }
        if ($longitud > $limite) {
            throw new InvalidArgumentException('Uno de los campos supera la longitud permitida.');
        }
    }

    return $datos;
}
