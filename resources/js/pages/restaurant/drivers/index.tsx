import { CheckCircle, Clock, Phone, Truck, Users, XCircle } from 'lucide-react';

import { ACTIVE_STATUS_CONFIGS, StatusBadge, StatusConfig } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import RestaurantLayout from '@/layouts/restaurant-layout';
import { Driver } from '@/types';

interface Props {
    drivers: Driver[];
}

/**
 * Configuraciones de disponibilidad
 */
const AVAILABILITY_CONFIGS: Record<string, StatusConfig> = {
    available: {
        color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 border border-green-200 dark:border-green-700',
        text: 'Disponible',
        icon: <CheckCircle className="h-3 w-3" />,
    },
    unavailable: {
        color: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-600',
        text: 'No Disponible',
        icon: <XCircle className="h-3 w-3" />,
    },
    default: {
        color: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-600',
        text: 'Desconocido',
        icon: <Clock className="h-3 w-3" />,
    },
};

/**
 * Pagina de motoristas del restaurante (solo lectura)
 */
export default function RestaurantDriversIndex({ drivers }: Props) {
    const activeDrivers = drivers.filter((d) => d.is_active);
    const availableDrivers = drivers.filter((d) => d.is_active && d.is_available);

    return (
        <RestaurantLayout title="Motoristas">
            <div className="flex flex-col gap-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                            <Truck className="h-5 w-5 text-primary" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight lg:text-3xl">
                                Motoristas
                            </h1>
                            <p className="text-sm text-muted-foreground">
                                Motoristas asignados a tu restaurante
                            </p>
                        </div>
                    </div>
                </div>

                {/* Stats */}
                <div className="grid gap-4 sm:grid-cols-3">
                    <Card>
                        <CardContent className="flex items-center gap-4 p-4">
                            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-muted">
                                <Users className="h-5 w-5" />
                            </div>
                            <div>
                                <p className="text-2xl font-bold">{drivers.length}</p>
                                <p className="text-sm text-muted-foreground">Total</p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="flex items-center gap-4 p-4">
                            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900">
                                <CheckCircle className="h-5 w-5 text-green-600 dark:text-green-400" />
                            </div>
                            <div>
                                <p className="text-2xl font-bold">{activeDrivers.length}</p>
                                <p className="text-sm text-muted-foreground">Activos</p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="flex items-center gap-4 p-4">
                            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                                <Truck className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div>
                                <p className="text-2xl font-bold">{availableDrivers.length}</p>
                                <p className="text-sm text-muted-foreground">Disponibles</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Lista de motoristas */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Users className="h-5 w-5" />
                            Listado de Motoristas
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {drivers.length === 0 ? (
                            <div className="py-12 text-center">
                                <Truck className="mx-auto h-12 w-12 text-muted-foreground/50" />
                                <p className="mt-4 text-muted-foreground">
                                    No hay motoristas asignados a tu restaurante
                                </p>
                            </div>
                        ) : (
                            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                {drivers.map((driver) => (
                                    <Card key={driver.id} className="overflow-hidden">
                                        <CardContent className="p-4">
                                            <div className="flex items-start gap-4">
                                                {/* Avatar/Icono */}
                                                <div
                                                    className={`flex h-12 w-12 items-center justify-center rounded-full ${
                                                        driver.is_available && driver.is_active
                                                            ? 'bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-400'
                                                            : 'bg-muted text-muted-foreground'
                                                    }`}
                                                >
                                                    {driver.name
                                                        .split(' ')
                                                        .map((n) => n[0])
                                                        .join('')
                                                        .toUpperCase()
                                                        .slice(0, 2)}
                                                </div>

                                                {/* Info */}
                                                <div className="flex-1 space-y-1">
                                                    <h3 className="font-semibold">{driver.name}</h3>
                                                    {driver.phone && (
                                                        <div className="flex items-center gap-1 text-sm text-muted-foreground">
                                                            <Phone className="h-3 w-3" />
                                                            <span>{driver.phone}</span>
                                                        </div>
                                                    )}
                                                </div>
                                            </div>

                                            {/* Badges de estado */}
                                            <div className="mt-4 flex flex-wrap items-center gap-2">
                                                <StatusBadge
                                                    status={driver.is_active ? 'active' : 'inactive'}
                                                    configs={ACTIVE_STATUS_CONFIGS}
                                                    className="text-xs"
                                                />
                                                <StatusBadge
                                                    status={driver.is_available ? 'available' : 'unavailable'}
                                                    configs={AVAILABILITY_CONFIGS}
                                                    className="text-xs"
                                                />
                                                {driver.active_orders_count !== undefined &&
                                                    driver.active_orders_count > 0 && (
                                                        <Badge variant="secondary" className="text-xs">
                                                            {driver.active_orders_count} orden(es)
                                                        </Badge>
                                                    )}
                                            </div>
                                        </CardContent>
                                    </Card>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Nota informativa */}
                <p className="text-center text-sm text-muted-foreground">
                    La gestion de motoristas se realiza desde el panel de administracion.
                </p>
            </div>
        </RestaurantLayout>
    );
}
