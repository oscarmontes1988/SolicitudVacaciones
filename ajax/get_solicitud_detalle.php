<?php
session_start();
header('Content-Type: application/json');

// 1. Seguridad: Verificar sesión de usuario
if (!isset($_SESSION['user'])) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

// 2. Dependencias
require_once '../config/database.php';
require_once '../models/solicitud_model.php';

// 3. Validación de Entrada
$solicitudId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$userId = $_SESSION['user']['id'];

if ($solicitudId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID de solicitud no válido.']);
    exit;
}

// 4. Lógica de Negocio: Obtener datos del modelo
$solicitud = getSolicitudById($conn, $solicitudId, $userId);

if ($solicitud === null) {
    echo json_encode(['status' => 'error', 'message' => 'Solicitud no encontrada o no tienes permiso para verla.']);
    exit;
}

// 5. Formateo de Datos para el Frontend
// Formatear las fechas principales
$solicitud['fecha_solicitud_fmt'] = date("d/m/Y H:i", strtotime($solicitud['fecha_solicitud']));
$solicitud['periodo_disfrute_fmt'] = date("d/m/Y", strtotime($solicitud['fecha_inicio_disfrute'])) . ' - ' . date("d/m/Y", strtotime($solicitud['fecha_fin_disfrute']));
$solicitud['periodo_causacion_fmt'] = date("d/m/Y", strtotime($solicitud['periodo_inicio'])) . ' - ' . date("d/m/Y", strtotime($solicitud['periodo_fin']));

// Formatear fechas y datos del historial
if (isset($solicitud['historial']) && is_array($solicitud['historial'])) {
    foreach ($solicitud['historial'] as $key => $evento) {
        $solicitud['historial'][$key]['fecha_accion_fmt'] = date("d/m/Y H:i", strtotime($evento['fecha_accion']));
        // Limpiar justificación para evitar null en JSON
        $solicitud['historial'][$key]['justificacion'] = $evento['justificacion'] ? htmlspecialchars($evento['justificacion']) : 'N/A';
        $solicitud['historial'][$key]['accion_fmt'] = htmlspecialchars($evento['accion']);
        $solicitud['historial'][$key]['nombre_completo_fmt'] = htmlspecialchars($evento['nombre_completo']);
        $solicitud['historial'][$key]['estado_resultante_fmt'] = htmlspecialchars($evento['estado_resultante']);
    }
}

// 6. Respuesta Exitosa
echo json_encode(['status' => 'success', 'data' => $solicitud]);

$conn->close();
