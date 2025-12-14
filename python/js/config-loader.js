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
}
