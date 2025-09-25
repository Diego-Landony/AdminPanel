import React, { useState } from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import { ArrowLeft, Save, User, Mail, Lock, Eye, EyeOff } from 'lucide-react';
import { showNotification } from '@/hooks/useNotifications';


import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { FormField } from '@/components/ui/form-field';

/**
 * Interfaz para el usuario a editar
 */
interface UserData {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    last_activity_at: string | null;
}

/**
 * Props del componente
 */
interface EditUserPageProps {
    user: UserData;
}

/**
 * Página para editar un usuario existente
 */
export default function EditUser({ user }: EditUserPageProps) {
    const [showPassword, setShowPassword] = useState(false);
    const [changePassword, setChangePassword] = useState(false);

    // Siempre llamar hooks antes de cualquier early return
    const { data, setData, patch, processing, errors } = useForm({
        name: user?.name || '',
        email: user?.email || '',
        password: '',
        password_confirmation: '',
    });

    // Validación defensiva para asegurar que user tiene las propiedades necesarias
    if (!user || typeof user !== 'object' || !user.name || !user.email || !user.id) {
        console.error('Usuario inválido recibido:', user);
        return (
            <AppLayout>
                <Head title="Error - Editar Usuario" />
                <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
                    <div className="text-center">
                        <h1 className="text-2xl font-bold text-red-600">Error al cargar usuario</h1>
                        <p className="text-muted-foreground">Los datos del usuario no están disponibles.</p>
                        <Link href={route('users.index')} className="mt-4 inline-block">
                            <Button variant="outline">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Volver a Usuarios
                            </Button>
                        </Link>
                    </div>
                </div>
            </AppLayout>
        );
    }

    /**
     * Maneja el envío del formulario
     */
    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        
        // Si no se va a cambiar la contraseña, no enviar los campos de password
        const submitData: Record<string, string> = { ...data };
        if (!changePassword) {
            delete submitData.password;
            delete submitData.password_confirmation;
        }
        
        patch(route('users.update', user.id), {
            onSuccess: () => {
                if (changePassword) {
                    setChangePassword(false);
                    setData('password', '');
                    setData('password_confirmation', '');
                }
            },
            onError: (errors) => {
                // Los errores de validación se muestran automáticamente
                // Los errores del servidor se manejan por el layout
                if (Object.keys(errors).length === 0) {
                    showNotification.error('Error del servidor al actualizar el usuario. Inténtalo de nuevo.');
                }
            }
        });
    };

    /**
     * Formatea una fecha para mostrar
     */
    const formatDate = (dateString: string | null) => {
        if (!dateString) return 'Nunca';
        try {
            return new Date(dateString).toLocaleDateString('es-GT', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch {
            return 'Fecha inválida';
        }
    };

    return (
        <AppLayout>
            <Head title={`Editar Usuario - ${user.name}`} />
            
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
                {/* Encabezado */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Editar Usuario</h1>
                        <p className="text-muted-foreground">
                            Modifica la información y contraseña de {user.name}
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
                    <div className="grid gap-6 lg:grid-cols-2">
                        {/* Información del Usuario y Cambio de Contraseña */}
                        <div className="space-y-6">
                            {/* Información del Usuario */}
                            <div className="space-y-4">
                                <div className="space-y-2">
                                    <h2 className="text-lg font-semibold flex items-center gap-2">
                                        <User className="h-5 w-5" />
                                        Información del Usuario
                                    </h2>
                                    <p className="text-sm text-muted-foreground">
                                        Datos básicos del usuario
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
                                </div>
                            </div>

                            {/* Cambio de Contraseña */}
                            <div className="space-y-4 pt-6 border-t border-border">
                                <div className="space-y-2">
                                    <h2 className="text-lg font-semibold flex items-center gap-2">
                                        <Lock className="h-5 w-5" />
                                        Cambiar Contraseña
                                    </h2>
                                    <p className="text-sm text-muted-foreground">
                                        Opcional: Cambiar la contraseña del usuario
                                    </p>
                                </div>
                                <div className="space-y-4">
                                    <div className="flex items-center space-x-2">
                                        <Checkbox
                                            id="change-password"
                                            checked={changePassword}
                                            onCheckedChange={(checked) => setChangePassword(checked as boolean)}
                                        />
                                        <Label htmlFor="change-password">
                                            Cambiar contraseña
                                        </Label>
                                    </div>

                                    {changePassword && (
                                        <div className="space-y-4">
                                            {/* Nueva Contraseña */}
                                            <FormField
                                                label="Nueva Contraseña"
                                                error={errors.password}
                                                description="Mínimo 6 caracteres"
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

                                            {/* Confirmar Nueva Contraseña */}
                                            <FormField
                                                label="Confirmar Nueva Contraseña"
                                                error={errors.password_confirmation}
                                            >
                                                <div className="relative">
                                                    <Lock className="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                                                    <Input
                                                        id="password_confirmation"
                                                        type={showPassword ? 'text' : 'password'}
                                                        placeholder="Repite la nueva contraseña"
                                                        value={data.password_confirmation}
                                                        onChange={(e) => setData('password_confirmation', e.target.value)}
                                                        className="pl-10"
                                                    />
                                                </div>
                                            </FormField>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Sidebar - Información del Sistema */}
                        <div className="space-y-6">
                            {/* Información del Sistema */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-sm">Información del Sistema</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <div>
                                        <Label className="text-xs text-muted-foreground">ID</Label>
                                        <p className="text-sm font-mono">#{user.id || 'N/A'}</p>
                                    </div>
                                    <Separator />
                                    <div>
                                        <Label className="text-xs text-muted-foreground">Email Verificado</Label>
                                        <p className="text-sm">
                                            {user.email_verified_at ? (
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
                                    <Separator />
                                    <div>
                                        <Label className="text-xs text-muted-foreground">Creado</Label>
                                        <p className="text-sm">{formatDate(user.created_at || null)}</p>
                                    </div>
                                    <div>
                                        <Label className="text-xs text-muted-foreground">Actualizado</Label>
                                        <p className="text-sm">{formatDate(user.updated_at || null)}</p>
                                    </div>
                                    <div>
                                        <Label className="text-xs text-muted-foreground">Última Actividad</Label>
                                        <p className="text-sm">{formatDate(user.last_activity_at || null)}</p>
                                    </div>
                                </CardContent>
                            </Card>
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
                            {processing ? 'Guardando...' : 'Guardar Cambios'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
