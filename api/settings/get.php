<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['admin_user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado.']);
    exit();
}

$config_file = __DIR__ . '/../../data/config.json';

if (!file_exists($config_file)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Archivo de configuración no encontrado.']);
    exit();
}

$settings = json_decode(file_get_contents($config_file), true);

if ($settings === null) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al leer el archivo de configuración.']);
    exit();
}

echo json_encode(['success' => true, 'settings' => $settings]);
?>
