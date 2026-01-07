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
        reordered: { label: 'Reordenado', color: 'purple' },
        badges_updated: { label: 'Badges actualizados', color: 'purple' },

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

        badge_type_created: { label: 'Tipo de badge creado', color: 'green' },
        badge_type_updated: { label: 'Tipo de badge actualizado', color: 'blue' },
        badge_type_deleted: { label: 'Tipo de badge eliminado', color: 'red' },
        badge_type_restored: { label: 'Tipo de badge restaurado', color: 'yellow' },
        badge_type_force_deleted: { label: 'Tipo de badge eliminado permanentemente', color: 'red' },

        product_variant_created: { label: 'Variante de producto creada', color: 'green' },
        product_variant_updated: { label: 'Variante de producto actualizada', color: 'blue' },
        product_variant_deleted: { label: 'Variante de producto eliminada', color: 'red' },
        product_variant_restored: { label: 'Variante de producto restaurada', color: 'yellow' },
        product_variant_force_deleted: { label: 'Variante de producto eliminada permanentemente', color: 'red' },

        promotional_banner_created: { label: 'Banner creado', color: 'green' },
        promotional_banner_updated: { label: 'Banner actualizado', color: 'blue' },
        promotional_banner_deleted: { label: 'Banner eliminado', color: 'red' },
        promotional_banner_restored: { label: 'Banner restaurado', color: 'yellow' },
        promotional_banner_force_deleted: { label: 'Banner eliminado permanentemente', color: 'red' },

        support_ticket_created: { label: 'Ticket de soporte creado', color: 'green' },
        support_ticket_updated: { label: 'Ticket de soporte actualizado', color: 'blue' },
        support_ticket_deleted: { label: 'Ticket de soporte eliminado', color: 'red' },
        support_ticket_restored: { label: 'Ticket de soporte restaurado', color: 'yellow' },
        support_ticket_force_deleted: { label: 'Ticket de soporte eliminado permanentemente', color: 'red' },

        support_reason_created: { label: 'Razon de soporte creada', color: 'green' },
        support_reason_updated: { label: 'Razon de soporte actualizada', color: 'blue' },
        support_reason_deleted: { label: 'Razon de soporte eliminada', color: 'red' },
        support_reason_restored: { label: 'Razon de soporte restaurada', color: 'yellow' },
        support_reason_force_deleted: { label: 'Razon de soporte eliminada permanentemente', color: 'red' },

        legal_document_created: { label: 'Documento legal creado', color: 'green' },
        legal_document_updated: { label: 'Documento legal actualizado', color: 'blue' },
        legal_document_deleted: { label: 'Documento legal eliminado', color: 'red' },
        legal_document_restored: { label: 'Documento legal restaurado', color: 'yellow' },
        legal_document_force_deleted: { label: 'Documento legal eliminado permanentemente', color: 'red' },

        customer_address_created: { label: 'Direccion de cliente creada', color: 'green' },
        customer_address_updated: { label: 'Direccion de cliente actualizada', color: 'blue' },
        customer_address_deleted: { label: 'Direccion de cliente eliminada', color: 'red' },
        customer_address_restored: { label: 'Direccion de cliente restaurada', color: 'yellow' },
        customer_address_force_deleted: { label: 'Direccion de cliente eliminada permanentemente', color: 'red' },

        customer_nit_created: { label: 'NIT de cliente creado', color: 'green' },
        customer_nit_updated: { label: 'NIT de cliente actualizado', color: 'blue' },
        customer_nit_deleted: { label: 'NIT de cliente eliminado', color: 'red' },
        customer_nit_restored: { label: 'NIT de cliente restaurado', color: 'yellow' },
        customer_nit_force_deleted: { label: 'NIT de cliente eliminado permanentemente', color: 'red' },

        customer_device_created: { label: 'Dispositivo de cliente creado', color: 'green' },
        customer_device_updated: { label: 'Dispositivo de cliente actualizado', color: 'blue' },
        customer_device_deleted: { label: 'Dispositivo de cliente eliminado', color: 'red' },
        customer_device_restored: { label: 'Dispositivo de cliente restaurado', color: 'yellow' },
        customer_device_force_deleted: { label: 'Dispositivo de cliente eliminado permanentemente', color: 'red' },

        points_setting_created: { label: 'Configuracion de puntos creada', color: 'green' },
        points_setting_updated: { label: 'Configuracion de puntos actualizada', color: 'blue' },
        points_setting_deleted: { label: 'Configuracion de puntos eliminada', color: 'red' },
        points_setting_restored: { label: 'Configuracion de puntos restaurada', color: 'yellow' },
        points_setting_force_deleted: { label: 'Configuracion de puntos eliminada permanentemente', color: 'red' },

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
