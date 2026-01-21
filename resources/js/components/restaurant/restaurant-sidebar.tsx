import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import { RestaurantAuth, RestaurantNavItem } from '@/types/restaurant';
import { Link, usePage } from '@inertiajs/react';
import { Bike, LayoutDashboard, ShoppingBag, User, Utensils } from 'lucide-react';

/**
 * Items de navegacion del sidebar del restaurante
 */
const getNavigationItems = (pendingOrdersCount?: number): RestaurantNavItem[] => [
    {
        title: 'Dashboard',
        href: '/restaurant/dashboard',
        icon: LayoutDashboard,
    },
    {
        title: 'Ordenes',
        href: '/restaurant/orders',
        icon: ShoppingBag,
        badge: pendingOrdersCount && pendingOrdersCount > 0 ? pendingOrdersCount : undefined,
    },
    {
        title: 'Motoristas',
        href: '/restaurant/drivers',
        icon: Bike,
    },
    {
        title: 'Mi Perfil',
        href: '/restaurant/profile',
        icon: User,
    },
];

interface RestaurantSidebarProps {
    isOpen: boolean;
    onClose?: () => void;
}

/**
 * Componente de sidebar para el panel de restaurante
 * Muestra la navegacion principal con badges de notificaciones
 */
export function RestaurantSidebar({ isOpen, onClose }: RestaurantSidebarProps) {
    const page = usePage();
    const { url } = page;
    const restaurantAuth = page.props.restaurantAuth as RestaurantAuth | undefined;
    const pending_orders_count = page.props.pending_orders_count as number | undefined;

    const navigationItems = getNavigationItems(pending_orders_count);

    return (
        <>
            {/* Overlay para movil */}
            {isOpen && (
                <div
                    className="fixed inset-0 z-40 bg-black/50 lg:hidden"
                    onClick={onClose}
                    aria-hidden="true"
                />
            )}

            {/* Sidebar */}
            <aside
                className={cn(
                    'fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r border-border bg-card transition-transform duration-300 ease-in-out lg:static lg:translate-x-0',
                    isOpen ? 'translate-x-0' : '-translate-x-full'
                )}
            >
                {/* Header del sidebar con logo/nombre */}
                <div className="flex h-16 items-center gap-3 border-b border-border px-4">
                    <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                        <Utensils className="h-5 w-5" />
                    </div>
                    <div className="flex flex-col">
                        <span className="text-sm font-semibold">Panel Restaurante</span>
                        <span className="text-xs text-muted-foreground truncate max-w-[150px]">
                            {restaurantAuth?.restaurant.name || 'Restaurante'}
                        </span>
                    </div>
                </div>

                {/* Navegacion */}
                <nav className="flex-1 overflow-y-auto p-4">
                    <ul className="space-y-1">
                        {navigationItems.map((item) => {
                            const isActive = url.startsWith(item.href);
                            const Icon = item.icon;

                            return (
                                <li key={item.href}>
                                    <Link
                                        href={item.href}
                                        onClick={onClose}
                                        className={cn(
                                            'flex items-center justify-between rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
                                            isActive
                                                ? 'bg-primary text-primary-foreground'
                                                : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                                        )}
                                    >
                                        <span className="flex items-center gap-3">
                                            <Icon className="h-5 w-5" />
                                            {item.title}
                                        </span>

                                        {item.badge && item.badge > 0 && (
                                            <Badge
                                                variant={isActive ? 'secondary' : 'default'}
                                                className={cn(
                                                    'min-w-[24px] justify-center',
                                                    isActive
                                                        ? 'bg-primary-foreground text-primary'
                                                        : 'bg-amber-500 text-white'
                                                )}
                                            >
                                                {item.badge > 99 ? '99+' : item.badge}
                                            </Badge>
                                        )}
                                    </Link>
                                </li>
                            );
                        })}
                    </ul>
                </nav>

            </aside>
        </>
    );
}
