import { showNotification } from '@/hooks/useNotifications';
import { router, useForm } from '@inertiajs/react';
import { Building2, Calendar, CreditCard, Edit2, Eye, EyeOff, FileText, Lock, Mail, Phone, Plus, Star, Trash2, User } from 'lucide-react';
import React, { useState } from 'react';

import { AddressFormModal } from '@/components/customers/AddressFormModal';
import { NitFormModal } from '@/components/customers/NitFormModal';
import { DeleteConfirmationDialog } from '@/components/DeleteConfirmationDialog';
import { EditPageLayout } from '@/components/edit-page-layout';
import { FormSection } from '@/components/form-section';
import { EditCustomersSkeleton } from '@/components/skeletons';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { AUTOCOMPLETE, FIELD_DESCRIPTIONS, NOTIFICATIONS, PLACEHOLDERS } from '@/constants/ui-constants';

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
interface CustomerAddress {
    id: number;
    label: string | null;
    address_line: string;
    latitude: number | null;
    longitude: number | null;
    delivery_notes: string | null;
    is_default: boolean;
}

interface CustomerNit {
    id: number;
    nit: string;
    nit_type: 'personal' | 'company' | 'other';
    nit_name: string | null;
    is_default: boolean;
}

interface Customer {
    id: number;
    first_name: string;
    last_name: string;
    full_name: string;
    email: string;
    subway_card: string;
    birth_date: string | null;
    gender: string | null;
    customer_type_id: number | null;
    customer_type: { id: number; name: string } | null;
    phone: string | null;
    points: number;
    addresses: CustomerAddress[];
    nits: CustomerNit[];
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

    const [addressModalOpen, setAddressModalOpen] = useState(false);
    const [selectedAddress, setSelectedAddress] = useState<CustomerAddress | null>(null);
    const [deleteAddressDialogOpen, setDeleteAddressDialogOpen] = useState(false);
    const [addressToDelete, setAddressToDelete] = useState<CustomerAddress | null>(null);
    const [isDeletingAddress, setIsDeletingAddress] = useState(false);

    const [nitModalOpen, setNitModalOpen] = useState(false);
    const [selectedNit, setSelectedNit] = useState<CustomerNit | null>(null);
    const [deleteNitDialogOpen, setDeleteNitDialogOpen] = useState(false);
    const [nitToDelete, setNitToDelete] = useState<CustomerNit | null>(null);
    const [isDeletingNit, setIsDeletingNit] = useState(false);

