// Animal Form Handler

let currentLanguage = 'es';
let translations = {};
let availableLanguages = {};

$(document).ready(function() {
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

    // Cargar idiomas disponibles
    if (action === 'edit') {
        loadAvailableLanguages();
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


// Cargar datos si es edición
if (action === 'edit' && animalId) {
    loadAnimalData(animalId);
}

async function loadAnimalData(id) {
    try {
        // Cargar traducciones primero
        await loadTranslations(id);

        const response = await fetch(`../api/animals/get.php?id=${id}`);
        const data = await response.json();

        if (data.success) {
            populateForm(data.config, data.detailedDescription);

            // Cargar datos del idioma actual (español por defecto)
            loadLanguageData(currentLanguage);
        } else {
            showNotification('Error al cargar animal', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error de conexión', 'error');
    }
}

function populateForm(config, detailedDescription) {
    // Información básica
    form.querySelector('[name="id"]').value = config.id || '';
    form.querySelector('[name="name"]').value = config.name || '';
    form.querySelector('[name="scientificName"]').value = config.scientificName || '';
    form.querySelector('[name="icon"]').value = config.icon || '';
    form.querySelector('[name="description"]').value = config.description || '';

    // Configuración AR
    form.querySelector('[name="arMode"]').value = config.arMode || 'marker';

    // GPS
    if (config.gps) {
        form.querySelector('[name="gps_enabled"]').checked = config.gps.enabled || false;
        form.querySelector('[name="gps_latitude"]').value = config.gps.latitude || '';
        form.querySelector('[name="gps_longitude"]').value = config.gps.longitude || '';
        form.querySelector('[name="gps_radius"]').value = config.gps.radius || 50;
    }

    // Info adicional
    if (config.info) {
        form.querySelector('[name="info_habitat"]').value = config.info.habitat || '';
        form.querySelector('[name="info_diet"]').value = config.info.diet || '';
        form.querySelector('[name="info_status"]').value = config.info.status || '';
        form.querySelector('[name="info_wikipedia"]').value = config.info.wikipedia || '';
    }

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
const emojiList = [
    ' حیوانات ', ' P ', '🐖', '🐄', '🐏', '🐑', '🐐', '🐪', '🐫', '🐴', '🦓', '🦒', '🐘', '🦏', '🦛', '🐭', '🐁', '🐀', '🐹', '🐰', '🐇', '🐿️', '🦔', '🦇', '🐻', '🐨', '🐼', '🦥', '🦦', '🦨', '🦘', '🦡', '🐾', '🦃', '🐔', '🐓', '🐣', '🐤', '🐥', '🐦', '🐧', '🕊️', '🦅', '🦆', '🦢', '🦉', '🦩', '🦚', '🦜', '🐸', '🐊', '🐢', '🦎', '🐍', '🐲', '🐉', '🐳', '🐋', '🐬', '🐟', '🐠', '🐡', '🦈', '🐙', '🐚', '🐌', '🦋', '🐛', '🐜', '🐝', '🐞', '🦗', '🕷️', '🕸️', '🦂', '🦟', '🦠',
    ' plantas ', '💐', '🌸', '💮', '🏵️', '🌹', '🥀', '🌺', '🌻', '🌼', '🌷', '🌱', '🌲', '🌳', '🌴', '🌵', '🌾', '🌿', '☘️', '🍀', '🍁', '🍂', '🍃',
    ' hongos ', '🍄'
];

const emojiInput = document.getElementById('emoji-input');
const emojiPickerBtn = document.getElementById('emoji-picker-btn');
const emojiPanel = document.getElementById('emoji-panel');

// Populate panel
emojiList.forEach(emoji => {
    if (emoji.includes(' ')) { // Es un título
        const title = document.createElement('div');
        title.className = 'emoji-category';
        title.textContent = emoji;
        emojiPanel.appendChild(title);
    } else {
        const emojiSpan = document.createElement('span');
        emojiSpan.className = 'emoji-item';
        emojiSpan.textContent = emoji;
        emojiPanel.appendChild(emojiSpan);
    }
});

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
        const response = await fetch('../data/languages.json');
        availableLanguages = await response.json();

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
        }
    } catch (error) {
        console.error('Error cargando idiomas:', error);
    }
}

async function loadTranslations(animalId) {
    try {
        const response = await fetch(`../models/${animalId}/translations.json`);
        translations = await response.json();

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
    } catch (error) {
        console.error('Error cargando traducciones:', error);
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
    const langData = translations[lang] || {};

    const translatableFields = document.querySelectorAll('.translatable');
    translatableFields.forEach(field => {
        const fieldName = field.dataset.field;
        const value = langData[fieldName] || '';

        if (fieldName === 'detailed_description') {
            $('#detailed-description').summernote('code', value);
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
