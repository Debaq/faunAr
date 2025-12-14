// Escanear carpetas de modelos y cargar configuraciones
async function loadProjects() {
    const container = document.getElementById('projects-container');

    try {
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
                const response = await fetch(`models/${folder}/config.json`);
                const config = await response.json();

                const card = createProjectCard(config, folder);
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

function createProjectCard(config, folder) {
    const card = document.createElement('div');
    card.className = 'project-card';

    const badges = [];
    if (config.gps?.enabled) badges.push('<span class="badge">📍 GPS</span>');
    if (config.marker?.enabled) badges.push('<span class="badge">🎯 Marcador</span>');
    if (config.audio?.enabled) badges.push('<span class="badge">🔊 Audio</span>');

    card.innerHTML = `
        ${config.thumbnail ? `<img src="models/${folder}/${config.thumbnail}" alt="${config.name}">` : '<img src="" alt="' + config.name + '" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">'}
        <h2>${config.name}</h2>
        <p class="scientific">${config.scientificName || ''}</p>
        <p>${config.description}</p>
        <div class="badges">
            ${badges.join('')}
        </div>
        <div class="card-actions">
            <button class="btn btn-primary" onclick="openViewer('${folder}')">
                🔍 Ver en AR
            </button>
            <button class="btn btn-secondary" onclick="showQR('${folder}', '${config.name}')">
                📱 Ver QR
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

// Cerrar modal al hacer click fuera
document.addEventListener('click', (e) => {
    const modal = document.getElementById('qr-modal');
    if (e.target === modal) {
        closeQRModal();
    }
});

// Cargar al inicio
document.addEventListener('DOMContentLoaded', loadProjects);
