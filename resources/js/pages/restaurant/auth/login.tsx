import { Head, useForm } from '@inertiajs/react';
import { Eye, EyeOff, Loader2, LogIn, Store } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import { ThemeToggle } from '@/components/theme-toggle';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { AUTOCOMPLETE, PLACEHOLDERS } from '@/constants/ui-constants';

/**
 * Tipo para el formulario de login de restaurante
 */
type RestaurantLoginForm = {
    email: string;
    password: string;
    remember: boolean;
};

/**
 * Pagina de inicio de sesion para usuarios de restaurante
 */
export default function RestaurantLogin() {
    const [showPassword, setShowPassword] = useState(false);

    // Hook de Inertia para manejar el formulario
    const { data, setData, post, processing, errors, reset } = useForm<Required<RestaurantLoginForm>>({
        email: '',
        password: '',
        remember: false,
    });

    /**
     * Maneja el envio del formulario
     */
    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('restaurant.login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <>
            <Head title="Panel de Restaurante - Iniciar Sesion" />

            <div className="flex min-h-svh flex-col items-center justify-center bg-background p-6 md:p-10">
                {/* Toggle de tema en la esquina superior derecha */}
                <div className="absolute top-4 right-4">
                    <ThemeToggle />
                </div>

                <div className="w-full max-w-sm">
                    <div className="flex flex-col gap-6">
                        {/* Logo y titulo del panel */}
                        <div className="flex flex-col items-center gap-4">
                            <div className="flex h-14 w-14 items-center justify-center rounded-xl bg-orange-500 text-white">
                                <Store className="h-8 w-8" />
                            </div>
                            <div className="text-center">
                                <h1 className="text-xl font-semibold text-foreground">Panel de Restaurante</h1>
                                <p className="text-sm text-muted-foreground">Gestiona tus ordenes y operaciones</p>
                            </div>
                        </div>

                        <Card className="w-full">
                            <CardHeader className="text-center">
                                <CardTitle className="text-lg">Iniciar Sesion</CardTitle>
                                <CardDescription>Ingresa tus credenciales para acceder</CardDescription>
                            </CardHeader>

                            <CardContent>
                                <form onSubmit={submit}>
                                    <div className="flex flex-col gap-5">
                                        {/* Campo de correo electronico */}
                                        <FormField label="Correo electronico" error={errors.email} required>
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
                                                placeholder={PLACEHOLDERS.email}
                                            />
                                        </FormField>

                                        {/* Campo de contrasena */}
                                        <FormField label="Contrasena" error={errors.password} required>
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
                                                    placeholder={PLACEHOLDERS.password}
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

                                        {/* Checkbox de recordar sesion */}
                                        <div className="flex items-center space-x-3">
                                            <Checkbox
                                                id="remember"
                                                name="remember"
                                                checked={data.remember}
                                                onClick={() => setData('remember', !data.remember)}
                                                tabIndex={3}
                                            />
                                            <Label htmlFor="remember">Recordar sesion</Label>
                                        </div>
                                    </div>
                                </form>
                            </CardContent>

                            <CardFooter>
                                {/* Boton de envio */}
                                <Button
                                    type="submit"
                                    className="w-full bg-orange-500 hover:bg-orange-600"
                                    onClick={submit}
                                    disabled={processing}
                                >
                                    {processing ? (
                                        <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                    ) : (
                                        <LogIn className="mr-2 h-4 w-4" />
                                    )}
                                    {processing ? 'Iniciando sesion...' : 'Iniciar Sesion'}
                                </Button>
                            </CardFooter>
                        </Card>

                        {/* Nota informativa */}
                        <p className="text-center text-xs text-muted-foreground">
                            Si no tienes acceso, contacta al administrador del sistema.
                        </p>
                    </div>
                </div>
            </div>
        </>
    );
}
