import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Building2, Calendar, CheckCircle, Clock, Mail, MapPin, Phone, User, XCircle } from 'lucide-react';

import { ACTIVE_STATUS_CONFIGS, StatusBadge } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { Driver, Order } from '@/types';

interface ShowDriverProps {
    driver: Driver & {
        orders?: Order[];
    };
}

/**
 * Configuraciones de estado de disponibilidad
 */
const AVAILABILITY_STATUS_CONFIGS: Record<string, { color: string; text: string; icon: React.ReactNode }> = {
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
};

const formatDate = (dateString: string | null): string => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('es-GT', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        timeZone: 'America/Guatemala',
    });
};

const formatDateShort = (dateString: string | null): string => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('es-GT', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

export default function ShowDriver({ driver }: ShowDriverProps) {
    const handleToggleAvailability = () => {
        router.patch(
            `/drivers/${driver.id}/toggle-availability`,
            {},
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    return (
        <AppLayout>
            <Head title={`Motorista - ${driver.name}`} />

            <div className="mx-auto flex h-full w-full flex-1 flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                            <User className="h-5 w-5 text-primary" />
                        </div>
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">{driver.name}</h1>
                            <p className="text-muted-foreground">{driver.email}</p>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" onClick={handleToggleAvailability} disabled={!driver.is_active}>
                            {driver.is_available ? (
                                <>
                                    <XCircle className="mr-2 h-4 w-4" />
                                    Marcar No Disponible
                                </>
                            ) : (
                                <>
                                    <CheckCircle className="mr-2 h-4 w-4" />
                                    Marcar Disponible
                                </>
                            )}
                        </Button>
                        <Link href={`/drivers/${driver.id}/edit`}>
                            <Button variant="outline">Editar</Button>
                        </Link>
                        <Link href="/drivers">
                            <Button variant="outline">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Volver
                            </Button>
                        </Link>
                    </div>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    {/* Informacion Personal */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <User className="h-5 w-5" />
                                Informacion Personal
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <span className="text-xs text-muted-foreground">Nombre</span>
                                    <p className="font-medium">{driver.name}</p>
                                </div>
                                <div>
                                    <span className="text-xs text-muted-foreground">Estado</span>
                                    <div className="mt-1">
                                        <StatusBadge
                                            status={driver.is_active ? 'active' : 'inactive'}
                                            configs={ACTIVE_STATUS_CONFIGS}
                                            className="text-xs"
                                        />
                                    </div>
                                </div>
                            </div>
                            <div>
                                <span className="text-xs text-muted-foreground">Email</span>
                                <div className="flex items-center gap-2">
                                    <Mail className="h-4 w-4 text-muted-foreground" />
                                    <p className="font-medium">{driver.email}</p>
                                </div>
                            </div>
                            {driver.phone && (
                                <div>
                                    <span className="text-xs text-muted-foreground">Telefono</span>
                                    <div className="flex items-center gap-2">
                                        <Phone className="h-4 w-4 text-muted-foreground" />
                                        <p className="font-medium">{driver.phone}</p>
                                    </div>
                                </div>
                            )}
                            <div>
                                <span className="text-xs text-muted-foreground">Disponibilidad</span>
                                <div className="mt-1">
                                    <StatusBadge
                                        status={driver.is_available ? 'available' : 'unavailable'}
                                        configs={AVAILABILITY_STATUS_CONFIGS}
                                        className="text-xs"
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Ubicacion */}
                    {driver.current_latitude && driver.current_longitude && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <MapPin className="h-5 w-5" />
                                    Ubicacion
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="flex items-center gap-2">
                                    <MapPin className="h-4 w-4 text-muted-foreground" />
                                    <p className="text-sm">
                                        {driver.current_latitude.toFixed(6)}, {driver.current_longitude.toFixed(6)}
                                    </p>
                                </div>
                                {driver.last_location_update && (
                                    <p className="text-xs text-muted-foreground mt-2">
                                        Actualizado: {formatDate(driver.last_location_update)}
                                    </p>
                                )}
                            </CardContent>
                        </Card>
                    )}

                    {/* Restaurante */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Building2 className="h-5 w-5" />
                                Restaurante
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {driver.restaurant ? (
                                <div className="space-y-2">
                                    <p className="font-medium">{driver.restaurant.name}</p>
                                    <p className="text-sm text-muted-foreground">{driver.restaurant.address}</p>
                                    {driver.restaurant.phone && (
                                        <div className="flex items-center gap-2">
                                            <Phone className="h-3 w-3 text-muted-foreground" />
                                            <span className="text-sm">{driver.restaurant.phone}</span>
                                        </div>
                                    )}
                                </div>
                            ) : (
                                <p className="text-sm text-muted-foreground">Sin restaurante asignado</p>
                            )}
                        </CardContent>
                    </Card>

                    {/* Ordenes Activas */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Clock className="h-5 w-5" />
                                Ordenes Activas ({driver.active_orders_count || 0})
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {driver.orders && driver.orders.length > 0 ? (
                                <div className="space-y-3">
                                    {driver.orders.slice(0, 5).map((order) => (
                                        <div key={order.id} className="rounded-lg border bg-muted/50 p-3">
                                            <div className="flex items-start justify-between">
                                                <div className="flex-1">
                                                    <p className="font-medium">#{order.order_number}</p>
                                                    <p className="text-sm text-muted-foreground">
                                                        {order.delivery_address || 'Sin direccion'}
                                                    </p>
                                                </div>
                                                <Badge variant="secondary" className="text-xs">
                                                    {order.status}
                                                </Badge>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-center text-sm text-muted-foreground py-4">No hay ordenes activas</p>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Informacion del Sistema */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Calendar className="h-5 w-5" />
                            Informacion del Sistema
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
                            <div>
                                <span className="text-xs text-muted-foreground">ID</span>
                                <p className="font-mono font-medium">#{driver.id}</p>
                            </div>
                            <div>
                                <span className="text-xs text-muted-foreground">Fecha de Registro</span>
                                <p className="font-medium">{formatDate(driver.created_at)}</p>
                            </div>
                            <div>
                                <span className="text-xs text-muted-foreground">Ultima Actualizacion</span>
                                <p className="font-medium">{formatDate(driver.updated_at)}</p>
                            </div>
                            <div>
                                <span className="text-xs text-muted-foreground">Ultimo Login</span>
                                <p className="font-medium">{formatDate(driver.last_login_at)}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
