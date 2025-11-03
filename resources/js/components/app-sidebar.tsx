import { NavMain } from '@/components/nav-main';
import { Sidebar, SidebarContent, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { usePermissions } from '@/hooks/use-permissions';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import {
    Activity,
    Gift,
    HandPlatter,
    Home,
    Layers,
    ListChecks,
    LucideIcon,
    MapPin,
    Package,
    Package2,
    Percent,
    Settings,
    Shield,
    Star,
    Tag,
    UserCircle,
    UserCog,
    Users,
    Utensils,
} from 'lucide-react';
import AppLogo from './app-logo';

/**
 * Configuración dinámica de páginas del sistema
 * Se actualiza automáticamente según las páginas detectadas
 */
export interface PageConfig {
    name: string;
    title: string;
    href: string;
    icon: LucideIcon;
    group?: string;
    permission: string;
}

export const systemPages: PageConfig[] = [
    {
        name: 'home',
        title: 'Inicio',
        href: '/home',
        icon: Home,
        permission: 'home.view',
    },
    {
        name: 'users',
        title: 'Gestión de usuarios',
        href: '/users',
        icon: Settings,
        group: 'Usuarios',
        permission: 'users.view',
    },
    {
        name: 'customers',
        title: 'Gestión de clientes',
        href: '/customers',
        icon: UserCircle,
        group: 'Clientes',
        permission: 'customers.view',
    },
    {
        name: 'customer-types',
        title: 'Tipos de cliente',
        href: '/customer-types',
        icon: Star,
        group: 'Clientes',
        permission: 'customer-types.view',
    },
    {
        name: 'restaurants',
        title: 'Restaurantes',
        href: '/restaurants',
        icon: Utensils,
        group: 'Restaurantes',
        permission: 'restaurants.view',
    },
    {
        name: 'restaurants-geofences',
        title: 'Geocercas',
        href: '/restaurants-geofences',
        icon: MapPin,
        group: 'Restaurantes',
        permission: 'restaurants.view',
    },
    {
        name: 'menu-categories',
        title: 'Categorías',
        href: '/menu/categories',
        icon: Layers,
        group: 'Menú',
        permission: 'menu.categories.view',
    },
    {
        name: 'menu-sections',
        title: 'Secciones',
        href: '/menu/sections',
        icon: ListChecks,
        group: 'Menú',
        permission: 'menu.sections.view',
    },
    {
        name: 'menu-products',
        title: 'Productos',
        href: '/menu/products',
        icon: Package,
        group: 'Menú',
        permission: 'menu.products.view',
    },
    {
        name: 'menu-combos',
        title: 'Combos',
        href: '/menu/combos',
        icon: Package2,
        group: 'Menú',
        permission: 'menu.combos.view',
    },
    {
        name: 'menu-promotions-daily-special',
        title: 'Sub del Día',
        href: '/menu/promotions/daily-special',
        icon: Tag,
        group: 'Promociones',
        permission: 'menu.promotions.view',
    },
    {
        name: 'menu-promotions-two-for-one',
        title: '2x1',
        href: '/menu/promotions/two-for-one',
        icon: Star,
        group: 'Promociones',
        permission: 'menu.promotions.view',
    },
    {
        name: 'menu-promotions-percentage',
        title: 'Porcentaje',
        href: '/menu/promotions/percentage',
        icon: Percent,
        group: 'Promociones',
        permission: 'menu.promotions.view',
    },
    {
        name: 'menu-promotions-bundle-specials',
        title: 'Combinados',
        href: '/menu/promotions/bundle-specials',
        icon: Gift,
        group: 'Promociones',
        permission: 'menu.promotions.view',
    },
    {
        name: 'activity',
        title: 'Actividad',
        href: '/activity',
        icon: Activity,
        permission: 'activity.view',
    },
    {
        name: 'roles',
        title: 'Roles',
        href: '/roles',
        icon: Shield,
        group: 'Usuarios',
        permission: 'roles.view',
    },
    {
        name: 'settings',
        title: 'Configuración',
        href: '/settings/profile',
        icon: Settings,
        permission: 'settings.view',
    },
];

/**
 * Iconos de grupos para la sidebar
 */
export const groupIcons: Record<string, LucideIcon> = {
    Usuarios: UserCog,
    Clientes: UserCircle,
    Restaurantes: Utensils,
    Menú: HandPlatter,
    Promociones: Percent,
};

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
        let settingsItem: NavItem | null = null;

        // Procesar cada página del sistema
        systemPages.forEach((page) => {
            // Verificar si el usuario tiene permisos para esta página
            if (!hasPermission(page.permission)) {
                return;
            }

            const navItem: NavItem = {
                title: page.title,
                href: page.href,
                icon: page.icon,
            };

            // Guardar settings para agregarlo al final
            if (page.name === 'settings') {
                settingsItem = navItem;
                return;
            }

            // Si tiene grupo, agregarlo al grupo correspondiente
            if (page.group) {
                if (!groupedItems[page.group]) {
                    groupedItems[page.group] = [];
                }
                // Push a child item without an icon so subitems don't show icons
                groupedItems[page.group].push({
                    title: navItem.title,
                    href: navItem.href,
                    // explicitly no icon for subitems
                    icon: null,
                });
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
                    icon: groupIcons[groupName] || Users,
                    items: groupItems,
                });
            }
        });

        // Agregar configuración al final
        if (settingsItem) {
            items.push(settingsItem);
        }

        // Si no hay items de navegación, mostrar mensaje de "sin acceso"
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
                            <Link href="/home" prefetch>
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
