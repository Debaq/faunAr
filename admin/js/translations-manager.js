// Gestión de Traducciones con Sistema de Pestañas

let languages = {};
let currentTab = 'config';
let currentSystemLang = null;
let systemTranslations = {};

// Variables globales para el modal
let currentAnimalId = null;

// Inicializar
document.addEventListener('DOMContentLoaded', () => {
    initTabs();
    loadLanguages();
    initTranslationModal();
});

// ============================================
// SISTEMA DE PESTAÑAS
// ============================================

function initTabs() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const saveBtn = document.getElementById('save-btn');

    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const tab = btn.dataset.tab;
            switchTab(tab);
        });
    });
}

function switchTab(tab) {
    // Actualizar botones
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.tab === tab);
    });

    // Actualizar contenido
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    document.getElementById(`tab-${tab}`).classList.add('active');

    // Actualizar botón de guardar
    const saveBtn = document.getElementById('save-btn');
    if (tab === 'config') {
        saveBtn.style.display = 'block';
        saveBtn.onclick = saveLanguages;
    } else if (tab === 'system') {
        saveBtn.style.display = 'block';
        saveBtn.onclick = saveSystemTranslations;
    } else {
        saveBtn.style.display = 'none';
    }

    currentTab = tab;

    // Cargar datos según la pestaña
    if (tab === 'system' && Object.keys(languages).length > 0) {
        loadSystemTab();
    } else if (tab === 'stats') {
        loadAnimalsProgress();
    }
}

// ============================================
// PESTAÑA: CONFIGURACIÓN
// ============================================

async function loadLanguages() {
    try {
        const response = await fetch('../api/languages/get.php');
        const data = await response.json();

        if (data.success) {
            languages = data.languages;
            renderLanguages();
        }
    } catch (error) {
        console.error('Error cargando idiomas:', error);
        showNotification('Error al cargar idiomas', 'error');
    }
}

function renderLanguages() {
    const container = document.getElementById('languages-list');

    const languageFlags = {
        'es': '🇪🇸', 'en': '🇬🇧', 'pt': '🇵🇹', 'fr': '🇫🇷', 'de': '🇩🇪',
        'it': '🇮🇹', 'zh': '🇨🇳', 'ja': '🇯🇵', 'ko': '🇰🇷', 'ru': '🇷🇺',
        'ar': '🇸🇦', 'hi': '🇮🇳', 'nl': '🇳🇱', 'sv': '🇸🇪', 'no': '🇳🇴',
        'da': '🇩🇰', 'fi': '🇫🇮', 'pl': '🇵🇱', 'tr': '🇹🇷', 'th': '🇹🇭',
        'vi': '🇻🇳', 'id': '🇮🇩', 'he': '🇮🇱', 'el': '🇬🇷', 'cs': '🇨🇿',
        'ro': '🇷🇴', 'hu': '🇭🇺', 'uk': '🇺🇦', 'ca': '🇪🇸'
    };

    container.innerHTML = '';

    for (const [code, lang] of Object.entries(languages)) {
        const card = document.createElement('div');
        card.style.cssText = 'background: #f5f6fa; padding: 12px 16px; border-radius: 6px; display: flex; align-items: center; justify-content: space-between;';

        card.innerHTML = `
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 14px;">${languageFlags[code] || '🌍'}</span>
                <strong style="font-size: 13px; color: #2c3e50;">${lang.name}</strong>
            </div>
            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                <input type="checkbox"
                       data-lang="${code}"
                       ${lang.enabled ? 'checked' : ''}
                       style="width: 16px; height: 16px; cursor: pointer;">
                <span style="color: ${lang.enabled ? '#27ae60' : '#95a5a6'}; font-weight: 500; font-size: 12px;">
                    ${lang.enabled ? '✓' : '✗'}
                </span>
            </label>
        `;

        const checkbox = card.querySelector('input[type="checkbox"]');
        checkbox.addEventListener('change', (e) => {
            languages[code].enabled = e.target.checked;
            renderLanguages();
        });

        container.appendChild(card);
    }
}

