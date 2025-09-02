import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { useTheme } from '@/hooks/use-theme';
import { Sun, Moon, Monitor } from 'lucide-react';

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
                return <Sun className="h-4 w-4" />;
            case 'dark':
                return <Moon className="h-4 w-4" />;
            default:
                return <Monitor className="h-4 w-4" />;
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
                    <Sun className="mr-2 h-4 w-4" />
                    <span>Claro</span>
                </DropdownMenuItem>
                <DropdownMenuItem onClick={() => setTheme('dark')}>
                    <Moon className="mr-2 h-4 w-4" />
                    <span>Oscuro</span>
                </DropdownMenuItem>
                <DropdownMenuItem onClick={() => setTheme('system')}>
                    <Monitor className="mr-2 h-4 w-4" />
                    <span>Automático</span>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
