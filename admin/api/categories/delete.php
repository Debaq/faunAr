<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

if (!isset($_SESSION['admin_user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['categoryId']) || !isset($data['targetCategoryId'])) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

$categoryToDelete = $data['categoryId'];
$targetCategory = $data['targetCategoryId'];

// 1. Cargar categorías
$categoriesFile = __DIR__ . '/../../../data/categories.json';
$categories = json_decode(file_get_contents($categoriesFile), true);

if (!isset($categories[$categoryToDelete])) {
    echo json_encode(['success' => false, 'message' => 'Categoría no encontrada']);
    exit;
}

// 2. Buscar todos los modelos con esta categoría y reasignarlos
$modelsDir = __DIR__ . '/../../../models/';
$folders = scandir($modelsDir);
$updatedCount = 0;

foreach ($folders as $folder) {
    if ($folder === '.' || $folder === '..' || $folder === 'models_originals') {
        continue;
    }

    $configPath = $modelsDir . $folder . '/config.json';

    if (file_exists($configPath)) {
        $config = json_decode(file_get_contents($configPath), true);

        // Si el modelo tiene la categoría que se va a eliminar, reasignarlo
        if (isset($config['category']) && $config['category'] === $categoryToDelete) {
            $config['category'] = $targetCategory;
            file_put_contents($configPath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $updatedCount++;
        }
    }
}

// 3. Eliminar la categoría
unset($categories[$categoryToDelete]);

// 4. Guardar categorías actualizadas
if (!file_put_contents($categoriesFile, json_encode($categories, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode(['success' => false, 'message' => 'Error al guardar categorías']);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => "Categoría eliminada. {$updatedCount} modelos reasignados.",
    'updatedCount' => $updatedCount
]);
?>
