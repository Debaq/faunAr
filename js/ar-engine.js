let currentConfig = null;
let userLocation = null;
let arSystemReady = false;
let globalAudioInstance = null;
let isAudioPlaying = false;
let isModelCaptured = false;
let markerAnchor = null;
let captured3DModel = null;

// Obtener parámetro de modelo desde URL
const urlParams = new URLSearchParams(window.location.search);
const modelId = urlParams.get('model');

// Función para solicitar permisos desde el botón del usuario
window.requestPermissionsFromUser = async function() {
    console.log('🔐 Usuario solicitando permisos de sensores...');
    const permissionsBtn = document.getElementById('request-permissions-btn');

    try {
        permissionsBtn.textContent = 'Solicitando permisos...';
        permissionsBtn.disabled = true;

        await requestAllPermissions();

        console.log('✅ Permisos concedidos, continuando con AR...');
        permissionsBtn.style.display = 'none';

        // Continuar con la inicialización normal
        updateLoadingStatus('Permisos concedidos, cargando configuración...');
        await continueInitAR();
    } catch (error) {
        console.error('Error solicitando permisos:', error);
        permissionsBtn.textContent = 'Reintentar Permisos';
        permissionsBtn.disabled = false;
        alert('Se necesitan permisos para continuar. Por favor, autoriza el acceso cuando se solicite.');
    }
};

// Función para iniciar AR manualmente (llamada por el botón)
window.manualStartAR = async function() {
    console.log('🖱️ Usuario presionó botón de inicio manual');
    const scene = document.querySelector('a-scene');
    const arSystem = scene.systems['mindar-image-system'];

    if (arSystem && arSystem.start) {
        try {
            console.log('🚀 Arrancando MindAR manualmente desde botón...');
            await arSystem.start();
            console.log('✓ MindAR arrancado exitosamente');

            // Ocultar el botón
            document.getElementById('start-ar-btn').style.display = 'none';
            updateLoadingStatus('Cámara activada, apunta al marcador...');

            // Continuar con el setup del modelo
            await continueMarkerSetup();
        } catch (error) {
            console.error('✗ Error al arrancar MindAR:', error);
            alert('Error al activar la cámara: ' + error.message);
        }
    } else {
        console.error('Sistema MindAR no disponible');
        alert('Error: Sistema AR no disponible');
    }
};

async function initAR() {
    if (!modelId) {
        alert('No se especificó modelo');
        document.getElementById('loading').style.display = 'none';
        return;
    }

    // Verificar si requiere permiso del usuario (iOS)
    const needsUserPermission = (
        (typeof DeviceOrientationEvent !== 'undefined' &&
         typeof DeviceOrientationEvent.requestPermission === 'function') ||
        (typeof DeviceMotionEvent !== 'undefined' &&
         typeof DeviceMotionEvent.requestPermission === 'function')
    );

    if (needsUserPermission) {
        // En iOS, mostrar botón para solicitar permisos
        console.log('📱 iOS detectado, mostrando botón de permisos');
        updateLoadingStatus('Toca "Permitir Sensores" para continuar');
        document.getElementById('request-permissions-btn').style.display = 'block';
        return;
    } else {
        // En Android u otros navegadores, solicitar permisos automáticamente
        try {
            updateLoadingStatus('Solicitando permisos de sensores...');
            await requestAllPermissions();
        } catch (error) {
            console.warn('⚠️ Error solicitando permisos:', error);
        }
        await continueInitAR();
    }
}

async function continueInitAR() {
    // Timeout de seguridad: ocultar loading después de 8 segundos pase lo que pase
    const safetyTimeout = setTimeout(() => {
        console.warn('Timeout de seguridad: ocultando loading después de 8 segundos');
        document.getElementById('loading').style.display = 'none';
    }, 8000);

    try {
        updateLoadingStatus('Cargando configuración...');
        currentConfig = await ConfigLoader.load(modelId);

        if (!currentConfig) {
            alert('Error cargando configuración');
            clearTimeout(safetyTimeout);
            document.getElementById('loading').style.display = 'none';
            return;
        }

        // Actualizar info panel
        updateInfoPanel();

        // Iniciar según modo AR
        if (currentConfig.arMode === 'gps' || currentConfig.arMode === 'hybrid') {
            await initGPS();
        }

        if (currentConfig.arMode === 'marker' || currentConfig.arMode === 'hybrid') {
            console.log('Iniciando modo marcador...');
            await initMarker();
            console.log('Modo marcador iniciado.');
        }
    } catch (error) {
        console.error('Error durante la inicialización de AR:', error);
        alert('Error durante la inicialización de AR: ' + error.message);
        clearTimeout(safetyTimeout);
        document.getElementById('loading').style.display = 'none';
    }
}

async function checkCameraAccess() {
    try {
        await navigator.mediaDevices.getUserMedia({ video: true });
        console.log('Acceso a cámara verificado');
    } catch (error) {
        console.error('Error de acceso a cámara:', error);
        alert(`Error de cámara: ${error.name} - ${error.message}. Asegúrate de usar HTTPS o localhost y cerrar otras apps que usen la cámara.`);
    }
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

    // Actualizar icono del botón de captura de modelo según el animal
    updateCaptureModelButton();
}

// Actualizar el icono del botón de captura de modelo según el animal cargado
function updateCaptureModelButton() {
    const captureBtn = document.getElementById('capture-model-btn');
    if (!captureBtn || !currentConfig) return;

    // Obtener el emoji desde el config, con fallback a emoji por defecto
    let emoji = '🦌'; // Default fallback

    if (currentConfig.icon) {
        // Si el config tiene un icono definido, usarlo
        emoji = currentConfig.icon;
        console.log('✓ Icono del botón cargado desde config:', emoji);
    } else {
        // Fallback: mapeo de animales a emojis (para configs antiguos sin icon)
        const animalEmojis = {
            'puma': '🐆',
            'huemul': '🦌',
            'huillin': '🦦',
            'condor': '🦅',
            'pudú': '🦌',
            'zorro': '🦊',
            'guanaco': '🦙',
            'pingüino': '🐧'
        };

        const modelNameLower = (currentConfig.name || modelId || '').toLowerCase();
        for (const [key, value] of Object.entries(animalEmojis)) {
            if (modelNameLower.includes(key)) {
                emoji = value;
                break;
            }
        }
        console.log('⚠️ Icono no encontrado en config, usando fallback:', emoji);
    }

    // Actualizar el CSS con el emoji correcto
    const style = document.createElement('style');
    style.id = 'capture-model-emoji-style';

    // Remover estilo previo si existe
    const prevStyle = document.getElementById('capture-model-emoji-style');
    if (prevStyle) prevStyle.remove();

    style.textContent = `
        #capture-model-btn::before {
            content: '${emoji}';
        }
    `;
    document.head.appendChild(style);
}

