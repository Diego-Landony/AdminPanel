import { Head, useForm } from '@inertiajs/react';
import { Eye, EyeOff, Key, Loader2 } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import { Button } from '@/components/ui/button';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
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
    const [showPassword, setShowPassword] = useState(false);
    const [showPasswordConfirmation, setShowPasswordConfirmation] = useState(false);

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
        <AuthLayout title="Restablecer contraseña" description="Ingresa tu nueva contraseña a continuación">
            <Head title="Restablecer Contraseña" />

            <form onSubmit={submit}>
                <div className="grid gap-6">
                    {/* Campo de email (solo lectura) */}
                    <FormField label="Correo electrónico" error={errors.email}>
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            autoComplete="email"
                            value={data.email}
                            className="pl-10"
                            readOnly
                            onChange={(e) => setData('email', e.target.value)}
                        />
                    </FormField>

                    {/* Campo de nueva contraseña */}
                    <FormField label="Nueva contraseña" error={errors.password} description="Mínimo 6 caracteres" required>
                        <div className="relative">
                            <Input
                                id="password"
                                type={showPassword ? 'text' : 'password'}
                                name="password"
                                autoComplete="new-password"
                                value={data.password}
                                className="pr-10 pl-10"
                                autoFocus
                                onChange={(e) => setData('password', e.target.value)}
                                placeholder="Mínimo 6 caracteres"
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

                    {/* Campo de confirmación de contraseña */}
                    <FormField label="Confirmar nueva contraseña" error={errors.password_confirmation} required>
                        <div className="relative">
                            <Input
                                id="password_confirmation"
                                type={showPasswordConfirmation ? 'text' : 'password'}
                                name="password_confirmation"
                                autoComplete="new-password"
                                value={data.password_confirmation}
                                className="pr-10 pl-10"
                                onChange={(e) => setData('password_confirmation', e.target.value)}
                                placeholder="Confirma tu nueva contraseña"
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

                    {/* Botón de envío */}
                    <Button type="submit" className="mt-4 w-full" disabled={processing}>
                        {processing ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <Key className="mr-2 h-4 w-4" />}
                        {processing ? 'Restableciendo...' : 'Restablecer contraseña'}
                    </Button>
                </div>
            </form>
        </AuthLayout>
    );
}
