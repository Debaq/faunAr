// Animal Form Handler

let currentLanguage = 'es';
let translations = {};
let availableLanguages = {};
let availableCategories = {};

// Cargar categorías disponibles
async function loadAvailableCategories() {
    try {
        const response = await fetch('../data/categories.json');
        availableCategories = await response.json();

        const selector = document.getElementById('category-select');
        if (selector) {
            selector.innerHTML = '';

            // Ordenar por order
            const sortedCategories = Object.entries(availableCategories)
                .filter(([id, cat]) => cat.enabled)
                .sort((a, b) => a[1].order - b[1].order);

            sortedCategories.forEach(([id, category]) => {
                const option = document.createElement('option');
                option.value = id;
                option.textContent = `${category.icon} ${category.name}`;
                selector.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error cargando categorías:', error);
    }
}

$(document).ready(async function() {
    // Cargar categorías primero
    await loadAvailableCategories();
    $('#detailed-description').summernote({
        placeholder: 'Escribe la descripción detallada aquí. Puedes usar HTML.',
        tabsize: 2,
        height: 300,
        toolbar: [
          ['style', ['style']],
          ['font', ['bold', 'underline', 'clear']],
          ['color', ['color']],
          ['para', ['ul', 'ol', 'paragraph']],
          ['table', ['table']],
          ['insert', ['link', 'picture', 'video']],
          ['view', ['fullscreen', 'codeview', 'help']]
        ],
        callbacks: {
            onPaste: function(e) {
                var clipboardData = (e.originalEvent || e).clipboardData;
                var pastedText = clipboardData.getData('text/plain');

                // Simple heuristic: if the pasted text appears to be an HTML string
                // This regex checks for a string that contains at least one HTML tag structure
                if (/<[a-z][\s\S]*>/i.test(pastedText.trim())) {
                    e.preventDefault(); // Prevent default plain text paste
                    $('#detailed-description').summernote('pasteHTML', pastedText);
                }
            }
        }
    });

    // Cargar idiomas disponibles y luego datos del animal
    if (action === 'edit') {
        await loadAvailableLanguages();
        if (animalId) {
            await loadAnimalData(animalId);
        }
    }
});

const form = document.getElementById('animal-form');
const urlParams = new URLSearchParams(window.location.search);
const action = urlParams.get('action');
const animalId = urlParams.get('id');

// --- Preview Logic for File Inputs ---
document.querySelectorAll('input[type="file"][data-preview-id]').forEach(input => {
    input.addEventListener('change', (event) => {
        const file = event.target.files[0];
        if (!file) return;

        const previewId = event.target.dataset.previewId;
        const previewElement = document.getElementById(previewId);

        if (previewElement) {
            previewElement.src = URL.createObjectURL(file);
        }
    });
});

async function loadAnimalData(id) {
    try {
        console.log('🐾 Cargando datos del animal:', id);

        // Cargar traducciones primero
        await loadTranslations(id);

        console.log('📡 Obteniendo configuración del animal...');
        const response = await fetch(`../api/animals/get.php?id=${id}`);
        const data = await response.json();

        if (data.success) {
            console.log('✅ Configuración del animal recibida');
            populateForm(data.config, data.detailedDescription);

            // Cargar datos del idioma actual (español por defecto)
            console.log('🔄 Cargando datos del idioma actual:', currentLanguage);
            loadLanguageData(currentLanguage);
        } else {
            console.error('❌ Error en respuesta de la API:', data);
            showNotification('Error al cargar animal', 'error');
        }
    } catch (error) {
        console.error('❌ Error:', error);
        showNotification('Error de conexión', 'error');
    }
}

function populateForm(config, detailedDescription) {
    // Información básica (solo campos NO traducibles)
    form.querySelector('[name="id"]').value = config.id || '';
    form.querySelector('[name="category"]').value = config.category || 'fauna';
    // name - traducible, se carga en loadLanguageData()
    form.querySelector('[name="scientificName"]').value = config.scientificName || '';
    form.querySelector('[name="icon"]').value = config.icon || '';
    // description - traducible, se carga en loadLanguageData()

    // Configuración AR
    form.querySelector('[name="arMode"]').value = config.arMode || 'marker';

    // GPS
    if (config.gps) {
        form.querySelector('[name="gps_enabled"]').checked = config.gps.enabled || false;
        form.querySelector('[name="gps_latitude"]').value = config.gps.latitude || '';
        form.querySelector('[name="gps_longitude"]').value = config.gps.longitude || '';
        form.querySelector('[name="gps_radius"]').value = config.gps.radius || 50;
    }

    // Info adicional - todos los campos son traducibles, se cargan en loadLanguageData()
    // habitat, diet, status, wikipedia - se cargan desde translations.json

    // Video URL (campo global, no traducible)
    if (config.video_url) {
        form.querySelector('[name="video_url"]').value = config.video_url || '';
    }

    // La descripción detallada ahora se carga desde translations.json vía loadLanguageData()
    // No la cargamos aquí para evitar sobrescribirla

    // --- Cargar Archivos ---
    const populateFileGroup = (filePath, groupPrefix) => {
        const nameElement = document.getElementById(`current-${groupPrefix}-file`);
        const downloadElement = document.getElementById(`download-${groupPrefix}-file`);
        const previewElement = document.getElementById(`preview-${groupPrefix}-file`);

        if (filePath) {
            const fileName = filePath.split('/').pop();
            nameElement.textContent = fileName;
            
            const fileUrl = `../models/${config.id}/${fileName}`;
            downloadElement.href = fileUrl;
            downloadElement.style.display = 'inline-block';

            if (previewElement) {
                previewElement.src = `${fileUrl}?t=${new Date().getTime()}`;
            }
        } else {
            nameElement.textContent = 'No hay archivo';
            downloadElement.style.display = 'none';
            if (previewElement) {
                previewElement.src = '../assets/images/map-placeholder.svg';
            }
        }
    };

    populateFileGroup(config.image, 'image');
    populateFileGroup(config.thumbnail, 'thumbnail');
    populateFileGroup(config.silhouette, 'silhouette');
    populateFileGroup(config.marker?.file, 'mind');
    populateFileGroup(config.audio?.file, 'audio');
    populateFileGroup(config.model?.glb, 'glb');
    populateFileGroup(config.model?.usdz, 'usdz');
    
    // Trigger change para visibilidad
    form.querySelector('[name="arMode"]').dispatchEvent(new Event('change'));
}

// Submit del formulario
form.addEventListener('submit', async (e) => {
    e.preventDefault();

    // Guardar traducciones del idioma actual antes de enviar
    if (action === 'edit') {
        saveCurrentLanguageData();

        // Limpiar idiomas deshabilitados antes de enviar
        const cleanedTranslations = {};
        for (const code in translations) {
            if (availableLanguages[code] && availableLanguages[code].enabled) {
                cleanedTranslations[code] = translations[code];
            }
        }
        translations = cleanedTranslations;
    }

    // Actualizar el textarea con el contenido de Summernote antes de enviar
    $('#detailed-description').val($('#detailed-description').summernote('code'));

    const formData = new FormData(form);
    const formAction = formData.get('action');

    // Agregar traducciones al FormData si es edición
    if (action === 'edit') {
        formData.append('translations', JSON.stringify(translations));
    }

    // Añadir un indicador de carga
    const submitButton = form.querySelector('button[type="submit"]');
    submitButton.disabled = true;
    submitButton.innerHTML = '💾 Guardando...';

    try {
        const endpoint = action === 'edit' ? '../api/animals/update.php' : '../api/animals/create.php';
        const response = await fetch(endpoint, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showNotification(
                action === 'edit' ? 'Animal actualizado correctamente' : 'Animal creado correctamente',
                'success'
            );
            setTimeout(() => {
                window.location.href = 'animals.php';
            }, 1500);
        } else {
            showNotification(data.message || 'Error al guardar', 'error');
            submitButton.disabled = false;
            submitButton.innerHTML = '💾 Guardar Animal';
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error de conexión', 'error');
        submitButton.disabled = false;
        submitButton.innerHTML = '💾 Guardar Animal';
    }
});

// Auto-completar algunos campos según el ID
form.querySelector('[name="id"]').addEventListener('input', (e) => {
    const id = e.target.value;
    if (action === 'create' && id) {
        // Auto-completar archivos si están vacíos
        const markerFile = form.querySelector('[name="marker_file"]');
        const modelGlb = form.querySelector('[name="model_glb"]');
        const modelUsdz = form.querySelector('[name="model_usdz"]');
        const silhouette = form.querySelector('[name="silhouette"]');

        if (!markerFile.value) markerFile.value = `${id}.mind`;
        if (!modelGlb.value) modelGlb.value = `${id}.glb`;
        if (!modelUsdz.value) modelUsdz.value = `${id}.usdz`;
        if (!silhouette.value) silhouette.value = `silueta_${id}.svg`;
    }
});

// Mostrar/ocultar config GPS según modo AR
form.querySelector('[name="arMode"]').addEventListener('change', (e) => {
    const mode = e.target.value;
    const gpsConfig = document.getElementById('gps-config');
    const markerConfig = document.getElementById('marker-config');

    if (mode === 'gps') {
        gpsConfig.style.display = 'block';
        markerConfig.style.display = 'none';
        form.querySelector('[name="gps_enabled"]').checked = true;
        form.querySelector('[name="marker_enabled"]').checked = false;
    } else if (mode === 'marker') {
        gpsConfig.style.display = 'none';
        markerConfig.style.display = 'block';
        form.querySelector('[name="gps_enabled"]').checked = false;
        form.querySelector('[name="marker_enabled"]').checked = true;
    } else if (mode === 'hybrid') {
        gpsConfig.style.display = 'block';
        markerConfig.style.display = 'block';
        form.querySelector('[name="gps_enabled"]').checked = true;
        form.querySelector('[name="marker_enabled"]').checked = true;
    }
});

// Inicializar visibilidad según modo actual
const currentMode = form.querySelector('[name="arMode"]').value;
if (currentMode === 'marker') {
    document.getElementById('gps-config').style.display = 'none';
} else if (currentMode === 'gps') {
    document.getElementById('marker-config').style.display = 'none';
}


// --- Emoji Picker Logic ---
const emojiCategories = {
    'Animales': ['🐖', '🐄', '🐏', '🐑', '🐐', '🐪', '🐫', '🐴', '🦓', '🦒', '🐘', '🦏', '🦛', '🐭', '🐁', '🐀', '🐹', '🐰', '🐇', '🐿️', '🦔', '🦇', '🐻', '🐨', '🐼', '🦥', '🦦', '🦨', '🦘', '🦡', '🐾'],
    'Aves': ['🦃', '🐔', '🐓', '🐣', '🐤', '🐥', '🐦', '🐧', '🕊️', '🦅', '🦆', '🦢', '🦉', '🦩', '🦚', '🦜'],
    'Reptiles y Anfibios': ['🐸', '🐊', '🐢', '🦎', '🐍', '🐲', '🐉'],
    'Peces': ['🐳', '🐋', '🐬', '🐟', '🐠', '🐡', '🦈', '🐙', '🐚'],
    'Insectos': ['🐌', '🦋', '🐛', '🐜', '🐝', '🐞', '🦗', '🕷️', '🕸️', '🦂', '🦟', '🦠'],
    'Plantas': ['💐', '🌸', '💮', '🏵️', '🌹', '🥀', '🌺', '🌻', '🌼', '🌷', '🌱', '🌲', '🌳', '🌴', '🌵', '🌾', '🌿', '☘️', '🍀', '🍁', '🍂', '🍃'],
    'Hongos': ['🍄']
};

const emojiInput = document.getElementById('emoji-input');
const emojiPickerBtn = document.getElementById('emoji-picker-btn');
const emojiPanel = document.getElementById('emoji-panel');

// Populate panel
for (const category in emojiCategories) {
    const title = document.createElement('div');
    title.className = 'emoji-category';
    title.textContent = category;
    emojiPanel.appendChild(title);

    emojiCategories[category].forEach(emoji => {
        const emojiSpan = document.createElement('span');
        emojiSpan.className = 'emoji-item';
        emojiSpan.textContent = emoji;
        emojiPanel.appendChild(emojiSpan);
    });
}

// Show/Hide panel
emojiPickerBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    emojiPanel.style.display = emojiPanel.style.display === 'block' ? 'none' : 'block';
});

// Select emoji
emojiPanel.addEventListener('click', (e) => {
    if (e.target.classList.contains('emoji-item')) {
        emojiInput.value = e.target.textContent;
        emojiPanel.style.display = 'none';
    }
});

// Hide panel if clicking outside
document.addEventListener('click', (e) => {
    if (!emojiPanel.contains(e.target) && e.target !== emojiPickerBtn) {
        emojiPanel.style.display = 'none';
    }
});

// ============ SISTEMA DE TRADUCCIONES ============

async function loadAvailableLanguages() {
    try {
        console.log('🌍 Cargando idiomas disponibles...');
        const response = await fetch('../data/languages.json');
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        availableLanguages = await response.json();
        console.log('✅ Idiomas disponibles cargados:', availableLanguages);

        const selector = document.getElementById('language-selector');
        if (selector) {
            selector.innerHTML = '';

            const languageFlags = {
                'es': '🇪🇸', 'en': '🇬🇧', 'pt': '🇵🇹', 'fr': '🇫🇷', 'de': '🇩🇪',
                'it': '🇮🇹', 'zh': '🇨🇳', 'ja': '🇯🇵', 'ko': '🇰🇷', 'ru': '🇷🇺',
                'ar': '🇸🇦', 'hi': '🇮🇳', 'nl': '🇳🇱', 'sv': '🇸🇪', 'no': '🇳🇴',
                'da': '🇩🇰', 'fi': '🇫🇮', 'pl': '🇵🇱', 'tr': '🇹🇷', 'th': '🇹🇭',
                'vi': '🇻🇳', 'id': '🇮🇩', 'he': '🇮🇱', 'el': '🇬🇷', 'cs': '🇨🇿',
                'ro': '🇷🇴', 'hu': '🇭🇺', 'uk': '🇺🇦', 'ca': '🇪🇸'
            };

            for (const [code, lang] of Object.entries(availableLanguages)) {
                if (lang.enabled) {
                    const option = document.createElement('option');
                    option.value = code;
                    const flag = languageFlags[code] || '🌍';
                    option.textContent = `${flag} ${code.toUpperCase()}`;
                    option.title = lang.name; // Mostrar nombre completo en tooltip
                    selector.appendChild(option);
                }
            }

            // Listener para cambio de idioma
            selector.addEventListener('change', handleLanguageChange);
            console.log('✅ Selector de idioma configurado');
        }
    } catch (error) {
        console.error('❌ Error cargando idiomas:', error);
    }
}

async function loadTranslations(animalId) {
    try {
        const response = await fetch(`../models/${animalId}/translations.json`);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        translations = await response.json();
        console.log('✅ Traducciones cargadas:', translations);

        // Limpiar idiomas deshabilitados
        for (const code in translations) {
            if (!availableLanguages[code] || !availableLanguages[code].enabled) {
                delete translations[code];
            }
        }

        // Agregar idiomas habilitados que faltan
        for (const code in availableLanguages) {
            if (availableLanguages[code].enabled && !translations[code]) {
                translations[code] = {
                    name: '',
                    short_description: '',
                    habitat: '',
                    diet: '',
                    status: '',
                    detailed_description: '',
                    wikipedia: ''
                };
            }
        }
        console.log('✅ Traducciones después de procesar:', translations);
    } catch (error) {
        console.error('❌ Error cargando traducciones:', error);
        // Inicializar estructura vacía solo con idiomas habilitados
        translations = {};
        for (const code in availableLanguages) {
            if (availableLanguages[code].enabled) {
                translations[code] = {
                    name: '',
                    short_description: '',
                    habitat: '',
                    diet: '',
                    status: '',
                    detailed_description: '',
                    wikipedia: ''
                };
            }
        }
        console.log('⚠️ Traducciones inicializadas vacías:', translations);
    }
}

function handleLanguageChange(e) {
    const newLang = e.target.value;

    // Guardar valores actuales en el idioma actual
    saveCurrentLanguageData();

    // Cambiar al nuevo idioma
    currentLanguage = newLang;

    // Cargar valores del nuevo idioma
    loadLanguageData(newLang);
}

function saveCurrentLanguageData() {
    if (!translations[currentLanguage]) {
        translations[currentLanguage] = {};
    }

    const translatableFields = document.querySelectorAll('.translatable');
    translatableFields.forEach(field => {
        const fieldName = field.dataset.field;
        if (fieldName === 'detailed_description') {
            translations[currentLanguage][fieldName] = $('#detailed-description').summernote('code');
        } else {
            translations[currentLanguage][fieldName] = field.value;
        }
    });
}

function loadLanguageData(lang) {
    console.log('🌐 Cargando datos del idioma:', lang);
    const langData = translations[lang] || {};
    console.log('📄 Datos del idioma:', langData);

    const translatableFields = document.querySelectorAll('.translatable');
    console.log('🔍 Campos traducibles encontrados:', translatableFields.length);

    translatableFields.forEach(field => {
        const fieldName = field.dataset.field;
        const value = langData[fieldName] || '';
        console.log(`  - Campo ${fieldName}:`, value ? `"${value.substring(0, 50)}..."` : '(vacío)');

        if (fieldName === 'detailed_description') {
            // Verificar si Summernote está inicializado
            if ($('#detailed-description').summernote) {
                try {
                    $('#detailed-description').summernote('code', value);
                    console.log('    ✅ Summernote actualizado');
                } catch (e) {
                    console.error('    ❌ Error al actualizar Summernote:', e);
                }
            } else {
                console.warn('    ⚠️ Summernote no está inicializado');
            }
        } else {
            field.value = value;
        }

        // Marcar visualmente si está vacío (sin traducción)
        if (!value && lang !== 'es') {
            field.parentElement.classList.add('changed');
        } else {
            field.parentElement.classList.remove('changed');
        }
    });
}
