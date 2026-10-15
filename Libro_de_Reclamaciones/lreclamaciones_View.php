<?php
session_set_cookie_params([
    'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
]);
session_start();
date_default_timezone_set('America/Lima');
require_once __DIR__ . '/enviar_reclamacion.php';

if (empty($_SESSION['reclamacion_csrf'])) {
    $_SESSION['reclamacion_csrf'] = bin2hex(random_bytes(32));
}

$numeroBorrador = $_SESSION['reclamacion_borrador']['numero'] ?? null;
if (($_SESSION['reclamacion_borrador']['version'] ?? null) !== 3
    || !is_string($numeroBorrador)
    || !preg_match('/^RCL-\d{6,}$/', $numeroBorrador)) {
    try {
        $fechaBorrador = new DateTimeImmutable('now', new DateTimeZone('America/Lima'));
        $_SESSION['reclamacion_borrador'] = [
            'version' => 3,
            'numero' => siguienteNumeroReclamacion(),
            'fecha' => $fechaBorrador->format(DateTimeInterface::ATOM),
        ];
    } catch (Throwable $exception) {
        error_log('Error al reservar un número de reclamación: ' . $exception->getMessage());
        $_SESSION['reclamacion_error'] = 'No se pudo iniciar el Libro de Reclamaciones. Inténtalo nuevamente más tarde.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campos = [
        'nombres', 'apellidos', 'tipo_documento', 'numero_documento', 'correo',
        'telefono', 'direccion', 'menor_edad', 'representante', 'relacionado_a',
        'fecha_incidente', 'monto', 'detalle_servicio', 'tipo_solicitud',
        'detalle_reclamacion', 'pedido',
    ];
    $datosAnteriores = [];
    foreach ($campos as $campo) {
        if (isset($_POST[$campo]) && is_string($_POST[$campo])) {
            $datosAnteriores[$campo] = trim($_POST[$campo]);
        }
    }

    $tokenPublicado = $_POST['csrf_token'] ?? '';
    if (!is_string($tokenPublicado)
        || !hash_equals($_SESSION['reclamacion_csrf'], $tokenPublicado)) {
        $_SESSION['reclamacion_error'] = 'La sesión del formulario venció. Recarga la página e intenta nuevamente.';
        $_SESSION['reclamacion_datos'] = $datosAnteriores;
    } elseif (!empty($_POST['website_url'])) {
        $_SESSION['reclamacion_error'] = 'No se pudo procesar el formulario. Verifica los datos e intenta nuevamente.';
        $_SESSION['reclamacion_datos'] = $datosAnteriores;
    } elseif (time() - (int) ($_SESSION['reclamacion_ultimo_envio'] ?? 0) < 30) {
        $_SESSION['reclamacion_error'] = 'Espera unos segundos antes de enviar otra reclamación.';
        $_SESSION['reclamacion_datos'] = $datosAnteriores;
    } else {
        try {
            $datosValidados = validarDatosReclamacion($_POST);
            if (empty($_SESSION['reclamacion_borrador']['numero'])) {
                throw new RuntimeException('No hay un número de seguimiento disponible para esta reclamación.');
            }
            $fechaRegistro = new DateTimeImmutable('now', new DateTimeZone('America/Lima'));
            $numeroRegistro = $_SESSION['reclamacion_borrador']['numero'];
            enviarCorreoReclamacion($datosValidados, $numeroRegistro, $fechaRegistro);
            $_SESSION['reclamacion_exito'] = 'Tu reclamación fue enviada correctamente. Gracias por comunicarte con SMARTT.';
            $_SESSION['reclamacion_registro'] = [
                'numero' => $numeroRegistro,
                'fecha' => $fechaRegistro->format(DateTimeInterface::ATOM),
            ];
            unset($_SESSION['reclamacion_borrador']);
            $_SESSION['reclamacion_ultimo_envio'] = time();
            unset($_SESSION['reclamacion_datos'], $_SESSION['reclamacion_error']);
        } catch (InvalidArgumentException $exception) {
            $_SESSION['reclamacion_error'] = $exception->getMessage();
            $_SESSION['reclamacion_datos'] = $datosAnteriores;
        } catch (Throwable $exception) {
            error_log('Error al enviar una reclamación: ' . $exception->getMessage());
            $_SESSION['reclamacion_error'] = 'No se pudo enviar la reclamación. Inténtalo más tarde o comunícate con nosotros por teléfono.';
            $_SESSION['reclamacion_datos'] = $datosAnteriores;
        }
    }

    header('Location: ' . ($_SERVER['SCRIPT_NAME'] ?? '/Libro_de_Reclamaciones/lreclamaciones_View.php'), true, 303);
    exit;
}

