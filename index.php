<?php
// Archivo: index.php
session_start();
if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit();
} else {
    header('Location: login.php');
    exit();
}
