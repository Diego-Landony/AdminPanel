/**
 * Script de inicialización del tema
 * Se ejecuta para aplicar el tema correcto y evitar el "flash" (FOUC).
 * Prioriza localStorage como la fuente de verdad en el cliente.
 */

const THEME_KEY = 'appearance';
type Theme = 'light' | 'dark' | 'system';

/**
 * Aplica el tema al elemento <html>.
 */
function applyTheme(theme: Theme) {
    const html = document.documentElement;
    html.classList.remove('light', 'dark');

    if (theme === 'system') {
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        html.classList.add(prefersDark ? 'dark' : 'light');
    } else {
        html.classList.add(theme);
    }
}

/**
 * Cambia el tema, lo guarda en localStorage y actualiza el servidor.
 */
export function changeTheme(theme: Theme) {
    // 1. Guardar en localStorage (fuente de verdad del cliente)
    localStorage.setItem(THEME_KEY, theme);
    
    // 2. Aplicar al DOM
    applyTheme(theme);
    
    // 3. Notificar al servidor para persistir en la cookie (para la próxima sesión)
    document.cookie = `${THEME_KEY}=${theme}; path=/; max-age=31536000; SameSite=Lax`;
    
    // Opcional: Enviar una petición silenciosa si se necesita una acción inmediata del servidor.
    // En este caso, la cookie es suficiente.
}

/**
 * Obtiene el tema actual desde localStorage.
 */
export function getCurrentTheme(): Theme {
    return (localStorage.getItem(THEME_KEY) as Theme) || 'system';
}

// La inicialización del tema se maneja directamente en app.blade.php
// para evitar FOUC (Flash of Unstyled Content)
