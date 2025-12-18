// Escanear carpetas de modelos y cargar configuraciones
async function loadProjects() {
    const container = document.getElementById('projects-container');

    try {
        // Obtener idioma actual
        const currentLang = window.i18n ? window.i18n.getCurrentLanguage() : 'es';

        // Obtener lista de modelos dinámicamente desde el servidor
        const modelsResponse = await fetch('api/get-models.php');
        const modelsData = await modelsResponse.json();

        if (!modelsData.success || modelsData.models.length === 0) {
            container.innerHTML = '<p style="text-align: center; padding: 2rem;">No se encontraron modelos disponibles</p>';
            return;
        }

        // Cargar configuración de cada modelo
        for (const folder of modelsData.models) {
            try {
                const [configRes, translationsRes] = await Promise.all([
                    fetch(`models/${folder}/config.json`),
                    fetch(`models/${folder}/translations.json`)
                ]);

                const config = await configRes.json();
                const translations = await translationsRes.json();

                // Obtener traducción del idioma actual o fallback a español
                const translation = translations[currentLang] || translations['es'] || {};

                const card = createProjectCard(config, folder, translation);
                container.appendChild(card);
            } catch (error) {
                console.error(`Error cargando ${folder}:`, error);
            }
        }
    } catch (error) {
        console.error('Error obteniendo lista de modelos:', error);
        container.innerHTML = '<p style="text-align: center; padding: 2rem; color: #e74c3c;">Error cargando modelos</p>';
    }
}

function createProjectCard(config, folder, translation = {}) {
    const card = document.createElement('div');
    card.className = 'project-card';
    card.dataset.folder = folder;

    // Usar traducción o fallback a config
    const displayName = translation.name || config.name;
    const displayDescription = translation.short_description || config.description;

    const badges = [];
    if (config.gps?.enabled) badges.push('<span class="badge">📍 GPS</span>');
    if (config.marker?.enabled) badges.push('<span class="badge">🎯 Marcador</span>');
    if (config.audio?.enabled) badges.push('<span class="badge">🔊 Audio</span>');

    // Recursos adicionales
    const resources = [];

    // Modelo GLB
    if (config.model?.glb) {
        resources.push(`<button class="btn-resource" onclick="downloadResource('models/${folder}/${config.model.glb}', '${displayName}.glb')" title="Descargar modelo 3D GLB">📦 GLB</button>`);
    }

    // Modelo USDZ
    if (config.model?.usdz) {
        resources.push(`<button class="btn-resource" onclick="downloadResource('models/${folder}/${config.model.usdz}', '${displayName}.usdz')" title="Descargar modelo 3D USDZ">📦 USDZ</button>`);
    }

    // Sonido
    if (config.audio?.enabled && config.audio?.file) {
        const buttonId = `sound-btn-${folder}`;
        resources.push(`<button class="btn-resource" id="${buttonId}" onclick="toggleSound('models/${folder}/${config.audio.file}', '${folder}', '${buttonId}')" title="Reproducir sonido">🔊 Sonido</button>`);
    }

    // Wikipedia - usar traducción si existe
    const wikiUrl = translation.wikipedia || config.info?.wikipedia;
    if (wikiUrl) {
        resources.push(`<a class="btn-resource" href="${wikiUrl}" target="_blank" title="Ver en Wikipedia">📖 Wiki</a>`);
    }

    card.innerHTML = `
        ${config.thumbnail ? `<img src="models/${folder}/${config.thumbnail}" alt="${displayName}" loading="lazy">` : '<img src="" alt="' + displayName + '" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">'}
        <h2>${displayName}</h2>
        <p class="scientific">${config.scientificName || ''}</p>
        <p>${displayDescription}</p>
        <div class="badges">
            ${badges.join('')}
        </div>
        ${resources.length > 0 ? `<div class="resources">${resources.join('')}</div>` : ''}
        <div class="card-actions">
            <button class="btn btn-primary" onclick="openViewer('${folder}')">
                🔍 Ver en AR
            </button>
            <button class="btn btn-secondary" onclick="showQR('${folder}', '${displayName}')">
                📱 Ver QR/Marcador
            </button>
        </div>
    `;

    return card;
}

function openViewer(modelId) {
    window.location.href = `viewer.html?model=${modelId}`;
}

