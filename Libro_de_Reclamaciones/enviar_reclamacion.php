<?php

// Cargar la configuración SMTP privada del hosting.
require_once '/home/smartbusinessper/Vcanova/correos.php';

require_once __DIR__ . '/PHPMailer-master/PHPMailer-master/src/Exception.php';
require_once __DIR__ . '/PHPMailer-master/PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer-master/PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Reserva un número correlativo para identificar un nuevo borrador.
 *
 * El contador se guarda fuera del directorio público del sitio y se bloquea
 * durante la actualización para evitar números repetidos entre solicitudes.
 */
function siguienteNumeroReclamacion()
{
    $directorioConfigurado = getenv('RECLAMACIONES_STORAGE_DIR');
    if ($directorioConfigurado === false || trim($directorioConfigurado) === '') {
        $raizPublica = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
        if ($raizPublica === false) {
            throw new RuntimeException('No se pudo determinar dónde guardar el contador de reclamaciones.');
        }
        $directorioConfigurado = dirname($raizPublica) . DIRECTORY_SEPARATOR . 'sbc_transport_reclamaciones';
    }

    if (!is_dir($directorioConfigurado)
        && !mkdir($directorioConfigurado, 0700, true)
        && !is_dir($directorioConfigurado)) {
        throw new RuntimeException('No se pudo crear el almacenamiento del contador de reclamaciones.');
    }

    $archivo = rtrim($directorioConfigurado, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'contador.txt';
    $manejador = fopen($archivo, 'c+');
    if ($manejador === false) {
        throw new RuntimeException('No se pudo abrir el contador de reclamaciones.');
    }

    if (!flock($manejador, LOCK_EX)) {
        fclose($manejador);
        throw new RuntimeException('No se pudo bloquear el contador de reclamaciones.');
    }

    try {
        rewind($manejador);
        $contenido = stream_get_contents($manejador);
        if ($contenido === false) {
            throw new RuntimeException('No se pudo leer el contador de reclamaciones.');
        }
        if ($contenido !== '' && !preg_match('/^\d+$/', trim($contenido))) {
            throw new RuntimeException('El contador de reclamaciones tiene un formato inválido.');
        }

        $siguiente = (int) trim($contenido) + 1;
        $valorGuardado = (string) $siguiente . PHP_EOL;
        rewind($manejador);
        if (!ftruncate($manejador, 0)
            || fwrite($manejador, $valorGuardado) !== strlen($valorGuardado)
            || !fflush($manejador)) {
            throw new RuntimeException('No se pudo guardar el contador de reclamaciones.');
        }
    } finally {
        flock($manejador, LOCK_UN);
        fclose($manejador);
    }

    return 'RCL-' . str_pad((string) $siguiente, 6, '0', STR_PAD_LEFT);
}

/**
 * Envía una reclamación por SMTP autenticado de Microsoft 365.
 *
 * La cuenta usada para autenticar, el remitente y el destinatario son el buzón
 * del Libro de Reclamaciones de SMARTT. Configura su credencial SMTP mediante
 * O365_SMTP_PASSWORD en Apache.
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
    $usuario = 'librodereclamaciones@smartt.com.pe';
    $contrasena = getenv('O365_SMTP_PASSWORD');
    $remitente = 'librodereclamaciones@smartt.com.pe';
    $destinatario = 'librodereclamaciones@smartt.com.pe';
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
    $rutaLogo = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'images'
        . DIRECTORY_SEPARATOR . 'logo' . DIRECTORY_SEPARATOR . 'logo.png';
    if (!is_file($rutaLogo) || !$mailer->addEmbeddedImage($rutaLogo, 'smartt-logo', 'logo-sbc-transport.png')) {
        throw new RuntimeException('No se pudo adjuntar el logo de SBC Transport al correo de reclamación.');
    }
    $mailer->Subject = 'Registro ' . $numeroRegistro . ' - '
        . strtolower($datos['tipo_solicitud']) . ' SMARTT';
    $tipoSolicitud = $escape($datos['tipo_solicitud']);
    $numeroSeguro = $escape($numeroRegistro);
    $fechaSegura = $escape($fechaTexto);
    $mailer->Body = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">'
        . '<meta name="viewport" content="width=device-width,initial-scale=1.0"></head>'
        . '<body style="margin:0;padding:32px 12px;background-color:#f1f5f7;'
        . 'font-family:Arial,Helvetica,sans-serif;color:#243746;">'
        . '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" '
        . 'style="border-collapse:collapse;background-color:#f1f5f7;"><tr><td align="center">'
        . '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" '
        . 'style="width:100%;max-width:760px;border-collapse:separate;border-spacing:0;'
        . 'background-color:#ffffff;border:1px solid #dce5e9;border-radius:10px;overflow:hidden;">'
        . '<tr><td align="center" style="padding:30px 28px 24px;border-bottom:4px solid #efbb0c;">'
        . '<img src="cid:smartt-logo" width="210" alt="SBC Transport" '
        . 'style="display:block;width:210px;max-width:100%;height:auto;margin:0 auto 20px;border:0;">'
        . '<p style="margin:0 0 8px;color:#657985;font-size:12px;font-weight:bold;'
        . 'letter-spacing:1.4px;text-transform:uppercase;">Notificación de atención al cliente</p>'
        . '<h1 style="margin:0;color:#006d88;font-size:24px;line-height:1.3;">'
        . 'Nueva solicitud del Libro de Reclamaciones</h1>'
        . '<p style="margin:10px 0 0;color:#526773;font-size:15px;line-height:1.5;">'
        . 'Se ha recibido una nueva solicitud. A continuación encontrarás el detalle para su atención.</p>'
        . '</td></tr>'
        . '<tr><td align="center" style="padding:24px 28px 10px;">'
        . '<span style="display:inline-block;padding:7px 13px;border:1px solid #b9dce4;'
        . 'border-radius:20px;color:#006d88;font-size:12px;font-weight:bold;'
        . 'letter-spacing:.5px;text-transform:uppercase;">' . $tipoSolicitud . '</span>'
        . '<p style="margin:15px 0 4px;color:#657985;font-size:12px;font-weight:bold;'
        . 'letter-spacing:.8px;text-transform:uppercase;">Número de registro</p>'
        . '<p style="margin:0;color:#172b35;font-size:25px;font-weight:bold;line-height:1.3;">'
        . $numeroSeguro . '</p>'
        . '<p style="margin:8px 0 0;color:#526773;font-size:14px;">'
        . '<strong>Fecha y hora:</strong> ' . $fechaSegura . ' (hora de Perú)</p>'
        . '</td></tr>'
        . '<tr><td style="padding:18px 28px 28px;">'
        . '<h2 style="margin:0 0 12px;color:#172b35;font-size:17px;line-height:1.4;">'
        . 'Información proporcionada</h2>'
        . '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" '
        . 'style="width:100%;border-collapse:collapse;border:1px solid #e2e9ec;">'
        . $filas
        . '</table>'
        . '<p style="margin:20px 0 0;color:#657985;font-size:12px;line-height:1.5;text-align:center;">'
        . 'Este mensaje fue generado automáticamente por el Libro de Reclamaciones de SBC Transport.</p>'
        . '</td></tr></table>'
        . '<p style="margin:14px 0 0;color:#81919a;font-size:11px;text-align:center;">'
        . 'SBC TRANSPORT S.A.C.</p>'
        . '</td></tr></table></body></html>';
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
