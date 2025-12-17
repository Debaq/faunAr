// Animal Form Handler

const form = document.getElementById('animal-form');
const urlParams = new URLSearchParams(window.location.search);
const action = urlParams.get('action');
const animalId = urlParams.get('id');

// Cargar datos si es edición
if (action === 'edit' && animalId) {
    loadAnimalData(animalId);
}

async function loadAnimalData(id) {
    try {
        const response = await fetch(`../api/animals/get.php?id=${id}`);
        const data = await response.json();

        if (data.success) {
            populateForm(data.config, data.detailedDescription);
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

    // Marcador
    if (config.marker) {
        form.querySelector('[name="marker_enabled"]').checked = config.marker.enabled || false;
        form.querySelector('[name="marker_type"]').value = config.marker.type || 'mind';
        form.querySelector('[name="marker_file"]').value = config.marker.file || '';
    }

    // GPS
    if (config.gps) {
        form.querySelector('[name="gps_enabled"]').checked = config.gps.enabled || false;
        form.querySelector('[name="gps_latitude"]').value = config.gps.latitude || '';
        form.querySelector('[name="gps_longitude"]').value = config.gps.longitude || '';
        form.querySelector('[name="gps_radius"]').value = config.gps.radius || 50;
    }

    // Modelo 3D
    if (config.model) {
        form.querySelector('[name="model_glb"]').value = config.model.glb || '';
        form.querySelector('[name="model_usdz"]').value = config.model.usdz || '';
        form.querySelector('[name="model_scale"]').value = config.model.scale || '0.5 0.5 0.5';
        form.querySelector('[name="model_position"]').value = config.model.position || '0 0 0';
        form.querySelector('[name="model_rotation"]').value = config.model.rotation || '0 0 0';
    }

    // Audio
    if (config.audio) {
        form.querySelector('[name="audio_enabled"]').checked = config.audio.enabled || false;
        form.querySelector('[name="audio_file"]').value = config.audio.file || '';
    }

    // Info adicional
    if (config.info) {
        form.querySelector('[name="info_habitat"]').value = config.info.habitat || '';
        form.querySelector('[name="info_diet"]').value = config.info.diet || '';
        form.querySelector('[name="info_status"]').value = config.info.status || '';
        form.querySelector('[name="info_wikipedia"]').value = config.info.wikipedia || '';
    }

    // Archivos
    form.querySelector('[name="thumbnail"]').value = config.thumbnail || '';
    form.querySelector('[name="silhouette"]').value = config.silhouette || '';

    // Descripción detallada
    if (detailedDescription) {
        form.querySelector('[name="detailed_description"]').value = detailedDescription;
    }
}

// Submit del formulario
form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const formData = new FormData(form);
    const action = formData.get('action');
    const id = action === 'edit' ? formData.get('original_id') : formData.get('id');

    // Construir objeto de configuración
    const animalData = {
        id: formData.get('id'),
        name: formData.get('name'),
        scientificName: formData.get('scientificName'),
        description: formData.get('description'),
        icon: formData.get('icon'),
        thumbnail: formData.get('thumbnail') || `thumbnail.png`,
        silhouette: formData.get('silhouette') || `silueta_${formData.get('id')}.svg`,
        arMode: formData.get('arMode'),
        gps: {
            enabled: formData.get('gps_enabled') === 'on',
            latitude: parseFloat(formData.get('gps_latitude')) || 0,
            longitude: parseFloat(formData.get('gps_longitude')) || 0,
            radius: parseInt(formData.get('gps_radius')) || 50
        },
        marker: {
            enabled: formData.get('marker_enabled') === 'on',
            type: formData.get('marker_type') || 'mind',
            file: formData.get('marker_file') || `${formData.get('id')}.mind`
        },
        model: {
            glb: formData.get('model_glb') || `${formData.get('id')}.glb`,
            usdz: formData.get('model_usdz') || `${formData.get('id')}.usdz`,
            scale: formData.get('model_scale') || '0.5 0.5 0.5',
            position: formData.get('model_position') || '0 0 0',
            rotation: formData.get('model_rotation') || '0 0 0'
        },
        audio: {
            enabled: formData.get('audio_enabled') === 'on',
            file: formData.get('audio_file') || 'sound.mp3'
        },
        info: {
            habitat: formData.get('info_habitat') || '',
            diet: formData.get('info_diet') || '',
            status: formData.get('info_status') || '',
            wikipedia: formData.get('info_wikipedia') || ''
        },
        detailedDescription: formData.get('detailed_description') || ''
    };

    try {
        const endpoint = action === 'edit' ? '../api/animals/update.php' : '../api/animals/create.php';
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(animalData)
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
            showNotification(data.error || 'Error al guardar', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error de conexión', 'error');
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
