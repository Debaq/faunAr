<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit();
}

$languages_file = __DIR__ . '/../../data/languages.json';

if (!file_exists($languages_file)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Archivo de idiomas no encontrado']);
    exit();
}

$languages = json_decode(file_get_contents($languages_file), true);

echo json_encode([
    'success' => true,
    'languages' => $languages
]);
?>
