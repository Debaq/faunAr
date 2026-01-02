<?php include 'includes/header.php'; ?>
<?php include 'includes/nav.php'; ?>

<?php
$action = $_GET['action'] ?? 'list';
$animalId = $_GET['id'] ?? '';
?>

<?php if ($action === 'list'): ?>
    <div class="page-header">
        <h1>Gestión de Animales</h1>
        <a href="?action=create" class="btn btn-primary">➕ Nuevo Animal</a>
    </div>

    <div class="filters">
        <input type="text" id="search" placeholder="🔍 Buscar por nombre o ID...">
        <select id="filter-mode">
            <option value="">Todos los modos</option>
            <option value="marker">Solo Marcador</option>
            <option value="gps">Solo GPS</option>
            <option value="hybrid">Híbrido</option>
        </select>
        <select id="filter-category">
            <option value="">Todas las categorías</option>
            <!-- Las categorías se cargarán aquí -->
        </select>
    </div>

    <table class="animals-table">
        <thead>
            <tr>
                <th>Miniatura</th>
                <th>ID</th>
                <th>Nombre</th>
                <th>Nombre Científico</th>
                <th>Categoría</th>
                <th>Modo AR</th>
                <th>Archivos</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="animals-tbody">
            <tr>
                <td colspan="8" class="loading">Cargando animales...</td>
            </tr>
        </tbody>
    </table>

    <script>
    let allAnimals = [];
    let allCategories = {};

    async function loadData() {
        try {
            await loadCategories();
            await loadAnimals();
        } catch (error) {
            console.error('Error al cargar datos:', error);
            document.getElementById('animals-tbody').innerHTML =
                '<tr><td colspan="8" style="text-align: center; color: #e74c3c;">Error al cargar datos</td></tr>';
        }
    }

    async function loadCategories() {
        try {
            const response = await fetch('../data/categories.json');
            allCategories = await response.json();
            populateCategoryFilter();
        } catch (error) {
            console.error('Error cargando categorías:', error);
            throw error; // Re-lanzar para que loadData lo maneje
        }
    }

    function populateCategoryFilter() {
        const filterCategory = document.getElementById('filter-category');
        for (const key in allCategories) {
            if (allCategories.hasOwnProperty(key)) {
                const option = document.createElement('option');
                option.value = key;
                option.textContent = allCategories[key].name;
                filterCategory.appendChild(option);
            }
        }
    }

    async function loadAnimals() {
        try {
            const response = await fetch('../api/animals/list.php');
            const data = await response.json();

            if (data.success) {
                allAnimals = data.animals;
                applyFilters();
            } else {
                 document.getElementById('animals-tbody').innerHTML =
                '<tr><td colspan="8" style="text-align: center; color: #e74c3c;">Error al cargar animales</td></tr>';
            }
        } catch (error) {
            console.error('Error cargando animales:', error);
            document.getElementById('animals-tbody').innerHTML =
                '<tr><td colspan="8" style="text-align: center; color: #e74c3c;">Error al cargar animales</td></tr>';
        }
    }

    function renderAnimals(animals) {
        const tbody = document.getElementById('animals-tbody');
        tbody.innerHTML = '';

        if (animals.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; color: #7f8c8d;">No hay animales que coincidan con los filtros</td></tr>';
            return;
        }

        animals.forEach(animal => {
            const row = document.createElement('tr');

            const thumbnailUrl = animal.thumbnail ?
                `../models/${animal.id}/${animal.thumbnail}` :
                '../assets/images/placeholder.png';

            const modeColors = {
                'marker': '#3498db',
                'gps': '#27ae60',
                'hybrid': '#f39c12'
            };

            const categoryName = allCategories[animal.category] ? allCategories[animal.category].name : 'Sin categoría';
            const categoryIcon = allCategories[animal.category] ? allCategories[animal.category].icon : '';


            row.innerHTML = `
                <td>
                    <img src="${thumbnailUrl}"
                         alt="${animal.name}"
                         class="animal-list-thumbnail"
                         onerror="this.src='../assets/images/placeholder.png'">
                </td>
                <td><code>${animal.id}</code></td>
                <td>
                    <strong>${animal.name}</strong>
                    <div style="font-size: 20px;">${animal.icon}</div>
                </td>
                <td style="font-style: italic; color: #7f8c8d;">${animal.scientificName}</td>
                <td>${categoryIcon} ${categoryName}</td>
                <td>
                    <span style="display: inline-block; padding: 4px 8px; background: ${modeColors[animal.arMode] || '#7f8c8d'}; color: white; border-radius: 4px; font-size: 12px; text-transform: uppercase;">
                        ${animal.arMode}
                    </span>
                </td>
                <td>${animal.filesCount} archivos</td>
                <td>
                    <a href="?action=edit&id=${animal.id}" class="btn btn-primary btn-small">Editar</a>
                    <button onclick="deleteAnimal('${animal.id}', '${animal.name}')" class="btn btn-danger btn-small">Eliminar</button>
                </td>
            `;
            tbody.appendChild(row);
        });
    }

    function applyFilters() {
        const search = document.getElementById('search').value.toLowerCase();
        const mode = document.getElementById('filter-mode').value;
        const category = document.getElementById('filter-category').value;

        let filtered = allAnimals;

        if (search) {
            filtered = filtered.filter(a =>
                a.id.toLowerCase().includes(search) ||
                a.name.toLowerCase().includes(search) ||
                (a.scientificName && a.scientificName.toLowerCase().includes(search))
            );
        }

        if (mode) {
            filtered = filtered.filter(a => a.arMode === mode);
        }

        if (category) {
            filtered = filtered.filter(a => a.category === category);
        }

        renderAnimals(filtered);
    }


    async function deleteAnimal(id, name) {
        if (!confirm(`¿Estás seguro de eliminar "${name}"?\n\nEsta acción no se puede deshacer.`)) {
            return;
        }

        try {
            const response = await fetch('../api/animals/delete.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });

            const data = await response.json();

            if (data.success) {
                showNotification('Animal eliminado correctamente', 'success');
                loadAnimals();
            } else {
                showNotification(data.error || 'Error al eliminar', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('Error de conexión', 'error');
        }
    }

    // Event Listeners
    document.getElementById('search').addEventListener('input', applyFilters);
    document.getElementById('filter-mode').addEventListener('change', applyFilters);
    document.getElementById('filter-category').addEventListener('change', applyFilters);

    loadData();
    </script>

<?php elseif ($action === 'create' || $action === 'edit'): ?>
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <div class="page-header">
        <h1><?= $action === 'create' ? 'Nuevo Animal' : 'Editar Animal' ?></h1>
        <a href="animals.php" class="btn btn-cancel">← Volver</a>
    </div>

    <?php if ($action === 'edit'): ?>
    <div style="background: white; padding: 15px 25px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 20px;">
        <span style="font-weight: 600; color: #2c3e50; font-size: 14px;">🌐 Idioma:</span>
        <select id="language-selector" style="padding: 6px 10px; border: 2px solid #dfe6e9; border-radius: 6px; font-size: 13px; min-width: 130px;">
            <!-- Opciones cargadas dinámicamente desde languages.json -->
        </select>
        <small style="color: #7f8c8d; font-size: 12px;">Los campos con <span style="color: #3498db;">★</span> son traducibles</small>
    </div>
    <?php endif; ?>

    <form id="animal-form" enctype="multipart/form-data">
        <input type="hidden" name="action" value="<?= $action ?>">
        <?php if ($action === 'edit'): ?>
            <input type="hidden" name="original_id" value="<?= htmlspecialchars($animalId) ?>">
        <?php endif; ?>

        <!-- Información Básica -->
        <div class="form-section">
            <h2>📝 Información Básica</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label>ID* <small>(solo minúsculas, sin espacios)</small></label>
                    <input type="text" name="id" pattern="[a-z]+" required
                           <?= $action === 'edit' ? 'readonly' : '' ?>>
                </div>
                <div class="form-group">
                    <label>Categoría*</label>
                    <select name="category" id="category-select" required>
                        <!-- Opciones cargadas dinámicamente -->
                    </select>
                </div>
                <div class="form-group translatable-field">
                    <label><span class="translatable-indicator">★</span> Nombre*</label>
                    <input type="text" name="name" class="translatable" data-field="name" required>
                </div>
                <div class="form-group">
                    <label>Nombre Científico*</label>
                    <input type="text" name="scientificName" required>
                </div>
                <div class="form-group">
                    <label>Emoji/Icono</label>
                    <div class="emoji-picker-container">
                        <input type="text" name="icon" id="emoji-input" readonly placeholder="Selecciona un emoji">
                        <button type="button" id="emoji-picker-btn" class="btn btn-secondary">🎨</button>
                        <div id="emoji-panel" class="emoji-panel" style="display: none;">
                            <!-- Emojis will be populated here -->
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group translatable-field">
                <label><span class="translatable-indicator">★</span> Descripción Corta</label>
                <textarea name="description" class="translatable" data-field="short_description" rows="3"></textarea>
            </div>
        </div>

        <!-- Configuración AR -->
        <div class="form-section">
            <h2>🎯 Configuración AR</h2>

            <div class="form-group">
                <label>Modo AR</label>
                <select name="arMode" id="arMode">
                    <option value="marker">Solo Marcador</option>
                    <option value="gps">Solo GPS</option>
                    <option value="hybrid">Híbrido (GPS + Marcador)</option>
                </select>
            </div>

            <div id="marker-config">
                <h3>🎯 Configuración de Marcador</h3>
                <div class="form-checkbox">
                    <input type="checkbox" name="marker_enabled" id="marker-enabled" checked>
                    <label for="marker-enabled">Habilitar Marcador</label>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Tipo de Marcador</label>
                        <select name="marker_type">
                            <option value="mind">MindAR (.mind)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Archivo de Marcador</label>
                        <input type="text" name="marker_file" placeholder="animal.mind">
                    </div>
                </div>
            </div>

            <div id="gps-config">
                <h3>📍 Configuración GPS</h3>
                <div class="form-checkbox">
                    <input type="checkbox" name="gps_enabled" id="gps-enabled">
                    <label for="gps-enabled">Habilitar GPS</label>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Latitud</label>
                        <input type="number" name="gps_latitude" step="0.000001" placeholder="-41.4693">
                    </div>
                    <div class="form-group">
                        <label>Longitud</label>
                        <input type="number" name="gps_longitude" step="0.000001" placeholder="-72.9424">
                    </div>
                    <div class="form-group">
                        <label>Radio (metros)</label>
                        <input type="number" name="gps_radius" value="50">
                    </div>
                </div>
            </div>
        </div>

        <!-- Archivos del Modelo -->
        <div class="form-section">
            <h2>📂 Archivos del Modelo</h2>
            <p style="margin-bottom: 20px; color: #7f8c8d;">
                Gestiona todos los archivos asociados a este animal. Si subes un nuevo archivo, reemplazará al existente.
            </p>

            <div class="file-upload-grid">
                <!-- Imagen Principal -->
                <div class="form-group file-upload-group">
                    <label>Imagen Principal</label>
                    <img src="../assets/images/map-placeholder.svg" id="preview-image-file" class="file-preview-image">
                    <div class="file-info">
                        <span id="current-image-file" class="current-file-name"></span>
                        <a id="download-image-file" href="#" class="btn btn-secondary btn-small" download>Descargar</a>
                    </div>
                    <input type="file" name="image_file" accept="image/png, image/jpeg" data-preview-id="preview-image-file">
                </div>

                <!-- Miniatura -->
                <div class="form-group file-upload-group">
                    <label>Miniatura</label>
                    <img src="../assets/images/map-placeholder.svg" id="preview-thumbnail-file" class="file-preview-image">
                    <div class="file-info">
                        <span id="current-thumbnail-file" class="current-file-name"></span>
                        <a id="download-thumbnail-file" href="#" class="btn btn-secondary btn-small" download>Descargar</a>
                    </div>
                    <input type="file" name="thumbnail_file" accept="image/png, image/jpeg" data-preview-id="preview-thumbnail-file">
                </div>

                <!-- Silueta -->
                <div class="form-group file-upload-group">
                    <label>Silueta</label>
                    <img src="../assets/images/map-placeholder.svg" id="preview-silhouette-file" class="file-preview-image">
                    <div class="file-info">
                        <span id="current-silhouette-file" class="current-file-name"></span>
                        <a id="download-silhouette-file" href="#" class="btn btn-secondary btn-small" download>Descargar</a>
                    </div>
                    <input type="file" name="silhouette_file" accept="image/svg+xml" data-preview-id="preview-silhouette-file">
                </div>

                <!-- Archivo MIND -->
                <div class="form-group file-upload-group">
                    <label>Archivo de Marcador (.mind)</label>
                    <div class="file-info">
                        <span id="current-mind-file" class="current-file-name"></span>
                        <a id="download-mind-file" href="#" class="btn btn-secondary btn-small" download>Descargar</a>
                    </div>
                    <input type="file" name="mind_file">
                </div>
                
                <!-- Archivo de Audio -->
                <div class="form-group file-upload-group">
                    <label>Archivo de Audio</label>
                    <div class="file-info">
                        <span id="current-audio-file" class="current-file-name"></span>
                        <a id="download-audio-file" href="#" class="btn btn-secondary btn-small" download>Descargar</a>
                    </div>
                    <input type="file" name="audio_file" accept="audio/mpeg">
                </div>
                
                <!-- Modelo GLB -->
                <div class="form-group file-upload-group">
                    <label>Modelo 3D (.glb)</label>
                    <div class="file-info">
                        <span id="current-glb-file" class="current-file-name"></span>
                        <a id="download-glb-file" href="#" class="btn btn-secondary btn-small" download>Descargar</a>
                    </div>
                    <input type="file" name="glb_file" accept=".glb">
                </div>

                <!-- Modelo USDZ -->
                <div class="form-group file-upload-group">
                    <label>Modelo 3D iOS (.usdz)</label>
                    <div class="file-info">
                        <span id="current-usdz-file" class="current-file-name"></span>
                        <a id="download-usdz-file" href="#" class="btn btn-secondary btn-small" download>Descargar</a>
                    </div>
                    <input type="file" name="usdz_file" accept=".usdz">
                </div>
            </div>
        </div>

        <!-- Información Adicional -->
        <div class="form-section">
            <h2>ℹ️ Información Adicional</h2>
            <div class="form-grid">
                <div class="form-group translatable-field">
                    <label><span class="translatable-indicator">★</span> Hábitat</label>
                    <input type="text" name="info_habitat" class="translatable" data-field="habitat" placeholder="Bosques templados, montañas...">
                </div>
                <div class="form-group translatable-field">
                    <label><span class="translatable-indicator">★</span> Dieta</label>
                    <input type="text" name="info_diet" class="translatable" data-field="diet" placeholder="Carnívoro, Herbívoro...">
                </div>
                <div class="form-group translatable-field">
                    <label><span class="translatable-indicator">★</span> Estado de Conservación</label>
                    <input type="text" name="info_status" class="translatable" data-field="status" placeholder="En peligro, Vulnerable...">
                </div>
                <div class="form-group translatable-field">
                    <label><span class="translatable-indicator">★</span> Wikipedia URL</label>
                    <input type="url" name="info_wikipedia" class="translatable" data-field="wikipedia" placeholder="https://es.wikipedia.org/wiki/...">
                </div>
            </div>

            <div class="form-group">
                <label>🎬 Video URL <small>(YouTube, Vimeo, etc. - usar URL embebida)</small></label>
                <input type="url" name="video_url" placeholder="https://www.youtube.com/embed/...">
                <small style="display: block; margin-top: 5px; color: #7f8c8d;">
                    Para YouTube: Usar formato https://www.youtube.com/embed/ID_DEL_VIDEO
                </small>
            </div>
        </div>

        <!-- Descripción Detallada -->
        <div class="form-section">
            <h2>📄 Descripción Detallada (HTML)</h2>
            <div class="form-group translatable-field">
                <label><span class="translatable-indicator">★</span> Contenido</label>
                <textarea name="detailed_description" id="detailed-description" class="translatable" data-field="detailed_description" rows="10"
                          placeholder="Puedes usar HTML: <h3>Título</h3>, <p>Párrafo</p>, <strong>Negrita</strong>"></textarea>
            </div>
        </div>

        <!-- Botones -->
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Guardar Animal</button>
            <a href="animals.php" class="btn btn-cancel">✕ Cancelar</a>
        </div>
    </form>


    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
    <script src="js/animal-form.js"></script>

    <style>
    .translatable-indicator {
        color: #3498db;
        margin-right: 3px;
        font-size: 12px;
    }
    .translatable-field {
        position: relative;
    }
    .translatable-field.changed {
        background: #fff9e6;
        padding: 10px;
        border-radius: 6px;
        margin-bottom: 10px;
    }
    </style>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
