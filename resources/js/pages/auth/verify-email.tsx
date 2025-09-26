// Components
import { Head, useForm } from '@inertiajs/react';
import { CheckCircle, Loader2, LogOut, Send } from 'lucide-react';
import { FormEventHandler } from 'react';

import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/auth-layout';

/**
 * Página de verificación de correo electrónico
 * Permite a los usuarios verificar su dirección de email y reenviar enlaces de verificación
 */
export default function VerifyEmail({ status }: { status?: string }) {
    // Hook de Inertia para manejar el formulario
    const { post, processing } = useForm({});

    /**
     * Maneja el reenvío del email de verificación
     * @param e - Evento del formulario
     */
    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('verification.send'));
    };

    return (
        <AuthLayout
            title="Verificar correo electrónico"
            description="Por favor verifica tu dirección de correo electrónico haciendo clic en el enlace que acabamos de enviarte."
        >
            <Head title="Verificación de Email" />

            {/* Mensaje de enlace enviado */}
            {status === 'verification-link-sent' && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    <CheckCircle className="mr-2 h-4 w-4" />
                    Se ha enviado un nuevo enlace de verificación a la dirección de correo electrónico que proporcionaste durante el registro.
                </div>
            )}

            <form onSubmit={submit} className="space-y-6 text-center">
                {/* Botón para reenviar email de verificación */}
                <Button disabled={processing} variant="secondary">
                    {processing ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <Send className="mr-2 h-4 w-4" />}
                    {processing ? 'Reenviando...' : 'Reenviar email de verificación'}
                </Button>

                {/* Enlace para cerrar sesión */}
                <TextLink href={route('logout')} method="post" className="mx-auto block text-sm">
                    <LogOut className="mr-1 h-4 w-4" />
                    Cerrar sesión
                </TextLink>
            </form>
        </AuthLayout>
    );
}