// Toggle del panel de información
window.toggleInfo = function() {
    const panel = document.getElementById('info-panel');
    if (panel.style.display === 'none' || !panel.style.display) {
        panel.style.display = 'block';
    } else {
        panel.style.display = 'none';
    }
};

function updateLoadingStatus(message) {
    document.getElementById('loading-status').textContent = message;
}

async function initGPS() {
    updateLoadingStatus('Solicitando permisos GPS...');

    if (!navigator.geolocation) {
        console.error('GPS no disponible');
        return;
    }

    // Activar componentes GPS en la cámara
    const camera = document.getElementById('camera');
    camera.setAttribute('gps-camera', '');
    camera.setAttribute('rotation-reader', '');

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

    const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
        Math.cos(φ1) * Math.cos(φ2) *
        Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

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

    // Animar botón de info para indicar que el modelo está disponible
    const infoBtn = document.getElementById('info-toggle-btn');
    if (infoBtn) {
        infoBtn.style.animation = 'pulse 1s ease-in-out 3';
    }

    // Reproducir sonido si está configurado
    if (currentConfig.audio?.enabled) {
        const audioGPS = new Audio(`models/${modelId}/${currentConfig.audio.file}`);
        audioGPS.loop = true;
        audioGPS.volume = 0.7;
        audioGPS.play()
            .then(() => console.log('🔊 Sonido GPS reproduciendo'))
            .catch(err => console.log('⚠️ Error reproduciendo sonido GPS:', err.message));
    }
}

async function initMarker() {
    updateLoadingStatus('Inicializando MindAR...');
    console.log('========== INICIO INICIALIZACIÓN MINDAR ==========');

    const scene = document.querySelector('a-scene');
    console.log('Escena A-Frame obtenida:', scene ? 'OK' : 'ERROR');

    // Verificar estado de video/cámara
    const existingVideo = document.querySelector('video');
    console.log('Video existente antes de MindAR:', existingVideo ? 'SÍ' : 'NO');
    if (existingVideo) {
        console.log('Video dimensions:', existingVideo.videoWidth, 'x', existingVideo.videoHeight);
        console.log('Video readyState:', existingVideo.readyState);
    }

    // Limpiar cualquier configuración previa de MindAR
    if (scene.systems['mindar-image-system']) {
        console.log('⚠️ Limpiando sistema MindAR previo...');
        scene.removeAttribute('mindar-image');
        // Esperar un poco para que se limpie
        await new Promise(resolve => setTimeout(resolve, 300));
    }

    // Limpiar entidades previas
    const oldAnchors = scene.querySelectorAll('[mindar-image-target]');
    console.log('Anchors previos encontrados:', oldAnchors.length);
    oldAnchors.forEach(anchor => anchor.remove());

    // Configurar MindAR en la escena
    let mindFile = `models/${modelId}/${currentConfig.marker.file}`;
    if (currentConfig.marker.file.startsWith('http')) {
        mindFile = currentConfig.marker.file;
    }

    console.log('📁 Archivo MindAR:', mindFile);
    console.log('🎨 Modelo 3D:', `models/${modelId}/${currentConfig.model.glb}`);

    try {
        scene.setAttribute('mindar-image', `imageTargetSrc: ${mindFile}; autoStart: true; uiLoading: no; uiScanning: no; uiError: no;`);
        console.log('✓ Atributo mindar-image configurado en escena con autoStart');
    } catch (error) {
        console.error('✗ Error al configurar mindar-image:', error);
    }

    // Esperar a que MindAR se inicialice y arranque
    console.log('⏳ Esperando 2 segundos para que MindAR arranque la cámara...');
    await new Promise(resolve => setTimeout(resolve, 2000));

    // Verificar que el sistema de MindAR esté activo
    if (scene.systems['mindar-image-system']) {
        console.log('✓ Sistema MindAR activo');

        // Intentar iniciar manualmente si no ha arrancado
        try {
            const arSystem = scene.systems['mindar-image-system'];
            if (arSystem && arSystem.start) {
                console.log('🚀 Intentando arrancar MindAR manualmente...');
                await arSystem.start();
                console.log('✓ MindAR arrancado manualmente');
            }
        } catch (e) {
            console.log('ℹ️ No se pudo arrancar manualmente (puede que ya esté arrancado):', e.message);
        }
    } else {
        console.error('✗ Sistema MindAR NO está activo');
    }

    // Verificar video después de inicializar MindAR
    await new Promise(resolve => setTimeout(resolve, 500));
    const videoAfter = document.querySelector('video');
    console.log('Video después de inicializar MindAR:', videoAfter ? 'SÍ' : 'NO');
    if (videoAfter) {
        console.log('Video dimensions:', videoAfter.videoWidth, 'x', videoAfter.videoHeight);
        console.log('Video readyState:', videoAfter.readyState);
        console.log('Video srcObject:', videoAfter.srcObject ? 'OK' : 'NULL');
    } else {
        console.error('✗✗✗ NO SE CREÓ EL ELEMENTO VIDEO - LA CÁMARA NO ARRANCÓ');
        console.log('💡 Mostrando botón de inicio manual...');

        // Mostrar botón de inicio manual
        updateLoadingStatus('Toca "Iniciar AR" para activar la cámara');
        const startBtn = document.getElementById('start-ar-btn');
        if (startBtn) {
            startBtn.style.display = 'block';
        }
        return; // Salir hasta que el usuario presione el botón
    }

    // Crear el target (anchor)
    const anchor = document.createElement('a-entity');
    anchor.setAttribute('mindar-image-target', 'targetIndex: 0');
    console.log('✓ Anchor creado con targetIndex: 0');

    // Crear modelo
    const modelEntity = document.createElement('a-entity');
    const modelPath = `models/${modelId}/${currentConfig.model.glb}`;
    console.log('📦 Cargando modelo desde:', modelPath);

    modelEntity.setAttribute('gltf-model', modelPath);
    modelEntity.setAttribute('scale', currentConfig.model.scale);
    modelEntity.setAttribute('position', currentConfig.model.position);
    modelEntity.setAttribute('rotation', currentConfig.model.rotation);

    // Evento de carga del modelo
    modelEntity.addEventListener('model-loaded', () => {
        console.log('✓✓✓ Modelo 3D cargado exitosamente ✓✓✓');
    });

    modelEntity.addEventListener('model-error', (event) => {
        console.error('✗✗✗ Error cargando modelo 3D:', event.detail);
    });

    anchor.appendChild(modelEntity);

    scene.appendChild(anchor);
    console.log('✓ Entidad anchor agregada a la escena');

    // Guardar referencia al anchor para poder ocultarlo después
    markerAnchor = anchor;

    // Reproducir sonido si está configurado
    if (currentConfig.audio?.enabled) {
        globalAudioInstance = new Audio(`models/${modelId}/${currentConfig.audio.file}`);
        globalAudioInstance.loop = true;
        globalAudioInstance.volume = 0.7;
        console.log('✓ Audio cargado:', currentConfig.audio.file);

        // Mostrar botón de control de audio
        document.getElementById('audio-control-btn').style.display = 'block';
    }

    // Eventos
    anchor.addEventListener('targetFound', () => {
        console.log('🎯🎯🎯 TARGET ENCONTRADO 🎯🎯🎯');
        document.getElementById('loading').style.display = 'none';

        // Desbloquear animal en el diario de campo
        if (modelId) {
            unlockAnimal(modelId);
        }

        // Si el modelo está capturado, no mostrar el del marcador
        if (isModelCaptured) {
            console.log('📦 Modelo ya capturado, manteniendo oculto el marcador');
            anchor.object3D.visible = false;
            return;
        }

        console.log('👁️ Mostrando modelo en marcador');

        // Reproducir sonido solo si no está capturado
        if (globalAudioInstance && !isAudioPlaying) {
            globalAudioInstance.play()
                .then(() => {
                    console.log('🔊 Sonido reproduciendo');
                    isAudioPlaying = true;
                    updateAudioButton();
                })
                .catch(err => {
                    console.log('⚠️ No se pudo reproducir el sonido automáticamente:', err.message);
                    console.log('💡 Usa el botón de audio para reproducirlo manualmente');
                });
        }
    });

    anchor.addEventListener('targetLost', () => {
        console.log('📍 Target perdido');

        // Solo pausar sonido si el modelo NO está capturado
        if (!isModelCaptured && globalAudioInstance && isAudioPlaying) {
            globalAudioInstance.pause();
            isAudioPlaying = false;
            updateAudioButton();
            console.log('🔇 Sonido pausado');
        }
    });

    // Fallback timeout más corto
    setTimeout(() => {
        console.log('⏰ Timeout de fallback: ocultando loading después de 3 segundos');
        document.getElementById('loading').style.display = 'none';
    }, 3000);

    console.log('========== FIN INICIALIZACIÓN MINDAR ==========');
}

