<?php
session_start();
header('Content-Type: application/json');

// Verificar autenticación
if (!isset($_SESSION['admin_user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit();
}

// Leer datos POST
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['language']) || !isset($input['translations'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit();
}

$targetLang = $input['language'];
$translationsData = $input['translations'];

if (!is_array($translationsData)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Formato de traducción inválido']);
    exit();
}

$modelsDir = __DIR__ . '/../../models';
$updatedCount = 0;
$errors = [];

foreach ($translationsData as $modelId => $translation) {
    $modelPath = "$modelsDir/$modelId";
    $translationsFile = "$modelPath/translations.json";

    if (!is_dir($modelPath) || !file_exists($translationsFile)) {
        $errors[] = "Modelo '$modelId' no encontrado";
        continue;
    }

    // Leer translations.json actual
    $currentTranslations = json_decode(file_get_contents($translationsFile), true);

    if (!$currentTranslations) {
        $errors[] = "Error leyendo translations.json de '$modelId'";
        continue;
    }

    // Actualizar el idioma destino
    $currentTranslations[$targetLang] = $translation;

    // Guardar
    if (file_put_contents($translationsFile, json_encode($currentTranslations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
        $updatedCount++;
    } else {
        $errors[] = "Error guardando translations.json de '$modelId'";
    }
}

echo json_encode([
    'success' => true,
    'updated' => $updatedCount,
    'errors' => $errors,
    'message' => "Se actualizaron $updatedCount animales" . (count($errors) > 0 ? ' con ' . count($errors) . ' errores' : '')
]);
?>
