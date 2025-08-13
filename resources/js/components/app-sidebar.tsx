import { NavMain } from '@/components/nav-main';
import { Sidebar, SidebarContent, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { LayoutGrid, Users, UserCog, Activity } from 'lucide-react';
import AppLogo from './app-logo';

/**
 * Elementos de navegación principales
 */
const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Usuarios',
        icon: Users,
        items: [
            {
                title: 'Gestión de usuarios',
                href: '/users',
                icon: UserCog,
            },
            {
                title: 'Actividad',
                href: '/audit',
                icon: Activity,
            },
        ],
    },
];

/**
 * Sidebar principal de la aplicación
 * Solo incluye navegación principal
 */
export function AppSidebar() {
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
                <NavMain items={mainNavItems} />
            </SidebarContent>
        </Sidebar>
    );
}
