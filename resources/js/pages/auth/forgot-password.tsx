// Components
import { Head, useForm } from '@inertiajs/react';
import { CheckCircle, Loader2, Send } from 'lucide-react';
import { FormEventHandler } from 'react';

import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import AuthLayout from '@/layouts/auth-layout';
import { PLACEHOLDERS } from '@/constants/ui-constants';

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
        post(route('password.email'), {
            // Los mensajes de éxito/error se manejan automáticamente por el layout
        });
    };

    return (
        <AuthLayout title="Recuperar contraseña" description="Recibe un enlace de recuperación">
            <Head title="Recuperar Contraseña" />

            <Card className="w-full max-w-sm">
                <CardHeader className="text-center">
                    <CardTitle>Recuperar contraseña</CardTitle>
                </CardHeader>

                <CardContent>
                    {/* Mensaje de estado */}
                    {status && (
                        <div className="mb-4 text-center text-sm font-medium text-green-600">
                            <CheckCircle className="mr-2 h-4 w-4" />
                            {status}
                        </div>
                    )}

                    <form onSubmit={submit}>
                        {/* Campo de email */}
                        <FormField label="Correo electrónico" error={errors.email} required>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                autoComplete="off"
                                value={data.email}
                                autoFocus
                                onChange={(e) => setData('email', e.target.value)}
                                placeholder={PLACEHOLDERS.email}
                            />
                        </FormField>
                    </form>
                </CardContent>

                <CardFooter className="flex-col gap-4">
                    {/* Botón de envío */}
                    <Button className="w-full" onClick={submit} disabled={processing}>
                        {processing ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <Send className="mr-2 h-4 w-4" />}
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
