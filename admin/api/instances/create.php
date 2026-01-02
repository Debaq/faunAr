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
if (empty($input['animalId']) || empty($input['placeId']) || empty($input['qrCode'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Faltan campos requeridos (animalId, placeId, qrCode)'
    ]);
    exit;
}

// Cargar instancias existentes
$instancesData = ['instances' => []];
if (file_exists($instancesFile)) {
    $instancesData = json_decode(file_get_contents($instancesFile), true) ?? ['instances' => []];
}

// Verificar que el código QR no exista
foreach ($instancesData['instances'] as $inst) {
    if ($inst['qrCode'] === $input['qrCode']) {
        echo json_encode([
            'success' => false,
            'error' => 'Ya existe una instancia con ese código QR'
        ]);
        exit;
    }
}

// Generar ID único para la instancia
$instanceId = 'inst-' . uniqid();

// Crear nueva instancia
$newInstance = [
    'id' => $instanceId,
    'qrCode' => $input['qrCode'],
    'animalId' => $input['animalId'],
    'placeId' => $input['placeId'],
    'variant' => $input['variant'] ?? 'default',
    'enabled' => isset($input['enabled']) && $input['enabled'] === 'on',
    'metadata' => [
        'notes' => $input['notes'] ?? '',
        'created' => date('Y-m-d H:i:s')
    ]
];

// GPS (opcional, usa las del lugar si no se especifica)
if (!empty($input['gps_latitude']) && !empty($input['gps_longitude'])) {
    $newInstance['gps'] = [
        'latitude' => floatval($input['gps_latitude']),
        'longitude' => floatval($input['gps_longitude']),
        'radius' => intval($input['gps_radius'] ?? 50)
    ];
}

// Marcador (opcional, usa el del animal si no se especifica)
if (!empty($input['marker_file'])) {
    $newInstance['marker'] = [
        'file' => $input['marker_file']
    ];
}

// Agregar al array de instancias
$instancesData['instances'][] = $newInstance;

// Guardar archivo
if (file_put_contents($instancesFile, json_encode($instancesData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode([
        'success' => true,
        'message' => 'Instancia creada correctamente',
        'instance' => $newInstance
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Error al guardar el archivo'
    ]);
}
?>
