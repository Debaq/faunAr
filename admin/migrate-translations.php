<?php
// Script de migración única: crea translations.json para todos los animales existentes
// Este script solo debe ejecutarse una vez

$models_dir = __DIR__ . '/../models';
$animals = array_filter(glob($models_dir . '/*'), 'is_dir');

echo "🔄 Iniciando migración de traducciones...\n\n";

foreach ($animals as $animal_dir) {
    $animal_id = basename($animal_dir);
    $config_path = $animal_dir . '/config.json';
    $translations_path = $animal_dir . '/translations.json';
    $description_path = $animal_dir . '/description.json';

    // Saltar si ya existe translations.json
    if (file_exists($translations_path)) {
        echo "⏭️  {$animal_id}: translations.json ya existe, saltando...\n";
        continue;
    }

    // Leer config.json
    if (!file_exists($config_path)) {
        echo "⚠️  {$animal_id}: config.json no encontrado, saltando...\n";
        continue;
    }

    $config = json_decode(file_get_contents($config_path), true);

    // Leer description.json si existe
    $detailed_description = '';
    if (file_exists($description_path)) {
        $desc_data = json_decode(file_get_contents($description_path), true);
        // Ahora todos tienen formato {"es": "..."}
        $detailed_description = $desc_data['es'] ?? '';
    }

    // Leer idiomas disponibles
    $languages_file = __DIR__ . '/../data/languages.json';
    $languages = json_decode(file_get_contents($languages_file), true);

    // Crear estructura de traducciones
    $translations = [];

    // Español con datos actuales
    $translations['es'] = [
        'name' => $config['name'] ?? '',
        'short_description' => $config['description'] ?? '',
        'habitat' => $config['info']['habitat'] ?? '',
        'diet' => $config['info']['diet'] ?? '',
        'status' => $config['info']['status'] ?? '',
        'detailed_description' => $detailed_description,
        'wikipedia' => $config['info']['wikipedia'] ?? ''
    ];

    // Otros idiomas vacíos
    foreach ($languages as $code => $lang) {
        if ($code !== 'es') {
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

    // Guardar translations.json
    if (file_put_contents($translations_path, json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
        echo "✅ {$animal_id}: translations.json creado exitosamente\n";
    } else {
        echo "❌ {$animal_id}: Error al crear translations.json\n";
    }
}

echo "\n🎉 Migración completada!\n";
?>
