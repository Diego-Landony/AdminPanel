import { showNotification } from '@/hooks/useNotifications';
import { useForm } from '@inertiajs/react';
import { Calendar, CreditCard, Eye, EyeOff, Hash, Lock, Mail, MapPin, Phone, User } from 'lucide-react';
import React, { useState } from 'react';

import { EditPageLayout } from '@/components/edit-page-layout';
import { FormSection } from '@/components/form-section';
import { EditCustomersSkeleton } from '@/components/skeletons';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { PLACEHOLDERS, AUTOCOMPLETE, FIELD_DESCRIPTIONS, NOTIFICATIONS } from '@/constants/ui-constants';

/**
 * Interfaz para el tipo de cliente
 */
interface CustomerType {
    id: number;
    name: string;
    color: string | null;
    points_required: number;
    multiplier: number;
}

/**
 * Interfaz para los datos del cliente
 */
interface Customer {
    id: number;
    full_name: string;
    email: string;
    subway_card: string;
    birth_date: string | null;
    gender: string | null;
    customer_type_id: number | null;
    customer_type: { id: number; name: string } | null;
    phone: string | null;
    address: string | null;
    location: string | null;
    nit: string | null;
    email_verified_at: string | null;
    created_at: string | null;
    updated_at: string | null;
    last_activity_at: string | null;
}

/**
 * Props del componente
 */
interface EditCustomerProps {
    customer: Customer;
    customer_types: CustomerType[];
}

/**
 * Formatea la fecha de manera legible
 */
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

/**
 * Página para editar un cliente existente
 */
