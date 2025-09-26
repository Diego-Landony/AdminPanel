import { useForm } from '@inertiajs/react';
import { Building2, Clock, Mail, MapPin, Phone, Settings, User } from 'lucide-react';
import React from 'react';

import { CreatePageLayout } from '@/components/create-page-layout';
import { FormSection } from '@/components/form-section';
import { Checkbox } from '@/components/ui/checkbox';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

interface RestaurantFormData {
    name: string;
    description: string;
    address: string;
    is_active: boolean;
    delivery_active: boolean;
    pickup_active: boolean;
    phone: string;
    schedule: Record<string, { is_open: boolean; open: string; close: string }>;
    minimum_order_amount: string;
    delivery_fee: string;
    estimated_delivery_time: string;
    email: string;
    manager_name: string;
    sort_order: string;
}

export default function RestaurantCreate() {
    const { data, setData, post, processing, errors } = useForm<RestaurantFormData>({
        name: '',
        description: '',
        address: '',
        is_active: true,
        delivery_active: true,
        pickup_active: true,
        phone: '',
        schedule: {
            monday: { is_open: true, open: '08:00', close: '22:00' },
            tuesday: { is_open: true, open: '08:00', close: '22:00' },
            wednesday: { is_open: true, open: '08:00', close: '22:00' },
            thursday: { is_open: true, open: '08:00', close: '22:00' },
            friday: { is_open: true, open: '08:00', close: '22:00' },
            saturday: { is_open: true, open: '08:00', close: '22:00' },
            sunday: { is_open: true, open: '08:00', close: '22:00' },
        },
        minimum_order_amount: '50.00',
        delivery_fee: '25.00',
        estimated_delivery_time: '30',
        email: '',
        manager_name: '',
        sort_order: '100',
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

    const dayLabels = {
        monday: 'Lunes',
        tuesday: 'Martes',
        wednesday: 'Miércoles',
        thursday: 'Jueves',
        friday: 'Viernes',
        saturday: 'Sábado',
        sunday: 'Domingo',
    };

    return (
        <CreatePageLayout
            title="Crear Nuevo Restaurante"
            description="Completa la información del restaurante"
            backHref={route('restaurants.index')}
            backLabel="Volver a Restaurantes"
            onSubmit={handleSubmit}
            submitLabel="Crear Restaurante"
            processing={processing}
            pageTitle="Crear Restaurante"
        >
            <FormSection icon={Building2} title="Información Básica" description="Datos principales del restaurante">
                <FormField label="Nombre" error={errors.name} required>
                    <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder="Nombre del restaurante" />
                </FormField>

                <FormField label="Descripción" error={errors.description}>
                    <Textarea
                        id="description"
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                        placeholder="Descripción del restaurante"
                        rows={3}
                    />
                </FormField>

                <FormField label="Dirección" error={errors.address} required>
                    <div className="relative">
                        <MapPin className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                        <Input
                            id="address"
                            value={data.address}
                            onChange={(e) => setData('address', e.target.value)}
                            placeholder="Dirección del restaurante"
                            className="pl-10"
                        />
                    </div>
                </FormField>

                <FormField label="Teléfono" error={errors.phone}>
                    <div className="relative">
                        <Phone className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                        <Input
                            id="phone"
                            value={data.phone}
                            onChange={(e) => setData('phone', e.target.value)}
                            placeholder="+502 1234 5678"
                            className="pl-10"
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
                            placeholder="email@restaurante.com"
                            className="pl-10"
                        />
                    </div>
                </FormField>

                <FormField label="Nombre del Encargado" error={errors.manager_name}>
                    <div className="relative">
                        <User className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                        <Input
                            id="manager_name"
                            value={data.manager_name}
                            onChange={(e) => setData('manager_name', e.target.value)}
                            placeholder="Nombre del encargado"
                            className="pl-10"
                        />
                    </div>
                </FormField>
            </FormSection>

            <FormSection icon={Settings} title="Configuración de Servicios" description="Servicios y configuración operativa">
                <div className="space-y-3">
                    <div className="flex items-center space-x-2">
                        <Checkbox id="is_active" checked={data.is_active} onCheckedChange={(checked) => setData('is_active', checked as boolean)} />
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

                <div className="grid gap-4">
                    <FormField label="Monto Mínimo de Pedido (Q)" error={errors.minimum_order_amount}>
                        <Input
                            id="minimum_order_amount"
                            type="number"
                            step="0.01"
                            value={data.minimum_order_amount}
                            onChange={(e) => setData('minimum_order_amount', e.target.value)}
                            placeholder="50.00"
                        />
                    </FormField>

                    <FormField label="Tarifa de Delivery (Q)" error={errors.delivery_fee}>
                        <Input
                            id="delivery_fee"
                            type="number"
                            step="0.01"
                            value={data.delivery_fee}
                            onChange={(e) => setData('delivery_fee', e.target.value)}
                            placeholder="25.00"
                        />
                    </FormField>

                    <FormField label="Tiempo Estimado de Entrega (min)" error={errors.estimated_delivery_time}>
                        <Input
                            id="estimated_delivery_time"
                            type="number"
                            value={data.estimated_delivery_time}
                            onChange={(e) => setData('estimated_delivery_time', e.target.value)}
                            placeholder="30"
                        />
                    </FormField>

                    <FormField label="Orden de Visualización" error={errors.sort_order}>
                        <Input
                            id="sort_order"
                            type="number"
                            value={data.sort_order}
                            onChange={(e) => setData('sort_order', e.target.value)}
                            placeholder="100"
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
        </CreatePageLayout>
    );
}
