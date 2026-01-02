<?php include 'includes/header.php'; ?>
<?php include 'includes/nav.php'; ?>

<style>
    .color-input-group { display: flex; align-items: center; gap: 10px; }
    .color-input-group input[type="color"] { width: 50px; height: 40px; padding: 2px; }
    .logo-preview { max-width: 300px; max-height: 100px; object-fit: contain; background: #f0f0f0; border: 1px solid #ccc; border-radius: 6px; margin-top: 10px; }

    .category-item {
        background: white;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 15px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        display: grid;
        grid-template-columns: 50px 1fr 1fr 150px 100px 150px;
        gap: 15px;
        align-items: center;
    }

    .category-item input[type="text"] {
        padding: 8px;
        border: 2px solid #dfe6e9;
        border-radius: 6px;
        font-size: 14px;
    }

    .category-item .drag-handle {
        cursor: move;
        font-size: 24px;
        text-align: center;
    }

    .category-item.dragging {
        opacity: 0.5;
    }

    .modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
    }

    .modal-content {
        background: white;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.2);
    }

    .modal-actions {
        margin-top: 20px;
        display: flex;
        gap: 10px;
        justify-content: flex-end;
    }
</style>

<div class="page-header">
    <h1>⚙️ Configuración General</h1>
</div>

<form id="settings-form" enctype="multipart/form-data">
    <div class="form-section">
        <h2>Configuración del Sitio</h2>
        <div class="form-group">
            <label for="site-title">Título del Sitio</label>
            <input type="text" id="site-title" name="site_title">
            <small>Aparece en el título de la pestaña del portal.</small>
        </div>
        <div class="form-group">
            <label for="site-base-url">URL Base del Sitio</label>
            <input type="url" id="site-base-url" name="site_baseUrl" placeholder="https://www.tusitio.com">
            <small>Importante para la generación de códigos QR y enlaces. No incluir la barra "/" al final.</small>
        </div>
    </div>

    <div class="form-section">
        <h2>Apariencia y Contenido</h2>
        
        <div class="form-group">
            <label>Logo del Portal</label>
            <img src="" id="logo-preview" class="logo-preview" alt="Logo preview">
            <input type="file" id="logo-upload" name="logo_file" accept="image/png, image/jpeg, image/svg+xml">
            <small>Sube el logo principal. Se guardará como <code>assets/images/logo_faunar.png</code>.</small>
        </div>
        
        <div class="form-grid">
            <div class="form-group">
                <label>Color Primario (Gradiente Inicio)</label>
                <div class="color-input-group">
                    <input type="color" id="primary-color-start" name="theme_primary_gradient_start">
                    <input type="text" id="primary-color-start-hex" class="color-hex-input">
                </div>
            </div>
            <div class="form-group">
                <label>Color Primario (Gradiente Fin)</label>
                <div class="color-input-group">
                    <input type="color" id="primary-color-end" name="theme_primary_gradient_end">
                    <input type="text" id="primary-color-end-hex" class="color-hex-input">
                </div>
            </div>
            <div class="form-group">
                <label>Color de Acento</label>
                <div class="color-input-group">
                    <input type="color" id="accent-color" name="theme_accent_color">
                    <input type="text" id="accent-color-hex" class="color-hex-input">
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="footer-text">Texto del Pie de Página</label>
            <input type="text" id="footer-text" name="site_footer_text">
        </div>

        <div class="form-group">
            <label for="about-summary">Resumen Ejecutivo (Página "Acerca de")</label>
            <textarea id="about-summary" name="about_summary" rows="6"></textarea>
        </div>
    </div>
    
    <div class="form-actions">
        <button type="submit" class="btn btn-primary">💾 Guardar Configuración General</button>
    </div>
</form>

