/**
 * Constantes de UI para mantener consistencia en toda la aplicación
 */

// Placeholders estandarizados
export const PLACEHOLDERS = {
    email: 'correo@email.com',
    password: '••••••••',
    name: 'Nombre completo',
    phone: '+502 1234-5678',
    address: 'Dirección completa',
    description: 'Describe las responsabilidades y alcance...',
    search: 'Buscar...',
    location: 'Ciudad, Departamento',
    nit: '12345678-9',
    subwayCard: '1234567890',
    amount: '50.00',
    deliveryFee: '25.00',
    estimatedTime: '30',
    sortOrder: '100',
    restaurantName: 'Nombre del restaurante',
    managerName: 'Nombre del encargado',
    roleName: 'Ej: usuario',
    roleNameEdit: 'ej: Gerente',
    searchUsers: 'Buscar usuarios...',
    authEmail: 'usuario@email.com',
    authPassword: 'Tu contraseña',
    authPasswordConfirm: 'Confirma tu contraseña',
    authName: 'Tu nombre completo',
    authPasswordMinimum: 'Mínimo 6 caracteres',
    authPasswordNew: 'Confirma tu nueva contraseña',
    authPasswordCurrent: 'Tu contraseña actual',
    settingsPassword: 'Contraseña actual',
    settingsPasswordMinimum: 'Mínimo 6 caracteres',
    settingsPasswordConfirm: 'Confirmar nueva contraseña',
    settingsProfileName: 'Nombre completo',
    settingsProfileEmail: 'Dirección de correo electrónico',
} as const;

// Mensajes de notificación estandarizados
export const NOTIFICATIONS = {
    serverError: 'Error del servidor. Inténtalo de nuevo.',
    connectionError: 'Error de conexión. Inténtalo de nuevo.',
    success: {
        created: 'Creado exitosamente',
        updated: 'Actualizado exitosamente',
        deleted: 'Eliminado exitosamente',
        userAdded: 'Usuario agregado al rol',
        userRemoved: 'Usuario removido del rol',
        customerCreated: 'Cliente creado exitosamente',
        customerUpdated: 'Cliente actualizado exitosamente',
    },
    error: {
        delete: 'Error al eliminar',
        updateUsers: 'Error al actualizar usuarios del rol',
        connectionUsers: 'Error de conexión al actualizar usuarios',
        serverUser: 'Error del servidor al actualizar el usuario. Inténtalo de nuevo.',
        serverUserCreate: 'Error del servidor al crear el usuario. Inténtalo de nuevo.',
        serverRole: 'Error del servidor al actualizar el rol. Inténtalo de nuevo.',
        serverRoleCreate: 'Error del servidor al crear el rol. Inténtalo de nuevo.',
        serverCustomerType: 'Error del servidor al crear el tipo de cliente. Inténtalo de nuevo.',
        deleteUser: 'Error al eliminar el usuario',
        deleteRestaurant: 'Error al eliminar el restaurante',
        deleteCustomer: 'Error al eliminar el cliente',
    }
} as const;

// Descripciones de validación estandarizadas
export const FIELD_DESCRIPTIONS = {
    password: 'Mínimo 6 caracteres',
    passwordOptional: 'Deja en blanco si no deseas cambiar la contraseña',
    email: 'Dirección de correo electrónico válida',
    emailVerification: 'Cambiar el email requerirá nueva verificación',
    nit: 'Número de Identificación Tributaria (opcional)',
    subwayCard: 'Número único de identificación del cliente',
    phoneFormat: 'Formato: +502 1234-5678',
    passwordMinimum6: 'Mínimo 6 caracteres',
    passwordSecurity6: 'Asegúrate de que tu cuenta use una contraseña de al menos 6 caracteres para mantener la seguridad',
} as const;

// Títulos y descriptions de FormSections estandarizados
export const FORM_SECTIONS = {
    personalInfo: {
        title: 'Información Personal',
        description: 'Datos básicos del cliente'
    },
    userInfo: {
        title: 'Información del Usuario',
        description: 'Datos básicos del usuario'
    },
    contactInfo: {
        title: 'Información de Contacto',
        description: 'Datos de contacto y ubicación del cliente'
    },
    security: {
        title: 'Seguridad',
        description: 'Configuración de acceso del cliente'
    },
    changePassword: {
        title: 'Cambiar Contraseña',
        description: 'Opcional: Cambiar la contraseña del usuario'
    },
    roleInfo: {
        title: 'Información del Rol',
        description: 'Datos básicos del nuevo rol'
    },
    roleInfoEdit: {
        title: 'Información Básica',
        description: 'Datos principales del rol'
    },
    permissions: {
        title: 'Permisos del Rol',
        description: 'Selecciona las acciones que este rol puede realizar en cada página'
    },
    basicInfo: {
        title: 'Información Básica',
        description: 'Datos principales del restaurante'
    },
    services: {
        title: 'Configuración de Servicios',
        description: 'Servicios y configuración operativa'
    },
    schedule: {
        title: 'Horarios de Atención',
        description: 'Define los horarios de atención para cada día de la semana'
    },
    systemInfo: {
        title: 'Información del Sistema',
        description: 'Datos del sistema y metadatos'
    }
} as const;

// AutoComplete values estandarizados
export const AUTOCOMPLETE = {
    name: 'name',
    email: 'email',
    newPassword: 'new-password',
    currentPassword: 'current-password',
    off: 'off',
    username: 'username',
    organizationName: 'organization',
    address: 'address',
    phone: 'tel',
} as const;