async function saveLanguages() {
    const btn = document.getElementById('save-btn');
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = '💾 Guardando...';

    try {
        const response = await fetch('../api/languages/update.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ languages })
        });

        const data = await response.json();

        if (data.success) {
            showNotification('Idiomas guardados correctamente', 'success');
        } else {
            showNotification(data.message || 'Error al guardar', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error de conexión', 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = originalText;
    }
}

// ============================================
// PESTAÑA: SISTEMA
// ============================================

function loadSystemTab() {
    const select = document.getElementById('system-lang-select');

    // Limpiar y llenar selector
    select.innerHTML = '<option value="">-- Selecciona un idioma --</option>';

    const languageFlags = {
        'es': '🇪🇸', 'en': '🇬🇧', 'pt': '🇵🇹', 'fr': '🇫🇷', 'de': '🇩🇪',
        'it': '🇮🇹', 'zh': '🇨🇳', 'ja': '🇯🇵', 'ko': '🇰🇷', 'ru': '🇷🇺',
        'ar': '🇸🇦', 'hi': '🇮🇳', 'nl': '🇳🇱', 'sv': '🇸🇪', 'no': '🇳🇴',
        'da': '🇩🇰', 'fi': '🇫🇮', 'pl': '🇵🇱', 'tr': '🇹🇷', 'th': '🇹🇭',
        'vi': '🇻🇳', 'id': '🇮🇩', 'he': '🇮🇱', 'el': '🇬🇷', 'cs': '🇨🇿',
        'ro': '🇷🇴', 'hu': '🇭🇺', 'uk': '🇺🇦', 'ca': '🇪🇸'
    };

    // Solo idiomas habilitados
    for (const [code, lang] of Object.entries(languages)) {
        if (lang.enabled) {
            const option = document.createElement('option');
            option.value = code;
            option.textContent = `${languageFlags[code] || '🌍'} ${lang.name}`;
            select.appendChild(option);
        }
    }

    // Evento de cambio
    select.onchange = (e) => loadSystemTranslations(e.target.value);
}

async function loadSystemTranslations(langCode) {
    if (!langCode) {
        document.getElementById('system-translations').innerHTML =
            '<div style="text-align: center; padding: 30px; color: #7f8c8d;">Selecciona un idioma para editar</div>';
        return;
    }

    currentSystemLang = langCode;

    try {
        const response = await fetch(`../data/i18n/${langCode}.json`);
        const data = await response.json();

        systemTranslations = data;
        renderSystemTranslations(data);
    } catch (error) {
        console.error('Error cargando traducciones:', error);
        document.getElementById('system-translations').innerHTML =
            '<div style="text-align: center; padding: 30px; color: #e74c3c;">Error al cargar traducciones</div>';
    }
}

function renderSystemTranslations(data) {
    const container = document.getElementById('system-translations');

    // Obtener referencia en español si no estamos editando español
    let reference = null;
    if (currentSystemLang !== 'es') {
        fetch('../data/i18n/es.json')
            .then(r => r.json())
            .then(refData => {
                reference = refData;
                renderForm(data, reference);
            })
            .catch(() => renderForm(data, null));
    } else {
        renderForm(data, null);
    }

    function renderForm(translations, ref) {
        container.innerHTML = '';

        // Renderizar secciones
        renderSection('Portal', 'portal', translations.portal, ref?.portal);
        renderSection('Visor AR', 'viewer', translations.viewer, ref?.viewer);
    }
}

function renderSection(title, key, data, reference) {
    const container = document.getElementById('system-translations');

    const section = document.createElement('div');
    section.className = 'translation-section';
    section.innerHTML = `<h3>${title}</h3>`;

    renderFields(section, data, reference, key);

    container.appendChild(section);
}

function renderFields(container, obj, refObj, path) {
    for (const [key, value] of Object.entries(obj)) {
        const currentPath = `${path}.${key}`;

        if (typeof value === 'object' && value !== null && !Array.isArray(value)) {
            // Es un objeto anidado, crear subsección
            const subsection = document.createElement('div');
            subsection.style.cssText = 'margin-left: 20px; margin-top: 15px; padding-left: 15px; border-left: 3px solid #dfe6e9;';
            subsection.innerHTML = `<h4 style="color: #7f8c8d; font-size: 14px; margin-bottom: 10px;">${key}</h4>`;
            renderFields(subsection, value, refObj?.[key], currentPath);
            container.appendChild(subsection);
        } else {
            // Es un campo traducible
            const field = document.createElement('div');
            field.className = 'translation-field';

            // Mostrar referencia si existe
            if (refObj && refObj[key]) {
                const refField = document.createElement('div');
                refField.className = 'translation-field reference';
                refField.innerHTML = `
                    <label>📌 Español (referencia):</label>
                    <div style="color: #27ae60; font-size: 13px; font-style: italic;">${refObj[key]}</div>
                `;
                field.appendChild(refField);
            }

            const label = document.createElement('label');
            label.textContent = key;
            field.appendChild(label);

            const input = document.createElement('input');
            input.type = 'text';
            input.value = value || '';
            input.dataset.path = currentPath;
            input.addEventListener('input', (e) => {
                updateTranslationValue(currentPath, e.target.value);
            });
            field.appendChild(input);

            container.appendChild(field);
        }
    }
}

function updateTranslationValue(path, value) {
    const keys = path.split('.');
    let obj = systemTranslations;

    for (let i = 0; i < keys.length - 1; i++) {
        obj = obj[keys[i]];
    }

    obj[keys[keys.length - 1]] = value;
}

async function saveSystemTranslations() {
    if (!currentSystemLang) {
        showNotification('Selecciona un idioma primero', 'warning');
        return;
    }

    const btn = document.getElementById('save-btn');
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = '💾 Guardando...';

    try {
        const response = await fetch('../api/languages/save-i18n.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                language: currentSystemLang,
                translations: systemTranslations
            })
        });

        const data = await response.json();

        if (data.success) {
            showNotification('Traducciones guardadas correctamente', 'success');
        } else {
            showNotification(data.message || 'Error al guardar', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error de conexión', 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = originalText;
    }
}

