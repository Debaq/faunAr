<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

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

$lang = $data['lang'] ?? '';
$translations = $data['translations'] ?? null;

if (empty($lang) || $translations === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Parámetros faltantes']);
    exit();
}

// Sanitizar nombre de idioma
$lang = preg_replace('/[^a-z]/', '', $lang);

$filePath = __DIR__ . "/../../data/i18n/{$lang}.json";

// Guardar
$json = json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
if (file_put_contents($filePath, $json)) {
    echo json_encode([
        'success' => true,
        'message' => 'Traducciones actualizadas correctamente'
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al guardar']);
}
?>
