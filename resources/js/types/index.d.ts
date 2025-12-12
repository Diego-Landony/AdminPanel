import type { route as routeFn } from 'ziggy-js';

declare global {
    const route: typeof routeFn;
}

// Re-export model types
export * from './models';

// Re-export common types (pagination, filters, columns, etc.)
export * from './common';

// Re-export component types (advanced type-safe components)
export * from './components';

// Extend Inertia core types
declare module '@inertiajs/core' {
    export interface PageProps {
        auth: {
            user: {
                id: number;
                name: string;
                email: string;
                avatar?: string;
                email_verified_at?: string | null;
                created_at?: string;
                updated_at?: string;
            };
        };
        name?: string;
        quote?: {
            message: string;
            author: string;
        };
        sidebarOpen?: boolean;
        ziggy?: {
            location: string;
        };
        [key: string]: unknown;
    }
}

// Navigation types
export interface NavItem {
    title: string;
    href?: string;
    icon?: React.ComponentType<{ className?: string }> | null;
    isActive?: boolean;
    items?: NavItem[]; // Para submenús
}

export interface BreadcrumbItem {
    title: string;
    href?: string;
}

export interface Auth {
    user: User;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at?: string | null;
    created_at?: string;
    updated_at?: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface SharedData {
    auth: Auth;
    name?: string;
    quote?: {
        message: string;
        author: string;
    };
    sidebarOpen?: boolean;
    ziggy?: {
        location: string;
    };
    [key: string]: unknown;
}
