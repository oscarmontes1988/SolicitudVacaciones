<?php

/**
 * Archivo: config/compatibility.php
 *
 * Rol: Proporcionar funciones de compatibilidad (polyfills) para versiones antiguas de PHP.
 */

// Polyfill para random_bytes() para PHP < 7.0
if (!function_exists('random_bytes')) {
    /**
     * Genera bytes pseudo-aleatorios criptográficamente seguros.
     *
     * ADVERTENCIA: Esta es una implementación de respaldo para sistemas sin random_bytes().
     * La fuente de aleatoriedad puede ser menos segura que la implementación nativa de PHP 7+.
     * Se recomienda encarecidamente actualizar PHP.
     *
     * @param int $length El número de bytes a generar.
     * @return string Los bytes generados.
     * @throws Exception Si no se puede encontrar una fuente segura de aleatoriedad.
     */
    function random_bytes($length)
    {
        return openssl_random_pseudo_bytes($length);
    }
}
