import { ReactNode } from 'react';

/**
 * Common type definitions used across the application
 *
 * This file contains shared interfaces and types that are frequently
 * duplicated across multiple pages and components, specifically for
 * pagination, data tables, and common UI patterns.
 *
 * These types are automatically exported from '@/types' via index.d.ts
 *
 * @example
 * ```typescript
 * import { PaginatedData, Filters, Column } from '@/types';
 *
 * interface UsersPageProps {
 *   users: PaginatedData<User>;
 *   filters: Filters;
 * }
 *
 * const columns: Column<User>[] = [
 *   { key: 'name', title: 'Name', sortable: true },
 *   { key: 'email', title: 'Email' }
 * ];
 * ```
 */

/**
 * Standard pagination response structure used by Laravel
 *
 * This is the simplified version without links, used by most DataTable implementations.
 * For the full Laravel pagination response with links, use PaginatedResponse<T> from models.ts
 *
 * @example
 * ```typescript
 * interface UsersPageProps {
 *   users: PaginatedData<User>;
 * }
 * ```
 */
export interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
}

/**
 * Column configuration for DataTable component
 *
 * @example
 * ```typescript
 * const columns: Column<User>[] = [
 *   {
 *     key: 'name',
 *     title: 'Name',
 *     sortable: true,
 *     width: 'lg'
 *   },
 *   {
 *     key: 'status',
 *     title: 'Status',
 *     render: (user) => <StatusBadge status={user.status} />
 *   }
 * ];
 * ```
 */
export interface Column<T> {
    key: string;
    title: string;
    width?: 'xs' | 'sm' | 'md' | 'lg' | 'xl' | 'auto' | 'full';
    align?: 'left' | 'center' | 'right';
    truncate?: boolean | number;
    sortable?: boolean;
    render?: (item: T, value: unknown) => ReactNode;
    className?: string;
}

/**
 * Statistics display for DataTable headers
 */
export interface DataTableStat {
    title: string;
    value: number | string;
    icon: ReactNode;
}

/**
 * Standard filter configuration used across index pages
 */
export interface Filters {
    search?: string | null;
    per_page: number;
    sort_field?: string;
    sort_direction?: 'asc' | 'desc';
}

/**
 * Filters for DataTable component (single-column sorting)
 */
export type DataTableFilters = Filters;
