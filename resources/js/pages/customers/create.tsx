import React, { useState } from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import { ArrowLeft, Save, User, Mail, Lock, Eye, EyeOff, CreditCard, Phone, MapPin, Calendar, Hash } from 'lucide-react';
import { showNotification } from '@/hooks/useNotifications';

import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { FormField } from '@/components/ui/form-field';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

/**
 * Página para crear un nuevo cliente
 */
export default function CreateCustomer() {
    const [showPassword, setShowPassword] = useState(false);
    
    const { data, setData, post, processing, errors, reset } = useForm({
        full_name: '',
        email: '',
        password: '',
        password_confirmation: '',
        subway_card: '',
        birth_date: '',
        gender: '',
        client_type: 'regular',
        phone: '',
        address: '',
        location: '',
        nit: '',
    });

    /**
     * Maneja el envío del formulario
     */
    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        
        post(route('customers.store'), {
            onSuccess: () => {
                reset();
                showNotification.success('Cliente creado exitosamente');
            },
            onError: (errors) => {
                const firstError = Object.values(errors)[0];
                if (typeof firstError === 'string') {
                    showNotification.error(firstError);
                }
            },
        });
    };

    return (
        <AppLayout
        >
            <Head title="Crear Cliente" />

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
                                <h1 className="text-2xl font-bold">Crear Nuevo Cliente</h1>
                                <p className="text-muted-foreground">
                                    Completa la información para registrar un nuevo cliente
                                </p>
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

                        {/* Information de Contacto */}
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
                                    Seguridad
                                </h2>
                                <p className="text-sm text-muted-foreground">
                                    Configuración de acceso del cliente
                                </p>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <FormField
                                    label="Contraseña"
                                    error={errors.password}
                                    required
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
                                    label="Confirmar Contraseña"
                                    error={errors.password_confirmation}
                                    required
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
                        <div className="flex justify-end gap-4 pt-6 border-t">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => reset()}
                                disabled={processing}
                            >
                                Limpiar
                            </Button>
                            <Button
                                type="submit"
                                disabled={processing}
                                className="min-w-[120px]"
                            >
                                {processing ? (
                                    <div className="flex items-center gap-2">
                                        <div className="h-4 w-4 animate-spin rounded-full border-2 border-background border-t-transparent" />
                                        Creando...
                                    </div>
                                ) : (
                                    <div className="flex items-center gap-2">
                                        <Save className="h-4 w-4" />
                                        Crear Cliente
                                    </div>
                                )}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}