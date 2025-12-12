import { PLACEHOLDERS } from '@/constants/ui-constants';
import { showNotification } from '@/hooks/useNotifications';
import { useForm } from '@inertiajs/react';
import React from 'react';

import { CreatePageLayout } from '@/components/create-page-layout';
import { FormSection } from '@/components/form-section';
import { CreateProductsSkeleton } from '@/components/skeletons';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { ENTITY_ICONS } from '@/constants/section-icons';
import { NOTIFICATIONS } from '@/constants/ui-constants';
import { Banknote, Layers } from 'lucide-react';

interface Category {
    id: number;
    name: string;
}

interface Product {
    id: number;
    name: string;
}

interface CreateVariantPageProps {
    product: Product;
    categories: Category[];
}

/**
 * Página para crear una variante de producto
 */
export default function VariantCreate({ product, categories }: CreateVariantPageProps) {
    const { data, setData, post, processing, errors, reset } = useForm({
        product_id: product.id,
        category_id: '',
        sku: '',
        base_price: '',
        delivery_price: '',
        interior_base_price: '',
        interior_delivery_price: '',
        is_active: true,
        sort_order: '',
    });

    /**
     * Maneja el envío del formulario
     */
    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        // Enviar directamente - Laravel convertirá los strings a números
        post(route('menu.products.variants.store', product.id), {
            onSuccess: () => {
                reset();
            },
            onError: (errors: Record<string, string>) => {
                if (Object.keys(errors).length === 0) {
                    showNotification.error(NOTIFICATIONS.error.server);
                }
            },
        });
    };

    return (
        <CreatePageLayout
            title="Nueva Variante"
            backHref={route('menu.products.variants.index', product.id)}
            backLabel="Volver"
            onSubmit={handleSubmit}
            submitLabel="Guardar"
            processing={processing}
            pageTitle={`Crear Variante para ${product.name}`}
            loading={processing}
            loadingSkeleton={CreateProductsSkeleton}
        >
            <FormSection icon={Layers} title="Información de la Variante">
                {/* Categoría */}
                <FormField label="Categoría (Tamaño)" error={errors.category_id} required>
                    <Select value={data.category_id} onValueChange={(value) => setData('category_id', value)}>
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {categories.map((category) => (
                                <SelectItem key={category.id} value={category.id.toString()}>
                                    <div className="flex items-center gap-2">
                                        <Layers className="h-4 w-4" />
                                        {category.name}
                                    </div>
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <p className="mt-1 text-xs text-muted-foreground">Cada producto puede tener solo una variante por categoría.</p>
                </FormField>

                {/* SKU */}
                <FormField label="SKU (Código único)" error={errors.sku}>
                    <Input id="sku" type="text" value={data.sku} onChange={(e) => setData('sku', e.target.value)} placeholder={PLACEHOLDERS.sku} />
                    <p className="mt-1 text-xs text-muted-foreground">
                        Identificador único de la variante. Se generará automáticamente combinando el producto y la categoría si no se especifica.
                    </p>
                </FormField>

                {/* Orden */}
                <FormField label="Orden" error={errors.sort_order}>
                    <Input
                        id="sort_order"
                        type="number"
                        min="0"
                        value={data.sort_order}
                        onChange={(e) => setData('sort_order', e.target.value)}
                        placeholder={PLACEHOLDERS.sortOrder}
                    />
                </FormField>
            </FormSection>

            <FormSection icon={Banknote} title="Precios Base (4 Tipos)">
                <p className="mb-4 text-sm text-muted-foreground">
                    Define los 4 tipos de precios para esta variante. Estos son los precios base antes de aplicar promociones.
                </p>
                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                    {/* Precio Base (Pickup) */}
                    <FormField label="Precio Pickup" error={errors.base_price} required>
                        <div className="relative">
                            <span className="absolute top-3 left-3 text-sm text-muted-foreground">Q</span>
                            <Input
                                id="base_price"
                                type="number"
                                min="0"
                                step="0.01"
                                value={data.base_price}
                                onChange={(e) => setData('base_price', e.target.value)}
                                className="pl-8"
                                placeholder={PLACEHOLDERS.price}
                            />
                        </div>
                        <p className="mt-1 text-xs text-muted-foreground">Precio para recoger en tienda (capital)</p>
                    </FormField>

                    {/* Precio Domicilio */}
                    <FormField label="Precio Domicilio" error={errors.delivery_price} required>
                        <div className="relative">
                            <span className="absolute top-3 left-3 text-sm text-muted-foreground">Q</span>
                            <Input
                                id="delivery_price"
                                type="number"
                                min="0"
                                step="0.01"
                                value={data.delivery_price}
                                onChange={(e) => setData('delivery_price', e.target.value)}
                                className="pl-8"
                                placeholder={PLACEHOLDERS.price}
                            />
                        </div>
                        <p className="mt-1 text-xs text-muted-foreground">Precio para entrega a domicilio (capital)</p>
                    </FormField>

                    {/* Precio Interior Pickup */}
                    <FormField label="Precio Interior Pickup" error={errors.interior_base_price} required>
                        <div className="relative">
                            <span className="absolute top-3 left-3 text-sm text-muted-foreground">Q</span>
                            <Input
                                id="interior_base_price"
                                type="number"
                                min="0"
                                step="0.01"
                                value={data.interior_base_price}
                                onChange={(e) => setData('interior_base_price', e.target.value)}
                                className="pl-8"
                                placeholder={PLACEHOLDERS.price}
                            />
                        </div>
                        <p className="mt-1 text-xs text-muted-foreground">Precio para recoger en tienda (interior del país)</p>
                    </FormField>

                    {/* Precio Interior Domicilio */}
                    <FormField label="Precio Interior Domicilio" error={errors.interior_delivery_price} required>
                        <div className="relative">
                            <span className="absolute top-3 left-3 text-sm text-muted-foreground">Q</span>
                            <Input
                                id="interior_delivery_price"
                                type="number"
                                min="0"
                                step="0.01"
                                value={data.interior_delivery_price}
                                onChange={(e) => setData('interior_delivery_price', e.target.value)}
                                className="pl-8"
                                placeholder={PLACEHOLDERS.price}
                            />
                        </div>
                        <p className="mt-1 text-xs text-muted-foreground">Precio para entrega a domicilio (interior del país)</p>
                    </FormField>
                </div>
            </FormSection>

            <FormSection icon={ENTITY_ICONS.menu.productInfo} title="Configuración">
                <div className="flex items-center justify-between rounded-lg border p-4">
                    <div className="space-y-0.5">
                        <Label htmlFor="is_active" className="text-sm font-medium">
                            Variante Activa
                        </Label>
                        <p className="text-xs text-muted-foreground">Las variantes inactivas no se mostrarán en el menú</p>
                    </div>
                    <Switch id="is_active" checked={data.is_active} onCheckedChange={(checked) => setData('is_active', checked)} />
                </div>
            </FormSection>
        </CreatePageLayout>
    );
}
