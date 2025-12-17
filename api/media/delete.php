<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit();
}

// Verificar autenticación
if (!isset($_SESSION['admin_user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit();
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$animalId = $data['animalId'] ?? '';
$filename = $data['filename'] ?? '';

if (empty($animalId) || empty($filename)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Parámetros faltantes']);
    exit();
}

// Sanitizar nombre de archivo para seguridad
$filename = basename($filename);

$filePath = __DIR__ . "/../../models/{$animalId}/{$filename}";

if (!file_exists($filePath)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Archivo no encontrado']);
    exit();
}

// Eliminar archivo
if (unlink($filePath)) {
    echo json_encode([
        'success' => true,
        'message' => 'Archivo eliminado correctamente'
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al eliminar archivo']);
}
?>
