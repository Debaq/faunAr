<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$instancesFile = __DIR__ . '/../../../data/instances.json';

// Verificar que el archivo existe
if (!file_exists($instancesFile)) {
    echo json_encode([
        'success' => false,
        'error' => 'Archivo instances.json no encontrado'
    ]);
    exit;
}

$instancesData = json_decode(file_get_contents($instancesFile), true);

if (!$instancesData) {
    echo json_encode([
        'success' => false,
        'error' => 'Error al leer instances.json'
    ]);
    exit;
}

// Si se solicita una instancia específica
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $instance = null;
    foreach ($instancesData['instances'] as $inst) {
        if ($inst['id'] === $id) {
            $instance = $inst;
            break;
        }
    }

    if ($instance) {
        echo json_encode([
            'success' => true,
            'instance' => $instance
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Instancia no encontrada'
        ]);
    }
    exit;
}

// Si se solicita por código QR
if (isset($_GET['qr'])) {
    $qr = $_GET['qr'];

    $instance = null;
    foreach ($instancesData['instances'] as $inst) {
        if ($inst['qrCode'] === $qr) {
            $instance = $inst;
            break;
        }
    }

    if ($instance) {
        echo json_encode([
            'success' => true,
            'instance' => $instance
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Instancia no encontrada'
        ]);
    }
    exit;
}

// Devolver todas las instancias
$instances = $instancesData['instances'] ?? [];

echo json_encode([
    'success' => true,
    'instances' => $instances,
    'count' => count($instances)
]);
?>
