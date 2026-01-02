<?php include 'includes/header.php'; ?>
<?php include 'includes/nav.php'; ?>

<div class="page-header">
    <h1>Gestión de Variantes</h1>
</div>

<div style="background: #e8f4f8; padding: 15px 20px; margin-bottom: 20px; border-radius: 8px; border-left: 4px solid #3498db;">
    <strong>ℹ️ Variantes:</strong> Diferentes versiones del mismo animal (ej: normal, navidad, verano). Permite cambiar modelos 3D, audio y marcadores sin crear animales duplicados.
</div>

<!-- Selector de animal -->
<div class="form-section">
    <h2>📦 Seleccionar Animal</h2>
    <select id="animal-selector" style="width: 100%; padding: 10px; border: 2px solid #dfe6e9; border-radius: 6px; font-size: 14px;">
        <option value="">Cargando animales...</option>
    </select>
</div>

<!-- Info del animal seleccionado -->
<div id="animal-info" style="display: none;">
    <div class="form-section">
        <h2>🎨 Variantes de <span id="animal-name-display"></span></h2>

        <div style="background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <h3>Variante Activa Global:</h3>
            <select id="active-variant-selector" style="padding: 8px 12px; border: 2px solid #3498db; border-radius: 6px; font-size: 14px; margin-right: 10px;">
                <option value="default">default</option>
            </select>
            <button onclick="saveActiveVariant()" class="btn btn-primary">💾 Guardar Variante Activa</button>
            <p style="margin-top: 10px; color: #7f8c8d; font-size: 13px;">
                Esta es la variante que se usará por defecto si no se especifica otra en la instancia.
            </p>
        </div>

        <div style="background: #fff; padding: 20px; border-radius: 8px;">
            <h3>Editor de Variantes (JSON):</h3>
            <p style="color: #7f8c8d; margin-bottom: 15px; font-size: 13px;">
                Edita directamente el JSON de variantes. Asegúrate de mantener el formato correcto.
            </p>
            <textarea id="variants-editor" rows="20" style="width: 100%; font-family: monospace; padding: 15px; border: 2px solid #dfe6e9; border-radius: 6px; font-size: 12px;"></textarea>
            <div style="margin-top: 15px;">
                <button onclick="saveVariants()" class="btn btn-primary">💾 Guardar Cambios</button>
                <button onclick="loadAnimalVariants()" class="btn btn-secondary">🔄 Recargar</button>
                <a href="https://jsonlint.com/" target="_blank" class="btn btn-secondary">✓ Validar JSON</a>
            </div>
        </div>
    </div>

    <!-- Guía rápida -->
    <div class="form-section">
        <h3>📖 Guía Rápida</h3>
        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; font-size: 13px;">
            <p><strong>Estructura de variantes:</strong></p>
            <pre style="background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 6px; overflow-x: auto;">{
  "default": {
    "model": {
      "glb": "pudu.glb",
      "scale": "1 1 1"
    },
    "audio": {
      "enabled": true,
      "file": "sound.mp3"
    }
  },
  "navidad": {
    "model": {
      "glb": "pudu_navidad.glb",
      "scale": "1.2 1.2 1.2"
    },
    "audio": {
      "file": "sound_navidad.mp3"
    }
  }
}</pre>
            <p style="margin-top: 10px;"><strong>Campos opcionales por variante:</strong></p>
            <ul>
                <li><code>model.glb</code> - Archivo GLB del modelo</li>
                <li><code>model.usdz</code> - Archivo USDZ (iOS)</li>
                <li><code>model.scale</code> - Escala del modelo</li>
                <li><code>model.position</code> - Posición inicial</li>
                <li><code>model.rotation</code> - Rotación inicial</li>
                <li><code>audio.file</code> - Archivo de audio</li>
                <li><code>audio.enabled</code> - Habilitar/deshabilitar audio</li>
                <li><code>marker.file</code> - Archivo de marcador AR</li>
            </ul>
        </div>
    </div>
</div>

<script>
let currentAnimalId = null;
let currentConfig = null;

