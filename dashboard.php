<?php
// Archivo: dashboard.php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/database.php';
require_once 'models/user_model.php';
require_once 'models/solicitud_model.php';
$user = $_SESSION['user'];
include 'templates/header.php';
if ($user['rol'] === 'solicitante') {
    $periodos = getPeriodosCausacion($conn, $user['id']);
    $solicitudes = getSolicitudesByUser($conn, $user['id']);

    // Lógica para calcular el total de días disponibles.
    // Esta variable es requerida por la vista 'dashboard_solicitante.php'.
    // NOTA: Esta es una lógica de ejemplo y debe ser refinada.
    // La forma correcta sería calcular los días otorgados por periodo y restar
    // los días ya tomados en solicitudes 'Aprobada'.
    $total_dias_disponibles = 0;
    foreach ($periodos as $periodo) {
        // Por ahora, sumamos 15 días por cada periodo de causación disponible.
        // En una implementación real, aquí deberías restar los días de las $solicitudes aprobadas
        // que correspondan a este periodo.
        $total_dias_disponibles += 15;
    }

    // Esta variable de estado también debe calcularse en el controlador, no en la vista.
    $is_disabled = $total_dias_disponibles <= 0;

    include 'templates/dashboard_solicitante.php';
} elseif ($user['rol'] === 'aprobador') {
    $pendientes = getSolicitudesPendientesParaAprobador($conn, $user['cargo']);
    include 'templates/dashboard_aprobador.php';
}
include 'templates/footer.php';
$conn->close();
