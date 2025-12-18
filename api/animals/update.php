<?php
session_start();
header('Content-Type: application/json');

// --- Functions ---
function send_json_error($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
}

function handle_file_upload($file_key, $animal_id, &$config, $config_path) {
    if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES[$file_key];
        
        // Use a consistent filename based on key, or original name
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

if (empty($_POST['original_id'])) {
    send_json_error('ID de animal no proporcionado.');
}

$id = $_POST['original_id'];
$model_path = __DIR__ . "/../../models/{$id}";
$config_path = "{$model_path}/config.json";

if (!file_exists($config_path)) {
    send_json_error('Animal no encontrado', 404);
}

// --- Logic ---
$config = json_decode(file_get_contents($config_path), true);

// 1. Update config from POST data
$config['name'] = $_POST['name'] ?? $config['name'];
$config['scientificName'] = $_POST['scientificName'] ?? $config['scientificName'];
$config['description'] = $_POST['description'] ?? $config['description'];
$config['icon'] = $_POST['icon'] ?? $config['icon'];
$config['arMode'] = $_POST['arMode'] ?? $config['arMode'];

$config['gps']['enabled'] = isset($_POST['gps_enabled']);
$config['gps']['latitude'] = floatval($_POST['gps_latitude'] ?? 0);
$config['gps']['longitude'] = floatval($_POST['gps_longitude'] ?? 0);
$config['gps']['radius'] = intval($_POST['gps_radius'] ?? 50);

$config['marker']['enabled'] = isset($_POST['marker_enabled']);

$config['audio']['enabled'] = isset($_POST['audio_enabled']);

$config['info']['habitat'] = $_POST['info_habitat'] ?? $config['info']['habitat'];
$config['info']['diet'] = $_POST['info_diet'] ?? $config['info']['diet'];
$config['info']['status'] = $_POST['info_status'] ?? $config['info']['status'];
$config['info']['wikipedia'] = $_POST['info_wikipedia'] ?? $config['info']['wikipedia'];

// Video URL (campo global, no traducible)
if (isset($_POST['video_url'])) {
    $config['video_url'] = $_POST['video_url'];
}


// 2. Handle file uploads
handle_file_upload('image_file', $id, $config, 'image');
handle_file_upload('thumbnail_file', $id, $config, 'thumbnail');
handle_file_upload('silhouette_file', $id, $config, 'silhouette');
handle_file_upload('mind_file', $id, $config, ['marker', 'file']);
handle_file_upload('audio_file', $id, $config, ['audio', 'file']);
handle_file_upload('glb_file', $id, $config, ['model', 'glb']);
handle_file_upload('usdz_file', $id, $config, ['model', 'usdz']);

// 3. Save translations.json
if (isset($_POST['translations'])) {
    $translations_path = "{$model_path}/translations.json";
    $translations = json_decode($_POST['translations'], true);

    if ($translations && !file_put_contents($translations_path, json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
        send_json_error('Error al guardar las traducciones.', 500);
    }

    // Actualizar config.json con los valores del español (idioma por defecto)
    if (isset($translations['es'])) {
        $config['name'] = $translations['es']['name'] ?? $config['name'];
        $config['description'] = $translations['es']['short_description'] ?? $config['description'];
        $config['info']['habitat'] = $translations['es']['habitat'] ?? $config['info']['habitat'];
        $config['info']['diet'] = $translations['es']['diet'] ?? $config['info']['diet'];
        $config['info']['status'] = $translations['es']['status'] ?? $config['info']['status'];
        $config['info']['wikipedia'] = $translations['es']['wikipedia'] ?? $config['info']['wikipedia'];
    }
}

// 4. Save config.json
if (!file_put_contents($config_path, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    send_json_error('Error al guardar la configuración.', 500);
}

// description.json ya no se usa - todo está en translations.json

// --- Success ---
echo json_encode(['success' => true, 'message' => 'Animal actualizado correctamente.']);
?>