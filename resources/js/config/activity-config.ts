/**
 * Configuración centralizada para el sistema de actividad
 */

export interface ActivityEventConfig {
    label: string;
    color: string;
}

export const ACTIVITY_CONFIG = {
    /**
     * Configuración de tipos de eventos
     */
    eventTypes: {
        // Eventos genéricos
        created: { label: 'Creado', color: 'green' },
        updated: { label: 'Actualizado', color: 'blue' },
        deleted: { label: 'Eliminado', color: 'red' },
        restored: { label: 'Restaurado', color: 'yellow' },
        force_deleted: { label: 'Eliminado permanentemente', color: 'red' },

        // Eventos de autenticación
        login: { label: 'Inicio de sesión', color: 'green' },
        logout: { label: 'Cierre de sesión', color: 'blue' },

        // Eventos legacy (mantenidos por compatibilidad)
        user_created: { label: 'Usuario creado', color: 'green' },
        user_updated: { label: 'Usuario actualizado', color: 'blue' },
        user_deleted: { label: 'Usuario eliminado', color: 'red' },
        user_restored: { label: 'Usuario restaurado', color: 'yellow' },
        user_force_deleted: { label: 'Usuario eliminado permanentemente', color: 'red' },

        role_created: { label: 'Rol creado', color: 'green' },
        role_updated: { label: 'Rol actualizado', color: 'blue' },
        role_deleted: { label: 'Rol eliminado', color: 'red' },
        role_restored: { label: 'Rol restaurado', color: 'yellow' },
        role_force_deleted: { label: 'Rol eliminado permanentemente', color: 'red' },
        role_users_updated: { label: 'Usuarios de rol actualizados', color: 'blue' },

        customer_created: { label: 'Cliente creado', color: 'green' },
        customer_updated: { label: 'Cliente actualizado', color: 'blue' },
        customer_deleted: { label: 'Cliente eliminado', color: 'red' },
        customer_restored: { label: 'Cliente restaurado', color: 'yellow' },
        customer_force_deleted: { label: 'Cliente eliminado permanentemente', color: 'red' },

        customer_type_created: { label: 'Tipo de cliente creado', color: 'green' },
        customer_type_updated: { label: 'Tipo de cliente actualizado', color: 'blue' },
        customer_type_deleted: { label: 'Tipo de cliente eliminado', color: 'red' },
        customer_type_restored: { label: 'Tipo de cliente restaurado', color: 'yellow' },
        customer_type_force_deleted: { label: 'Tipo de cliente eliminado permanentemente', color: 'red' },

        restaurant_created: { label: 'Restaurante creado', color: 'green' },
        restaurant_updated: { label: 'Restaurante actualizado', color: 'blue' },
        restaurant_deleted: { label: 'Restaurante eliminado', color: 'red' },
        restaurant_restored: { label: 'Restaurante restaurado', color: 'yellow' },
        restaurant_force_deleted: { label: 'Restaurante eliminado permanentemente', color: 'red' },

        theme_changed: { label: 'Tema cambiado', color: 'blue' },
    } as Record<string, ActivityEventConfig>,

    /**
     * Mapeo de colores base para Tailwind classes
     */
    colorClasses: {
        green: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        blue: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        red: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
        yellow: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        purple: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
        gray: 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300',
    },

    /**
     * Obtiene el color Tailwind class para un tipo de evento
     */
    getColor(eventType: string): string {
        const config = this.eventTypes[eventType];
        if (!config) {
            return this.colorClasses.gray;
        }
        return this.colorClasses[config.color as keyof typeof this.colorClasses] || this.colorClasses.gray;
    },

    /**
     * Obtiene el label traducido para un tipo de evento
     */
    getLabel(eventType: string): string {
        const config = this.eventTypes[eventType];
        if (!config) {
            // Fallback: capitalizar y reemplazar guiones bajos
            return eventType.charAt(0).toUpperCase() + eventType.slice(1).replace(/_/g, ' ');
        }
        return config.label;
    },
};
