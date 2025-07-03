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

// PASO 1: Inicialización del Manejador de Conexión
// `mysqli_init()` prepara un recurso de conexión antes de intentar conectarse.
// Esto es más flexible que `new mysqli()` porque nos permite establecer opciones
// de conexión (como timeouts o certificados SSL) antes de la conexión real.
$conn = mysqli_init();
// Comprobación de sanidad del sistema: Si `mysqli_init()` falla, indica un problema
// fundamental con la instalación de PHP (la extensión mysqli podría estar deshabilitada
// en php.ini). Es un error irrecuperable a nivel de script.

if (!$conn) {
    // `die()` detiene la ejecución inmediatamente. Es la acción correcta aquí, ya que la
    // aplicación es inútil sin una conexión a la BD.
    die("Error crítico: mysqli_init falló. Revisa la configuración de PHP.");
}

// PASO 2: Intento de Conexión Real
// `mysqli_real_connect()` intenta la conexión usando el manejador inicializado.
// El uso del operador de supresión de errores `@` es una decisión deliberada y técnica.
// Evita que PHP emita su propio "Warning" en la pantalla, permitiéndonos implementar
// nuestro propio flujo de manejo de errores, más limpio y controlado, en el paso siguiente.
@mysqli_real_connect($conn, DB_USER, DB_PASS, DB_NAME);

// PASO 3: Verificación Precisa del Error de Conexión
// `mysqli_connect_errno()` es la forma canónica y más fiable de saber si `mysqli_real_connect`
// tuvo éxito. Devuelve 0 si la conexión es correcta, o un código de error numérico en caso contrario.
if (mysqli_connect_errno()) {
    // Si la conexión falla, construimos un mensaje de error detallado para el log o para el desarrollador.
    // Incluir `mysqli_connect_errno()` y `mysqli_connect_error()` es vital para un diagnóstico rápido.
    // Por ejemplo, aquí es donde veríamos errores como "Connection refused" (firewall), "Access denied" (credenciales incorrectas)
    // o el "(2054) Server sent charset unknown..." (incompatibilidad de versión).
    // ADVERTENCIA: En un entorno de producción, este mensaje detallado debería ir a un log de errores,
    // y al usuario se le debería mostrar un mensaje genérico como "Error al conectar con el servicio.".
    die("Conexión fallida: (" . mysqli_connect_errno() . ") " . mysqli_connect_error());
}

// PASO 4: Establecimiento del Conjunto de Caracteres
// Si hemos llegado hasta aquí, la conexión es exitosa. El siguiente paso crítico es
// asegurar la consistencia de la codificación de caracteres.
// `mysqli_set_charset($conn, "utf8")` le dice a MySQL: "A partir de ahora, toda la
// información que te envíe desde PHP estará en UTF-8". Esto previene la corrupción
// de datos con acentos, eñes y otros caracteres no-ASCII. Es una necesidad, no una opción.
if (!mysqli_set_charset($conn, "utf8")) {
    // Aunque es poco común, esta operación podría fallar si el charset no es soportado.
    // Manejamos este caso borde también, para una robustez completa.
    printf("Error al cargar el conjunto de caracteres utf8: %s\n", mysqli_error($conn));
    exit(); // `exit()` es un alias de `die()`.
}
// Si el script concluye sin interrupciones, la variable global `$conn` queda disponible
// para el resto de la aplicación, conteniendo un recurso de conexión válido y listo para usar.
