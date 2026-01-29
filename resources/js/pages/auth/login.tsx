import { Head, useForm } from '@inertiajs/react';
import { CheckCircle, Eye, EyeOff, Loader2 } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { AUTOCOMPLETE, PLACEHOLDERS } from '@/constants/ui-constants';

/**
 * Tipo para el formulario de login
 */
type LoginForm = {
    email: string;
    password: string;
    remember: boolean;
};

/**
 * Props de la pagina de login
 */
interface LoginProps {
    status?: string;
    canResetPassword: boolean;
}

type LoginType = 'admin' | 'restaurant';

/**
 * Pagina de inicio de sesion con tabs para Administrativo y Restaurante
 */
export default function Login({ status, canResetPassword }: LoginProps) {
    const [showPassword, setShowPassword] = useState(false);
    const [loginType, setLoginType] = useState<LoginType>('restaurant');

    const { data, setData, post, processing, errors, reset, clearErrors } = useForm<Required<LoginForm>>({
        email: '',
        password: '',
        remember: false,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        const routeName = loginType === 'admin' ? 'login' : 'restaurant.login.store';
        post(route(routeName), {
            onFinish: () => reset('password'),
        });
    };

    const handleLoginTypeChange = (type: LoginType) => {
        setLoginType(type);
        clearErrors();
    };

    return (
        <div className="bg-muted flex min-h-svh flex-col items-center justify-center p-6 md:p-10">
            <Head title="Iniciar Sesión" />
            <div className="w-full max-w-sm md:max-w-4xl">
                <div className="flex flex-col gap-6">
                    <Card className="overflow-hidden p-0">
                        <CardContent className="grid p-0 md:grid-cols-2">
                            <form className="p-6 md:p-8 flex items-center" onSubmit={submit}>
                                <div className="flex flex-col gap-6 w-full">
                                    {/* Tabs para seleccionar tipo de login */}
                                    <Tabs value={loginType} onValueChange={(v) => handleLoginTypeChange(v as LoginType)} className="w-full">
                                        <TabsList className="grid w-full grid-cols-2">
                                            <TabsTrigger value="restaurant">Restaurante</TabsTrigger>
                                            <TabsTrigger value="admin">Administrativo</TabsTrigger>
                                        </TabsList>
                                    </Tabs>

                                    {/* Campo Email */}
                                    <div className="grid gap-2">
                                        <Label htmlFor="email">Correo electrónico</Label>
                                        <Input
                                            id="email"
                                            type="email"
                                            autoFocus
                                            tabIndex={1}
                                            autoComplete={AUTOCOMPLETE.email}
                                            value={data.email}
                                            onChange={(e) => setData('email', e.target.value)}
                                            placeholder={PLACEHOLDERS.email}
                                            className={errors.email ? 'border-destructive' : ''}
                                        />
                                        {errors.email && (
                                            <p className="text-sm text-destructive">{errors.email}</p>
                                        )}
                                    </div>

                                    {/* Campo Password */}
                                    <div className="grid gap-2">
                                        <div className="flex items-center">
                                            <Label htmlFor="password">Contraseña</Label>
                                            {loginType === 'admin' && canResetPassword && (
                                                <TextLink
                                                    href={route('password.request')}
                                                    className="ml-auto text-sm underline-offset-2 hover:underline"
                                                >
                                                    ¿Olvidaste tu contraseña?
                                                </TextLink>
                                            )}
                                        </div>
                                        <div className="relative">
                                            <Input
                                                id="password"
                                                type={showPassword ? 'text' : 'password'}
                                                tabIndex={2}
                                                autoComplete={AUTOCOMPLETE.currentPassword}
                                                value={data.password}
                                                onChange={(e) => setData('password', e.target.value)}
                                                placeholder={PLACEHOLDERS.password}
                                                className={`pr-10 ${errors.password ? 'border-destructive' : ''}`}
                                            />
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                className="absolute top-1 right-1 h-8 w-8 p-0"
                                                onClick={() => setShowPassword(!showPassword)}
                                                tabIndex={-1}
                                            >
                                                {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                            </Button>
                                        </div>
                                        {errors.password && (
                                            <p className="text-sm text-destructive">{errors.password}</p>
                                        )}
                                    </div>

                                    {/* Checkbox Recordar */}
                                    <div className="flex items-center space-x-2">
                                        <Checkbox
                                            id="remember"
                                            checked={data.remember}
                                            onCheckedChange={(checked) => setData('remember', checked as boolean)}
                                            tabIndex={3}
                                        />
                                        <Label htmlFor="remember" className="text-sm font-normal cursor-pointer">
                                            Recordar sesión
                                        </Label>
                                    </div>

                                    {/* Botón Submit */}
                                    <Button
                                        type="submit"
                                        className={`w-full ${loginType === 'restaurant' ? 'bg-orange-500 hover:bg-orange-600' : ''}`}
                                        disabled={processing}
                                        tabIndex={4}
                                    >
                                        {processing ? (
                                            <>
                                                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                                Iniciando sesión...
                                            </>
                                        ) : (
                                            'Iniciar Sesión'
                                        )}
                                    </Button>

                                    {/* Mensaje de estado */}
                                    {status && (
                                        <div className="flex items-center justify-center gap-2 text-sm font-medium text-green-600">
                                            <CheckCircle className="h-4 w-4" />
                                            {status}
                                        </div>
                                    )}

                                    {/* Nota */}
                                    <p className="text-center text-sm text-muted-foreground mt-auto">
                                        Si no tienes acceso, contacta al administrador.
                                    </p>
                                </div>
                            </form>

                            {/* Imagen */}
                            <div className="bg-muted relative hidden md:block min-h-[500px]">
                                <img
                                    src="/login-image.jpg"
                                    alt="Subway Guatemala"
                                    className="absolute inset-0 h-full w-full object-cover"
                                />
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    );
}
