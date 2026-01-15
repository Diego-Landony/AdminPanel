import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useTheme } from '@/hooks/use-theme';
import { RestaurantAuth } from '@/types/restaurant';
import { Link, usePage } from '@inertiajs/react';
import { ChevronDown, LogOut, Menu, Monitor, Moon, Sun, User } from 'lucide-react';

interface RestaurantHeaderProps {
    onMenuClick: () => void;
}

/**
 * Header del panel de restaurante
 * Incluye boton hamburguesa, toggle de tema y menu de usuario
 */
export function RestaurantHeader({ onMenuClick }: RestaurantHeaderProps) {
    const page = usePage();
    const restaurantAuth = page.props.restaurantAuth as RestaurantAuth | undefined;
    const { appearance, setTheme } = useTheme();

    /**
     * Obtiene el icono del tema actual
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
     * Obtiene el texto del tema actual
     */
    const getThemeText = () => {
        switch (appearance) {
            case 'light':
                return 'Claro';
            case 'dark':
                return 'Oscuro';
            default:
                return 'Auto';
        }
    };

    return (
        <header className="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-border bg-card px-4 lg:px-6">
            {/* Lado izquierdo: Boton hamburguesa */}
            <div className="flex items-center gap-4">
                <Button
                    variant="ghost"
                    size="icon"
                    className="lg:hidden"
                    onClick={onMenuClick}
                    aria-label="Abrir menu"
                >
                    <Menu className="h-5 w-5" />
                </Button>

                {/* Titulo de la pagina (opcional, se puede mostrar breadcrumbs aqui) */}
                <h1 className="text-lg font-semibold lg:hidden">
                    {restaurantAuth?.restaurant.name || 'Panel Restaurante'}
                </h1>
            </div>

            {/* Lado derecho: Tema y usuario */}
            <div className="flex items-center gap-2">
                {/* Toggle de tema */}
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button variant="ghost" size="sm" className="h-9 gap-2 px-3">
                            {getThemeIcon()}
                            <span className="hidden sm:inline">{getThemeText()}</span>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem onClick={() => setTheme('light')}>
                            <Sun className="mr-2 h-4 w-4" />
                            Claro
                        </DropdownMenuItem>
                        <DropdownMenuItem onClick={() => setTheme('dark')}>
                            <Moon className="mr-2 h-4 w-4" />
                            Oscuro
                        </DropdownMenuItem>
                        <DropdownMenuItem onClick={() => setTheme('system')}>
                            <Monitor className="mr-2 h-4 w-4" />
                            Automatico
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>

                {/* Menu de usuario */}
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button variant="ghost" className="h-9 gap-2 px-3">
                            <div className="flex h-7 w-7 items-center justify-center rounded-full bg-muted text-xs font-medium">
                                {restaurantAuth?.user.name
                                    ? restaurantAuth.user.name
                                          .split(' ')
                                          .map((n) => n[0])
                                          .join('')
                                          .toUpperCase()
                                          .slice(0, 2)
                                    : 'U'}
                            </div>
                            <span className="hidden text-sm font-medium md:inline">
                                {restaurantAuth?.user.name || 'Usuario'}
                            </span>
                            <ChevronDown className="h-4 w-4" />
                        </Button>
                    </DropdownMenuTrigger>

                    <DropdownMenuContent align="end" className="w-56">
                        <DropdownMenuLabel>
                            <div className="flex flex-col space-y-1">
                                <p className="text-sm font-medium">
                                    {restaurantAuth?.user.name || 'Usuario'}
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    {restaurantAuth?.user.email || 'email@ejemplo.com'}
                                </p>
                            </div>
                        </DropdownMenuLabel>

                        <DropdownMenuSeparator />

                        <DropdownMenuItem asChild>
                            <Link
                                href="/restaurant/profile"
                                className="flex items-center gap-2"
                            >
                                <User className="h-4 w-4" />
                                Mi Perfil
                            </Link>
                        </DropdownMenuItem>

                        <DropdownMenuSeparator />

                        <DropdownMenuItem asChild>
                            <Link
                                href="/restaurant/logout"
                                method="post"
                                as="button"
                                className="flex w-full items-center gap-2 text-destructive focus:text-destructive"
                            >
                                <LogOut className="h-4 w-4" />
                                Cerrar Sesion
                            </Link>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </header>
    );
}
