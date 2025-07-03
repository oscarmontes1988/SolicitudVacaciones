<?php

/**
 * Archivo: ajax/procesar_aprobacion.php
 *
 * Rol: Endpoint de API para procesar la decisión de un aprobador (Aprobar/Rechazar).
 *
 * Arquitectura:
 * Al igual que 'guardar_solicitud.php', este es un "mini-controlador" que maneja una acción
 * específica. Su diseño es crucial para el funcionamiento del flujo de trabajo (workflow).
 * Se encarga de la transición de estados de una solicitud.
 *
 * Flujo de Ejecución:
 * 1. Inicia sesión y establece cabeceras.
 * 2. Realiza un control de seguridad de dos niveles: autenticación y autorización (rol).
 * 3. Incluye dependencias.
 * 4. Recopila los datos de la decisión (ID de la solicitud, decisión, justificación).
 * 5. Obtiene el estado actual de la solicitud de la base de datos para tomar una decisión informada.
 * 6. Aplica la lógica de negocio para determinar el nuevo estado de la solicitud.
 * 7. Construye y ejecuta una sentencia SQL preparada para actualizar el registro.
 * 8. Devuelve una respuesta JSON estándar.
 * 9. Cierra los recursos.
 */

// Inicia la sesión para acceder a los datos del usuario logueado.
session_start();

// Informa al cliente que la respuesta será JSON.
header('Content-Type: application/json');


// --- PUNTO DE CONTROL DE SEGURIDAD: AUTENTICACIÓN Y AUTORIZACIÓN ---
// Este es un control de seguridad más robusto que el anterior.
// 1. `!isset($_SESSION['user'])`: Verifica que el usuario esté logueado (Autenticación).
// 2. `$_SESSION['user']['rol'] !== 'aprobador'`: Verifica que el usuario tenga el permiso
//    adecuado para realizar esta acción (Autorización). Esto previene que un usuario 'solicitante'
//    pueda invocar este endpoint, incluso si está logueado. Una práctica de seguridad fundamental.
if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] !== 'aprobador') {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

// Dependencias necesarias para la lógica y la conexión a la BD.
require_once '../config/database.php';
require_once '../models/solicitud_model.php';

// --- RECOPILACIÓN Y VALIDACIÓN DE DATOS DE ENTRADA ---
// Recogemos los datos enviados por el frontend.
// Mejora Sugerida: Sería ideal aplicar un filtrado y saneamiento a cada variable.
// Ejemplo:
// $solicitudId = filter_input(INPUT_POST, 'solicitud_id', FILTER_SANITIZE_NUMBER_INT);
// $decision = in_array($_POST['decision'], ['Aprobada', 'Rechazada']) ? $_POST['decision'] : null;
// $justificacion = filter_input(INPUT_POST, 'justificacion', FILTER_SANITIZE_STRING);
// Esto aseguraría que las variables contengan solo los tipos y valores esperados.
$solicitudId = $_POST['solicitud_id'];
$decision = $_POST['decision'];
$justificacion = $_POST['justificacion'];

// --- OBTENCIÓN DEL ESTADO ACTUAL (LECTURA ANTES DE ESCRITURA) ---
// Justificación Técnica: Antes de modificar un registro, es una práctica crítica leer su
// estado actual. No podemos confiar en que el estado no ha cambiado desde que el aprobador
// cargó la página. Esta consulta asegura que nuestra lógica de transición de estados se basa
// en la información más reciente de la base de datos.
// Hacemos un JOIN con la tabla de usuarios para obtener el 'tipo_funcionario' del solicitante,
// dato indispensable para determinar el flujo de trabajo correcto.
$sqlGet = "SELECT s.estado, u.tipo_funcionario FROM solicitudes_vacaciones s JOIN users u ON s.user_id = u.id WHERE s.id = ?";
$stmtGet = $conn->prepare($sqlGet);
$stmtGet->bind_param("i", $solicitudId);
$stmtGet->execute();
$solicitud = $stmtGet->get_result()->fetch_assoc();
$stmtGet->close(); // Cerramos este statement tan pronto como ya no lo necesitamos.

// Comprobación de existencia: Si la consulta no devuelve nada, significa que el ID de la solicitud
// es inválido o fue borrado. Debemos detener la ejecución.
if (!$solicitud) {
    echo json_encode(['status' => 'error', 'message' => 'Solicitud no encontrada.']);
    exit;
}
// --- LÓGICA DE NEGOCIO: MÁQUINA DE ESTADOS DEL WORKFLOW ---
// Aquí es donde se decide el futuro de la solicitud.
$siguienteAprobador = null; // Inicializamos a null por defecto.
if ($decision === 'Rechazada') {
    // Si la decisión es un rechazo, el flujo se detiene inmediatamente.
    // El estado final es 'Rechazada' y no hay un siguiente aprobador.
    $estadoFinal = 'Rechazada';
} else {
    // Si la decisión es una aprobación, delegamos la lógica a nuestra función de workflow.
    // Le pasamos el tipo de funcionario (para saber qué flujo seguir) y su estado actual.
    $siguienteAprobador = getSiguienteAprobador($solicitud['tipo_funcionario'], $solicitud['estado']);

    // Usamos un operador ternario para una asignación concisa y legible.
    // Si `getSiguienteAprobador` devolvió un aprobador, construimos el nuevo estado "Esperando...".
    // Si devolvió `null`, significa que este era el último paso del flujo y el estado final es "Autorizadas".
    $estadoFinal = $siguienteAprobador ? "Esperando Aprobación " . $siguienteAprobador : 'Vacaciones Autorizadas';
}

// --- INTERACCIÓN CON LA BASE DE DATOS: ACTUALIZACIÓN SEGURA ---
// Preparamos la consulta UPDATE. De nuevo, usando sentencias preparadas para máxima seguridad.
$sql = "UPDATE solicitudes_vacaciones SET estado = ?, justificacion_aprobador = ?, aprobador_actual = ? WHERE id = ?";
$stmt = $conn->prepare($sql);


// Vinculamos los parámetros. Notar el tipo 'sssi'.
// 'justificacion' y 'aprobador_actual' son strings, 'id' es un integer.
// Es crucial que el tipo de datos coincida con el de la columna en la base de datos.
$stmt->bind_param("sssi", $estadoFinal, $justificacion, $siguienteAprobador, $solicitudId);

// --- RESPUESTA Y CIERRE ---
if ($stmt->execute()) {
    // Éxito: Informamos al frontend.
    echo json_encode(['status' => 'success', 'message' => 'Decisión procesada.']);
} else {
    // Fracaso:
    // Advertencia de Seguridad/UX: No revelar detalles del error de la base de datos al cliente.
    // En desarrollo, podríamos loggear `$stmt->error` para depuración.
    error_log("Error al procesar aprobación para solicitud ID $solicitudId: " . $stmt->error);
    echo json_encode(['status' => 'error', 'message' => 'Error al procesar la decisión.']);
}

// Liberamos los recursos.
$stmt->close();
$conn->close();
