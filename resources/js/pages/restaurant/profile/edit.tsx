import { Transition } from '@headlessui/react';
import { useForm, usePage } from '@inertiajs/react';
import { Building2, Eye, EyeOff, KeyRound, User } from 'lucide-react';
import { FormEventHandler, useRef, useState } from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { AUTOCOMPLETE, FIELD_DESCRIPTIONS, PLACEHOLDERS } from '@/constants/ui-constants';
import RestaurantLayout from '@/layouts/restaurant-layout';
import { RestaurantSharedData } from '@/types/restaurant';

/**
 * Formulario de perfil del usuario
 */
type ProfileForm = {
    name: string;
    email: string;
};

/**
 * Formulario de contraseña
 */
type PasswordForm = {
    current_password: string;
    password: string;
    password_confirmation: string;
};

/**
 * Pagina de perfil del usuario del restaurante
 */
export default function RestaurantProfileEdit() {
    const { props } = usePage<RestaurantSharedData>();
    const { restaurantAuth } = props;

    // Form de perfil
    const {
        data: profileData,
        setData: setProfileData,
        patch: patchProfile,
        errors: profileErrors,
        processing: profileProcessing,
        recentlySuccessful: profileRecentlySuccessful,
    } = useForm<Required<ProfileForm>>({
        name: restaurantAuth?.user.name || '',
        email: restaurantAuth?.user.email || '',
    });

    // Form de contraseña
    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);
    const [showCurrentPassword, setShowCurrentPassword] = useState(false);
    const [showPassword, setShowPassword] = useState(false);
    const [showPasswordConfirmation, setShowPasswordConfirmation] = useState(false);

    const {
        data: passwordData,
        setData: setPasswordData,
        errors: passwordErrors,
        put: putPassword,
        reset: resetPassword,
        processing: passwordProcessing,
        recentlySuccessful: passwordRecentlySuccessful,
    } = useForm<PasswordForm>({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    /**
     * Envia el formulario de actualizacion de perfil
     */
    const submitProfile: FormEventHandler = (e) => {
        e.preventDefault();

        patchProfile('/restaurant/profile', {
            preserveScroll: true,
        });
    };

    /**
     * Actualiza la contraseña del usuario
     */
    const submitPassword: FormEventHandler = (e) => {
        e.preventDefault();

        putPassword('/restaurant/profile/password', {
            preserveScroll: true,
            onSuccess: () => resetPassword(),
            onError: (errors) => {
                if (errors.password) {
                    resetPassword('password', 'password_confirmation');
                    passwordInput.current?.focus();
                }

                if (errors.current_password) {
                    resetPassword('current_password');
                    currentPasswordInput.current?.focus();
                }
            },
        });
    };

    return (
        <RestaurantLayout title="Mi Perfil">
            <div className="flex flex-col gap-6">
                {/* Header */}
                <div className="flex items-center gap-3">
                    <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                        <User className="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight lg:text-3xl">Mi Perfil</h1>
                        <p className="text-sm text-muted-foreground">
                            Actualiza tu informacion personal y contraseña
                        </p>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    {/* Columna principal */}
                    <div className="space-y-6 lg:col-span-2">
                        {/* Seccion: Informacion del Perfil */}
                        <Card>
                            <CardHeader>
                                <div className="flex items-center gap-2">
                                    <User className="h-5 w-5 text-primary" />
                                    <CardTitle>Informacion Personal</CardTitle>
                                </div>
                                <CardDescription>Tu nombre y correo electronico.</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={submitProfile} className="space-y-4">
                                    <FormField label="Nombre" error={profileErrors.name} required>
                                        <Input
                                            id="name"
                                            value={profileData.name}
                                            onChange={(e) => setProfileData('name', e.target.value)}
                                            autoComplete={AUTOCOMPLETE.name}
                                        />
                                    </FormField>

                                    <FormField
                                        label="Correo Electronico"
                                        error={profileErrors.email}
                                        required
                                    >
                                        <Input
                                            id="email"
                                            type="email"
                                            value={profileData.email}
                                            onChange={(e) => setProfileData('email', e.target.value)}
                                            autoComplete={AUTOCOMPLETE.email}
                                            placeholder={PLACEHOLDERS.email}
                                        />
                                    </FormField>

                                    <div className="flex items-center gap-4 pt-2">
                                        <Button disabled={profileProcessing}>
                                            {profileProcessing ? 'Guardando...' : 'Guardar'}
                                        </Button>

                                        <Transition
                                            show={profileRecentlySuccessful}
                                            enter="transition ease-in-out"
                                            enterFrom="opacity-0"
                                            leave="transition ease-in-out"
                                            leaveTo="opacity-0"
                                        >
                                            <p className="text-sm text-neutral-600">Guardado</p>
                                        </Transition>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>

                        {/* Seccion: Cambiar Contraseña */}
                        <Card>
                            <CardHeader>
                                <div className="flex items-center gap-2">
                                    <KeyRound className="h-5 w-5 text-primary" />
                                    <CardTitle>Cambiar Contraseña</CardTitle>
                                </div>
                                <CardDescription>
                                    {FIELD_DESCRIPTIONS.passwordSecurity6}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={submitPassword} className="space-y-4">
                                    <FormField
                                        label="Contraseña Actual"
                                        error={passwordErrors.current_password}
                                        required
                                    >
                                        <div className="relative">
                                            <Input
                                                id="current_password"
                                                ref={currentPasswordInput}
                                                value={passwordData.current_password}
                                                onChange={(e) =>
                                                    setPasswordData('current_password', e.target.value)
                                                }
                                                type={showCurrentPassword ? 'text' : 'password'}
                                                autoComplete={AUTOCOMPLETE.currentPassword}
                                                placeholder={PLACEHOLDERS.password}
                                                className="pr-10"
                                            />
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                className="absolute top-1 right-1 h-8 w-8 p-0"
                                                onClick={() =>
                                                    setShowCurrentPassword(!showCurrentPassword)
                                                }
                                            >
                                                {showCurrentPassword ? (
                                                    <EyeOff className="h-4 w-4" />
                                                ) : (
                                                    <Eye className="h-4 w-4" />
                                                )}
                                            </Button>
                                        </div>
                                    </FormField>

                                    <FormField
                                        label="Nueva Contraseña"
                                        error={passwordErrors.password}
                                        description={FIELD_DESCRIPTIONS.passwordMinimum6}
                                        required
                                    >
                                        <div className="relative">
                                            <Input
                                                id="password"
                                                ref={passwordInput}
                                                value={passwordData.password}
                                                onChange={(e) =>
                                                    setPasswordData('password', e.target.value)
                                                }
                                                type={showPassword ? 'text' : 'password'}
                                                autoComplete={AUTOCOMPLETE.newPassword}
                                                placeholder={PLACEHOLDERS.password}
                                                className="pr-10"
                                            />
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                className="absolute top-1 right-1 h-8 w-8 p-0"
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

                                    <FormField
                                        label="Confirmar Contraseña"
                                        error={passwordErrors.password_confirmation}
                                        required
                                    >
                                        <div className="relative">
                                            <Input
                                                id="password_confirmation"
                                                value={passwordData.password_confirmation}
                                                onChange={(e) =>
                                                    setPasswordData(
                                                        'password_confirmation',
                                                        e.target.value,
                                                    )
                                                }
                                                type={showPasswordConfirmation ? 'text' : 'password'}
                                                autoComplete={AUTOCOMPLETE.newPassword}
                                                placeholder={PLACEHOLDERS.password}
                                                className="pr-10"
                                            />
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                className="absolute top-1 right-1 h-8 w-8 p-0"
                                                onClick={() =>
                                                    setShowPasswordConfirmation(!showPasswordConfirmation)
                                                }
                                            >
                                                {showPasswordConfirmation ? (
                                                    <EyeOff className="h-4 w-4" />
                                                ) : (
                                                    <Eye className="h-4 w-4" />
                                                )}
                                            </Button>
                                        </div>
                                    </FormField>

                                    <div className="flex items-center gap-4 pt-2">
                                        <Button disabled={passwordProcessing}>
                                            {passwordProcessing && (
                                                <div className="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent" />
                                            )}
                                            {passwordProcessing
                                                ? 'Guardando...'
                                                : 'Cambiar Contraseña'}
                                        </Button>

                                        <Transition
                                            show={passwordRecentlySuccessful}
                                            enter="transition ease-in-out"
                                            enterFrom="opacity-0"
                                            leave="transition ease-in-out"
                                            leaveTo="opacity-0"
                                        >
                                            <p className="text-sm text-neutral-600">
                                                Contraseña actualizada
                                            </p>
                                        </Transition>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Columna lateral */}
                    <div className="space-y-6">
                        {/* Informacion del Restaurante */}
                        <Card>
                            <CardHeader>
                                <div className="flex items-center gap-2">
                                    <Building2 className="h-5 w-5 text-primary" />
                                    <CardTitle>Restaurante</CardTitle>
                                </div>
                                <CardDescription>Informacion de tu restaurante</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <div>
                                    <span className="text-xs text-muted-foreground">Nombre</span>
                                    <p className="font-medium">
                                        {restaurantAuth?.restaurant.name || 'N/A'}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </RestaurantLayout>
    );
}
