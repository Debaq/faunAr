document.addEventListener('DOMContentLoaded', () => {
    // 1. Crear y añadir la estructura del modal al body
    const modalHTML = `
        <div id="description-modal" class="description-modal">
            <div class="description-modal-content">
                <div class="description-modal-header">
                    <h2 id="description-modal-title"></h2>
                    <span class="description-modal-close">&times;</span>
                </div>
                <img id="description-modal-image" src="" alt="Imagen del modelo" class="description-modal-image">
                <div id="description-modal-text" class="description-modal-text"></div>
                <div id="description-modal-actions" class="description-modal-actions"></div>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHTML);

    // 2. Obtener referencias a los elementos del DOM
    const modal = document.getElementById('description-modal');
    const modalTitle = document.getElementById('description-modal-title');
    const modalImage = document.getElementById('description-modal-image');
    const modalText = document.getElementById('description-modal-text');
    const modalActions = document.getElementById('description-modal-actions');
    const closeModalButton = document.querySelector('.description-modal-close');
    const projectsContainer = document.getElementById('projects-container');

    // 3. Función para cerrar el modal
    const closeModal = () => {
        stopSound(); // Llama a la función global para detener cualquier sonido
        modal.classList.remove('active');
    };

    // 4. Event Listeners para cerrar el modal
    closeModalButton.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });

    // 5. Event listener principal en el contenedor de proyectos (delegación de eventos)
    if (projectsContainer) {
        projectsContainer.addEventListener('click', async (e) => {
            const card = e.target.closest('.project-card');

            // Salir si no se hizo clic en una tarjeta o si se hizo clic en un botón o enlace
            if (!card || e.target.closest('button, a, .btn-resource')) {
                return;
            }

            const folder = card.dataset.folder;
            if (!folder) return;

            // Poblar y abrir el modal
            await populateAndOpenModal(folder, card);
        });
    }

    // 6. Función para poblar el modal y abrirlo
    const populateAndOpenModal = async (folder, card) => {
        try {
            // Cargar datos de config.json y description.json
            const [configRes, descRes] = await Promise.all([
                fetch(`models/${folder}/config.json`),
                fetch(`models/${folder}/description.json`)
            ]);

            if (!configRes.ok || !descRes.ok) {
                throw new Error(`No se pudo cargar la información para ${folder}`);
            }

            const config = await configRes.json();
            const description = await descRes.json();

            // Poblar contenido
            modalTitle.textContent = config.name || 'Detalles';
            modalImage.src = `models/${folder}/imagen_${folder}.png`;
            modalImage.alt = `Imagen de ${config.name}`;
            modalText.innerHTML = description.description || '<p>No hay descripción disponible.</p>';

            // Limpiar acciones anteriores y clonar nuevas
            modalActions.innerHTML = '';
            const resourceButtons = card.querySelectorAll('.resources .btn-resource');
            
            resourceButtons.forEach(button => {
                const clone = button.cloneNode(true);
                
                // Si es el botón de sonido, necesita un tratamiento especial
                if (clone.id.startsWith('sound-btn-')) {
                    const originalId = clone.id;
                    const newId = `sound-btn-modal-${folder}`;
                    clone.id = newId;

                    // Re-escribir el onclick para que llame a toggleSound con el nuevo ID
                    const originalOnclick = button.getAttribute('onclick');
                    if (originalOnclick) {
                        // Extraer los dos primeros argumentos (url, modelId)
                        const args = originalOnclick.match(/\(([^)]+)\)/)[1].split(',').slice(0, 2).join(',');
                        clone.setAttribute('onclick', `toggleSound(${args}, '${newId}')`);
                    }

                    // Sincronizar el estado visual del nuevo botón con el original
                    const originalButton = document.getElementById(originalId);
                    if (originalButton) {
                        clone.innerHTML = originalButton.innerHTML;
                        clone.title = originalButton.title;
                    }
                }
                
                modalActions.appendChild(clone);
            });

            // Mostrar modal
            modal.classList.add('active');

        } catch (error) {
            console.error('Error al abrir el modal:', error);
            alert('No se pudo cargar la información del modelo.');
        }
    };
});
