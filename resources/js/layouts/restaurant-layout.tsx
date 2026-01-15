import { RestaurantHeader } from '@/components/restaurant/restaurant-header';
import { RestaurantSidebar } from '@/components/restaurant/restaurant-sidebar';
import { Toaster } from '@/components/ui/sonner';
import { useNotifications } from '@/hooks/useNotifications';
import { RestaurantLayoutProps } from '@/types/restaurant';
import { Head } from '@inertiajs/react';
import { useState } from 'react';

/**
 * Layout principal del panel de restaurante
 * Proporciona la estructura base con sidebar, header y area de contenido
 * Soporta modo responsive con sidebar colapsable en movil
 */
export default function RestaurantLayout({ children, title }: RestaurantLayoutProps) {
    const [sidebarOpen, setSidebarOpen] = useState(false);

    // Usar el hook de notificaciones para manejar mensajes flash
    useNotifications();

    const handleMenuClick = () => {
        setSidebarOpen(!sidebarOpen);
    };

    const handleSidebarClose = () => {
        setSidebarOpen(false);
    };

    return (
        <>
            <Head title={title ? `${title} - Restaurante` : 'Panel Restaurante'} />

            <div className="flex min-h-screen bg-background">
                {/* Sidebar */}
                <RestaurantSidebar isOpen={sidebarOpen} onClose={handleSidebarClose} />

                {/* Contenido principal */}
                <div className="flex flex-1 flex-col lg:ml-0">
                    {/* Header */}
                    <RestaurantHeader onMenuClick={handleMenuClick} />

                    {/* Area de contenido */}
                    <main className="flex-1 overflow-y-auto p-4 lg:p-6">
                        {children}
                    </main>
                </div>
            </div>

            {/* Toast notifications */}
            <Toaster position="top-center" richColors closeButton />
        </>
    );
}
