/**
 * Tipos TypeScript para el panel de restaurante
 *
 * Este archivo centraliza todas las interfaces especificas
 * del panel de restaurante (separado del admin)
 */

import { PageProps } from '@inertiajs/core';
import { Order, Restaurant, Driver } from './models';

/**
 * Autenticacion del panel de restaurante
 */
export interface RestaurantAuth {
    user: {
        id: number;
        name: string;
        email: string;
    };
    restaurant: {
        id: number;
        name: string;
    };
}

/**
 * Estadisticas del dashboard del restaurante
 */
export interface RestaurantDashboardStats {
    pending_orders: number;
    preparing_orders: number;
    ready_orders: number;
    completed_today: number;
    total_today: number;
    cash_sales_today: number;
    card_sales_today: number;
    total_sales_today: number;
}

/**
 * Props de la pagina del dashboard del restaurante
 */
export interface RestaurantDashboardProps {
    restaurantAuth: RestaurantAuth;
    stats: RestaurantDashboardStats;
    recent_orders: Order[];
    pending_orders_count: number;
}

/**
 * Props compartidas del panel de restaurante
 */
export interface RestaurantSharedData extends PageProps {
    restaurantAuth: RestaurantAuth | null;
    pending_orders_count?: number;
    flash?: {
        success?: string;
        error?: string;
        warning?: string;
        info?: string;
        message?: string;
        status?: string;
    };
}

/**
 * Item de navegacion del restaurante
 */
export interface RestaurantNavItem {
    title: string;
    href: string;
    icon: React.ComponentType<{ className?: string }>;
    badge?: number;
}

/**
 * Props del layout del restaurante
 */
export interface RestaurantLayoutProps {
    children: React.ReactNode;
    title?: string;
}

/**
 * Props de la pagina de ordenes del restaurante
 */
export interface RestaurantOrdersPageProps {
    restaurantAuth: RestaurantAuth;
    orders: Order[];
    pending_orders_count: number;
}

/**
 * Props de la pagina de motoristas del restaurante
 */
export interface RestaurantDriversPageProps {
    restaurantAuth: RestaurantAuth;
    drivers: Driver[];
    pending_orders_count: number;
}

/**
 * Props de la pagina de perfil del restaurante
 */
export interface RestaurantProfilePageProps {
    restaurantAuth: RestaurantAuth;
    restaurant: Restaurant;
    pending_orders_count: number;
}
