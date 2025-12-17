<?php include 'includes/header.php'; ?>
<?php include 'includes/nav.php'; ?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<div class="page-header">
    <h1>📍 Gestión de Lugares GPS</h1>
</div>

<div class="gps-layout" style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
    <div class="map-panel" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h3 style="margin-bottom: 15px;">Mapa Interactivo</h3>
        <div id="interactive-map" style="height: 600px; border-radius: 8px; overflow: hidden;"></div>
        <div style="margin-top: 15px; padding: 10px; background: #f5f6fa; border-radius: 6px; font-size: 13px; color: #7f8c8d;">
            <strong>Instrucciones:</strong> Haz clic en el mapa para establecer coordenadas, o arrastra los marcadores existentes.
        </div>
    </div>

    <div class="places-panel">
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px;">
            <h3>Animales con GPS</h3>
            <div id="places-list" style="max-height: 300px; overflow-y: auto;">
                <div style="text-align: center; color: #7f8c8d; padding: 20px;">Cargando...</div>
            </div>
        </div>

        <div class="place-editor" id="place-editor" style="display: none; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h3>Editar Ubicación</h3>
            <form id="place-form">
                <input type="hidden" name="animalId">

                <div class="form-group">
                    <label>Animal</label>
                    <input type="text" name="animalName" readonly style="background: #f5f6fa;">
                </div>

                <div class="form-group">
                    <label>Latitud</label>
                    <input type="number" name="latitude" step="0.000001" required>
                </div>

                <div class="form-group">
                    <label>Longitud</label>
                    <input type="number" name="longitude" step="0.000001" required>
                </div>

                <div class="form-group">
                    <label>Radio (metros)</label>
                    <input type="number" name="radius" value="50" required>
                </div>

                <div class="form-checkbox">
                    <input type="checkbox" name="enabled" id="gps-place-enabled">
                    <label for="gps-place-enabled">GPS Habilitado</label>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">💾 Guardar</button>
                    <button type="button" class="btn btn-cancel" onclick="closePlaceEditor()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="js/gps-map.js"></script>

<?php include 'includes/footer.php'; ?>
