<?php include 'includes/header.php'; ?>
<?php include 'includes/nav.php'; ?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<?php
$action = $_GET['action'] ?? 'list';
$locationId = $_GET['id'] ?? '';
?>

<?php if ($action === 'list'): ?>
    <div class="page-header">
        <h1>Gestión de Lugares/Sectores</h1>
        <a href="?action=create" class="btn btn-primary">➕ Nuevo Lugar</a>
    </div>

    <!-- Mapa de lugares -->
    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px;">
        <h3 style="margin-bottom: 15px;">🗺️ Mapa de Lugares</h3>
        <div id="locations-map" style="height: 400px; border-radius: 8px; overflow: hidden;"></div>
        <p style="margin-top: 10px; color: #7f8c8d; font-size: 13px;">
            Los marcadores muestran todos los lugares registrados. Haz clic en un marcador para ver detalles.
        </p>
    </div>

    <div class="filters">
        <input type="text" id="search" placeholder="🔍 Buscar por nombre o región...">
        <select id="filter-enabled">
            <option value="">Todos</option>
            <option value="true">Solo Activos</option>
            <option value="false">Solo Inactivos</option>
        </select>
    </div>

    <table class="animals-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Región</th>
                <th>Coordenadas GPS</th>
                <th>Radio (m)</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="locations-tbody">
            <tr>
                <td colspan="7" class="loading">Cargando lugares...</td>
            </tr>
        </tbody>
    </table>

    <script>
    let allLocations = [];

    async function loadLocations() {
        try {
            const response = await fetch('api/locations/get.php');
            const data = await response.json();

            if (data.success) {
                allLocations = data.locations;
                renderLocations(allLocations);
            }
        } catch (error) {
            console.error('Error cargando lugares:', error);
            document.getElementById('locations-tbody').innerHTML =
                '<tr><td colspan="7" style="text-align: center; color: #e74c3c;">Error al cargar lugares</td></tr>';
        }
    }

    function renderLocations(locations) {
        const tbody = document.getElementById('locations-tbody');
        tbody.innerHTML = '';

        if (locations.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: #7f8c8d;">No hay lugares registrados</td></tr>';
            return;
        }

        locations.forEach(location => {
            const row = document.createElement('tr');

            const statusColor = location.enabled ? '#27ae60' : '#95a5a6';
            const statusText = location.enabled ? 'Activo' : 'Inactivo';

            row.innerHTML = `
                <td><code>${location.id}</code></td>
                <td><strong>${location.name}</strong></td>
                <td>${location.metadata?.region || 'N/A'}</td>
                <td>
                    <small>${location.gps.latitude.toFixed(4)}, ${location.gps.longitude.toFixed(4)}</small>
                </td>
                <td>${location.gps.radius}</td>
                <td>
                    <span style="display: inline-block; padding: 4px 8px; background: ${statusColor}; color: white; border-radius: 4px; font-size: 12px;">
                        ${statusText}
                    </span>
                </td>
                <td>
                    <a href="?action=edit&id=${location.id}" class="btn btn-primary btn-small">Editar</a>
                    <button onclick="deleteLocation('${location.id}', '${location.name}')" class="btn btn-danger btn-small">Eliminar</button>
                </td>
            `;
            tbody.appendChild(row);
        });
    }

    async function deleteLocation(id, name) {
        if (!confirm(`¿Estás seguro de eliminar "${name}"?\n\nEsta acción no se puede deshacer.`)) {
            return;
        }

        try {
            const response = await fetch('api/locations/delete.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });

            const data = await response.json();

            if (data.success) {
                showNotification('Lugar eliminado correctamente', 'success');
                loadLocations();
            } else {
                showNotification(data.error || 'Error al eliminar', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('Error de conexión', 'error');
        }
    }

    // Búsqueda
    document.getElementById('search').addEventListener('input', (e) => {
        const search = e.target.value.toLowerCase();
        const filtered = allLocations.filter(p =>
            p.id.toLowerCase().includes(search) ||
            p.name.toLowerCase().includes(search) ||
            (p.metadata?.region && p.metadata.region.toLowerCase().includes(search))
        );
        renderLocations(filtered);
    });

    // Filtro por estado
    document.getElementById('filter-enabled').addEventListener('change', (e) => {
        const enabled = e.target.value;
        const filtered = enabled === '' ? allLocations : allLocations.filter(p => p.enabled === (enabled === 'true'));
        renderLocations(filtered);
    });

    loadLocations();

    // Mapa de vista general
    let locationsMap = null;
    let locationMarkers = {};

    function initLocationsMap() {
        locationsMap = L.map('locations-map').setView([-41.5, -72.5], 6);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(locationsMap);
    }

    function updateMapMarkers(locations) {
        // Limpiar marcadores anteriores
        Object.values(locationMarkers).forEach(marker => marker.remove());
        locationMarkers = {};

        // Agregar marcadores para cada lugar
        locations.forEach(location => {
            if (location.gps && location.gps.latitude && location.gps.longitude) {
                const marker = L.marker([location.gps.latitude, location.gps.longitude]).addTo(locationsMap);

                // Círculo para mostrar el radio
                const circle = L.circle([location.gps.latitude, location.gps.longitude], {
                    color: location.enabled ? '#27ae60' : '#95a5a6',
                    fillColor: location.enabled ? '#27ae60' : '#95a5a6',
                    fillOpacity: 0.2,
                    radius: location.gps.radius
                }).addTo(locationsMap);

                marker.bindPopup(`
                    <strong>${location.name}</strong><br>
                    ${location.metadata?.region || ''}<br>
                    Radio: ${location.gps.radius}m<br>
                    <a href="?action=edit&id=${location.id}">Editar</a>
                `);

                locationMarkers[location.id] = { marker, circle };
            }
        });
    }

    // Inicializar mapa cuando se cargan los lugares
    const originalLoadLocations = loadLocations;
    loadLocations = async function() {
        await originalLoadLocations();
        if (!locationsMap) {
            initLocationsMap();
        }
        updateMapMarkers(allLocations);
    };
    </script>

<?php elseif ($action === 'create' || $action === 'edit'): ?>
    <div class="page-header">
        <h1><?= $action === 'create' ? 'Nuevo Lugar' : 'Editar Lugar' ?></h1>
        <a href="locations.php" class="btn btn-cancel">← Volver</a>
    </div>

    <form id="location-form">
        <input type="hidden" name="action" value="<?= $action ?>">
        <?php if ($action === 'edit'): ?>
            <input type="hidden" name="original_id" value="<?= htmlspecialchars($locationId) ?>">
        <?php endif; ?>

        <!-- Información Básica -->
        <div class="form-section">
            <h2>📝 Información Básica</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label>ID* <small>(solo minúsculas, guiones permitidos)</small></label>
                    <input type="text" name="id" pattern="[a-z\-]+" required
                           <?= $action === 'edit' ? 'readonly' : '' ?>>
                </div>
                <div class="form-group">
                    <label>Nombre del Lugar*</label>
                    <input type="text" name="name" required placeholder="Parque Nacional...">
                </div>
            </div>

            <div class="form-group">
                <label>Descripción</label>
                <textarea name="description" rows="3" placeholder="Descripción del lugar..."></textarea>
            </div>

            <div class="form-checkbox">
                <input type="checkbox" name="enabled" id="enabled" checked>
                <label for="enabled">Lugar Activo</label>
            </div>
        </div>

        <!-- Configuración GPS con Mapa -->
        <div class="form-section">
            <h2>📍 Ubicación GPS</h2>
            <p style="margin-bottom: 20px; color: #7f8c8d;">
                Haz clic en el mapa para establecer las coordenadas del lugar, o ingresa manualmente.
            </p>

            <!-- Mapa interactivo -->
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <div id="location-edit-map" style="height: 400px; border-radius: 8px; overflow: hidden;"></div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Latitud*</label>
                    <input type="number" name="gps_latitude" id="gps-latitude" step="0.000001" required placeholder="-41.4693">
                </div>
                <div class="form-group">
                    <label>Longitud*</label>
                    <input type="number" name="gps_longitude" id="gps-longitude" step="0.000001" required placeholder="-72.9424">
                </div>
                <div class="form-group">
                    <label>Radio (metros)*</label>
                    <input type="number" name="gps_radius" id="gps-radius" value="5000" required min="10">
                </div>
            </div>
        </div>

        <!-- Metadata -->
        <div class="form-section">
            <h2>ℹ️ Información Adicional</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label>Región</label>
                    <input type="text" name="metadata_region" placeholder="Región de Los Lagos">
                </div>
                <div class="form-group">
                    <label>País</label>
                    <input type="text" name="metadata_country" value="Chile">
                </div>
            </div>
        </div>

        <!-- Botones -->
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Guardar Lugar</button>
            <a href="locations.php" class="btn btn-cancel">✕ Cancelar</a>
        </div>
    </form>

    <script>
    const form = document.getElementById('location-form');
    const action = '<?= $action ?>';
    const locationId = '<?= $locationId ?>';

    // Cargar datos si es edición
    if (action === 'edit' && locationId) {
        loadLocationData(locationId);
    }

    async function loadLocationData(id) {
        try {
            const response = await fetch(`api/locations/get.php?id=${id}`);
            const data = await response.json();

            if (data.success && data.location) {
                populateForm(data.location);
            } else {
                showNotification('Error al cargar lugar', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('Error de conexión', 'error');
        }
    }

    function populateForm(location) {
        form.querySelector('[name="id"]').value = location.id || '';
        form.querySelector('[name="name"]').value = location.name || '';
        form.querySelector('[name="description"]').value = location.description || '';
        form.querySelector('[name="enabled"]').checked = location.enabled !== false;

        // GPS
        form.querySelector('[name="gps_latitude"]').value = location.gps?.latitude || '';
        form.querySelector('[name="gps_longitude"]').value = location.gps?.longitude || '';
        form.querySelector('[name="gps_radius"]').value = location.gps?.radius || 5000;

        // Metadata
        form.querySelector('[name="metadata_region"]').value = location.metadata?.region || '';
        form.querySelector('[name="metadata_country"]').value = location.metadata?.country || 'Chile';
    }

    // Submit del formulario
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.innerHTML = '💾 Guardando...';

        const formData = new FormData(form);
        const jsonData = {};

        for (let [key, value] of formData.entries()) {
            jsonData[key] = value;
        }

        try {
            const endpoint = action === 'edit' ? 'api/locations/update.php' : 'api/locations/create.php';
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(jsonData)
            });

            const data = await response.json();

            if (data.success) {
                showNotification(
                    action === 'edit' ? 'Lugar actualizado correctamente' : 'Lugar creado correctamente',
                    'success'
                );
                setTimeout(() => {
                    window.location.href = 'locations.php';
                }, 1500);
            } else {
                showNotification(data.error || 'Error al guardar', 'error');
                submitButton.disabled = false;
                submitButton.innerHTML = '💾 Guardar Lugar';
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('Error de conexión', 'error');
            submitButton.disabled = false;
            submitButton.innerHTML = '💾 Guardar Lugar';
        }
    });

    // ========== MAPA INTERACTIVO DE EDICIÓN ==========
    let editMap = null;
    let editMarker = null;
    let editCircle = null;

    function initEditMap() {
        // Coordenadas por defecto (Chile central)
        const defaultLat = parseFloat(form.querySelector('[name="gps_latitude"]').value) || -41.5;
        const defaultLng = parseFloat(form.querySelector('[name="gps_longitude"]').value) || -72.5;
        const defaultRadius = parseInt(form.querySelector('[name="gps_radius"]').value) || 5000;

        editMap = L.map('location-edit-map').setView([defaultLat, defaultLng], 10);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(editMap);

        // Marcador inicial
        editMarker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(editMap);
        editCircle = L.circle([defaultLat, defaultLng], {
            color: '#3498db',
            fillColor: '#3498db',
            fillOpacity: 0.2,
            radius: defaultRadius
        }).addTo(editMap);

        // Click en el mapa para mover marcador
        editMap.on('click', function(e) {
            const lat = e.latlng.lat;
            const lng = e.latlng.lng;
            updateMarkerPosition(lat, lng);
        });

        // Drag del marcador
        editMarker.on('dragend', function(e) {
            const position = e.target.getLatLng();
            updateMarkerPosition(position.lat, position.lng);
        });

        // Actualizar círculo cuando cambia el radio
        form.querySelector('[name="gps_radius"]').addEventListener('input', function(e) {
            const newRadius = parseInt(e.target.value) || 5000;
            if (editCircle) {
                editCircle.setRadius(newRadius);
            }
        });

        // Actualizar mapa cuando se cambian inputs manualmente
        form.querySelector('[name="gps_latitude"]').addEventListener('change', syncMapFromInputs);
        form.querySelector('[name="gps_longitude"]').addEventListener('change', syncMapFromInputs);
    }

    function updateMarkerPosition(lat, lng) {
        form.querySelector('[name="gps_latitude"]').value = lat.toFixed(6);
        form.querySelector('[name="gps_longitude"]').value = lng.toFixed(6);

        editMarker.setLatLng([lat, lng]);
        editCircle.setLatLng([lat, lng]);
        editMap.panTo([lat, lng]);
    }

    function syncMapFromInputs() {
        const lat = parseFloat(form.querySelector('[name="gps_latitude"]').value);
        const lng = parseFloat(form.querySelector('[name="gps_longitude"]').value);

        if (!isNaN(lat) && !isNaN(lng)) {
            editMarker.setLatLng([lat, lng]);
            editCircle.setLatLng([lat, lng]);
            editMap.setView([lat, lng]);
        }
    }

    // Inicializar mapa después de un breve delay para asegurar que el DOM esté listo
    setTimeout(initEditMap, 100);
    </script>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>
