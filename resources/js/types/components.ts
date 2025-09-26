import { ReactNode } from 'react';

import { CustomerId, CustomerStatus, RoleId, UserId, UserStatus } from './branded';

/**
 * Tipos base para componentes reutilizables con type safety mejorada
 */

// Utility types para props comunes
export interface BaseComponentProps {
    /** Identificador único para el componente */
    id?: string;
    /** Clases CSS adicionales */
    className?: string;
    /** Props de data-* para testing */
    'data-testid'?: string;
}

export interface LoadingStateProps {
    /** Si el componente está en estado de carga */
    isLoading?: boolean;
    /** Texto personalizado para el estado de carga */
    loadingText?: string;
    /** Si se debe mostrar un skeleton en lugar de spinner */
    showSkeleton?: boolean;
}

export interface ErrorStateProps {
    /** Si hay un error */
    hasError?: boolean;
    /** Mensaje de error a mostrar */
    errorMessage?: string;
    /** Función para retry en caso de error */
    onRetry?: () => void;
}

// Tipos mejorados para DataTable
export interface DataTableColumn<T extends Record<string, unknown>> {
    /** Clave única de la columna */
    readonly key: keyof T | string;
    /** Título visible de la columna */
    readonly title: string;
    /** Ancho predefinido de la columna */
    readonly width?: 'xs' | 'sm' | 'md' | 'lg' | 'xl' | 'auto' | 'full';
    /** Alineación del texto */
    readonly align?: 'left' | 'center' | 'right';
    /** Si el texto debe truncarse (true o número de caracteres) */
    readonly truncate?: boolean | number;
    /** Si la columna es sorteable */
    readonly sortable?: boolean;
    /** Función para renderizar el contenido de la celda */
    readonly render?: (item: T, value: unknown) => ReactNode;
    /** Clases CSS adicionales */
    readonly className?: string;
    /** Tooltip descriptivo para la columna */
    readonly tooltip?: string;
    /** Si la columna está oculta en móviles */
    readonly hideOnMobile?: boolean;
}

export interface DataTableStat {
    readonly title: string;
    readonly value: number | string;
    readonly icon: ReactNode;
    readonly description?: string;
    readonly color?: 'default' | 'primary' | 'success' | 'warning' | 'error';
    readonly trend?: {
        value: number;
        isPositive: boolean;
    };
}

export interface PaginatedData<T> {
    readonly data: readonly T[];
    readonly current_page: number;
    readonly last_page: number;
    readonly per_page: number;
    readonly total: number;
    readonly from: number;
    readonly to: number;
}

export interface SortConfig {
    readonly field?: string;
    readonly direction?: 'asc' | 'desc';
}

export interface DataTableFilters extends BaseComponentProps {
    readonly search?: string | null;
    readonly per_page: number;
    readonly sort_field?: string;
    readonly sort_direction?: 'asc' | 'desc';
}

export interface DataTableProps<T extends Record<string, unknown>>
    extends BaseComponentProps,
        LoadingStateProps,
        ErrorStateProps {
    /** Título de la tabla */
    readonly title: string;
    /** Descripción de la tabla */
    readonly description: string;
    /** Datos paginados */
    readonly data: PaginatedData<T>;
    /** Configuración de columnas */
    readonly columns: readonly DataTableColumn<T>[];
    /** Estadísticas opcionales */
    readonly stats?: readonly DataTableStat[];
    /** Filtros aplicados */
    readonly filters: DataTableFilters;
    /** URL para crear nuevo elemento */
    readonly createUrl?: string;
    /** Texto del botón de crear */
    readonly createLabel?: string;
    /** Placeholder para búsqueda */
    readonly searchPlaceholder?: string;
    /** Componente skeleton para carga */
    readonly loadingSkeleton?: React.ComponentType<{ rows: number }>;
    /** Renderizador para tarjetas móviles */
    readonly renderMobileCard?: (item: T) => ReactNode;
    /** Callback para refresh */
    readonly onRefresh?: () => void;
    /** Si la tabla admite selección múltiple */
    readonly selectable?: boolean;
    /** Items seleccionados */
    readonly selectedItems?: readonly string[];
    /** Callback para cambio de selección */
    readonly onSelectionChange?: (selectedIds: readonly string[]) => void;
}

// Tipos específicos para entidades del dominio
export interface User {
    readonly id: UserId;
    readonly name: string;
    readonly email: string;
    readonly status: UserStatus;
    readonly roles: readonly Role[];
    readonly created_at: string;
    readonly updated_at: string;
    readonly email_verified_at?: string | null;
}

export interface Customer {
    readonly id: CustomerId;
    readonly name: string;
    readonly email: string;
    readonly phone?: string | null;
    readonly status: CustomerStatus;
    readonly customer_type_id?: string | null;
    readonly customer_type?: CustomerType | null;
    readonly nit?: string | null;
    readonly subway_card?: string | null;
    readonly address?: string | null;
    readonly created_at: string;
    readonly updated_at: string;
}

export interface CustomerType {
    readonly id: string;
    readonly name: string;
    readonly description?: string | null;
    readonly is_active: boolean;
    readonly created_at: string;
    readonly updated_at: string;
}

export interface Role {
    readonly id: RoleId;
    readonly name: string;
    readonly description?: string | null;
    readonly permissions: readonly Permission[];
    readonly users_count?: number;
    readonly created_at: string;
    readonly updated_at: string;
}

