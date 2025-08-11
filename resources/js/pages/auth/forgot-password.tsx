// Components
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import AuthLayout from '@/layouts/auth-layout';

/**
 * Página de recuperación de contraseña
 */
export default function ForgotPassword({ status }: { status?: string }) {
    // Hook de formulario para recuperar contraseña
    const { data, setData, post, processing, errors } = useForm<Required<{ email: string }>>({
        email: '',
    });

    /**
     * Manejador del envío del formulario
     */
    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('password.email'));
    };

    return (
        <AuthLayout 
            title="Recuperar contraseña" 
            description="Recibe un enlace de recuperación"
        >
            <Head title="Recuperar Contraseña" />

            <Card className="w-full max-w-sm">
                <CardHeader className="text-center">
                    <CardTitle>Recuperar contraseña</CardTitle>
                </CardHeader>

                <CardContent>
                    {/* Mensaje de estado */}
                    {status && (
                        <div className="mb-4 text-center text-sm font-medium text-green-600">
                            <i className="fas fa-check-circle mr-2"></i>
                            {status}
                        </div>
                    )}

                    <form onSubmit={submit}>
                        {/* Campo de email */}
                        <div className="grid gap-2">
                            <Label htmlFor="email">
                                <i className="fas fa-envelope mr-2 text-muted-foreground"></i>
                                Correo electrónico
                            </Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                autoComplete="off"
                                value={data.email}
                                autoFocus
                                onChange={(e) => setData('email', e.target.value)}
                                placeholder="correo@ejemplo.com"
                            />
                            <InputError message={errors.email} />
                        </div>
                    </form>
                </CardContent>

                <CardFooter className="flex-col gap-4">
                    {/* Botón de envío */}
                    <Button className="w-full" onClick={submit} disabled={processing}>
                        {processing ? (
                            <i className="fas fa-spinner fa-spin mr-2"></i>
                        ) : (
                            <i className="fas fa-paper-plane mr-2"></i>
                        )}
                        {processing ? 'Enviando...' : 'Enviar enlace'}
                    </Button>

                    {/* Enlaces de navegación */}
                    <div className="text-center text-sm text-muted-foreground">
                        <TextLink href={route('login')} className="hover:text-foreground">
                            Volver al login
                        </TextLink>
                    </div>
                </CardFooter>
            </Card>
        </AuthLayout>
    );
}
