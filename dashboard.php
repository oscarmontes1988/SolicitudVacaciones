<?php
// Archivo: dashboard.php
session_start();
if (!isset($_SESSION['user'])) { header('Location: login.php'); exit; }
require_once 'config/database.php';
require_once 'models/user_model.php';
require_once 'models/solicitud_model.php';
$user = $_SESSION['user'];
include 'templates/header.php';
if ($user['rol'] === 'solicitante') {
    $periodos = getPeriodosCausacion($conn, $user['id']);
    $solicitudes = getSolicitudesByUser($conn, $user['id']);
    include 'templates/dashboard_solicitante.php';
} elseif ($user['rol'] === 'aprobador') {
    $pendientes = getSolicitudesPendientesParaAprobador($conn, $user['cargo']);
    include 'templates/dashboard_aprobador.php';
}
include 'templates/footer.php';
$conn->close();
?>