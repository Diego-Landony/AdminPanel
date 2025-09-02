import { useState, useEffect, useCallback } from 'react';
import { changeTheme, getCurrentTheme } from '@/lib/theme-init';

type Theme = 'light' | 'dark' | 'system';

/**
 * Hook para manejar el estado del tema en los componentes de React.
 * Lee desde localStorage y proporciona una función para cambiar el tema.
 */
export function useTheme() {
    const [appearance, setAppearance] = useState<Theme>(getCurrentTheme);

    const setTheme = useCallback((theme: Theme) => {
        // 1. Llamar a la función centralizada para cambiar el tema
        changeTheme(theme);
        
        // 2. Actualizar el estado de React para que los componentes reaccionen
        setAppearance(theme);
    }, []);
    
    // Escucha cambios en localStorage (ej. desde otra pestaña)
    useEffect(() => {
        const handleStorageChange = () => {
            setAppearance(getCurrentTheme());
        };
        
        window.addEventListener('storage', handleStorageChange);
        return () => window.removeEventListener('storage', handleStorageChange);
    }, []);
    
    // Escucha cambios de tema del sistema operativo
    useEffect(() => {
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        
        const handleSystemChange = () => {
            if (getCurrentTheme() === 'system') {
                setAppearance('system');
            }
        };
        
        mediaQuery.addEventListener('change', handleSystemChange);
        return () => mediaQuery.removeEventListener('change', handleSystemChange);
    }, []);

    return {
        appearance,
        setTheme,
    };
}
