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
    load: async function(modelId, variantName = null) {
        // Cache key incluye la variante si se especifica
        const cacheKey = variantName ? `${modelId}:${variantName}` : modelId;

        if (this.animalConfigs[cacheKey]) return this.animalConfigs[cacheKey];
        if (!modelId) return null;

        try {
            const response = await fetch(`models/${modelId}/config.json?t=${new Date().getTime()}`);
            if (!response.ok) throw new Error(`config.json for ${modelId} not found`);
            const rawConfig = await response.json();

            // Aplicar variante si existe el sistema de variantes
            const finalConfig = this._applyVariant(rawConfig, variantName);

            this.animalConfigs[cacheKey] = finalConfig;
            return finalConfig;
        } catch (error) {
            console.error(`Error loading config for ${modelId}:`, error);
            return null;
        }
    },

    // INTERNAL: Aplica variante a un config
    _applyVariant: function(config, variantName = null) {
        // Si no tiene sistema de variantes, retornar config tal cual (backward compatible)
        if (!config.variants) {
            console.log('📦 Config sin variantes, usando formato legacy');
            return config;
        }

        // Determinar qué variante usar
        const targetVariant = variantName || config.activeVariant || 'default';

        console.log(`🎨 Aplicando variante: ${targetVariant}`);

        // Verificar que la variante existe
        if (!config.variants[targetVariant]) {
            console.warn(`⚠️ Variante "${targetVariant}" no encontrada, usando "default"`);
            if (!config.variants.default) {
                console.error('❌ No existe variante "default", usando config sin variante');
                return config;
            }
        }

        const variant = config.variants[targetVariant] || config.variants.default;

        // Crear config final combinando base + variante
        const finalConfig = {
            ...config,
            // Remover la estructura de variantes del config final
            variants: undefined,
            activeVariant: targetVariant,

            // Aplicar datos de la variante
            model: variant.model || config.model,
            audio: variant.audio || config.audio,
            marker: variant.marker || config.marker,

            // Metadata de variante
            _variantMetadata: {
                current: targetVariant,
                available: Object.keys(config.variants)
            }
        };

        console.log(`✅ Variante "${targetVariant}" aplicada`);
        return finalConfig;
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

    // NEW: Loads config by QR code (PHASE 2: Instances)
    loadByQR: async function(qrCode) {
        if (!qrCode) return null;

        try {
            console.log(`🔍 Buscando instancia con QR: ${qrCode}`);

            // 1. Cargar instances.json
            const instancesRes = await fetch(`data/instances.json?t=${new Date().getTime()}`);
            if (!instancesRes.ok) {
                console.error('instances.json no encontrado');
                return null;
            }

            const instancesData = await instancesRes.json();

            // 2. Buscar instancia por código QR
            const instance = instancesData.instances.find(inst => inst.qrCode === qrCode);

            if (!instance) {
                console.error(`Instancia con QR "${qrCode}" no encontrada`);
                return null;
            }

            console.log('✅ Instancia encontrada:', instance);

            // Verificar si la instancia está activa
            if (instance.enabled === false) {
                console.warn('⚠️ Instancia deshabilitada');
                return null;
            }

            // 3. Determinar variante a usar (prioridad: instancia > animal > default)
            const variantToUse = instance.variant || null;

            console.log(`🎨 Variante solicitada: ${variantToUse || 'auto (del config)'}`);

            // 4. Cargar config base del animal CON variante
            const animalConfig = await this.load(instance.animalId, variantToUse);

            if (!animalConfig) {
                console.error(`Config del animal "${instance.animalId}" no encontrado`);
                return null;
            }

            console.log('✅ Config del animal cargado:', animalConfig);

            // 5. Aplicar overrides de la instancia
            const finalConfig = { ...animalConfig };

            // Override GPS si la instancia tiene coordenadas específicas
            if (instance.gps && instance.gps.latitude && instance.gps.longitude) {
                finalConfig.gps = {
                    enabled: true,
                    latitude: instance.gps.latitude,
                    longitude: instance.gps.longitude,
                    radius: instance.gps.radius || 50
                };
                console.log('📍 GPS sobrescrito con coordenadas de instancia');
            } else if (instance.placeId) {
                // Si no tiene GPS específico, cargar el del lugar
                try {
                    const placesRes = await fetch(`data/places.json?t=${new Date().getTime()}`);
                    if (placesRes.ok) {
                        const placesData = await placesRes.json();
                        const place = placesData[instance.placeId];

                        if (place && place.gps) {
                            finalConfig.gps = {
                                enabled: true,
                                latitude: place.gps.latitude,
                                longitude: place.gps.longitude,
                                radius: place.gps.radius || 5000
                            };
                            console.log('📍 GPS cargado desde lugar:', place.name);
                        }
                    }
                } catch (error) {
                    console.warn('No se pudo cargar GPS del lugar:', error);
                }
            }

            // Override marcador si la instancia tiene uno específico
            if (instance.marker && instance.marker.file) {
                finalConfig.marker = {
                    enabled: true,
                    type: 'mind',
                    file: instance.marker.file
                };
                console.log('🎯 Marcador sobrescrito con el de instancia');
            }

            // Variante ya aplicada en load(), solo agregar metadata
            if (instance.variant) {
                console.log('🎨 Variante de instancia:', instance.variant);
            }

            // Agregar metadata de la instancia al config
            finalConfig._instanceMetadata = {
                instanceId: instance.id,
                qrCode: instance.qrCode,
                placeId: instance.placeId,
                variant: instance.variant || 'default',
                notes: instance.metadata?.notes || ''
            };

            console.log('✅ Config final generado:', finalConfig);
            return finalConfig;

        } catch (error) {
            console.error('Error loading config by QR:', error);
            return null;
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
