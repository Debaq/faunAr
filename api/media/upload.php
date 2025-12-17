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

$animalId = $_POST['animalId'] ?? '';
$fileType = $_POST['fileType'] ?? '';

if (empty($animalId) || empty($fileType)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Parámetros faltantes']);
    exit();
}

$targetDir = __DIR__ . "/../../models/{$animalId}/";

// Crear directorio si no existe
if (!is_dir($targetDir)) {
    if (!mkdir($targetDir, 0755, true)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error al crear directorio']);
        exit();
    }
}

if (!isset($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No se recibió archivo']);
    exit();
}

$file = $_FILES['file'];

// Validar errores de upload
if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Error en el upload: ' . $file['error']]);
    exit();
}

// Configuración de tipos permitidos
$allowedTypes = [
    'glb' => [
        'mimes' => ['model/gltf-binary', 'application/octet-stream'],
        'extensions' => ['glb'],
        'maxSize' => 52428800 // 50MB
    ],
    'usdz' => [
        'mimes' => ['model/vnd.usdz+zip', 'application/octet-stream', 'application/zip'],
        'extensions' => ['usdz'],
        'maxSize' => 104857600 // 100MB
    ],
    'mind' => [
        'mimes' => ['application/octet-stream'],
        'extensions' => ['mind'],
        'maxSize' => 5242880 // 5MB
    ],
    'audio' => [
        'mimes' => ['audio/mpeg', 'audio/mp3'],
        'extensions' => ['mp3'],
        'maxSize' => 10485760 // 10MB
    ],
    'thumbnail' => [
        'mimes' => ['image/png', 'image/jpeg', 'image/jpg'],
        'extensions' => ['png', 'jpg', 'jpeg'],
        'maxSize' => 2097152 // 2MB
    ],
    'image' => [
        'mimes' => ['image/png', 'image/jpeg', 'image/jpg'],
        'extensions' => ['png', 'jpg', 'jpeg'],
        'maxSize' => 5242880 // 5MB
    ],
    'silhouette' => [
        'mimes' => ['image/svg+xml'],
        'extensions' => ['svg'],
        'maxSize' => 2097152 // 2MB
    ]
];

if (!isset($allowedTypes[$fileType])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Tipo de archivo no permitido']);
    exit();
}

$config = $allowedTypes[$fileType];

// Validar tamaño
if ($file['size'] > $config['maxSize']) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Archivo demasiado grande. Máximo: ' . round($config['maxSize'] / 1024 / 1024, 2) . 'MB'
    ]);
    exit();
}

// Validar tipo MIME
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $config['mimes'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Tipo de archivo inválido. MIME detectado: ' . $mimeType
    ]);
    exit();
}

// Validar extensión
$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($extension, $config['extensions'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Extensión no permitida. Permitidas: ' . implode(', ', $config['extensions'])
    ]);
    exit();
}

// Sanitizar nombre de archivo
$filename = $_POST['customFilename'] ?? $file['name'];
$filename = preg_replace('/[^a-z0-9._-]/i', '', $filename);

// Asegurar extensión correcta
$filename = pathinfo($filename, PATHINFO_FILENAME) . '.' . $extension;

$targetPath = $targetDir . $filename;

// Mover archivo
if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al guardar archivo']);
    exit();
}

// Actualizar config.json si es necesario
$configPath = $targetDir . 'config.json';
if (file_exists($configPath)) {
    $animalConfig = json_decode(file_get_contents($configPath), true);

    switch ($fileType) {
        case 'glb':
            $animalConfig['model']['glb'] = $filename;
            break;
        case 'usdz':
            $animalConfig['model']['usdz'] = $filename;
            break;
        case 'mind':
            $animalConfig['marker']['file'] = $filename;
            break;
        case 'audio':
            $animalConfig['audio']['file'] = $filename;
            $animalConfig['audio']['enabled'] = true;
            break;
        case 'thumbnail':
            $animalConfig['thumbnail'] = $filename;
            break;
        case 'silhouette':
            $animalConfig['silhouette'] = $filename;
            break;
    }

    file_put_contents(
        $configPath,
        json_encode($animalConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
}

echo json_encode([
    'success' => true,
    'filename' => $filename,
    'path' => "models/{$animalId}/{$filename}",
    'size' => filesize($targetPath),
    'message' => 'Archivo subido correctamente'
]);
?>