$mensajeExito = $_SESSION['reclamacion_exito'] ?? '';
$mensajeError = $_SESSION['reclamacion_error'] ?? '';
$registro = $_SESSION['reclamacion_registro'] ?? null;
$constanciaVisible = $mensajeExito !== '' && is_array($registro)
    ? $registro
    : ($_SESSION['reclamacion_borrador'] ?? null);
$datosFormulario = $_SESSION['reclamacion_datos'] ?? [];
unset(
    $_SESSION['reclamacion_exito'],
    $_SESSION['reclamacion_error'],
    $_SESSION['reclamacion_datos'],
    $_SESSION['reclamacion_registro']
);

function valorFormulario($campo)
{
    global $datosFormulario;
    $valor = $datosFormulario[$campo] ?? '';
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Registra una queja o reclamo relacionado con los servicios de transporte de SMARTT.">
    <title>Libro de Reclamaciones | SMARTT</title>
    <link rel="icon" type="image/png" href="../images/logo/logo.png">
    <link rel="stylesheet" href="reclamaciones.css">
</head>
<body>
    <main class="page-shell">
        <section class="intro" aria-labelledby="page-title">
            <p class="eyebrow">Atención al cliente</p>
            <h1 id="page-title">Libro de Reclamaciones</h1>
            <p class="intro-copy">En SMARTT queremos atenderte mejor. Completa este formulario para registrar una queja o un reclamo relacionado con nuestros servicios.</p>
            <div class="company-card">
                <span class="company-mark" aria-hidden="true">S</span>
                <div>
                    <strong>SMARTT TRANSPORT S.A.C.</strong>
                    <span>Servicio de transporte de carga</span>
                </div>
            </div>
        </section>

        <div class="form-card">
            <div class="form-heading">
                <div>
                    <h2>Libro de Reclamaciones</h2>
                    <p>Conforme a la <strong>Ley N.º 29571 – Código de Protección y Defensa del Consumidor.</strong> Complete el formulario y conserve su número de registro.</p>
                </div>
                <span class="form-badge"><span aria-hidden="true">●</span> Atención de reclamos</span>
            </div>

            <?php if (is_array($constanciaVisible)): ?>
                <div class="receipt-bar" aria-label="<?= $mensajeExito !== '' && is_array($registro) ? 'Constancia de registro' : 'Número provisional de seguimiento' ?>">
                    <div class="receipt-chip">
                        <span><?= $mensajeExito !== '' && is_array($registro) ? 'N° de registro' : 'N° de seguimiento provisional' ?></span>
                        <strong id="registration-number"><?= htmlspecialchars($constanciaVisible['numero'], ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <button class="copy-registration" id="copy-registration" type="button" aria-label="Copiar número de registro">
                        Copiar
                    </button>
                    <div class="receipt-chip receipt-date">
                        <span><?= $mensajeExito !== '' && is_array($registro) ? 'Fecha de registro' : 'Inicio del formulario' ?></span>
                        <time id="registration-date" datetime="<?= htmlspecialchars($constanciaVisible['fecha'], ENT_QUOTES, 'UTF-8') ?>"></time>
                    </div>
                    <span class="copy-status" id="copy-status" role="status" aria-live="polite"></span>
                    <?php if (!($mensajeExito !== '' && is_array($registro))): ?>
                        <span class="receipt-pending">Se confirma al enviar el formulario.</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($mensajeExito !== ''): ?>
                <div class="form-notice form-notice-success" role="status">
                    <?= htmlspecialchars($mensajeExito, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                </div>
            <?php elseif ($mensajeError !== ''): ?>
                <div class="form-notice form-notice-error" role="alert">
                    <?= htmlspecialchars($mensajeError, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form action="<?= htmlspecialchars($_SERVER['SCRIPT_NAME'] ?? '', ENT_QUOTES, 'UTF-8') ?>" method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['reclamacion_csrf'], ENT_QUOTES, 'UTF-8') ?>">
                <div class="form-trap" aria-hidden="true">
                    <label for="website-url">Deja este campo vacío</label>
                    <input id="website-url" name="website_url" type="text" tabindex="-1" autocomplete="off">
                </div>
                <fieldset class="form-section">
                    <legend><span class="step-number">1</span> Identificación del consumidor</legend>
                    <p class="section-help">Ingresa los datos de la persona que presenta la queja o el reclamo.</p>

                    <div class="field-grid">
                        <div class="field">
                            <label for="nombres">Nombres <span class="required-mark">*</span></label>
                            <input id="nombres" name="nombres" type="text" autocomplete="given-name" placeholder="Ingresa tus nombres" value="<?= valorFormulario('nombres') ?>" required>
                        </div>
                        <div class="field">
                            <label for="apellidos">Apellidos <span class="required-mark">*</span></label>
                            <input id="apellidos" name="apellidos" type="text" autocomplete="family-name" placeholder="Ingresa tus apellidos" value="<?= valorFormulario('apellidos') ?>" required>
                        </div>
                        <div class="field">
                            <label for="tipo-documento">Tipo de documento <span class="required-mark">*</span></label>
                            <select id="tipo-documento" name="tipo_documento" required>
                                <option value="" <?= valorFormulario('tipo_documento') === '' ? 'selected' : '' ?> disabled>Selecciona una opción</option>
                                <option value="DNI" <?= valorFormulario('tipo_documento') === 'DNI' ? 'selected' : '' ?>>DNI</option>
                                <option value="CE" <?= valorFormulario('tipo_documento') === 'CE' ? 'selected' : '' ?>>Carné de extranjería</option>
                                <option value="Pasaporte" <?= valorFormulario('tipo_documento') === 'Pasaporte' ? 'selected' : '' ?>>Pasaporte</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="numero-documento">Número de documento <span class="required-mark">*</span></label>
                            <input id="numero-documento" name="numero_documento" type="text" autocomplete="off" placeholder="Ingresa tu número de documento" value="<?= valorFormulario('numero_documento') ?>" required>
                        </div>
                        <div class="field">
                            <label for="correo">Correo electrónico <span class="required-mark">*</span></label>
                            <input id="correo" name="correo" type="email" autocomplete="email" placeholder="nombre@correo.com" value="<?= valorFormulario('correo') ?>" required>
                        </div>
                        <div class="field">
                            <label for="telefono">Teléfono <span class="required-mark">*</span></label>
                            <input id="telefono" name="telefono" type="tel" autocomplete="tel" placeholder="Ingresa tu teléfono" value="<?= valorFormulario('telefono') ?>" required>
                        </div>
                        <div class="field field-wide">
                            <label for="direccion">Dirección <span class="required-mark">*</span></label>
                            <input id="direccion" name="direccion" type="text" autocomplete="street-address" placeholder="Ingresa tu dirección completa" value="<?= valorFormulario('direccion') ?>" required>
                        </div>
                        <div class="field field-wide">
                            <span class="field-label">¿Eres menor de edad? <span class="required-mark">*</span></span>
                            <div class="radio-group">
                                <label class="radio-option"><input type="radio" name="menor_edad" value="si" <?= valorFormulario('menor_edad') === 'si' ? 'checked' : '' ?> required> Sí</label>
                                <label class="radio-option"><input type="radio" name="menor_edad" value="no" <?= valorFormulario('menor_edad') === 'no' ? 'checked' : '' ?> required> No</label>
                            </div>
                        </div>
                        <div class="field field-wide" id="representante-field" <?= valorFormulario('menor_edad') === 'si' ? '' : 'hidden' ?>>
                            <label for="representante">Nombre del padre, madre o representante <span class="required-mark">*</span></label>
                            <input id="representante" name="representante" type="text" autocomplete="name" placeholder="Ingresa el nombre del representante" value="<?= valorFormulario('representante') ?>" <?= valorFormulario('menor_edad') === 'si' ? 'required' : 'disabled' ?>>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="form-section">
                    <legend><span class="step-number">2</span> Identificación del servicio contratado</legend>
                    <p class="section-help">Cuéntanos sobre el servicio relacionado con tu solicitud.</p>

                    <div class="field-grid">
                        <div class="field">
                            <label for="relacionado-a">Relacionado a <span class="required-mark">*</span></label>
                            <select id="relacionado-a" name="relacionado_a" required>
                                <option value="" <?= valorFormulario('relacionado_a') === '' ? 'selected' : '' ?> disabled>Selecciona una opción</option>
                                <option value="Servicio" <?= valorFormulario('relacionado_a') === 'Servicio' ? 'selected' : '' ?>>Servicio</option>
                                <option value="Producto" <?= valorFormulario('relacionado_a') === 'Producto' ? 'selected' : '' ?>>Producto</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="fecha-incidente">Fecha del incidente <span class="required-mark">*</span></label>
                            <input id="fecha-incidente" name="fecha_incidente" type="date" value="<?= valorFormulario('fecha_incidente') ?>" required>
                        </div>
                        <div class="field">
                            <label for="monto">Monto reclamado (S/) <span class="required-mark">*</span></label>
                            <input id="monto" name="monto" type="number" min="0" step="0.01" inputmode="decimal" placeholder="0.00" value="<?= valorFormulario('monto') ?>" required>
                        </div>
                        <div class="field field-wide">
                            <label for="detalle-servicio">Detalle del producto o servicio <span class="required-mark">*</span></label>
                            <textarea id="detalle-servicio" name="detalle_servicio" rows="3" placeholder="Describe el servicio de transporte contratado" required><?= valorFormulario('detalle_servicio') ?></textarea>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="form-section">
                    <legend><span class="step-number">3</span> Detalle de la reclamación</legend>
                    <p class="section-help">Describe lo ocurrido y qué solución esperas recibir.</p>

                    <div class="field-grid">
                        <div class="field">
                            <label for="tipo-solicitud">Tipo de solicitud <span class="required-mark">*</span></label>
                            <select id="tipo-solicitud" name="tipo_solicitud" required>
                                <option value="" <?= valorFormulario('tipo_solicitud') === '' ? 'selected' : '' ?> disabled>Selecciona una opción</option>
                                <option value="Reclamo" <?= valorFormulario('tipo_solicitud') === 'Reclamo' ? 'selected' : '' ?>>Reclamo</option>
                                <option value="Queja" <?= valorFormulario('tipo_solicitud') === 'Queja' ? 'selected' : '' ?>>Queja</option>
                            </select>
                        </div>
                        <div class="field field-wide">
                            <label for="detalle-reclamacion">Detalle de la reclamación o queja <span class="required-mark">*</span></label>
                            <textarea id="detalle-reclamacion" name="detalle_reclamacion" rows="5" placeholder="Cuéntanos los hechos con el mayor detalle posible" required><?= valorFormulario('detalle_reclamacion') ?></textarea>
                        </div>
                        <div class="field field-wide">
                            <label for="pedido">Pedido del consumidor <span class="required-mark">*</span></label>
                            <textarea id="pedido" name="pedido" rows="3" placeholder="Indica qué solución solicitas" required><?= valorFormulario('pedido') ?></textarea>
                        </div>
                    </div>

                    <div class="definitions">
                        <p><strong>Reclamo:</strong> disconformidad relacionada con un producto o servicio.</p>
                        <p><strong>Queja:</strong> malestar o descontento relacionado con la atención al público, no vinculado directamente con el producto o servicio.</p>
                    </div>
                </fieldset>

                <div class="form-footer">
                    <p>Verifica tus datos antes de enviar el formulario.</p>
                    <button class="submit-button" type="submit" <?= is_array($_SESSION['reclamacion_borrador'] ?? null) ? '' : 'disabled' ?>>Enviar reclamación <span aria-hidden="true">→</span></button>
                </div>
            </form>
        </div>

        <p class="page-note">La presentación de una queja o reclamo no impide acudir a otras vías de solución de controversias ni es requisito previo para presentar una denuncia ante INDECOPI.</p>
    </main>

    <script>
        const registrationDate = document.getElementById('registration-date');
        if (registrationDate) {
            const date = new Date(registrationDate.dateTime);
            registrationDate.textContent = new Intl.DateTimeFormat('es-PE', {
                dateStyle: 'medium',
                timeStyle: 'medium',
                timeZone: 'America/Lima'
            }).format(date);
        }

        const copyRegistrationButton = document.getElementById('copy-registration');
        if (copyRegistrationButton) {
            copyRegistrationButton.addEventListener('click', async () => {
                const registrationNumber = document.getElementById('registration-number').textContent.trim();
                const copyStatus = document.getElementById('copy-status');

                try {
                    if (navigator.clipboard && window.isSecureContext) {
                        await navigator.clipboard.writeText(registrationNumber);
                    } else {
                        const temporaryInput = document.createElement('textarea');
                        temporaryInput.value = registrationNumber;
                        temporaryInput.setAttribute('readonly', '');
                        temporaryInput.style.position = 'fixed';
                        temporaryInput.style.opacity = '0';
                        document.body.appendChild(temporaryInput);
                        temporaryInput.select();
                        const copied = document.execCommand('copy');
                        temporaryInput.remove();
                        if (!copied) {
                            throw new Error('No se pudo copiar el número.');
                        }
                    }

                    copyRegistrationButton.textContent = 'Copiado';
                    copyStatus.textContent = 'Número copiado al portapapeles.';
                } catch (error) {
                    copyRegistrationButton.textContent = 'Error al copiar';
                    copyStatus.textContent = 'Selecciona y copia el número manualmente.';
                }

                window.setTimeout(() => {
                    copyRegistrationButton.textContent = 'Copiar';
                    copyStatus.textContent = '';
                }, 2500);
            });
        }

        const minorRadios = document.querySelectorAll('input[name="menor_edad"]');
        const representativeField = document.getElementById('representante-field');
        const representativeInput = document.getElementById('representante');

        minorRadios.forEach((radio) => {
            radio.addEventListener('change', () => {
                const isMinor = radio.value === 'si' && radio.checked;
                representativeField.hidden = !isMinor;
                representativeInput.disabled = !isMinor;
                representativeInput.required = isMinor;
            });
        });
    </script>
    <script src="../assets/js/site-nav.js?v=11"></script>
    <script src="../assets/js/site-footer.js?v=3"></script>
    <script src="../assets/js/whatsapp-widget.js?v=2"></script>
</body>
</html>
