<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$instancesFile = __DIR__ . '/../../../data/instances.json';

// Leer datos del request
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'ID no especificado'
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

// Buscar y eliminar la instancia
$instanceIndex = -1;
foreach ($instancesData['instances'] as $index => $inst) {
    if ($inst['id'] === $input['id']) {
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

// Eliminar instancia
array_splice($instancesData['instances'], $instanceIndex, 1);

// Guardar archivo
if (file_put_contents($instancesFile, json_encode($instancesData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode([
        'success' => true,
        'message' => 'Instancia eliminada correctamente'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Error al guardar el archivo'
    ]);
}
?>
