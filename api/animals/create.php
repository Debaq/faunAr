<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

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

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    exit();
}

// Validar campos requeridos
$id = $data['id'] ?? '';
$name = $data['name'] ?? '';
$scientificName = $data['scientificName'] ?? '';

if (empty($id) || empty($name) || empty($scientificName)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Campos requeridos faltantes']);
    exit();
}

// Validar formato de ID (solo minúsculas, sin espacios)
if (!preg_match('/^[a-z]+$/', $id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID debe contener solo minúsculas sin espacios']);
    exit();
}

$modelPath = __DIR__ . "/../../models/{$id}";

// Verificar que no exista
if (file_exists($modelPath)) {
    http_response_code(409);
    echo json_encode(['success' => false, 'error' => 'El ID ya existe']);
    exit();
}

// Crear estructura de configuración
$config = [
    'id' => $id,
    'name' => $name,
    'scientificName' => $scientificName,
    'description' => $data['description'] ?? '',
    'thumbnail' => $data['thumbnail'] ?? 'thumbnail.png',
    'icon' => $data['icon'] ?? '',
    'silhouette' => $data['silhouette'] ?? "silueta_{$id}.svg",
    'arMode' => $data['arMode'] ?? 'marker',
    'gps' => [
        'enabled' => $data['gps']['enabled'] ?? false,
        'latitude' => $data['gps']['latitude'] ?? 0,
        'longitude' => $data['gps']['longitude'] ?? 0,
        'radius' => $data['gps']['radius'] ?? 50
    ],
    'marker' => [
        'enabled' => $data['marker']['enabled'] ?? true,
        'type' => $data['marker']['type'] ?? 'mind',
        'file' => $data['marker']['file'] ?? "{$id}.mind"
    ],
    'model' => [
        'glb' => $data['model']['glb'] ?? "{$id}.glb",
        'usdz' => $data['model']['usdz'] ?? "{$id}.usdz",
        'scale' => $data['model']['scale'] ?? '0.5 0.5 0.5',
        'position' => $data['model']['position'] ?? '0 0 0',
        'rotation' => $data['model']['rotation'] ?? '0 0 0'
    ],
    'audio' => [
        'enabled' => $data['audio']['enabled'] ?? false,
        'file' => $data['audio']['file'] ?? 'sound.mp3'
    ],
    'info' => [
        'habitat' => $data['info']['habitat'] ?? '',
        'diet' => $data['info']['diet'] ?? '',
        'status' => $data['info']['status'] ?? '',
        'wikipedia' => $data['info']['wikipedia'] ?? ''
    ]
];

// Crear carpeta
if (!mkdir($modelPath, 0755, true)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al crear directorio']);
    exit();
}

// Guardar config.json
$configJson = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
file_put_contents("{$modelPath}/config.json", $configJson);

// Guardar description.json si se proporciona
if (!empty($data['detailedDescription'])) {
    $descData = ['description' => $data['detailedDescription']];
    file_put_contents(
        "{$modelPath}/description.json",
        json_encode($descData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
}

echo json_encode([
    'success' => true,
    'id' => $id,
    'message' => 'Animal creado correctamente'
]);
?>
