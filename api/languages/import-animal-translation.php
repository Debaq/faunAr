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

if (!isset($input['animalId']) || !isset($input['language']) || !isset($input['translation'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit();
}

$animalId = $input['animalId'];
$targetLang = $input['language'];
$translation = $input['translation'];

$modelPath = __DIR__ . "/../../models/{$animalId}";
$translationsFile = "{$modelPath}/translations.json";

if (!is_dir($modelPath) || !file_exists($translationsFile)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Animal no encontrado']);
    exit();
}

// Leer translations.json actual
$currentTranslations = json_decode(file_get_contents($translationsFile), true);

if (!$currentTranslations) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error leyendo translations.json']);
    exit();
}

// Validar campos requeridos en la traducción
$requiredFields = ['name', 'short_description', 'habitat', 'diet', 'status', 'detailed_description', 'wikipedia'];
foreach ($requiredFields as $field) {
    if (!isset($translation[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Campo faltante: $field"]);
        exit();
    }
}

// Actualizar el idioma destino
$currentTranslations[$targetLang] = $translation;

// Guardar
if (file_put_contents($translationsFile, json_encode($currentTranslations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode([
        'success' => true,
        'message' => 'Traducción aplicada correctamente'
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al guardar traducción']);
}
?>
