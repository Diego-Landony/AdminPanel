import React, { useState } from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import { ArrowLeft, Save, User, Mail, Lock, Eye, EyeOff } from 'lucide-react';
import { toast } from 'sonner';


import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { FormField } from '@/components/ui/form-field';
import { BreadcrumbItem } from '@/types';

/**
 * Breadcrumbs para la navegación
 */
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Usuarios',
        href: '/users',
    },
    {
        title: 'Crear Usuario',
        href: '/users/create',
    },
];



/**
 * Página para crear un nuevo usuario
 */
export default function CreateUser() {
    const [showPassword, setShowPassword] = useState(false);
    const [showPasswordConfirmation, setShowPasswordConfirmation] = useState(false);
    
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    /**
     * Maneja el envío del formulario
     */
    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        
        post(route('users.store'), {
            onSuccess: () => {
                reset();
            },
            onError: (errors) => {
                // Los errores de validación se muestran automáticamente
                // Los errores del servidor se manejan por el layout
                if (Object.keys(errors).length === 0) {
                    toast.error('Error del servidor al crear el usuario. Inténtalo de nuevo.');
                }
            }
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Crear Usuario" />
            
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
                {/* Encabezado */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Crear Usuario</h1>
                        <p className="text-muted-foreground">
                            Agrega un nuevo usuario al sistema con los datos básicos
                        </p>
                    </div>
                    <Link href={route('users.index')}>
                        <Button variant="outline">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver a Usuarios
                        </Button>
                    </Link>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="max-w-2xl mx-auto">
                        {/* Información del Usuario */}
                        <div className="space-y-4">
                            <div className="space-y-2">
                                <h2 className="text-lg font-semibold flex items-center gap-2">
                                    <User className="h-5 w-5" />
                                    Información del Usuario
                                </h2>
                                <p className="text-sm text-muted-foreground">
                                    Datos básicos del nuevo usuario
                                </p>
                            </div>
                            <div className="space-y-4">
                                {/* Nombre */}
                                <FormField
                                    label="Nombre Completo"
                                    error={errors.name}
                                    required
                                >
                                    <Input
                                        id="name"
                                        type="text"
                                        placeholder="Ej: Juan Pérez"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                    />
                                </FormField>

                                {/* Email */}
                                <FormField
                                    label="Correo Electrónico"
                                    error={errors.email}
                                    required
                                >
                                    <div className="relative">
                                        <Mail className="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                                        <Input
                                            id="email"
                                            type="email"
                                            placeholder="usuario@ejemplo.com"
                                            value={data.email}
                                            onChange={(e) => setData('email', e.target.value)}
                                            className="pl-10"
                                        />
                                    </div>
                                </FormField>

                                {/* Contraseña */}
                                <FormField
                                    label="Contraseña"
                                    error={errors.password}
                                    description="Mínimo 6 caracteres"
                                    required
                                >
                                    <div className="relative">
                                        <Lock className="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                                        <Input
                                            id="password"
                                            type={showPassword ? 'text' : 'password'}
                                            placeholder="Mínimo 6 caracteres"
                                            value={data.password}
                                            onChange={(e) => setData('password', e.target.value)}
                                            className="pl-10 pr-10"
                                        />
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            className="absolute right-1 top-1 h-8 w-8 p-0"
                                            onClick={() => setShowPassword(!showPassword)}
                                        >
                                            {showPassword ? (
                                                <EyeOff className="h-4 w-4" />
                                            ) : (
                                                <Eye className="h-4 w-4" />
                                            )}
                                        </Button>
                                    </div>
                                </FormField>

                                {/* Confirmar Contraseña */}
                                <FormField
                                    label="Confirmar Contraseña"
                                    error={errors.password_confirmation}
                                    required
                                >
                                    <div className="relative">
                                        <Lock className="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                                        <Input
                                            id="password_confirmation"
                                            type={showPasswordConfirmation ? 'text' : 'password'}
                                            placeholder="Repite la contraseña"
                                            value={data.password_confirmation}
                                            onChange={(e) => setData('password_confirmation', e.target.value)}
                                            className="pl-10 pr-10"
                                        />
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            className="absolute right-1 top-1 h-8 w-8 p-0"
                                            onClick={() => setShowPasswordConfirmation(!showPasswordConfirmation)}
                                        >
                                            {showPasswordConfirmation ? (
                                                <EyeOff className="h-4 w-4" />
                                            ) : (
                                                <Eye className="h-4 w-4" />
                                            )}
                                        </Button>
                                    </div>
                                </FormField>
                            </div>
                        </div>
                    </div>

                    {/* Botones de Acción */}
                    <div className="flex items-center justify-end space-x-4">
                        <Link href={route('users.index')}>
                            <Button variant="outline" type="button">
                                Cancelar
                            </Button>
                        </Link>
                        <Button type="submit" disabled={processing}>
                            <Save className="mr-2 h-4 w-4" />
                            {processing ? 'Creando...' : 'Crear Usuario'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