<!-- Sección de Categorías -->
<div class="form-section" style="margin-top: 30px;">
    <div class="page-header" style="margin-bottom: 20px;">
        <h2>📁 Gestión de Categorías</h2>
        <button class="btn btn-primary" onclick="addCategory()">➕ Nueva Categoría</button>
    </div>

    <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
        <strong>⚠️ Importante:</strong> Al eliminar una categoría, deberás reasignar los modelos que pertenecen a ella.
        <br><small>La primera categoría en la lista se usa como predeterminada para modelos sin categoría.</small>
    </div>

    <div id="categories-container">
        <!-- Categorías cargadas dinámicamente -->
    </div>
</div>

<!-- Modal para eliminar categoría -->
<div id="delete-modal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 500px;">
        <h3>Eliminar Categoría</h3>
        <p id="delete-message"></p>
        <div class="form-group">
            <label>Reasignar modelos a:</label>
            <select id="target-category" class="form-control">
                <!-- Opciones cargadas dinámicamente -->
            </select>
        </div>
        <div class="modal-actions">
            <button class="btn btn-danger" onclick="confirmDelete()">Eliminar y Reasignar</button>
            <button class="btn btn-cancel" onclick="closeDeleteModal()">Cancelar</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('settings-form');
    // Inputs
    const titleInput = document.getElementById('site-title');
    const baseUrlInput = document.getElementById('site-base-url');
    const logoUpload = document.getElementById('logo-upload');
    const logoPreview = document.getElementById('logo-preview');
    const colorStartInput = document.getElementById('primary-color-start');
    const colorEndInput = document.getElementById('primary-color-end');
    const accentColorInput = document.getElementById('accent-color');
    const footerTextInput = document.getElementById('footer-text');
    const aboutSummaryInput = document.getElementById('about-summary');
    
    // Sincronizar inputs de color y texto
    const syncColorInputs = (colorPicker, textInput) => {
        textInput.value = colorPicker.value.toUpperCase();
        colorPicker.addEventListener('input', () => textInput.value = colorPicker.value.toUpperCase());
        textInput.addEventListener('input', () => {
            if (/^#[0-9A-F]{6}$/i.test(textInput.value)) {
                colorPicker.value = textInput.value;
            }
        });
    };
    syncColorInputs(colorStartInput, document.getElementById('primary-color-start-hex'));
    syncColorInputs(colorEndInput, document.getElementById('primary-color-end-hex'));
    syncColorInputs(accentColorInput, document.getElementById('accent-color-hex'));

    // Cargar configuración inicial
    async function loadAllSettings() {
        try {
            // Cargar config.json
            const settingsRes = await fetch('../api/settings/get.php');
            const settingsData = await settingsRes.json();
            if (settingsData.success) {
                const config = settingsData.settings;
                titleInput.value = config.site?.title || '';
                baseUrlInput.value = config.site?.baseUrl || '';
                footerTextInput.value = config.site?.footer_text || 'TecMedHub @ 2025';
                
                colorStartInput.value = config.theme?.primary_gradient_start || '#2d5016';
                colorEndInput.value = config.theme?.primary_gradient_end || '#1a2f0a';
                accentColorInput.value = config.theme?.accent_color || '#4CAF50';
                
                // Disparar evento para actualizar los inputs de texto
                colorStartInput.dispatchEvent(new Event('input'));
                colorEndInput.dispatchEvent(new Event('input'));
                accentColorInput.dispatchEvent(new Event('input'));

                logoPreview.src = `../assets/images/logo_faunar.png?t=${new Date().getTime()}`;
            } else {
                showNotification(settingsData.message, 'error');
            }

            // Cargar about.json
            const aboutRes = await fetch('../api/about/get.php');
            const aboutData = await aboutRes.json();
            if (aboutData.success) {
                aboutSummaryInput.value = aboutData.content?.executive_summary?.paragraph || '';
            } else {
                showNotification(aboutData.message, 'error');
            }
        } catch (error) {
            showNotification('Error al cargar la configuración completa.', 'error');
        }
    }

    // Preview de logo al seleccionar
    logoUpload.addEventListener('change', () => {
        const file = logoUpload.files[0];
        if (file) {
            logoPreview.src = URL.createObjectURL(file);
        }
    });

    // Guardar configuración
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.innerHTML = '💾 Guardando...';

        const formData = new FormData();
        
        // Datos para config.json
        formData.append('site_title', titleInput.value);
        formData.append('site_baseUrl', baseUrlInput.value.replace(/\/$/, ''));
        formData.append('site_footer_text', footerTextInput.value);
        formData.append('theme_primary_gradient_start', colorStartInput.value);
        formData.append('theme_primary_gradient_end', colorEndInput.value);
        formData.append('theme_accent_color', accentColorInput.value);
        
        // Archivo de logo
        if (logoUpload.files[0]) {
            formData.append('logo_file', logoUpload.files[0]);
        }
        
        // Datos para about.json
        const aboutData = {
            executive_summary: {
                paragraph: aboutSummaryInput.value
            }
        };

        try {
            // Guardar settings y logo
            const settingsPromise = fetch('../api/settings/update.php', {
                method: 'POST',
                body: formData // Usamos FormData para el archivo
            });

            // Guardar about.json
            const aboutPromise = fetch('../api/about/update.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(aboutData)
            });

            const [settingsResult, aboutResult] = await Promise.all([
                settingsPromise.then(res => res.json()),
                aboutPromise.then(res => res.json())
            ]);

            if (settingsResult.success && aboutResult.success) {
                showNotification('Configuración guardada correctamente.', 'success');
                // Forzar recarga de preview del logo por si cambió
                logoPreview.src = `../assets/images/logo_faunar.png?t=${new Date().getTime()}`;
            } else {
                showNotification(`Error: ${settingsResult.message || ''} ${aboutResult.message || ''}`.trim(), 'error');
            }
        } catch (error) {
            showNotification('Error de conexión al guardar.', 'error');
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = '💾 Guardar Toda la Configuración';
        }
    });

    loadAllSettings();
});

