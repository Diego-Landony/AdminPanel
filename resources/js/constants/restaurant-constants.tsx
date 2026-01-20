import {
    AlertCircle,
    Check,
    CheckCircle,
    Clock,
    Package,
    ShoppingBag,
    Truck,
    XCircle,
} from 'lucide-react';
import { StatusConfig } from '@/components/status-badge';

/**
 * Configuración de estados de órdenes para el panel de restaurante
 */
export const ORDER_STATUS_CONFIGS: Record<string, StatusConfig> = {
    pending: {
        color: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300 border border-yellow-200 dark:border-yellow-700',
        text: 'Pendiente',
        icon: <Clock className="h-3 w-3" />,
    },
    confirmed: {
        color: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 border border-blue-200 dark:border-blue-700',
        text: 'Confirmado',
        icon: <CheckCircle className="h-3 w-3" />,
    },
    preparing: {
        color: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 border border-blue-200 dark:border-blue-700',
        text: 'Preparando',
        icon: <Package className="h-3 w-3" />,
    },
    ready: {
        color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 border border-green-200 dark:border-green-700',
        text: 'Lista',
        icon: <CheckCircle className="h-3 w-3" />,
    },
    out_for_delivery: {
        color: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300 border border-orange-200 dark:border-orange-700',
        text: 'En Camino',
        icon: <Truck className="h-3 w-3" />,
    },
    delivered: {
        color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 border border-green-200 dark:border-green-700',
        text: 'Entregada',
        icon: <CheckCircle className="h-3 w-3" />,
    },
    completed: {
        color: 'bg-green-200 text-green-900 dark:bg-green-800 dark:text-green-200 border border-green-300 dark:border-green-600',
        text: 'Completada',
        icon: <CheckCircle className="h-3 w-3" />,
    },
    cancelled: {
        color: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 border border-red-200 dark:border-red-700',
        text: 'Cancelada',
        icon: <XCircle className="h-3 w-3" />,
    },
    default: {
        color: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-600',
        text: 'Desconocido',
        icon: <AlertCircle className="h-3 w-3" />,
    },
};

/**
 * Configuración de tipos de servicio
 */
export const SERVICE_TYPE_CONFIGS: Record<string, StatusConfig> = {
    delivery: {
        color: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 border border-blue-200 dark:border-blue-700',
        text: 'Delivery',
        icon: <Truck className="h-3 w-3" />,
    },
    pickup: {
        color: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300 border border-orange-200 dark:border-orange-700',
        text: 'Pickup',
        icon: <ShoppingBag className="h-3 w-3" />,
    },
    default: {
        color: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-600',
        text: 'Desconocido',
        icon: <AlertCircle className="h-3 w-3" />,
    },
};

/**
 * Configuración de estados de pago
 */
export const PAYMENT_STATUS_CONFIGS: Record<string, StatusConfig> = {
    pending: {
        color: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300 border border-yellow-200 dark:border-yellow-700',
        text: 'Pendiente',
        icon: <Clock className="h-3 w-3" />,
    },
    paid: {
        color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 border border-green-200 dark:border-green-700',
        text: 'Pagado',
        icon: <CheckCircle className="h-3 w-3" />,
    },
    refunded: {
        color: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 border border-red-200 dark:border-red-700',
        text: 'Reembolsado',
        icon: <XCircle className="h-3 w-3" />,
    },
    default: {
        color: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-600',
        text: 'Desconocido',
        icon: <AlertCircle className="h-3 w-3" />,
    },
};

/**
 * Etiquetas para métodos de pago
 */
export const PAYMENT_METHOD_LABELS: Record<string, string> = {
    cash: 'Efectivo',
    card: 'Tarjeta',
    online: 'En Linea',
};

/**
 * Configuración de estados para la tabla de órdenes (con colores de fila)
 */
export const ORDER_TABLE_STATUS_CONFIG: Record<string, {
    label: string;
    rowBg: string;
    textColor: string;
    icon: React.ReactNode;
    description: string;
}> = {
    pending: {
        label: 'Nueva Orden',
        rowBg: 'bg-yellow-50 dark:bg-yellow-950/30 border-l-4 border-l-yellow-500',
        textColor: 'text-yellow-700 dark:text-yellow-400',
        icon: <Clock className="h-4 w-4" />,
        description: 'Esperando que aceptes',
    },
    preparing: {
        label: 'En Preparación',
        rowBg: 'bg-blue-50 dark:bg-blue-950/30 border-l-4 border-l-blue-500',
        textColor: 'text-blue-700 dark:text-blue-400',
        icon: <Package className="h-4 w-4" />,
        description: 'Preparando el pedido',
    },
    ready: {
        label: 'Lista para Entregar',
        rowBg: 'bg-green-50 dark:bg-green-950/30 border-l-4 border-l-green-500',
        textColor: 'text-green-700 dark:text-green-400',
        icon: <Check className="h-4 w-4" />,
        description: 'Pedido listo',
    },
    out_for_delivery: {
        label: 'En Camino',
        rowBg: 'bg-purple-50 dark:bg-purple-950/30 border-l-4 border-l-purple-500',
        textColor: 'text-purple-700 dark:text-purple-400',
        icon: <Truck className="h-4 w-4" />,
        description: 'Motorista en camino',
    },
    delivered: {
        label: 'Entregada',
        rowBg: 'bg-gray-50 dark:bg-gray-900/30 border-l-4 border-l-gray-400',
        textColor: 'text-gray-600 dark:text-gray-400',
        icon: <Check className="h-4 w-4" />,
        description: 'Pedido entregado',
    },
    completed: {
        label: 'Completada',
        rowBg: 'bg-gray-50 dark:bg-gray-900/30 border-l-4 border-l-gray-400',
        textColor: 'text-gray-600 dark:text-gray-400',
        icon: <Check className="h-4 w-4" />,
        description: 'Pedido completado',
    },
    cancelled: {
        label: 'Cancelada',
        rowBg: 'bg-red-50 dark:bg-red-950/30 border-l-4 border-l-red-500',
        textColor: 'text-red-600 dark:text-red-400',
        icon: <XCircle className="h-4 w-4" />,
        description: 'Pedido cancelado',
    },
};
