// Sistema de Internacionalización (i18n) para FaunAR

class I18nManager {
    constructor() {
        this.currentLang = this.detectLanguage();
        this.translations = {};
        this.fallbackLang = 'es';
        this.initialized = false;
    }

    detectLanguage() {
        // 1. Verificar localStorage
        const saved = localStorage.getItem('faunar_language');
        if (saved) return saved;

        // 2. Detectar idioma del navegador
        const browserLang = navigator.language || navigator.userLanguage;
        const langCode = browserLang.split('-')[0];

        // 3. Verificar si es soportado
        const supportedLangs = ['es', 'en'];
        return supportedLangs.includes(langCode) ? langCode : 'es';
    }

    async init() {
        try {
            await this.loadLanguage(this.currentLang);
            this.initialized = true;
            return true;
        } catch (error) {
            console.error('Error inicializando i18n:', error);
            if (this.currentLang !== this.fallbackLang) {
                await this.loadLanguage(this.fallbackLang);
            }
            return false;
        }
    }

    async loadLanguage(lang) {
        try {
            const response = await fetch(`data/i18n/${lang}.json`);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            this.translations = await response.json();
            this.currentLang = lang;
            localStorage.setItem('faunar_language', lang);

            // Actualizar elementos del DOM
            this.updateDOM();

            return true;
        } catch (error) {
            console.error(`Error cargando idioma ${lang}:`, error);
            if (lang !== this.fallbackLang) {
                return await this.loadLanguage(this.fallbackLang);
            }
            throw error;
        }
    }

    t(key, params = {}, fallback = null) {
        const keys = key.split('.');
        let value = this.translations;

        for (const k of keys) {
            if (value && typeof value === 'object') {
                value = value[k];
            } else {
                value = undefined;
                break;
            }
        }

        // Si no se encuentra, usar fallback o la clave
        if (value === undefined || value === null) {
            if (fallback !== null) {
                return fallback;
            }
            console.warn(`Translation key not found: ${key}`);
            return key.split('.').pop();
        }

        // Si es un objeto, retornar el objeto completo
        if (typeof value === 'object') {
            return value;
        }

        // Reemplazar parámetros {variable}
        let result = String(value);
        Object.keys(params).forEach(param => {
            const regex = new RegExp(`\\{${param}\\}`, 'g');
            result = result.replace(regex, params[param]);
        });

        return result;
    }

    updateDOM() {
        // Actualizar elementos con data-i18n
        document.querySelectorAll('[data-i18n]').forEach(el => {
            const key = el.getAttribute('data-i18n');
            const text = this.t(key);
            if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') {
                el.placeholder = text;
            } else {
                el.textContent = text;
            }
        });

        // Actualizar placeholders
        document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
            const key = el.getAttribute('data-i18n-placeholder');
            el.placeholder = this.t(key);
        });

        // Actualizar títulos/tooltips
        document.querySelectorAll('[data-i18n-title]').forEach(el => {
            const key = el.getAttribute('data-i18n-title');
            el.title = this.t(key);
        });

        // Actualizar atributos alt
        document.querySelectorAll('[data-i18n-alt]').forEach(el => {
            const key = el.getAttribute('data-i18n-alt');
            el.alt = this.t(key);
        });

        // Actualizar selector de idioma si existe
        this.updateLanguageSelector();
    }

    updateLanguageSelector() {
        const selector = document.getElementById('language-select');
        if (selector) {
            selector.value = this.currentLang;
        }
    }

    async setLanguage(lang) {
        if (lang === this.currentLang) return;

        try {
            await this.loadLanguage(lang);
            // Emitir evento personalizado
            window.dispatchEvent(new CustomEvent('languageChanged', {
                detail: { language: lang }
            }));
        } catch (error) {
            console.error('Error cambiando idioma:', error);
        }
    }

    getCurrentLanguage() {
        return this.currentLang;
    }

    getAvailableLanguages() {
        return ['es', 'en'];
    }
}

// Instancia global
const i18n = new I18nManager();

// Auto-inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        i18n.init().catch(err => console.error('Error auto-init i18n:', err));
    });
} else {
    i18n.init().catch(err => console.error('Error auto-init i18n:', err));
}