// Función para continuar el setup después del inicio manual
async function continueMarkerSetup() {
    console.log('Continuando setup del marcador...');
    const scene = document.querySelector('a-scene');

    // Verificar video
    await new Promise(resolve => setTimeout(resolve, 500));
    const video = document.querySelector('video');
    console.log('Video después de inicio manual:', video ? 'SÍ' : 'NO');

    if (!video) {
        console.error('El video sigue sin crearse');
        alert('Error: No se pudo activar la cámara');
        return;
    }

    // Crear el target (anchor) si no existe
    let anchor = scene.querySelector('[mindar-image-target]');
    if (anchor) {
        console.log('Anchor ya existe, reutilizando...');
        document.getElementById('loading').style.display = 'none';
        return;
    }

    anchor = document.createElement('a-entity');
    anchor.setAttribute('mindar-image-target', 'targetIndex: 0');
    console.log('✓ Anchor creado con targetIndex: 0');

    // Crear modelo
    const modelEntity = document.createElement('a-entity');
    const modelPath = `models/${modelId}/${currentConfig.model.glb}`;
    console.log('📦 Cargando modelo desde:', modelPath);

    modelEntity.setAttribute('gltf-model', modelPath);
    modelEntity.setAttribute('scale', currentConfig.model.scale);
    modelEntity.setAttribute('position', currentConfig.model.position);
    modelEntity.setAttribute('rotation', currentConfig.model.rotation);

    // Evento de carga del modelo
    modelEntity.addEventListener('model-loaded', () => {
        console.log('✓✓✓ Modelo 3D cargado exitosamente ✓✓✓');
    });

    modelEntity.addEventListener('model-error', (event) => {
        console.error('✗✗✗ Error cargando modelo 3D:', event.detail);
    });

    anchor.appendChild(modelEntity);

    scene.appendChild(anchor);
    console.log('✓ Entidad anchor agregada a la escena');

    // Guardar referencia al anchor para poder ocultarlo después
    markerAnchor = anchor;

    // Eventos
    anchor.addEventListener('targetFound', () => {
        console.log('🎯🎯🎯 TARGET ENCONTRADO 🎯🎯🎯');
        document.getElementById('loading').style.display = 'none';

        // Desbloquear animal en el diario de campo
        if (modelId) {
            unlockAnimal(modelId);
        }

        // Si el modelo está capturado, no mostrar el del marcador
        if (isModelCaptured) {
            console.log('📦 Modelo ya capturado, manteniendo oculto el marcador');
            anchor.object3D.visible = false;
            return;
        }

        console.log('👁️ Mostrando modelo en marcador');

        // Reproducir sonido solo si no está capturado
        if (globalAudioInstance && !isAudioPlaying) {
            globalAudioInstance.play()
                .then(() => {
                    console.log('🔊 Sonido reproduciendo (manual)');
                    isAudioPlaying = true;
                    updateAudioButton();
                })
                .catch(err => {
                    console.log('⚠️ No se pudo reproducir el sonido automáticamente:', err.message);
                    console.log('💡 Usa el botón de audio para reproducirlo manualmente');
                });
        }
    });

    anchor.addEventListener('targetLost', () => {
        console.log('📍 Target perdido');

        // Solo pausar sonido si el modelo NO está capturado
        if (!isModelCaptured && globalAudioInstance && isAudioPlaying) {
            globalAudioInstance.pause();
            isAudioPlaying = false;
            updateAudioButton();
            console.log('🔇 Sonido pausado (manual)');
        }
    });

    // Timeout para ocultar loading
    setTimeout(() => {
        console.log('⏰ Ocultando loading');
        document.getElementById('loading').style.display = 'none';
    }, 3000);
}

// Variables para control de rotación táctil y gestos
let isRotating = false;
let isPanning = false;
let previousMousePosition = { x: 0, y: 0 };
let currentRotation = { x: 0, y: 0 };
let initialPinchDistance = 0;
let currentModelScale = 1;
let currentModelPosition = { x: 0, y: 0, z: -15 };
let lastTouchCount = 0;
let gyroControlsEnabled = false;
let captureDeviceOrientation = null; // Orientación capturada al momento de cazar

// Calcular distancia entre dos toques (para pinch)
function getTouchDistance(touch1, touch2) {
    const dx = touch1.clientX - touch2.clientX;
    const dy = touch1.clientY - touch2.clientY;
    return Math.sqrt(dx * dx + dy * dy);
}

// Calcular centro entre dos toques
function getTouchCenter(touch1, touch2) {
    return {
        x: (touch1.clientX + touch2.clientX) / 2,
        y: (touch1.clientY + touch2.clientY) / 2
    };
}

