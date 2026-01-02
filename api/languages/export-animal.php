<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$animalId = $_GET['id'] ?? '';

if (empty($animalId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de animal requerido']);
    exit();
}

$modelPath = __DIR__ . "/../../models/{$animalId}";
$translationsFile = "{$modelPath}/translations.json";

if (!is_dir($modelPath) || !file_exists($translationsFile)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Animal no encontrado']);
    exit();
}

$translations = json_decode(file_get_contents($translationsFile), true);

if (!isset($translations['es'])) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'No hay datos en español para este animal']);
    exit();
}

echo json_encode([
    'success' => true,
    'data' => $translations['es']
]);
?>
