/**
 * Iconos estandarizados para FormSections
 * Mantiene consistencia visual en toda la aplicación
 */

import {
    User,
    Lock,
    Phone,
    Shield,
    Key,
    Building2,
    Settings,
    Clock,
    Tag,
    CreditCard
} from 'lucide-react';

// Iconos por tipo de sección
export const SECTION_ICONS = {
    // Información personal/usuario
    personalInfo: User,
    userInfo: User,

    // Contacto
    contactInfo: Phone,

    // Seguridad y permisos
    security: Lock,
    changePassword: Lock,
    roleInfo: Shield,
    permissions: Key,

    // Información básica/general
    basicInfo: Building2,

    // Configuración
    services: Settings,

    // Tiempo/horarios
    schedule: Clock,

    // Tipos y categorías
    typeInfo: Tag,

    // Información del sistema
    systemInfo: CreditCard,
} as const;

// Mapeo específico por entidad para casos especiales
export const ENTITY_ICONS = {
    user: {
        info: User,
        security: Lock,
        system: CreditCard,
    },
    customer: {
        personal: User,
        contact: Phone,
        security: Lock,
        system: CreditCard,
    },
    role: {
        info: Shield,
        permissions: Key,
    },
    restaurant: {
        basic: Building2,
        services: Settings,
        schedule: Clock,
    },
    customerType: {
        info: Tag,
    }
} as const;

export type SectionIconKey = keyof typeof SECTION_ICONS;
export type EntityIconKey = keyof typeof ENTITY_ICONS;