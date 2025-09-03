<?php

/**
 * Archivo: ajax/eliminar_solicitud.php
 * Rol: Endpoint de API para eliminar una solicitud de vacaciones.
 */

session_start();
header('Content-Type: application/json');

// 1. Puntos de Control y Dependencias
if (!isset($_SESSION['user'])) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

require_once '../config/database.php';
require_once '../models/solicitud_model.php';
require_once '../models/user_model.php';

// 2. Recopilación y Validación de Datos
$solicitudId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$userId = $_SESSION['user']['id'];

if ($solicitudId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID de solicitud no válido.']);
    exit;
}

// 3. Lógica de Negocio: Verificar estado y eliminar
$conn->begin_transaction();

try {
    // Obtener el estado actual de la solicitud y verificar que pertenezca al usuario
    $sqlCheck = "SELECT estado, periodo_causacion_id FROM solicitudes_vacaciones WHERE id = ? AND user_id = ? FOR UPDATE"; // FOR UPDATE para bloquear la fila
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->bind_param("ii", $solicitudId, $userId);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();
    $solicitudData = $resultCheck->fetch_assoc();
    $stmtCheck->close();

    if (!$solicitudData) {
        throw new Exception("Solicitud no encontrada o no tienes permiso para eliminarla.");
    }

    if ($solicitudData['estado'] !== 'Esperando Aprobación Coordinador') {
        throw new Exception("Solo se pueden eliminar solicitudes en estado 'Esperando Aprobación Coordinador'.");
    }

    // Liberar el período de causación asociado
    if (!empty($solicitudData['periodo_causacion_id'])) {
        $periodoId = $solicitudData['periodo_causacion_id'];
        $sqlUpdatePeriodo = "UPDATE periodos_causacion SET disponible = 1 WHERE id = ?";
        $stmtUpdatePeriodo = $conn->prepare($sqlUpdatePeriodo);
        if (!$stmtUpdatePeriodo) {
            throw new Exception("Error al preparar la liberación del período.");
        }
        $stmtUpdatePeriodo->bind_param("i", $periodoId);
        if (!$stmtUpdatePeriodo->execute()) {
            throw new Exception("Error al liberar el período de causación.");
        }
        $stmtUpdatePeriodo->close();
    }

    // Eliminar historial primero (debido a la clave foránea)
    $sqlDeleteHistorial = "DELETE FROM solicitudes_historial WHERE solicitud_id = ?";
    $stmtDeleteHistorial = $conn->prepare($sqlDeleteHistorial);
    $stmtDeleteHistorial->bind_param("i", $solicitudId);
    if (!$stmtDeleteHistorial->execute()) {
        throw new Exception("Error al eliminar el historial de la solicitud.");
    }
    $stmtDeleteHistorial->close();

    // Eliminar la solicitud principal
    $sqlDeleteSolicitud = "DELETE FROM solicitudes_vacaciones WHERE id = ?";
    $stmtDeleteSolicitud = $conn->prepare($sqlDeleteSolicitud);
    $stmtDeleteSolicitud->bind_param("i", $solicitudId);
    if (!$stmtDeleteSolicitud->execute()) {
        throw new Exception("Error al eliminar la solicitud principal.");
    }
    $stmtDeleteSolicitud->close();

    $conn->commit();

    // Recalcular saldo de vacaciones y periodos después de la eliminación
    $saldoActualizado = getSaldoVacaciones($conn, $userId);
    $periodosActualizados = getPeriodosCausacion($conn, $userId);

    // Encontrar el nuevo periodo más antiguo disponible
    $nuevoPeriodoMasAntiguoId = null;
    foreach ($periodosActualizados as $p) {
        if ($p['disponible'] == 1) {
            $nuevoPeriodoMasAntiguoId = $p['id'];
            break;
        }
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Solicitud eliminada correctamente.',
        'diasDisponibles' => $saldoActualizado,
        'periodos' => $periodosActualizados,
        'nuevoPeriodoId' => $nuevoPeriodoMasAntiguoId
    ]);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Error en eliminar_solicitud.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();

?>