<?php
session_start();
header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode([
        'success' => false,
        'message' => 'No autorizado'
    ]);
    exit;
}

// Obtener datos POST
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['language']) || !isset($input['translations'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Datos incompletos'
    ]);
    exit;
}

$language = $input['language'];
$translations = $input['translations'];

// Validar idioma
if (!preg_match('/^[a-z]{2}$/', $language)) {
    echo json_encode([
        'success' => false,
        'message' => 'Código de idioma inválido'
    ]);
    exit;
}

// Ruta del archivo i18n
$i18nPath = __DIR__ . '/../../data/i18n/' . $language . '.json';

// Verificar que el directorio existe
if (!is_dir(dirname($i18nPath))) {
    mkdir(dirname($i18nPath), 0755, true);
}

// Guardar con formato bonito
$jsonContent = json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

if (file_put_contents($i18nPath, $jsonContent) !== false) {
    echo json_encode([
        'success' => true,
        'message' => 'Traducciones guardadas correctamente'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error al escribir el archivo'
    ]);
}
