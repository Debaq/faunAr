<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit();
}

$models_dir = __DIR__ . '/../../models';
$animals = array_filter(glob($models_dir . '/*'), 'is_dir');

$progress = [];

// Campos traducibles que vamos a contar
$translatable_fields = ['name', 'short_description', 'habitat', 'diet', 'status', 'detailed_description', 'wikipedia'];
$total_fields = count($translatable_fields);

foreach ($animals as $animal_dir) {
    $animal_id = basename($animal_dir);
    $translations_path = $animal_dir . '/translations.json';

    if (!file_exists($translations_path)) {
        continue;
    }

    $translations = json_decode(file_get_contents($translations_path), true);

    $animal_progress = [
        'id' => $animal_id,
        'name' => $translations['es']['name'] ?? $animal_id,
        'languages' => []
    ];

    foreach ($translations as $lang => $fields) {
        $filled_count = 0;

        foreach ($translatable_fields as $field) {
            if (!empty($fields[$field])) {
                $filled_count++;
            }
        }

        $percentage = round(($filled_count / $total_fields) * 100);

        $animal_progress['languages'][$lang] = [
            'filled' => $filled_count,
            'total' => $total_fields,
            'percentage' => $percentage
        ];
    }

    $progress[] = $animal_progress;
}

echo json_encode([
    'success' => true,
    'progress' => $progress
]);
?>
