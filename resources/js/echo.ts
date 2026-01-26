import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Make Pusher available globally for Echo
window.Pusher = Pusher;

// Configuracion de WebSocket publico
// VITE_WS_HOST debe ser el dominio publico (ej: ws.subwaycardgt.com)
// Si no esta definido, usa el host de Reverb local
const wsHost = import.meta.env.VITE_WS_HOST || import.meta.env.VITE_REVERB_HOST || window.location.hostname;
const wsPort = import.meta.env.VITE_WS_PORT || 443;
const wsScheme = import.meta.env.VITE_WS_SCHEME || 'https';

// Detectar el endpoint de autenticacion correcto segun la URL actual
// - /restaurant/* usa el guard 'restaurant' -> /restaurant/broadcasting/auth
// - El resto (admin panel) usa el guard 'auth' -> /broadcasting/auth
const isRestaurantPanel = window.location.pathname.startsWith('/restaurant');
const authEndpoint = isRestaurantPanel ? '/restaurant/broadcasting/auth' : '/broadcasting/auth';

// Initialize Laravel Echo with Reverb configuration
window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: wsHost,
    wsPort: Number(wsPort),
    wssPort: Number(wsPort),
    forceTLS: wsScheme === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: authEndpoint,
    // Deshabilitar activity check para mantener conexion activa en segundo plano
    activityTimeout: 120000, // 2 minutos
    pongTimeout: 30000, // 30 segundos
});

// Add Pusher to Window interface
declare global {
    interface Window {
        Pusher: typeof Pusher;
    }
}
