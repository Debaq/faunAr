<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$languages_file = __DIR__ . '/../../data/languages.json';

if (!file_exists($languages_file)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Archivo de idiomas no encontrado']);
    exit();
}

$languages = json_decode(file_get_contents($languages_file), true);

// Filtrar solo idiomas activados
$enabled_languages = [];
foreach ($languages as $code => $lang) {
    if (isset($lang['enabled']) && $lang['enabled'] === true) {
        $enabled_languages[$code] = $lang;
    }
}

echo json_encode([
    'success' => true,
    'languages' => $enabled_languages
]);
?>
