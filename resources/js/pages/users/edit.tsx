import { showNotification } from '@/hooks/useNotifications';
import { useForm } from '@inertiajs/react';
import { Eye, EyeOff, Lock, Mail, Shield, User } from 'lucide-react';
import React, { useState } from 'react';

import { EditPageLayout } from '@/components/edit-page-layout';
import { FormSection } from '@/components/form-section';
import { EditUsersSkeleton } from '@/components/skeletons';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ScrollArea } from '@/components/ui/scroll-area';
import { PLACEHOLDERS, AUTOCOMPLETE, FIELD_DESCRIPTIONS, NOTIFICATIONS } from '@/constants/ui-constants';

/**
 * Interfaz para el rol
 */
interface Role {
    id: number;
    name: string;
    description: string | null;
}

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
    roles: number[];
}

/**
 * Props del componente
 */
interface EditUserPageProps {
    user: UserData;
    all_roles: Role[];
}

/**
 * Página para editar un usuario existente
 */
export default function EditUser({ user, all_roles }: EditUserPageProps) {
    const [showPassword, setShowPassword] = useState(false);
    const [changePassword, setChangePassword] = useState(false);
    const [isRoleModalOpen, setIsRoleModalOpen] = useState(false);
    const [selectedRoles, setSelectedRoles] = useState<number[]>(user?.roles || []);
    const [searchTerm, setSearchTerm] = useState('');

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
            <EditPageLayout
                title="Error al cargar usuario"
                description="Los datos del usuario no están disponibles"
                backHref={route('users.index')}
                backLabel="Volver"
                onSubmit={() => {}}
                processing={false}
                pageTitle="Error - Editar Usuario"
                loading={false}
                loadingSkeleton={EditUsersSkeleton}
            >
                <div className="text-center text-red-600">
                    Error al cargar los datos del usuario.
                </div>
            </EditPageLayout>
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
                    showNotification.error(NOTIFICATIONS.error.serverUser);
                }
            },
        });
    };

    /**
     * Maneja el cambio de roles del usuario (auto-save)
     */
    const handleRoleChange = async (roleId: number, checked: boolean) => {
        const newSelectedRoles = checked
            ? [...selectedRoles, roleId]
            : selectedRoles.filter(id => id !== roleId);

        setSelectedRoles(newSelectedRoles);

        // Auto-save con fetch
        try {
            const response = await fetch(`/users/${user.id}/roles`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ roles: newSelectedRoles }),
            });

            if (response.ok) {
                showNotification.success(checked ? 'Rol agregado exitosamente' : 'Rol removido exitosamente');
            } else {
                // Revertir el cambio si falla
                setSelectedRoles(selectedRoles);
                const errorData = await response.json();
                showNotification.error(errorData.error || 'Error al actualizar roles');
            }
        } catch (error) {
            // Revertir el cambio si falla
            setSelectedRoles(selectedRoles);
            console.error('Error saving roles:', error);
            showNotification.error('Error de conexión al actualizar roles');
        }
    };

    /**
     * Filtra roles basado en el término de búsqueda
     */
    const filteredRoles = all_roles.filter(
        (role) => role.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                  (role.description && role.description.toLowerCase().includes(searchTerm.toLowerCase()))
    );

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
                minute: '2-digit',
            });
        } catch {
            return 'Fecha inválida';
        }
    };

    return (
        <EditPageLayout
            title="Editar Usuario"
            description={`Modifica la información y contraseña de ${user.name}`}
            backHref={route('users.index')}
            onSubmit={handleSubmit}
            processing={processing}
            pageTitle={`Editar Usuario - ${user.name}`}
            loading={processing}
            loadingSkeleton={EditUsersSkeleton}
        >
            <FormSection icon={User} title="Información del Usuario">
                <FormField label="Nombre Completo" error={errors.name} required>
                    <Input
                        id="name"
                        type="text"
                        placeholder={PLACEHOLDERS.name}
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        autoComplete={AUTOCOMPLETE.name}
                    />
                </FormField>

                <FormField label="Correo Electrónico" error={errors.email} required>
                    <div className="relative">
                        <Mail className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                        <Input
                            id="email"
                            type="email"
                            placeholder={PLACEHOLDERS.email}
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            className="pl-10"
                            autoComplete={AUTOCOMPLETE.email}
                        />
                    </div>
                </FormField>
            </FormSection>

            {/* Modal para gestionar roles del usuario */}
            <div className="flex justify-start py-8">
                <Dialog open={isRoleModalOpen} onOpenChange={setIsRoleModalOpen}>
                    <DialogTrigger asChild>
                        <Button variant="outline">
                            <Shield className="mr-2 h-4 w-4" />
                            Gestionar Roles del Usuario
                        </Button>
                    </DialogTrigger>
                    <DialogContent className="max-w-md">
                        <DialogHeader>
                            <DialogTitle>Gestionar Roles del Usuario</DialogTitle>
                            <DialogDescription>
                                Selecciona los roles que tendrá {user.name}. Los cambios se guardan automáticamente.
                            </DialogDescription>
                        </DialogHeader>

                        <div className="space-y-4">
                            {/* Buscador */}
                            <div className="relative">
                                <Input
                                    type="text"
                                    placeholder="Buscar roles..."
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                    className="h-9 text-sm"
                                />
                                <Shield className="absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            </div>

                            {/* Lista de roles con scroll */}
                            <ScrollArea className="h-[300px] rounded-lg border">
                                <div className="p-2">
                                    {filteredRoles.map((role) => (
                                        <div
                                            key={role.id}
                                            className={`flex items-center gap-3 rounded-md p-2 transition-colors ${
                                                selectedRoles.includes(role.id)
                                                    ? 'border border-primary/20 bg-primary/5'
                                                    : 'hover:bg-muted/50'
                                            }`}
                                        >
                                            <Checkbox
                                                id={`role-${role.id}`}
                                                checked={selectedRoles.includes(role.id)}
                                                onCheckedChange={(checked) => handleRoleChange(role.id, checked as boolean)}
                                                className="data-[state=checked]:border-primary data-[state=checked]:bg-primary"
                                            />
                                            <div className="min-w-0 flex-1">
                                                <Label
                                                    htmlFor={`role-${role.id}`}
                                                    className="block cursor-pointer text-sm font-medium"
                                                >
                                                    {role.name}
                                                </Label>
                                                <p className="truncate text-xs text-muted-foreground">
                                                    {role.description || 'Sin descripción'}
                                                </p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </ScrollArea>
                        </div>
                    </DialogContent>
                </Dialog>
            </div>

            <FormSection icon={Lock} title="Cambiar Contraseña" description="Opcional: Cambiar la contraseña del usuario" className="mt-4">
                <div className="space-y-4">
                    <div className="flex items-center space-x-2 py-2">
                        <Checkbox
                            id="change-password"
                            checked={changePassword}
                            onCheckedChange={(checked) => setChangePassword(checked as boolean)}
                        />
                        <Label htmlFor="change-password">Cambiar contraseña</Label>
                    </div>

                    {changePassword && (
                        <div className="space-y-4">
                            <FormField label="Nueva Contraseña" error={errors.password} description={FIELD_DESCRIPTIONS.password}>
                                <div className="relative">
                                    <Lock className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        id="password"
                                        type={showPassword ? 'text' : 'password'}
                                        placeholder={PLACEHOLDERS.password}
                                        value={data.password}
                                        onChange={(e) => setData('password', e.target.value)}
                                        className="pr-10 pl-10"
                                        autoComplete={AUTOCOMPLETE.newPassword}
                                    />
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        className="absolute top-1 right-1 h-11 w-11 md:h-8 md:w-8 p-0"
                                        onClick={() => setShowPassword(!showPassword)}
                                    >
                                        {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                    </Button>
                                </div>
                            </FormField>

                            <FormField label="Confirmar Nueva Contraseña" error={errors.password_confirmation}>
                                <div className="relative">
                                    <Lock className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        id="password_confirmation"
                                        type={showPassword ? 'text' : 'password'}
                                        placeholder={PLACEHOLDERS.password}
                                        value={data.password_confirmation}
                                        onChange={(e) => setData('password_confirmation', e.target.value)}
                                        className="pl-10"
                                        autoComplete={AUTOCOMPLETE.newPassword}
                                    />
                                </div>
                            </FormField>
                        </div>
                    )}
                </div>
            </FormSection>

            <FormSection icon={User} title="Información del Sistema" className="mt-6">
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <Label className="text-xs text-muted-foreground">ID</Label>
                        <p className="font-mono text-sm">#{user.id || 'N/A'}</p>
                    </div>
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
                </div>
            </FormSection>

        </EditPageLayout>
    );
}
