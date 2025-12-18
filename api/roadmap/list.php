<?php
header('Content-Type: application/json');
session_start();

// Auth check
if (!isset($_SESSION['admin_user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado.']);
    exit();
}

$roadmap_file = __DIR__ . '/../../data/roadmap.json';

if (!file_exists($roadmap_file)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Archivo de hoja de ruta no encontrado.']);
    exit();
}

$data = json_decode(file_get_contents($roadmap_file), true);

if ($data === null) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al leer el archivo de hoja de ruta.']);
    exit();
}

echo json_encode(['success' => true, 'tasks' => $data['tasks']]);
?>
