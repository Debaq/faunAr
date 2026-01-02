<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

function getDefaultCategory() {
    $categoriesFile = __DIR__ . '/../../data/categories.json';
    if (file_exists($categoriesFile)) {
        $categories = json_decode(file_get_contents($categoriesFile), true);

        // Filtrar habilitadas y ordenar
        $enabledCategories = array_filter($categories, function($cat) {
            return isset($cat['enabled']) && $cat['enabled'];
        });

        uasort($enabledCategories, function($a, $b) {
            return ($a['order'] ?? 999) - ($b['order'] ?? 999);
        });

        // Retornar la primera categoría o 'fauna' por defecto
        return !empty($enabledCategories) ? array_key_first($enabledCategories) : 'fauna';
    }

    return 'fauna';
}

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
                    'category' => $config['category'] ?? getDefaultCategory(),
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