async function requestGyroPermission() {
    // Solo iOS 13+ requiere permiso explícito
    if (typeof DeviceOrientationEvent !== 'undefined' &&
        typeof DeviceOrientationEvent.requestPermission === 'function') {
        try {
            const permission = await DeviceOrientationEvent.requestPermission();
            if (permission === 'granted') {
                console.log('✅ Permiso de giroscopio concedido');
                return true;
            } else {
                console.log('❌ Permiso de giroscopio denegado');
                return false;
            }
        } catch (error) {
            console.error('Error solicitando permiso de giroscopio:', error);
            return false;
        }
    } else {
        // Android o navegadores que no requieren permiso
        console.log('✅ Giroscopio disponible sin permiso');
        return true;
    }
}

async function requestAllPermissions() {
    console.log('🔐 Solicitando permisos de sensores...');

    let permissionsGranted = {
        orientation: false,
        motion: false,
        camera: false
    };

    // Solicitar permiso para DeviceOrientation (giroscopio)
    if (typeof DeviceOrientationEvent !== 'undefined' &&
        typeof DeviceOrientationEvent.requestPermission === 'function') {
        try {
            const orientationPermission = await DeviceOrientationEvent.requestPermission();
            if (orientationPermission === 'granted') {
                console.log('✅ Permiso de DeviceOrientation (giroscopio) concedido');
                permissionsGranted.orientation = true;
            } else {
                console.log('⚠️ Permiso de DeviceOrientation denegado');
            }
        } catch (error) {
            console.error('Error solicitando DeviceOrientation:', error);
        }
    } else {
        // Navegadores que no requieren permiso
        permissionsGranted.orientation = true;
    }

    // Solicitar permiso para DeviceMotion (acelerómetro)
    if (typeof DeviceMotionEvent !== 'undefined' &&
        typeof DeviceMotionEvent.requestPermission === 'function') {
        try {
            const motionPermission = await DeviceMotionEvent.requestPermission();
            if (motionPermission === 'granted') {
                console.log('✅ Permiso de DeviceMotion (acelerómetro) concedido');
                permissionsGranted.motion = true;
            } else {
                console.log('⚠️ Permiso de DeviceMotion denegado');
            }
        } catch (error) {
            console.error('Error solicitando DeviceMotion:', error);
        }
    } else {
        // Navegadores que no requieren permiso
        permissionsGranted.motion = true;
    }

    // Solicitar permiso de cámara (MindAR lo solicitará después si es necesario)
    try {
        const stream = await navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: 'environment',
                width: { ideal: 1920 },
                height: { ideal: 1080 }
            }
        });
        console.log('✅ Permiso de cámara concedido');
        permissionsGranted.camera = true;
        stream.getTracks().forEach(track => track.stop());
    } catch (error) {
        console.warn('⚠️ Permiso de cámara no concedido ahora, MindAR lo solicitará después');
        // No lanzar error, continuar de todos modos
    }

    console.log('🔐 Permisos procesados:', permissionsGranted);

    // Si al menos los permisos de sensores fueron concedidos, continuar
    if (!permissionsGranted.orientation && !permissionsGranted.motion) {
        throw new Error('Se necesitan permisos de sensores para la experiencia completa');
    }
}

function setupModelRotationControls(entity) {
    const scene = document.querySelector('a-scene');
    const canvas = scene.canvas;
    const camera = document.getElementById('camera');

    console.log('🎮 Controles avanzados 3D activados');

    // Variables para pan con dos dedos
    let previousPanPosition = { x: 0, y: 0 };

    // Funciones de control
    const onTouchStart = (e) => {
        const touchCount = e.touches.length;
        lastTouchCount = touchCount;

        if (touchCount === 1) {
            // Un dedo: rotar
            isRotating = true;
            isPanning = false;
            previousMousePosition = {
                x: e.touches[0].clientX,
                y: e.touches[0].clientY
            };
            console.log('👆 Rotación (1 dedo)');
        } else if (touchCount === 2) {
            // Dos dedos: pinch para zoom y pan para mover
            isRotating = false;
            isPanning = true;
            initialPinchDistance = getTouchDistance(e.touches[0], e.touches[1]);
            const center = getTouchCenter(e.touches[0], e.touches[1]);
            previousPanPosition = center;
            console.log('✌️ Pinch/Pan (2 dedos)');
        }
    };

    const onTouchMove = (e) => {
        e.preventDefault();
        const touchCount = e.touches.length;

        if (touchCount === 1 && isRotating) {
            // Rotación con un dedo
            const deltaX = e.touches[0].clientX - previousMousePosition.x;
            const deltaY = e.touches[0].clientY - previousMousePosition.y;

            currentRotation.y += deltaX * 0.5;
            currentRotation.x += deltaY * 0.5;

            entity.setAttribute('rotation', `${currentRotation.x} ${currentRotation.y} 0`);

            previousMousePosition = {
                x: e.touches[0].clientX,
                y: e.touches[0].clientY
            };
        } else if (touchCount === 2 && isPanning) {
            // Pinch to zoom
            const currentDistance = getTouchDistance(e.touches[0], e.touches[1]);
            const scaleDelta = (currentDistance - initialPinchDistance) * 0.01;
            currentModelScale = Math.max(0.5, Math.min(3, currentModelScale + scaleDelta));

            const baseScale = currentConfig.model.scale || '1 1 1';
            const scaleParts = baseScale.split(' ').map(v => parseFloat(v) * 8 * currentModelScale);
            entity.setAttribute('scale', scaleParts.join(' '));

            initialPinchDistance = currentDistance;

            // Pan para mover modelo (XY)
            const center = getTouchCenter(e.touches[0], e.touches[1]);
            const deltaX = (center.x - previousPanPosition.x) * 0.05;
            const deltaY = (center.y - previousPanPosition.y) * 0.05;

            currentModelPosition.x += deltaX;
            currentModelPosition.y -= deltaY; // Invertir Y para que sea natural

            entity.setAttribute('position',
                `${currentModelPosition.x} ${currentModelPosition.y} ${currentModelPosition.z}`);

            previousPanPosition = center;
        }
    };

    const onTouchEnd = (e) => {
        if (e.touches.length === 0) {
            isRotating = false;
            isPanning = false;
            console.log('👆 Gesto finalizado');
        }
    };

    // Eventos de mouse para escritorio (rotación simple)
    const onMouseDown = (e) => {
        isRotating = true;
        previousMousePosition = { x: e.clientX, y: e.clientY };
    };

    const onMouseMove = (e) => {
        if (!isRotating) return;

        const deltaX = e.clientX - previousMousePosition.x;
        const deltaY = e.clientY - previousMousePosition.y;

        currentRotation.y += deltaX * 0.5;
        currentRotation.x += deltaY * 0.5;

        entity.setAttribute('rotation', `${currentRotation.x} ${currentRotation.y} 0`);

        previousMousePosition = { x: e.clientX, y: e.clientY };
    };

    const onMouseUp = () => {
        isRotating = false;
    };

    // Eventos táctiles (móvil)
    canvas.addEventListener('touchstart', onTouchStart, { passive: false });
    canvas.addEventListener('touchmove', onTouchMove, { passive: false });
    canvas.addEventListener('touchend', onTouchEnd);

    // Eventos de mouse (escritorio)
    canvas.addEventListener('mousedown', onMouseDown);
    canvas.addEventListener('mousemove', onMouseMove);
    canvas.addEventListener('mouseup', onMouseUp);
    canvas.addEventListener('mouseleave', onMouseUp);

    // Activar giroscopio para la cámara con orientación de referencia
    requestGyroPermission().then(granted => {
        if (granted) {
            // Habilitar look-controls en la cámara para seguir el giroscopio
            camera.setAttribute('look-controls', 'enabled: true; magicWindowTrackingEnabled: true; touchEnabled: false');
            gyroControlsEnabled = true;
            console.log('🔄 Controles de giroscopio activados - mueve tu dispositivo');

            // Si hay orientación capturada, usarla como referencia
            if (captureDeviceOrientation) {
                console.log('📱 Usando orientación de captura como referencia:', captureDeviceOrientation);

                // Escuchar eventos de orientación para ajustar el modelo relativo a la posición de captura
                const orientationUpdateHandler = (event) => {
                    if (!gyroControlsEnabled || !isModelCaptured) {
                        window.removeEventListener('deviceorientation', orientationUpdateHandler);
                        return;
                    }

                    // Calcular la diferencia de orientación desde la captura
                    const deltaAlpha = (event.alpha || 0) - captureDeviceOrientation.alpha;
                    const deltaBeta = (event.beta || 0) - captureDeviceOrientation.beta;
                    const deltaGamma = (event.gamma || 0) - captureDeviceOrientation.gamma;

                    // Aplicar las deltas como rotación adicional del modelo
                    // Beta = rotación X, Gamma = rotación Y, Alpha = rotación Z
                    const rotX = currentRotation.x + deltaBeta * 0.5;
                    const rotY = currentRotation.y + deltaAlpha * 0.5;
                    const rotZ = deltaGamma * 0.3;

                    entity.setAttribute('rotation', `${rotX} ${rotY} ${rotZ}`);
                };

                window.addEventListener('deviceorientation', orientationUpdateHandler);
            }
        } else {
            console.log('ℹ️ Modo 3D sin giroscopio - usa gestos táctiles');
        }
    });

    entity.setAttribute('data-rotation-controls', 'true');
}

