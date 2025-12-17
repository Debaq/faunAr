<?php include 'includes/header.php'; ?>
<?php include 'includes/nav.php'; ?>

<div class="page-header">
    <h1>📱 Generador de Códigos QR</h1>
</div>

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

<script>
let animals = [];

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

loadQRs();
</script>

<?php include 'includes/footer.php'; ?>
