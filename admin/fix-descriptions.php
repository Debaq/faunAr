<?php
// Script para normalizar description.json al formato {"es": "..."}

$models_dir = __DIR__ . '/../models';
$animals = array_filter(glob($models_dir . '/*'), 'is_dir');

echo "🔧 Normalizando archivos description.json...\n\n";

foreach ($animals as $animal_dir) {
    $animal_id = basename($animal_dir);
    $description_path = $animal_dir . '/description.json';

    if (!file_exists($description_path)) {
        echo "⏭️  {$animal_id}: no tiene description.json\n";
        continue;
    }

    $desc_data = json_decode(file_get_contents($description_path), true);

    // Si tiene formato viejo {"description": "..."}, convertirlo
    if (isset($desc_data['description']) && !isset($desc_data['es'])) {
        $new_format = [
            'es' => $desc_data['description']
        ];

        if (file_put_contents($description_path, json_encode($new_format, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            echo "✅ {$animal_id}: convertido a formato {\"es\": \"...\"}\n";
        } else {
            echo "❌ {$animal_id}: error al convertir\n";
        }
    } elseif (isset($desc_data['es'])) {
        echo "✓  {$animal_id}: ya tiene formato correcto\n";
    } else {
        echo "⚠️  {$animal_id}: formato desconocido\n";
    }
}

echo "\n🎉 Normalización completada!\n";
?>
