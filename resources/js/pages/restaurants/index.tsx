import { PageProps, type BreadcrumbItem } from "@/types";
import { Head } from "@inertiajs/react";

import AppLayout from "@/layouts/app-layout";
import { RestaurantTable } from "@/components/restaurants/restaurant-table";

/**
 * Breadcrumbs para la navegación de restaurantes
 */
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Restaurantes',
        href: '/restaurants',
    },
    {
        title: 'Gestión de restaurantes',
        href: '/restaurants',
    },
];

interface Restaurant {
    id: number;
    name: string;
    description: string | null;
    latitude: number;
    longitude: number;
    address: string;
    is_active: boolean;
    delivery_active: boolean;
    pickup_active: boolean;
    phone: string | null;
    schedule: Record<string, unknown>; // JSON
    minimum_order_amount: number;
    delivery_area: Record<string, unknown>; // JSON
    image: string | null;
    email: string | null;
    manager_name: string | null;
    delivery_fee: number;
    estimated_delivery_time: number;
    rating: number;
    total_reviews: number;
    sort_order: number;
    created_at: string;
    updated_at: string;
}

interface Filters {
    search: string | null;
    per_page: number;
    sort_field?: string;
    sort_direction?: 'asc' | 'desc';
}

interface RestaurantsPageProps extends PageProps {
    restaurants: {
        data: Restaurant[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    total_restaurants: number;
    active_restaurants: number;
    delivery_restaurants: number;
    pickup_restaurants: number;
    filters: Filters;
}

/**
 * Página principal de gestión de restaurantes
 * Refactorizada para usar el componente RestaurantTable reutilizable
 */
export default function RestaurantsIndex({
    restaurants,
    total_restaurants,
    active_restaurants,
    delivery_restaurants,
    pickup_restaurants,
    filters,
}: RestaurantsPageProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Gestión de Restaurantes" />

            <RestaurantTable
                restaurants={restaurants}
                total_restaurants={total_restaurants}
                active_restaurants={active_restaurants}
                delivery_restaurants={delivery_restaurants}
                pickup_restaurants={pickup_restaurants}
                filters={filters}
            />
        </AppLayout>
    );
}