# FaunAR - Sistema Portal WebAR

## Instrucciones para Claude Code

Construir un sistema portal para experiencias AR en web que permita:
- Portal principal para listar todos los proyectos/modelos AR disponibles
- Acceso directo vía QR a modelos específicos
- Cada modelo en subcarpeta independiente con su configuración
- Soporte GPS y/o marcadores según configuración
- Soporte archivos MIND para marcadores

## Estructura del Proyecto

```
faunar/
├── index.html                    # Portal principal
├── viewer.html                   # Visor AR universal
├── css/
│   ├── portal.css               # Estilos del portal
│   └── viewer.css               # Estilos del visor
├── js/
│   ├── portal.js                # Lógica del portal
│   ├── ar-engine.js             # Motor AR (GPS + marcadores)
│   └── config-loader.js         # Cargador de configuraciones
└── models/                       # Subcarpetas de modelos
    ├── jaguar/
    │   ├── config.json          # Configuración del modelo
    │   ├── model.glb            # Modelo 3D iOS/Android
    │   ├── model.usdz           # Modelo 3D iOS nativo (opcional)
    │   ├── sound.mp3            # Sonido (opcional)
    │   ├── marker.mind          # Archivo MIND para marcador (opcional)
    │   └── marker.patt          # Patrón AR.js (opcional)
    └── otro-animal/
        └── config.json
```

## Paso 1: Crear config.json ejemplo

Crear archivo en `models/jaguar/config.json`:

```json
{
  "id": "jaguar",
  "name": "Jaguar",
  "scientificName": "Panthera onca",
  "description": "El jaguar es el felino más grande de América y el tercero del mundo.",
  "thumbnail": "thumbnail.jpg",
  "arMode": "hybrid",
  "gps": {
    "enabled": true,
    "latitude": -41.4693,
    "longitude": -72.9424,
    "radius": 50
  },
  "marker": {
    "enabled": true,
    "type": "mind",
    "file": "marker.mind"
  },
  "model": {
    "glb": "model.glb",
    "usdz": "model.usdz",
    "scale": "1 1 1",
    "position": "0 0 0",
    "rotation": "0 0 0"
  },
  "audio": {
    "enabled": false,
    "file": "sound.mp3"
  },
  "info": {
    "habitat": "Selvas tropicales",
    "diet": "Carnívoro",
    "status": "Casi amenazado"
  }
}
```

## Paso 2: Crear index.html (Portal Principal)

```html
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FaunAR - Portal</title>
    <link rel="stylesheet" href="css/portal.css">
</head>
<body>
    <header>
        <h1>🌿 FaunAR</h1>
        <p>Experiencias de fauna en Realidad Aumentada</p>
    </header>

    <main id="projects-container">
        <!-- Se carga dinámicamente desde JS -->
    </main>

    <footer>
        <p>Escanea un QR o selecciona un proyecto</p>
    </footer>

    <script src="js/portal.js"></script>
</body>
</html>
```

## Paso 3: Crear css/portal.css

```css
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: system-ui, -apple-system, sans-serif;
    background: linear-gradient(135deg, #2d5016 0%, #1a2f0a 100%);
    min-height: 100vh;
    color: white;
}

header {
    text-align: center;
    padding: 40px 20px;
}

header h1 {
    font-size: 2.5rem;
    margin-bottom: 10px;
}

header p {
    font-size: 1.1rem;
    opacity: 0.9;
}

main {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.project-card {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border-radius: 15px;
    padding: 20px;
    cursor: pointer;
    transition: transform 0.3s, background 0.3s;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.project-card:hover {
    transform: translateY(-5px);
    background: rgba(255, 255, 255, 0.15);
}

.project-card img {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: 10px;
    margin-bottom: 15px;
}

.project-card h2 {
    font-size: 1.5rem;
    margin-bottom: 5px;
}

.project-card .scientific {
    font-style: italic;
    opacity: 0.8;
    margin-bottom: 10px;
    font-size: 0.9rem;
}

.project-card p {
    line-height: 1.5;
    margin-bottom: 15px;
}

.project-card .badges {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.badge {
    background: rgba(76, 175, 80, 0.3);
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.8rem;
    border: 1px solid rgba(76, 175, 80, 0.5);
}

footer {
    text-align: center;
    padding: 40px 20px;
    opacity: 0.7;
}

@media (max-width: 768px) {
    main {
        grid-template-columns: 1fr;
    }
}
```

## Paso 4: Crear js/portal.js

```javascript
// Escanear carpetas de modelos y cargar configuraciones
async function loadProjects() {
    const container = document.getElementById('projects-container');
    
    // Lista de modelos (esto se expande según agregues carpetas)
    const modelFolders = ['jaguar']; // Agregar más según sea necesario
    
    for (const folder of modelFolders) {
        try {
            const response = await fetch(`models/${folder}/config.json`);
            const config = await response.json();
            
            const card = createProjectCard(config, folder);
            container.appendChild(card);
        } catch (error) {
            console.error(`Error cargando ${folder}:`, error);
        }
    }
}

function createProjectCard(config, folder) {
    const card = document.createElement('div');
    card.className = 'project-card';
    card.onclick = () => openViewer(folder);
    
    const badges = [];
    if (config.gps?.enabled) badges.push('<span class="badge">📍 GPS</span>');
    if (config.marker?.enabled) badges.push('<span class="badge">🎯 Marcador</span>');
    if (config.audio?.enabled) badges.push('<span class="badge">🔊 Audio</span>');
    
    card.innerHTML = `
        ${config.thumbnail ? `<img src="models/${folder}/${config.thumbnail}" alt="${config.name}">` : ''}
        <h2>${config.name}</h2>
        <p class="scientific">${config.scientificName || ''}</p>
        <p>${config.description}</p>
        <div class="badges">
            ${badges.join('')}
        </div>
    `;
    
    return card;
}

function openViewer(modelId) {
    window.location.href = `viewer.html?model=${modelId}`;
}

// Cargar al inicio
document.addEventListener('DOMContentLoaded', loadProjects);
```

