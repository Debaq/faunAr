class ConfigLoader {
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

    // Obtener lista dinámica de modelos disponibles desde PHP
    static async getAvailableModels() {
        try {
            const response = await fetch('api/get-models.php');
            if (!response.ok) throw new Error('No se pudo obtener lista de modelos');

            const data = await response.json();

            if (data.success && data.models) {
                console.log(`📋 ${data.count} modelos disponibles:`, data.models);
                return data.models;
            }

            throw new Error('Respuesta inválida del servidor');
        } catch (error) {
            console.error('Error obteniendo modelos disponibles:', error);
            // Fallback: intentar listar manualmente (no funcionará en todos los entornos)
            console.warn('⚠️ Usando fallback: lista vacía de modelos');
            return [];
        }
    }

    // Cargar todos los configs disponibles
    static async loadAll() {
        const modelIds = await this.getAvailableModels();
        const configs = [];

        for (const modelId of modelIds) {
            const config = await this.load(modelId);
            if (config) {
                configs.push(config);
            }
        }

        console.log(`✅ Cargados ${configs.length} configs de animales`);
        return configs;
    }
}
