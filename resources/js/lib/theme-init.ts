/**
 * Script de inicialización del tema
 * Se ejecuta al cargar la página para aplicar el tema correcto
 */

/**
 * Inicializa el tema del sistema
 * Lee la cookie de Laravel y aplica el tema correspondiente
 */
export function initializeTheme() {
    // Obtener el tema de la cookie
    const getCookie = (name: string): string | null => {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop()?.split(';').shift() || null;
        return null;
    };

    const theme = getCookie('appearance') || 'system';
    
    // Aplicar el tema al DOM
    applyTheme(theme);
    
    // Escuchar cambios en la preferencia del sistema (para tema automático)
    if (theme === 'system') {
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        mediaQuery.addEventListener('change', (e) => {
            if (getCookie('appearance') === 'system') {
                applyTheme('system');
            }
        });
    }
}

/**
 * Aplica el tema especificado al DOM
 * @param theme - Tema a aplicar ('light', 'dark', 'system')
 */
function applyTheme(theme: string) {
    const html = document.documentElement;
    
    // Remover clases existentes
    html.classList.remove('light', 'dark');
    
    // Aplicar nueva clase
    if (theme === 'system') {
        // Para tema automático, usar la preferencia del sistema
        if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
            html.classList.add('dark');
        } else {
            html.classList.add('light');
        }
    } else {
        html.classList.add(theme);
    }
}

/**
 * Función para cambiar el tema dinámicamente
 * @param theme - Tema a establecer
 */
export function changeTheme(theme: 'light' | 'dark' | 'system') {
    // Establecer cookie
    document.cookie = `appearance=${theme}; path=/; max-age=${365 * 24 * 60 * 60}`;
    
    // Aplicar tema
    applyTheme(theme);
}

/**
 * Función para sincronizar el tema con el estado de Inertia
 * @param appearance - Tema desde Inertia
 */
export function syncThemeWithInertia(appearance: string) {
    if (appearance) {
        applyTheme(appearance);
    }
}

// Inicializar tema cuando se carga el script
if (typeof document !== 'undefined') {
    // Esperar a que el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeTheme);
    } else {
        initializeTheme();
    }
}
