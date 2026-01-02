<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

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