// ============================================
// PESTAÑA: ESTADÍSTICAS
// ============================================

async function loadAnimalsProgress() {
    try {
        const response = await fetch('../api/languages/progress.php');
        const data = await response.json();

        if (data.success) {
            renderAnimalsProgress(data.progress);
        }
    } catch (error) {
        console.error('Error cargando progreso:', error);
        document.getElementById('animals-progress').innerHTML =
            '<div style="text-align: center; padding: 30px; color: #e74c3c;">Error al cargar progreso</div>';
    }
}

function renderAnimalsProgress(progress) {
    const container = document.getElementById('animals-progress');

    if (progress.length === 0) {
        container.innerHTML = '<div style="text-align: center; padding: 30px; color: #7f8c8d;">No hay animales registrados</div>';
        return;
    }

    const languageFlags = {
        'es': '🇪🇸', 'en': '🇬🇧', 'pt': '🇵🇹', 'fr': '🇫🇷', 'de': '🇩🇪',
        'it': '🇮🇹', 'zh': '🇨🇳', 'ja': '🇯🇵', 'ko': '🇰🇷', 'ru': '🇷🇺',
        'ar': '🇸🇦', 'hi': '🇮🇳', 'nl': '🇳🇱', 'sv': '🇸🇪', 'no': '🇳🇴',
        'da': '🇩🇰', 'fi': '🇫🇮', 'pl': '🇵🇱', 'tr': '🇹🇷', 'th': '🇹🇭',
        'vi': '🇻🇳', 'id': '🇮🇩', 'he': '🇮🇱', 'el': '🇬🇷', 'cs': '🇨🇿',
        'ro': '🇷🇴', 'hu': '🇭🇺', 'uk': '🇺🇦', 'ca': '🇪🇸'
    };

    container.innerHTML = '';

    progress.forEach(animal => {
        const card = document.createElement('div');
        card.style.cssText = 'background: #f5f6fa; padding: 20px; border-radius: 8px; margin-bottom: 15px;';

        let languagesHTML = '';
        let enabledLanguages = [];

        // Recopilar idiomas activos
        for (const [lang, stats] of Object.entries(animal.languages)) {
            // Verificar si el idioma está activo
            if (!languages[lang] || !languages[lang].enabled) continue;

            const color = stats.percentage === 100 ? '#27ae60' :
                         stats.percentage >= 50 ? '#f39c12' : '#e74c3c';

            enabledLanguages.push({
                code: lang,
                flag: languageFlags[lang] || '🌍',
                percentage: stats.percentage,
                color: color
            });
        }

        // Crear HTML compacto de banderas y porcentajes
        if (enabledLanguages.length > 0) {
            languagesHTML = enabledLanguages.map(l =>
                `<span style="display: inline-flex; align-items: center; gap: 4px; margin: 0 6px 6px 0; padding: 4px 8px; background: #fff; border-radius: 4px; border: 1px solid ${l.color};">
                    <span style="font-size: 12px;">${l.flag}</span>
                    <strong style="color: ${l.color}; font-size: 12px;">${l.percentage}%</strong>
                </span>`
            ).join('');
        } else {
            languagesHTML = '<span style="color: #7f8c8d; font-size: 12px;">No hay idiomas activados</span>';
        }

        card.innerHTML = `
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                <div>
                    <h3 style="margin: 0 0 8px 0; color: #2c3e50; font-size: 18px;">${animal.name}</h3>
                    <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                        ${languagesHTML}
                    </div>
                </div>
                <div style="display: flex; gap: 8px;">
                    <button class="btn btn-secondary btn-small" onclick="openTranslationModal('${animal.id}', '${animal.name}')" style="white-space: nowrap;">
                        🤖 Traducir con LLM
                    </button>
                    <a href="animals.php?action=edit&id=${animal.id}" class="btn btn-primary btn-small" style="white-space: nowrap;">
                        Editar Traducciones
                    </a>
                </div>
            </div>
        `;

        container.appendChild(card);
    });
}

