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
    </div>

    <table class="animals-table">
        <thead>
            <tr>
                <th>Miniatura</th>
                <th>ID</th>
                <th>Nombre</th>
                <th>Nombre Científico</th>
                <th>Modo AR</th>
                <th>Archivos</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="animals-tbody">
            <tr>
                <td colspan="7" class="loading">Cargando animales...</td>
            </tr>
        </tbody>
    </table>

    <script>
    let allAnimals = [];

    async function loadAnimals() {
        try {
            const response = await fetch('../api/animals/list.php');
            const data = await response.json();

            if (data.success) {
                allAnimals = data.animals;
                renderAnimals(allAnimals);
            }
        } catch (error) {
            console.error('Error cargando animales:', error);
            document.getElementById('animals-tbody').innerHTML =
                '<tr><td colspan="7" style="text-align: center; color: #e74c3c;">Error al cargar animales</td></tr>';
        }
    }

    function renderAnimals(animals) {
        const tbody = document.getElementById('animals-tbody');
        tbody.innerHTML = '';

        if (animals.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: #7f8c8d;">No hay animales registrados</td></tr>';
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

            row.innerHTML = `
                <td>
                    <img src="${thumbnailUrl}"
                         alt="${animal.name}"
                         style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px;"
                         onerror="this.src='../assets/images/placeholder.png'">
                </td>
                <td><code>${animal.id}</code></td>
                <td>
                    <strong>${animal.name}</strong>
                    <div style="font-size: 20px;">${animal.icon}</div>
                </td>
                <td style="font-style: italic; color: #7f8c8d;">${animal.scientificName}</td>
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

    // Búsqueda
    document.getElementById('search').addEventListener('input', (e) => {
        const search = e.target.value.toLowerCase();
        const filtered = allAnimals.filter(a =>
            a.id.toLowerCase().includes(search) ||
            a.name.toLowerCase().includes(search) ||
            a.scientificName.toLowerCase().includes(search)
        );
        renderAnimals(filtered);
    });

    // Filtro por modo
    document.getElementById('filter-mode').addEventListener('change', (e) => {
        const mode = e.target.value;
        const filtered = mode ? allAnimals.filter(a => a.arMode === mode) : allAnimals;
        renderAnimals(filtered);
    });

    loadAnimals();
    </script>

<?php elseif ($action === 'create' || $action === 'edit'): ?>
    <div class="page-header">
        <h1><?= $action === 'create' ? 'Nuevo Animal' : 'Editar Animal' ?></h1>
        <a href="animals.php" class="btn btn-cancel">← Volver</a>
    </div>

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
                    <label>Nombre*</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Nombre Científico*</label>
                    <input type="text" name="scientificName" required>
                </div>
                <div class="form-group">
                    <label>Emoji/Icono</label>
                    <input type="text" name="icon" placeholder="🐆">
                </div>
            </div>

            <div class="form-group">
                <label>Descripción Corta</label>
                <textarea name="description" rows="3"></textarea>
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

        <!-- Configuración del Modelo 3D -->
        <div class="form-section">
            <h2>🎨 Configuración del Modelo 3D</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label>Archivo GLB</label>
                    <input type="text" name="model_glb" placeholder="animal.glb">
                </div>
                <div class="form-group">
                    <label>Archivo USDZ (iOS)</label>
                    <input type="text" name="model_usdz" placeholder="animal.usdz">
                </div>
                <div class="form-group">
                    <label>Escala (X Y Z)</label>
                    <input type="text" name="model_scale" value="0.5 0.5 0.5">
                </div>
                <div class="form-group">
                    <label>Posición (X Y Z)</label>
                    <input type="text" name="model_position" value="0 0 0">
                </div>
                <div class="form-group">
                    <label>Rotación (X Y Z)</label>
                    <input type="text" name="model_rotation" value="0 0 0">
                </div>
            </div>
        </div>

        <!-- Audio -->
        <div class="form-section">
            <h2>🔊 Audio</h2>
            <div class="form-checkbox">
                <input type="checkbox" name="audio_enabled" id="audio-enabled">
                <label for="audio-enabled">Habilitar Audio</label>
            </div>
            <div class="form-group">
                <label>Archivo de Audio</label>
                <input type="text" name="audio_file" placeholder="sound.mp3">
            </div>
        </div>

        <!-- Información Adicional -->
        <div class="form-section">
            <h2>ℹ️ Información Adicional</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label>Hábitat</label>
                    <input type="text" name="info_habitat" placeholder="Bosques templados, montañas...">
                </div>
                <div class="form-group">
                    <label>Dieta</label>
                    <input type="text" name="info_diet" placeholder="Carnívoro, Herbívoro...">
                </div>
                <div class="form-group">
                    <label>Estado de Conservación</label>
                    <input type="text" name="info_status" placeholder="En peligro, Vulnerable...">
                </div>
                <div class="form-group">
                    <label>Wikipedia URL</label>
                    <input type="url" name="info_wikipedia" placeholder="https://es.wikipedia.org/wiki/...">
                </div>
            </div>
        </div>

        <!-- Descripción Detallada -->
        <div class="form-section">
            <h2>📄 Descripción Detallada (HTML)</h2>
            <div class="form-group">
                <textarea name="detailed_description" id="detailed-description" rows="10"
                          placeholder="Puedes usar HTML: <h3>Título</h3>, <p>Párrafo</p>, <strong>Negrita</strong>"></textarea>
            </div>
        </div>

        <!-- Archivos -->
        <div class="form-section">
            <h2>📁 Nombres de Archivos</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label>Miniatura</label>
                    <input type="text" name="thumbnail" placeholder="thumbnail.png">
                </div>
                <div class="form-group">
                    <label>Silueta SVG</label>
                    <input type="text" name="silhouette" placeholder="silueta_animal.svg">
                </div>
            </div>
            <small style="color: #7f8c8d;">
                Los archivos deben subirse por separado en la sección de "Upload de Archivos"
            </small>
        </div>

        <!-- Botones -->
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Guardar Animal</button>
            <a href="animals.php" class="btn btn-cancel">✕ Cancelar</a>
        </div>
    </form>

    <script src="js/animal-form.js"></script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
