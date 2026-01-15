import { Head, useForm } from '@inertiajs/react';
import { Building2, CheckCircle, Eye, EyeOff, Loader2, LogIn, Shield, Store } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { AUTOCOMPLETE, PLACEHOLDERS } from '@/constants/ui-constants';
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
    const [loginType, setLoginType] = useState<LoginType>('admin');

    // Hook de Inertia para manejar el formulario
    const { data, setData, post, processing, errors, reset, clearErrors } = useForm<Required<LoginForm>>({
        email: '',
        password: '',
        remember: false,
    });

    /**
     * Maneja el envio del formulario
     */
    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        const routeName = loginType === 'admin' ? 'login' : 'restaurant.login';

        post(route(routeName), {
            onFinish: () => reset('password'),
        });
    };

    /**
     * Cambia el tipo de login y limpia errores
     */
    const handleLoginTypeChange = (type: LoginType) => {
        setLoginType(type);
        clearErrors();
    };

    return (
        <AuthLayout
            title={loginType === 'admin' ? 'Panel Administrativo' : 'Panel de Restaurante'}
            description={loginType === 'admin' ? 'Acceso para administradores' : 'Acceso para restaurantes'}
        >
            <Head title="Iniciar Sesion" />

            <Card className="w-full max-w-sm">
                <CardHeader className="space-y-4 text-center">
                    {/* Tabs para seleccionar tipo de login */}
                    <Tabs value={loginType} onValueChange={(v) => handleLoginTypeChange(v as LoginType)} className="w-full">
                        <TabsList className="grid w-full grid-cols-2">
                            <TabsTrigger value="admin" className="flex items-center gap-2">
                                <Shield className="h-4 w-4" />
                                <span className="hidden sm:inline">Administrativo</span>
                                <span className="sm:hidden">Admin</span>
                            </TabsTrigger>
                            <TabsTrigger value="restaurant" className="flex items-center gap-2">
                                <Store className="h-4 w-4" />
                                <span>Restaurante</span>
                            </TabsTrigger>
                        </TabsList>
                    </Tabs>

                    <CardTitle className="flex items-center justify-center gap-2">
                        {loginType === 'admin' ? (
                            <>
                                <Shield className="h-5 w-5 text-primary" />
                                Panel Administrativo
                            </>
                        ) : (
                            <>
                                <Building2 className="h-5 w-5 text-orange-500" />
                                Panel de Restaurante
                            </>
                        )}
                    </CardTitle>
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
                                    placeholder={PLACEHOLDERS.email}
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
                                <Label htmlFor="remember">Recordar sesión</Label>
                            </div>
                        </div>
                    </form>
                </CardContent>

                <CardFooter className="flex-col gap-4">
                    {/* Boton de envio */}
                    <Button
                        type="submit"
                        className={`w-full ${loginType === 'restaurant' ? 'bg-orange-500 hover:bg-orange-600' : ''}`}
                        onClick={submit}
                        disabled={processing}
                    >
                        {processing ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <LogIn className="mr-2 h-4 w-4" />}
                        {processing ? 'Iniciando sesión...' : 'Iniciar Sesión'}
                    </Button>

                    {/* Mensaje de estado */}
                    {status && (
                        <div className="flex items-center justify-center text-sm font-medium text-green-600">
                            <CheckCircle className="mr-2 h-4 w-4" />
                            {status}
                        </div>
                    )}

                    {/* Enlaces de navegación - solo para admin */}
                    {loginType === 'admin' && (
                        <div className="flex flex-col gap-2 text-center text-sm text-muted-foreground">
                            {canResetPassword && (
                                <TextLink href={route('password.request')} className="hover:text-foreground">
                                    ¿Olvidaste tu contraseña?
                                </TextLink>
                            )}
                        </div>
                    )}

                    {/* Nota para restaurantes */}
                    {loginType === 'restaurant' && (
                        <p className="text-center text-xs text-muted-foreground">
                            Si no tienes acceso, contacta al administrador responsable.
                        </p>
                    )}
                </CardFooter>
            </Card>
        </AuthLayout>
    );
}
