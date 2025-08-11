// Components
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';

/**
 * Página de confirmación de contraseña
 * Área segura que requiere confirmación de contraseña antes de continuar
 */
export default function ConfirmPassword() {
    // Hook de Inertia para manejar el formulario
    const { data, setData, post, processing, errors, reset } = useForm<Required<{ password: string }>>({
        password: '',
    });

    /**
     * Maneja el envío del formulario de confirmación
     * @param e - Evento del formulario
     */
    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('password.confirm'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <AuthLayout
            title="Confirma tu contraseña"
            description="Esta es un área segura de la aplicación. Por favor confirma tu contraseña antes de continuar."
        >
            <Head title="Confirmar Contraseña" />

            <form onSubmit={submit}>
                <div className="space-y-6">
                    {/* Campo de contraseña */}
                    <div className="grid gap-2">
                        <Label htmlFor="password">
                            <i className="fas fa-lock mr-2 text-muted-foreground"></i>
                            Contraseña
                        </Label>
                        <Input
                            id="password"
                            type="password"
                            name="password"
                            placeholder="Tu contraseña actual"
                            autoComplete="current-password"
                            value={data.password}
                            autoFocus
                            onChange={(e) => setData('password', e.target.value)}
                            className="pl-10"
                        />

                        <InputError message={errors.password} />
                    </div>

                    {/* Botón de confirmación */}
                    <div className="flex items-center">
                        <Button className="w-full" disabled={processing}>
                            {processing ? (
                                <i className="fas fa-spinner fa-spin mr-2"></i>
                            ) : (
                                <i className="fas fa-shield-check mr-2"></i>
                            )}
                            {processing ? 'Confirmando...' : 'Confirmar contraseña'}
                        </Button>
                    </div>
                </div>
            </form>
        </AuthLayout>
    );
}
