import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Percent, ArrowLeft } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';

export default function EditPercentage() {
    return (
        <AppLayout>
            <Head title="Editar Promoción de Porcentaje" />

            <div className="flex items-center justify-center min-h-[60vh]">
                <div className="text-center max-w-md px-4">
                    <div className="flex justify-center mb-6">
                        <div className="bg-green-100 dark:bg-green-950/20 p-4 rounded-full">
                            <Percent className="h-12 w-12 text-green-600 dark:text-green-400" />
                        </div>
                    </div>

                    <h1 className="text-2xl font-bold mb-3">Editar Promoción de Porcentaje</h1>
                    <p className="text-muted-foreground mb-6">
                        Esta funcionalidad estará disponible próximamente.
                    </p>

                    <Button asChild variant="outline">
                        <Link href="/menu/promotions/percentage">
                            <ArrowLeft className="h-4 w-4 mr-2" />
                            Volver a Porcentaje
                        </Link>
                    </Button>
                </div>
            </div>
        </AppLayout>
    );
}
