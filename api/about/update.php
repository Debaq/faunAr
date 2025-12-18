<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['admin_user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if ($input === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
    exit();
}

$about_file = __DIR__ . '/../../assets/data/about.json';
$current_data = json_decode(file_get_contents($about_file), true);

// Merge recursively to only update the provided summary paragraph
$current_data['content'] = array_replace_recursive($current_data['content'], $input);

if (file_put_contents($about_file, json_encode($current_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode(['success' => true, 'message' => 'Información "Acerca de" guardada.']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al guardar el archivo about.json.']);
}
?>
