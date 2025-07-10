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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
    <header class="app-header">
        <div class="header-container">
            <div class="app-brand">
                <a href="dashboard.php"> <i class="fas fa-plane-departure app-logo-icon"></i>
                    <h1>Sistema de Solicitud de Vacaciones</h1>
                </a>
            </div>

            <?php if (isset($_SESSION['user'])) : // Mostramos esta sección solo si el usuario ha iniciado sesión.
            ?>
                <nav class="main-nav">
                    <ul>
                        <li><a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                        <li><a href="solicitar_vacaciones.php" class="nav-link"><i class="fas fa-suitcase"></i> Solicitar</a></li>
                        <li><a href="historial.php" class="nav-link"><i class="fas fa-history"></i> Historial</a></li>
                    </ul>
                </nav>

                <div class="user-profile-actions">
                    <span class="user-welcome-text">Bienvenido,
                        <?php echo htmlspecialchars($_SESSION['user']['nombre_completo']); ?>
                    </span>
                    <a href="logout.php" class="btn btn-logout"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <main> ```