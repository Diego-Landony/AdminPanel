import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import { HomeSkeleton } from '@/components/skeletons';
import { useState } from 'react';

/**
 * Breadcrumbs para la navegación de la página de inicio
 */
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Inicio',
        href: '/home',
    },
];

/**
 * Página principal de inicio
 * Primera página después del login
 */
export default function Home() {
    const [isLoading] = useState(false);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Inicio" />
            
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                {isLoading ? (
                    <HomeSkeleton />
                ) : (
                    <>
                        {/* Título de la página */}
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">Inicio</h1>
                            <p className="text-muted-foreground">
                                Bienvenido
                            </p>
                        </div>

                        {/* Contenido principal con patrón de rayas */}
                        <div className="relative min-h-[100vh] flex-1 overflow-hidden rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
                            <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                            <div className="absolute inset-0 flex items-center justify-center">
                                <div className="text-center text-gray-500">
                                    <p>Hola</p>
                                </div>
                            </div>
                        </div>
                    </>
                )}
            </div>
        </AppLayout>
    );
}
