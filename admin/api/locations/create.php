<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$placesFile = __DIR__ . '/../../../data/places.json';

// Leer datos del request
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode([
        'success' => false,
        'error' => 'Datos inválidos'
    ]);
    exit;
}

// Validar campos requeridos
if (empty($input['id']) || empty($input['name']) ||
    !isset($input['gps_latitude']) || !isset($input['gps_longitude'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Faltan campos requeridos'
    ]);
    exit;
}

// Cargar places existentes
$placesData = [];
if (file_exists($placesFile)) {
    $placesData = json_decode(file_get_contents($placesFile), true) ?? [];
}

// Verificar que el ID no exista
if (isset($placesData[$input['id']])) {
    echo json_encode([
        'success' => false,
        'error' => 'Ya existe un lugar con ese ID'
    ]);
    exit;
}

// Crear nuevo lugar
$newLocation = [
    'id' => $input['id'],
    'name' => $input['name'],
    'description' => $input['description'] ?? '',
    'gps' => [
        'latitude' => floatval($input['gps_latitude']),
        'longitude' => floatval($input['gps_longitude']),
        'radius' => intval($input['gps_radius'] ?? 5000)
    ],
    'enabled' => isset($input['enabled']) && $input['enabled'] === 'on',
    'metadata' => [
        'region' => $input['metadata_region'] ?? '',
        'country' => $input['metadata_country'] ?? 'Chile',
        'created' => date('Y-m-d')
    ]
];

// Agregar al objeto de lugares
$placesData[$input['id']] = $newLocation;

// Guardar archivo
if (file_put_contents($placesFile, json_encode($placesData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode([
        'success' => true,
        'message' => 'Lugar creado correctamente',
        'location' => $newLocation
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Error al guardar el archivo'
    ]);
}
?>
