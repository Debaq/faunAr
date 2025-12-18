<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

if (!isset($_SESSION['admin_user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit();
}

$languages_file = __DIR__ . '/../../data/languages.json';

// Leer datos del POST
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['languages'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos de idiomas no proporcionados']);
    exit();
}

// Guardar idiomas actualizados
if (!file_put_contents($languages_file, json_encode($input['languages'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al guardar idiomas']);
    exit();
}

echo json_encode([
    'success' => true,
    'message' => 'Idiomas actualizados correctamente'
]);
?>
