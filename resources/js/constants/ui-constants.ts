/**
 * Constantes de UI para mantener consistencia en toda la aplicación
 */

// Placeholders estandarizados - Solo valores técnicos o con formato específico
export const PLACEHOLDERS = {
    // Autenticación y credenciales
    email: 'usuario@email.com',
    password: '••••••••',

    // Contacto y ubicación (con formato específico)
    phone: '+502 1234-5678',
    address: '5ta Av. 12-34, Zona 4, Ciudad',
    location: 'Guatemala, Guatemala',
    latitude: '14.634915',
    longitude: '-90.506882',

    // Identificación
    nit: '12345678-9',
    subwayCard: '1234567890',

    // Valores numéricos con unidad/formato
    price: '0.00',
    percentage: '0',
    amount: '00.00',
    deliveryFee: '00.00',
    estimatedTime: '00',

    // Auto-generados
    sku: 'Se genera automáticamente',
    sortOrder: '0',

    // Búsqueda
    search: 'Buscar...',

    // Campos de texto genéricos
    name: 'Nombre',
    description: 'Descripción',

    // Campos específicos de entidades
    roleName: 'Nombre del rol',
    categoryName: 'Nombre de la categoría',
    productName: 'Nombre del producto',
    comboLabel: 'Etiqueta del elemento',
    choiceGroupLabel: 'Ej: Elige tu sub de 15cm',

    // Selects genéricos
    selectGender: 'Selecciona el género',
    selectCustomerType: 'Selecciona el tipo de cliente (opcional)',
    selectVariant: 'Selecciona una variante',
    selectCategory: 'Buscar categoría...',
    selectProduct: 'Buscar producto...',
    selectRole: 'Buscar roles...',

    // Búsquedas específicas
    searchUserEventDescription: 'Buscar por usuario, evento, descripción...',
    searchEventTypes: 'Tipos de evento...',
    searchUsers: 'Usuarios...',
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
        roleAdded: 'Rol agregado exitosamente',
        roleRemoved: 'Rol removido exitosamente',
        customerCreated: 'Cliente creado exitosamente',
        customerUpdated: 'Cliente actualizado exitosamente',
        optionAdded: 'Opción agregada al grupo',
        optionRemoved: 'Opción eliminada del grupo',
        itemAdded: 'Item agregado al combo',
        itemRemoved: 'Item eliminado del combo',
    },
    error: {
        server: 'Error del servidor. Inténtalo de nuevo.',
        delete: 'Error al eliminar',
        updateUsers: 'Error al actualizar usuarios del rol',
        updateRoles: 'Error al actualizar roles',
        connectionUsers: 'Error de conexión al actualizar usuarios',
        connectionRoles: 'Error de conexión al actualizar roles',
        removeOwnAdminRole: 'No puedes remover tu propio rol de administrador',
        serverUser: 'Error del servidor al actualizar el usuario. Inténtalo de nuevo.',
        serverUserCreate: 'Error del servidor al crear el usuario. Inténtalo de nuevo.',
        serverRole: 'Error del servidor al actualizar el rol. Inténtalo de nuevo.',
        serverRoleCreate: 'Error del servidor al crear el rol. Inténtalo de nuevo.',
        serverCustomerType: 'Error del servidor al crear el tipo de cliente. Inténtalo de nuevo.',
        deleteUser: 'Error al eliminar el usuario',
        deleteRestaurant: 'Error al eliminar el restaurante',
        deleteCustomer: 'Error al eliminar el cliente',
        // Network and data loading errors
        networkConnection: 'Error de conexión. Verifica tu conexión a internet.',
        dataLoading: 'Error al cargar los datos. Intenta recargar la página.',
        serverTimeout: 'El servidor no responde. Inténtalo de nuevo en unos momentos.',
        invalidData: 'Los datos recibidos no son válidos.',
        permissionDenied: 'No tienes permisos para realizar esta acción.',
        sessionExpired: 'Tu sesión ha expirado. Por favor, inicia sesión nuevamente.',
        rateLimited: 'Demasiadas solicitudes. Espera unos momentos antes de intentar de nuevo.',
        // Choice groups errors
        minOptionsRequired: 'Se requieren al menos 2 opciones en el grupo',
        duplicateOption: 'Esta opción ya existe en el grupo',
        variantInconsistency: 'Todas las variantes deben ser del mismo tamaño',
        cannotDeleteProductInUse: 'No se puede eliminar un producto usado en combos',
        choiceGroupLabelRequired: 'La etiqueta del grupo es obligatoria',
        choiceGroupOptionsRequired: 'Cada grupo debe tener al menos 2 opciones',
        fixedItemProductRequired: 'Los items fijos deben tener un producto seleccionado',
        minItemRequired: 'Debe haber al menos 1 item en la promoción',
    },
    warning: {
        inactiveOptionsDetected: 'El grupo tiene opciones inactivas',
        comboPartiallyAvailable: 'Combo disponible con opciones limitadas',
    },
} as const;

