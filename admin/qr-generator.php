<?php include 'includes/header.php'; ?>
<?php include 'includes/nav.php'; ?>

<div class="page-header">
    <h1>📱 Generador de Códigos QR</h1>
</div>

<div class="form-section">
    <h2>Generador de QR Personalizado</h2>
    <form id="custom-qr-form">
        <div class="form-grid">
            <div class="form-group">
                <label for="animal-select">Seleccionar Animal (Opcional)</label>
                <select id="animal-select" name="animal_id">
                    <option value="">-- URL e Imagen Manual --</option>
                    <!-- Animales se cargarán aquí -->
                </select>
            </div>
            <div class="form-group">
                <label for="qr-url">URL a codificar</label>
                <input type="text" id="qr-url" name="url" placeholder="https://ejemplo.com" required>
            </div>
        </div>
        <div class="form-group">
            <label for="qr-center-image">Imagen Central (si no se selecciona animal)</label>
            <input type="file" id="qr-center-image" name="center_image" accept="image/png, image/jpeg">
        </div>

        <div class="form-group">
            <label for="qr-size-slider">Tamaño del QR (<span id="qr-size-value">90</span>%)</label>
            <input type="range" id="qr-size-slider" name="qr_size" min="50" max="98" value="90" class="slider">
        </div>

        <div class="form-group">
            <label for="icon-size-slider">Tamaño de la Imagen Central (<span id="icon-size-value">100</span>%)</label>
            <input type="range" id="icon-size-slider" name="icon_size" min="30" max="150" value="100" class="slider">
        </div>

        <div class="form-actions">
            <a href="#" id="download-qr-btn" class="btn btn-secondary" style="display: none;" download="custom_qr.png">📥 Descargar</a>
        </div>
    </form>
    
    <div id="custom-qr-preview-container" style="margin-top: 20px; text-align: center;">
        <h3 id="preview-title" style="display: none;">Preview</h3>
        <img id="custom-qr-preview" style="max-width: 300px; max-height: 300px; margin-top: 10px; border: 1px solid #ccc;">
    </div>
</div>

<hr style="margin: 40px 0; border: 1px solid var(--border-color);">

<div class="quick-actions">
    <h2>Acciones</h2>
    <button onclick="regenerateAll()" class="btn btn-primary">🔄 Regenerar Todos los QR</button>
    <button onclick="downloadAllQR()" class="btn btn-secondary">📥 Descargar Todos (ZIP)</button>
    <div style="margin-top: 15px;">
        <label>URL Base del sitio:</label>
        <input type="text" id="base-url" value="http://localhost" style="width: 300px; padding: 8px; border: 2px solid #dfe6e9; border-radius: 6px;">
    </div>
</div>

<div id="generation-progress" style="display: none; background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px;">
    <h3>Generando códigos QR...</h3>
    <div style="background: #f5f6fa; height: 30px; border-radius: 15px; overflow: hidden; margin: 15px 0;">
        <div id="qr-progress" style="height: 100%; background: linear-gradient(90deg, #2c5f2d, #97be5a); width: 0%; transition: width 0.3s;"></div>
    </div>
    <p id="qr-status"></p>
</div>

