<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Obtener todos los modelos
$modelsDir = __DIR__ . '/../../models';
$models = array_diff(scandir($modelsDir), ['.', '..']);

$exportData = [];

foreach ($models as $modelId) {
    $modelPath = "$modelsDir/$modelId";

    if (!is_dir($modelPath)) {
        continue;
    }

    $translationsFile = "$modelPath/translations.json";

    if (!file_exists($translationsFile)) {
        continue;
    }

    $translations = json_decode(file_get_contents($translationsFile), true);

    if (!isset($translations['es'])) {
        continue;
    }

    // Exportar solo los datos en español
    $exportData[$modelId] = $translations['es'];
}

echo json_encode([
    'success' => true,
    'data' => $exportData,
    'count' => count($exportData)
]);
?>
