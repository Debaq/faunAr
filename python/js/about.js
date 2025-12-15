document.addEventListener('DOMContentLoaded', () => {
    const aboutButton = document.getElementById('about-button');
    const aboutModal = document.getElementById('about-modal');
    const closeModal = document.querySelector('.about-modal-close');
    let contentLoaded = false;

    if (aboutButton && aboutModal) {
        aboutButton.addEventListener('click', async () => {
            console.log('About button clicked');
            if (!contentLoaded) {
                console.log('Loading about content...');
                await loadAboutContent();
                contentLoaded = true;
            }
            aboutModal.style.display = 'block';
        });
    }

    if (closeModal) {
        closeModal.addEventListener('click', () => {
            aboutModal.style.display = 'none';
        });
    }

    window.addEventListener('click', (event) => {
        if (event.target === aboutModal) {
            aboutModal.style.display = 'none';
        }
    });

    async function loadAboutContent() {
        try {
            console.log('Fetching about.json...');
            const response = await fetch('assets/data/about.json');
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            console.log('About data loaded:', data);
            populateModal(data);
        } catch (error) {
            console.error("Could not load about content:", error);
            const modalBody = aboutModal.querySelector('.about-modal-body');
            modalBody.innerHTML = '<p>Error al cargar la información. Por favor, intente más tarde.</p>';
        }
    }

    function populateModal(data) {
        console.log('Populating modal with data:', data);
        const modalHeader = aboutModal.querySelector('.about-modal-header');
        const modalBody = aboutModal.querySelector('.about-modal-body');
        const modalFooter = aboutModal.querySelector('.about-modal-footer');

        console.log('Modal elements:', { modalHeader, modalBody, modalFooter });

        modalHeader.innerHTML = `
            <img src="assets/images/logo_faunar.png" alt="FaunAR Logo" style="max-width: 120px; margin-bottom: 10px;">
            <h2>${data.content.formal_title || 'Acerca de FaunAR'}</h2>
            <span class="about-modal-close">&times;</span>
        `;
        console.log('Header set with logo');

        // Re-attach close button listener
        const newCloseBtn = modalHeader.querySelector('.about-modal-close');
        if (newCloseBtn) {
            newCloseBtn.addEventListener('click', () => {
                aboutModal.style.display = 'none';
            });
        }

        let bodyHtml = '';

        const createSection = (title, content) => {
            if (!title || !content) return '';
            let section = `<h3>${title}</h3>`;
            if (typeof content === 'string') {
                section += `<p>${content}</p>`;
            } else if (Array.isArray(content)) {
                section += '<ul>';
                content.forEach(item => {
                    section += `<li>${item}</li>`;
                });
                section += '</ul>';
            }
            return section;
        };
        
        const summary = data.content.executive_summary;
        if(summary) bodyHtml += createSection(summary.title, summary.paragraph);

        const scope = data.content.scope_and_location;
        if(scope) bodyHtml += createSection(scope.title, scope.paragraph);

        const methodology = data.content.methodology_and_technology;
        if(methodology) {
             bodyHtml += `<h3>${methodology.title}</h3>`;
             if(methodology.description_paragraph) bodyHtml += `<p>${methodology.description_paragraph}</p>`;
             if(methodology.content_list_title) bodyHtml += `<p><strong>${methodology.content_list_title}</strong></p>`;
             if(methodology.content_list) {
                 bodyHtml += '<ul>';
                 methodology.content_list.forEach(item => {
                    bodyHtml += `<li>${item}</li>`;
                 });
                 bodyHtml += '</ul>';
             }
        }

        const impact = data.content.impact_and_objectives;
        if(impact) {
             bodyHtml += `<h3>${impact.title}</h3>`;
             if(impact.preface_paragraph) bodyHtml += `<p>${impact.preface_paragraph}</p>`;
             if(impact.objectives_list) {
                 bodyHtml += '<ul>';
                 impact.objectives_list.forEach(item => {
                    bodyHtml += `<li>${item}</li>`;
                 });
                 bodyHtml += '</ul>';
             }
             if(impact.closing_paragraph) bodyHtml += `<p>${impact.closing_paragraph}</p>`;
        }

        modalBody.innerHTML = bodyHtml;
        console.log('Body HTML set, length:', bodyHtml.length);

        let footerHtml = `
            <div style="display: flex; justify-content: space-around; align-items: center; flex-wrap: wrap; gap: 20px; margin-bottom: 15px;">
                <img src="https://www.uach.cl/uach/_imag/uach/logo-v2.png" alt="Logo UACh">
                <img src="assets/images/logo.png" alt="Logo TecMedHub">
            </div>
        `;
        if (data.contact_info && data.contact_info.institution) {
             footerHtml += `<p><strong>${data.contact_info.institution}</strong></p>`;
        }
       if (data.contact_info && data.contact_info.social_media) {
            data.contact_info.social_media.forEach(social => {
                footerHtml += `<p><a href="${social.url}" target="_blank">${social.platform}: ${social.username}</a></p>`;
            });
        }
        modalFooter.innerHTML = footerHtml;
        console.log('Footer HTML set');
        console.log('Modal population complete');
    }
});
