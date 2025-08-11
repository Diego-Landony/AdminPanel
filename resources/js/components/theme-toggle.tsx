import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { useTheme } from '@/hooks/use-theme';

/**
 * Componente de toggle de tema
 * Permite cambiar entre tema claro, oscuro y automático usando el sistema nativo de Laravel
 */
export function ThemeToggle() {
    const { appearance, setTheme } = useTheme();

    /**
     * Obtiene el icono correspondiente al tema actual
     */
    const getThemeIcon = () => {
        switch (appearance) {
            case 'light':
                return <i className="fas fa-sun h-4 w-4"></i>;
            case 'dark':
                return <i className="fas fa-moon h-4 w-4"></i>;
            default:
                return <i className="fas fa-desktop h-4 w-4"></i>;
        }
    };

    /**
     * Obtiene el texto descriptivo del tema actual
     */
    const getThemeText = () => {
        switch (appearance) {
            case 'light':
                return 'Claro';
            case 'dark':
                return 'Oscuro';
            default:
                return 'Automático';
        }
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="sm" className="h-8 px-3 py-2">
                    <span className="sr-only">Cambiar tema</span>
                    <div className="flex items-center gap-2">
                        {getThemeIcon()}
                        <span className="text-sm font-medium">{getThemeText()}</span>
                    </div>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                <DropdownMenuItem onClick={() => setTheme('light')}>
                    <i className="fas fa-sun mr-2 h-4 w-4"></i>
                    <span>Claro</span>
                </DropdownMenuItem>
                <DropdownMenuItem onClick={() => setTheme('dark')}>
                    <i className="fas fa-moon mr-2 h-4 w-4"></i>
                    <span>Oscuro</span>
                </DropdownMenuItem>
                <DropdownMenuItem onClick={() => setTheme('system')}>
                    <i className="fas fa-desktop mr-2 h-4 w-4"></i>
                    <span>Automático</span>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