    const { data, setData, put, processing, errors, reset, isDirty } = useForm({
        first_name: customer.first_name || '',
        last_name: customer.last_name || '',
        email: customer.email || '',
        password: '',
        password_confirmation: '',
        subway_card: customer.subway_card || '',
        birth_date: customer.birth_date || '',
        gender: customer.gender || '',
        customer_type_id: customer.customer_type_id,
        phone: customer.phone || '',
        points: customer.points ?? 0,
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
            first_name: customer.first_name || '',
            last_name: customer.last_name || '',
            email: customer.email || '',
            password: '',
            password_confirmation: '',
            subway_card: customer.subway_card || '',
            birth_date: customer.birth_date || '',
            gender: customer.gender || '',
            customer_type_id: customer.customer_type_id,
            phone: customer.phone || '',
            points: customer.points ?? 0,
        });
    };

    const handleAddAddress = () => {
        setSelectedAddress(null);
        setAddressModalOpen(true);
    };

    const handleEditAddress = (address: CustomerAddress) => {
        setSelectedAddress(address);
        setAddressModalOpen(true);
    };

    const handleDeleteAddressClick = (address: CustomerAddress) => {
        setAddressToDelete(address);
        setDeleteAddressDialogOpen(true);
    };

    const handleDeleteAddressConfirm = () => {
        if (!addressToDelete) return;

        setIsDeletingAddress(true);
        router.delete(route('customers.addresses.destroy', { customer: customer.id, address: addressToDelete.id }), {
            onSuccess: () => {
                showNotification.success('Dirección eliminada exitosamente');
                setDeleteAddressDialogOpen(false);
                setAddressToDelete(null);
            },
            onError: (errors) => {
                const firstError = Object.values(errors)[0];
                if (typeof firstError === 'string') {
                    showNotification.error(firstError);
                }
            },
            onFinish: () => setIsDeletingAddress(false),
        });
    };

    const handleAddNit = () => {
        setSelectedNit(null);
        setNitModalOpen(true);
    };

    const handleEditNit = (nit: CustomerNit) => {
        setSelectedNit(nit);
        setNitModalOpen(true);
    };

    const handleDeleteNitClick = (nit: CustomerNit) => {
        setNitToDelete(nit);
        setDeleteNitDialogOpen(true);
    };

    const handleDeleteNitConfirm = () => {
        if (!nitToDelete) return;

        setIsDeletingNit(true);
        router.delete(route('customers.nits.destroy', { customer: customer.id, nit: nitToDelete.id }), {
            onSuccess: () => {
                showNotification.success('NIT eliminado exitosamente');
                setDeleteNitDialogOpen(false);
                setNitToDelete(null);
            },
            onError: (errors) => {
                const firstError = Object.values(errors)[0];
                if (typeof firstError === 'string') {
                    showNotification.error(firstError);
                }
            },
            onFinish: () => setIsDeletingNit(false),
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
            <div className="space-y-8">
                <Card>
                    <CardContent className="pt-6">
                        <FormSection
                            icon={User}
                            title="Información Personal"
                            description="Detalles básicos del perfil del cliente"
                        >
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <FormField label="Nombre" error={errors.first_name} required>
                                    <Input type="text" value={data.first_name} onChange={(e) => setData('first_name', e.target.value)} autoComplete="given-name" />
                                </FormField>

                                <FormField label="Apellido" error={errors.last_name} required>
                                    <Input type="text" value={data.last_name} onChange={(e) => setData('last_name', e.target.value)} autoComplete="family-name" />
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
                                            <SelectValue placeholder={PLACEHOLDERS.selectGender} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="male">Masculino</SelectItem>
                                            <SelectItem value="female">Femenino</SelectItem>
                                            <SelectItem value="other">Otro</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </FormField>

                                <FormField label="Tipo de Cliente" error={errors.customer_type_id}>
                                    <Select
                                        value={data.customer_type_id?.toString() || ''}
                                        onValueChange={(value) => setData('customer_type_id', value ? parseInt(value) : null)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder={PLACEHOLDERS.selectCustomerType} />
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

                                <FormField label="Puntos de Lealtad" error={errors.points} description="Puntos acumulados del cliente">
                                    <div className="relative">
                                        <Star className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                                        <Input
                                            type="number"
                                            min="0"
                                            value={data.points}
                                            onChange={(e) => setData('points', parseInt(e.target.value) || 0)}
                                            className="pl-10"
                                        />
                                    </div>
                                </FormField>
                            </div>
                        </FormSection>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        <FormSection icon={Phone} title="Información de Contacto" description="Teléfono, direcciones y datos fiscales">
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

                <div>
                    <div className="flex items-center justify-between mb-3">
                        <div>
                            <span className="text-sm font-medium">Direcciones Guardadas</span>
                            <p className="text-xs text-muted-foreground">Este cliente tiene {customer.addresses.length} dirección{customer.addresses.length !== 1 ? 'es' : ''} guardada{customer.addresses.length !== 1 ? 's' : ''}</p>
                        </div>
                        <Button type="button" variant="outline" size="sm" onClick={handleAddAddress}>
                            <Plus className="h-4 w-4 mr-1" />
                            Agregar
                        </Button>
                    </div>
                    {customer.addresses.length > 0 ? (
                        <div className="space-y-2">
                            {customer.addresses.map((address) => (
                                <div key={address.id} className="rounded-lg border bg-muted/50 p-3">
                                    <div className="flex items-start justify-between">
                                        <div className="flex-1">
                                            {address.label && (
                                                <span className="text-sm font-medium">{address.label}</span>
                                            )}
                                            <p className="text-sm text-muted-foreground">{address.address_line}</p>
                                            {address.delivery_notes && (
                                                <p className="text-xs text-muted-foreground mt-1">Notas: {address.delivery_notes}</p>
                                            )}
                                        </div>
                                        <div className="flex items-center gap-2 ml-2">
                                            {address.is_default && (
                                                <Badge variant="default" className="text-xs">Predeterminada</Badge>
                                            )}
                                            <Button type="button" variant="ghost" size="sm" onClick={() => handleEditAddress(address)} className="h-8 w-8 p-0">
                                                <Edit2 className="h-4 w-4" />
                                            </Button>
                                            <Button type="button" variant="ghost" size="sm" onClick={() => handleDeleteAddressClick(address)} className="h-8 w-8 p-0 text-destructive hover:text-destructive">
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="rounded-lg border border-dashed p-4 text-center">
                            <p className="text-sm text-muted-foreground mb-2">No hay direcciones guardadas</p>
                            <Button type="button" variant="outline" size="sm" onClick={handleAddAddress}>
                                <Plus className="h-4 w-4 mr-1" />
                                Agregar primera dirección
                            </Button>
                        </div>
                    )}
                </div>

                <div>
                    <div className="flex items-center justify-between mb-3">
                        <div>
                            <span className="text-sm font-medium">NITs Guardados</span>
                            <p className="text-xs text-muted-foreground">
                                Este cliente tiene {customer.nits.length} NIT{customer.nits.length !== 1 ? 's' : ''} guardado{customer.nits.length !== 1 ? 's' : ''}
                            </p>
                        </div>
                        <Button type="button" variant="outline" size="sm" onClick={handleAddNit}>
                            <Plus className="h-4 w-4 mr-1" />
                            Agregar
                        </Button>
                    </div>
                    {customer.nits.length > 0 ? (
                        <div className="space-y-2">
                            {customer.nits.map((nit) => (
                                <div key={nit.id} className="rounded-lg border bg-muted/50 p-3">
                                    <div className="flex items-start justify-between">
                                        <div className="flex-1">
                                            <div className="flex items-center gap-2 mb-1">
                                                {nit.nit_type === 'personal' && (
                                                    <User className="h-3 w-3 text-blue-600" />
                                                )}
                                                {nit.nit_type === 'company' && (
                                                    <Building2 className="h-3 w-3 text-purple-600" />
                                                )}
                                                {nit.nit_type === 'other' && (
                                                    <FileText className="h-3 w-3 text-gray-600" />
                                                )}
                                                <Badge
                                                    variant="outline"
                                                    className={
                                                        nit.nit_type === 'personal'
                                                            ? 'border-blue-200 bg-blue-50 text-blue-700'
                                                            : nit.nit_type === 'company'
                                                            ? 'border-purple-200 bg-purple-50 text-purple-700'
                                                            : 'border-gray-200 bg-gray-50 text-gray-700'
                                                    }
                                                >
                                                    {nit.nit_type === 'personal' && 'Personal'}
                                                    {nit.nit_type === 'company' && 'Empresa'}
                                                    {nit.nit_type === 'other' && 'Otro'}
                                                </Badge>
                                            </div>
                                            <p className="text-sm font-medium font-mono">{nit.nit}</p>
                                            {nit.nit_name && (
                                                <p className="text-xs text-muted-foreground mt-1">
                                                    <Building2 className="inline h-3 w-3 mr-1" />
                                                    {nit.nit_name}
                                                </p>
                                            )}
                                        </div>
                                        <div className="flex items-center gap-2 ml-2">
                                            {nit.is_default && (
                                                <Badge variant="default" className="text-xs">Predeterminado</Badge>
                                            )}
                                            <Button type="button" variant="ghost" size="sm" onClick={() => handleEditNit(nit)} className="h-8 w-8 p-0">
                                                <Edit2 className="h-4 w-4" />
                                            </Button>
                                            <Button type="button" variant="ghost" size="sm" onClick={() => handleDeleteNitClick(nit)} className="h-8 w-8 p-0 text-destructive hover:text-destructive">
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="rounded-lg border border-dashed p-4 text-center">
                            <p className="text-sm text-muted-foreground mb-2">No hay NITs guardados</p>
                            <Button type="button" variant="outline" size="sm" onClick={handleAddNit}>
                                <Plus className="h-4 w-4 mr-1" />
                                Agregar primer NIT
                            </Button>
                        </div>
                    )}
                </div>
                        </FormSection>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        <FormSection icon={Lock} title="Seguridad" description="Configuración de acceso y contraseña">
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
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        <FormSection icon={User} title="Información del Sistema" description="Datos del registro del cliente">
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
                    </CardContent>
                </Card>
            </div>

            <AddressFormModal isOpen={addressModalOpen} onClose={() => setAddressModalOpen(false)} customerId={customer.id} address={selectedAddress} />

            <NitFormModal isOpen={nitModalOpen} onClose={() => setNitModalOpen(false)} customerId={customer.id} nit={selectedNit} />

            <DeleteConfirmationDialog
                isOpen={deleteAddressDialogOpen}
                onClose={() => setDeleteAddressDialogOpen(false)}
                onConfirm={handleDeleteAddressConfirm}
                isDeleting={isDeletingAddress}
                entityName={addressToDelete?.address_line || ''}
                entityType="dirección"
            />

            <DeleteConfirmationDialog
                isOpen={deleteNitDialogOpen}
                onClose={() => setDeleteNitDialogOpen(false)}
                onConfirm={handleDeleteNitConfirm}
                isDeleting={isDeletingNit}
                entityName={nitToDelete?.nit || ''}
                entityType="NIT"
            />
        </EditPageLayout>
    );
}
