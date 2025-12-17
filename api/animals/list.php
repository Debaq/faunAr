<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$modelsDir = __DIR__ . '/../../models/';
$animals = [];

if (is_dir($modelsDir)) {
    $folders = scandir($modelsDir);

    foreach ($folders as $folder) {
        if ($folder === '.' || $folder === '..' || $folder === 'models_originals') {
            continue;
        }

        $folderPath = $modelsDir . $folder;
        $configPath = $folderPath . '/config.json';

        if (is_dir($folderPath) && file_exists($configPath)) {
            $config = json_decode(file_get_contents($configPath), true);

            if ($config) {
                // Contar archivos en la carpeta
                $files = array_diff(scandir($folderPath), ['.', '..']);
                $filesCount = count($files);

                // Obtener fecha de última modificación
                $lastModified = date('Y-m-d', filemtime($configPath));

                $animals[] = [
                    'id' => $config['id'],
                    'name' => $config['name'],
                    'scientificName' => $config['scientificName'],
                    'thumbnail' => $config['thumbnail'] ?? null,
                    'icon' => $config['icon'] ?? '',
                    'arMode' => $config['arMode'] ?? 'marker',
                    'gps' => $config['gps'] ?? null,
                    'marker' => $config['marker'] ?? null,
                    'audio' => $config['audio'] ?? null,
                    'filesCount' => $filesCount,
                    'lastModified' => $lastModified
                ];
            }
        }
    }
}

// Ordenar por nombre
usort($animals, function($a, $b) {
    return strcmp($a['name'], $b['name']);
});

echo json_encode([
    'success' => true,
    'animals' => $animals,
    'count' => count($animals)
]);
?>
