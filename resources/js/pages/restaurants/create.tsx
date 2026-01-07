import { useForm } from '@inertiajs/react';
import { Building2, Clock, DollarSign, Hash, Mail, MapPin, Network, Phone, Settings } from 'lucide-react';
import React from 'react';

import { CreatePageLayout } from '@/components/create-page-layout';
import { FormSection } from '@/components/form-section';
import { CreateRestaurantsSkeleton } from '@/components/skeletons';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { AUTOCOMPLETE, PLACEHOLDERS } from '@/constants/ui-constants';

interface RestaurantFormData {
    name: string;
    address: string;
    price_location: 'capital' | 'interior';
    is_active: boolean;
    delivery_active: boolean;
    pickup_active: boolean;
    phone: string;
    email: string;
    ip: string;
    franchise_number: string;
    schedule: Record<string, { is_open: boolean; open: string; close: string }>;
    minimum_order_amount: string;
    estimated_delivery_time: string;
}

export default function RestaurantCreate() {
    const { data, setData, post, processing, errors } = useForm<RestaurantFormData>({
        name: '',
        address: '',
        price_location: 'capital',
        is_active: true,
        delivery_active: true,
        pickup_active: true,
        phone: '',
        email: '',
        ip: '',
        franchise_number: '',
        schedule: {
            monday: { is_open: true, open: '08:00', close: '22:00' },
            tuesday: { is_open: true, open: '08:00', close: '22:00' },
            wednesday: { is_open: true, open: '08:00', close: '22:00' },
            thursday: { is_open: true, open: '08:00', close: '22:00' },
            friday: { is_open: true, open: '08:00', close: '22:00' },
            saturday: { is_open: true, open: '08:00', close: '22:00' },
            sunday: { is_open: true, open: '08:00', close: '22:00' },
        },
        minimum_order_amount: '',
        estimated_delivery_time: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('restaurants.store'));
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

    const dayLabels: Record<string, string> = {
        monday: 'Lun',
        tuesday: 'Mar',
        wednesday: 'Mie',
        thursday: 'Jue',
        friday: 'Vie',
        saturday: 'Sab',
        sunday: 'Dom',
    };

    return (
        <CreatePageLayout
            title="Nuevo Restaurante"
            backHref={route('restaurants.index')}
            onSubmit={handleSubmit}
            processing={processing}
            pageTitle="Crear Restaurante"
            loading={processing}
            loadingSkeleton={CreateRestaurantsSkeleton}
        >
            <div className="space-y-8">
                {/* Informacion Basica */}
                <Card>
                    <CardContent className="pt-6">
                        <FormSection icon={Building2} title="Informacion Basica" description="Datos principales del restaurante">
                            <div className="space-y-6">
                                <FormField label="Nombre" error={errors.name} required>
                                    <Input
                                        id="name"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        autoComplete={AUTOCOMPLETE.organizationName}
                                    />
                                </FormField>

                                <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                                    <div className="md:col-span-2">
                                        <FormField label="Direccion" error={errors.address} required>
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
                                    </div>

                                    <FormField label="Tipo de Precio" error={errors.price_location} required>
                                        <div className="relative">
                                            <DollarSign className="pointer-events-none absolute top-3 left-3 z-10 h-4 w-4 text-muted-foreground" />
                                            <Select
                                                value={data.price_location}
                                                onValueChange={(value: 'capital' | 'interior') => setData('price_location', value)}
                                            >
                                                <SelectTrigger className="pl-10">
                                                    <SelectValue placeholder="Seleccionar tipo" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="capital">Capital</SelectItem>
                                                    <SelectItem value="interior">Interior</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    </FormField>
                                </div>

                                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <FormField label="Telefono" error={errors.phone}>
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
                                </div>

                                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <FormField label="IP del Restaurante" error={errors.ip}>
                                        <div className="relative">
                                            <Network className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                                            <Input
                                                id="ip"
                                                value={data.ip}
                                                onChange={(e) => setData('ip', e.target.value)}
                                                placeholder={PLACEHOLDERS.ip}
                                                className="pl-10"
                                            />
                                        </div>
                                    </FormField>

                                    <FormField label="Numero de Franquicia" error={errors.franchise_number}>
                                        <div className="relative">
                                            <Hash className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                                            <Input
                                                id="franchise_number"
                                                value={data.franchise_number}
                                                onChange={(e) => setData('franchise_number', e.target.value)}
                                                placeholder={PLACEHOLDERS.franchiseNumber}
                                                className="pl-10"
                                            />
                                        </div>
                                    </FormField>
                                </div>
                            </div>
                        </FormSection>
                    </CardContent>
                </Card>

                {/* Configuracion de Servicios */}
                <Card>
                    <CardContent className="pt-6">
                        <FormSection icon={Settings} title="Configuracion de Servicios" description="Opciones de delivery y pickup">
                            <div className="space-y-6">
                                <div className="grid grid-cols-1 gap-4 rounded-lg border p-4 md:grid-cols-3">
                                    <div className="flex items-center justify-between md:flex-col md:items-start md:gap-2">
                                        <Label htmlFor="is_active" className="cursor-pointer text-sm font-medium">
                                            Restaurante Activo
                                        </Label>
                                        <Switch
                                            id="is_active"
                                            checked={data.is_active}
                                            onCheckedChange={(checked) => setData('is_active', checked as boolean)}
                                        />
                                    </div>

                                    <div className="flex items-center justify-between md:flex-col md:items-start md:gap-2">
                                        <Label htmlFor="delivery_active" className="cursor-pointer text-sm font-medium">
                                            Servicio de Delivery
                                        </Label>
                                        <Switch
                                            id="delivery_active"
                                            checked={data.delivery_active}
                                            onCheckedChange={(checked) => setData('delivery_active', checked as boolean)}
                                        />
                                    </div>

                                    <div className="flex items-center justify-between md:flex-col md:items-start md:gap-2">
                                        <Label htmlFor="pickup_active" className="cursor-pointer text-sm font-medium">
                                            Servicio de Pickup
                                        </Label>
                                        <Switch
                                            id="pickup_active"
                                            checked={data.pickup_active}
                                            onCheckedChange={(checked) => setData('pickup_active', checked as boolean)}
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <FormField label="Monto Minimo de Pedido (Q)" error={errors.minimum_order_amount}>
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
                            </div>
                        </FormSection>
                    </CardContent>
                </Card>

                {/* Horarios de Atención */}
                <Card>
                    <CardContent className="pt-6">
                        <FormSection icon={Clock} title="Horarios de Atención" description="Define los horarios de atención para cada día de la semana">
                            <div className="space-y-4">
                                {Object.entries(dayLabels).map(([day, label]) => (
                                    <div key={day} className="flex flex-col space-y-2 sm:flex-row sm:items-center sm:space-y-0 sm:space-x-4">
                                        <div className="w-24 flex-shrink-0">
                                            <Label className="font-medium">{label}</Label>
                                        </div>
                                        <div className="flex items-center space-x-2">
                                            <Checkbox
                                                checked={data.schedule[day].is_open}
                                                onCheckedChange={(checked) => handleScheduleChange(day, 'is_open', checked as boolean)}
                                            />
                                            <Label className="text-sm">Abierto</Label>
                                        </div>
                                        {data.schedule[day].is_open && (
                                            <div className="flex flex-col space-y-2 sm:flex-row sm:items-center sm:space-y-0 sm:space-x-4">
                                                <div className="flex items-center space-x-2">
                                                    <Label className="text-sm font-medium">De:</Label>
                                                    <Input
                                                        type="time"
                                                        value={data.schedule[day].open}
                                                        onChange={(e) => handleScheduleChange(day, 'open', e.target.value)}
                                                        className="w-32"
                                                    />
                                                </div>
                                                <div className="flex items-center space-x-2">
                                                    <Label className="text-sm font-medium">A:</Label>
                                                    <Input
                                                        type="time"
                                                        value={data.schedule[day].close}
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
                    </CardContent>
                </Card>
            </div>
        </CreatePageLayout>
    );
}