// ============ GESTIÓN DE CATEGORÍAS ============
let categories = {};
let categoryToDelete = null;

async function loadCategories() {
    try {
        const response = await fetch('api/categories/list.php');
        const data = await response.json();

        if (data.success) {
            categories = data.categories;
            renderCategories();
        }
    } catch (error) {
        console.error('Error cargando categorías:', error);
        showNotification('Error al cargar categorías', 'error');
    }
}

function renderCategories() {
    const container = document.getElementById('categories-container');
    container.innerHTML = '';

    // Ordenar por order
    const sortedCategories = Object.entries(categories).sort((a, b) => a[1].order - b[1].order);

    sortedCategories.forEach(([id, category]) => {
        const item = document.createElement('div');
        item.className = 'category-item';
        item.draggable = true;
        item.dataset.id = id;

        item.innerHTML = `
            <div class="drag-handle">☰</div>
            <input type="text" value="${category.icon}" maxlength="2" onchange="updateCategory('${id}', 'icon', this.value)">
            <input type="text" value="${category.name}" onchange="updateCategory('${id}', 'name', this.value)">
            <label style="display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" ${category.enabled ? 'checked' : ''} onchange="updateCategory('${id}', 'enabled', this.checked)">
                Habilitada
            </label>
            <span style="color: #7f8c8d; font-size: 13px;">Orden: ${category.order}</span>
            <button class="btn btn-danger btn-small" onclick="deleteCategory('${id}')">🗑️ Eliminar</button>
        `;

        // Drag and drop
        item.addEventListener('dragstart', handleDragStart);
        item.addEventListener('dragover', handleDragOver);
        item.addEventListener('drop', handleDrop);
        item.addEventListener('dragend', handleDragEnd);

        container.appendChild(item);
    });
}

function updateCategory(id, field, value) {
    categories[id][field] = value;
    saveCategories();
}

