<?php include 'includes/header.php'; ?>
<?php include 'includes/nav.php'; ?>

<div class="page-header">
    <h1>🌐 Gestión de Traducciones</h1>
    <button class="btn btn-primary" id="save-btn" style="display: none;">💾 Guardar</button>
</div>

<!-- Pestañas -->
<div class="tabs-container">
    <div class="tabs-header">
        <button class="tab-btn active" data-tab="config">⚙️ Configuración</button>
        <button class="tab-btn" data-tab="system">🔧 Sistema</button>
        <button class="tab-btn" data-tab="stats">📊 Estadísticas</button>
    </div>

    <!-- Pestaña: Configuración -->
    <div class="tab-content active" id="tab-config">
        <div class="tab-section">
            <h2>⚙️ Configuración de Idiomas</h2>
            <p class="description">
                Activa o desactiva los idiomas disponibles en la aplicación. Los idiomas desactivados no se mostrarán en el selector de idiomas.
            </p>

            <div id="languages-list" class="languages-grid">
                <div style="text-align: center; padding: 30px; color: #7f8c8d;">
                    Cargando idiomas...
                </div>
            </div>
        </div>
    </div>

    <!-- Pestaña: Sistema -->
    <div class="tab-content" id="tab-system">
        <div class="tab-section">
            <h2>🔧 Traducciones del Sistema</h2>
            <p class="description">
                Gestiona las traducciones de la interfaz del sistema (botones, mensajes, etc.). Selecciona un idioma para editar sus traducciones.
            </p>

            <div class="system-editor">
                <div class="language-selector-container">
                    <label for="system-lang-select">Idioma a editar:</label>
                    <select id="system-lang-select">
                        <option value="">Cargando...</option>
                    </select>
                </div>

                <div id="system-translations">
                    <div style="text-align: center; padding: 30px; color: #7f8c8d;">
                        Selecciona un idioma para editar
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pestaña: Estadísticas -->
    <div class="tab-content" id="tab-stats">
        <div class="tab-section">
            <h2>📊 Estadísticas de Traducción</h2>
            <p class="description">
                Progreso de traducción de cada animal. Usa el botón "🤖 Traducir con LLM" para copiar el prompt y pegarlo en Claude/ChatGPT.
            </p>

            <div id="animals-progress">
                <div style="text-align: center; padding: 30px; color: #7f8c8d;">
                    Cargando progreso...
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de traducción LLM -->
<div id="translation-modal" class="translation-modal">
    <div class="translation-modal-content">
        <div class="translation-modal-header">
            <h3>🤖 Traducir con LLM</h3>
            <span class="translation-modal-close">&times;</span>
        </div>
        <div class="translation-modal-body">
            <div class="translation-step">
                <h4>Paso 1: Copia este prompt y pégalo en Claude/ChatGPT</h4>
                <textarea id="llm-prompt" readonly></textarea>
                <div class="translation-actions">
                    <button id="copy-prompt-btn" class="btn btn-secondary">📋 Copiar Prompt</button>
                </div>
            </div>

            <div class="translation-step">
                <h4>Paso 2: Selecciona el idioma al que tradujiste</h4>
                <select id="modal-target-lang">
                    <option value="">-- Selecciona idioma --</option>
                </select>
            </div>

            <div class="translation-step">
                <h4>Paso 3: Pega la respuesta del LLM o sube un archivo</h4>
                <textarea id="llm-response" placeholder="Pega aquí la respuesta completa del LLM..."></textarea>
                <div class="translation-actions">
                    <div class="file-upload-wrapper">
                        <input type="file" id="llm-file" accept=".txt,.json">
                        <button id="load-file-btn" class="btn btn-secondary">📂 Cargar archivo</button>
                    </div>
                </div>
            </div>

            <div class="translation-actions" style="justify-content: flex-end; border-top: 2px solid #dfe6e9; padding-top: 15px;">
                <button id="cancel-translation-btn" class="btn btn-cancel">Cancelar</button>
                <button id="process-translation-btn" class="btn btn-primary">✅ Aplicar Traducción</button>
            </div>
        </div>
    </div>
</div>

<script src="js/translations-manager.js"></script>

