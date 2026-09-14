<?php include 'includes/header.php'; ?>
<?php include 'includes/nav.php'; ?>

<div class="page-header">
    <h1>Gestión de Categorías</h1>
    <button class="btn btn-primary" onclick="addCategory()">➕ Nueva Categoría</button>
</div>

<div style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
    <strong>⚠️ Importante:</strong> Al eliminar una categoría, deberás reasignar los modelos que pertenecen a ella.
    <br><small>La primera categoría en la lista se usa como predeterminada para modelos sin categoría.</small>
</div>

<div id="categories-container">
    <!-- Categorías cargadas dinámicamente -->
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

<style>
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

<script>
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
    const modelsResponse = await fetch('../../api/animals/list.php');
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
