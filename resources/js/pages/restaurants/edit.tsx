import { useForm } from '@inertiajs/react';
import { Building2, Clock, Mail, MapPin, Phone, Settings, Navigation, FileText } from 'lucide-react';
import React from 'react';

import { Button } from '@/components/ui/button';
import { EditPageLayout } from '@/components/edit-page-layout';
import { FormSection } from '@/components/form-section';
import { EditRestaurantsSkeleton } from '@/components/skeletons';
import { Checkbox } from '@/components/ui/checkbox';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { PLACEHOLDERS, AUTOCOMPLETE } from '@/constants/ui-constants';

interface Restaurant {
    id: number;
    name: string;
    address: string;
    latitude: number | null;
    longitude: number | null;
    is_active: boolean;
    delivery_active: boolean;
    pickup_active: boolean;
    phone: string | null;
    email: string | null;
    schedule: Record<string, { is_open: boolean; open: string; close: string }> | null;
    minimum_order_amount: number;
    estimated_delivery_time: number;
    geofence_kml: string | null;
    created_at: string;
    updated_at: string;
}

interface EditPageProps {
    restaurant: Restaurant;
}

interface RestaurantFormData {
    name: string;
    address: string;
    latitude: string;
    longitude: string;
    is_active: boolean;
    delivery_active: boolean;
    pickup_active: boolean;
    phone: string;
    email: string;
    schedule: Record<string, { is_open: boolean; open: string; close: string }>;
    minimum_order_amount: string;
    estimated_delivery_time: string;
}

