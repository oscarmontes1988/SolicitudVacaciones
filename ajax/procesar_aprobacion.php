<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] !== 'aprobador') {
    echo json_encode(array('status' => 'error', 'message' => 'No autorizado'));
    exit;
}

require_once '../config/database.php';
require_once '../models/solicitud_model.php';
require_once '../models/user_model.php';

// Entrada
$solicitudId     = isset($_POST['solicitud_id']) ? intval($_POST['solicitud_id']) : 0;
$decision        = isset($_POST['decision']) ? $_POST['decision'] : '';
$comentarios   = isset($_POST['comentarios']) ? trim($_POST['comentarios']) : '';
$usuarioAccionId = $_SESSION['user']['id'];

if ($solicitudId <= 0 || ($decision !== 'Aprobada' && $decision !== 'Rechazada')) {
    echo json_encode(array('status' => 'error', 'message' => 'Datos inválidos.'));
    exit;
}

// Consultar estado actual
$sqlGet = "
    SELECT s.user_id, s.estado, u.tipo_funcionario, s.periodo_causacion_id
    FROM solicitudes_vacaciones s
    JOIN users u ON s.user_id = u.id
    WHERE s.id = ?
";
$stmtGet = $conn->prepare($sqlGet);
if (!$stmtGet) {
    error_log("Error prepare consulta: " . $conn->error);
    echo json_encode(array('status' => 'error', 'message' => 'Error interno (consulta).'));
    exit;
}
$stmtGet->bind_param("i", $solicitudId);
$stmtGet->execute();
$stmtGet->bind_result($solicitud_user_id, $estadoActual, $tipoFuncionario, $periodoId);
$solicitud_encontrada = $stmtGet->fetch();
$stmtGet->close();

if (!$solicitud_encontrada) {
    echo json_encode(array('status' => 'error', 'message' => 'Solicitud no encontrada.'));
    $conn->close();
    exit;
}

// Definir estado nuevo
$siguienteAprobador = null;
$accion = '';
if ($decision === 'Rechazada') {
    $estadoFinal = 'Rechazada';
    $accion = 'Rechazada';
} else {
    $siguienteAprobador = getSiguienteAprobador($tipoFuncionario, $estadoActual);
    $estadoFinal = $siguienteAprobador ? "Esperando Aprobación " . $siguienteAprobador : 'Vacaciones Autorizadas';
    $accion = 'Aprobada';
}

// ✅ TRANSACCIÓN CON CONFIRMACIÓN REAL
$conn->begin_transaction();

try {
    // 1. Actualizar la solicitud principal
    $sqlUpdate = "UPDATE solicitudes_vacaciones SET estado = ?, comentarios_aprobador = ?, aprobador_actual = ? WHERE id = ?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    if (!$stmtUpdate) {
        throw new Exception("Error en prepare de actualización: " . $conn->error);
    }
    $stmtUpdate->bind_param("sssi", $estadoFinal, $comentarios, $siguienteAprobador, $solicitudId);
    if (!$stmtUpdate->execute()) {
        throw new Exception("Error al ejecutar update: " . $stmtUpdate->error);
    }
    $stmtUpdate->close();

    // 2. Registrar la acción en el historial
    if (!registrarAccionEnHistorial($conn, $solicitudId, $usuarioAccionId, $accion, $estadoFinal, $comentarios)) {
        // No abortamos la transacción por esto, pero lo registramos
        error_log("ADVERTENCIA: Falló registrarAccionEnHistorial para solicitud $solicitudId");
    }

    // 3. Si la solicitud es Rechazada, liberar el período de causación
    if ($decision === 'Rechazada') {
        if (!empty($periodoId)) {
            $sqlUpdatePeriodo = "UPDATE periodos_causacion SET disponible = 1 WHERE id = ?";
            $stmtUpdatePeriodo = $conn->prepare($sqlUpdatePeriodo);
            if (!$stmtUpdatePeriodo) {
                throw new Exception("Error al preparar la liberación del período.");
            }
            $stmtUpdatePeriodo->bind_param("i", $periodoId);
            if (!$stmtUpdatePeriodo->execute()) {
                throw new Exception("Error al liberar el período de causación por rechazo.");
            }
            $stmtUpdatePeriodo->close();
        }
    }
    // Nota: Si es aprobada, el período permanece no disponible (disponible=0), que es el estado que se 
    // le asignó al momento de crear la solicitud. No se necesita ninguna acción aquí para la aprobación.

    // 4. Si todo fue bien, confirmar la transacción
    $conn->commit();

    echo json_encode(array('status' => 'success', 'message' => 'Decisión procesada correctamente.'));

} catch (Exception $e) {
    $conn->rollback();
    error_log("ERROR en transacción de aprobación para solicitud $solicitudId: " . $e->getMessage());
    echo json_encode(array('status' => 'error', 'message' => 'No se pudo procesar la solicitud: ' . $e->getMessage()));
}

$conn->close();
