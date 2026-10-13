<?php

require_once __DIR__ . '/php/send.php';

$urlVolver = 'https://smartt.com.pe';
$errores = [];
$enviado = '';

if (isset($_POST['submit'])) {

    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $mensaje = trim($_POST['mensaje'] ?? '');

    // Validación del nombre
    if ($nombre === '') {
        $errores[] = 'Por favor ingresa un nombre';
    } elseif (!preg_match("/^[\p{L}\p{M} .'-]+$/u", $nombre)) {
        $errores[] = 'El nombre contiene caracteres no permitidos';
    }

    if ($correo === '') {
        $errores[] = 'Por favor ingresa un correo';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'Por favor ingresa un correo válido';
    }

    if ($mensaje === '') {
        $errores[] = 'Por favor ingresa el mensaje';
    }

    if (!$errores) {
        try {
            enviarCorreoContacto($nombre, $correo, $mensaje);
            $enviado = true;
        } catch (Exception $e) {
            error_log('Error al enviar el correo: ' . $e->getMessage());
            $errores[] = 'No se pudo enviar el correo. Inténtalo más tarde.';
        }
    }
}

require __DIR__ . '/php/contactenos_view.php';
?>
