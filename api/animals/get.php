<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$id = $_GET['id'] ?? '';

if (empty($id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID requerido']);
    exit();
}

$modelPath = __DIR__ . "/../../models/{$id}";
$configPath = "{$modelPath}/config.json";
$translationsPath = "{$modelPath}/translations.json";

if (!is_dir($modelPath) || !file_exists($configPath)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Animal no encontrado']);
    exit();
}

// Leer configuración
$config = json_decode(file_get_contents($configPath), true);

// Leer descripción detallada desde translations.json
$description = null;
if (file_exists($translationsPath)) {
    $translations = json_decode(file_get_contents($translationsPath), true);
    $description = $translations['es']['detailed_description'] ?? null;
}

// Listar archivos en la carpeta
$files = [];
$allFiles = array_diff(scandir($modelPath), ['.', '..']);
foreach ($allFiles as $file) {
    $filePath = "{$modelPath}/{$file}";
    if (is_file($filePath)) {
        $files[] = [
            'name' => $file,
            'size' => filesize($filePath),
            'type' => pathinfo($file, PATHINFO_EXTENSION),
            'modified' => date('Y-m-d H:i:s', filemtime($filePath))
        ];
    }
}

echo json_encode([
    'success' => true,
    'config' => $config,
    'detailedDescription' => $description,
    'files' => $files
]);
?>