<div class="qr-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
    <div style="text-align: center; color: #7f8c8d; grid-column: 1 / -1;">
        Cargando...
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcode/build/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // --- Custom QR Generator Logic ---
    const customQrForm = document.getElementById('custom-qr-form');
    if (customQrForm) {
        const animalSelect = document.getElementById('animal-select');
        const qrUrlInput = document.getElementById('qr-url');
        const imageInput = document.getElementById('qr-center-image');
        const qrSizeSlider = document.getElementById('qr-size-slider');
        const iconSizeSlider = document.getElementById('icon-size-slider');
        const qrSizeValue = document.getElementById('qr-size-value');
        const iconSizeValue = document.getElementById('icon-size-value');
        const previewImg = document.getElementById('custom-qr-preview');
        const previewTitle = document.getElementById('preview-title');
        const downloadBtn = document.getElementById('download-qr-btn');
        let selectedAnimalImage = null;

        function debounce(func, delay) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), delay);
            };
        }

        const triggerQrGeneration = () => {
            const url = qrUrlInput.value;
            const imageFile = imageInput.files[0];
            if (!url) return;

            let imageSource = null;
            if (selectedAnimalImage && selectedAnimalImage.complete) {
                imageSource = selectedAnimalImage;
            } else if (imageFile) {
                imageSource = new Image();
                const reader = new FileReader();
                reader.onload = (event) => {
                    imageSource.onload = () => generateCustomQr(url, imageSource);
                    imageSource.src = event.target.result;
                };
                reader.readAsDataURL(imageFile);
                return;
            }
            
            if (imageSource) {
                generateCustomQr(url, imageSource);
            }
        };
        
        const debouncedGeneration = debounce(triggerQrGeneration, 300);

        function generateCustomQr(url, centerImage) {
            QRCode.toDataURL(url, { errorCorrectionLevel: 'H', width: 1024, margin: 1 }, (err, qrDataUrl) => {
                if (err) {
                    showNotification('Error generando el código QR.', 'error');
                    console.error(err);
                    return;
                }

                const qrImg = new Image();
                qrImg.onload = () => {
                    const finalSize = 1000;
                    const borderSize = 100;
                    const innerSize = finalSize - (borderSize * 2);
                    const canvas = document.createElement('canvas');
                    canvas.width = finalSize;
                    canvas.height = finalSize;
                    const ctx = canvas.getContext('2d');

                    ctx.fillStyle = 'black';
                    ctx.fillRect(0, 0, finalSize, finalSize);
                    ctx.fillStyle = 'white';
                    ctx.fillRect(borderSize, borderSize, innerSize, innerSize);

                    const qrSizePercent = parseInt(qrSizeSlider.value, 10);
                    const qrDrawSize = Math.round(innerSize * (qrSizePercent / 100));
                    const qrPos = Math.round((innerSize - qrDrawSize) / 2) + borderSize;
                    
                    ctx.drawImage(qrImg, qrPos, qrPos, qrDrawSize, qrDrawSize);

                    const iconSizePercent = parseInt(iconSizeSlider.value, 10);
                    const safeAreaSize = qrDrawSize * 0.30;
                    const iconDrawSize = Math.round(safeAreaSize * (iconSizePercent / 100));

                    const centerBgPos = Math.round((finalSize - safeAreaSize) / 2);
                    ctx.fillStyle = 'white';
                    ctx.fillRect(centerBgPos, centerBgPos, safeAreaSize, safeAreaSize);
                    
                    const centerIconPos = Math.round((finalSize - iconDrawSize) / 2);
                    ctx.drawImage(centerImage, centerIconPos, centerIconPos, iconDrawSize, iconDrawSize);

                    const finalImageURL = canvas.toDataURL('image/png');
                    previewImg.src = finalImageURL;
                    previewTitle.style.display = 'block';
                    downloadBtn.href = finalImageURL;
                    downloadBtn.style.display = 'inline-block';
                    const selectedAnimalName = animalSelect.value || 'custom';
                    downloadBtn.download = `qr_personalizado_${selectedAnimalName}.png`;
                };
                qrImg.src = qrDataUrl;
            });
        }

        async function loadAnimalsForSelect() {
            try {
                const response = await fetch('../api/get-models.php');
                const data = await response.json();
                if (data.success) {
                    for (const modelId of data.models) {
                        try {
                            const configRes = await fetch(`../models/${modelId}/config.json`);
                            const config = await configRes.json();
                            const option = document.createElement('option');
                            option.value = modelId;
                            option.textContent = config.name || modelId;
                            option.dataset.thumbnail = config.thumbnail || '';
                            animalSelect.appendChild(option);
                        } catch (e) {}
                    }
                }
            } catch (error) {
                console.error('Error loading animals for select:', error);
            }
        }

        animalSelect.addEventListener('change', async (e) => {
            const selectedOption = e.target.options[e.target.selectedIndex];
            const modelId = e.target.value;
            const thumbnail = selectedOption.dataset.thumbnail;
            
            selectedAnimalImage = null;
            if (modelId && thumbnail) {
                const baseUrl = document.getElementById('base-url').value.replace(/\/$/, "");
                qrUrlInput.value = `${baseUrl}/viewer.html?model=${modelId}`;
                imageInput.disabled = true;
                imageInput.value = '';

                const imageUrl = `../models/${modelId}/${thumbnail}`;
                try {
                    const response = await fetch(imageUrl);
                    if (!response.ok) throw new Error('Network response was not ok');
                    const blob = await response.blob();
                    const objectURL = URL.createObjectURL(blob);
                    selectedAnimalImage = new Image();
                    selectedAnimalImage.onload = () => debouncedGeneration();
                    selectedAnimalImage.onerror = () => {
                        showNotification(`No se pudo cargar la imagen para ${selectedOption.textContent}.`, 'error');
                        imageInput.disabled = false;
                    };
                    selectedAnimalImage.src = objectURL;
                } catch (error) {
                    showNotification(`No se pudo cargar la imagen para ${selectedOption.textContent}.`, 'error');
                    imageInput.disabled = false;
                    debouncedGeneration();
                }
            } else {
                qrUrlInput.value = '';
                imageInput.disabled = false;
                debouncedGeneration();
            }
        });
        
        qrUrlInput.addEventListener('input', debouncedGeneration);
        imageInput.addEventListener('change', debouncedGeneration);
        qrSizeSlider.addEventListener('input', (e) => { qrSizeValue.textContent = e.target.value; debouncedGeneration(); });
        iconSizeSlider.addEventListener('input', (e) => { iconSizeValue.textContent = e.target.value; debouncedGeneration(); });

        loadAnimalsForSelect();
    }
});
</script>