// Función legacy - ya no se usa, todo se maneja desde captureModel()
window.enable3DMode = function () {
    console.log('⚠️ enable3DMode es una función legacy y ya no se usa');
    console.log('💡 Usa el botón de captura de modelo en su lugar');
};

// Función para capturar la orientación actual del dispositivo
function captureCurrentDeviceOrientation() {
    return new Promise((resolve) => {
        const orientationHandler = (event) => {
            const orientation = {
                alpha: event.alpha || 0,  // Rotación Z (0-360)
                beta: event.beta || 0,    // Rotación X (-180 a 180)
                gamma: event.gamma || 0,  // Rotación Y (-90 a 90)
                timestamp: Date.now()
            };
            console.log('📱 Orientación capturada:', orientation);
            window.removeEventListener('deviceorientation', orientationHandler);
            resolve(orientation);
        };

        window.addEventListener('deviceorientation', orientationHandler);

        // Timeout de seguridad si no hay eventos de orientación
        setTimeout(() => {
            window.removeEventListener('deviceorientation', orientationHandler);
            resolve(null);
        }, 1000);
    });
}

// ============================================
// CAPTURA DE MODELO DEL MARCADOR
// ============================================

window.captureModel = async function() {
    console.log('🦌 Botón de captura de modelo presionado');

    const captureBtn = document.getElementById('capture-model-btn');
    const flash = document.getElementById('capture-flash');
    const camera = document.getElementById('camera');

    // Verificar que el sistema esté listo
    if (!currentConfig || !markerAnchor) {
        console.warn('⚠️ Sistema AR no está listo aún');
        return;
    }

    if (!isModelCaptured) {
        // ===== CAPTURAR MODELO =====
        console.log('📦 Capturando modelo del marcador...');

        // Capturar orientación del dispositivo al momento de "cazar"
        captureDeviceOrientation = await captureCurrentDeviceOrientation();
        if (captureDeviceOrientation) {
            console.log('🎯 Orientación del dispositivo capturada para referencia 3D');
        } else {
            console.log('⚠️ No se pudo capturar orientación del dispositivo');
        }

        // Efecto de flash
        flash.classList.add('flash');
        setTimeout(() => flash.classList.remove('flash'), 150);

        // Ocultar el modelo del marcador completamente
        if (markerAnchor && markerAnchor.object3D) {
            markerAnchor.object3D.visible = false;
            console.log('👻 Modelo del marcador ocultado');
        }

        // Marcar como capturado ANTES de crear el modelo 3D
        isModelCaptured = true;

        // Crear el modelo 3D capturado
        createCaptured3DModel();

        // Cambiar botón a estado "capturado"
        captureBtn.classList.add('captured');
        captureBtn.title = 'Liberar modelo';

        console.log('✅ Modelo capturado exitosamente');

    } else {
        // ===== LIBERAR MODELO =====
        console.log('🔓 Liberando modelo al marcador...');

        // Eliminar el modelo 3D capturado
        if (captured3DModel) {
            captured3DModel.remove();
            captured3DModel = null;
            console.log('🗑️ Modelo 3D capturado eliminado');
        }

        // Limpiar cualquier otro modelo 3D que pueda existir
        const existing3DModels = camera.querySelectorAll('[data-3d-preview]');
        existing3DModels.forEach(el => {
            el.remove();
            console.log('🗑️ Modelo 3D extra eliminado');
        });

        // Desactivar controles de giroscopio si estaban activos
        if (gyroControlsEnabled) {
            camera.setAttribute('look-controls', 'enabled: false');
            gyroControlsEnabled = false;
            console.log('🔄 Controles de giroscopio desactivados');
        }

        // Ocultar hint de controles
        const controlsHint = document.getElementById('controls-hint');
        if (controlsHint) {
            controlsHint.style.display = 'none';
        }

        // Resetear variables
        currentRotation = { x: 0, y: 0 };
        currentModelScale = 1;
        currentModelPosition = { x: 0, y: 0, z: -15 };
        isRotating = false;
        isPanning = false;
        captureDeviceOrientation = null; // Resetear orientación capturada

        // Marcar como NO capturado
        isModelCaptured = false;

        // Mostrar el modelo del marcador nuevamente
        if (markerAnchor && markerAnchor.object3D) {
            markerAnchor.object3D.visible = true;
            console.log('👁️ Modelo del marcador visible nuevamente');
        }

        // Pausar audio si está reproduciendo
        if (globalAudioInstance && isAudioPlaying) {
            globalAudioInstance.pause();
            isAudioPlaying = false;
            updateAudioButton();
            console.log('🔇 Audio pausado');
        }

        // Restaurar botón
        captureBtn.classList.remove('captured');
        captureBtn.title = 'Capturar modelo';

        console.log('✅ Modelo liberado de vuelta al marcador');
    }
};

