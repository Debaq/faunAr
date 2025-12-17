<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Timeout de sesión: 30 minutos
$timeout = 1800;

// Verificar si existe sesión
if (!isset($_SESSION['admin_user'])) {
    echo json_encode([
        'valid' => false,
        'error' => 'No hay sesión activa'
    ]);
    exit();
}

// Verificar timeout
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout)) {
    session_destroy();
    echo json_encode([
        'valid' => false,
        'error' => 'Sesión expirada'
    ]);
    exit();
}

// Actualizar última actividad
$_SESSION['LAST_ACTIVITY'] = time();

// Sesión válida
echo json_encode([
    'valid' => true,
    'user' => $_SESSION['admin_user'],
    'last_activity' => $_SESSION['LAST_ACTIVITY']
]);
?>
