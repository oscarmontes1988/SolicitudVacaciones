<?php
// Archivo: login.php
session_start();
if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}
require_once 'config/database.php';
require_once 'models/user_model.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = getUserByUsername($conn, $_POST['username']);

    if ($user && password_verify($_POST['password'], $user['password'])) {
        $_SESSION['user'] = $user;
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Usuario o contraseña incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Solicitud de Vacaciones</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body class="login-page">
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <i class="fas fa-plane-departure login-icon"></i>
                <h2 class="login-title">Bienvenido al Sistema de Vacaciones</h2>
                <p class="login-subtitle">Inicia sesión para gestionar tus días de descanso.</p>
            </div>

            <?php if ($error) : ?>
                <p class="alert alert-danger"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>

            <form method="POST" action="login.php" class="login-form">
                <div class="form-group-login">
                    <label for="username" class="sr-only">Usuario:</label>
                    <div class="input-icon-group">
                        <i class="fas fa-user icon-left"></i>
                        <input type="text" id="username" name="username" placeholder="Usuario" required class="form-input-login">
                    </div>
                </div>
                <div class="form-group-login">
                    <label for="password" class="sr-only">Contraseña:</label>
                    <div class="input-icon-group">
                        <i class="fas fa-lock icon-left"></i>
                        <input type="password" id="password" name="password" placeholder="Contraseña" required class="form-input-login">
                        <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block" id="loginButton">
                    <span class="btn-text">Iniciar Sesión</span>
                    <span class="spinner" style="display: none;"></span> </button>
            </form>

            <div class="login-footer">
                <a href="forgot_password.php" class="forgot-password-link">¿Olvidaste tu contraseña?</a>
            </div>
        </div>
    </div>

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const passwordField = document.querySelector('#password');
        const loginButton = document.querySelector('#loginButton');
        const btnText = loginButton.querySelector('.btn-text');
        const spinner = loginButton.querySelector('.spinner');

        // Toggle password visibility
        togglePassword.addEventListener('click', function() {
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });

        // Add loading state to button on form submission
        document.querySelector('.login-form').addEventListener('submit', function() {
            btnText.style.display = 'none';
            spinner.style.display = 'inline-block'; // Muestra el spinner
            loginButton.disabled = true; // Deshabilita el botón
        });
    </script>
</body>

</html>