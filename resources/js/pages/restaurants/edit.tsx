import { PageProps } from '@/types';
import { useForm } from '@inertiajs/react';
import { Building2, Clock, Mail, MapPin, Phone, Settings, User } from 'lucide-react';

import { EditPageLayout } from '@/components/edit-page-layout';
import { FormSection } from '@/components/form-section';
import { EditRestaurantsSkeleton } from '@/components/skeletons';
import { Checkbox } from '@/components/ui/checkbox';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { PLACEHOLDERS, AUTOCOMPLETE } from '@/constants/ui-constants';

interface Restaurant {
    id: number;
    name: string;
    description: string;
    address: string;
    is_active: boolean;
    delivery_active: boolean;
    pickup_active: boolean;
    phone: string;
    schedule: Record<string, { is_open: boolean; open: string; close: string }> | null;
    minimum_order_amount: number;
    delivery_fee: number;
    estimated_delivery_time: number;
    image: string;
    email: string;
    manager_name: string;
    rating: number;
    total_reviews: number;
    sort_order: number;
    created_at: string;
    updated_at: string;
}

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

interface RestaurantEditPageProps extends PageProps {
    restaurant: Restaurant;
}

export default function RestaurantEdit({ restaurant }: RestaurantEditPageProps) {
    const defaultSchedule = {
        monday: { is_open: true, open: '08:00', close: '22:00' },
        tuesday: { is_open: true, open: '08:00', close: '22:00' },
        wednesday: { is_open: true, open: '08:00', close: '22:00' },
        thursday: { is_open: true, open: '08:00', close: '22:00' },
        friday: { is_open: true, open: '08:00', close: '22:00' },
        saturday: { is_open: true, open: '08:00', close: '22:00' },
        sunday: { is_open: true, open: '08:00', close: '22:00' },
    };

    const { data, setData, put, processing, errors } = useForm<RestaurantFormData>({
        name: restaurant.name,
        description: restaurant.description || '',
        address: restaurant.address,
        is_active: restaurant.is_active,
        delivery_active: restaurant.delivery_active,
        pickup_active: restaurant.pickup_active,
        phone: restaurant.phone || '',
        schedule: restaurant.schedule || defaultSchedule,
        minimum_order_amount: restaurant.minimum_order_amount.toString(),
        delivery_fee: restaurant.delivery_fee.toString(),
        estimated_delivery_time: restaurant.estimated_delivery_time?.toString() || '',
        email: restaurant.email || '',
        manager_name: restaurant.manager_name || '',
        sort_order: restaurant.sort_order.toString(),
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

    return (
        <EditPageLayout
            title="Editar Restaurante"
            description={`Actualiza la información de ${restaurant.name}`}
            backHref={route('restaurants.index')}
            onSubmit={handleSubmit}
            processing={processing}
            pageTitle={`Editar Restaurante - ${restaurant.name}`}
            loading={processing}
            loadingSkeleton={EditRestaurantsSkeleton}
        >
            <FormSection icon={Building2} title="Información Básica" description="Datos principales del restaurante">
                <FormField label="Nombre" error={errors.name} required>
                    <Input
                        id="name"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        placeholder={PLACEHOLDERS.restaurantName}
                        autoComplete={AUTOCOMPLETE.organizationName}
                    />
                </FormField>

                <FormField label="Descripción" error={errors.description}>
                    <Textarea
                        id="description"
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                        placeholder={PLACEHOLDERS.description}
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
                            placeholder={PLACEHOLDERS.address}
                            className="pl-10"
                            autoComplete={AUTOCOMPLETE.address}
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

                <FormField label="Nombre del Encargado" error={errors.manager_name}>
                    <div className="relative">
                        <User className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                        <Input
                            id="manager_name"
                            value={data.manager_name}
                            onChange={(e) => setData('manager_name', e.target.value)}
                            placeholder={PLACEHOLDERS.managerName}
                            className="pl-10"
                            autoComplete={AUTOCOMPLETE.name}
                        />
                    </div>
                </FormField>
            </FormSection>

            <FormSection icon={Settings} title="Configuración de Servicios" description="Servicios y configuración operativa">
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

                <div className="border-t pt-4">
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Rating Actual</Label>
                            <div className="text-2xl font-bold">
                                {restaurant.rating ? Number(restaurant.rating).toFixed(1) : '0.0'}
                            </div>
                        </div>
                        <div className="space-y-2">
                            <Label>Total de Reseñas</Label>
                            <div className="text-2xl font-bold">{restaurant.total_reviews || 0}</div>
                        </div>
                    </div>
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

        </EditPageLayout>
    );
}
