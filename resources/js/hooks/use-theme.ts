import { usePage } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import { useEffect } from 'react';
import { type SharedData } from '@/types';
import { changeTheme, syncThemeWithInertia } from '@/lib/theme-init';

/**
 * Hook personalizado para manejar el tema del sistema
 * Proporciona funciones para cambiar y obtener el tema actual
 */
export function useTheme() {
    const { appearance } = usePage<SharedData>().props;

    // Sincronizar tema cuando cambie el prop de Inertia
    useEffect(() => {
        if (appearance && typeof appearance === 'string') {
            syncThemeWithInertia(appearance);
        }
    }, [appearance]);

    /**
     * Cambia el tema del sistema
     * @param theme - Tema a establecer ('light', 'dark', 'system')
     */
    const setTheme = (theme: 'light' | 'dark' | 'system') => {
        // Aplicar tema inmediatamente al DOM
        changeTheme(theme);
        
        // Enviar petición POST a Laravel para persistir el cambio
        router.post(route('theme.update'), { theme }, {
            preserveState: true,
            preserveScroll: true,
            onError: (errors) => {
                console.error('Error al cambiar tema:', errors);
            },
        });
    };

    /**
     * Obtiene el tema actual
     */
    const getCurrentTheme = () => appearance;

    /**
     * Verifica si el tema actual es oscuro
     */
    const isDark = () => appearance === 'dark' || 
        (appearance === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);

    /**
     * Verifica si el tema actual es claro
     */
    const isLight = () => appearance === 'light' || 
        (appearance === 'system' && window.matchMedia('(prefers-color-scheme: light)').matches);

    return {
        appearance,
        setTheme,
        getCurrentTheme,
        isDark,
        isLight,
    };
}
