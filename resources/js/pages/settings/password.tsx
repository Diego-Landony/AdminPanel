import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { Transition } from '@headlessui/react';
import { Head, useForm } from '@inertiajs/react';
import { Eye, EyeOff } from 'lucide-react';
import { FormEventHandler, useRef, useState } from 'react';

import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { PLACEHOLDERS, AUTOCOMPLETE, FIELD_DESCRIPTIONS } from '@/constants/ui-constants';

/**
 * Página de configuración de contraseña
 * Permite al usuario actualizar su contraseña de forma segura
 */
export default function Password() {
    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);
    const [showCurrentPassword, setShowCurrentPassword] = useState(false);
    const [showPassword, setShowPassword] = useState(false);
    const [showPasswordConfirmation, setShowPasswordConfirmation] = useState(false);

    const { data, setData, errors, put, reset, processing, recentlySuccessful } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    /**
     * Actualiza la contraseña del usuario
     */
    const updatePassword: FormEventHandler = (e) => {
        e.preventDefault();

        put(route('password.update'), {
            preserveScroll: true,
            onSuccess: () => reset(),
            onError: (errors) => {
                if (errors.password) {
                    reset('password', 'password_confirmation');
                    passwordInput.current?.focus();
                }

                if (errors.current_password) {
                    reset('current_password');
                    currentPasswordInput.current?.focus();
                }
            },
        });
    };

    return (
        <AppLayout>
            <Head title="Configuración de Contraseña" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title="Actualizar Contraseña"
                        description={FIELD_DESCRIPTIONS.passwordSecurity6}
                    />

                    <form onSubmit={updatePassword} className="space-y-6">
                        <FormField label="Contraseña Actual" error={errors.current_password} required>
                            <div className="relative">
                                <Input
                                    id="current_password"
                                    ref={currentPasswordInput}
                                    value={data.current_password}
                                    onChange={(e) => setData('current_password', e.target.value)}
                                    type={showCurrentPassword ? 'text' : 'password'}
                                    autoComplete={AUTOCOMPLETE.currentPassword}
                                    placeholder={PLACEHOLDERS.settingsPassword}
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

                        <FormField label="Nueva Contraseña" error={errors.password} description={FIELD_DESCRIPTIONS.passwordMinimum6} required>
                            <div className="relative">
                                <Input
                                    id="password"
                                    ref={passwordInput}
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    type={showPassword ? 'text' : 'password'}
                                    autoComplete={AUTOCOMPLETE.newPassword}
                                    placeholder={PLACEHOLDERS.settingsPasswordMinimum}
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

                        <FormField label="Confirmar Contraseña" error={errors.password_confirmation} required>
                            <div className="relative">
                                <Input
                                    id="password_confirmation"
                                    value={data.password_confirmation}
                                    onChange={(e) => setData('password_confirmation', e.target.value)}
                                    type={showPasswordConfirmation ? 'text' : 'password'}
                                    autoComplete={AUTOCOMPLETE.newPassword}
                                    placeholder={PLACEHOLDERS.settingsPasswordConfirm}
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

                        <div className="flex items-center gap-4">
                            <Button disabled={processing}>{processing ? 'Guardando...' : 'Guardar Contraseña'}</Button>

                            <Transition
                                show={recentlySuccessful}
                                enter="transition ease-in-out"
                                enterFrom="opacity-0"
                                leave="transition ease-in-out"
                                leaveTo="opacity-0"
                            >
                                <p className="text-sm text-neutral-600">Guardado</p>
                            </Transition>
                        </div>
                    </form>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
