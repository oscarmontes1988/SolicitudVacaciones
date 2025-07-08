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

    // --- INICIO DE CÓDIGO DE DEPURACIÓN TEMPORAL ---
    //var_dump($user);
    //die(); // Detiene la ejecución para que podamos ver la salida.
    // --- FIN DE CÓDIGO DE DEPURACIÓN TEMPORAL ---
    // ¡CORRECCIÓN DE SEGURIDAD CRÍTICA!
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
    <title>Login - Sistema de Vacaciones</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="login-page">
    <div class="login-container">
        <h2>Iniciar Sesión</h2>
        <?php if ($error): ?><p class="error-message"><?php echo $error; ?></p><?php endif; ?>
        <form method="POST" action="login.php">
            <div class="form-group"><label for="username">Usuario:</label><input type="text" id="username" name="username" required></div>
            <div class="form-group"><label for="password">Contraseña:</label><input type="password" id="password" name="password" required></div>
            <button type="submit" class="btn btn-primary">Ingresar</button>
        </form>
    </div>
</body>

</html>