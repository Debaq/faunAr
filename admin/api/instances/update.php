<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$instancesFile = __DIR__ . '/../../../data/instances.json';

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
if (empty($input['original_id']) || empty($input['placeId']) || empty($input['qrCode'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Faltan campos requeridos'
    ]);
    exit;
}

// Cargar instancias existentes
if (!file_exists($instancesFile)) {
    echo json_encode([
        'success' => false,
        'error' => 'Archivo instances.json no encontrado'
    ]);
    exit;
}

$instancesData = json_decode(file_get_contents($instancesFile), true);

// Buscar la instancia a actualizar
$instanceIndex = -1;
foreach ($instancesData['instances'] as $index => $inst) {
    if ($inst['id'] === $input['original_id']) {
        $instanceIndex = $index;
        break;
    }
}

if ($instanceIndex === -1) {
    echo json_encode([
        'success' => false,
        'error' => 'Instancia no encontrada'
    ]);
    exit;
}

// Actualizar instancia
$updatedInstance = [
    'id' => $input['original_id'],
    'qrCode' => $input['qrCode'],
    'animalId' => $input['animalId'],
    'placeId' => $input['placeId'],
    'variant' => $input['variant'] ?? 'default',
    'enabled' => isset($input['enabled']) && $input['enabled'] === 'on',
    'metadata' => [
        'notes' => $input['notes'] ?? '',
        'created' => $instancesData['instances'][$instanceIndex]['metadata']['created'] ?? date('Y-m-d H:i:s'),
        'updated' => date('Y-m-d H:i:s')
    ]
];

// GPS (opcional)
if (!empty($input['gps_latitude']) && !empty($input['gps_longitude'])) {
    $updatedInstance['gps'] = [
        'latitude' => floatval($input['gps_latitude']),
        'longitude' => floatval($input['gps_longitude']),
        'radius' => intval($input['gps_radius'] ?? 50)
    ];
}

// Marcador (opcional)
if (!empty($input['marker_file'])) {
    $updatedInstance['marker'] = [
        'file' => $input['marker_file']
    ];
}

$instancesData['instances'][$instanceIndex] = $updatedInstance;

// Guardar archivo
if (file_put_contents($instancesFile, json_encode($instancesData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode([
        'success' => true,
        'message' => 'Instancia actualizada correctamente',
        'instance' => $updatedInstance
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Error al guardar el archivo'
    ]);
}
?>
