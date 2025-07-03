<?php

/**
 * Archivo: config/database.php
 *
 * Rol: Punto central y único para la configuración y establecimiento de la conexión a la base de datos.
 *
 * Justificación de Diseño:
 * Centralizar la configuración de la conexión en un solo archivo es una práctica fundamental del
 * principio DRY (Don't Repeat Yourself). Esto nos permite gestionar las credenciales y los
 * parámetros de conexión de forma eficiente y segura. Si la base de datos se migra a otro servidor
 * o las credenciales cambian, este es el único lugar que necesita ser modificado.
 *
 * Advertencia de Seguridad en Producción:
 * En un entorno de producción, este archivo NUNCA debería estar dentro del 'document root'
 * (la carpeta pública como 'htdocs' o 'www'). Un error de configuración del servidor podría
 * exponer su contenido. La práctica correcta es ubicarlo un nivel por encima y cargarlo
 * con una ruta absoluta, por ejemplo: `require_once '/var/www/config/database.php';`.
 */

// --- SECCIÓN DE CONFIGURACIÓN DE CREDENCIALES ---

// Usamos constantes (define) en lugar de variables para las credenciales.
// Justificación Técnica: Las constantes tienen un ámbito global y son inmutables.
// Esto previene que sus valores puedan ser sobrescritos accidentalmente en otra parte
// del código, añadiendo una capa de previsibilidad y seguridad.
define('DB_HOST', '192.168.80.175');
define('DB_USER', 'root'); // ADVERTENCIA: El uso del usuario 'root' en producción es una vulnerabilidad de seguridad crítica. Se debe crear un usuario específico para la aplicación con los permisos mínimos necesarios (Principio de Mínimo Privilegio).
define('DB_PASS', 'M01ses8o8o'); // La contraseña se maneja aquí. En sistemas más avanzados, esto se cargaría desde variables de entorno (.env) para no versionar credenciales en el repositorio de código.
define('DB_NAME', 'empresa_vacaciones');

// --- SECCIÓN DE ESTABLECIMIENTO DE CONEXIÓN ROBUSTA ---
// Este bloque de código implementa una conexión en varios pasos, ideal para depurar
// y manejar problemas de conexión complejos (como incompatibilidades de versión).

// Creamos una nueva instancia del objeto mysqli, que es la forma moderna (en la era de PHP 5.6)
// de interactuar con una base de datos MySQL. Es la sucesora de la antigua extensión `mysql_`.
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// La primera y más importante comprobación después de intentar una conexión.
// Si `connect_error` tiene un valor, significa que la conexión falló.
// Usamos `die()` para detener la ejecución del script inmediatamente. No tiene sentido
// continuar si no podemos hablar con la base de datos.
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// ¡Crítico para aplicaciones multilingües o con caracteres especiales (como ñ, á, é)!
// Le decimos a la base de datos que toda la comunicación que venga desde PHP
// estará codificada en UTF-8. Esto previene problemas de caracteres corruptos (ej: '??').
$conn->set_charset("utf8");