// Función para crear el modelo 3D capturado
function createCaptured3DModel() {
    const camera = document.getElementById('camera');
    const scene = document.querySelector('a-scene');

    if (!camera || !scene) {
        console.error('❌ Cámara o escena no encontrada');
        return;
    }

    console.log('🎨 Creando modelo 3D capturado...');

    // Asegurar que el video de la cámara esté visible
    const video = document.querySelector('video');
    if (video) {
        video.style.display = 'block';
        video.style.zIndex = '-2';
    }

    // Asegurar que el canvas sea transparente
    const canvas = scene.canvas;
    if (canvas) {
        canvas.style.background = 'transparent';
    }

    // Asegurar que la escena no tenga fondo
    scene.removeAttribute('background');

    let entity;

    if (currentConfig.model.type === 'primitive') {
        // Modelo primitivo
        entity = document.createElement(currentConfig.model.primitive || 'a-box');
        entity.setAttribute('color', currentConfig.model.color || 'red');
    } else {
        // Modelo 3D (GLB/GLTF)
        entity = document.createElement('a-entity');
        const modelPath = `models/${modelId}/${currentConfig.model.glb}`;
        entity.setAttribute('gltf-model', modelPath);

        if (currentConfig.model.glb.includes('glb') || currentConfig.model.glb.includes('gltf')) {
            entity.setAttribute('animation-mixer', '');
        }
    }

    // Marcar como modelo capturado
    entity.setAttribute('data-3d-preview', 'true');

    // Configurar escala y posición inicial
    const baseScale = currentConfig.model.scale || '1 1 1';
    const scaleParts = baseScale.split(' ').map(v => parseFloat(v) * 8);
    const scale = scaleParts.join(' ');

    entity.setAttribute('scale', scale);
    entity.setAttribute('position', '0 0 -15');
    entity.setAttribute('rotation', '0 0 0');

    // Resetear variables de control
    currentRotation = { x: 0, y: 0 };
    currentModelScale = 1;
    currentModelPosition = { x: 0, y: 0, z: -15 };

    // Agregar a la cámara
    camera.appendChild(entity);
    captured3DModel = entity;

    // Configurar controles
    setupModelRotationControls(entity);

    // Mostrar hint de controles
    const controlsHint = document.getElementById('controls-hint');
    if (controlsHint) {
        controlsHint.style.display = 'block';
        setTimeout(() => {
            controlsHint.style.opacity = '0';
            controlsHint.style.transition = 'opacity 0.5s';
            setTimeout(() => {
                controlsHint.style.display = 'none';
                controlsHint.style.opacity = '1';
            }, 500);
        }, 5000);
    }

    // Reproducir audio si está configurado
    if (currentConfig.audio?.enabled && globalAudioInstance && !isAudioPlaying) {
        globalAudioInstance.play()
            .then(() => {
                console.log('🔊 Sonido reproduciendo en modo capturado');
                isAudioPlaying = true;
                updateAudioButton();
            })
            .catch(err => {
                console.log('⚠️ Error reproduciendo sonido:', err.message);
            });
    }

    console.log('✅ Modelo 3D capturado creado exitosamente');
}

window.testCamera = async function () {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ video: true });
        alert('Éxito: Cámara detectada y accesible.\nTracks: ' + stream.getVideoTracks().length);
        // Detener el stream de prueba para no bloquear
        stream.getTracks().forEach(track => track.stop());
    } catch (err) {
        console.error('Error de cámara:', err);
        alert('Error de cámara: ' + err.name + ' - ' + err.message);
    }
};

// ============================================
// FUNCIONALIDAD DE CAPTURA DE FOTOS
// ============================================

let capturedPhotos = [];
let currentPhotoData = null;
let currentPhotoIndex = -1;

// Cargar fotos guardadas del localStorage
function loadSavedPhotos() {
    const saved = localStorage.getItem('faunar_photos');
    if (saved) {
        capturedPhotos = JSON.parse(saved);
        updateGalleryButton();
    }
}

// Guardar fotos en localStorage
function savePhotos() {
    localStorage.setItem('faunar_photos', JSON.stringify(capturedPhotos));
    updateGalleryButton();
}

// Capturar foto de la escena AR
window.capturePhoto = function() {
    const scene = document.querySelector('a-scene');

    if (!scene) {
        alert('Escena AR no disponible');
        return;
    }

    // Efecto de flash
    const flash = document.getElementById('capture-flash');
    flash.classList.add('flash');
    setTimeout(() => flash.classList.remove('flash'), 150);

    // Sonido de cámara (opcional)
    const clickSound = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBjGH0fPTgjMGHm7A7+OZURE');
    clickSound.volume = 0.3;
    clickSound.play().catch(() => {});

    requestAnimationFrame(() => {
        // Buscar el video de la cámara y el canvas de WebGL
        const video = document.querySelector('video');
        const glCanvas = scene.canvas || document.querySelector('canvas');

        if (!video || !glCanvas) {
            alert('No se pudo capturar: video o canvas no disponible');
            return;
        }

        // Crear canvas temporal para combinar video + modelo 3D
        const tempCanvas = document.createElement('canvas');
        tempCanvas.width = video.videoWidth || glCanvas.width;
        tempCanvas.height = video.videoHeight || glCanvas.height;
        const ctx = tempCanvas.getContext('2d');

        // Dibujar el video de la cámara primero (fondo)
        ctx.drawImage(video, 0, 0, tempCanvas.width, tempCanvas.height);

        // Dibujar el canvas de WebGL encima (modelo 3D)
        ctx.drawImage(glCanvas, 0, 0, tempCanvas.width, tempCanvas.height);

        // Convertir a imagen
        const dataURL = tempCanvas.toDataURL('image/png');

        // Guardar foto con metadata
        const photo = {
            id: Date.now(),
            data: dataURL,
            timestamp: new Date().toISOString(),
            modelId: modelId,
            modelName: currentConfig?.name || 'Desconocido'
        };

        capturedPhotos.unshift(photo);
        savePhotos();

        // Mostrar preview
        currentPhotoData = photo;
        showPhotoPreview(photo.data);
    });
};

// Mostrar preview de la foto capturada
function showPhotoPreview(imageData, photoIndex = -1) {
    const preview = document.getElementById('photo-preview');
    const previewImage = document.getElementById('preview-image');
    const counter = document.getElementById('photo-counter');
    const prevBtn = document.getElementById('prev-photo-btn');
    const nextBtn = document.getElementById('next-photo-btn');

    previewImage.src = imageData;
    currentPhotoIndex = photoIndex;

    // Actualizar contador y botones de navegación
    if (photoIndex >= 0 && capturedPhotos.length > 0) {
        counter.textContent = `${photoIndex + 1} / ${capturedPhotos.length}`;
        counter.style.display = 'block';

        // Habilitar/deshabilitar botones según la posición
        prevBtn.disabled = photoIndex === 0;
        nextBtn.disabled = photoIndex === capturedPhotos.length - 1;
        prevBtn.style.display = 'block';
        nextBtn.style.display = 'block';
    } else {
        // Foto recién capturada, ocultar navegación
        counter.style.display = 'none';
        prevBtn.style.display = 'none';
        nextBtn.style.display = 'none';
    }

    preview.style.display = 'flex';
}

