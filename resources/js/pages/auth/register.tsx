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
 * Tipo para el formulario de registro
 */
type RegisterForm = {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
};

/**
 * Página de registro de usuarios
 */
export default function Register() {
    // Hook de Inertia para manejar el formulario
    const { data, setData, post, processing, errors, reset } = useForm<Required<RegisterForm>>({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    /**
     * Maneja el envío del formulario
     */
    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <AuthLayout 
            title="Registro" 
            description="Crea tu cuenta"
        >
            <Head title="Registro" />
            
            <Card className="w-full max-w-sm">
                <CardHeader className="text-center">
                    <CardTitle>Registro</CardTitle>
                </CardHeader>

                <CardContent>
                    <form onSubmit={submit}>
                        <div className="flex flex-col gap-6">
                            {/* Campo de nombre completo */}
                            <div className="grid gap-2">
                                <Label htmlFor="name">
                                    <i className="fas fa-user mr-2 text-muted-foreground"></i>
                                    Nombre completo
                                </Label>
                                <Input
                                    id="name"
                                    type="text"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    disabled={processing}
                                    placeholder="Tu nombre completo"
                                />
                                <InputError message={errors.name} className="mt-2" />
                            </div>

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
                                    tabIndex={2}
                                    autoComplete="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    disabled={processing}
                                    placeholder="correo@ejemplo.com"
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
                                    tabIndex={3}
                                    autoComplete="new-password"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    disabled={processing}
                                    placeholder="Tu contraseña"
                                />
                                <InputError message={errors.password} />
                            </div>

                            {/* Campo de confirmación de contraseña */}
                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation">
                                    <i className="fas fa-shield-alt mr-2 text-muted-foreground"></i>
                                    Confirmar contraseña
                                </Label>
                                <Input
                                    id="password_confirmation"
                                    type="password"
                                    name="password_confirmation"
                                    autoComplete="new-password"
                                    value={data.password_confirmation}
                                    onChange={(e) => setData('password_confirmation', e.target.value)}
                                    disabled={processing}
                                    placeholder="Confirma tu contraseña"
                                />
                                <InputError message={errors.password_confirmation} className="mt-2" />
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
                            <i className="fas fa-user-plus mr-2"></i>
                        )}
                        {processing ? 'Creando cuenta...' : 'Crear cuenta'}
                    </Button>

                    {/* Enlaces de navegación */}
                    <div className="text-center text-sm text-muted-foreground">
                        <TextLink href={route('login')} className="hover:text-foreground">
                            ¿Ya tienes cuenta? Inicia sesión
                        </TextLink>
                    </div>
                </CardFooter>
            </Card>
        </AuthLayout>
    );
}
