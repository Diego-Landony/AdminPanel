import { Breadcrumbs } from '@/components/breadcrumbs';
import { ThemeToggle } from '@/components/theme-toggle';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { type BreadcrumbItem as BreadcrumbItemType } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { ChevronDown, LogOut, Settings } from 'lucide-react';

interface PageProps {
    auth?: {
        user?: {
            name?: string;
            email?: string;
        } | null;
    };
}

/**
 * Header del sidebar con breadcrumbs, selector de tema y opciones de usuario
 */
export function AppSidebarHeader({ breadcrumbs = [] }: { breadcrumbs?: BreadcrumbItemType[] }) {
    const { auth } = usePage().props as PageProps;
    const user = auth?.user;

    return (
        <header className="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-sidebar-border/50 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
            {/* Lado izquierdo: Sidebar trigger y breadcrumbs */}
            <div className="flex items-center gap-2">
                <SidebarTrigger className="-ml-1" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>

            {/* Lado derecho: Selector de tema y dropdown del usuario */}
            <div className="flex items-center gap-3">
                {/* Selector de tema */}
                <ThemeToggle />

                {/* Dropdown del usuario */}
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button variant="ghost" className="flex h-auto items-center gap-2 px-3 py-2">
                            <div className="flex items-center gap-3">
                                <div className="flex h-8 w-8 items-center justify-center rounded-full bg-muted text-sm font-medium">
                                    {user?.name
                                        ? user.name
                                              .split(' ')
                                              .map((n: string) => n[0])
                                              .join('')
                                              .toUpperCase()
                                        : 'U'}
                                </div>
                                <div className="hidden text-left md:block">
                                    <p className="text-sm font-medium">{user?.name || 'Usuario'}</p>
                                    <p className="max-w-[150px] truncate text-xs text-muted-foreground">{user?.email || 'usuario@ejemplo.com'}</p>
                                </div>
                            </div>
                            <ChevronDown className="h-4 w-4" />
                        </Button>
                    </DropdownMenuTrigger>

                    <DropdownMenuContent align="end" className="w-56">
                        <DropdownMenuLabel>
                            <div className="flex items-center gap-2">
                                <div className="flex h-8 w-8 items-center justify-center rounded-full bg-muted text-sm font-medium">
                                    {user?.name
                                        ? user.name
                                              .split(' ')
                                              .map((n: string) => n[0])
                                              .join('')
                                              .toUpperCase()
                                        : 'U'}
                                </div>
                                <div>
                                    <p className="text-sm font-medium">{user?.name || 'Usuario'}</p>
                                    <p className="text-xs text-muted-foreground">{user?.email || 'usuario@ejemplo.com'}</p>
                                </div>
                            </div>
                        </DropdownMenuLabel>

                        <DropdownMenuSeparator />

                        <DropdownMenuItem asChild>
                            <Link href={route('profile.edit')} className="flex items-center gap-2">
                                <Settings className="h-4 w-4" />
                                Configuración
                            </Link>
                        </DropdownMenuItem>

                        <DropdownMenuItem asChild>
                            <Link href={route('logout')} method="post" as="button" className="flex w-full items-center gap-2">
                                <LogOut className="h-4 w-4" />
                                Cerrar Sesión
                            </Link>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </header>
    );
}
