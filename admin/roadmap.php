<?php include 'includes/header.php'; ?>
<?php include 'includes/nav.php'; ?>

<div class="page-header">
    <h1>Hoja de Ruta del Proyecto</h1>
    <button id="add-task-btn" class="btn btn-primary">➕ Nueva Tarea</button>
</div>

<div id="roadmap-container" class="roadmap-grid">
    <!-- Las tareas se cargarán aquí -->
</div>

<!-- Modal para crear/editar tareas -->
<div id="task-modal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <h2 id="modal-title">Nueva Tarea</h2>
        <form id="task-form">
            <input type="hidden" name="task_id" id="task-id">
            
            <div class="form-group">
                <label for="task-title">Título</label>
                <input type="text" id="task-title" name="title" required>
            </div>
            
            <div class="form-group">
                <label for="task-description">Descripción</label>
                <textarea id="task-description" name="description" rows="4"></textarea>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="task-status">Estado</label>
                    <select id="task-status" name="status">
                        <option value="pendiente">Pendiente</option>
                        <option value="en_progreso">En Progreso</option>
                        <option value="completado">Completado</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="task-due-date">Fecha Límite</label>
                    <input type="date" id="task-due-date" name="due_date">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Guardar</button>
                <button type="button" id="cancel-btn" class="btn btn-cancel">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script src="js/roadmap.js"></script>

<?php include 'includes/footer.php'; ?>