// ============================================
// MODAL DE TRADUCCIÓN POR ANIMAL
// ============================================

function initTranslationModal() {
    const modal = document.getElementById('translation-modal');
    const closeBtn = document.querySelector('.translation-modal-close');
    const cancelBtn = document.getElementById('cancel-translation-btn');
    const copyBtn = document.getElementById('copy-prompt-btn');
    const loadFileBtn = document.getElementById('load-file-btn');
    const fileInput = document.getElementById('llm-file');
    const processBtn = document.getElementById('process-translation-btn');

    // Cerrar modal
    const closeModal = () => {
        modal.classList.remove('active');
        currentAnimalId = null;
        document.getElementById('llm-prompt').value = '';
        document.getElementById('llm-response').value = '';
        document.getElementById('modal-target-lang').value = '';
        fileInput.value = '';
    };

    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });

    // Copiar prompt
    copyBtn.addEventListener('click', async () => {
        const prompt = document.getElementById('llm-prompt');
        try {
            await navigator.clipboard.writeText(prompt.value);
            showNotification('Prompt copiado al portapapeles', 'success');
        } catch (error) {
            prompt.select();
            document.execCommand('copy');
            showNotification('Prompt copiado (fallback)', 'success');
        }
    });

    // Cargar archivo
    loadFileBtn.addEventListener('click', async () => {
        const file = fileInput.files[0];
        if (!file) {
            showNotification('Selecciona un archivo primero', 'warning');
            return;
        }

        try {
            const content = await file.text();
            document.getElementById('llm-response').value = content;
            showNotification('Archivo cargado correctamente', 'success');
        } catch (error) {
            showNotification('Error al leer el archivo', 'error');
        }
    });

    // Procesar traducción
    processBtn.addEventListener('click', () => processAnimalTranslation());

    // Poblar selector de idiomas
    populateModalLanguageSelector();
}

