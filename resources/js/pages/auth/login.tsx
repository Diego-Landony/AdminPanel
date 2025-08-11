import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import AuthLayout from '@/layouts/auth-layout';

/**
 * Tipo para el formulario de login
 */
type LoginForm = {
    email: string;
    password: string;
    remember: boolean;
};

/**
 * Props de la página de login
 */
interface LoginProps {
    status?: string;
    canResetPassword: boolean;
}

/**
 * Página de inicio de sesión
 */
export default function Login({ status, canResetPassword }: LoginProps) {
    // Hook de Inertia para manejar el formulario
    const { data, setData, post, processing, errors, reset } = useForm<Required<LoginForm>>({
        email: '',
        password: '',
        remember: false,
    });

    /**
     * Maneja el envío del formulario
     */
    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <AuthLayout 
            title="Inicia sesión" 
            description="Accede a tu cuenta"
        >
            <Head title="Iniciar Sesión" />

            <Card className="w-full max-w-sm">
                <CardHeader className="text-center">
                    <CardTitle>Inicia sesión</CardTitle>
                </CardHeader>

                <CardContent>
                    <form onSubmit={submit}>
                        <div className="flex flex-col gap-6">
                            {/* Campo de correo electrónico */}
                            <div className="grid gap-2">
                                <Label htmlFor="email">
                                    <i className="fas fa-envelope mr-2 text-muted-foreground"></i>
                                    Correo electrónico
                                </Label>
                                <Input
                                    id="email"
                                    type="email"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    placeholder="usuario@email.com"
                                />
                                <InputError message={errors.email} />
                            </div>

                            {/* Campo de contraseña */}
                            <div className="grid gap-2">
                                <Label htmlFor="password">
                                    <i className="fas fa-lock mr-2 text-muted-foreground"></i>
                                    Contraseña
                                </Label>
                                <Input
                                    id="password"
                                    type="password"
                                    required
                                    tabIndex={2}
                                    autoComplete="current-password"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    placeholder="Tu contraseña"
                                />
                                <InputError message={errors.password} />
                            </div>

                            {/* Checkbox de recordar sesión */}
                            <div className="flex items-center space-x-3">
                                <Checkbox
                                    id="remember"
                                    name="remember"
                                    checked={data.remember}
                                    onClick={() => setData('remember', !data.remember)}
                                    tabIndex={3}
                                />
                                <Label htmlFor="remember">Recordar sesión</Label>
                            </div>
                        </div>
                    </form>
                </CardContent>

                <CardFooter className="flex-col gap-4">
                    {/* Botón de envío */}
                    <Button type="submit" className="w-full" onClick={submit} disabled={processing}>
                        {processing ? (
                            <i className="fas fa-spinner fa-spin mr-2"></i>
                        ) : (
                            <i className="fas fa-sign-in-alt mr-2"></i>
                        )}
                        {processing ? 'Iniciando sesión...' : 'Iniciar Sesión'}
                    </Button>

                    {/* Mensaje de estado */}
                    {status && (
                        <div className="text-center text-sm font-medium text-green-600">
                            <i className="fas fa-check-circle mr-2"></i>
                            {status}
                        </div>
                    )}

                    {/* Enlaces de navegación */}
                    <div className="flex flex-col gap-2 text-center text-sm text-muted-foreground">
                        {canResetPassword && (
                            <TextLink href={route('password.request')} className="hover:text-foreground">
                                ¿Olvidaste tu contraseña?
                            </TextLink>
                        )}
                        <TextLink href={route('register')} className="hover:text-foreground">
                            ¿No tienes cuenta? Regístrate
                        </TextLink>
                    </div>
                </CardFooter>
            </Card>
        </AuthLayout>
    );
}
