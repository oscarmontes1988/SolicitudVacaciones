<?php

/**
 * Archivo: ajax/guardar_solicitud.php
 * Rol: Endpoint de API para procesar la creación de una nueva solicitud y su historial.
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
require_once '../models/user_model.php'; // Necesario para getSaldoVacaciones y getPeriodosCausacion

// 2. Recopilación y Validación de Datos
$userId = $_SESSION['user']['id'];
$tipoFuncionario = $_SESSION['user']['tipo_funcionario'];
$periodoId = isset($_POST['periodo_causacion_id']) ? $_POST['periodo_causacion_id'] : null;
$fechaInicio = isset($_POST['fecha_inicio_disfrute']) ? $_POST['fecha_inicio_disfrute'] : null;
$fechaFin = isset($_POST['fecha_fin_disfrute']) ? $_POST['fecha_fin_disfrute'] : null;


if (empty($periodoId) || empty($fechaInicio) || empty($fechaFin) || strtotime($fechaFin) <= strtotime($fechaInicio)) {
    echo json_encode(['status' => 'error', 'message' => 'Datos inválidos o incompletos.']);
    exit;
}

// 3. Lógica de Negocio (Workflow)
$primerAprobador = getSiguienteAprobador($tipoFuncionario);
if ($primerAprobador === null) {
    echo json_encode(['status' => 'error', 'message' => 'No se pudo definir un flujo de aprobación para tu cargo.']);
    exit;
}
$estadoInicial = "Esperando Aprobación " . $primerAprobador;

// --- INICIO DE LA TRANSACCIÓN ---
// Una transacción asegura que ambas operaciones (crear solicitud y crear historial)
// se completen con éxito. Si una falla, ambas se revierten.
$conn->begin_transaction();

try {
    // 4. Inserción en 'solicitudes_vacaciones'
    $sqlSolicitud = "INSERT INTO solicitudes_vacaciones (user_id, periodo_causacion_id, fecha_inicio_disfrute, fecha_fin_disfrute, estado, aprobador_actual) VALUES (?, ?, ?, ?, ?, ?)";
    $stmtSolicitud = $conn->prepare($sqlSolicitud);
    $stmtSolicitud->bind_param("iissss", $userId, $periodoId, $fechaInicio, $fechaFin, $estadoInicial, $primerAprobador);

    if (!$stmtSolicitud->execute()) {
        throw new Exception("Error al guardar la solicitud principal.");
    }

    // Obtenemos el ID de la solicitud recién creada para usarlo en el historial
    $nuevaSolicitudId = $conn->insert_id;
    $stmtSolicitud->close();

    // 5. Inserción en 'solicitudes_historial' usando la función del modelo
    if (!registrarAccionEnHistorial($conn, $nuevaSolicitudId, $userId, 'Creada', $estadoInicial)) {
        throw new Exception("Error al registrar la acción en el historial.");
    }

    // Si todo fue bien, confirmamos los cambios en la base de datos
    $conn->commit();

    // 6. Preparamos la respuesta exitosa para el frontend
    $saldoActualizado = getSaldoVacaciones($conn, $userId); // Recalculamos el saldo de días

    // Obtenemos los datos completos de la nueva solicitud para devolverlos al frontend
    $sqlNuevaFila = "SELECT s.*, p.fecha_inicio AS periodo_inicio, p.fecha_fin AS periodo_fin 
                     FROM solicitudes_vacaciones s
                     JOIN periodos_causacion p ON s.periodo_causacion_id = p.id
                     WHERE s.id = ?";
    $stmtNuevaFila = $conn->prepare($sqlNuevaFila);
    $stmtNuevaFila->bind_param("i", $nuevaSolicitudId);
    $stmtNuevaFila->execute();
    $nuevaSolicitudData = $stmtNuevaFila->get_result()->fetch_assoc();
    $stmtNuevaFila->close();

    // Formateamos las fechas y datos para que coincidan con el HTML del dashboard
    $fechaDisfrute = date("d/m/Y", strtotime($nuevaSolicitudData['fecha_inicio_disfrute'])) . " - " . date("d/m/Y", strtotime($nuevaSolicitudData['fecha_fin_disfrute']));
    $fechaCausacion = date("d/m/Y", strtotime($nuevaSolicitudData['periodo_inicio'])) . " - " . date("d/m/Y", strtotime($nuevaSolicitudData['periodo_fin']));
    $fechaSolicitud = date("d/m/Y H:i", strtotime($nuevaSolicitudData['fecha_solicitud']));
    $estadoClass = str_replace([' '], ['-'], strtolower(htmlspecialchars($nuevaSolicitudData['estado'])));


    // Creamos el HTML de la nueva fila que el JavaScript insertará en la tabla del historial
    $newRowHtml = "<tr data-id='{$nuevaSolicitudId}'>" .
        "<td>{$fechaDisfrute}</td>" .
        "<td>{$fechaCausacion}</td>" .
        "<td>{$fechaSolicitud}</td>" .
        "<td><span class='status-badge status-{$estadoClass}'>" . htmlspecialchars($nuevaSolicitudData['estado']) . "</span></td>" .
        "<td><button class='btn btn-secondary btn-sm' onclick='verDetalle({$nuevaSolicitudId})'><i class='fas fa-eye'></i> Ver</button></td>" .
        "</tr>";

    echo json_encode([
        'status' => 'success',
        'message' => 'Solicitud enviada correctamente.',
        'newRowHtml' => $newRowHtml,
        'diasDisponibles' => $saldoActualizado
    ]);
} catch (Exception $e) {
    // Si algo falló en el 'try', revertimos todos los cambios para mantener la BD consistente
    $conn->rollback();
    error_log("Error en guardar_solicitud.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Ocurrió un error al procesar tu solicitud.']);
}

$conn->close();
