<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if (!isset($_SESSION['admin_user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$categoriesFile = __DIR__ . '/../../../data/categories.json';

if (!file_exists($categoriesFile)) {
    echo json_encode(['success' => false, 'message' => 'Archivo de categorías no encontrado']);
    exit;
}

$categories = json_decode(file_get_contents($categoriesFile), true);

echo json_encode([
    'success' => true,
    'categories' => $categories
]);
?>