// Navegar entre fotos en el preview
window.navigatePhoto = function(direction) {
    if (currentPhotoIndex < 0 || capturedPhotos.length === 0) return;

    const newIndex = currentPhotoIndex + direction;

    if (newIndex >= 0 && newIndex < capturedPhotos.length) {
        currentPhotoIndex = newIndex;
        currentPhotoData = capturedPhotos[newIndex];
        showPhotoPreview(currentPhotoData.data, newIndex);
    }
};

// Cerrar preview
window.closePreview = function() {
    document.getElementById('photo-preview').style.display = 'none';
    currentPhotoData = null;
    currentPhotoIndex = -1;
};

// Descargar foto
window.downloadPhoto = function() {
    if (!currentPhotoData) return;

    const link = document.createElement('a');
    const fileName = `FaunAR_${currentConfig?.name || 'foto'}_${new Date().getTime()}.png`;

    link.download = fileName;
    link.href = currentPhotoData.data;
    link.click();

    // Mostrar confirmación
    const downloadBtn = document.getElementById('download-btn');
    const originalText = downloadBtn.textContent;
    downloadBtn.textContent = '✓ Descargada';
    setTimeout(() => {
        downloadBtn.textContent = originalText;
    }, 2000);
};

// Compartir foto (Web Share API)
window.sharePhoto = async function() {
    if (!currentPhotoData) return;

    try {
        // Convertir data URL a Blob
        const response = await fetch(currentPhotoData.data);
        const blob = await response.blob();
        const file = new File([blob], `FaunAR_${currentConfig?.name}.png`, { type: 'image/png' });

        // Verificar si Web Share API está disponible
        if (navigator.share && navigator.canShare && navigator.canShare({ files: [file] })) {
            await navigator.share({
                files: [file],
                title: `Foto AR - ${currentConfig?.name}`,
                text: `¡Mira esta foto AR de ${currentConfig?.name} capturada con FaunAR!`
            });
        } else {
            // Fallback: copiar al portapapeles si está disponible
            if (navigator.clipboard && navigator.clipboard.write) {
                await navigator.clipboard.write([
                    new ClipboardItem({
                        'image/png': blob
                    })
                ]);
                alert('Foto copiada al portapapeles');
            } else {
                alert('Función de compartir no disponible. Usa el botón de descargar.');
            }
        }
    } catch (error) {
        console.error('Error compartiendo:', error);
        alert('No se pudo compartir la foto. Intenta descargarla.');
    }
};

// Abrir galería
window.openGallery = function() {
    const gallery = document.getElementById('photo-gallery');
    const grid = document.getElementById('photo-gallery-grid');

    // Limpiar grid
    grid.innerHTML = '';

    if (capturedPhotos.length === 0) {
        grid.innerHTML = '<p style="color: white; text-align: center; padding: 40px; grid-column: 1/-1;">No hay fotos capturadas aún. ¡Captura tu primera foto AR!</p>';
    } else {
        // Agregar fotos al grid
        capturedPhotos.forEach((photo, index) => {
            const item = document.createElement('div');
            item.className = 'gallery-photo-item';
            item.innerHTML = `<img src="${photo.data}" alt="Foto ${index + 1}">`;
            item.onclick = () => {
                currentPhotoData = photo;
                closeGallery(); // Cerrar galería primero
                showPhotoPreview(photo.data, index);
            };
            grid.appendChild(item);
        });
    }

    gallery.style.display = 'flex';
};

// Cerrar galería
window.closeGallery = function() {
    document.getElementById('photo-gallery').style.display = 'none';
};

// Actualizar botón de galería con última foto
function updateGalleryButton() {
    const galleryBtn = document.getElementById('gallery-btn');

    if (capturedPhotos.length > 0) {
        galleryBtn.style.backgroundImage = `url('${capturedPhotos[0].data}')`;
        galleryBtn.style.backgroundSize = 'cover';
        galleryBtn.style.backgroundPosition = 'center';
    }
}

// ============================================
// CONTROL DE AUDIO
// ============================================

// Toggle de audio
window.toggleAudio = function() {
    if (!globalAudioInstance) {
        console.log('No hay audio configurado');
        return;
    }

    if (isAudioPlaying) {
        globalAudioInstance.pause();
        isAudioPlaying = false;
        console.log('🔇 Audio pausado manualmente');
    } else {
        globalAudioInstance.play()
            .then(() => {
                isAudioPlaying = true;
                console.log('🔊 Audio reproduciendo manualmente');
            })
            .catch(err => {
                console.error('Error reproduciendo audio:', err);
                alert('No se pudo reproducir el audio');
            });
    }

    updateAudioButton();
};

// Actualizar ícono del botón de audio
function updateAudioButton() {
    const audioBtn = document.getElementById('audio-control-btn');
    if (audioBtn) {
        audioBtn.textContent = isAudioPlaying ? '🔊' : '🔇';
        audioBtn.style.background = isAudioPlaying ? 'rgba(33, 150, 243, 0.8)' : 'rgba(158, 158, 158, 0.8)';
    }
}

// ============================================
// MEJORA DE INICIALIZACIÓN DE CÁMARA
// ============================================

// Mejorar solicitud de permisos de cámara
async function ensureCameraPermissions() {
    try {
        // Intentar obtener permisos de cámara de forma explícita
        const stream = await navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: 'environment',
                width: { ideal: 1920 },
                height: { ideal: 1080 }
            }
        });

        console.log('Permisos de cámara concedidos');

        // Detener el stream de prueba
        stream.getTracks().forEach(track => track.stop());

        return true;
    } catch (error) {
        console.error('Error solicitando permisos de cámara:', error);
        alert('Se necesitan permisos de cámara para usar la experiencia AR. Por favor, autoriza el acceso a la cámara.');
        return false;
    }
}

// ============================================
// DIARIO DE CAMPO (FIELD JOURNAL)
// ============================================

// Lista completa de animales - se carga dinámicamente desde los configs
let ALL_ANIMALS = [];

// Cargar todos los animales desde los configs
async function loadAllAnimals() {
    if (ALL_ANIMALS.length > 0) {
        return ALL_ANIMALS; // Ya cargados
    }

    const configs = await ConfigLoader.loadAll();
    ALL_ANIMALS = configs.map(config => ({
        id: config.id,
        name: config.name,
        scientificName: config.scientificName,
        icon: config.icon || '❓',
        silhouette: config.silhouette || null
    }));

    console.log('📋 Animales cargados para diario:', ALL_ANIMALS);
    return ALL_ANIMALS;
}

