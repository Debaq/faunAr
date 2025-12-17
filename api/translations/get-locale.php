<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$lang = $_GET['lang'] ?? 'es';

// Sanitizar
$lang = preg_replace('/[^a-z]/', '', $lang);

$filePath = __DIR__ . "/../../data/i18n/{$lang}.json";

if (!file_exists($filePath)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Idioma no encontrado']);
    exit();
}

$translations = json_decode(file_get_contents($filePath), true);

echo json_encode([
    'success' => true,
    'language' => $lang,
    'translations' => $translations
]);
?>
