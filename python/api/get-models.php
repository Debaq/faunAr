<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$modelsDir = __DIR__ . '/../models/';
$models = [];

// Escanear carpetas en models/
if (is_dir($modelsDir)) {
    $folders = scandir($modelsDir);

    foreach ($folders as $folder) {
        // Ignorar . y ..
        if ($folder === '.' || $folder === '..') {
            continue;
        }

        $folderPath = $modelsDir . $folder;
        $configPath = $folderPath . '/config.json';

        // Verificar que sea directorio y tenga config.json
        if (is_dir($folderPath) && file_exists($configPath)) {
            $models[] = $folder;
        }
    }
}

// Ordenar alfabéticamente
sort($models);

echo json_encode([
    'success' => true,
    'models' => $models,
    'count' => count($models)
]);
?>
