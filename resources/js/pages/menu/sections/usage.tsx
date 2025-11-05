import { Head, Link } from '@inertiajs/react';

import { ACTIVE_STATUS_CONFIGS, StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { ArrowLeft, Package } from 'lucide-react';

interface Category {
    id: number;
    name: string;
}

interface Product {
    id: number;
    name: string;
    description: string | null;
    is_active: boolean;
    category: Category;
}

interface Section {
    id: number;
    title: string;
    description: string | null;
    products: Product[];
}

interface UsageSectionProps {
    section: Section;
}

export default function UsageSection({ section }: UsageSectionProps) {
    return (
        <AppLayout>
            <Head title={`Uso de Sección: ${section.title}`} />

            <div className="mx-auto max-w-5xl space-y-6 p-6">
                {/* Header */}
                <div className="flex items-center gap-4">
                    <Link href="/menu/sections">
                        <Button variant="ghost" size="icon">
                            <ArrowLeft className="h-5 w-5" />
                        </Button>
                    </Link>
                    <div>
                        <h1 className="text-2xl font-bold text-foreground">Uso de Sección: {section.title}</h1>
                        {section.description && <p className="text-sm text-muted-foreground">{section.description}</p>}
                    </div>
                </div>

                {/* Products Using This Section */}
                <div className="rounded-lg border bg-card p-6">
                    <h2 className="mb-4 flex items-center gap-2 text-lg font-semibold">
                        <Package className="h-5 w-5 text-primary" />
                        Productos que usan esta sección ({section.products?.length || 0})
                    </h2>

                    {section.products && section.products.length > 0 ? (
                        <div className="space-y-3">
                            {section.products.map((product) => (
                                <div key={product.id} className="flex items-center justify-between rounded-lg border p-4">
                                    <div className="flex-1">
                                        <div className="flex items-center gap-2">
                                            <span className="font-medium">{product.name}</span>
                                            <StatusBadge status={product.is_active ? 'active' : 'inactive'} configs={ACTIVE_STATUS_CONFIGS} showIcon={false} />
                                        </div>
                                        {product.description && <p className="mt-1 text-sm text-muted-foreground">{product.description}</p>}
                                        <p className="mt-1 text-xs text-muted-foreground">Categoría: {product.category.name}</p>
                                    </div>
                                    <Link href={`/menu/products/${product.id}/edit`}>
                                        <Button variant="outline" size="sm">
                                            Ver Producto
                                        </Button>
                                    </Link>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="py-8 text-center text-muted-foreground">
                            <Package className="mx-auto mb-2 h-12 w-12 opacity-50" />
                            <p>No hay productos usando esta sección</p>
                        </div>
                    )}
                </div>

                {/* Actions */}
                <div className="flex justify-end gap-3">
                    <Link href="/menu/sections">
                        <Button variant="outline">Volver</Button>
                    </Link>
                    <Link href={`/menu/sections/${section.id}/edit`}>
                        <Button>Editar Sección</Button>
                    </Link>
                </div>
            </div>
        </AppLayout>
    );
}