<script>
let animals = [];

async function loadSettingsAndQRs() {
    // Primero, cargar la configuración para obtener la URL base
    try {
        const settingsResponse = await fetch('../api/settings/get.php');
        const settingsData = await settingsResponse.json();
        if (settingsData.success && settingsData.settings.site.baseUrl) {
            document.getElementById('base-url').value = settingsData.settings.site.baseUrl;
        }
    } catch (error) {
        console.error('Error fetching settings for QR generator:', error);
    }
    
    // Luego, cargar los QRs
    await loadQRs();
}


async function loadQRs() {
    try {
        const response = await fetch('../api/get-models.php');
        const data = await response.json();

        if (data.success) {
            const grid = document.querySelector('.qr-grid');
            grid.innerHTML = '';

            for (const modelId of data.models) {
                const configRes = await fetch(`../models/${modelId}/config.json`);
                const config = await configRes.json();

                const card = document.createElement('div');
                card.style.cssText = 'background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center;';

                const qrPath = `../models/${modelId}/qr_${modelId}.png`;
                const qrExists = await checkFileExists(qrPath);

                card.innerHTML = `
                    <h3 style="margin-bottom: 10px;">${config.name}</h3>
                    <p style="color: #7f8c8d; font-size: 14px; margin-bottom: 15px;">${config.scientificName}</p>
                    ${qrExists ?
                        `<img src="${qrPath}?t=${Date.now()}" alt="QR ${config.name}" style="width: 200px; height: 200px; margin: 15px auto; display: block; border: 2px solid #dfe6e9; border-radius: 8px;">` :
                        `<div style="width: 200px; height: 200px; margin: 15px auto; background: #f5f6fa; display: flex; align-items: center; justify-content: center; border-radius: 8px; color: #7f8c8d;">No generado</div>`
                    }
                    <button onclick="generateSingle('${modelId}', '${config.name}')" class="btn btn-primary btn-small">
                        ${qrExists ? '🔄 Regenerar' : '✨ Generar'}
                    </button>
                    ${qrExists ?
                        `<a href="${qrPath}" download="qr_${modelId}.png" class="btn btn-secondary btn-small" style="margin-left: 5px;">📥 Descargar</a>` :
                        ''
                    }
                `;

                grid.appendChild(card);
            }

            animals = data.models;
        }
    } catch (error) {
        console.error('Error cargando QRs:', error);
    }
}

async function checkFileExists(url) {
    try {
        const response = await fetch(url, { method: 'HEAD' });
        return response.ok;
    } catch {
        return false;
    }
}

async function generateSingle(animalId, animalName) {
    const baseUrl = document.getElementById('base-url').value;

    try {
        const response = await fetch('../api/qr/generate.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ animalId, baseUrl })
        });

        const data = await response.json();

        if (data.success) {
            showNotification(`QR generado para ${animalName}`, 'success');
            loadQRs();
        } else {
            showNotification(data.error || 'Error al generar QR', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error de conexión', 'error');
    }
}

async function regenerateAll() {
    if (!confirm('¿Regenerar todos los códigos QR?\n\nEsto puede tardar unos minutos.')) {
        return;
    }

    const baseUrl = document.getElementById('base-url').value;
    const progressDiv = document.getElementById('generation-progress');
    const progressBar = document.getElementById('qr-progress');
    const statusText = document.getElementById('qr-status');

    progressDiv.style.display = 'block';
    progressBar.style.width = '0%';
    statusText.textContent = 'Iniciando...';

    try {
        const response = await fetch('../api/qr/regenerate-all.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ baseUrl })
        });

        const data = await response.json();

        progressBar.style.width = '100%';

        if (data.success) {
            statusText.textContent = `Completado: ${data.successCount} exitosos, ${data.errorCount} errores de ${data.total} total`;
            showNotification(`QRs regenerados: ${data.successCount}/${data.total}`, 'success');

            setTimeout(() => {
                progressDiv.style.display = 'none';
                loadQRs();
            }, 3000);
        } else {
            statusText.textContent = 'Error al regenerar QRs';
            showNotification(data.error || 'Error al regenerar', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        statusText.textContent = 'Error de conexión';
        showNotification('Error de conexión', 'error');
    }
}

function downloadAllQR() {
    showNotification('Esta funcionalidad requiere crear un archivo ZIP en el servidor', 'error');
}

loadSettingsAndQRs();
</script>

<?php include 'includes/footer.php'; ?>
