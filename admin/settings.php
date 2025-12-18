<?php include 'includes/header.php'; ?>
<?php include 'includes/nav.php'; ?>

<style>
    .color-input-group { display: flex; align-items: center; gap: 10px; }
    .color-input-group input[type="color"] { width: 50px; height: 40px; padding: 2px; }
    .logo-preview { max-width: 300px; max-height: 100px; object-fit: contain; background: #f0f0f0; border: 1px solid #ccc; border-radius: 6px; margin-top: 10px; }
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
        <button type="submit" class="btn btn-primary">💾 Guardar Toda la Configuración</button>
    </div>
</form>

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
</script>

<?php include 'includes/footer.php'; ?>