<?php
// Archivo: reset_password.php

require_once 'config/database.php';
require_once 'models/user_model.php';

$token = isset($_GET['token']) ? $_GET['token'] : null;
$message = '';
$message_type = 'error';
$show_form = false;

if (!$token) {
    $message = 'Token no proporcionado. El enlace no es válido.';
} else {
    $user = getUserByResetToken($conn, $token);

    if (!$user) {
        $message = 'El token no es válido o ha expirado. Por favor, solicita un nuevo enlace.';
    } else {
        $show_form = true; // El token es válido, mostramos el formulario
    }
}

if ($show_form && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    if ($password !== $password_confirm) {
        $message = 'Las contraseñas no coinciden.';
        $message_type = 'error'; // Asegúrate de que el tipo sea 'error' para mostrar el estilo rojo
    } elseif (strlen($password) < 8) {
        $message = 'La contraseña debe tener al menos 8 caracteres.';
        $message_type = 'error';
    } else {
        $new_password_hash = password_hash($password, PASSWORD_DEFAULT);
        updatePassword($conn, $user['id'], $new_password_hash);

        $message = '¡Tu contraseña ha sido actualizada con éxito! Ahora puedes iniciar sesión con tu nueva contraseña.';
        $message_type = 'success';
        $show_form = false; // Ocultar el formulario después del éxito
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
                <i class="fas fa-unlock-alt login-icon"></i>
                <h2 class="login-title">Establecer Nueva Contraseña</h2>
                <p class="login-subtitle">Ingresa y confirma tu nueva contraseña segura.</p>
            </div>

            <?php if ($message) : ?>
                <p class="alert <?php echo ($message_type === 'success') ? 'alert-success' : 'alert-danger'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </p>
            <?php endif; ?>

            <?php if ($show_form) : ?>
                <form method="POST" action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>" class="login-form">
                    <div class="form-group-login"> <label for="password" class="sr-only">Nueva Contraseña:</label>
                        <div class="input-icon-group">
                            <i class="fas fa-lock icon-left"></i>
                            <input type="password" id="password" name="password" placeholder="Nueva Contraseña" required class="form-input-login">
                            <i class="fas fa-eye toggle-password" id="togglePasswordNew"></i>
                        </div>
                    </div>
                    <div class="form-group-login">
                        <label for="password_confirm" class="sr-only">Confirmar Nueva Contraseña:</label>
                        <div class="input-icon-group">
                            <i class="fas fa-lock icon-left"></i>
                            <input type="password" id="password_confirm" name="password_confirm" placeholder="Confirmar Contraseña" required class="form-input-login">
                            <i class="fas fa-eye toggle-password" id="togglePasswordConfirm"></i>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block" id="resetPasswordButton">
                        <span class="btn-text">Guardar Nueva Contraseña</span>
                        <span class="spinner" style="display: none;"></span>
                    </button>
                </form>
            <?php else : ?>
                <div class="login-footer">
                    <a href="login.php" class="forgot-password-link">Ir al inicio de sesión</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Funcionalidad para mostrar/ocultar contraseñas
        function setupPasswordToggle(toggleId, passwordFieldId) {
            const toggle = document.querySelector(toggleId);
            const passwordField = document.querySelector(passwordFieldId);
            if (toggle && passwordField) {
                toggle.addEventListener('click', function() {
                    const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordField.setAttribute('type', type);
                    this.classList.toggle('fa-eye-slash');
                });
            }
        }

        setupPasswordToggle('#togglePasswordNew', '#password');
        setupPasswordToggle('#togglePasswordConfirm', '#password_confirm');

        // Spinner para el botón de envío
        const resetPasswordButton = document.querySelector('#resetPasswordButton');
        if (resetPasswordButton) {
            const btnText = resetPasswordButton.querySelector('.btn-text');
            const spinner = resetPasswordButton.querySelector('.spinner');

            document.querySelector('.login-form').addEventListener('submit', function() {
                // Solo activa el spinner si el formulario es visible (show_form es true)
                if (<?php echo json_encode($show_form); ?>) {
                    btnText.style.display = 'none';
                    spinner.style.display = 'inline-block';
                    resetPasswordButton.disabled = true;
                }
            });
        }
    </script>
</body>

</html>