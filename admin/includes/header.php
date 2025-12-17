<?php
session_start();

// Verificar sesión
if (!isset($_SESSION['admin_user'])) {
    header('Location: index.php');
    exit();
}

// Timeout de sesión: 30 minutos
$timeout = 1800;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout)) {
    session_destroy();
    header('Location: index.php');
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

// Determinar página actual
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FaunAR - Admin Panel</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="admin-layout">
