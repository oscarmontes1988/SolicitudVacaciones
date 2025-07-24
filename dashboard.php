<?php
// Archivo: dashboard.php
// Rol: Controlador principal que enruta al usuario a su panel correspondiente.

session_start();

// 1. Verificación de Seguridad
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// 2. Dependencias
require_once 'config/database.php';
require_once 'models/user_model.php';
require_once 'models/solicitud_model.php';

// 3. Obtiene los datos del usuario
$user = $_SESSION['user'];

// 4. Carga la cabecera reutilizable
include 'templates/header.php';

// 5. Lógica de Enrutamiento y Preparación de Datos
if ($user['rol'] === 'solicitante') {

    $periodos = getPeriodosCausacion($conn, $user['id']);
    $solicitudes = getSolicitudesByUser($conn, $user['id']);
    $total_dias_disponibles = getSaldoVacaciones($conn, $user['id']);

    // --- CORRECCIÓN: Obtiene festivos para un rango de 30 años ---
    $currentYear = (int)date('Y');
    $festivos = [];
    for ($i = 0; $i < 30; $i++) { // Calcula para el año actual y los 29 siguientes
        $festivos = array_merge($festivos, getFestivosLeyEmiliani($currentYear + $i));
    }

    $periodo_mas_antiguo_id = !empty($periodos) ? $periodos[0]['id'] : null;
    $is_disabled = $total_dias_disponibles <= 0;

    // Carga la plantilla específica para el solicitante
    // La vista usará las variables $festivos, $total_dias_disponibles, etc.
    include 'templates/dashboard_solicitante.php';
} elseif ($user['rol'] === 'aprobador') {

    $pendientes = getSolicitudesPendientesParaAprobador($conn, $user['cargo']);

    // Carga la plantilla específica para el aprobador
    include 'templates/dashboard_aprobador.php';
}

// 6. Carga el pie de página reutilizable
include 'templates/footer.php';

// 7. Cierra la conexión a la base de datos
$conn->close();
