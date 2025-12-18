// Global ConfigLoader object
const ConfigLoader = {
    mainConfig: null,
    animalConfigs: {},

    // Loads the main data/config.json
    loadMain: async function() {
        if (this.mainConfig) return this.mainConfig;
        try {
            const response = await fetch(`data/config.json?t=${new Date().getTime()}`);
            if (!response.ok) throw new Error('config.json not found');
            this.mainConfig = await response.json();
            return this.mainConfig;
        } catch (error) {
            console.error('Error loading main config:', error);
            return null;
        }
    },

    // Loads a specific animal's config.json
    load: async function(modelId) {
        if (this.animalConfigs[modelId]) return this.animalConfigs[modelId];
        if (!modelId) return null;
        try {
            const response = await fetch(`models/${modelId}/config.json?t=${new Date().getTime()}`);
            if (!response.ok) throw new Error(`config.json for ${modelId} not found`);
            const animalConfig = await response.json();
            this.animalConfigs[modelId] = animalConfig;
            return animalConfig;
        } catch (error) {
            console.error(`Error loading config for ${modelId}:`, error);
            return null;
        }
    },

    // Loads all animal configs by fetching the model list first
    loadAll: async function() {
        try {
            const response = await fetch('api/get-models.php');
            const data = await response.json();
            if (!data.success) throw new Error('Could not get model list');
            
            const configPromises = data.models.map(id => this.load(id));
            const configs = await Promise.all(configPromises);
            
            return configs.filter(c => c !== null);
        } catch (error) {
            console.error('Error loading all animal configs:', error);
            return [];
        }
    },
    
    // Applies dynamic theme styles from config
    applyTheme: function(config) {
        if (!config || !config.theme) return;
        const style = document.createElement('style');
        style.innerHTML = `
            :root {
                ${config.theme.primary_gradient_start ? `--primary-gradient-start: ${config.theme.primary_gradient_start};` : ''}
                ${config.theme.primary_gradient_end ? `--primary-gradient-end: ${config.theme.primary_gradient_end};` : ''}
                ${config.theme.accent_color ? `--accent-color: ${config.theme.accent_color};` : ''}
            }
        `;
        document.head.appendChild(style);
    },
    
    // Applies dynamic content from config
    applyContent: function(config) {
        if (!config || !config.site) return;
        
        const footerText = document.getElementById('footer-text');
        if (footerText && config.site.footer_text) {
            footerText.textContent = config.site.footer_text;
        }
        
        if (config.site.title) {
            document.title = config.site.title;
        }
    }
};

// Self-executing function for the main portal page (index.html)
// It checks for an element unique to the portal page.
if (document.getElementById('projects-container')) { 
    document.addEventListener('DOMContentLoaded', async () => {
        const mainConfig = await ConfigLoader.loadMain();
        if(mainConfig){
            ConfigLoader.applyTheme(mainConfig);
            ConfigLoader.applyContent(mainConfig);
        }
    });
}
