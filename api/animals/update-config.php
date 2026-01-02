<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Leer datos del request
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['animalId']) || !isset($input['config'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Datos inválidos (se requiere animalId y config)'
    ]);
    exit;
}

$animalId = $input['animalId'];
$newConfig = $input['config'];

// Validar que el animalId no contenga caracteres peligrosos
if (!preg_match('/^[a-z]+$/', $animalId)) {
    echo json_encode([
        'success' => false,
        'error' => 'ID de animal inválido'
    ]);
    exit;
}

$configPath = __DIR__ . '/../../models/' . $animalId . '/config.json';

// Verificar que el directorio del animal existe
if (!file_exists(dirname($configPath))) {
    echo json_encode([
        'success' => false,
        'error' => 'Animal no encontrado'
    ]);
    exit;
}

// Hacer backup del config actual (opcional pero recomendado)
if (file_exists($configPath)) {
    $backupPath = __DIR__ . '/../../models/' . $animalId . '/config.backup.json';
    copy($configPath, $backupPath);
}

// Guardar nuevo config
$jsonContent = json_encode($newConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

if (file_put_contents($configPath, $jsonContent)) {
    echo json_encode([
        'success' => true,
        'message' => 'Configuración actualizada correctamente'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Error al escribir el archivo'
    ]);
}
?>
