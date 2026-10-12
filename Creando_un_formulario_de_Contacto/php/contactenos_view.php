<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style2.css?v=2">
    <link rel="icon" href="Iconos/SBC_TRANSPORT.ico" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Condensed:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <title>Contacto | SMARTT</title>
</head>
<body>

<div class="wrap">
  <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');?>" method="post" novalidate>
    <img class="form-brand" src="Iconos/SBC_TRANSPORT.png" alt="SMARTT - Transporte de carga pesada">
    <h1 class="form-title">Contáctenos</h1>
    <p class="form-intro">Envíanos tu consulta y nuestro equipo se pondrá en contacto contigo.</p>

    <label class="visually-hidden" for="nombre">Nombre</label>
    <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Nombre" autocomplete="name" value="<?php if(!$enviado && isset($nombre)) echo htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?>">

    <label class="visually-hidden" for="correo">Correo electrónico</label>
    <input type="email" class="form-control" id="correo" name="correo" placeholder="Correo electrónico" autocomplete="email" value="<?php if(!$enviado && isset($correo)) echo htmlspecialchars($correo, ENT_QUOTES, 'UTF-8'); ?>">

    <label class="visually-hidden" for="mensaje">Mensaje</label>
    <textarea name="mensaje" class="form-control" id="mensaje" placeholder="¿En qué podemos ayudarte?"><?php if(!$enviado && isset($mensaje)) echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?></textarea>
    <?php if(!empty($errores)) : ?>
    <div class="alert error">
        <?php foreach($errores as $error) : ?>
            <p><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endforeach; ?>
    </div>
    <?php elseif(isset($enviado) && $enviado): ?>
    <div class="alert success">
        <p>Enviado Correctamente</p>
    </div>
    <?php endif ?>

    <div class="form-actions">
      <a class="btn btn-secondary" href="<?php echo $urlVolver !== '' ? htmlspecialchars($urlVolver, ENT_QUOTES, 'UTF-8') : '#'; ?>"<?php echo $urlVolver === '' ? ' onclick="if (history.length > 1) { history.back(); } else { return false; }"' : ''; ?>>Volver</a>
      <input type="submit" name="submit" class="btn btn-primary" value="Enviar Correo">
    </div>
  </form>
</div>
</body>
</html>