// GPS Map Manager para gestión de lugares

class GPSMapManager {
    constructor() {
        this.map = null;
        this.markers = {};
        this.animals = [];
        this.selectedAnimal = null;
        this.tempMarker = null;
    }

    async init() {
        // Inicializar mapa centrado en Los Lagos, Chile
        this.map = L.map('interactive-map').setView([-41.4693, -72.9424], 10);

        // Agregar capa de mapa
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(this.map);

        // Cargar animales
        await this.loadAnimals();

        // Event listeners
        this.map.on('click', (e) => this.onMapClick(e));

        // Form submit
        document.getElementById('place-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.savePlace();
        });
    }

    async loadAnimals() {
        try {
            const response = await fetch('../api/animals/list.php');
            const data = await response.json();

            if (data.success) {
                this.animals = data.animals;
                this.renderPlacesList();
                this.renderMarkers();
            }
        } catch (error) {
            console.error('Error cargando animales:', error);
        }
    }

    renderPlacesList() {
        const list = document.getElementById('places-list');
        list.innerHTML = '';

        this.animals.forEach(animal => {
            const item = document.createElement('div');
            item.style.cssText = 'padding: 12px; border-bottom: 1px solid #dfe6e9; display: flex; justify-content: space-between; align-items: center;';

            const gpsEnabled = animal.gps?.enabled || false;
            const hasCoords = animal.gps?.latitude && animal.gps?.longitude;

            item.innerHTML = `
                <div>
                    <strong style="display: block;">${animal.name}</strong>
                    <small style="color: #7f8c8d;">
                        ${gpsEnabled ?
                            hasCoords ? `📍 ${animal.gps.latitude.toFixed(4)}, ${animal.gps.longitude.toFixed(4)}` : '📍 GPS sin coordenadas'
                            : '⚪ GPS Inactivo'
                        }
                    </small>
                </div>
                <button onclick="gpsMap.editPlace('${animal.id}')" class="btn btn-primary btn-small">Editar</button>
            `;

            list.appendChild(item);
        });

        if (this.animals.length === 0) {
            list.innerHTML = '<div style="text-align: center; color: #7f8c8d; padding: 20px;">No hay animales registrados</div>';
        }
    }

    renderMarkers() {
        // Limpiar marcadores previos
        Object.values(this.markers).forEach(({ marker, circle }) => {
            if (marker) marker.remove();
            if (circle) circle.remove();
        });
        this.markers = {};

        this.animals.forEach(animal => {
            if (animal.gps?.enabled && animal.gps.latitude && animal.gps.longitude) {
                // Crear marcador
                const marker = L.marker([animal.gps.latitude, animal.gps.longitude], {
                    draggable: true
                }).addTo(this.map);

                marker.bindPopup(`
                    <strong>${animal.name}</strong><br>
                    Radio: ${animal.gps.radius}m<br>
                    <button onclick="gpsMap.editPlace('${animal.id}')" class="btn btn-primary btn-small" style="margin-top: 5px;">Editar</button>
                `);

                // Evento drag
                marker.on('dragend', (e) => {
                    const pos = e.target.getLatLng();
                    this.updateAnimalCoords(animal.id, pos.lat, pos.lng);
                });

                // Círculo de radio
                const circle = L.circle([animal.gps.latitude, animal.gps.longitude], {
                    radius: animal.gps.radius,
                    color: '#2c5f2d',
                    fillColor: '#97be5a',
                    fillOpacity: 0.2
                }).addTo(this.map);

                this.markers[animal.id] = { marker, circle };
            }
        });
    }

    onMapClick(e) {
        if (!this.selectedAnimal) return;

        // Actualizar coordenadas del formulario
        document.querySelector('[name="latitude"]').value = e.latlng.lat.toFixed(6);
        document.querySelector('[name="longitude"]').value = e.latlng.lng.toFixed(6);

        // Mostrar marcador temporal
        this.showTempMarker(e.latlng);
    }

    showTempMarker(latlng) {
        if (this.tempMarker) this.tempMarker.remove();

        this.tempMarker = L.marker(latlng, {
            draggable: true,
            icon: L.icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            })
        }).addTo(this.map);

        this.tempMarker.on('dragend', (e) => {
            const pos = e.target.getLatLng();
            document.querySelector('[name="latitude"]').value = pos.lat.toFixed(6);
            document.querySelector('[name="longitude"]').value = pos.lng.toFixed(6);
        });
    }

    editPlace(animalId) {
        const animal = this.animals.find(a => a.id === animalId);
        if (!animal) return;

        this.selectedAnimal = animalId;

        // Mostrar formulario
        const editor = document.getElementById('place-editor');
        editor.style.display = 'block';

        // Rellenar formulario
        document.querySelector('[name="animalId"]').value = animal.id;
        document.querySelector('[name="animalName"]').value = animal.name;
        document.querySelector('[name="latitude"]').value = animal.gps?.latitude || -41.4693;
        document.querySelector('[name="longitude"]').value = animal.gps?.longitude || -72.9424;
        document.querySelector('[name="radius"]').value = animal.gps?.radius || 50;
        document.querySelector('[name="enabled"]').checked = animal.gps?.enabled || false;

        // Centrar mapa
        if (animal.gps?.latitude && animal.gps?.longitude) {
            this.map.setView([animal.gps.latitude, animal.gps.longitude], 15);
        } else {
            // Si no tiene coordenadas, mostrar marcador temporal en el centro actual
            const center = this.map.getCenter();
            this.showTempMarker(center);
        }
    }

    async savePlace() {
        const formData = new FormData(document.getElementById('place-form'));

        const data = {
            id: formData.get('animalId'),
            gps: {
                enabled: formData.get('enabled') === 'on',
                latitude: parseFloat(formData.get('latitude')),
                longitude: parseFloat(formData.get('longitude')),
                radius: parseInt(formData.get('radius'))
            }
        };

        try {
            const response = await fetch('../api/animals/update.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                showNotification('Ubicación guardada correctamente', 'success');
                await this.loadAnimals();
                this.closePlaceEditor();
            } else {
                showNotification(result.error || 'Error al guardar', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('Error de conexión', 'error');
        }
    }

    async updateAnimalCoords(animalId, lat, lng) {
        try {
            const response = await fetch('../api/animals/update.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id: animalId,
                    gps: {
                        latitude: lat,
                        longitude: lng
                    }
                })
            });

            if (response.ok) {
                showNotification('Coordenadas actualizadas', 'success');
                await this.loadAnimals();
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }

    closePlaceEditor() {
        document.getElementById('place-editor').style.display = 'none';
        this.selectedAnimal = null;
        if (this.tempMarker) {
            this.tempMarker.remove();
            this.tempMarker = null;
        }
    }
}

// Instancia global
const gpsMap = new GPSMapManager();

// Función global para cerrar editor
function closePlaceEditor() {
    gpsMap.closePlaceEditor();
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    gpsMap.init();
});
