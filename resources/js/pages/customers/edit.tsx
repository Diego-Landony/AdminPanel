import React, { useState } from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import { ArrowLeft, Save, User, Mail, Lock, Eye, EyeOff, CreditCard, Phone, MapPin, Calendar, Hash, Check, X } from 'lucide-react';
import { toast } from 'sonner';

import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { FormField } from '@/components/ui/form-field';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import { BreadcrumbItem } from '@/types';

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
    client_type: string | null;
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
        timeZone: 'America/Guatemala'
    });
};

/**
 * Página para editar un cliente existente
 */
export default function EditCustomer({ customer }: EditCustomerProps) {
    const [showPassword, setShowPassword] = useState(false);
    
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Clientes',
            href: '/customers',
        },
        {
            title: customer.full_name,
            href: `/customers/${customer.id}/edit`,
        },
    ];
    
    const { data, setData, put, processing, errors, reset, isDirty } = useForm({
        full_name: customer.full_name || '',
        email: customer.email || '',
        password: '',
        password_confirmation: '',
        subway_card: customer.subway_card || '',
        birth_date: customer.birth_date || '',
        gender: customer.gender || '',
        client_type: customer.client_type || 'regular',
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
                toast.success('Cliente actualizado exitosamente');
                // Limpiar los campos de contraseña
                setData('password', '');
                setData('password_confirmation', '');
            },
            onError: (errors) => {
                const firstError = Object.values(errors)[0];
                if (typeof firstError === 'string') {
                    toast.error(firstError);
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
            client_type: customer.client_type || 'regular',
            phone: customer.phone || '',
            address: customer.address || '',
            location: customer.location || '',
            nit: customer.nit || '',
        });
    };

    return (
        <AppLayout
            breadcrumbs={breadcrumbs}
        >
            <Head title={`Editar Cliente - ${customer.full_name}`} />

            <div className="max-w-4xl mx-auto">
                <div className="bg-background rounded-lg border shadow-sm">
                    {/* Header */}
                    <div className="flex items-center justify-between p-6 border-b">
                        <div className="flex items-center gap-4">
                            <Link
                                href="/customers"
                                className="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-3"
                            >
                                <ArrowLeft className="h-4 w-4 mr-2" />
                                Volver
                            </Link>
                            <div>
                                <h1 className="text-2xl font-bold">Editar Cliente</h1>
                                <p className="text-muted-foreground">
                                    Modifica la información del cliente
                                </p>
                            </div>
                        </div>
                        <div className="flex items-center gap-2">
                            {customer.email_verified_at ? (
                                <Badge variant="outline" className="bg-green-50 text-green-700 border-green-200">
                                    <Check className="h-3 w-3 mr-1" />
                                    Email Verificado
                                </Badge>
                            ) : (
                                <Badge variant="outline" className="bg-red-50 text-red-700 border-red-200">
                                    <X className="h-3 w-3 mr-1" />
                                    Email No Verificado
                                </Badge>
                            )}
                        </div>
                    </div>

                    {/* Información del cliente */}
                    <div className="p-6 bg-muted/30 border-b">
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                            <div>
                                <span className="text-muted-foreground">ID:</span>
                                <span className="ml-2 font-mono">#{customer.id}</span>
                            </div>
                            <div>
                                <span className="text-muted-foreground">Creado:</span>
                                <span className="ml-2">{formatDate(customer.created_at)}</span>
                            </div>
                            <div>
                                <span className="text-muted-foreground">Última actividad:</span>
                                <span className="ml-2">{formatDate(customer.last_activity_at)}</span>
                            </div>
                        </div>
                    </div>

                    {/* Formulario */}
                    <form onSubmit={handleSubmit} className="p-6 space-y-8">
                        {/* Información Personal */}
                        <div className="space-y-6">
                            <div className="border-b pb-4">
                                <h2 className="text-lg font-semibold flex items-center gap-2">
                                    <User className="h-5 w-5" />
                                    Información Personal
                                </h2>
                                <p className="text-sm text-muted-foreground">
                                    Datos básicos del cliente
                                </p>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <FormField
                                    label="Nombre Completo"
                                    error={errors.full_name}
                                    required
                                >
                                    <Input
                                        type="text"
                                        value={data.full_name}
                                        onChange={(e) => setData('full_name', e.target.value)}
                                        placeholder="Ingrese el nombre completo"
                                        autoComplete="name"
                                        className={errors.full_name ? 'border-red-500' : ''}
                                    />
                                </FormField>

                                <FormField
                                    label="Email"
                                    error={errors.email}
                                    required
                                    description={customer.email !== data.email ? "Cambiar el email requerirá nueva verificación" : ""}
                                >
                                    <div className="relative">
                                        <Mail className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                        <Input
                                            type="email"
                                            value={data.email}
                                            onChange={(e) => setData('email', e.target.value)}
                                            placeholder="cliente@ejemplo.com"
                                            autoComplete="email"
                                            className={`pl-10 ${errors.email ? 'border-red-500' : ''}`}
                                        />
                                    </div>
                                </FormField>

                                <FormField
                                    label="Tarjeta Subway"
                                    error={errors.subway_card}
                                    required
                                    description="Número único de identificación del cliente"
                                >
                                    <div className="relative">
                                        <CreditCard className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                        <Input
                                            type="text"
                                            value={data.subway_card}
                                            onChange={(e) => setData('subway_card', e.target.value)}
                                            placeholder="1234567890"
                                            className={`pl-10 ${errors.subway_card ? 'border-red-500' : ''}`}
                                        />
                                    </div>
                                </FormField>

                                <FormField
                                    label="Fecha de Nacimiento"
                                    error={errors.birth_date}
                                    required
                                >
                                    <div className="relative">
                                        <Calendar className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                        <Input
                                            type="date"
                                            value={data.birth_date}
                                            onChange={(e) => setData('birth_date', e.target.value)}
                                            className={`pl-10 ${errors.birth_date ? 'border-red-500' : ''}`}
                                            max={new Date().toISOString().split('T')[0]}
                                        />
                                    </div>
                                </FormField>

                                <FormField
                                    label="Género"
                                    error={errors.gender}
                                >
                                    <Select value={data.gender} onValueChange={(value) => setData('gender', value)}>
                                        <SelectTrigger className={errors.gender ? 'border-red-500' : ''}>
                                            <SelectValue placeholder="Selecciona el género" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="masculino">Masculino</SelectItem>
                                            <SelectItem value="femenino">Femenino</SelectItem>
                                            <SelectItem value="otro">Otro</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </FormField>

                                <FormField
                                    label="Tipo de Cliente"
                                    error={errors.client_type}
                                >
                                    <Select value={data.client_type} onValueChange={(value) => setData('client_type', value)}>
                                        <SelectTrigger className={errors.client_type ? 'border-red-500' : ''}>
                                            <SelectValue placeholder="Selecciona el tipo de cliente" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="regular">Regular</SelectItem>
                                            <SelectItem value="premium">Premium</SelectItem>
                                            <SelectItem value="vip">VIP</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </FormField>
                            </div>
                        </div>

                        {/* Información de Contacto */}
                        <div className="space-y-6">
                            <div className="border-b pb-4">
                                <h2 className="text-lg font-semibold flex items-center gap-2">
                                    <Phone className="h-5 w-5" />
                                    Información de Contacto
                                </h2>
                                <p className="text-sm text-muted-foreground">
                                    Datos de contacto y ubicación del cliente
                                </p>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <FormField
                                    label="Teléfono"
                                    error={errors.phone}
                                >
                                    <div className="relative">
                                        <Phone className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                        <Input
                                            type="tel"
                                            value={data.phone}
                                            onChange={(e) => setData('phone', e.target.value)}
                                            placeholder="+502 1234-5678"
                                            className={`pl-10 ${errors.phone ? 'border-red-500' : ''}`}
                                        />
                                    </div>
                                </FormField>

                                <FormField
                                    label="Ubicación"
                                    error={errors.location}
                                >
                                    <div className="relative">
                                        <MapPin className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                        <Input
                                            type="text"
                                            value={data.location}
                                            onChange={(e) => setData('location', e.target.value)}
                                            placeholder="Ciudad, Departamento"
                                            className={`pl-10 ${errors.location ? 'border-red-500' : ''}`}
                                        />
                                    </div>
                                </FormField>

                                <FormField
                                    label="NIT"
                                    error={errors.nit}
                                    description="Número de Identificación Tributaria (opcional)"
                                >
                                    <div className="relative">
                                        <Hash className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                        <Input
                                            type="text"
                                            value={data.nit}
                                            onChange={(e) => setData('nit', e.target.value)}
                                            placeholder="12345678-9"
                                            className={`pl-10 ${errors.nit ? 'border-red-500' : ''}`}
                                        />
                                    </div>
                                </FormField>
                            </div>

                            <FormField
                                label="Dirección"
                                error={errors.address}
                            >
                                <Textarea
                                    value={data.address}
                                    onChange={(e) => setData('address', e.target.value)}
                                    placeholder="Dirección completa del cliente"
                                    className={errors.address ? 'border-red-500' : ''}
                                    rows={3}
                                />
                            </FormField>
                        </div>

                        {/* Seguridad */}
                        <div className="space-y-6">
                            <div className="border-b pb-4">
                                <h2 className="text-lg font-semibold flex items-center gap-2">
                                    <Lock className="h-5 w-5" />
                                    Cambiar Contraseña
                                </h2>
                                <p className="text-sm text-muted-foreground">
                                    Deja en blanco si no deseas cambiar la contraseña
                                </p>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <FormField
                                    label="Nueva Contraseña"
                                    error={errors.password}
                                    description="Mínimo 8 caracteres"
                                >
                                    <div className="relative">
                                        <Lock className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                        <Input
                                            type={showPassword ? 'text' : 'password'}
                                            value={data.password}
                                            onChange={(e) => setData('password', e.target.value)}
                                            placeholder="••••••••"
                                            autoComplete="new-password"
                                            className={`pl-10 pr-10 ${errors.password ? 'border-red-500' : ''}`}
                                        />
                                        <button
                                            type="button"
                                            onClick={() => setShowPassword(!showPassword)}
                                            className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                                        >
                                            {showPassword ? (
                                                <EyeOff className="h-4 w-4" />
                                            ) : (
                                                <Eye className="h-4 w-4" />
                                            )}
                                        </button>
                                    </div>
                                </FormField>

                                <FormField
                                    label="Confirmar Nueva Contraseña"
                                    error={errors.password_confirmation}
                                >
                                    <div className="relative">
                                        <Lock className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                        <Input
                                            type={showPassword ? 'text' : 'password'}
                                            value={data.password_confirmation}
                                            onChange={(e) => setData('password_confirmation', e.target.value)}
                                            placeholder="••••••••"
                                            autoComplete="new-password"
                                            className={`pl-10 ${errors.password_confirmation ? 'border-red-500' : ''}`}
                                        />
                                    </div>
                                </FormField>
                            </div>
                        </div>

                        {/* Botones de acción */}
                        <div className="flex justify-between items-center pt-6 border-t">
                            <div className="text-sm text-muted-foreground">
                                {isDirty ? 'Tienes cambios sin guardar' : 'Sin cambios'}
                            </div>
                            <div className="flex gap-4">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={handleReset}
                                    disabled={processing || !isDirty}
                                >
                                    Descartar Cambios
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={processing || !isDirty}
                                    className="min-w-[120px]"
                                >
                                    {processing ? (
                                        <div className="flex items-center gap-2">
                                            <div className="h-4 w-4 animate-spin rounded-full border-2 border-background border-t-transparent" />
                                            Guardando...
                                        </div>
                                    ) : (
                                        <div className="flex items-center gap-2">
                                            <Save className="h-4 w-4" />
                                            Guardar Cambios
                                        </div>
                                    )}
                                </Button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}