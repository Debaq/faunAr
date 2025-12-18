<?php
header('Content-Type: application/json');
session_start();

function send_json_error($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
}

if (!isset($_SESSION['admin_user'])) {
    send_json_error('No autenticado', 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_error('Método no permitido', 405);
}

$config_file = __DIR__ . '/../../data/config.json';
$current_config = json_decode(file_get_contents($config_file), true);
if ($current_config === null) {
    send_json_error('Error al leer el archivo de configuración principal.', 500);
}

// --- Handle Logo Upload ---
if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
    $logo_file = $_FILES['logo_file'];
    $allowed_types = ['image/png', 'image/jpeg', 'image/svg+xml'];
    
    if (!in_array($logo_file['type'], $allowed_types)) {
        send_json_error('Tipo de archivo de logo no permitido. Usar PNG, JPG o SVG.');
    }

    $target_path = __DIR__ . '/../../assets/images/logo_faunar.png';
    if (!move_uploaded_file($logo_file['tmp_name'], $target_path)) {
        send_json_error('Error al guardar el nuevo logo.', 500);
    }
}

// --- Update config.json from POST data ---
$new_config = $current_config;

// Site settings
$new_config['site']['title'] = $_POST['site_title'] ?? $current_config['site']['title'];
$new_config['site']['baseUrl'] = $_POST['site_baseUrl'] ?? $current_config['site']['baseUrl'];
$new_config['site']['footer_text'] = $_POST['site_footer_text'] ?? ($current_config['site']['footer_text'] ?? '');

// Theme settings
$new_config['theme']['primary_gradient_start'] = $_POST['theme_primary_gradient_start'] ?? $current_config['theme']['primary_gradient_start'];
$new_config['theme']['primary_gradient_end'] = $_POST['theme_primary_gradient_end'] ?? $current_config['theme']['primary_gradient_end'];
$new_config['theme']['accent_color'] = $_POST['theme_accent_color'] ?? $current_config['theme']['accent_color'];


// Save the updated config file
if (file_put_contents($config_file, json_encode($new_config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode(['success' => true, 'message' => 'Configuración guardada.']);
} else {
    send_json_error('Error al guardar el archivo de configuración.', 500);
}
?>