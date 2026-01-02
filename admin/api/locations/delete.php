<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$placesFile = __DIR__ . '/../../../data/places.json';

// Leer datos del request
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'ID no especificado'
    ]);
    exit;
}

// Cargar places existentes
if (!file_exists($placesFile)) {
    echo json_encode([
        'success' => false,
        'error' => 'Archivo places.json no encontrado'
    ]);
    exit;
}

$placesData = json_decode(file_get_contents($placesFile), true);

if (!isset($placesData[$input['id']])) {
    echo json_encode([
        'success' => false,
        'error' => 'Lugar no encontrado'
    ]);
    exit;
}

// Eliminar lugar
unset($placesData[$input['id']]);

// Guardar archivo
if (file_put_contents($placesFile, json_encode($placesData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode([
        'success' => true,
        'message' => 'Lugar eliminado correctamente'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Error al guardar el archivo'
    ]);
}
?>