export default function RestaurantEdit({ restaurant }: EditPageProps) {
    const { data, setData, put, processing, errors } = useForm<RestaurantFormData>({
        name: restaurant.name,
        address: restaurant.address,
        latitude: restaurant.latitude?.toString() || '',
        longitude: restaurant.longitude?.toString() || '',
        is_active: restaurant.is_active,
        delivery_active: restaurant.delivery_active,
        pickup_active: restaurant.pickup_active,
        phone: restaurant.phone || '',
        email: restaurant.email || '',
        schedule: restaurant.schedule || {
            monday: { is_open: true, open: '08:00', close: '22:00' },
            tuesday: { is_open: true, open: '08:00', close: '22:00' },
            wednesday: { is_open: true, open: '08:00', close: '22:00' },
            thursday: { is_open: true, open: '08:00', close: '22:00' },
            friday: { is_open: true, open: '08:00', close: '22:00' },
            saturday: { is_open: true, open: '08:00', close: '22:00' },
            sunday: { is_open: true, open: '08:00', close: '22:00' },
        },
        minimum_order_amount: restaurant.minimum_order_amount.toString(),
        estimated_delivery_time: restaurant.estimated_delivery_time.toString(),
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('restaurants.update', restaurant.id));
    };

    const handleScheduleChange = (day: string, field: string, value: boolean | string) => {
        setData('schedule', {
            ...data.schedule,
            [day]: {
                ...data.schedule[day],
                [field]: value,
            },
        });
    };

    const dayLabels = {
        monday: 'Lunes',
        tuesday: 'Martes',
        wednesday: 'Miércoles',
        thursday: 'Jueves',
        friday: 'Viernes',
        saturday: 'Sábado',
        sunday: 'Domingo',
    };

    const hasGeofence = !!restaurant.geofence_kml;

    return (
        <EditPageLayout
            title="Editar Restaurante"
            description={`Modifica los datos de "${restaurant.name}"`}
            backHref={route('restaurants.index')}
            onSubmit={handleSubmit}
            processing={processing}
            pageTitle={`Editar ${restaurant.name}`}
            loading={false}
            loadingSkeleton={EditRestaurantsSkeleton}
        >
            <FormSection icon={Building2} title="Información Básica">
                <FormField label="Nombre" error={errors.name} required>
                    <Input
                        id="name"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        autoComplete={AUTOCOMPLETE.organizationName}
                    />
                </FormField>

                <FormField label="Dirección" error={errors.address} required>
                    <div className="relative">
                        <MapPin className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                        <Input
                            id="address"
                            value={data.address}
                            onChange={(e) => setData('address', e.target.value)}
                            placeholder={PLACEHOLDERS.address}
                            className="pl-10"
                            autoComplete={AUTOCOMPLETE.address}
                        />
                    </div>
                </FormField>

                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <FormField label="Latitud" error={errors.latitude}>
                        <div className="relative">
                            <Navigation className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                            <Input
                                id="latitude"
                                type="number"
                                step="any"
                                value={data.latitude}
                                onChange={(e) => setData('latitude', e.target.value)}
                                placeholder={PLACEHOLDERS.latitude}
                                className="pl-10"
                            />
                        </div>
                    </FormField>

                    <FormField label="Longitud" error={errors.longitude}>
                        <div className="relative">
                            <Navigation className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                            <Input
                                id="longitude"
                                type="number"
                                step="any"
                                value={data.longitude}
                                onChange={(e) => setData('longitude', e.target.value)}
                                placeholder={PLACEHOLDERS.longitude}
                                className="pl-10"
                            />
                        </div>
                    </FormField>
                </div>

                <FormField label="Teléfono" error={errors.phone}>
                    <div className="relative">
                        <Phone className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                        <Input
                            id="phone"
                            value={data.phone}
                            onChange={(e) => setData('phone', e.target.value)}
                            placeholder={PLACEHOLDERS.phone}
                            className="pl-10"
                            autoComplete={AUTOCOMPLETE.phone}
                        />
                    </div>
                </FormField>

                <FormField label="Email" error={errors.email}>
                    <div className="relative">
                        <Mail className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                        <Input
                            id="email"
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            placeholder={PLACEHOLDERS.email}
                            className="pl-10"
                            autoComplete={AUTOCOMPLETE.email}
                        />
                    </div>
                </FormField>
            </FormSection>

            <FormSection icon={FileText} title="Geocerca KML" description="Administrar archivo KML para definir zona de entrega">
                <div className="space-y-4">
                    <div className="flex items-center justify-between p-4 border rounded-lg bg-gray-50 dark:bg-gray-800">
                        <div className="flex items-center gap-3">
                            <FileText className="h-5 w-5 text-gray-500" />
                            <div>
                                <p className="font-medium">Estado de Geocerca</p>
                                <p className="text-sm text-gray-600 dark:text-gray-400">
                                    {hasGeofence ? 'KML cargado y configurado' : 'Sin geocerca definida'}
                                </p>
                            </div>
                        </div>
                        <div className="flex items-center gap-2">
                            {hasGeofence && (
                                <Badge className="bg-green-100 text-green-800 border-green-200">
                                    <FileText className="h-3 w-3 mr-1" />
                                    KML Activo
                                </Badge>
                            )}
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => window.open(route('restaurants.kml.show', restaurant.id), '_blank')}
                                className="flex items-center gap-2"
                            >
                                <FileText className="h-4 w-4" />
                                Gestionar KML
                            </Button>
                        </div>
                    </div>
                </div>
            </FormSection>

            <FormSection icon={Settings} title="Configuración de Servicios">
                <div className="space-y-3">
                    <div className="flex items-center space-x-2">
                        <Checkbox
                            id="is_active"
                            checked={data.is_active}
                            onCheckedChange={(checked) => setData('is_active', checked as boolean)}
                        />
                        <Label htmlFor="is_active">Restaurante Activo</Label>
                    </div>

                    <div className="flex items-center space-x-2">
                        <Checkbox
                            id="delivery_active"
                            checked={data.delivery_active}
                            onCheckedChange={(checked) => setData('delivery_active', checked as boolean)}
                        />
                        <Label htmlFor="delivery_active">Servicio de Delivery</Label>
                    </div>

                    <div className="flex items-center space-x-2">
                        <Checkbox
                            id="pickup_active"
                            checked={data.pickup_active}
                            onCheckedChange={(checked) => setData('pickup_active', checked as boolean)}
                        />
                        <Label htmlFor="pickup_active">Servicio de Pickup</Label>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <FormField label="Monto Mínimo de Pedido (Q)" error={errors.minimum_order_amount}>
                        <Input
                            id="minimum_order_amount"
                            type="number"
                            step="0.01"
                            value={data.minimum_order_amount}
                            onChange={(e) => setData('minimum_order_amount', e.target.value)}
                            placeholder={PLACEHOLDERS.amount}
                        />
                    </FormField>

                    <FormField label="Tiempo Estimado de Entrega (min)" error={errors.estimated_delivery_time}>
                        <Input
                            id="estimated_delivery_time"
                            type="number"
                            value={data.estimated_delivery_time}
                            onChange={(e) => setData('estimated_delivery_time', e.target.value)}
                            placeholder={PLACEHOLDERS.estimatedTime}
                        />
                    </FormField>
                </div>
            </FormSection>

            <FormSection icon={Clock} title="Horarios de Atención" description="Define los horarios de atención para cada día de la semana">
                <div className="space-y-4">
                    {Object.entries(dayLabels).map(([day, label]) => (
                        <div key={day} className="flex flex-col space-y-2 sm:flex-row sm:items-center sm:space-y-0 sm:space-x-4">
                            <div className="w-24 flex-shrink-0">
                                <Label className="font-medium">{label}</Label>
                            </div>
                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    checked={data.schedule[day]?.is_open || false}
                                    onCheckedChange={(checked) => handleScheduleChange(day, 'is_open', checked as boolean)}
                                />
                                <Label className="text-sm">Abierto</Label>
                            </div>
                            {data.schedule[day]?.is_open && (
                                <div className="flex flex-col space-y-2 sm:flex-row sm:items-center sm:space-y-0 sm:space-x-4">
                                    <div className="flex items-center space-x-2">
                                        <Label className="text-sm font-medium">De:</Label>
                                        <Input
                                            type="time"
                                            value={data.schedule[day]?.open || '08:00'}
                                            onChange={(e) => handleScheduleChange(day, 'open', e.target.value)}
                                            className="w-32"
                                        />
                                    </div>
                                    <div className="flex items-center space-x-2">
                                        <Label className="text-sm font-medium">A:</Label>
                                        <Input
                                            type="time"
                                            value={data.schedule[day]?.close || '22:00'}
                                            onChange={(e) => handleScheduleChange(day, 'close', e.target.value)}
                                            className="w-32"
                                        />
                                    </div>
                                </div>
                            )}
                        </div>
                    ))}
                </div>
            </FormSection>
        </EditPageLayout>
    );
}