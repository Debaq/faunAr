<?php
session_start();
header('Content-Type: application/json');

// --- Functions ---
function send_json_error($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
}

function handle_file_upload($file_key, $animal_id, &$config) {
    if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES[$file_key];
        
        $name_map = [
            'image_file' => "imagen_{$animal_id}.png",
            'thumbnail_file' => 'thumbnail.png',
            'silhouette_file' => "silueta_{$animal_id}.svg",
            'mind_file' => "{$animal_id}.mind",
            'audio_file' => 'sound.mp3',
            'glb_file' => "{$animal_id}.glb",
            'usdz_file' => "{$animal_id}.usdz",
        ];
        $filename = $name_map[$file_key] ?? basename($file['name']);
        
        $target_dir = __DIR__ . "/../../models/{$animal_id}";
        $target_path = "{$target_dir}/{$filename}";

        if (!move_uploaded_file($file['tmp_name'], $target_path)) {
            // Clean up created directory on failure
            rmdir($target_dir);
            send_json_error("Error al mover el archivo {$filename}.", 500);
        }

        // Update config path
        $keys_to_update = [
            'image_file' => 'image',
            'thumbnail_file' => 'thumbnail',
            'silhouette_file' => 'silhouette',
            'mind_file' => ['marker', 'file'],
            'audio_file' => ['audio', 'file'],
            'glb_file' => ['model', 'glb'],
            'usdz_file' => ['model', 'usdz'],
        ];
        
        $key_path = $keys_to_update[$file_key];
        if (is_array($key_path)) {
            $config[$key_path[0]][$key_path[1]] = $filename;
        } else {
            $config[$key_path] = $filename;
        }
    }
}


// --- Security and Setup ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_error('Método no permitido', 405);
}

if (!isset($_SESSION['admin_user'])) {
    send_json_error('No autenticado', 401);
}

// --- Validation ---
$id = $_POST['id'] ?? '';
$name = $_POST['name'] ?? '';

if (empty($id) || empty($name)) {
    send_json_error('El ID y el Nombre son obligatorios.');
}

if (!preg_match('/^[a-z0-9_]+$/', $id)) {
    send_json_error('ID debe contener solo minúsculas, números y guiones bajos, sin espacios.');
}

$model_path = __DIR__ . "/../../models/{$id}";

if (file_exists($model_path)) {
    send_json_error('El ID del animal ya existe.', 409);
}

// --- Logic ---

// 1. Create directory
if (!mkdir($model_path, 0755, true)) {
    send_json_error('Error al crear el directorio para el animal.', 500);
}

// 2. Build initial config from POST
$config = [
    'id' => $id,
    'name' => $name,
    'scientificName' => $_POST['scientificName'] ?? '',
    'description' => $_POST['description'] ?? '',
    'icon' => $_POST['icon'] ?? '',
    'arMode' => $_POST['arMode'] ?? 'marker',
    'image' => "imagen_{$id}.png",
    'thumbnail' => 'thumbnail.png',
    'silhouette' => "silueta_{$id}.svg",
    'gps' => [
        'enabled' => isset($_POST['gps_enabled']),
        'latitude' => floatval($_POST['gps_latitude'] ?? 0),
        'longitude' => floatval($_POST['gps_longitude'] ?? 0),
        'radius' => intval($_POST['gps_radius'] ?? 50)
    ],
    'marker' => [
        'enabled' => isset($_POST['marker_enabled']),
        'type' => 'mind',
        'file' => "{$id}.mind"
    ],
    'audio' => [
        'enabled' => isset($_POST['audio_enabled']),
        'file' => 'sound.mp3'
    ],
    'model' => [
        'glb' => "{$id}.glb",
        'usdz' => "{$id}.usdz",
        'scale' => '0.5 0.5 0.5',
        'position' => '0 0 0',
        'rotation' => '0 0 0'
    ],
    'info' => [
        'habitat' => $_POST['info_habitat'] ?? '',
        'diet' => $_POST['info_diet'] ?? '',
        'status' => $_POST['info_status'] ?? '',
        'wikipedia' => $_POST['info_wikipedia'] ?? ''
    ],
    'video_url' => $_POST['video_url'] ?? ''
];

// 3. Handle file uploads (this will override default names if files are provided)
handle_file_upload('image_file', $id, $config);
handle_file_upload('thumbnail_file', $id, $config);
handle_file_upload('silhouette_file', $id, $config);
handle_file_upload('mind_file', $id, $config);
handle_file_upload('audio_file', $id, $config);
handle_file_upload('glb_file', $id, $config);
handle_file_upload('usdz_file', $id, $config);

// 4. Create translations.json structure
// Leer idiomas disponibles
$languages_file = __DIR__ . '/../../data/languages.json';
$languages = json_decode(file_get_contents($languages_file), true);

$translations = [];

// Español con datos actuales
$translations['es'] = [
    'name' => $config['name'],
    'short_description' => $config['description'],
    'habitat' => $config['info']['habitat'],
    'diet' => $config['info']['diet'],
    'status' => $config['info']['status'],
    'detailed_description' => $_POST['detailed_description'] ?? '',
    'wikipedia' => $config['info']['wikipedia']
];

// Solo agregar idiomas habilitados
foreach ($languages as $code => $lang) {
    if ($code !== 'es' && isset($lang['enabled']) && $lang['enabled']) {
        $translations[$code] = [
            'name' => '',
            'short_description' => '',
            'habitat' => '',
            'diet' => '',
            'status' => '',
            'detailed_description' => '',
            'wikipedia' => ''
        ];
    }
}

$translations_path = "{$model_path}/translations.json";
if (!file_put_contents($translations_path, json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    send_json_error('Error al crear el archivo de traducciones.', 500);
}

// 5. Save config.json
$config_path = "{$model_path}/config.json";
if (!file_put_contents($config_path, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    send_json_error('Error al guardar la configuración.', 500);
}

// description.json ya no se usa - todo está en translations.json

// --- Success ---
echo json_encode(['success' => true, 'message' => 'Animal creado correctamente.']);
?>