import { showNotification } from '@/hooks/useNotifications';
import { useForm } from '@inertiajs/react';
import React, { useState } from 'react';

import { CreatePageLayout } from '@/components/create-page-layout';
import { FormSection } from '@/components/form-section';
import { CreateProductsSkeleton } from '@/components/skeletons';
import { Checkbox } from '@/components/ui/checkbox';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ENTITY_ICONS } from '@/constants/section-icons';
import { NOTIFICATIONS } from '@/constants/ui-constants';
import { Banknote, Layers, Package } from 'lucide-react';

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

        // Convertir valores string a números antes de enviar
        const submitData = {
            ...data,
            category_id: data.category_id ? parseInt(data.category_id) : null,
            base_price: data.base_price ? parseFloat(data.base_price) : 0,
            delivery_price: data.delivery_price ? parseFloat(data.delivery_price) : 0,
            interior_base_price: data.interior_base_price ? parseFloat(data.interior_base_price) : 0,
            interior_delivery_price: data.interior_delivery_price ? parseFloat(data.interior_delivery_price) : 0,
            sort_order: data.sort_order ? parseInt(data.sort_order) : 0,
        };

        post(route('menu.products.variants.store', product.id), {
            data: submitData,
            onSuccess: () => {
                reset();
            },
            onError: (errors) => {
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
            backLabel={`Volver a Variantes de ${product.name}`}
            onSubmit={handleSubmit}
            submitLabel="Crear Variante"
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
                            <SelectValue placeholder="Selecciona una categoría" />
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
                    <p className="text-xs text-muted-foreground mt-1">
                        Cada producto puede tener solo una variante por categoría.
                    </p>
                </FormField>

                {/* SKU */}
                <FormField label="SKU (Código único)" error={errors.sku}>
                    <Input
                        id="sku"
                        type="text"
                        value={data.sku}
                        onChange={(e) => setData('sku', e.target.value)}
                        placeholder="Se genera automáticamente si se deja vacío"
                    />
                    <p className="text-xs text-muted-foreground mt-1">
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
                        placeholder="0"
                    />
                </FormField>
            </FormSection>

            <FormSection icon={Banknote} title="Precios Base (4 Tipos)">
                <p className="text-sm text-muted-foreground mb-4">
                    Define los 4 tipos de precios para esta variante. Estos son los precios base antes de aplicar promociones.
                </p>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                                placeholder="0.00"
                            />
                        </div>
                        <p className="text-xs text-muted-foreground mt-1">
                            Precio para recoger en tienda (capital)
                        </p>
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
                                placeholder="0.00"
                            />
                        </div>
                        <p className="text-xs text-muted-foreground mt-1">
                            Precio para entrega a domicilio (capital)
                        </p>
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
                                placeholder="0.00"
                            />
                        </div>
                        <p className="text-xs text-muted-foreground mt-1">
                            Precio para recoger en tienda (interior del país)
                        </p>
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
                                placeholder="0.00"
                            />
                        </div>
                        <p className="text-xs text-muted-foreground mt-1">
                            Precio para entrega a domicilio (interior del país)
                        </p>
                    </FormField>
                </div>
            </FormSection>

            <FormSection icon={ENTITY_ICONS.menu.productInfo} title="Configuración">
                <div className="flex items-center space-x-2">
                    <Checkbox
                        id="is_active"
                        checked={data.is_active}
                        onCheckedChange={(checked) => setData('is_active', checked as boolean)}
                    />
                    <Label htmlFor="is_active" className="text-sm leading-none font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                        Variante activa
                    </Label>
                </div>
                <p className="text-sm text-muted-foreground mt-2">
                    Las variantes inactivas no se mostrarán en el menú del cliente.
                </p>
            </FormSection>
        </CreatePageLayout>
    );
}