function populateModalLanguageSelector() {
    const select = document.getElementById('modal-target-lang');
    if (!select) return;

    const languageFlags = {
        'es': '🇪🇸', 'en': '🇬🇧', 'pt': '🇵🇹', 'fr': '🇫🇷', 'de': '🇩🇪',
        'it': '🇮🇹', 'zh': '🇨🇳', 'ja': '🇯🇵', 'ko': '🇰🇷', 'ru': '🇷🇺',
        'ar': '🇸🇦', 'hi': '🇮🇳', 'nl': '🇳🇱', 'sv': '🇸🇪', 'no': '🇳🇴',
        'da': '🇩🇰', 'fi': '🇫🇮', 'pl': '🇵🇱', 'tr': '🇹🇷', 'th': '🇹🇭',
        'vi': '🇻🇳', 'id': '🇮🇩', 'he': '🇮🇱', 'el': '🇬🇷', 'cs': '🇨🇿',
        'ro': '🇷🇴', 'hu': '🇭🇺', 'uk': '🇺🇦', 'ca': '🇪🇸'
    };

    select.innerHTML = '<option value="">-- Selecciona idioma --</option>';

    for (const [code, lang] of Object.entries(languages)) {
        if (lang.enabled && code !== 'es') {
            const option = document.createElement('option');
            option.value = code;
            option.textContent = `${languageFlags[code] || '🌍'} ${lang.name}`;
            select.appendChild(option);
        }
    }
}

async function openTranslationModal(animalId, animalName) {
    currentAnimalId = animalId;

    try {
        // Cargar datos en español del animal
        const response = await fetch(`../api/languages/export-animal.php?id=${animalId}`);
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.message || 'Error al cargar datos');
        }

        // Generar prompt
        const prompt = `# Instrucciones de Traducción

Traduce el siguiente JSON con información del animal "${animalName}" desde español al idioma solicitado.

**IMPORTANTE:**
- Mantén la estructura JSON exacta
- Traduce SOLO los valores de texto, NO las claves
- Conserva todos los tags HTML en "detailed_description"
- Adapta la URL de Wikipedia al idioma de destino si existe
- Devuelve ÚNICAMENTE el JSON traducido

**Campos:**
- name: Nombre del animal
- short_description: Descripción breve
- habitat: Hábitat
- diet: Dieta
- status: Estado de conservación
- detailed_description: Descripción detallada (HTML)
- wikipedia: URL de Wikipedia

---

${JSON.stringify(data.data, null, 2)}`;

        document.getElementById('llm-prompt').value = prompt;
        document.getElementById('translation-modal').classList.add('active');

    } catch (error) {
        console.error('Error:', error);
        showNotification(error.message || 'Error al abrir modal', 'error');
    }
}

async function processAnimalTranslation() {
    const targetLang = document.getElementById('modal-target-lang').value;
    const response = document.getElementById('llm-response').value.trim();

    if (!targetLang) {
        showNotification('Selecciona un idioma', 'warning');
        return;
    }

    if (!response) {
        showNotification('Pega o carga la respuesta del LLM', 'warning');
        return;
    }

    const btn = document.getElementById('process-translation-btn');
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = '⏳ Procesando...';

    try {
        // Extraer JSON de la respuesta
        let jsonData;
        try {
            jsonData = JSON.parse(response);
        } catch {
            // Buscar JSON en el contenido
            const jsonMatch = response.match(/\{[\s\S]*\}/);
            if (!jsonMatch) {
                throw new Error('No se encontró JSON válido en la respuesta');
            }
            jsonData = JSON.parse(jsonMatch[0]);
        }

        // Enviar al servidor
        const result = await fetch('../api/languages/import-animal-translation.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                animalId: currentAnimalId,
                language: targetLang,
                translation: jsonData
            })
        });

        const resultData = await result.json();

        if (resultData.success) {
            showNotification('Traducción aplicada correctamente', 'success');
            document.getElementById('translation-modal').classList.remove('active');
            loadAnimalsProgress(); // Recargar estadísticas
        } else {
            throw new Error(resultData.message || 'Error al aplicar traducción');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification(error.message || 'Error al procesar traducción', 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = originalText;
    }
}

// Hacer la función global para onclick
window.openTranslationModal = openTranslationModal;
