<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$i18nDir = __DIR__ . '/../../data/i18n/';
$languages = [];

if (is_dir($i18nDir)) {
    $files = scandir($i18nDir);

    foreach ($files as $file) {
        if ($file === '.' || $file === '..' || $file === 'metadata.json') {
            continue;
        }

        $ext = pathinfo($file, PATHINFO_EXTENSION);
        if ($ext === 'json') {
            $lang = pathinfo($file, PATHINFO_FILENAME);
            $languages[] = $lang;
        }
    }
}

// Cargar metadata
$metadataPath = $i18nDir . 'metadata.json';
$metadata = file_exists($metadataPath) ? json_decode(file_get_contents($metadataPath), true) : null;

echo json_encode([
    'success' => true,
    'languages' => $languages,
    'default' => $metadata['defaultLanguage'] ?? 'es',
    'metadata' => $metadata
]);
?>
