import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

import AppLayout from '@/layouts/app-layout';
import { CustomerTable } from '@/components/customers/customer-table';

/**
 * Breadcrumbs para la navegación de clientes
 */
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Clientes',
        href: '/customers',
    },
    {
        title: 'Gestión de clientes',
        href: '/customers',
    },
];

/**
 * Interfaz para los datos del cliente
 */
interface Customer {
    id: number;
    full_name: string;
    email: string;
    subway_card: string;
    birth_date: string;
    gender: string | null;
    client_type: string | null;
    customer_type: {
        id: number;
        name: string;
        display_name: string;
        color: string | null;
        multiplier: number;
    } | null;
    phone: string | null;
    location: string | null;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    last_activity: string | null;
    last_purchase: string | null;
    puntos: number;
    puntos_updated_at: string | null;
    is_online: boolean;
    status: string;
}

/**
 * Interfaz para las estadísticas de tipos de clientes
 */
interface CustomerTypeStat {
    id: number;
    display_name: string;
    color: string;
    customer_count: number;
}

/**
 * Interfaz para las props de la página
 */
interface CustomersPageProps {
    customers: {
        data: Customer[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    customer_type_stats: CustomerTypeStat[];
    filters: {
        search: string | null;
        per_page: number;
        sort_field?: string;
        sort_direction?: 'asc' | 'desc';
    };
}

/**
 * Página principal de gestión de clientes
 * Refactorizada para usar el componente CustomerTable reutilizable
 */
export default function CustomersIndex({
    customers,
    customer_type_stats,
    filters,
}: CustomersPageProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Gestión de Clientes" />

            <CustomerTable
                customers={customers}
                customer_type_stats={customer_type_stats}
                filters={filters}
            />
        </AppLayout>
    );
}