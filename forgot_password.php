<?php
// Archivo: forgot_password.php

use PHPMailer\PHPMailer\PHPMailer;
// Se usa un alias para la clase Exception de PHPMailer para evitar conflictos con la clase Exception global de PHP.
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Ofusca una dirección de correo electrónico para mostrarla de forma segura.
 * Ejemplo: 'usuario@ejemplo.com' se convierte en 'u****@ejemplo.com'
 * @param string $email La dirección de correo a ofuscar.
 * @return string El correo ofuscado.
 */
function obscure_email($email)
{
    $parts = explode('@', $email);
    if (count($parts) !== 2) return ''; // No es un email válido
    $name = $parts[0];
    $domain = $parts[1];
    return substr($name, 0, 1) . '****' . '@' . $domain;
}

require_once 'config/database.php';
require_once 'models/user_model.php';
require_once 'config/compatibility.php';
require_once 'vendor/autoload.php'; // Carga el autoloader de Composer
require_once 'config/mailer_config.php'; // Carga la configuración del correo

$message = '';
$message_type = ''; // 'success' o 'error'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $user = getUserByUsername($conn, $username);

    if ($user && !empty($user['email'])) {
        // El usuario existe y tiene un email registrado.
        $obscured_email = obscure_email($user['email']);
        $message = "Si los datos son correctos, hemos enviado un enlace a <strong>" . htmlspecialchars($obscured_email) . "</strong> para restablecer tu contraseña.";
        $message_type = 'success';

        // 1. Generar un token seguro
        $token = bin2hex(random_bytes(32));
        // 2. Establecer una fecha de expiración (ej. 1 hora desde ahora)
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
        // 3. Guardar el token y la fecha en la base de datos
        setResetToken($conn, $user['id'], $token, $expires_at);

        // 4. Enviar el correo electrónico real con PHPMailer
        $reset_link = "http://localhost/Solicitud_Vacaciones/reset_password.php?token=" . $token;
        $mail = new PHPMailer(true);

        try {
            // Configuración del servidor
            $mail->isSMTP();
            $mail->Host = MAIL_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = MAIL_USERNAME;
            $mail->Password = MAIL_PASSWORD;
            $mail->SMTPSecure = MAIL_SMTP_SECURE;
            $mail->Port = MAIL_PORT;
            $mail->CharSet = 'UTF-8';

            // Destinatarios
            $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
            $mail->addAddress($user['email'], $user['nombre_completo']);

            // Contenido
            $mail->isHTML(true);
            $mail->Subject = 'Restablecimiento de contraseña';
            $mail->Body = "Hola " . htmlspecialchars($user['nombre_completo']) . ",<br><br>Has solicitado restablecer tu contraseña. Haz clic en el siguiente enlace para continuar:<br><a href='{$reset_link}'>Restablecer mi contraseña</a><br><br>Si no solicitaste esto, puedes ignorar este correo.<br><br>Saludos,<br>El equipo del Sistema de Vacaciones.";
            $mail->AltBody = "Hola " . htmlspecialchars($user['nombre_completo']) . ",\n\nHas solicitado restablecer tu contraseña. Copia y pega el siguiente enlace en tu navegador para continuar:\n{$reset_link}\n\nSi no solicitaste esto, puedes ignorar este correo.";

            $mail->send();
        } catch (PHPMailerException $e) {
            // No mostramos el error al usuario por seguridad, pero lo registramos para el desarrollador.
            error_log("El mensaje no pudo ser enviado. Mailer Error: {$mail->ErrorInfo}");
        }
    } else {
        // Para no revelar si un usuario existe (seguridad), mostramos un mensaje genérico.
        // El atacante no sabrá si el fallo fue por un usuario inexistente o un usuario sin email.
        $message = 'Si tu usuario está registrado en nuestro sistema, recibirás un correo con las instrucciones.';
        $message_type = 'success';
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="login-page">
    <div class="login-container">
        <h2>Restablecer Contraseña</h2>
        <p>Ingresa tu nombre de usuario y te enviaremos un enlace a tu correo registrado para restablecer la contraseña.</p>

        <?php if (!empty($message)): ?>
            <p class="<?php echo $message_type === 'success' ? 'success-message' : 'error-message'; ?>">
                <?php echo $message; // Usamos echo sin htmlspecialchars porque el mensaje puede contener HTML (<strong>, <a>) 
                ?>
            </p>
        <?php endif; ?>

        <form method="POST" action="forgot_password.php">
            <div class="form-group">
                <label for="username" class="sr-only">Nombre de usuario:</label>
                <input type="text" id="username" name="username" placeholder="Nombre de usuario" required>
            </div>
            <button type="submit" class="btn btn-primary">Enviar Enlace</button>
        </form>
        <div class="login-footer">
            <a href="login.php">Volver al inicio de sesión</a>
        </div>
    </div>
</body>

</html>