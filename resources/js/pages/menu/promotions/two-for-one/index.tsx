import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Star, ArrowLeft } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';

export default function TwoForOneIndex() {
    return (
        <AppLayout>
            <Head title="Promociones 2x1" />

            <div className="flex items-center justify-center min-h-[60vh]">
                <div className="text-center max-w-md px-4">
                    <div className="flex justify-center mb-6">
                        <div className="bg-purple-100 dark:bg-purple-950/20 p-4 rounded-full">
                            <Star className="h-12 w-12 text-purple-600 dark:text-purple-400" />
                        </div>
                    </div>

                    <h1 className="text-2xl font-bold mb-3">Promociones 2x1</h1>
                    <p className="text-muted-foreground mb-6">
                        Esta funcionalidad estará disponible próximamente. Las promociones 2x1 permitirán
                        ofrecer el producto más barato gratis al comprar dos o más productos de una categoría.
                    </p>

                    <Button asChild variant="outline">
                        <Link href="/menu/promotions">
                            <ArrowLeft className="h-4 w-4 mr-2" />
                            Volver a Promociones
                        </Link>
                    </Button>
                </div>
            </div>
        </AppLayout>
    );
}
