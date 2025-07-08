<?php

/**
 * Archivo de utilidad para crear hashes de contraseñas.
 * ¡ADVERTENCIA! Elimina este archivo de tu servidor de producción una vez que hayas actualizado las contraseñas.
 */

if (isset($_POST['password'])) {
    $password = $_POST['password'];
    // PASSWORD_DEFAULT es la mejor opción, ya que se actualizará automáticamente
    // con las nuevas versiones de PHP si se introduce un algoritmo más fuerte.
    $hash = password_hash($password, PASSWORD_DEFAULT);
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <title>Generador de Hash</title>
</head>

<body>
    <h1>Generador de Hash de Contraseña</h1>
    <form method="POST">
        <input type="text" name="password" placeholder="Escribe la contraseña aquí" size="30">
        <button type="submit">Generar Hash</button>
    </form>
    <?php if (isset($hash)): ?>
        <h2>Hash Generado:</h2>
        <p>Copia y pega esto en la columna 'password' de tu base de datos:</p>
        <pre><strong><?php echo htmlspecialchars($hash); ?></strong></pre>
    <?php endif; ?>
</body>

</html>