// Translation Editor

let currentLanguage = 'es';
let currentSection = 'portal';
let translations = {};
let originalTranslations = {};

// Cargar traducciones
async function loadTranslations(lang) {
    try {
        const response = await fetch(`../api/translations/get-locale.php?lang=${lang}`);
        const data = await response.json();

        if (data.success) {
            translations = data.translations;
            originalTranslations = JSON.parse(JSON.stringify(translations));
            currentLanguage = lang;

            renderSection(currentSection);
            updateStats();
        }
    } catch (error) {
        console.error('Error cargando traducciones:', error);
    }
}

// Renderizar sección
function renderSection(section) {
    currentSection = section;

    // Actualizar botones activos
    document.querySelectorAll('#section-list li').forEach(li => {
        li.classList.remove('active');
        if (li.dataset.section === section) {
            li.classList.add('active');
        }
    });

    const container = document.getElementById('translation-fields');
    container.innerHTML = '';

    const sectionData = translations[section] || {};

    // Crear campos recursivamente
    renderFields(container, sectionData, section);
}

function renderFields(container, data, path) {
    Object.keys(data).forEach(key => {
        const value = data[key];
        const fullPath = `${path}.${key}`;

        if (typeof value === 'object' && value !== null && !Array.isArray(value)) {
            // Es un objeto, crear subsección
            const subsection = document.createElement('div');
            subsection.style.cssText = 'margin-bottom: 30px; padding-left: 15px; border-left: 3px solid #dfe6e9;';
            subsection.innerHTML = `<h4 style="color: #2c5f2d; margin-bottom: 15px;">${key}</h4>`;

            renderFields(subsection, value, fullPath);
            container.appendChild(subsection);
        } else {
            // Es un valor, crear campo de input
            const field = document.createElement('div');
            field.className = 'translation-field';

            const isMultiline = typeof value === 'string' && value.length > 80;

            field.innerHTML = `
                <label>${key}</label>
                ${isMultiline ?
                    `<textarea data-path="${fullPath}" rows="3">${value || ''}</textarea>` :
                    `<input type="text" data-path="${fullPath}" value="${value || ''}">`
                }
                <small>${fullPath}</small>
            `;

            container.appendChild(field);
        }
    });
}

// Guardar traducciones
async function saveTranslations() {
    // Recopilar valores de todos los campos
    const inputs = document.querySelectorAll('[data-path]');

    inputs.forEach(input => {
        const path = input.dataset.path;
        const value = input.value;

        // Actualizar objeto de traducciones
        setNestedValue(translations, path, value);
    });

    try {
        const response = await fetch('../api/translations/update.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                lang: currentLanguage,
                translations: translations
            })
        });

        const data = await response.json();

        if (data.success) {
            showNotification('Traducciones guardadas correctamente', 'success');
            originalTranslations = JSON.parse(JSON.stringify(translations));
        } else {
            showNotification(data.error || 'Error al guardar', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error de conexión', 'error');
    }
}

function setNestedValue(obj, path, value) {
    const keys = path.split('.');
    let current = obj;

    for (let i = 0; i < keys.length - 1; i++) {
        const key = keys[i];
        if (!current[key]) {
            current[key] = {};
        }
        current = current[key];
    }

    current[keys[keys.length - 1]] = value;
}

// Actualizar estadísticas
function updateStats() {
    const totalKeys = countKeys(translations);
    const translatedKeys = countNonEmptyKeys(translations);
    const missingKeys = totalKeys - translatedKeys;

    document.getElementById('total-keys').textContent = totalKeys;
    document.getElementById('translated-keys').textContent = translatedKeys;
    document.getElementById('missing-keys').textContent = missingKeys;
}

function countKeys(obj) {
    let count = 0;
    for (const key in obj) {
        if (typeof obj[key] === 'object' && obj[key] !== null && !Array.isArray(obj[key])) {
            count += countKeys(obj[key]);
        } else {
            count++;
        }
    }
    return count;
}

function countNonEmptyKeys(obj) {
    let count = 0;
    for (const key in obj) {
        if (typeof obj[key] === 'object' && obj[key] !== null && !Array.isArray(obj[key])) {
            count += countNonEmptyKeys(obj[key]);
        } else if (obj[key] !== '' && obj[key] !== null && obj[key] !== undefined) {
            count++;
        }
    }
    return count;
}

// Event listeners
document.getElementById('current-language').addEventListener('change', (e) => {
    loadTranslations(e.target.value);
});

document.querySelectorAll('#section-list li').forEach(li => {
    li.addEventListener('click', () => {
        renderSection(li.dataset.section);
    });
});

// Cargar traducciones iniciales
loadTranslations('es');
