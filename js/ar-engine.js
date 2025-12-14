let currentConfig = null;
let userLocation = null;

// Obtener parámetro de modelo desde URL
const urlParams = new URLSearchParams(window.location.search);
const modelId = urlParams.get('model');

async function initAR() {
    if (!modelId) {
        alert('No se especificó modelo');
        return;
    }

    updateLoadingStatus('Cargando configuración...');
    currentConfig = await ConfigLoader.load(modelId);

    if (!currentConfig) {
        alert('Error cargando configuración');
        return;
    }

    // Actualizar info panel
    updateInfoPanel();

    // Iniciar según modo AR
    if (currentConfig.arMode === 'gps' || currentConfig.arMode === 'hybrid') {
        await initGPS();
    }

    if (currentConfig.arMode === 'marker' || currentConfig.arMode === 'hybrid') {
        await initMarker();
    }

    setTimeout(() => {
        document.getElementById('loading').style.display = 'none';
    }, 2000);
}

function updateInfoPanel() {
    document.getElementById('animal-name').textContent = currentConfig.name;
    document.getElementById('animal-scientific').textContent = currentConfig.scientificName || '';

    if (currentConfig.info) {
        let details = '';
        if (currentConfig.info.habitat) details += `<p>🏞️ ${currentConfig.info.habitat}</p>`;
        if (currentConfig.info.diet) details += `<p>🍖 ${currentConfig.info.diet}</p>`;
        if (currentConfig.info.status) details += `<p>⚠️ ${currentConfig.info.status}</p>`;
        document.getElementById('animal-details').innerHTML = details;
    }
}

function updateLoadingStatus(message) {
    document.getElementById('loading-status').textContent = message;
}

async function initGPS() {
    updateLoadingStatus('Solicitando permisos GPS...');

    if (!navigator.geolocation) {
        console.error('GPS no disponible');
        return;
    }

    navigator.geolocation.watchPosition(
        (position) => {
            userLocation = {
                lat: position.coords.latitude,
                lon: position.coords.longitude
            };

            if (currentConfig.gps?.enabled) {
                const distance = calculateDistance(
                    userLocation.lat,
                    userLocation.lon,
                    currentConfig.gps.latitude,
                    currentConfig.gps.longitude
                );

                updateGPSStatus(distance);

                // Si está dentro del radio, mostrar modelo GPS
                if (distance <= currentConfig.gps.radius) {
                    showGPSModel();
                }
            }
        },
        (error) => {
            console.error('Error GPS:', error);
        },
        {
            enableHighAccuracy: true,
            maximumAge: 0,
            timeout: 27000
        }
    );
}

function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371e3; // Radio de la Tierra en metros
    const φ1 = lat1 * Math.PI / 180;
    const φ2 = lat2 * Math.PI / 180;
    const Δφ = (lat2 - lat1) * Math.PI / 180;
    const Δλ = (lon2 - lon1) * Math.PI / 180;

    const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
              Math.cos(φ1) * Math.cos(φ2) *
              Math.sin(Δλ/2) * Math.sin(Δλ/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));

    return R * c; // Distancia en metros
}

function updateGPSStatus(distance) {
    const statusDiv = document.getElementById('gps-status');
    statusDiv.style.display = 'block';

    document.getElementById('distance-text').textContent =
        `📍 Distancia: ${Math.round(distance)}m`;

    if (distance <= currentConfig.gps.radius) {
        document.getElementById('direction-text').textContent =
            '✅ ¡Estás en el punto! Busca el marcador o mira alrededor';
    } else {
        document.getElementById('direction-text').textContent =
            `➡️ Acércate ${Math.round(distance - currentConfig.gps.radius)}m más`;
    }
}

function showGPSModel() {
    const container = document.getElementById('gps-container');

    // Evitar duplicados
    if (container.querySelector('[gps-entity-place]')) return;

    const entity = document.createElement('a-entity');
    entity.setAttribute('gps-entity-place',
        `latitude: ${currentConfig.gps.latitude}; longitude: ${currentConfig.gps.longitude}`);
    entity.setAttribute('gltf-model', `models/${modelId}/${currentConfig.model.glb}`);
    entity.setAttribute('scale', currentConfig.model.scale);
    entity.setAttribute('rotation', currentConfig.model.rotation);

    if (currentConfig.model.glb.includes('glb') || currentConfig.model.glb.includes('gltf')) {
        entity.setAttribute('animation-mixer', '');
    }

    container.appendChild(entity);

    document.getElementById('info-panel').style.display = 'block';

    // Reproducir sonido si está configurado
    if (currentConfig.audio?.enabled) {
        const audio = new Audio(`models/${modelId}/${currentConfig.audio.file}`);
        audio.play().catch(e => console.log('Audio bloqueado:', e));
    }
}

async function initMarker() {
    updateLoadingStatus('Inicializando detección de marcadores...');

    const scene = document.querySelector('a-scene');
    const markerContainer = document.getElementById('marker-container');

    let marker;

    if (currentConfig.marker.type === 'mind') {
        // MIND marker (NFT) - usar MindAR.js
        marker = document.createElement('a-nft');
        marker.setAttribute('type', 'nft');
        marker.setAttribute('url', `models/${modelId}/${currentConfig.marker.file.replace('.mind', '')}`);
    } else if (currentConfig.marker.type === 'pattern') {
        // Pattern marker (.patt) - usar AR.js
        marker = document.createElement('a-marker');
        marker.setAttribute('type', 'pattern');
        marker.setAttribute('url', `models/${modelId}/${currentConfig.marker.file}`);
    }

    marker.setAttribute('smooth', 'true');
    marker.setAttribute('smoothCount', '10');
    marker.setAttribute('smoothTolerance', '0.01');
    marker.setAttribute('smoothThreshold', '5');

    // Agregar modelo al marcador
    const modelEntity = document.createElement('a-entity');
    modelEntity.setAttribute('gltf-model', `models/${modelId}/${currentConfig.model.glb}`);
    modelEntity.setAttribute('scale', currentConfig.model.scale);
    modelEntity.setAttribute('position', currentConfig.model.position);
    modelEntity.setAttribute('rotation', currentConfig.model.rotation);

    if (currentConfig.model.glb.includes('glb') || currentConfig.model.glb.includes('gltf')) {
        modelEntity.setAttribute('animation-mixer', '');
    }

    marker.appendChild(modelEntity);
    scene.appendChild(marker);

    // Eventos del marcador
    marker.addEventListener('markerFound', () => {
        console.log('Marcador encontrado');
        document.getElementById('info-panel').style.display = 'block';

        if (currentConfig.audio?.enabled) {
            const audio = new Audio(`models/${modelId}/${currentConfig.audio.file}`);
            audio.play().catch(e => console.log('Audio bloqueado:', e));
        }
    });

    marker.addEventListener('markerLost', () => {
        console.log('Marcador perdido');
        document.getElementById('info-panel').style.display = 'none';
    });
}

// Iniciar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', initAR);
