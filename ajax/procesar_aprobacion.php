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
$justificacion   = isset($_POST['justificacion']) ? trim($_POST['justificacion']) : '';
$usuarioAccionId = $_SESSION['user']['id'];

if ($solicitudId <= 0 || ($decision !== 'Aprobada' && $decision !== 'Rechazada')) {
    echo json_encode(array('status' => 'error', 'message' => 'Datos inválidos.'));
    exit;
}

// Consultar estado actual
$sqlGet = "
    SELECT s.user_id, s.estado, u.tipo_funcionario
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
$stmtGet->bind_result($solicitud_user_id, $estadoActual, $tipoFuncionario);
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
$todoCorrecto = true;

try {
    // Actualizar solicitud
    $sqlUpdate = "
        UPDATE solicitudes_vacaciones
        SET estado = ?, justificacion_aprobador = ?, aprobador_actual = ?
        WHERE id = ?
    ";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    if (!$stmtUpdate) {
        throw new Exception("Error en prepare de actualización: " . $conn->error);
    }

    $stmtUpdate->bind_param("sssi", $estadoFinal, $justificacion, $siguienteAprobador, $solicitudId);
    if (!$stmtUpdate->execute()) {
        throw new Exception("Error al ejecutar update: " . $stmtUpdate->error);
    }
    $stmtUpdate->close();

    // Registrar historial (NO ABORTA si falla)
    $registrado = registrarAccionEnHistorial($conn, $solicitudId, $usuarioAccionId, $accion, $estadoFinal, $justificacion);
    if (!$registrado) {
        error_log("Falló registrarAccionEnHistorial para solicitud $solicitudId");
    }

    // Si todo lo crítico funcionó, confirmamos
    $conn->commit();
    echo json_encode(array('status' => 'success', 'message' => 'Decisión procesada correctamente.'));
} catch (Exception $e) {
    $conn->rollback();
    error_log("ERROR en transacción solicitud $solicitudId: " . $e->getMessage());
    echo json_encode(array('status' => 'error', 'message' => 'No se pudo procesar la solicitud.'));
}

$conn->close();
