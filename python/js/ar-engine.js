let currentConfig = null;
let userLocation = null;
let arSystemReady = false;
let globalAudioInstance = null;
let isAudioPlaying = false;

// Obtener parámetro de modelo desde URL
const urlParams = new URLSearchParams(window.location.search);
const modelId = urlParams.get('model');

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
        console.log('🎯🎯🎯 TARGET ENCONTRADO - MOSTRANDO MODELO 🎯🎯🎯');
        document.getElementById('loading').style.display = 'none';

        // Reproducir sonido
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

        // Pausar sonido
        if (globalAudioInstance && isAudioPlaying) {
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

    // Eventos
    anchor.addEventListener('targetFound', () => {
        console.log('🎯🎯🎯 TARGET ENCONTRADO - MOSTRANDO MODELO 🎯🎯🎯');
        document.getElementById('loading').style.display = 'none';

        // Reproducir sonido si está configurado
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

        // Pausar sonido
        if (globalAudioInstance && isAudioPlaying) {
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

window.enable3DMode = function () {
    console.log('🎨 Botón Ver en 3D presionado');

    // Verificar que la configuración esté cargada
    if (!currentConfig) {
        console.error('❌ Configuración no cargada aún');
        alert('Espera un momento, cargando configuración...');
        return;
    }

    console.log('✓ Configuración disponible:', currentConfig);

    const scene = document.querySelector('a-scene');
    const camera = document.getElementById('camera');
    const view3DBtn = document.getElementById('view-3d-btn');

    if (!scene || !camera) {
        console.error('❌ Escena o cámara no encontrada');
        alert('Error: Escena AR no disponible');
        return;
    }

    // Verificar si ya existe el modelo 3D
    const existing3DModels = camera.querySelectorAll('[data-3d-preview]');
    if (existing3DModels.length > 0) {
        console.log('Modelos 3D ya visibles, removiendo...');

        // Detener audio si está reproduciendo
        if (globalAudioInstance && isAudioPlaying) {
            globalAudioInstance.pause();
            isAudioPlaying = false;
            updateAudioButton();
            console.log('🔇 Audio detenido');
        }

        // Remover todos los elementos de preview (modelo y cubo)
        existing3DModels.forEach(el => el.remove());

        view3DBtn.innerHTML = '👁️ Ver en 3D';
        view3DBtn.style.background = 'rgba(76, 175, 80, 0.8)';
        return;
    }

    // Ocultar loading si está visible
    document.getElementById('loading').style.display = 'none';

    // Asegurarse de que el video de la cámara esté visible
    const video = document.querySelector('video');
    if (video) {
        video.style.display = 'block';
        video.style.zIndex = '-2';
        console.log('📹 Video de cámara asegurado como visible');
    }

    // Asegurar que el canvas sea transparente
    const canvas = scene.canvas;
    if (canvas) {
        canvas.style.background = 'transparent';
        console.log('🎨 Canvas configurado como transparente');
    }

    // Asegurar que la escena no tenga fondo
    scene.removeAttribute('background');
    console.log('🎨 Fondo de escena removido para ver la cámara');

    let entity;

    if (currentConfig.model.type === 'primitive') {
        // Modelo primitivo (Cubo, Esfera, etc.)
        entity = document.createElement(currentConfig.model.primitive || 'a-box');
        entity.setAttribute('color', currentConfig.model.color || 'red');
    } else {
        // Modelo 3D (GLB/GLTF)
        entity = document.createElement('a-entity');
        const modelPath = `models/${modelId}/${currentConfig.model.glb}`;
        console.log('📦 Cargando modelo 3D desde:', modelPath);
        entity.setAttribute('gltf-model', modelPath);

        if (currentConfig.model.glb.includes('glb') || currentConfig.model.glb.includes('gltf')) {
            entity.setAttribute('animation-mixer', '');
        }

        // Eventos de carga
        entity.addEventListener('model-loaded', () => {
            console.log('✅ Modelo 3D cargado exitosamente en modo preview');
            console.log('🎨 El modelo debería estar visible girando frente a ti');
        });

        entity.addEventListener('model-error', (event) => {
            console.error('❌ Error cargando modelo 3D:', event.detail);
            alert('Error al cargar el modelo 3D. Verifica la consola para más detalles.');
        });
    }

    // Marcar para poder identificarlo después
    entity.setAttribute('data-3d-preview', 'true');

    // Configurar escala y posición - AUMENTAMOS LA ESCALA para mejor visibilidad
    const baseScale = currentConfig.model.scale || '1 1 1';
    const scaleParts = baseScale.split(' ').map(v => parseFloat(v) * 3); // TRIPLICAR escala
    const scale = scaleParts.join(' ');

    entity.setAttribute('scale', scale);
    entity.setAttribute('position', '0 0 -0.8'); // MUY CERCA: 0.8 metros frente a la cámara
    entity.setAttribute('rotation', '0 0 0');

    console.log('📐 Modelo configurado - Escala:', scale, 'Posición: 0 0 -0.8');

    // Agregar controles de rotación básica
    entity.setAttribute('animation', 'property: rotation; to: 0 360 0; loop: true; dur: 10000; easing: linear');

    // AGREGAR CUBO DE PRUEBA GRANDE para verificar visibilidad
    const testCube = document.createElement('a-box');
    testCube.setAttribute('color', '#FF0000');
    testCube.setAttribute('position', '0.3 0 -0.8');
    testCube.setAttribute('scale', '0.15 0.15 0.15'); // Cubo más grande
    testCube.setAttribute('data-3d-preview', 'true');
    testCube.setAttribute('animation', 'property: rotation; to: 360 360 0; loop: true; dur: 5000; easing: linear');
    camera.appendChild(testCube);
    console.log('🧪 Cubo de prueba ROJO GRANDE agregado a la derecha');

    // Agregar a la cámara para que siempre esté visible
    camera.appendChild(entity);
    console.log('✓ Modelo agregado a la cámara');

    // Verificar que el modelo esté en la escena
    setTimeout(() => {
        const object3D = entity.object3D;
        if (object3D) {
            console.log('🔍 Verificación Object3D del MODELO:', {
                visible: object3D.visible,
                position: `x:${object3D.position.x.toFixed(2)} y:${object3D.position.y.toFixed(2)} z:${object3D.position.z.toFixed(2)}`,
                scale: `x:${object3D.scale.x.toFixed(2)} y:${object3D.scale.y.toFixed(2)} z:${object3D.scale.z.toFixed(2)}`,
                parent: object3D.parent ? 'OK' : 'NULL',
                children: object3D.children.length
            });

            // Verificar si tiene geometría
            object3D.traverse((child) => {
                if (child.geometry) {
                    console.log('  └─ Geometría encontrada:', child.geometry.type);
                }
                if (child.material) {
                    console.log('  └─ Material encontrado:', child.material.type, 'visible:', child.visible);
                }
            });
        } else {
            console.error('❌ Object3D no encontrado');
        }

        // Verificar CUBO
        const cubeObject = testCube.object3D;
        if (cubeObject) {
            console.log('🧪 Verificación Object3D del CUBO ROJO:', {
                visible: cubeObject.visible,
                position: `x:${cubeObject.position.x.toFixed(2)} y:${cubeObject.position.y.toFixed(2)} z:${cubeObject.position.z.toFixed(2)}`,
                scale: `x:${cubeObject.scale.x.toFixed(2)} y:${cubeObject.scale.y.toFixed(2)} z:${cubeObject.scale.z.toFixed(2)}`
            });
        }

        // Verificar cámara
        const cameraObject = camera.object3D;
        if (cameraObject) {
            const cam = cameraObject.children.find(c => c.isCamera);
            console.log('📷 Cámara Object3D:', {
                position: `x:${cameraObject.position.x.toFixed(2)} y:${cameraObject.position.y.toFixed(2)} z:${cameraObject.position.z.toFixed(2)}`,
                rotation: `x:${cameraObject.rotation.x.toFixed(2)} y:${cameraObject.rotation.y.toFixed(2)} z:${cameraObject.rotation.z.toFixed(2)}`,
                children: cameraObject.children.length
            });

            if (cam) {
                console.log('📷 Configuración de cámara THREE.js:', {
                    near: cam.near,
                    far: cam.far,
                    fov: cam.fov,
                    aspect: cam.aspect
                });
            }
        }

        // Verificar renderer
        const renderer = scene.renderer;
        if (renderer) {
            console.log('🖼️ Renderer info:', {
                alpha: renderer.alpha,
                autoClear: renderer.autoClear,
                sortObjects: renderer.sortObjects
            });
        }
    }, 1000);

    // Reproducir sonido si está configurado
    if (currentConfig.audio?.enabled && globalAudioInstance) {
        globalAudioInstance.play()
            .then(() => {
                console.log('🔊 Sonido reproduciendo en modo 3D');
                isAudioPlaying = true;
                updateAudioButton();
            })
            .catch(err => {
                console.log('⚠️ Error reproduciendo sonido:', err.message);
                console.log('💡 Usa el botón de audio para reproducirlo manualmente');
            });
    }

    // Animar botón de info
    const infoBtn = document.getElementById('info-toggle-btn');
    if (infoBtn) {
        infoBtn.style.animation = 'pulse 1s ease-in-out 3';
    }

    // Cambiar texto del botón
    view3DBtn.innerHTML = '✕ Ocultar 3D';
    view3DBtn.style.background = 'rgba(244, 67, 54, 0.8)';

    console.log('✅ Modelo 3D activado en modo preview');
};

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
function showPhotoPreview(imageData) {
    const preview = document.getElementById('photo-preview');
    const previewImage = document.getElementById('preview-image');

    previewImage.src = imageData;
    preview.style.display = 'flex';
}

// Cerrar preview
window.closePreview = function() {
    document.getElementById('photo-preview').style.display = 'none';
    currentPhotoData = null;
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
                showPhotoPreview(photo.data);
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

// Iniciar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', async () => {
    // Cargar fotos guardadas
    loadSavedPhotos();

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
