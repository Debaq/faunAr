<?php
header('Content-Type: application/json');
session_start();

// Although public data, check session to prevent unauthorized scraping via admin panel API
if (!isset($_SESSION['admin_user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado.']);
    exit();
}

$about_file = __DIR__ . '/../../assets/data/about.json';

if (!file_exists($about_file)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Archivo "about.json" no encontrado.']);
    exit();
}

// No need to decode, just return the content as is, or decode and return a specific part
$data = json_decode(file_get_contents($about_file), true);

if ($data === null) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al leer el archivo about.json.']);
    exit();
}

// Return only the editable content part
echo json_encode(['success' => true, 'content' => $data['content']]);
?>
