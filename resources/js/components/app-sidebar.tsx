import { NavMain } from '@/components/nav-main';
import { Sidebar, SidebarContent, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { LayoutGrid, Users, UserCog, Activity, Shield, LucideIcon, Home } from 'lucide-react';
import { usePermissions } from '@/hooks/use-permissions';
import AppLogo from './app-logo';

/**
 * Configuración dinámica de páginas del sistema
 * Se actualiza automáticamente según las páginas detectadas
 */
interface PageConfig {
    name: string;
    title: string;
    href: string;
    icon: LucideIcon;
    group?: string;
    permission: string;
}

const systemPages: PageConfig[] = [
    {
        name: 'home',
        title: 'Inicio',
        href: '/home',
        icon: Home,
        permission: 'home.view'
    },
    {
        name: 'dashboard',
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
        permission: 'dashboard.view'
    },
    {
        name: 'users',
        title: 'Gestión de usuarios',
        href: '/users',
        icon: UserCog,
        group: 'Usuarios',
        permission: 'users.view'
    },
    {
        name: 'activity',
        title: 'Actividad',
        href: '/activity',
        icon: Activity,
        group: 'Usuarios',
        permission: 'activity.view'
    },
    {
        name: 'roles',
        title: 'Roles',
        href: '/roles',
        icon: Shield,
        group: 'Usuarios',
        permission: 'roles.view'
    }
];

/**
 * Sidebar principal de la aplicación
 * Sistema dinámico basado en permisos escalable
 */
export function AppSidebar() {
    const { hasPermission } = usePermissions();

    // Generar items de navegación basados en permisos dinámicamente
    const getNavItems = (): NavItem[] => {
        const items: NavItem[] = [];
        const groupedItems: Record<string, NavItem[]> = {};

        // Procesar cada página del sistema
        systemPages.forEach(page => {
            // Verificar si el usuario tiene permisos para esta página
            if (!hasPermission(page.permission)) {
                return;
            }

            const navItem: NavItem = {
                title: page.title,
                href: page.href,
                icon: page.icon,
            };

            // Si tiene grupo, agregarlo al grupo correspondiente
            if (page.group) {
                if (!groupedItems[page.group]) {
                    groupedItems[page.group] = [];
                }
                groupedItems[page.group].push(navItem);
            } else {
                // Sin grupo, agregar directamente
                items.push(navItem);
            }
        });

        // Agregar grupos que tienen items
        Object.entries(groupedItems).forEach(([groupName, groupItems]) => {
            if (groupItems.length > 0) {
                items.push({
                    title: groupName,
                    icon: Users, // Icono por defecto para grupos
                    items: groupItems,
                });
            }
        });

        // ✅ Si no hay items de navegación, mostrar mensaje de "sin acceso"
        if (items.length === 0) {
            items.push({
                title: 'Sin Acceso',
                href: '/no-access',
                icon: Shield,
            });
        }

        return items;
    };

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={getNavItems()} />
            </SidebarContent>
        </Sidebar>
    );
}
