import { Head, useForm } from '@inertiajs/react';
import { Eye, EyeOff, Loader2, UserPlus } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import AuthLayout from '@/layouts/auth-layout';
import { PLACEHOLDERS, AUTOCOMPLETE } from '@/constants/ui-constants';

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
    const [showPassword, setShowPassword] = useState(false);
    const [showPasswordConfirmation, setShowPasswordConfirmation] = useState(false);

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
            // Los mensajes de éxito/error se manejan automáticamente por el layout
        });
    };

    return (
        <AuthLayout title="Registro" description="Crea tu cuenta">
            <Head title="Registro" />

            <Card className="w-full max-w-sm">
                <CardHeader className="text-center">
                    <CardTitle>Registro</CardTitle>
                </CardHeader>

                <CardContent>
                    <form onSubmit={submit}>
                        <div className="flex flex-col gap-6">
                            {/* Campo de nombre completo */}
                            <FormField label="Nombre completo" error={errors.name} required>
                                <Input
                                    id="name"
                                    type="text"
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete={AUTOCOMPLETE.name}
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    disabled={processing}
                                    placeholder={PLACEHOLDERS.authName}
                                />
                            </FormField>

                            {/* Campo de correo electrónico */}
                            <FormField label="Correo electrónico" error={errors.email} required>
                                <Input
                                    id="email"
                                    type="email"
                                    tabIndex={2}
                                    autoComplete={AUTOCOMPLETE.email}
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    disabled={processing}
                                    placeholder={PLACEHOLDERS.email}
                                />
                            </FormField>

                            {/* Campo de contraseña */}
                            <FormField label="Contraseña" error={errors.password} required>
                                <div className="relative">
                                    <Input
                                        id="password"
                                        type={showPassword ? 'text' : 'password'}
                                        tabIndex={3}
                                        autoComplete={AUTOCOMPLETE.newPassword}
                                        value={data.password}
                                        onChange={(e) => setData('password', e.target.value)}
                                        disabled={processing}
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

                            {/* Campo de confirmación de contraseña */}
                            <FormField label="Confirmar contraseña" error={errors.password_confirmation} required>
                                <div className="relative">
                                    <Input
                                        id="password_confirmation"
                                        type={showPasswordConfirmation ? 'text' : 'password'}
                                        name="password_confirmation"
                                        autoComplete={AUTOCOMPLETE.newPassword}
                                        value={data.password_confirmation}
                                        onChange={(e) => setData('password_confirmation', e.target.value)}
                                        disabled={processing}
                                        placeholder={PLACEHOLDERS.authPasswordConfirm}
                                        className="pr-10"
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
                        </div>
                    </form>
                </CardContent>

                <CardFooter className="flex-col gap-4">
                    {/* Botón de envío */}
                    <Button type="submit" className="w-full" onClick={submit} disabled={processing}>
                        {processing ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <UserPlus className="mr-2 h-4 w-4" />}
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
