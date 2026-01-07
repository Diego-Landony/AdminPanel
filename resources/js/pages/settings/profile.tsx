import { type SharedData } from '@/types';
import { Transition } from '@headlessui/react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { Eye, EyeOff, KeyRound, User } from 'lucide-react';
import { FormEventHandler, useRef, useState } from 'react';

import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { AUTOCOMPLETE, FIELD_DESCRIPTIONS, PLACEHOLDERS } from '@/constants/ui-constants';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';

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
 * Página de configuración de perfil
 * Permite al usuario actualizar su información personal y contraseña
 */
export default function Profile({ mustVerifyEmail, status }: { mustVerifyEmail: boolean; status?: string }) {
    const { auth } = usePage<SharedData>().props;

    // Form de perfil
    const {
        data: profileData,
        setData: setProfileData,
        patch: patchProfile,
        errors: profileErrors,
        processing: profileProcessing,
        recentlySuccessful: profileRecentlySuccessful,
    } = useForm<Required<ProfileForm>>({
        name: auth.user.name,
        email: auth.user.email,
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
     * Envía el formulario de actualización de perfil
     */
    const submitProfile: FormEventHandler = (e) => {
        e.preventDefault();

        patchProfile(route('profile.update'), {
            preserveScroll: true,
        });
    };

    /**
     * Actualiza la contraseña del usuario
     */
    const submitPassword: FormEventHandler = (e) => {
        e.preventDefault();

        putPassword(route('password.update'), {
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
        <AppLayout>
            <Head title="Configuración de Perfil" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall title="Perfil" description="Actualiza tu información personal y contraseña." />

                    {/* Sección: Información del Perfil */}
                    <Card>
                        <CardHeader>
                            <div className="flex items-center gap-2">
                                <User className="h-5 w-5 text-primary" />
                                <CardTitle>Información Personal</CardTitle>
                            </div>
                            <CardDescription>Tu nombre y correo electrónico.</CardDescription>
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

                                <FormField label="Correo Electrónico" error={profileErrors.email} required>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={profileData.email}
                                        onChange={(e) => setProfileData('email', e.target.value)}
                                        autoComplete={AUTOCOMPLETE.email}
                                        placeholder={PLACEHOLDERS.email}
                                    />
                                </FormField>

                                {mustVerifyEmail && auth.user.email_verified_at === null && (
                                    <div>
                                        <p className="text-sm text-muted-foreground">
                                            Tu correo no está verificado.{' '}
                                            <Link
                                                href={route('verification.send')}
                                                method="post"
                                                as="button"
                                                className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                            >
                                                Reenviar verificación.
                                            </Link>
                                        </p>

                                        {status === 'verification-link-sent' && (
                                            <div className="mt-2 text-sm font-medium text-green-600">
                                                Se ha enviado un nuevo enlace de verificación.
                                            </div>
                                        )}
                                    </div>
                                )}

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

                    {/* Sección: Cambiar Contraseña */}
                    <Card>
                        <CardHeader>
                            <div className="flex items-center gap-2">
                                <KeyRound className="h-5 w-5 text-primary" />
                                <CardTitle>Cambiar Contraseña</CardTitle>
                            </div>
                            <CardDescription>{FIELD_DESCRIPTIONS.passwordSecurity6}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submitPassword} className="space-y-4">
                                <FormField label="Contraseña Actual" error={passwordErrors.current_password} required>
                                    <div className="relative">
                                        <Input
                                            id="current_password"
                                            ref={currentPasswordInput}
                                            value={passwordData.current_password}
                                            onChange={(e) => setPasswordData('current_password', e.target.value)}
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
                                            onClick={() => setShowCurrentPassword(!showCurrentPassword)}
                                        >
                                            {showCurrentPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
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
                                            onChange={(e) => setPasswordData('password', e.target.value)}
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
                                            {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                        </Button>
                                    </div>
                                </FormField>

                                <FormField label="Confirmar Contraseña" error={passwordErrors.password_confirmation} required>
                                    <div className="relative">
                                        <Input
                                            id="password_confirmation"
                                            value={passwordData.password_confirmation}
                                            onChange={(e) => setPasswordData('password_confirmation', e.target.value)}
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
                                            onClick={() => setShowPasswordConfirmation(!showPasswordConfirmation)}
                                        >
                                            {showPasswordConfirmation ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                        </Button>
                                    </div>
                                </FormField>

                                <div className="flex items-center gap-4 pt-2">
                                    <Button disabled={passwordProcessing}>
                                        {passwordProcessing && (
                                            <div className="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent" />
                                        )}
                                        {passwordProcessing ? 'Guardando...' : 'Cambiar Contraseña'}
                                    </Button>

                                    <Transition
                                        show={passwordRecentlySuccessful}
                                        enter="transition ease-in-out"
                                        enterFrom="opacity-0"
                                        leave="transition ease-in-out"
                                        leaveTo="opacity-0"
                                    >
                                        <p className="text-sm text-neutral-600">Contraseña actualizada</p>
                                    </Transition>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
