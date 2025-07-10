<?php
// Archivo: forgot_password.php

use PHPMailer\PHPMailer\PHPMailer;
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
        $obscured_email = obscure_email($user['email']);
        $message = "Si los datos son correctos, hemos enviado un enlace a <strong>" . htmlspecialchars($obscured_email) . "</strong> para restablecer tu contraseña.";
        $message_type = 'success';

        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
        setResetToken($conn, $user['id'], $token, $expires_at);

        $reset_link = "http://localhost/Solicitud_Vacaciones/reset_password.php?token=" . $token;
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = MAIL_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = MAIL_USERNAME;
            $mail->Password = MAIL_PASSWORD;
            $mail->SMTPSecure = MAIL_SMTP_SECURE;
            $mail->Port = MAIL_PORT;
            $mail->CharSet = 'UTF-8';

            $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
            $mail->addAddress($user['email'], $user['nombre_completo']);

            $mail->isHTML(true);
            $mail->Subject = 'Restablecimiento de contraseña';
            $mail->Body = "Hola " . htmlspecialchars($user['nombre_completo']) . ",<br><br>Has solicitado restablecer tu contraseña. Haz clic en el siguiente enlace para continuar:<br><a href='{$reset_link}'>Restablecer mi contraseña</a><br><br>Si no solicitaste esto, puedes ignorar este correo.<br><br>Saludos,<br>El equipo del Sistema de Vacaciones.";
            $mail->AltBody = "Hola " . htmlspecialchars($user['nombre_completo']) . ",\n\nHas solicitado restablecer tu contraseña. Copia y pega el siguiente enlace en tu navegador para continuar:\n{$reset_link}\n\nSi no solicitaste esto, puedes ignorar este correo.";

            $mail->send();
        } catch (PHPMailerException $e) {
            error_log("El mensaje no pudo ser enviado. Mailer Error: {$mail->ErrorInfo}");
        }
    } else {
        // Mensaje genérico por seguridad
        $message = 'Si tu usuario está registrado en nuestro sistema, recibirás un correo con las instrucciones.';
        $message_type = 'success'; // Mostrar como éxito incluso si el usuario no existe para no dar pistas a atacantes
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - Sistema de Vacaciones</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body class="login-page">
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <i class="fas fa-key login-icon"></i>
                <h2 class="login-title">¿Olvidaste tu Contraseña?</h2>
                <p class="login-subtitle">Ingresa tu nombre de usuario y te enviaremos un enlace a tu correo registrado.</p>
            </div>

            <?php if (!empty($message)) : ?>
                <p class="alert <?php echo $message_type === 'success' ? 'alert-info' : 'alert-danger'; ?>">
                    <?php echo $message; // No htmlspecialchars aquí porque puede contener HTML (<strong>) 
                    ?>
                </p>
            <?php endif; ?>

            <form method="POST" action="forgot_password.php" class="login-form">
                <div class="form-group-login"> <label for="username" class="sr-only">Nombre de usuario:</label>
                    <div class="input-icon-group">
                        <i class="fas fa-user icon-left"></i>
                        <input type="text" id="username" name="username" placeholder="Nombre de usuario" required class="form-input-login">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block">
                    Enviar Enlace de Restablecimiento
                </button>
            </form>

            <div class="login-footer"> <a href="login.php" class="forgot-password-link">Volver al inicio de sesión</a>
            </div>
        </div>
    </div>
</body>

</html>