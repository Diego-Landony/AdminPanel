import { Head, useForm } from '@inertiajs/react';
import { CheckCircle, Eye, EyeOff, Loader2, LogIn } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { PLACEHOLDERS, AUTOCOMPLETE } from '@/constants/ui-constants';

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
    const [showPassword, setShowPassword] = useState(false);

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
            // Los mensajes de éxito/error se manejan automáticamente por el layout
        });
    };

    return (
        <AuthLayout title="Inicia sesión" description="Accede a tu cuenta">
            <Head title="Iniciar Sesión" />

            <Card className="w-full max-w-sm">
                <CardHeader className="text-center">
                    <CardTitle>Inicia sesión</CardTitle>
                </CardHeader>

                <CardContent>
                    <form onSubmit={submit}>
                        <div className="flex flex-col gap-6">
                            {/* Campo de correo electrónico */}
                            <FormField label="Correo electrónico" error={errors.email} required>
                                <Input
                                    id="email"
                                    type="email"
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete={AUTOCOMPLETE.email}
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    onKeyDown={(e) => {
                                        if (e.key === 'Enter' && data.email && data.password) {
                                            e.preventDefault();
                                            submit(e);
                                        }
                                    }}
                                    placeholder={PLACEHOLDERS.authEmail}
                                />
                            </FormField>

                            {/* Campo de contraseña */}
                            <FormField label="Contraseña" error={errors.password} required>
                                <div className="relative">
                                    <Input
                                        id="password"
                                        type={showPassword ? 'text' : 'password'}
                                        tabIndex={2}
                                        autoComplete={AUTOCOMPLETE.currentPassword}
                                        value={data.password}
                                        onChange={(e) => setData('password', e.target.value)}
                                        onKeyDown={(e) => {
                                            if (e.key === 'Enter' && data.email && data.password) {
                                                e.preventDefault();
                                                submit(e);
                                            }
                                        }}
                                        placeholder={PLACEHOLDERS.authPassword}
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
                        {processing ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <LogIn className="mr-2 h-4 w-4" />}
                        {processing ? 'Iniciando sesión...' : 'Iniciar Sesión'}
                    </Button>

                    {/* Mensaje de estado */}
                    {status && (
                        <div className="text-center text-sm font-medium text-green-600">
                            <CheckCircle className="mr-2 h-4 w-4" />
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