// Cargar animales descubiertos del localStorage
function loadDiscoveredAnimals() {
    const saved = localStorage.getItem('faunar_discovered_animals');
    if (saved) {
        return JSON.parse(saved);
    }
    return [];
}

// Guardar animales descubiertos
function saveDiscoveredAnimals(discovered) {
    localStorage.setItem('faunar_discovered_animals', JSON.stringify(discovered));
}

// Desbloquear un animal (llamar cuando se detecta el marcador)
function unlockAnimal(animalId) {
    let discovered = loadDiscoveredAnimals();

    // Si ya está descubierto, no hacer nada
    if (discovered.includes(animalId)) {
        console.log(`Animal ${animalId} ya estaba descubierto`);
        return false;
    }

    // Añadir a descubiertos
    discovered.push(animalId);
    saveDiscoveredAnimals(discovered);

    console.log(`✅ ¡Nuevo animal descubierto: ${animalId}!`);

    // Actualizar badge
    updateFieldJournalBadge();

    // Mostrar notificación
    showAnimalUnlockedNotification(animalId);

    return true;
}

// Actualizar badge del botón del diario
function updateFieldJournalBadge() {
    const badge = document.getElementById('field-journal-badge');
    if (badge) {
        const discovered = loadDiscoveredAnimals();
        badge.textContent = discovered.length;

        // Animar badge cuando cambia
        badge.style.transform = 'scale(1.3)';
        setTimeout(() => {
            badge.style.transform = 'scale(1)';
        }, 200);
    }
}

// Mostrar notificación de animal desbloqueado
async function showAnimalUnlockedNotification(animalId) {
    await loadAllAnimals();
    const animal = ALL_ANIMALS.find(a => a.id === animalId);
    if (!animal) return;

    // Crear elemento de notificación
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 80px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(139, 69, 19, 0.95);
        color: white;
        padding: 15px 25px;
        border-radius: 12px;
        z-index: 3000;
        backdrop-filter: blur(10px);
        border: 2px solid rgba(255, 255, 255, 0.3);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
        font-size: 16px;
        text-align: center;
        animation: slideDown 0.5s ease-out;
    `;

    notification.innerHTML = `
        <div style="font-size: 32px; margin-bottom: 5px;">${animal.icon}</div>
        <div style="font-weight: bold; margin-bottom: 3px;">¡Nuevo descubrimiento!</div>
        <div style="font-size: 14px; opacity: 0.9;">${animal.name}</div>
    `;

    document.body.appendChild(notification);

    // Añadir animación
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateX(-50%) translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
        }
    `;
    document.head.appendChild(style);

    // Remover después de 3 segundos
    setTimeout(() => {
        notification.style.transition = 'opacity 0.5s';
        notification.style.opacity = '0';
        setTimeout(() => {
            notification.remove();
            style.remove();
        }, 500);
    }, 3000);
}

// Abrir diario de campo
window.openFieldJournal = async function() {
    const journal = document.getElementById('field-journal');
    journal.style.display = 'flex';

    // Renderizar contenido
    await renderFieldJournal();
};

// Cerrar diario de campo
window.closeFieldJournal = function() {
    document.getElementById('field-journal').style.display = 'none';
};

// Renderizar contenido del diario
async function renderFieldJournal() {
    await loadAllAnimals();

    const discovered = loadDiscoveredAnimals();
    const progressDiv = document.getElementById('animals-progress');
    const gridDiv = document.getElementById('animals-grid');

    // Calcular progreso
    const totalAnimals = ALL_ANIMALS.length;
    const discoveredCount = discovered.length;
    const percentage = Math.round((discoveredCount / totalAnimals) * 100);

    // Renderizar barra de progreso
    progressDiv.innerHTML = `
        <div style="margin-bottom: 10px;">
            <strong>${discoveredCount}</strong> de <strong>${totalAnimals}</strong> especies descubiertas
        </div>
        <div class="progress-bar">
            <div class="progress-fill" style="width: ${percentage}%;">
                ${percentage > 15 ? percentage + '%' : ''}
            </div>
        </div>
    `;

    // Renderizar grid de animales
    gridDiv.innerHTML = '';

    ALL_ANIMALS.forEach(animal => {
        const isUnlocked = discovered.includes(animal.id);

        const item = document.createElement('div');
        item.className = `animal-item ${isUnlocked ? 'unlocked' : 'locked'}`;

        // Usar silueta SVG si está disponible, sino emoji
        let iconHTML;
        if (animal.silhouette) {
            const silhouettePath = `models/${animal.id}/${animal.silhouette}`;
            iconHTML = `<img src="${silhouettePath}" class="animal-silhouette" alt="${animal.name}" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                        <div class="animal-icon" style="display: none;">${animal.icon}</div>`;
        } else {
            iconHTML = `<div class="animal-icon">${animal.icon}</div>`;
        }

        item.innerHTML = `
            ${isUnlocked ? '' : '<div class="lock-icon">🔒</div>'}
            ${iconHTML}
            <div class="animal-name">${isUnlocked ? animal.name : '???'}</div>
        `;

        // Click solo si está desbloqueado
        if (isUnlocked) {
            item.onclick = () => showAnimalDetail(animal);
        }

        gridDiv.appendChild(item);
    });
}

// Mostrar detalle de un animal (modal simple)
function showAnimalDetail(animal) {
    const detail = `
        <strong>${animal.icon} ${animal.name}</strong><br>
        <em>${animal.scientificName}</em><br><br>
        Animal descubierto ✓
    `;

    // Crear modal simple
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: rgba(40, 40, 40, 0.98);
        color: white;
        padding: 30px;
        border-radius: 16px;
        z-index: 3000;
        max-width: 400px;
        text-align: center;
        border: 2px solid rgba(139, 69, 19, 0.8);
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.7);
    `;

    modal.innerHTML = `
        ${detail}
        <button onclick="this.parentElement.remove()" style="
            margin-top: 20px;
            padding: 10px 30px;
            background: rgba(139, 69, 19, 0.8);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
        ">Cerrar</button>
    `;

    document.body.appendChild(modal);
}

// Iniciar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', async () => {
    // Cargar fotos guardadas
    loadSavedPhotos();

    // Cargar animales disponibles para el diario de campo
    await loadAllAnimals();

    // Actualizar badge del diario de campo
    updateFieldJournalBadge();

    // Esperar a que A-Frame esté completamente cargado
    const scene = document.querySelector('a-scene');

    if (scene.hasLoaded) {
        console.log('A-Frame ya cargado, iniciando AR...');
        await initAR();
    } else {
        console.log('Esperando a que A-Frame cargue...');
        scene.addEventListener('loaded', async () => {
            console.log('A-Frame cargado, iniciando AR...');
            await initAR();
        });
    }
});
