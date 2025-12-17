class ConfigLoader {
    // Lista de todos los modelos disponibles (ubicación predeterminada)
    static ALL_MODELS = [
        'puma',
        'huillin',
        'pudu',
        'zorrodedarwin',
        'carpintero',
        'chucao',
        'martinpescador',
        'ranadedarwin'
    ];

    static async load(modelId) {
        try {
            const response = await fetch(`models/${modelId}/config.json`);
            if (!response.ok) throw new Error('Config not found');
            return await response.json();
        } catch (error) {
            console.error('Error loading config:', error);
            return null;
        }
    }

    // Cargar todos los configs disponibles
    static async loadAll() {
        const configs = [];

        for (const modelId of this.ALL_MODELS) {
            const config = await this.load(modelId);
            if (config) {
                configs.push(config);
            }
        }

        console.log(`✅ Cargados ${configs.length} configs de animales`);
        return configs;
    }
}