export interface Permission {
    readonly id: string;
    readonly name: string;
    readonly description?: string | null;
    readonly module: string;
    readonly action: string;
}

// Props para formularios con type safety mejorada
export interface FormFieldProps<T = unknown> extends BaseComponentProps {
    /** Nombre del campo */
    readonly name: string;
    /** Label del campo */
    readonly label: string;
    /** Valor actual */
    readonly value: T;
    /** Callback para cambio de valor */
    readonly onChange: (value: T) => void;
    /** Si el campo es requerido */
    readonly required?: boolean;
    /** Si el campo está deshabilitado */
    readonly disabled?: boolean;
    /** Texto de ayuda */
    readonly helpText?: string;
    /** Mensaje de error */
    readonly error?: string;
    /** Placeholder */
    readonly placeholder?: string;
    /** Autocompletado */
    readonly autoComplete?: string;
}

export interface FormSectionProps extends BaseComponentProps {
    /** Título de la sección */
    readonly title: string;
    /** Descripción de la sección */
    readonly description: string;
    /** Contenido de la sección */
    readonly children: ReactNode;
    /** Si la sección es collapsible */
    readonly collapsible?: boolean;
    /** Si la sección está colapsada por defecto */
    readonly defaultCollapsed?: boolean;
    /** Ícono para la sección */
    readonly icon?: ReactNode;
}

// Props para botones con variants type-safe
export type ButtonVariant = 'default' | 'destructive' | 'outline' | 'secondary' | 'ghost' | 'link';
export type ButtonSize = 'default' | 'sm' | 'lg' | 'icon';

export interface ButtonProps extends BaseComponentProps {
    /** Variante visual del botón */
    readonly variant?: ButtonVariant;
    /** Tamaño del botón */
    readonly size?: ButtonSize;
    /** Si el botón está deshabilitado */
    readonly disabled?: boolean;
    /** Si el botón está en estado de carga */
    readonly loading?: boolean;
    /** Texto de carga personalizado */
    readonly loadingText?: string;
    /** Ícono del botón */
    readonly icon?: ReactNode;
    /** Posición del ícono */
    readonly iconPosition?: 'left' | 'right';
    /** Callback de click */
    readonly onClick?: () => void;
    /** Tipo del botón */
    readonly type?: 'button' | 'submit' | 'reset';
    /** Contenido del botón */
    readonly children?: ReactNode;
}

// Props para modales y dialogs
export interface ModalProps extends BaseComponentProps {
    /** Si el modal está abierto */
    readonly isOpen: boolean;
    /** Callback para cerrar el modal */
    readonly onClose: () => void;
    /** Título del modal */
    readonly title: string;
    /** Descripción del modal */
    readonly description?: string;
    /** Contenido del modal */
    readonly children: ReactNode;
    /** Si se puede cerrar clickeando fuera */
    readonly closeOnOverlayClick?: boolean;
    /** Si se puede cerrar con ESC */
    readonly closeOnEscape?: boolean;
    /** Tamaño del modal */
    readonly size?: 'sm' | 'md' | 'lg' | 'xl' | 'full';
}

// Props para navegación y breadcrumbs
export interface BreadcrumbItem {
    readonly label: string;
    readonly href?: string;
    readonly current?: boolean;
}

export interface NavigationProps extends BaseComponentProps {
    /** Items del breadcrumb */
    readonly breadcrumbItems?: readonly BreadcrumbItem[];
    /** Título de la página */
    readonly pageTitle?: string;
    /** Subtítulo de la página */
    readonly pageDescription?: string;
    /** Acciones de la página */
    readonly pageActions?: ReactNode;
}

// Tipos para status y badges
export type StatusVariant = 'default' | 'active' | 'inactive' | 'pending' | 'success' | 'warning' | 'error';

export interface StatusBadgeProps extends BaseComponentProps {
    /** Valor del status */
    readonly status: string;
    /** Variante visual */
    readonly variant?: StatusVariant;
    /** Si debe mostrar un indicador */
    readonly withIndicator?: boolean;
    /** Texto personalizado */
    readonly label?: string;
}

// Eventos con type safety
export interface TableActionEvent<T> {
    readonly action: 'view' | 'edit' | 'delete' | 'duplicate' | string;
    readonly item: T;
    readonly index: number;
}

export interface FormSubmitEvent<T> {
    readonly data: T;
    readonly isValid: boolean;
    readonly errors: Record<string, string[]>;
}

// Configuración de filtros con types más específicos
export interface FilterOption<T = string> {
    readonly value: T;
    readonly label: string;
    readonly count?: number;
    readonly disabled?: boolean;
}

export interface FilterConfig<T = string> {
    readonly key: string;
    readonly label: string;
    readonly type: 'select' | 'multiselect' | 'search' | 'date' | 'daterange';
    readonly options?: readonly FilterOption<T>[];
    readonly placeholder?: string;
    readonly defaultValue?: T | readonly T[];
}

export interface AdvancedFiltersProps extends BaseComponentProps {
    /** Configuración de filtros */
    readonly filters: readonly FilterConfig[];
    /** Valores actuales */
    readonly values: Record<string, unknown>;
    /** Callback para cambio de valores */
    readonly onChange: (key: string, value: unknown) => void;
    /** Callback para aplicar filtros */
    readonly onApply: () => void;
    /** Callback para limpiar filtros */
    readonly onClear: () => void;
    /** Si los filtros están siendo aplicados */
    readonly isApplying?: boolean;
}