// Cargar lista de animales
async function loadAnimals() {
    try {
        const response = await fetch('../api/animals/list.php');
        const data = await response.json();

        if (data.success) {
            const selector = document.getElementById('animal-selector');
            selector.innerHTML = '<option value="">Selecciona un animal</option>';

            data.animals.forEach(animal => {
                const option = document.createElement('option');
                option.value = animal.id;
                option.textContent = `${animal.icon} ${animal.name}`;
                selector.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error cargando animales:', error);
        showNotification('Error cargando animales', 'error');
    }
}

// Evento de cambio de animal
document.getElementById('animal-selector').addEventListener('change', (e) => {
    currentAnimalId = e.target.value;
    if (currentAnimalId) {
        loadAnimalVariants();
    } else {
        document.getElementById('animal-info').style.display = 'none';
    }
});

// Cargar variantes del animal
async function loadAnimalVariants() {
    if (!currentAnimalId) return;

    try {
        const response = await fetch(`../models/${currentAnimalId}/config.json?t=${new Date().getTime()}`);
        currentConfig = await response.json();

        // Mostrar nombre del animal
        document.getElementById('animal-name-display').textContent = currentConfig.name;

        // Si NO tiene sistema de variantes, inicializarlo
        if (!currentConfig.variants) {
            console.log('Config sin variantes, creando estructura inicial');

            // Migrar config antiguo a variante "default"
            currentConfig.variants = {
                default: {
                    model: currentConfig.model || {},
                    audio: currentConfig.audio || {},
                    marker: currentConfig.marker || {}
                }
            };
            currentConfig.activeVariant = 'default';
        }

        // Poblar selector de variante activa
        const activeSelector = document.getElementById('active-variant-selector');
        activeSelector.innerHTML = '';
        Object.keys(currentConfig.variants).forEach(variantName => {
            const option = document.createElement('option');
            option.value = variantName;
            option.textContent = variantName;
            if (variantName === currentConfig.activeVariant) {
                option.selected = true;
            }
            activeSelector.appendChild(option);
        });

        // Mostrar JSON de variantes en el editor
        document.getElementById('variants-editor').value = JSON.stringify(currentConfig.variants, null, 2);

        // Mostrar sección
        document.getElementById('animal-info').style.display = 'block';

    } catch (error) {
        console.error('Error cargando config:', error);
        showNotification('Error cargando configuración del animal', 'error');
    }
}

// Guardar variante activa
async function saveActiveVariant() {
    if (!currentAnimalId || !currentConfig) return;

    const newActiveVariant = document.getElementById('active-variant-selector').value;

    try {
        currentConfig.activeVariant = newActiveVariant;

        const response = await fetch(`../api/animals/update-config.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                animalId: currentAnimalId,
                config: currentConfig
            })
        });

        const data = await response.json();

        if (data.success) {
            showNotification('Variante activa guardada', 'success');
        } else {
            showNotification(data.error || 'Error al guardar', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error de conexión', 'error');
    }
}

// Guardar variantes editadas
async function saveVariants() {
    if (!currentAnimalId || !currentConfig) return;

    try {
        // Parsear JSON del editor
        const variantsJSON = document.getElementById('variants-editor').value;
        const parsedVariants = JSON.parse(variantsJSON);

        // Actualizar config
        currentConfig.variants = parsedVariants;

        // Verificar que la variante activa existe
        if (!parsedVariants[currentConfig.activeVariant]) {
            currentConfig.activeVariant = 'default';
            showNotification('Variante activa no existe en nuevas variantes, cambiada a "default"', 'warning');
        }

        // Guardar
        const response = await fetch(`../api/animals/update-config.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                animalId: currentAnimalId,
                config: currentConfig
            })
        });

        const data = await response.json();

        if (data.success) {
            showNotification('Variantes guardadas correctamente', 'success');
            loadAnimalVariants(); // Recargar para actualizar selector
        } else {
            showNotification(data.error || 'Error al guardar', 'error');
        }
    } catch (error) {
        if (error instanceof SyntaxError) {
            showNotification('Error: JSON inválido. Revisa el formato', 'error');
        } else {
            console.error('Error:', error);
            showNotification('Error al guardar variantes', 'error');
        }
    }
}

loadAnimals();
</script>

<?php include 'includes/footer.php'; ?>
