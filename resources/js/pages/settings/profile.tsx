import { type SharedData } from '@/types';
import { Transition } from '@headlessui/react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';

import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
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
 * Página de configuración de perfil
 * Permite al usuario actualizar su información personal
 */
export default function Profile({ mustVerifyEmail, status }: { mustVerifyEmail: boolean; status?: string }) {
    const { auth } = usePage<SharedData>().props;

    const { data, setData, patch, errors, processing, recentlySuccessful } = useForm<Required<ProfileForm>>({
        name: auth.user.name,
        email: auth.user.email,
    });

    /**
     * Envía el formulario de actualización de perfil
     */
    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        patch(route('profile.update'), {
            preserveScroll: true,
            // Los mensajes de éxito/error se manejan automáticamente por el layout
        });
    };

    return (
        <AppLayout>
            <Head title="Configuración de Perfil" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall title="Información del Perfil" description="Actualiza tu nombre y dirección de correo electrónico" />

                    <form onSubmit={submit} className="space-y-6">
                        <FormField label="Nombre" error={errors.name} required>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                autoComplete="name"
                                placeholder="Nombre completo"
                            />
                        </FormField>

                        <FormField label="Dirección de Correo" error={errors.email} required>
                            <Input
                                id="email"
                                type="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                autoComplete="username"
                                placeholder="Dirección de correo electrónico"
                            />
                        </FormField>

                        {mustVerifyEmail && auth.user.email_verified_at === null && (
                            <div>
                                <p className="-mt-4 text-sm text-muted-foreground">
                                    Tu dirección de correo electrónico no está verificada.{' '}
                                    <Link
                                        href={route('verification.send')}
                                        method="post"
                                        as="button"
                                        className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                    >
                                        Haz clic aquí para reenviar el correo de verificación.
                                    </Link>
                                </p>

                                {status === 'verification-link-sent' && (
                                    <div className="mt-2 text-sm font-medium text-green-600">
                                        Se ha enviado un nuevo enlace de verificación a tu dirección de correo electrónico.
                                    </div>
                                )}
                            </div>
                        )}

                        <div className="flex items-center gap-4">
                            <Button disabled={processing}>{processing ? 'Guardando...' : 'Guardar'}</Button>

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
