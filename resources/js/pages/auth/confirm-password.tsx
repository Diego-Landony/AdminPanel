// Components
import { Head, useForm } from '@inertiajs/react';
import { Eye, EyeOff, Loader2, ShieldCheck } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import { Button } from '@/components/ui/button';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import AuthLayout from '@/layouts/auth-layout';
import { PLACEHOLDERS } from '@/constants/ui-constants';

/**
 * Página de confirmación de contraseña
 * Área segura que requiere confirmación de contraseña antes de continuar
 */
export default function ConfirmPassword() {
    const [showPassword, setShowPassword] = useState(false);

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
                    <FormField label="Contraseña" error={errors.password} required>
                        <div className="relative">
                            <Input
                                id="password"
                                type={showPassword ? 'text' : 'password'}
                                name="password"
                                placeholder={PLACEHOLDERS.password}
                                autoComplete="current-password"
                                value={data.password}
                                autoFocus
                                onChange={(e) => setData('password', e.target.value)}
                                className="pr-10 pl-10"
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

                    {/* Botón de confirmación */}
                    <div className="flex items-center">
                        <Button className="w-full" disabled={processing}>
                            {processing ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <ShieldCheck className="mr-2 h-4 w-4" />}
                            {processing ? 'Confirmando...' : 'Confirmar contraseña'}
                        </Button>
                    </div>
                </div>
            </form>
        </AuthLayout>
    );
}