## Paso 5: Crear viewer.html (Visor AR Universal)

```html
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FaunAR - Visor</title>
    <script src="https://aframe.io/releases/1.4.2/aframe.min.js"></script>
    <script src="https://raw.githack.com/AR-js-org/AR.js/master/aframe/build/aframe-ar.js"></script>
    <script src="https://raw.githack.com/AR-js-org/AR.js/master/aframe/build/aframe-ar-nft.js"></script>
    <link rel="stylesheet" href="css/viewer.css">
</head>
<body>
    <div id="loading">
        <div class="spinner"></div>
        <h2>Cargando experiencia AR...</h2>
        <p id="loading-status">Iniciando</p>
    </div>

    <button id="back-btn" onclick="history.back()">← Volver</button>

    <div id="info-panel">
        <h3 id="animal-name"></h3>
        <p id="animal-scientific"></p>
        <div id="animal-details"></div>
    </div>

    <div id="gps-status" style="display: none;">
        <p id="distance-text"></p>
        <p id="direction-text"></p>
    </div>

    <a-scene
        id="ar-scene"
        embedded
        arjs="sourceType: webcam; debugUIEnabled: false;"
        vr-mode-ui="enabled: false"
        renderer="logarithmicDepthBuffer: true;">
        
        <a-camera gps-camera rotation-reader></a-camera>
        
        <!-- Contenedor para modelos GPS -->
        <a-entity id="gps-container"></a-entity>
        
        <!-- Contenedor para marcadores -->
        <div id="marker-container"></div>
    </a-scene>

    <script src="js/config-loader.js"></script>
    <script src="js/ar-engine.js"></script>
</body>
</html>
```

## Paso 6: Crear css/viewer.css

```css
body {
    margin: 0;
    overflow: hidden;
    font-family: system-ui, -apple-system, sans-serif;
}

#loading {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.9);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    z-index: 1000;
    color: white;
}

.spinner {
    width: 50px;
    height: 50px;
    border: 4px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 20px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

#back-btn {
    position: fixed;
    top: 10px;
    right: 10px;
    background: rgba(76, 175, 80, 0.9);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
    z-index: 100;
    backdrop-filter: blur(10px);
}

#info-panel {
    position: fixed;
    bottom: 20px;
    left: 20px;
    right: 20px;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(10px);
    color: white;
    padding: 20px;
    border-radius: 15px;
    max-width: 400px;
    display: none;
    z-index: 100;
}

#info-panel h3 {
    margin: 0 0 5px 0;
    font-size: 1.5rem;
}

#info-panel p {
    margin: 0 0 10px 0;
    opacity: 0.8;
    font-style: italic;
}

#gps-status {
    position: fixed;
    top: 70px;
    left: 10px;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 15px;
    border-radius: 10px;
    z-index: 100;
}

#gps-status p {
    margin: 5px 0;
}
```

## Paso 7: Crear js/config-loader.js

```javascript
class ConfigLoader {
    static async load(modelId) {
        try {
            const response = await fetch(`models/${modelId}/config.json`);
            if (!response.ok) throw new Error('Config not found');
            return await response.json();
        } catch (error) {
            console.error('Error loading config:', error);
            return null;
        }
    }
}
```

## Paso 8: Crear js/ar-engine.js

```javascript
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
        // MIND marker (NFT)
        marker = document.createElement('a-nft');
        marker.setAttribute('type', 'nft');
        marker.setAttribute('url', `models/${modelId}/${currentConfig.marker.file.replace('.mind', '')}`);
    } else {
        // Pattern marker (.patt)
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
```

## Paso 9: Crear README.md

```markdown
# FaunAR

Sistema modular de realidad aumentada web para experiencias de fauna.

## Agregar nuevo modelo

1. Crear carpeta en `models/nombre-animal/`
2. Agregar archivos:
   - `config.json` (obligatorio)
   - `model.glb` (obligatorio)
   - `model.usdz` (opcional, para iOS nativo)
   - `thumbnail.jpg` (opcional)
   - `sound.mp3` (opcional)
   - `marker.mind` o `marker.patt` (según configuración)

3. Editar `js/portal.js` y agregar el nombre de la carpeta al array `modelFolders`

## Configuración GPS

En `config.json` usa coordenadas reales:
```json
"gps": {
  "enabled": true,
  "latitude": -41.4693,
  "longitude": -72.9424,
  "radius": 50
}
```

## Deployment

Subir a cualquier hosting con HTTPS (Netlify, Vercel, GitHub Pages).
```

## TAREA FINAL

Crear la estructura de carpetas y archivos como se indica arriba. El sistema debe estar listo para:
1. Mostrar portal con proyectos disponibles
2. Cargar configuraciones dinámicamente
3. Soportar GPS y marcadores según config
4. Ser modular para agregar nuevos modelos fácilmente