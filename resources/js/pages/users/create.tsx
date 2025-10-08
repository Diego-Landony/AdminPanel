import { showNotification } from '@/hooks/useNotifications';
import { useForm } from '@inertiajs/react';
import { Eye, EyeOff, Lock, Mail, User } from 'lucide-react';
import React, { useState } from 'react';

import { CreatePageLayout } from '@/components/create-page-layout';
import { FormSection } from '@/components/form-section';
import { CreateUsersSkeleton } from '@/components/skeletons';
import { Button } from '@/components/ui/button';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { PLACEHOLDERS, AUTOCOMPLETE, FIELD_DESCRIPTIONS, NOTIFICATIONS } from '@/constants/ui-constants';

/**
 * Página para crear un nuevo usuario
 */
export default function CreateUser() {
    const [showPassword, setShowPassword] = useState(false);

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
                    showNotification.error(NOTIFICATIONS.error.serverUserCreate);
                }
            },
        });
    };

    return (
        <CreatePageLayout
            title="Nuevo Usuario"
            backHref={route('users.index')}
            onSubmit={handleSubmit}
            processing={processing}
            pageTitle="Crear Usuario"
            loading={processing}
            loadingSkeleton={CreateUsersSkeleton}
        >
            <FormSection icon={User} title="Información del Usuario">
                <FormField label="Nombre Completo" error={errors.name} required>
                    <Input
                        id="name"
                        type="text"
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

                <FormField label="Contraseña" error={errors.password} description={FIELD_DESCRIPTIONS.password} required>
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

                <FormField label="Confirmar Contraseña" error={errors.password_confirmation} required>
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
            </FormSection>
        </CreatePageLayout>
    );
}
