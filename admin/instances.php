<?php include 'includes/header.php'; ?>
<?php include 'includes/nav.php'; ?>

<?php
$action = $_GET['action'] ?? 'list';
$instanceId = $_GET['id'] ?? '';
?>

<?php if ($action === 'list'): ?>
    <div class="page-header">
        <h1>Gestión de Instancias de Animales</h1>
        <a href="?action=create" class="btn btn-primary">➕ Nueva Instancia</a>
    </div>

    <div style="background: #e8f4f8; padding: 15px 20px; margin-bottom: 20px; border-radius: 8px; border-left: 4px solid #3498db;">
        <strong>ℹ️ Instancias:</strong> Cada instancia es una ubicación física donde se coloca un QR. Un mismo animal puede tener múltiples instancias en diferentes lugares.
    </div>

    <div class="filters">
        <input type="text" id="search" placeholder="🔍 Buscar por animal, lugar o código QR...">
        <select id="filter-place">
            <option value="">Todos los lugares</option>
        </select>
        <select id="filter-enabled">
            <option value="">Todos</option>
            <option value="true">Solo Activos</option>
            <option value="false">Solo Inactivos</option>
        </select>
    </div>

    <table class="animals-table">
        <thead>
            <tr>
                <th>Código QR</th>
                <th>Animal</th>
                <th>Lugar</th>
                <th>Variante</th>
                <th>Coordenadas GPS</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="instances-tbody">
            <tr>
                <td colspan="7" class="loading">Cargando instancias...</td>
            </tr>
        </tbody>
    </table>

    <script>
    let allInstances = [];
    let allAnimals = [];
    let allPlaces = [];

    async function loadData() {
        try {
            // Cargar todas las instancias
            const instancesRes = await fetch('api/instances/get.php');
            const instancesData = await instancesRes.json();

            // Cargar todos los animales
            const animalsRes = await fetch('../api/animals/list.php');
            const animalsData = await animalsRes.json();

            // Cargar todos los lugares
            const placesRes = await fetch('api/locations/get.php');
            const placesData = await placesRes.json();

            if (instancesData.success && animalsData.success && placesData.success) {
                allInstances = instancesData.instances;
                allAnimals = animalsData.animals;
                allPlaces = placesData.locations;

                // Poblar filtro de lugares
                const placeFilter = document.getElementById('filter-place');
                allPlaces.forEach(place => {
                    const option = document.createElement('option');
                    option.value = place.id;
                    option.textContent = place.name;
                    placeFilter.appendChild(option);
                });

                renderInstances(allInstances);
            }
        } catch (error) {
            console.error('Error cargando datos:', error);
            document.getElementById('instances-tbody').innerHTML =
                '<tr><td colspan="7" style="text-align: center; color: #e74c3c;">Error al cargar instancias</td></tr>';
        }
    }

    function renderInstances(instances) {
        const tbody = document.getElementById('instances-tbody');
        tbody.innerHTML = '';

        if (instances.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: #7f8c8d;">No hay instancias registradas</td></tr>';
            return;
        }

        instances.forEach(instance => {
            const row = document.createElement('tr');

            // Encontrar animal y lugar
            const animal = allAnimals.find(a => a.id === instance.animalId);
            const place = allPlaces.find(p => p.id === instance.placeId);

            const statusColor = instance.enabled ? '#27ae60' : '#95a5a6';
            const statusText = instance.enabled ? 'Activo' : 'Inactivo';

            row.innerHTML = `
                <td>
                    <code style="background: #f8f9fa; padding: 4px 8px; border-radius: 4px; font-weight: 600;">
                        ${instance.qrCode}
                    </code>
                </td>
                <td>
                    <strong>${animal ? animal.name : instance.animalId}</strong>
                    ${animal ? '<div style="font-size: 20px;">' + animal.icon + '</div>' : ''}
                </td>
                <td>${place ? place.name : instance.placeId}</td>
                <td>
                    <span style="background: #e8f4f8; padding: 3px 8px; border-radius: 4px; font-size: 12px;">
                        ${instance.variant || 'default'}
                    </span>
                </td>
                <td>
                    <small>${instance.gps?.latitude?.toFixed(4) || 'N/A'}, ${instance.gps?.longitude?.toFixed(4) || 'N/A'}</small>
                </td>
                <td>
                    <span style="display: inline-block; padding: 4px 8px; background: ${statusColor}; color: white; border-radius: 4px; font-size: 12px;">
                        ${statusText}
                    </span>
                </td>
                <td>
                    <a href="?action=edit&id=${instance.id}" class="btn btn-primary btn-small">Editar</a>
                    <button onclick="viewQR('${instance.qrCode}')" class="btn btn-secondary btn-small">Ver QR</button>
                    <button onclick="deleteInstance('${instance.id}', '${instance.qrCode}')" class="btn btn-danger btn-small">Eliminar</button>
                </td>
            `;
            tbody.appendChild(row);
        });
    }

    async function deleteInstance(id, qrCode) {
        if (!confirm(`¿Estás seguro de eliminar la instancia "${qrCode}"?\n\nEsta acción no se puede deshacer.`)) {
            return;
        }

        try {
            const response = await fetch('api/instances/delete.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });

            const data = await response.json();

            if (data.success) {
                showNotification('Instancia eliminada correctamente', 'success');
                loadData();
            } else {
                showNotification(data.error || 'Error al eliminar', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('Error de conexión', 'error');
        }
    }

    function viewQR(qrCode) {
        // Abrir modal con QR
        const modal = document.createElement('div');
        modal.style.cssText = `
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.8); z-index: 9999;
            display: flex; align-items: center; justify-content: center;
        `;

        modal.innerHTML = `
            <div style="background: white; padding: 30px; border-radius: 12px; text-align: center; max-width: 400px;">
                <h3 style="margin-bottom: 20px;">Código QR: ${qrCode}</h3>
                <div id="qr-container"></div>
                <p style="margin-top: 20px; color: #7f8c8d; font-size: 13px;">
                    URL: <code>viewer.html?qr=${qrCode}</code>
                </p>
                <button onclick="this.closest('[style*=fixed]').remove()" class="btn btn-secondary" style="margin-top: 20px;">Cerrar</button>
            </div>
        `;

        document.body.appendChild(modal);

        // Generar QR usando QRCode.js (agregar librería si no existe)
        const script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js';
        script.onload = () => {
            new QRCode(document.getElementById('qr-container'), {
                text: `${window.location.origin}/viewer.html?qr=${qrCode}`,
                width: 256,
                height: 256
            });
        };
        document.head.appendChild(script);
    }

    // Búsqueda
    document.getElementById('search').addEventListener('input', (e) => {
        const search = e.target.value.toLowerCase();
        const filtered = allInstances.filter(i =>
            i.qrCode.toLowerCase().includes(search) ||
            i.animalId.toLowerCase().includes(search) ||
            i.placeId.toLowerCase().includes(search)
        );
        renderInstances(filtered);
    });

    // Filtro por lugar
    document.getElementById('filter-place').addEventListener('change', (e) => {
        const placeId = e.target.value;
        const filtered = placeId ? allInstances.filter(i => i.placeId === placeId) : allInstances;
        renderInstances(filtered);
    });

    // Filtro por estado
    document.getElementById('filter-enabled').addEventListener('change', (e) => {
        const enabled = e.target.value;
        const filtered = enabled === '' ? allInstances : allInstances.filter(i => i.enabled === (enabled === 'true'));
        renderInstances(filtered);
    });

    loadData();
    </script>

<?php elseif ($action === 'create' || $action === 'edit'): ?>
    <div class="page-header">
        <h1><?= $action === 'create' ? 'Nueva Instancia' : 'Editar Instancia' ?></h1>
        <a href="instances.php" class="btn btn-cancel">← Volver</a>
    </div>

    <form id="instance-form">
        <input type="hidden" name="action" value="<?= $action ?>">
        <?php if ($action === 'edit'): ?>
            <input type="hidden" name="original_id" value="<?= htmlspecialchars($instanceId) ?>">
        <?php endif; ?>

        <!-- Información Básica -->
        <div class="form-section">
            <h2>📝 Información Básica</h2>

            <?php if ($action === 'edit'): ?>
            <div class="form-group">
                <label>ID de Instancia</label>
                <input type="text" name="id" readonly style="background: #f5f6fa;">
            </div>
            <?php endif; ?>

            <div class="form-grid">
                <div class="form-group">
                    <label>Animal*</label>
                    <select name="animalId" id="animal-select" required <?= $action === 'edit' ? 'disabled' : '' ?>>
                        <option value="">Selecciona un animal</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Lugar/Sector*</label>
                    <select name="placeId" id="place-select" required>
                        <option value="">Selecciona un lugar</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Código QR*</label>
                <div style="display: flex; gap: 10px;">
                    <input type="text" name="qrCode" id="qr-code-input" required readonly style="flex: 1; background: #f5f6fa;">
                    <?php if ($action === 'create'): ?>
                    <button type="button" onclick="generateQRCode()" class="btn btn-secondary">🔄 Generar</button>
                    <?php endif; ?>
                </div>
                <small style="color: #7f8c8d;">Código único para esta instancia. Se genera automáticamente.</small>
            </div>

            <div class="form-group">
                <label>Variante</label>
                <select name="variant" id="variant-select">
                    <option value="default">default</option>
                </select>
                <small style="color: #7f8c8d;">Versión del modelo 3D a usar. Las variantes se gestionan en <a href="variants.php">Variantes</a>.</small>
            </div>

            <div class="form-checkbox">
                <input type="checkbox" name="enabled" id="enabled" checked>
                <label for="enabled">Instancia Activa</label>
            </div>
        </div>

        <!-- Configuración GPS -->
        <div class="form-section">
            <h2>📍 Ubicación GPS Específica</h2>
            <p style="margin-bottom: 20px; color: #7f8c8d;">
                Coordenadas exactas donde se ubicará el QR físicamente (opcional, se puede usar la del lugar).
            </p>

            <div class="form-grid">
                <div class="form-group">
                    <label>Latitud</label>
                    <input type="number" name="gps_latitude" step="0.000001" placeholder="Usar coordenadas del lugar">
                </div>
                <div class="form-group">
                    <label>Longitud</label>
                    <input type="number" name="gps_longitude" step="0.000001" placeholder="Usar coordenadas del lugar">
                </div>
                <div class="form-group">
                    <label>Radio (metros)</label>
                    <input type="number" name="gps_radius" value="50">
                </div>
            </div>
        </div>

        <!-- Marcador AR -->
        <div class="form-section">
            <h2>🎯 Marcador AR</h2>
            <div class="form-group">
                <label>Archivo de Marcador (.mind)</label>
                <select name="marker_file" id="marker-select">
                    <option value="">Usar marcador del animal</option>
                </select>
                <small style="color: #7f8c8d;">Por defecto usa el marcador configurado en el animal</small>
            </div>
        </div>

        <!-- Metadata -->
        <div class="form-section">
            <h2>ℹ️ Información Adicional</h2>
            <div class="form-group">
                <label>Notas</label>
                <textarea name="notes" rows="3" placeholder="Notas sobre esta instancia..."></textarea>
            </div>
        </div>

        <!-- Botones -->
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Guardar Instancia</button>
            <a href="instances.php" class="btn btn-cancel">✕ Cancelar</a>
        </div>
    </form>

    <script>
    const form = document.getElementById('instance-form');
    const action = '<?= $action ?>';
    const instanceId = '<?= $instanceId ?>';

    let availableAnimals = [];
    let availablePlaces = [];

    // Cargar variantes de un animal
    async function loadAnimalVariants(animalId) {
        try {
            const response = await fetch(`../models/${animalId}/config.json?t=${new Date().getTime()}`);
            const config = await response.json();

            const variantSelect = document.getElementById('variant-select');
            variantSelect.innerHTML = '';

            // Si tiene sistema de variantes
            if (config.variants) {
                Object.keys(config.variants).forEach(variantName => {
                    const option = document.createElement('option');
                    option.value = variantName;
                    option.textContent = variantName;
                    if (variantName === config.activeVariant) {
                        option.textContent += ' (activa)';
                    }
                    variantSelect.appendChild(option);
                });
            } else {
                // Config legacy sin variantes
                const option = document.createElement('option');
                option.value = 'default';
                option.textContent = 'default';
                variantSelect.appendChild(option);
            }
        } catch (error) {
            console.error('Error cargando variantes:', error);
            // Fallback
            const variantSelect = document.getElementById('variant-select');
            variantSelect.innerHTML = '<option value="default">default</option>';
        }
    }

    // Cargar datos iniciales
    async function loadFormData() {
        try {
            // Cargar animales
            const animalsRes = await fetch('../api/animals/list.php');
            const animalsData = await animalsRes.json();

            if (animalsData.success) {
                availableAnimals = animalsData.animals;
                const animalSelect = document.getElementById('animal-select');
                animalsData.animals.forEach(animal => {
                    const option = document.createElement('option');
                    option.value = animal.id;
                    option.textContent = `${animal.icon} ${animal.name}`;
                    animalSelect.appendChild(option);
                });

                // Listener para cargar variantes cuando se selecciona un animal
                animalSelect.addEventListener('change', (e) => {
                    if (e.target.value) {
                        loadAnimalVariants(e.target.value);
                    }
                });
            }

            // Cargar lugares
            const placesRes = await fetch('api/locations/get.php');
            const placesData = await placesRes.json();

            if (placesData.success) {
                availablePlaces = placesData.locations;
                const placeSelect = document.getElementById('place-select');
                placesData.locations.forEach(place => {
                    const option = document.createElement('option');
                    option.value = place.id;
                    option.textContent = place.name;
                    placeSelect.appendChild(option);
                });
            }

            // Si es edición, cargar datos de la instancia
            if (action === 'edit' && instanceId) {
                loadInstanceData(instanceId);
            }
        } catch (error) {
            console.error('Error cargando datos:', error);
            showNotification('Error cargando datos del formulario', 'error');
        }
    }

    async function loadInstanceData(id) {
        try {
            const response = await fetch(`api/instances/get.php?id=${id}`);
            const data = await response.json();

            if (data.success && data.instance) {
                populateForm(data.instance);
            } else {
                showNotification('Error al cargar instancia', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('Error de conexión', 'error');
        }
    }

    function populateForm(instance) {
        if (form.querySelector('[name="id"]')) {
            form.querySelector('[name="id"]').value = instance.id || '';
        }
        form.querySelector('[name="animalId"]').value = instance.animalId || '';
        form.querySelector('[name="placeId"]').value = instance.placeId || '';
        form.querySelector('[name="qrCode"]').value = instance.qrCode || '';
        form.querySelector('[name="enabled"]').checked = instance.enabled !== false;

        // Cargar variantes del animal y luego seleccionar la de la instancia
        if (instance.animalId) {
            loadAnimalVariants(instance.animalId).then(() => {
                form.querySelector('[name="variant"]').value = instance.variant || 'default';
            });
        }

        // GPS
        if (instance.gps) {
            form.querySelector('[name="gps_latitude"]').value = instance.gps.latitude || '';
            form.querySelector('[name="gps_longitude"]').value = instance.gps.longitude || '';
            form.querySelector('[name="gps_radius"]').value = instance.gps.radius || 50;
        }

        // Marker
        if (instance.marker?.file) {
            form.querySelector('[name="marker_file"]').value = instance.marker.file;
        }

        // Metadata
        if (instance.metadata?.notes) {
            form.querySelector('[name="notes"]').value = instance.metadata.notes;
        }
    }

    // Generar código QR único
    function generateQRCode() {
        const animal = document.getElementById('animal-select').value;
        const place = document.getElementById('place-select').value;

        if (!animal || !place) {
            alert('Selecciona primero un animal y un lugar');
            return;
        }

        // Generar código único: QR_[lugar]_[animal]_[timestamp]
        const timestamp = Date.now().toString(36).slice(-4).toUpperCase();
        const qrCode = `QR_${place.slice(0, 3).toUpperCase()}_${animal.slice(0, 3).toUpperCase()}_${timestamp}`;

        document.getElementById('qr-code-input').value = qrCode;
    }

    // Auto-generar QR cuando se selecciona animal y lugar (solo en crear)
    if (action === 'create') {
        document.getElementById('animal-select').addEventListener('change', () => {
            if (document.getElementById('place-select').value && !document.getElementById('qr-code-input').value) {
                generateQRCode();
            }
        });

        document.getElementById('place-select').addEventListener('change', () => {
            if (document.getElementById('animal-select').value && !document.getElementById('qr-code-input').value) {
                generateQRCode();
            }
        });
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

        // Si animal está disabled (en edición), obtener el valor manualmente
        if (action === 'edit') {
            jsonData.animalId = form.querySelector('[name="animalId"]').value;
        }

        try {
            const endpoint = action === 'edit' ? 'api/instances/update.php' : 'api/instances/create.php';
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(jsonData)
            });

            const data = await response.json();

            if (data.success) {
                showNotification(
                    action === 'edit' ? 'Instancia actualizada correctamente' : 'Instancia creada correctamente',
                    'success'
                );
                setTimeout(() => {
                    window.location.href = 'instances.php';
                }, 1500);
            } else {
                showNotification(data.error || 'Error al guardar', 'error');
                submitButton.disabled = false;
                submitButton.innerHTML = '💾 Guardar Instancia';
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('Error de conexión', 'error');
            submitButton.disabled = false;
            submitButton.innerHTML = '💾 Guardar Instancia';
        }
    });

    loadFormData();
    </script>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>
