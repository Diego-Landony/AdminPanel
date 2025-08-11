import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';

/**
 * Props de la página de restablecimiento de contraseña
 */
interface ResetPasswordProps {
    token: string;
    email: string;
}

/**
 * Tipo para el formulario de restablecimiento de contraseña
 */
type ResetPasswordForm = {
    token: string;
    email: string;
    password: string;
    password_confirmation: string;
};

/**
 * Página de restablecimiento de contraseña
 * Permite a los usuarios establecer una nueva contraseña usando un token de seguridad
 */
export default function ResetPassword({ token, email }: ResetPasswordProps) {
    // Hook de Inertia para manejar el formulario
    const { data, setData, post, processing, errors, reset } = useForm<Required<ResetPasswordForm>>({
        token: token,
        email: email,
        password: '',
        password_confirmation: '',
    });

    /**
     * Maneja el envío del formulario de restablecimiento
     * @param e - Evento del formulario
     */
    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('password.store'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <AuthLayout 
            title="Restablecer contraseña" 
            description="Ingresa tu nueva contraseña a continuación"
        >
            <Head title="Restablecer Contraseña" />

            <form onSubmit={submit}>
                <div className="grid gap-6">
                    {/* Campo de email (solo lectura) */}
                    <div className="grid gap-2">
                        <Label htmlFor="email">
                            <i className="fas fa-envelope mr-2 text-muted-foreground"></i>
                            Correo electrónico
                        </Label>
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            autoComplete="email"
                            value={data.email}
                            className="mt-1 block w-full pl-10"
                            readOnly
                            onChange={(e) => setData('email', e.target.value)}
                        />
                        <InputError message={errors.email} className="mt-2" />
                    </div>

                    {/* Campo de nueva contraseña */}
                    <div className="grid gap-2">
                        <Label htmlFor="password">
                            <i className="fas fa-lock mr-2 text-muted-foreground"></i>
                            Nueva contraseña
                        </Label>
                        <Input
                            id="password"
                            type="password"
                            name="password"
                            autoComplete="new-password"
                            value={data.password}
                            className="mt-1 block w-full pl-10"
                            autoFocus
                            onChange={(e) => setData('password', e.target.value)}
                            placeholder="Tu nueva contraseña"
                        />
                        <InputError message={errors.password} />
                    </div>

                    {/* Campo de confirmación de contraseña */}
                    <div className="grid gap-2">
                        <Label htmlFor="password_confirmation">
                            <i className="fas fa-shield-alt mr-2 text-muted-foreground"></i>
                            Confirmar nueva contraseña
                        </Label>
                        <Input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            autoComplete="new-password"
                            value={data.password_confirmation}
                            className="mt-1 block w-full pl-10"
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                            placeholder="Confirma tu nueva contraseña"
                        />
                        <InputError message={errors.password_confirmation} className="mt-2" />
                    </div>

                    {/* Botón de envío */}
                    <Button type="submit" className="mt-4 w-full" disabled={processing}>
                        {processing ? (
                            <i className="fas fa-spinner fa-spin mr-2"></i>
                        ) : (
                            <i className="fas fa-key mr-2"></i>
                        )}
                        {processing ? 'Restableciendo...' : 'Restablecer contraseña'}
                    </Button>
                </div>
            </form>
        </AuthLayout>
    );
}
