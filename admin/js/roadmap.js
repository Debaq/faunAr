document.addEventListener('DOMContentLoaded', () => {
    const roadmapContainer = document.getElementById('roadmap-container');
    const modal = document.getElementById('task-modal');
    const modalTitle = document.getElementById('modal-title');
    const taskForm = document.getElementById('task-form');
    const addTaskBtn = document.getElementById('add-task-btn');
    const cancelBtn = document.getElementById('cancel-btn');
    const taskIdField = document.getElementById('task-id');

    const statusClasses = {
        pendiente: 'status-pending',
        en_progreso: 'status-progress',
        completado: 'status-completed'
    };
    const statusText = {
        pendiente: 'Pendiente',
        en_progreso: 'En Progreso',
        completado: 'Completado'
    };

    // --- Functions ---

    const fetchTasks = async () => {
        try {
            const response = await fetch('../api/roadmap/list.php');
            const data = await response.json();
            if (data.success) {
                renderTasks(data.tasks);
            } else {
                showNotification(data.message, 'error');
            }
        } catch (error) {
            showNotification('Error de conexión al cargar tareas.', 'error');
        }
    };

    const renderTasks = (tasks) => {
        roadmapContainer.innerHTML = '';
        if (tasks.length === 0) {
            roadmapContainer.innerHTML = '<p class="no-tasks">No hay tareas en la hoja de ruta. ¡Añade una!</p>';
            return;
        }

        tasks.forEach(task => {
            const dueDate = task.due_date ? new Date(task.due_date).toLocaleDateString() : 'Sin fecha';
            const card = document.createElement('div');
            card.className = `task-card ${statusClasses[task.status] || ''}`;
            card.dataset.taskId = task.id;

            card.innerHTML = `
                <div class="task-card-header">
                    <h3>${task.title}</h3>
                    <span class="task-status">${statusText[task.status]}</span>
                </div>
                <p>${task.description || '<i>Sin descripción</i>'}</p>
                <div class="task-card-footer">
                    <span>📅 ${dueDate}</span>
                    <div class="task-actions">
                        <button class="btn btn-secondary btn-small edit-btn">Editar</button>
                        <button class="btn btn-danger btn-small delete-btn">Eliminar</button>
                    </div>
                </div>
            `;
            roadmapContainer.appendChild(card);
        });
    };

    const openModal = (task = null) => {
        taskForm.reset();
        if (task) {
            modalTitle.textContent = 'Editar Tarea';
            taskIdField.value = task.id;
            document.getElementById('task-title').value = task.title;
            document.getElementById('task-description').value = task.description;
            document.getElementById('task-status').value = task.status;
            document.getElementById('task-due-date').value = task.due_date;
        } else {
            modalTitle.textContent = 'Nueva Tarea';
            taskIdField.value = '';
        }
        modal.style.display = 'flex';
    };

    const closeModal = () => {
        modal.style.display = 'none';
    };

    const handleFormSubmit = async (e) => {
        e.preventDefault();
        const taskId = taskIdField.value;
        const data = {
            task_id: taskId,
            title: document.getElementById('task-title').value,
            description: document.getElementById('task-description').value,
            status: document.getElementById('task-status').value,
            due_date: document.getElementById('task-due-date').value
        };

        const endpoint = taskId ? '../api/roadmap/update.php' : '../api/roadmap/create.php';
        
        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();

            if (result.success) {
                showNotification(taskId ? 'Tarea actualizada' : 'Tarea creada', 'success');
                closeModal();
                fetchTasks();
            } else {
                showNotification(result.message, 'error');
            }
        } catch (error) {
            showNotification('Error de conexión al guardar.', 'error');
        }
    };
    
    const handleDelete = async (taskId) => {
        if (!confirm('¿Estás seguro de eliminar esta tarea?')) return;
        
        try {
            const response = await fetch('../api/roadmap/delete.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ task_id: taskId })
            });
            const result = await response.json();

            if (result.success) {
                showNotification('Tarea eliminada', 'success');
                fetchTasks();
            } else {
                showNotification(result.message, 'error');
            }
        } catch (error) {
            showNotification('Error de conexión al eliminar.', 'error');
        }
    };
    
    // --- Event Listeners ---

    addTaskBtn.addEventListener('click', () => openModal());
    cancelBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });
    taskForm.addEventListener('submit', handleFormSubmit);

    roadmapContainer.addEventListener('click', (e) => {
        const card = e.target.closest('.task-card');
        if (!card) return;

        const taskId = card.dataset.taskId;

        if (e.target.classList.contains('edit-btn')) {
            // Find the task data and open modal
            // This is inefficient, but simple for now
            fetch('../api/roadmap/list.php').then(res => res.json()).then(data => {
                const task = data.tasks.find(t => t.id === taskId);
                if (task) openModal(task);
            });
        }
        
        if (e.target.classList.contains('delete-btn')) {
            handleDelete(taskId);
        }
    });

    // --- Initial Load ---
    fetchTasks();
});
