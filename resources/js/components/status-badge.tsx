import React from 'react';
import { Badge } from '@/components/ui/badge';
import { Circle } from 'lucide-react';

export interface StatusConfig {
    color: string;
    text: string;
    icon?: React.ReactNode;
}

interface StatusBadgeProps {
    status: string;
    configs: Record<string, StatusConfig>;
    showIcon?: boolean;
    className?: string;
}

export function StatusBadge({ status, configs, showIcon = true, className = '' }: StatusBadgeProps) {
    const config = configs[status] || configs.default || { color: 'bg-gray-100 text-gray-700', text: status };
    
    return (
        <Badge className={`${config.color} ${className}`}>
            {showIcon && config.icon && <span className="mr-1">{config.icon}</span>}
            {config.text}
        </Badge>
    );
}

// Configuraciones predefinidas pero extensibles
export const CONNECTION_STATUS_CONFIGS: Record<string, StatusConfig> = {
    online: {
        color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 border border-green-200 dark:border-green-700',
        text: 'En línea',
        icon: <Circle className="h-2 w-2 text-green-600" />
    },
    recent: {
        color: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 border border-blue-200 dark:border-blue-700',
        text: 'Reciente',
        icon: <Circle className="h-2 w-2 text-blue-600" />
    },
    offline: {
        color: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-600',
        text: 'Desconectado',
        icon: <Circle className="h-2 w-2 text-gray-400" />
    },
    never: {
        color: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 border border-red-200 dark:border-red-700',
        text: 'Nunca',
        icon: <Circle className="h-2 w-2 text-red-600" />
    },
    default: {
        color: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-600',
        text: 'Desconocido',
        icon: <Circle className="h-2 w-2 text-gray-400" />
    }
};

export const CUSTOMER_TYPE_COLORS: Record<string, StatusConfig> = {
    gray: {
        color: 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300 border border-gray-200 dark:border-gray-700',
        text: ''
    },
    orange: {
        color: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300 border border-orange-200 dark:border-orange-700',
        text: ''
    },
    slate: {
        color: 'bg-slate-100 text-slate-800 dark:bg-slate-900 dark:text-slate-300 border border-slate-200 dark:border-slate-700',
        text: ''
    },
    yellow: {
        color: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300 border border-yellow-200 dark:border-yellow-700',
        text: ''
    },
    purple: {
        color: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300 border border-purple-200 dark:border-purple-700',
        text: ''
    },
    green: {
        color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 border border-green-200 dark:border-green-700',
        text: ''
    },
    blue: {
        color: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 border border-blue-200 dark:border-blue-700',
        text: ''
    },
    red: {
        color: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 border border-red-200 dark:border-red-700',
        text: ''
    },
    default: {
        color: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 border border-blue-200 dark:border-blue-700',
        text: ''
    }
};