<?php include 'includes/header.php'; ?>
<?php include 'includes/nav.php'; ?>

<div class="page-header">
    <h1>🌐 Gestión de Traducciones</h1>
    <div class="language-selector">
        <label>Idioma:</label>
        <select id="current-language">
            <option value="es">🇨🇱 Español</option>
            <option value="en">🇺🇸 English</option>
        </select>
        <button class="btn btn-primary" onclick="saveTranslations()">💾 Guardar Cambios</button>
    </div>
</div>

<div class="translation-editor" style="display: grid; grid-template-columns: 250px 1fr; gap: 20px;">
    <div class="editor-sidebar" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); height: fit-content;">
        <h3>Secciones</h3>
        <ul id="section-list" style="list-style: none; padding: 0; margin: 0;">
            <li data-section="portal" class="active" style="padding: 10px; cursor: pointer; border-radius: 6px; margin-bottom: 5px;">Portal</li>
            <li data-section="viewer" style="padding: 10px; cursor: pointer; border-radius: 6px; margin-bottom: 5px;">Visor AR</li>
            <li data-section="animals" style="padding: 10px; cursor: pointer; border-radius: 6px;">Animales</li>
        </ul>
    </div>

    <div class="editor-main" style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <div id="translation-fields" class="translation-fields">
            <div style="text-align: center; color: #7f8c8d; padding: 40px;">
                Cargando traducciones...
            </div>
        </div>
    </div>
</div>

<div class="translation-stats" style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-top: 20px;">
    <h3>Estadísticas</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 15px;">
        <div style="text-align: center; padding: 15px; background: #f5f6fa; border-radius: 6px;">
            <strong id="total-keys" style="font-size: 32px; color: #2c5f2d; display: block;">0</strong>
            <span style="color: #7f8c8d; font-size: 14px;">Claves totales</span>
        </div>
        <div style="text-align: center; padding: 15px; background: #f5f6fa; border-radius: 6px;">
            <strong id="translated-keys" style="font-size: 32px; color: #27ae60; display: block;">0</strong>
            <span style="color: #7f8c8d; font-size: 14px;">Traducidas</span>
        </div>
        <div style="text-align: center; padding: 15px; background: #f5f6fa; border-radius: 6px;">
            <strong id="missing-keys" style="font-size: 32px; color: #e74c3c; display: block;">0</strong>
            <span style="color: #7f8c8d; font-size: 14px;">Faltantes</span>
        </div>
    </div>
</div>

<script src="js/translation-editor.js"></script>

<style>
.editor-sidebar li:hover {
    background: #f5f6fa;
}
.editor-sidebar li.active {
    background: #2c5f2d;
    color: white;
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
}
.translation-field input:focus,
.translation-field textarea:focus {
    outline: none;
    border-color: #2c5f2d;
}
.translation-field small {
    display: block;
    color: #7f8c8d;
    font-size: 12px;
    margin-top: 3px;
}
</style>

<?php include 'includes/footer.php'; ?>