// Descripciones de validación estandarizadas
export const FIELD_DESCRIPTIONS = {
    password: 'Mínimo 6 caracteres',
    passwordOptional: 'Deja en blanco si no deseas cambiar la contraseña',
    email: 'Dirección de correo electrónico válida',
    emailVerification: 'Cambiar el email requerirá nueva verificación',
    nit: '(opcional)',
    subwayCard: '',
    phoneFormat: '+502 1234-5678',
    passwordMinimum6: 'Mínimo 6 caracteres',
    passwordSecurity6: 'Asegúrate de que tu cuenta use una contraseña de al menos 6 caracteres para mantener la seguridad',
} as const;

// Títulos y descriptions de FormSections estandarizados
export const FORM_SECTIONS = {
    personalInfo: {
        title: 'Información Personal',
        description: 'Datos básicos del cliente',
    },
    userInfo: {
        title: 'Información del Usuario',
        description: 'Datos básicos del usuario',
    },
    contactInfo: {
        title: 'Información de Contacto',
        description: 'Datos de contacto y ubicación del cliente',
    },
    security: {
        title: 'Seguridad',
        description: 'Configuración de acceso del cliente',
    },
    changePassword: {
        title: 'Cambiar Contraseña',
        description: 'Opcional: Cambiar la contraseña del usuario',
    },
    roleInfo: {
        title: 'Información del Rol',
        description: 'Datos básicos del nuevo rol',
    },
    roleInfoEdit: {
        title: 'Información Básica',
        description: 'Datos principales del rol',
    },
    permissions: {
        title: 'Permisos del Rol',
        description: 'Selecciona las acciones que este rol puede realizar en cada página',
    },
    basicInfo: {
        title: 'Información Básica',
        description: 'Datos principales del restaurante',
    },
    services: {
        title: 'Configuración de Servicios',
        description: 'Servicios y configuración operativa',
    },
    schedule: {
        title: 'Horarios de Atención',
        description: 'Define los horarios de atención para cada día de la semana',
    },
    systemInfo: {
        title: 'Información del Sistema',
        description: 'Datos del sistema y metadatos',
    },
    comboItems: {
        title: 'Items del Combo',
        description: 'Define productos fijos o grupos de elección para el combo',
    },
    choiceGroupOptions: {
        title: 'Opciones Disponibles',
        description: 'Mínimo 2 opciones requeridas para el grupo de elección',
    },
} as const;

// Descripciones de páginas estandarizadas
export const PAGE_DESCRIPTIONS = {
    dailySpecial: 'Configura precios especiales para productos específicos en días determinados de la semana.',
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

// Símbolos de moneda
export const CURRENCY = {
    symbol: 'Q',
    code: 'GTQ',
    name: 'Quetzal',
} as const;

// Tipos de items de combo
export const COMBO_ITEM_TYPES = {
    fixed: 'fixed',
    choiceGroup: 'choice_group',
} as const;

// Labels y textos para Combos
export const COMBO_LABELS = {
    itemTypes: {
        fixed: 'Item Fijo',
        choiceGroup: 'Grupo de Elección',
    },
    itemTypesDescription: {
        fixed: 'Producto específico incluido en el combo',
        choiceGroup: 'Grupo de productos donde el cliente elige uno',
    },
    addFixedItem: 'Agregar Item Fijo',
    addChoiceGroup: 'Agregar Grupo de Elección',
    addOption: 'Agregar Opción',
    removeOption: 'Eliminar Opción',
    minOptionsRequired: 'Mínimo 2 opciones requeridas',
    choiceGroupLabelRequired: 'La etiqueta es obligatoria',
    choiceGroupOptionsRequired: 'Debes agregar al menos 2 opciones',
    variantConsistencyError: 'Todas las opciones deben ser de la misma variante',
    choiceGroupExample: 'Ej: Elige tu bebida',
} as const;