export default function EditCustomer({ customer, customer_types }: EditCustomerProps) {
    const [showPassword, setShowPassword] = useState(false);

    const { data, setData, put, processing, errors, reset, isDirty } = useForm({
        full_name: customer.full_name || '',
        email: customer.email || '',
        password: '',
        password_confirmation: '',
        subway_card: customer.subway_card || '',
        birth_date: customer.birth_date || '',
        gender: customer.gender || '',
        customer_type_id: customer.customer_type_id,
        phone: customer.phone || '',
        address: customer.address || '',
        location: customer.location || '',
        nit: customer.nit || '',
    });

    /**
     * Maneja el envío del formulario
     */
    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        put(route('customers.update', customer.id), {
            onSuccess: () => {
                showNotification.success(NOTIFICATIONS.success.customerUpdated);
                // Limpiar los campos de contraseña
                setData('password', '');
                setData('password_confirmation', '');
            },
            onError: (errors) => {
                const firstError = Object.values(errors)[0];
                if (typeof firstError === 'string') {
                    showNotification.error(firstError);
                }
            },
        });
    };

    /**
     * Resetea el formulario a los valores originales
     */
    const handleReset = () => {
        reset();
        setData({
            full_name: customer.full_name || '',
            email: customer.email || '',
            password: '',
            password_confirmation: '',
            subway_card: customer.subway_card || '',
            birth_date: customer.birth_date || '',
            gender: customer.gender || '',
            customer_type_id: customer.customer_type_id,
            phone: customer.phone || '',
            address: customer.address || '',
            location: customer.location || '',
            nit: customer.nit || '',
        });
    };

    return (
        <EditPageLayout
            title="Editar Cliente"
            description={`Modifica la información de ${customer.full_name}`}
            backHref={route('customers.index')}
            onSubmit={handleSubmit}
            processing={processing}
            pageTitle={`Editar Cliente - ${customer.full_name}`}
            loading={processing}
            loadingSkeleton={EditCustomersSkeleton}
            isDirty={isDirty}
            onReset={handleReset}
            showResetButton={true}
        >
            <FormSection icon={User} title="Información Personal">
                <FormField label="Nombre Completo" error={errors.full_name} required>
                    <Input
                        type="text"
                        value={data.full_name}
                        onChange={(e) => setData('full_name', e.target.value)}
                        placeholder={PLACEHOLDERS.name}
                        autoComplete={AUTOCOMPLETE.name}
                    />
                </FormField>

                <FormField label="Email" error={errors.email} required>
                    <div className="relative">
                        <Mail className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                        <Input
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            placeholder={PLACEHOLDERS.email}
                            autoComplete={AUTOCOMPLETE.email}
                            className="pl-10"
                        />
                    </div>
                </FormField>

                <FormField label="Tarjeta Subway" error={errors.subway_card} required description={FIELD_DESCRIPTIONS.subwayCard}>
                    <div className="relative">
                        <CreditCard className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                        <Input
                            type="text"
                            value={data.subway_card}
                            onChange={(e) => setData('subway_card', e.target.value)}
                            placeholder={PLACEHOLDERS.subwayCard}
                            className="pl-10"
                        />
                    </div>
                </FormField>

                <FormField label="Fecha de Nacimiento" error={errors.birth_date} required>
                    <div className="relative">
                        <Calendar className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                        <Input
                            type="date"
                            value={data.birth_date}
                            onChange={(e) => setData('birth_date', e.target.value)}
                            className="pl-10"
                            max={new Date().toISOString().split('T')[0]}
                        />
                    </div>
                </FormField>

                <FormField label="Género" error={errors.gender}>
                    <Select value={data.gender} onValueChange={(value) => setData('gender', value)}>
                        <SelectTrigger>
                            <SelectValue placeholder="Selecciona el género" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="masculino">Masculino</SelectItem>
                            <SelectItem value="femenino">Femenino</SelectItem>
                            <SelectItem value="otro">Otro</SelectItem>
                        </SelectContent>
                    </Select>
                </FormField>

                <FormField label="Tipo de Cliente" error={errors.customer_type_id}>
                    <Select
                        value={data.customer_type_id?.toString() || ''}
                        onValueChange={(value) => setData('customer_type_id', value ? parseInt(value) : null)}
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Selecciona el tipo de cliente (opcional)" />
                        </SelectTrigger>
                        <SelectContent>
                            {customer_types.map((type) => (
                                <SelectItem key={type.id} value={type.id.toString()}>
                                    {type.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </FormField>
            </FormSection>

            <FormSection icon={Phone} title="Información de Contacto">
                <FormField label="Teléfono" error={errors.phone}>
                    <div className="relative">
                        <Phone className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                        <Input
                            type="tel"
                            value={data.phone}
                            onChange={(e) => setData('phone', e.target.value)}
                            placeholder={PLACEHOLDERS.phone}
                            className="pl-10"
                        />
                    </div>
                </FormField>

                <FormField label="Ubicación" error={errors.location}>
                    <div className="relative">
                        <MapPin className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                        <Input
                            type="text"
                            value={data.location}
                            onChange={(e) => setData('location', e.target.value)}
                            placeholder={PLACEHOLDERS.location}
                            className="pl-10"
                        />
                    </div>
                </FormField>

                <FormField label="NIT" error={errors.nit} description={FIELD_DESCRIPTIONS.nit}>
                    <div className="relative">
                        <Hash className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                        <Input
                            type="text"
                            value={data.nit}
                            onChange={(e) => setData('nit', e.target.value)}
                            placeholder={PLACEHOLDERS.nit}
                            className="pl-10"
                        />
                    </div>
                </FormField>

                <FormField label="Dirección" error={errors.address}>
                    <Textarea
                        value={data.address}
                        onChange={(e) => setData('address', e.target.value)}
                        placeholder={PLACEHOLDERS.address}
                        rows={3}
                    />
                </FormField>
            </FormSection>

            <FormSection icon={Lock} title="Seguridad">
                <FormField label="Contraseña" error={errors.password} description={FIELD_DESCRIPTIONS.password}>
                    <div className="relative">
                        <Lock className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                        <Input
                            type={showPassword ? 'text' : 'password'}
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            placeholder={PLACEHOLDERS.password}
                            autoComplete={AUTOCOMPLETE.newPassword}
                            className="pr-10 pl-10"
                        />
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            className="absolute top-1 right-1 h-8 w-8 p-0"
                            onClick={() => setShowPassword(!showPassword)}
                        >
                            {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                        </Button>
                    </div>
                </FormField>

                <FormField label="Confirmar Contraseña" error={errors.password_confirmation}>
                    <div className="relative">
                        <Lock className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                        <Input
                            type={showPassword ? 'text' : 'password'}
                            value={data.password_confirmation}
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                            placeholder={PLACEHOLDERS.password}
                            autoComplete={AUTOCOMPLETE.newPassword}
                            className="pl-10"
                        />
                    </div>
                </FormField>
            </FormSection>

            <FormSection icon={User} title="Información del Sistema">
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <span className="text-xs text-muted-foreground">ID</span>
                        <p className="font-mono text-sm">#{customer.id}</p>
                    </div>
                    <div>
                        <span className="text-xs text-muted-foreground">Email Verificado</span>
                        <p className="text-sm">
                            {customer.email_verified_at ? (
                                <Badge variant="default" className="text-xs">
                                    Verificado
                                </Badge>
                            ) : (
                                <Badge variant="destructive" className="text-xs">
                                    No verificado
                                </Badge>
                            )}
                        </p>
                    </div>
                    <div>
                        <span className="text-xs text-muted-foreground">Creado</span>
                        <p className="text-sm">{formatDate(customer.created_at)}</p>
                    </div>
                    <div>
                        <span className="text-xs text-muted-foreground">Actualizado</span>
                        <p className="text-sm">{formatDate(customer.updated_at)}</p>
                    </div>
                    <div>
                        <span className="text-xs text-muted-foreground">Última Actividad</span>
                        <p className="text-sm">{formatDate(customer.last_activity_at)}</p>
                    </div>
                </div>
            </FormSection>

        </EditPageLayout>
    );
}
