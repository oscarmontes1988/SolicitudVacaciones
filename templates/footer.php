<?php

/**
 * Archivo: templates/footer.php
 *
 * Rol: Parte de la Vista (View). Es el pie de página reutilizable.
 * Cierra las etiquetas HTML abiertas en el header.
 */
?>
</main>
</div>

<!-- MEJORA DE RENDIMIENTO: Es una práctica estándar poner los scripts de JavaScript
         justo antes de cerrar la etiqueta </body>. Esto permite que el navegador
         renderice todo el contenido visible de la página (HTML y CSS) primero,
         lo que da al usuario la percepción de una carga más rápida. -->

<!-- MEJORA UX: Librería para notificaciones más amigables que el alert() por defecto -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="js/script.js"></script>

<?php
// Carga scripts específicos para cada rol para mantener el código organizado.
if (isset($_SESSION['user']) && $_SESSION['user']['rol'] === 'aprobador') {
    echo '<script src="js/dashboard_aprobador.js"></script>';
}
?>
</body>

</html>