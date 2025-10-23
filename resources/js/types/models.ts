/**
 * Tipos TypeScript para los modelos del backend
 *
 * Este archivo centraliza todas las interfaces de los modelos
 * para mantener consistencia entre frontend y backend
 */

/**
 * Usuario del sistema (administrador)
 */
export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    last_login_at: string | null;
    last_activity_at: string | null;
    timezone: string;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
    // Computed attributes
    status: 'online' | 'recent' | 'offline' | 'never';
    is_online: boolean;
    // Relations
    roles?: Role[];
}

/**
 * Cliente de Subway
 */
export interface Customer {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    subway_card: string;
    birth_date: string;
    gender: string | null;
    customer_type_id: number | null;
    phone: string | null;
    address: string | null;
    nit: string | null;
    fcm_token: string | null;
    last_login_at: string | null;
    last_activity_at: string | null;
    last_purchase_at: string | null;
    points: number;
    points_updated_at: string | null;
    timezone: string;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
    // Computed attributes
    status: 'online' | 'recent' | 'offline' | 'never';
    is_online: boolean;
    // Relations
    customer_type?: CustomerType;
    addresses?: CustomerAddress[];
}

/**
 * Tipo de cliente (Regular, Premium, VIP, etc.)
 */
export interface CustomerType {
    id: number;
    name: string;
    points_required: number;
    multiplier: number;
    color: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    // Relations
    customers?: Customer[];
}

/**
 * Dirección de entrega del cliente
 */
export interface CustomerAddress {
    id: number;
    customer_id: number;
    label: string;
    address_line: string;
    latitude: number;
    longitude: number;
    delivery_notes: string | null;
    is_default: boolean;
    created_at: string;
    updated_at: string;
    // Relations
    customer?: Customer;
}

/**
 * Restaurante de Subway
 */
export interface Restaurant {
    id: number;
    name: string;
    latitude: number | null;
    longitude: number | null;
    geofence_kml: string | null;
    address: string;
    is_active: boolean;
    delivery_active: boolean;
    pickup_active: boolean;
    phone: string | null;
    schedule: RestaurantSchedule | null;
    minimum_order_amount: number;
    email: string | null;
    estimated_delivery_time: number | null;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
    // Computed attributes
    status_text?: string;
    today_schedule?: string;
    coordinates?: { lat: number; lng: number };
}

/**
 * Horario de restaurante (JSON)
 */
export interface RestaurantSchedule {
    monday: DaySchedule;
    tuesday: DaySchedule;
    wednesday: DaySchedule;
    thursday: DaySchedule;
    friday: DaySchedule;
    saturday: DaySchedule;
    sunday: DaySchedule;
}

/**
 * Horario de un día específico
 */
export interface DaySchedule {
    is_open: boolean;
    open: string; // formato "HH:mm"
    close: string; // formato "HH:mm"
}

/**
 * Rol del sistema
 */
export interface Role {
    id: number;
    name: string;
    description: string | null;
    is_system: boolean;
    created_at: string;
    updated_at: string;
    // Relations
    users?: User[];
    permissions?: Permission[];
}

/**
 * Permiso del sistema
 */
export interface Permission {
    id: number;
    name: string;
    display_name: string;
    description: string | null;
    group: string;
    created_at: string;
    updated_at: string;
    // Relations
    roles?: Role[];
}

/**
 * Log de actividad del sistema
 */
export interface ActivityLog {
    id: number;
    user_id: number | null;
    event_type: string;
    target_model: string | null;
    target_id: number | null;
    description: string;
    old_values: Record<string, unknown> | null;
    new_values: Record<string, unknown> | null;
    user_agent: string | null;
    created_at: string;
    updated_at: string;
    // Relations
    user?: User;
}

/**
 * Actividad de usuario (login, navegación, etc.)
 */
export interface UserActivity {
    id: number;
    user_id: number;
    activity_type: string;
    description: string;
    user_agent: string | null;
    url: string | null;
    method: string | null;
    metadata: Record<string, unknown> | null;
    created_at: string;
    updated_at: string;
    // Relations
    user?: User;
}

/**
 * Respuesta paginada genérica
 */
export interface PaginatedResponse<T> {
    data: T[];
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
    from: number;
    to: number;
    links: PaginationLink[];
}

/**
 * Link de paginación
 */
export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}
