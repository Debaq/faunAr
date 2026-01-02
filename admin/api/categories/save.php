<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

if (!isset($_SESSION['admin_user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['categories'])) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

$categoriesFile = __DIR__ . '/../../../data/categories.json';

// Guardar categorías
if (!file_put_contents($categoriesFile, json_encode($data['categories'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode(['success' => false, 'message' => 'Error al guardar categorías']);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Categorías guardadas correctamente']);
?>
