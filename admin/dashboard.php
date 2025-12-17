<?php include 'includes/header.php'; ?>
<?php include 'includes/nav.php'; ?>

<div class="page-header">
    <h1>Dashboard</h1>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <h3>Animales</h3>
        <div class="stat-number" id="total-animals">0</div>
    </div>
    <div class="stat-card">
        <h3>Archivos</h3>
        <div class="stat-number" id="total-files">0</div>
    </div>
    <div class="stat-card">
        <h3>Idiomas</h3>
        <div class="stat-number" id="total-languages">2</div>
    </div>
    <div class="stat-card">
        <h3>Con GPS</h3>
        <div class="stat-number" id="total-gps">0</div>
    </div>
</div>

<div class="quick-actions">
    <h2>Acciones Rápidas</h2>
    <a href="animals.php?action=create" class="btn btn-primary">➕ Nuevo Animal</a>
    <a href="qr-generator.php" class="btn btn-secondary">🔄 Regenerar QR</a>
    <a href="translations.php" class="btn btn-secondary">🌐 Traducciones</a>
    <a href="../index.html" class="btn btn-secondary" target="_blank">👁️ Ver Portal</a>
</div>

<div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h2 style="margin-bottom: 20px;">Últimos Animales</h2>
    <table id="recent-animals">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Nombre Científico</th>
                <th>Modo AR</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="5" class="loading">Cargando...</td>
            </tr>
        </tbody>
    </table>
</div>

<script>
async function loadStats() {
    try {
        const response = await fetch('../api/get-models.php');
        const data = await response.json();

        if (data.success) {
            document.getElementById('total-animals').textContent = data.count;

            // Cargar todos los configs para estadísticas
            let totalFiles = 0;
            let totalGps = 0;

            for (const modelId of data.models) {
                const configRes = await fetch(`../models/${modelId}/config.json`);
                const config = await configRes.json();

                if (config.gps?.enabled) totalGps++;

                // Contar archivos en la carpeta
                totalFiles += 5; // Estimado: config, glb, mind, sound, thumbnail
            }

            document.getElementById('total-files').textContent = totalFiles;
            document.getElementById('total-gps').textContent = totalGps;

            // Cargar tabla de animales recientes
            await loadRecentAnimals(data.models.slice(0, 5));
        }
    } catch (error) {
        console.error('Error cargando estadísticas:', error);
    }
}

async function loadRecentAnimals(modelIds) {
    const tbody = document.querySelector('#recent-animals tbody');
    tbody.innerHTML = '';

    for (const modelId of modelIds) {
        try {
            const response = await fetch(`../models/${modelId}/config.json`);
            const config = await response.json();

            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${config.id}</td>
                <td>${config.name}</td>
                <td style="font-style: italic; color: #7f8c8d;">${config.scientificName}</td>
                <td>
                    <span style="display: inline-block; padding: 4px 8px; background: ${config.arMode === 'marker' ? '#3498db' : config.arMode === 'gps' ? '#27ae60' : '#f39c12'}; color: white; border-radius: 4px; font-size: 12px;">
                        ${config.arMode.toUpperCase()}
                    </span>
                </td>
                <td>
                    <a href="animals.php?action=edit&id=${config.id}" class="btn btn-primary btn-small">Editar</a>
                </td>
            `;
            tbody.appendChild(row);
        } catch (error) {
            console.error(`Error cargando ${modelId}:`, error);
        }
    }

    if (tbody.children.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: #7f8c8d;">No hay animales registrados</td></tr>';
    }
}

// Cargar estadísticas al cargar la página
document.addEventListener('DOMContentLoaded', loadStats);
</script>

<?php include 'includes/footer.php'; ?>