<style>
/* Pestañas */
.tabs-container {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.tabs-header {
    display: flex;
    background: #f5f6fa;
    border-bottom: 2px solid #dfe6e9;
}

.tab-btn {
    flex: 1;
    padding: 15px 20px;
    background: none;
    border: none;
    cursor: pointer;
    font-size: 15px;
    font-weight: 600;
    color: #7f8c8d;
    transition: all 0.3s;
    border-bottom: 3px solid transparent;
}

.tab-btn:hover {
    background: #ecf0f1;
    color: #2c3e50;
}

.tab-btn.active {
    background: white;
    color: #2c5f2d;
    border-bottom-color: #2c5f2d;
}

.tab-content {
    display: none;
    padding: 25px;
}

.tab-content.active {
    display: block;
}

.tab-section h2 {
    margin-top: 0;
    color: #2c3e50;
    font-size: 20px;
    margin-bottom: 10px;
}

.tab-section .description {
    color: #7f8c8d;
    margin-bottom: 25px;
    line-height: 1.6;
}

/* Grid de idiomas */
.languages-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 10px;
}

/* Editor de sistema */
.system-editor {
    max-width: 1200px;
}

.language-selector-container {
    margin-bottom: 25px;
    padding: 15px;
    background: #f5f6fa;
    border-radius: 6px;
}

.language-selector-container label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #2c3e50;
}

.language-selector-container select {
    width: 100%;
    max-width: 300px;
    padding: 10px;
    border: 2px solid #dfe6e9;
    border-radius: 6px;
    font-size: 14px;
    cursor: pointer;
}

.language-selector-container select:focus {
    outline: none;
    border-color: #2c5f2d;
}

/* Secciones de traducción */
.translation-section {
    margin-bottom: 30px;
    padding: 20px;
    background: #f5f6fa;
    border-radius: 6px;
}

.translation-section h3 {
    margin: 0 0 15px 0;
    color: #2c5f2d;
    font-size: 16px;
    padding-bottom: 10px;
    border-bottom: 2px solid #dfe6e9;
}

.translation-field {
    margin-bottom: 20px;
}

.translation-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: #2c3e50;
    font-size: 14px;
}

.translation-field input,
.translation-field textarea {
    width: 100%;
    padding: 10px;
    border: 2px solid #dfe6e9;
    border-radius: 6px;
    font-size: 14px;
    font-family: inherit;
    background: white;
}

.translation-field input:focus,
.translation-field textarea:focus {
    outline: none;
    border-color: #2c5f2d;
}

.translation-field textarea {
    min-height: 100px;
    resize: vertical;
}

.translation-field small {
    display: block;
    color: #7f8c8d;
    font-size: 12px;
    margin-top: 3px;
}

.translation-field.reference {
    background: #e8f5e9;
    padding: 10px;
    border-radius: 4px;
    margin-bottom: 10px;
}

.translation-field.reference label {
    color: #27ae60;
}

/* Modal de traducción */
.translation-modal {
    display: none;
    position: fixed;
    z-index: 10000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
}

.translation-modal.active {
    display: flex;
    align-items: center;
    justify-content: center;
}

.translation-modal-content {
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 700px;
    max-height: 85vh;
    overflow-y: auto;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
}

.translation-modal-header {
    padding: 20px 25px;
    border-bottom: 2px solid #dfe6e9;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f5f6fa;
    border-radius: 12px 12px 0 0;
}

.translation-modal-header h3 {
    margin: 0;
    color: #2c3e50;
    font-size: 18px;
}

.translation-modal-close {
    font-size: 28px;
    font-weight: bold;
    color: #7f8c8d;
    cursor: pointer;
    line-height: 1;
    transition: color 0.2s;
}

.translation-modal-close:hover {
    color: #e74c3c;
}

.translation-modal-body {
    padding: 25px;
}

.translation-step {
    margin-bottom: 20px;
}

.translation-step h4 {
    margin: 0 0 10px 0;
    color: #2c3e50;
    font-size: 14px;
    font-weight: 600;
}

.translation-step textarea {
    width: 100%;
    padding: 12px;
    border: 2px solid #dfe6e9;
    border-radius: 6px;
    font-family: 'Courier New', monospace;
    font-size: 12px;
    resize: vertical;
    min-height: 120px;
}

.translation-step select {
    width: 100%;
    padding: 10px;
    border: 2px solid #dfe6e9;
    border-radius: 6px;
    font-size: 14px;
}

.translation-step .file-upload-wrapper {
    display: flex;
    gap: 10px;
    align-items: center;
}

.translation-step input[type="file"] {
    flex: 1;
    padding: 8px;
    border: 2px dashed #dfe6e9;
    border-radius: 6px;
    font-size: 13px;
    cursor: pointer;
}

.translation-step input[type="file"]:hover {
    border-color: #2c5f2d;
    background: #f5f6fa;
}

.translation-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}
</style>

<?php include 'includes/footer.php'; ?>
