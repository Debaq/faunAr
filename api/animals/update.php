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

if (!$data || empty($data['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    exit();
}

$id = $data['id'];
$modelPath = __DIR__ . "/../../models/{$id}";
$configPath = "{$modelPath}/config.json";

if (!file_exists($configPath)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Animal no encontrado']);
    exit();
}

// Leer configuración actual
$config = json_decode(file_get_contents($configPath), true);

// Actualizar campos (merge recursivo)
function array_merge_recursive_distinct($array1, $array2) {
    $merged = $array1;
    foreach ($array2 as $key => $value) {
        if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
            $merged[$key] = array_merge_recursive_distinct($merged[$key], $value);
        } else {
            $merged[$key] = $value;
        }
    }
    return $merged;
}

// Actualizar solo los campos proporcionados
$updatedConfig = array_merge_recursive_distinct($config, $data);

// Guardar
$configJson = json_encode($updatedConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
file_put_contents($configPath, $configJson);

// Actualizar description.json si se proporciona
if (isset($data['detailedDescription'])) {
    $descPath = "{$modelPath}/description.json";
    $descData = ['description' => $data['detailedDescription']];
    file_put_contents(
        $descPath,
        json_encode($descData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
}

echo json_encode([
    'success' => true,
    'message' => 'Animal actualizado correctamente'
]);
?>
