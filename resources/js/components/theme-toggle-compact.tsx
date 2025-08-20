import { Moon, Sun } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useTheme } from '@/hooks/use-theme';

/**
 * Componente de toggle de tema compacto
 * Versión simplificada para el sidebar que solo alterna entre claro y oscuro
 */
export function ThemeToggleCompact() {
    const { appearance, setTheme } = useTheme();

    /**
     * Alterna entre tema claro y oscuro
     */
    const toggleTheme = () => {
        const newTheme = appearance === 'dark' ? 'light' : 'dark';
        setTheme(newTheme);
    };

    /**
     * Obtiene el icono correspondiente al tema actual
     */
    const getThemeIcon = () => {
        return appearance === 'dark' ? <Sun className="h-4 w-4" /> : <Moon className="h-4 w-4" />;
    };

    /**
     * Obtiene el texto descriptivo del tema actual
     */
    const getThemeText = () => {
        return appearance === 'dark' ? 'Cambiar a tema claro' : 'Cambiar a tema oscuro';
    };

    return (
        <Button
            variant="ghost"
            size="sm"
            className="h-8 w-8 p-0"
            onClick={toggleTheme}
            title={getThemeText()}
        >
            <span className="sr-only">{getThemeText()}</span>
            {getThemeIcon()}
        </Button>
    );
}
