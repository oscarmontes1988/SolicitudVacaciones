<?php

/**
 * Archivo: templates/header.php
 *
 * Rol: Parte de la Vista (View). Es la cabecera reutilizable de nuestro HTML.
 *
 * Usar plantillas como esta sigue el principio DRY (Don't Repeat Yourself - No te repitas).
 * Así, si queremos cambiar el menú o el título, solo lo hacemos en un lugar.
 */
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Solicitud de Vacaciones</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <div class="container">
        <header>
            <h1>Sistema de Solicitud de Vacaciones</h1>
            <?php if (isset($_SESSION['user'])): // Mostramos esta sección solo si el usuario ha iniciado sesión. 
            ?>
                <div class="user-info">
                    <span>Bienvenido, <?php
                                        // PREVENCIÓN DE XSS (Cross-Site Scripting): ¡La regla de oro número 2!
                                        // SIEMPRE que imprimas en pantalla datos que provengan de un usuario o de la BD,
                                        // debes escaparlos con htmlspecialchars(). Esto convierte caracteres como < y >
                                        // en sus entidades HTML (< y >), evitando que un atacante pueda inyectar
                                        // scripts maliciosos en tu página.
                                        echo htmlspecialchars($_SESSION['user']['nombre_completo']); ?></span>
                    <a href="logout.php">Cerrar Sesión</a>
                </div>
            <?php endif; ?>
        </header>
        <main>