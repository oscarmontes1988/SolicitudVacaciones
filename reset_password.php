<?php
// Archivo: reset_password.php

require_once 'config/database.php';
require_once 'models/user_model.php';

// Se reemplaza el operador de fusión de null (??) por un ternario con isset() para compatibilidad con PHP < 7.0
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
    } elseif (strlen($password) < 8) {
        $message = 'La contraseña debe tener al menos 8 caracteres.';
    } else {
        // Hashear la nueva contraseña
        $new_password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Actualizar la contraseña y limpiar el token
        updatePassword($conn, $user['id'], $new_password_hash);

        $message = '¡Tu contraseña ha sido actualizada con éxito!';
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
    <title>Nueva Contraseña</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body class="login-page">
    <div class="login-container">
        <h2>Crear Nueva Contraseña</h2>

        <?php if ($message): ?>
            <p class="<?php echo $message_type === 'success' ? 'success-message' : 'error-message'; ?>"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <?php if ($show_form): ?>
            <form method="POST" action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>">
                <div class="form-group">
                    <label for="password" class="sr-only">Nueva Contraseña:</label>
                    <input type="password" id="password" name="password" placeholder="Nueva Contraseña" required>
                </div>
                <div class="form-group">
                    <label for="password_confirm" class="sr-only">Confirmar Nueva Contraseña:</label>
                    <input type="password" id="password_confirm" name="password_confirm" placeholder="Confirmar Nueva Contraseña" required>
                </div>
                <button type="submit" class="btn btn-primary">Guardar Contraseña</button>
            </form>
        <?php else: ?>
            <div class="login-footer">
                <a href="login.php">Ir al inicio de sesión</a>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>