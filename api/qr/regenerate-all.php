<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit();
}

// Verificar autenticación
if (!isset($_SESSION['admin_user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit();
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$baseUrl = $data['baseUrl'] ?? 'http://localhost';

// Obtener lista de modelos
$modelsDir = __DIR__ . '/../../models/';
$models = [];

if (is_dir($modelsDir)) {
    $folders = scandir($modelsDir);

    foreach ($folders as $folder) {
        if ($folder === '.' || $folder === '..' || $folder === 'models_originals') {
            continue;
        }

        $folderPath = $modelsDir . $folder;
        $configPath = $folderPath . '/config.json';

        if (is_dir($folderPath) && file_exists($configPath)) {
            $models[] = $folder;
        }
    }
}

$results = [];
$successCount = 0;
$errorCount = 0;

foreach ($models as $animalId) {
    $viewerUrl = rtrim($baseUrl, '/') . "/viewer.html?model={$animalId}";
    $qrPath = "{$modelsDir}{$animalId}/qr_{$animalId}.png";

    // Generar QR usando API de Google
    $qrApiUrl = 'https://chart.googleapis.com/chart?cht=qr&chs=512x512&chl=' . urlencode($viewerUrl);
    $qrData = @file_get_contents($qrApiUrl);

    if ($qrData !== false && file_put_contents($qrPath, $qrData)) {
        $results[$animalId] = ['success' => true];
        $successCount++;
    } else {
        $results[$animalId] = ['success' => false, 'error' => 'Error al generar/guardar'];
        $errorCount++;
    }

    // Pequeña pausa para no sobrecargar la API
    usleep(200000); // 0.2 segundos
}

echo json_encode([
    'success' => true,
    'total' => count($models),
    'successCount' => $successCount,
    'errorCount' => $errorCount,
    'results' => $results
]);
?>