function addCategory() {
    const id = prompt('ID de la nueva categoría (solo letras minúsculas, sin espacios):');

    if (!id) return;

    if (!/^[a-z]+$/.test(id)) {
        showNotification('El ID debe contener solo letras minúsculas sin espacios', 'error');
        return;
    }

    if (categories[id]) {
        showNotification('Ya existe una categoría con ese ID', 'error');
        return;
    }

    const name = prompt('Nombre de la categoría:');
    if (!name) return;

    const icon = prompt('Emoji/Icono de la categoría:', '📦');

    // Obtener el siguiente orden
    const maxOrder = Math.max(...Object.values(categories).map(c => c.order), -1);

    categories[id] = {
        name: name,
        icon: icon || '📦',
        enabled: true,
        order: maxOrder + 1
    };

    saveCategories();
    renderCategories();
}

async function deleteCategory(id) {
    const modelsResponse = await fetch('../api/animals/list.php');
    const modelsData = await modelsResponse.json();

    const modelsInCategory = modelsData.animals.filter(a => a.category === id).length;

    const message = modelsInCategory > 0
        ? `¿Estás seguro de eliminar la categoría "${categories[id].name}"?\n\nHay ${modelsInCategory} modelo(s) en esta categoría que serán reasignados.`
        : `¿Estás seguro de eliminar la categoría "${categories[id].name}"?`;

    document.getElementById('delete-message').textContent = message;

    // Llenar select con otras categorías
    const select = document.getElementById('target-category');
    select.innerHTML = '';

    Object.entries(categories).forEach(([catId, cat]) => {
        if (catId !== id) {
            const option = document.createElement('option');
            option.value = catId;
            option.textContent = `${cat.icon} ${cat.name}`;
            select.appendChild(option);
        }
    });

    // Si solo hay una categoría, no se puede eliminar
    if (select.options.length === 0) {
        showNotification('No puedes eliminar la única categoría', 'error');
        return;
    }

    categoryToDelete = id;
    document.getElementById('delete-modal').style.display = 'flex';
}

function closeDeleteModal() {
    document.getElementById('delete-modal').style.display = 'none';
    categoryToDelete = null;
}

async function confirmDelete() {
    const targetCategory = document.getElementById('target-category').value;

    if (!targetCategory) {
        showNotification('Debes seleccionar una categoría destino', 'error');
        return;
    }

    try {
        const response = await fetch('api/categories/delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                categoryId: categoryToDelete,
                targetCategoryId: targetCategory
            })
        });

        const data = await response.json();

        if (data.success) {
            showNotification(data.message, 'success');
            delete categories[categoryToDelete];
            closeDeleteModal();
            renderCategories();
        } else {
            showNotification(data.message || 'Error al eliminar', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error de conexión', 'error');
    }
}

async function saveCategories() {
    try {
        const response = await fetch('api/categories/save.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ categories })
        });

        const data = await response.json();

        if (!data.success) {
            showNotification('Error al guardar categorías', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error de conexión', 'error');
    }
}

// Drag and Drop
let draggedElement = null;

function handleDragStart(e) {
    draggedElement = this;
    this.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
}

function handleDragOver(e) {
    if (e.preventDefault) {
        e.preventDefault();
    }
    e.dataTransfer.dropEffect = 'move';
    return false;
}

function handleDrop(e) {
    if (e.stopPropagation) {
        e.stopPropagation();
    }

    if (draggedElement !== this) {
        const allItems = [...document.querySelectorAll('.category-item')];
        const draggedIndex = allItems.indexOf(draggedElement);
        const targetIndex = allItems.indexOf(this);

        // Reordenar en el DOM
        if (draggedIndex < targetIndex) {
            this.parentNode.insertBefore(draggedElement, this.nextSibling);
        } else {
            this.parentNode.insertBefore(draggedElement, this);
        }

        // Actualizar orden en el objeto
        const newOrder = [...document.querySelectorAll('.category-item')].map((el, idx) => {
            const id = el.dataset.id;
            categories[id].order = idx;
            return id;
        });

        renderCategories();
        saveCategories();
    }

    return false;
}

function handleDragEnd(e) {
    this.classList.remove('dragging');
}

loadCategories();
</script>

<?php include 'includes/footer.php'; ?>