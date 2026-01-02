<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$placesFile = __DIR__ . '/../../../data/places.json';

// Verificar que el archivo existe
if (!file_exists($placesFile)) {
    echo json_encode([
        'success' => false,
        'error' => 'Archivo places.json no encontrado'
    ]);
    exit;
}

$placesData = json_decode(file_get_contents($placesFile), true);

if (!$placesData) {
    echo json_encode([
        'success' => false,
        'error' => 'Error al leer places.json'
    ]);
    exit;
}

// Si se solicita un lugar específico
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    if (isset($placesData[$id])) {
        echo json_encode([
            'success' => true,
            'location' => $placesData[$id]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Lugar no encontrado'
        ]);
    }
    exit;
}

// Convertir objeto a array de lugares
$locations = [];
foreach ($placesData as $id => $place) {
    $locations[] = $place;
}

// Ordenar por nombre
usort($locations, function($a, $b) {
    return strcmp($a['name'], $b['name']);
});

echo json_encode([
    'success' => true,
    'locations' => $locations,
    'count' => count($locations)
]);
?>
