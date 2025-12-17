<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

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

$animalId = $data['animalId'] ?? '';
$baseUrl = $data['baseUrl'] ?? 'http://localhost';

if (empty($animalId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de animal requerido']);
    exit();
}

$modelPath = __DIR__ . "/../../models/{$animalId}";
if (!is_dir($modelPath)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Animal no encontrado']);
    exit();
}

// URL del visor AR
$viewerUrl = rtrim($baseUrl, '/') . "/viewer.html?model={$animalId}";

// Ruta del QR
$qrPath = "{$modelPath}/qr_{$animalId}.png";

// Usar API de Google Charts para generar QR (simple y sin dependencias)
$qrApiUrl = 'https://chart.googleapis.com/chart?cht=qr&chs=512x512&chl=' . urlencode($viewerUrl);

// Descargar imagen del QR
$qrData = @file_get_contents($qrApiUrl);

if ($qrData === false) {
    // Si falla la API de Google, intentar generar con PHP QR Code library si está disponible
    // O usar una alternativa local
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al generar QR. Verifica conexión a internet o instala una librería QR local.'
    ]);
    exit();
}

// Guardar imagen
if (!file_put_contents($qrPath, $qrData)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al guardar QR']);
    exit();
}

echo json_encode([
    'success' => true,
    'qrPath' => "models/{$animalId}/qr_{$animalId}.png",
    'viewerUrl' => $viewerUrl,
    'message' => 'QR generado correctamente'
]);
?>
