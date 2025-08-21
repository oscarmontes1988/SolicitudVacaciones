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

// MEJORA DE SEGURIDAD: Cargar variables de entorno desde el archivo .env
// Esto evita tener credenciales directamente en el código.
require_once __DIR__ . '/../vendor/autoload.php';

// Usamos la sintaxis para la versión 2.x de la librería, compatible con PHP 5.6
$dotenv = new Dotenv\Dotenv(__DIR__ . '/../');
$dotenv->load();

// Leemos las credenciales desde las variables de entorno cargadas.
// El uso de getenv() es la forma estándar de acceder a estas variables.
$db_host = getenv('DB_HOST');
$db_user = getenv('DB_USER');
$db_pass = getenv('DB_PASS');
$db_name = getenv('DB_NAME');

// --- SECCIÓN DE ESTABLECIMIENTO DE CONEXIÓN ROBUSTA ---
// Este bloque de código implementa una conexión en varios pasos, ideal para depurar
// y manejar problemas de conexión complejos (como incompatibilidades de versión).

// Creamos una nueva instancia del objeto mysqli, que es la forma moderna (en la era de PHP 5.6)
// de interactuar con una base de datos MySQL. Es la sucesora de la antigua extensión `mysql_`.
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

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