async function showQR(modelId, modelName) {
    const modal = document.getElementById('qr-modal');
    const qrContainer = document.getElementById('qr-container');
    const modalTitle = document.getElementById('modal-title');
    const modalSubtitle = document.getElementById('modal-subtitle');

    // Limpiar QR anterior
    qrContainer.innerHTML = '';

    // Generar URL completa
    const viewerURL = `${window.location.origin}${window.location.pathname.replace('index.html', '')}viewer.html?model=${modelId}`;

    // Actualizar textos
    modalTitle.textContent = modelName;
    modalSubtitle.textContent = 'Escanea este código QR para ver el modelo en AR';

    // Verificar si existe QR pre-generado
    const qrImagePath = `models/${modelId}/qr_${modelId}.png`;

    try {
        const response = await fetch(qrImagePath, { method: 'HEAD' });

        if (response.ok) {
            // Usar QR existente
            const img = document.createElement('img');
            img.src = qrImagePath;
            img.alt = `QR Code ${modelName}`;
            img.style.width = '256px';
            img.style.height = '256px';
            qrContainer.appendChild(img);

            // Guardar ruta de imagen para descarga
            window.currentQRImage = qrImagePath;
        } else {
            throw new Error('QR no encontrado');
        }
    } catch (error) {
        // Si no existe, generar QR automáticamente
        new QRCode(qrContainer, {
            text: viewerURL,
            width: 256,
            height: 256,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });

        // Guardar URL para descarga
        window.currentQRUrl = viewerURL;
        window.currentQRImage = null;
    }

    // Mostrar modal
    modal.classList.add('active');
}

function closeQRModal() {
    const modal = document.getElementById('qr-modal');
    modal.classList.remove('active');
}

async function downloadQR() {
    // Si hay imagen pre-generada, descargarla
    if (window.currentQRImage) {
        const link = document.createElement('a');
        link.download = 'faunar-qr.png';
        link.href = window.currentQRImage;
        link.click();
        return;
    }

    // Si es QR generado, descargar desde canvas
    const canvas = document.querySelector('#qr-container canvas');
    if (canvas) {
        const link = document.createElement('a');
        link.download = 'faunar-qr.png';
        link.href = canvas.toDataURL();
        link.click();
    }
}

// Descargar recurso
function downloadResource(url, filename) {
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    link.click();
}

// Reproducir/detener sonido
let currentAudio = null;
let currentButton = null;
let currentModelId = null;

// Handlers de eventos de audio
function handleAudioEnd() {
    stopSound();
}

function handleAudioError() {
    stopSound();
}

function toggleSound(url, modelId, buttonId) {
    const button = document.getElementById(buttonId);

    // Si es el mismo audio y está reproduciendo, detener
    if (currentAudio && currentModelId === modelId) {
        stopSound();
        return;
    }

    // Si hay otro audio reproduciéndose, detenerlo completamente
    if (currentAudio) {
        stopSound();
    }

    // Crear nuevo audio
    const newAudio = new Audio();
    newAudio.src = url;
    newAudio.loop = false; // Asegurarse de que no se repita

    // Actualizar referencias globales ANTES de reproducir
    currentAudio = newAudio;
    currentModelId = modelId;
    // currentButton ya no es la única referencia, pero lo actualizamos para el estado visual inmediato
    currentButton = button; 

    // Cambiar texto de ambos botones (si existen)
    const cardButton = document.getElementById(`sound-btn-${modelId}`);
    const modalButton = document.getElementById(`sound-btn-modal-${modelId}`);
    
    if (cardButton) {
        cardButton.innerHTML = '⏹️ Stop';
        cardButton.title = 'Detener sonido';
    }
    if (modalButton) {
        modalButton.innerHTML = '⏹️ Stop';
        modalButton.title = 'Detener sonido';
    }

    // Cuando el audio termine, restaurar botón
    newAudio.addEventListener('ended', handleAudioEnd);

    // Manejar errores de carga
    newAudio.addEventListener('error', handleAudioError);

    // Reproducir solo cuando esté listo
    newAudio.addEventListener('canplaythrough', function playWhenReady() {
        if (currentAudio === newAudio) {
            newAudio.play().catch(error => {
                console.error('Error reproduciendo sonido:', error);
                stopSound();
            });
        }
        newAudio.removeEventListener('canplaythrough', playWhenReady);
    }, { once: true });

    newAudio.load();
}

function stopSound() {
    if (currentAudio) {
        currentAudio.pause();
        currentAudio.currentTime = 0;
        currentAudio.removeEventListener('ended', handleAudioEnd);
        currentAudio.removeEventListener('error', handleAudioError);
        currentAudio = null;
    }

    // Restaurar ambos botones (card y modal) si existen
    if (currentModelId) {
        const cardButton = document.getElementById(`sound-btn-${currentModelId}`);
        const modalButton = document.getElementById(`sound-btn-modal-${currentModelId}`);
        
        if (cardButton) {
            cardButton.innerHTML = '🔊 Sonido';
            cardButton.title = 'Reproducir sonido';
        }
        if (modalButton) {
            modalButton.innerHTML = '🔊 Sonido';
            modalButton.title = 'Reproducir sonido';
        }
    }

    currentButton = null;
    currentModelId = null;
}

// Cerrar modal al hacer click fuera
document.addEventListener('click', (e) => {
    const modal = document.getElementById('qr-modal');
    if (e.target === modal) {
        closeQRModal();
    }
});

// Recargar proyectos cuando cambie el idioma
window.addEventListener('languageChanged', () => {
    const container = document.getElementById('projects-container');
    container.innerHTML = ''; // Limpiar contenedor
    loadProjects(); // Recargar con nuevo idioma
});

// Cargar al inicio
document.addEventListener('DOMContentLoaded', loadProjects);
