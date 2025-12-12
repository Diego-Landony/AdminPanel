import { Head, Link } from '@inertiajs/react';

import { ACTIVE_STATUS_CONFIGS, StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { CURRENCY } from '@/constants/ui-constants';
import AppLayout from '@/layouts/app-layout';
import { ArrowLeft, Check, ListChecks, Package, X } from 'lucide-react';

interface SectionOption {
    id: number;
    name: string;
    is_extra: boolean;
    price_modifier: number;
    sort_order: number;
}

interface Product {
    id: number;
    name: string;
    description: string | null;
    is_active: boolean;
}

interface Section {
    id: number;
    title: string;
    description: string | null;
    is_required: boolean;
    allow_multiple: boolean;
    min_selections: number;
    max_selections: number;
    is_active: boolean;
    sort_order: number;
    options: SectionOption[];
    products: Product[];
}

interface ShowSectionProps {
    section: Section;
}

export default function ShowSection({ section }: ShowSectionProps) {
    return (
        <AppLayout>
            <Head title={`Sección: ${section.title}`} />

            <div className="mx-auto max-w-5xl space-y-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Link href="/menu/sections">
                            <Button variant="ghost" size="icon">
                                <ArrowLeft className="h-5 w-5" />
                            </Button>
                        </Link>
                        <div>
                            <h1 className="text-2xl font-bold text-foreground">{section.title}</h1>
                            {section.description && <p className="text-sm text-muted-foreground">{section.description}</p>}
                        </div>
                    </div>
                    <StatusBadge status={section.is_active ? 'active' : 'inactive'} configs={ACTIVE_STATUS_CONFIGS} />
                </div>

                {/* Section Details */}
                <div className="rounded-lg border bg-card p-6">
                    <h2 className="mb-4 flex items-center gap-2 text-lg font-semibold">
                        <ListChecks className="h-5 w-5 text-primary" />
                        Configuración
                    </h2>
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div className="flex items-center justify-between rounded-lg border p-3">
                            <span className="text-sm font-medium">Requerida</span>
                            {section.is_required ? <Check className="h-5 w-5 text-green-600" /> : <X className="h-5 w-5 text-gray-400" />}
                        </div>
                        <div className="flex items-center justify-between rounded-lg border p-3">
                            <span className="text-sm font-medium">Permite múltiples</span>
                            {section.allow_multiple ? <Check className="h-5 w-5 text-green-600" /> : <X className="h-5 w-5 text-gray-400" />}
                        </div>
                        <div className="flex items-center justify-between rounded-lg border p-3">
                            <span className="text-sm font-medium">Mínimo de selecciones</span>
                            <span className="font-mono text-sm font-semibold">{section.min_selections}</span>
                        </div>
                        <div className="flex items-center justify-between rounded-lg border p-3">
                            <span className="text-sm font-medium">Máximo de selecciones</span>
                            <span className="font-mono text-sm font-semibold">{section.max_selections}</span>
                        </div>
                    </div>
                </div>

                {/* Section Options */}
                {section.options && section.options.length > 0 && (
                    <div className="rounded-lg border bg-card p-6">
                        <h2 className="mb-4 flex items-center gap-2 text-lg font-semibold">
                            <ListChecks className="h-5 w-5 text-primary" />
                            Opciones ({section.options.length})
                        </h2>
                        <div className="space-y-2">
                            {section.options.map((option) => (
                                <div key={option.id} className="flex items-center justify-between rounded-lg border p-3">
                                    <div>
                                        <span className="font-medium">{option.name}</span>
                                        {option.is_extra && (
                                            <span className="ml-2 text-sm text-muted-foreground">
                                                (+{CURRENCY.symbol}{Number(option.price_modifier).toFixed(2)})
                                            </span>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* Associated Products */}
                {section.products && section.products.length > 0 && (
                    <div className="rounded-lg border bg-card p-6">
                        <h2 className="mb-4 flex items-center gap-2 text-lg font-semibold">
                            <Package className="h-5 w-5 text-primary" />
                            Productos Asociados ({section.products.length})
                        </h2>
                        <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
                            {section.products.map((product) => (
                                <div key={product.id} className="flex items-center justify-between rounded-lg border p-3">
                                    <div>
                                        <div className="font-medium">{product.name}</div>
                                        {product.description && <div className="text-sm text-muted-foreground">{product.description}</div>}
                                    </div>
                                    <StatusBadge status={product.is_active ? 'active' : 'inactive'} configs={ACTIVE_STATUS_CONFIGS} showIcon={false} />
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* Actions */}
                <div className="flex justify-end gap-3">
                    <Link href="/menu/sections">
                        <Button variant="outline">Volver</Button>
                    </Link>
                    <Link href={`/menu/sections/${section.id}/edit`}>
                        <Button>Editar</Button>
                    </Link>
                </div>
            </div>
        </AppLayout>
    );
}
