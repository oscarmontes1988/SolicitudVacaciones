<?php

/**
 * Archivo: ajax/guardar_solicitud.php
 *
 * Rol: Endpoint de API para procesar la creación de una nueva solicitud de vacaciones.
 *
 * Arquitectura:
 * Este script actúa como un "mini-controlador" sin vista. Es invocado en segundo plano (vía AJAX)
 * desde el formulario del frontend. Su única responsabilidad es recibir datos, validarlos,
 * interactuar con la capa de modelo para persistir la información y devolver una respuesta
 * estandarizada en formato JSON. Este desacoplamiento es fundamental para mantener el código
 * limpio y escalable.
 *
 * Flujo de Ejecución:
 * 1. Inicia la sesión y establece las cabeceras HTTP.
 * 2. Realiza un control de seguridad para asegurar que el usuario esté autenticado.
 * 3. Incluye las dependencias (configuración de BD y funciones del modelo).
 * 4. Recopila y valida los datos de entrada (provenientes de $_SESSION y $_POST).
 * 5. Determina el estado inicial de la solicitud aplicando la lógica de negocio del workflow.
 * 6. Construye y ejecuta una sentencia SQL preparada para insertar el nuevo registro de forma segura.
 * 7. Devuelve una respuesta JSON indicando el éxito o fracaso de la operación.
 * 8. Cierra los recursos de la base de datos.
 */

// session_start() debe ser una de las primeras cosas en ejecutarse. Es indispensable para
// poder acceder a la variable superglobal $_SESSION y verificar la autenticación del usuario.
session_start();

// Establecemos la cabecera 'Content-Type'. Esto es un contrato con el cliente (el JavaScript que hace la llamada).
// Le estamos diciendo explícitamente: "Lo que te voy a devolver no es HTML, es JSON". Esto asegura
// que jQuery (o cualquier otro cliente HTTP) parsee la respuesta correctamente.

header('Content-Type: application/json');

// --- PUNTO DE CONTROL DE SEGURIDAD #1: AUTENTICACIÓN ---
// Este es el "guardián" del endpoint. Si no existe una sesión de usuario activa, cortamos
// la ejecución de inmediato. Un endpoint sin protección es una vulnerabilidad crítica.
// `exit` es crucial para asegurar que el resto del script no se ejecute bajo ninguna circunstancia.
if (!isset($_SESSION['user'])) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

// Incluimos las dependencias. Usamos `require_once` porque el script es inútil sin estos archivos.
// `require` provocaría un error fatal (lo cual es deseable aquí), y `_once` previene
// problemas de redeclaración de funciones si los archivos se incluyeran múltiples veces.
// La ruta '../' indica que debemos subir un nivel desde la carpeta 'ajax' para encontrar 'config' y 'models'.
require_once '../config/database.php';
require_once '../models/solicitud_model.php';


// --- RECOPILACIÓN DE DATOS ---
// Separamos los datos de fuentes "confiables" (la sesión) de los que vienen del cliente (POST).
$userId = $_SESSION['user']['id'];
$tipoFuncionario = $_SESSION['user']['tipo_funcionario'];

// Datos provenientes del cliente. Estos datos son "no confiables" por definición.
// Mejora Sugerida: Para una mayor robustez, se podría aplicar un filtrado aquí.
// Ejemplo: $periodoId = filter_input(INPUT_POST, 'periodo_causacion_id', FILTER_SANITIZE_NUMBER_INT);
// Aunque las sentencias preparadas nos protegen de la inyección SQL, el saneamiento
// es una buena práctica de "defensa en profundidad".
$periodoId = $_POST['periodo_causacion_id'];
$fechaInicio = $_POST['fecha_inicio'];
$fechaFin = $_POST['fecha_fin'];


// --- VALIDACIÓN DE LA LÓGICA DE NEGOCIO ---
// Advertencia: NUNCA confíes en la validación del frontend (JavaScript). Es para la usabilidad del usuario.
// La validación real y autoritativa SIEMPRE debe ocurrir en el backend.
// Aquí comprobamos que los campos obligatorios no estén vacíos y que la lógica de fechas sea coherente.
if (empty($periodoId) || empty($fechaInicio) || empty($fechaFin) || strtotime($fechaFin) <= strtotime($fechaInicio)) {
    // Si la validación falla, devolvemos un error y terminamos la ejecución.
    echo json_encode(['status' => 'error', 'message' => 'Datos inválidos.']);
    exit;
}

// --- APLICACIÓN DE LA LÓGICA DE NEGOCIO (WORKFLOW) ---
// Aquí, el script deja de ser un simple CRUD para convertirse en un iniciador de proceso.
// Se delega la lógica del workflow a la función `getSiguienteAprobador`.
$primerAprobador = getSiguienteAprobador($tipoFuncionario);

// Comprobación de sanidad: ¿Qué pasa si el tipo de funcionario no tiene un flujo definido?
// Este `if` maneja ese caso borde de forma elegante.
if ($primerAprobador === null) {
    echo json_encode(['status' => 'error', 'message' => 'No se pudo definir un flujo de aprobación.']);
    exit;
}
// Construimos el estado inicial de forma dinámica basado en la respuesta del workflow.
$estadoInicial = "Esperando Aprobación " . $primerAprobador;


// --- INTERACCIÓN CON LA BASE DE DATOS: ESCRITURA SEGURA ---
// Práctica Estándar: Usamos sentencias preparadas (prepared statements) para TODAS las operaciones
// de escritura (INSERT, UPDATE, DELETE). Esta es la defensa más efectiva contra la inyección SQL.
$sql = "INSERT INTO solicitudes_vacaciones (user_id, periodo_causacion_id, fecha_inicio_disfrute, fecha_fin_disfrute, estado, aprobador_actual) VALUES (?, ?, ?, ?, ?, ?)";

// 1. Preparamos la consulta. El motor de la BD la analiza y compila, separando la lógica de los datos.
$stmt = $conn->prepare($sql);
// 2. Vinculamos nuestras variables PHP a los marcadores de posición (?).
// El primer argumento "iissss" es CRÍTICO: le dice a la BD el tipo de cada variable.
// i = integer (entero)
// s = string (cadena de texto)
// d = double (flotante)
// b = blob (binario)
// Esto proporciona una capa adicional de seguridad y type-checking.
$stmt->bind_param("iissss", $userId, $periodoId, $fechaInicio, $fechaFin, $estadoInicial, $primerAprobador);

// --- RESPUESTA Y CIERRE ---
// 3. Ejecutamos la consulta. `execute()` devuelve `true` si fue exitosa, `false` si no.
if ($stmt->execute()) {
    // Éxito: Devolvemos una respuesta JSON estandarizada. El frontend puede confiar en recibir 'status' y 'message'.
    echo json_encode(['status' => 'success', 'message' => 'Solicitud enviada correctamente.']);
} else {
    // Fracaso: Devolvemos un mensaje de error genérico.
    // Advertencia: En un entorno de desarrollo (debug), podría ser útil loggear el error real:
    // error_log("Error al guardar solicitud: " . $stmt->error);
    // Pero nunca muestres `$stmt->error` directamente al usuario final por seguridad.
    echo json_encode(['status' => 'error', 'message' => 'Error al guardar la solicitud.']);
}

// Buena práctica de "limpieza": Cerramos el statement y la conexión para liberar
// los recursos en el servidor de base de datos inmediatamente. Aunque PHP lo haría
// al final del script, hacerlo explícitamente es más profesional y eficiente.
$stmt->close();
$conn->close();
